<?php

function facturaDocumentoCargar(PDO $pdo, int $id): ?array
{
    $s = $pdo->prepare("
        SELECT f.*, c.nombre AS contacto_nombre, c.apellidos AS contacto_apellidos,
               c.email AS contacto_email, c.telefono AS contacto_telefono,
               c.empresa AS contacto_empresa
        FROM facturas f
        INNER JOIN contactos c ON c.id_contacto = f.contacto_id
        WHERE f.id_factura = ?
        LIMIT 1
    ");
    $s->execute([$id]);
    $factura = $s->fetch();
    if (!$factura) {
        return null;
    }

    $l = $pdo->prepare("SELECT * FROM factura_lineas WHERE factura_id = ? ORDER BY orden, id_linea");
    $l->execute([$id]);
    $factura['lineas'] = $l->fetchAll();

    return $factura;
}

function facturaDocumentoNombre(array $factura): string
{
    $numero = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $factura['numero']);
    return 'factura-' . trim($numero, '-') . '.pdf';
}

function facturaDocumentoPdf(array $factura): string
{
    $pdf = new FacturaPdfSimple();
    $pdf->addPage();

    $contacto = trim(($factura['contacto_nombre'] ?? '') . ' ' . ($factura['contacto_apellidos'] ?? ''));
    $empresa = trim((string) ($factura['contacto_empresa'] ?? ''));

    $pdf->text(50, 790, 'L&J CRM', 20);
    $pdf->text(50, 765, 'Factura ' . $factura['numero'], 15);
    $pdf->text(50, 742, 'Fecha emision: ' . facturaDocumentoFecha($factura['fecha_emision']), 10);
    $pdf->text(50, 726, 'Fecha vencimiento: ' . facturaDocumentoFecha($factura['fecha_vencimiento'] ?? null), 10);
    $pdf->text(405, 790, 'Cliente', 12);
    $pdf->text(405, 770, $empresa !== '' ? $empresa : $contacto, 10);
    if ($empresa !== '' && $contacto !== '') {
        $pdf->text(405, 754, $contacto, 10);
    }
    $pdf->text(405, 738, (string) ($factura['contacto_email'] ?? ''), 10);
    $pdf->text(405, 722, (string) ($factura['contacto_telefono'] ?? ''), 10);

    $y = 675;
    $pdf->line(50, $y + 14, 545, $y + 14);
    $pdf->text(50, $y, 'Concepto', 10);
    $pdf->text(300, $y, 'Cant.', 10);
    $pdf->text(360, $y, 'Precio', 10);
    $pdf->text(430, $y, 'IVA', 10);
    $pdf->text(490, $y, 'Total', 10);
    $pdf->line(50, $y - 8, 545, $y - 8);
    $y -= 28;

    foreach ($factura['lineas'] as $linea) {
        foreach (facturaDocumentoPartirTexto((string) $linea['concepto'], 44) as $idx => $trozo) {
            $pdf->text(50, $y, $trozo, 9);
            if ($idx === 0) {
                $pdf->text(300, $y, facturaDocumentoNumero($linea['cantidad']), 9);
                $pdf->text(360, $y, facturaDocumentoMoneda($linea['precio_unitario']), 9);
                $pdf->text(430, $y, facturaDocumentoNumero($linea['iva_porcentaje']) . '%', 9);
                $pdf->text(490, $y, facturaDocumentoMoneda($linea['total_linea']), 9);
            }
            $y -= 14;
        }
        $y -= 4;
        if ($y < 130) {
            $pdf->addPage();
            $y = 790;
        }
    }

    $pdf->line(345, 118, 545, 118);
    $pdf->text(360, 98, 'Base imponible', 10);
    $pdf->text(485, 98, facturaDocumentoMoneda($factura['base_imponible']), 10);
    $pdf->text(360, 78, 'IVA', 10);
    $pdf->text(485, 78, facturaDocumentoMoneda($factura['iva_total']), 10);
    $pdf->text(360, 54, 'Total', 13);
    $pdf->text(485, 54, facturaDocumentoMoneda($factura['total']), 13);

    if (!empty($factura['notas'])) {
        $pdf->text(50, 98, 'Notas', 10);
        $ny = 80;
        foreach (facturaDocumentoPartirTexto((string) $factura['notas'], 62) as $trozo) {
            $pdf->text(50, $ny, $trozo, 8);
            $ny -= 12;
            if ($ny < 45) {
                break;
            }
        }
    }

    return $pdf->output();
}

function facturaDocumentoHtmlEmail(array $factura): string
{
    $numero = htmlspecialchars((string) $factura['numero'], ENT_QUOTES, 'UTF-8');
    $total = htmlspecialchars(facturaDocumentoMoneda($factura['total']), ENT_QUOTES, 'UTF-8');
    $fecha = htmlspecialchars(facturaDocumentoFecha($factura['fecha_emision']), ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#1f2937;">
  <div style="max-width:560px;margin:32px auto;background:#fff;border-radius:12px;padding:28px;border:1px solid #e5e7eb;">
    <h1 style="margin:0 0 8px;font-size:22px;color:#111827;">Factura {$numero}</h1>
    <p style="margin:0 0 20px;color:#6b7280;">Adjuntamos tu factura emitida el {$fecha}.</p>
    <div style="background:#f9fafb;border-radius:10px;padding:18px;margin-bottom:20px;">
      <span style="display:block;color:#6b7280;font-size:13px;">Total</span>
      <strong style="font-size:26px;color:#111827;">{$total}</strong>
    </div>
    <p style="margin:0;color:#6b7280;font-size:13px;">Gracias por confiar en L&amp;J CRM.</p>
  </div>
</body>
</html>
HTML;
}

/**
 * Email de recordatorio de pago para facturas vencidas
 * (usado por database/tareas/recordatorios_facturas.php).
 */
function facturaDocumentoHtmlRecordatorio(array $factura): string
{
    $numero = htmlspecialchars((string) $factura['numero'], ENT_QUOTES, 'UTF-8');
    $total = htmlspecialchars(facturaDocumentoMoneda($factura['total']), ENT_QUOTES, 'UTF-8');
    $vencimiento = htmlspecialchars(facturaDocumentoFecha($factura['fecha_vencimiento'] ?? null), ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#1f2937;">
  <div style="max-width:560px;margin:32px auto;background:#fff;border-radius:12px;padding:28px;border:1px solid #e5e7eb;">
    <h1 style="margin:0 0 8px;font-size:22px;color:#111827;">Recordatorio de pago</h1>
    <p style="margin:0 0 20px;color:#6b7280;">
      La factura <strong>{$numero}</strong>, con vencimiento el {$vencimiento}, sigue pendiente de pago.
      Adjuntamos de nuevo el documento por si te resulta útil.
    </p>
    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:18px;margin-bottom:20px;">
      <span style="display:block;color:#9a3412;font-size:13px;">Importe pendiente</span>
      <strong style="font-size:26px;color:#9a3412;">{$total}</strong>
    </div>
    <p style="margin:0;color:#6b7280;font-size:13px;">
      Si ya has realizado el pago, ignora este mensaje. Si necesitas más plazo o tienes alguna duda, responde a este correo.
    </p>
  </div>
</body>
</html>
HTML;
}

function facturaDocumentoFecha(?string $fecha): string
{
    if (!$fecha) {
        return '-';
    }
    $dt = DateTime::createFromFormat('Y-m-d', substr($fecha, 0, 10));
    return $dt ? $dt->format('d/m/Y') : $fecha;
}

function facturaDocumentoNumero($valor): string
{
    return number_format((float) $valor, 2, ',', '.');
}

function facturaDocumentoMoneda($valor): string
{
    return facturaDocumentoNumero($valor) . ' EUR';
}

function facturaDocumentoPartirTexto(string $texto, int $largo): array
{
    $texto = trim(preg_replace('/\s+/', ' ', $texto));
    if ($texto === '') {
        return [''];
    }
    return explode("\n", wordwrap($texto, $largo, "\n", true));
}

class FacturaPdfSimple
{
    private array $pages = [];
    private array $current = [];

    public function addPage(): void
    {
        if ($this->current) {
            $this->pages[] = $this->current;
        }
        $this->current = [];
    }

    public function text(float $x, float $y, string $text, int $size = 10): void
    {
        $this->current[] = sprintf(
            "BT /F1 %d Tf %.2F %.2F Td (%s) Tj ET",
            $size,
            $x,
            $y,
            $this->escape($this->toWinAnsi($text))
        );
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->current[] = sprintf("%.2F %.2F m %.2F %.2F l S", $x1, $y1, $x2, $y2);
    }

    public function output(): string
    {
        if ($this->current) {
            $this->pages[] = $this->current;
            $this->current = [];
        }

        $n = count($this->pages);
        if ($n === 0) {
            $this->pages[] = []; // evita un PDF sin ninguna pagina
            $n = 1;
        }

        // Plan de numeracion de objetos (claro y sin colisiones):
        //   1              -> Catalog
        //   2              -> Pages
        //   3 .. 2+n        -> Page (una por pagina)
        //   3+n .. 2+2n     -> Content stream (una por pagina)
        //   3+2n            -> Font
        $fontObjNum = 3 + 2 * $n;

        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        $kids = [];
        foreach ($this->pages as $idx => $page) {
            $pageObjNum = 3 + $idx;
            $contentObjNum = 3 + $n + $idx;
            $kids[] = "{$pageObjNum} 0 R";

            $objects[$pageObjNum] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] "
                . "/Resources << /Font << /F1 {$fontObjNum} 0 R >> >> /Contents {$contentObjNum} 0 R >>";

            $stream = implode("\n", $page);
            $objects[$contentObjNum] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
        }

        $objects[2] = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count {$n} >>";
        $objects[$fontObjNum] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $maxNum = max(array_keys($objects));
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxNum + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxNum; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . ($maxNum + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function toWinAnsi(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $text);
    }
}
