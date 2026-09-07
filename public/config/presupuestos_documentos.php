<?php

require_once __DIR__ . '/facturas_documentos.php'; // reutiliza FacturaPdfSimple y helpers de formato

function presupuestoDocumentoCargar(PDO $pdo, int $id): ?array
{
    $s = $pdo->prepare("
        SELECT p.*, c.nombre AS contacto_nombre, c.apellidos AS contacto_apellidos,
               c.email AS contacto_email, c.telefono AS contacto_telefono,
               c.empresa AS contacto_empresa
        FROM presupuestos p
        INNER JOIN contactos c ON c.id_contacto = p.contacto_id
        WHERE p.id_presupuesto = ?
        LIMIT 1
    ");
    $s->execute([$id]);
    $presupuesto = $s->fetch();
    if (!$presupuesto) {
        return null;
    }

    $l = $pdo->prepare("SELECT * FROM presupuesto_lineas WHERE presupuesto_id = ? ORDER BY orden, id_linea");
    $l->execute([$id]);
    $presupuesto['lineas'] = $l->fetchAll();

    return $presupuesto;
}

function presupuestoDocumentoNombre(array $presupuesto): string
{
    $numero = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $presupuesto['numero']);
    return 'presupuesto-' . trim($numero, '-') . '.pdf';
}

function presupuestoDocumentoPdf(array $presupuesto): string
{
    $pdf = new FacturaPdfSimple();
    $pdf->addPage();

    $contacto = trim(($presupuesto['contacto_nombre'] ?? '') . ' ' . ($presupuesto['contacto_apellidos'] ?? ''));
    $empresa = trim((string) ($presupuesto['contacto_empresa'] ?? ''));

    $pdf->text(50, 790, 'L&J CRM', 20);
    $pdf->text(50, 765, 'Presupuesto ' . $presupuesto['numero'], 15);
    $pdf->text(50, 742, 'Fecha emision: ' . facturaDocumentoFecha($presupuesto['fecha_emision']), 10);
    $pdf->text(50, 726, 'Valido hasta: ' . facturaDocumentoFecha($presupuesto['fecha_validez'] ?? null), 10);
    $pdf->text(405, 790, 'Cliente', 12);
    $pdf->text(405, 770, $empresa !== '' ? $empresa : $contacto, 10);
    if ($empresa !== '' && $contacto !== '') {
        $pdf->text(405, 754, $contacto, 10);
    }
    $pdf->text(405, 738, (string) ($presupuesto['contacto_email'] ?? ''), 10);
    $pdf->text(405, 722, (string) ($presupuesto['contacto_telefono'] ?? ''), 10);

    $y = 675;
    $pdf->line(50, $y + 14, 545, $y + 14);
    $pdf->text(50, $y, 'Concepto', 10);
    $pdf->text(300, $y, 'Cant.', 10);
    $pdf->text(360, $y, 'Precio', 10);
    $pdf->text(430, $y, 'IVA', 10);
    $pdf->text(490, $y, 'Total', 10);
    $pdf->line(50, $y - 8, 545, $y - 8);
    $y -= 28;

    foreach ($presupuesto['lineas'] as $linea) {
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
    $pdf->text(485, 98, facturaDocumentoMoneda($presupuesto['base_imponible']), 10);
    $pdf->text(360, 78, 'IVA', 10);
    $pdf->text(485, 78, facturaDocumentoMoneda($presupuesto['iva_total']), 10);
    $pdf->text(360, 54, 'Total', 13);
    $pdf->text(485, 54, facturaDocumentoMoneda($presupuesto['total']), 13);

    if (!empty($presupuesto['notas'])) {
        $pdf->text(50, 98, 'Notas', 10);
        $ny = 80;
        foreach (facturaDocumentoPartirTexto((string) $presupuesto['notas'], 62) as $trozo) {
            $pdf->text(50, $ny, $trozo, 8);
            $ny -= 12;
            if ($ny < 45) {
                break;
            }
        }
    }

    return $pdf->output();
}

function presupuestoDocumentoHtmlEmail(array $presupuesto, ?string $urlConfirmacion = null): string
{
    $numero = htmlspecialchars((string) $presupuesto['numero'], ENT_QUOTES, 'UTF-8');
    $total = htmlspecialchars(facturaDocumentoMoneda($presupuesto['total']), ENT_QUOTES, 'UTF-8');
    $validez = htmlspecialchars(facturaDocumentoFecha($presupuesto['fecha_validez'] ?? null), ENT_QUOTES, 'UTF-8');

    $botonesHtml = '';
    if ($urlConfirmacion) {
        $url = htmlspecialchars($urlConfirmacion, ENT_QUOTES, 'UTF-8');
        $botonesHtml = <<<HTML
    <div style="text-align:center;margin:24px 0;">
      <a href="{$url}" style="display:inline-block;background:#16a34a;color:#fff;text-decoration:none;
         padding:14px 28px;border-radius:10px;font-weight:600;font-size:15px;">
        Ver y confirmar presupuesto
      </a>
    </div>
    <p style="margin:0 0 20px;color:#9ca3af;font-size:12px;text-align:center;">
      Podrás aceptarlo o rechazarlo directamente desde esa página, sin necesidad de registrarte.
    </p>
HTML;
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#1f2937;">
  <div style="max-width:560px;margin:32px auto;background:#fff;border-radius:12px;padding:28px;border:1px solid #e5e7eb;">
    <h1 style="margin:0 0 8px;font-size:22px;color:#111827;">Presupuesto {$numero}</h1>
    <p style="margin:0 0 20px;color:#6b7280;">Adjuntamos tu presupuesto. Válido hasta el {$validez}.</p>
    <div style="background:#f9fafb;border-radius:10px;padding:18px;margin-bottom:20px;">
      <span style="display:block;color:#6b7280;font-size:13px;">Total estimado</span>
      <strong style="font-size:26px;color:#111827;">{$total}</strong>
    </div>
    {$botonesHtml}
    <p style="margin:0;color:#6b7280;font-size:13px;">Responde a este email si tienes cualquier duda o quieres confirmarlo.</p>
  </div>
</body>
</html>
HTML;
}
