<?php
/**
 * Soporte Multi-tenant (Grupos / Organizaciones) y Plantillas de Correo de Auth.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Devuelve el ID del grupo (tenant) al que pertenece el usuario autenticado.
 */
function obtenerIdGrupoActual(): int
{
    $grupoId = (int) ($_SESSION['id_grupo'] ?? 0);
    if ($grupoId <= 0) {
        throw new RuntimeException('No hay un grupo activo en la sesión.');
    }
    return $grupoId;
}

/**
 * Añade una restricción de tenant a una consulta por clave primaria.
 * Debe usarse en cualquier lectura o modificación de un recurso del CRM.
 */
function recursoPerteneceAlGrupo(PDO $pdo, string $tabla, string $clave, int $id, int $grupoId): bool
{
    $tablas = [
        'contactos' => 'id_contacto', 'leads' => 'id_lead',
        'oportunidades' => 'id_oportunidad', 'actividades' => 'id_actividad',
        'presupuestos' => 'id_presupuesto', 'facturas' => 'id_factura',
        'facturas_recurrentes' => 'id_recurrente',
        'productos' => 'id_producto', 'proveedores' => 'id_proveedor',
        'avisos' => 'id_aviso', 'usuarios' => 'id_usuario',
    ];
    if (!isset($tablas[$tabla]) || $tablas[$tabla] !== $clave) {
        throw new InvalidArgumentException('Recurso multiempresa no válido.');
    }
    $sql = "SELECT 1 FROM `{$tabla}` WHERE `{$clave}` = ? AND id_grupo = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $grupoId]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Garantiza de forma idempotente que las tablas y columnas multi-tenant existan.
 */
function asegurarEsquemaMultitenant(PDO $pdo): void
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;

    try {
        // 1. Tabla de grupos
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `grupos` (
              `id_grupo`   INT AUTO_INCREMENT PRIMARY KEY,
              `nombre`     VARCHAR(150) NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("INSERT IGNORE INTO `grupos` (`id_grupo`, `nombre`) VALUES (1, 'Organización Principal');");

        // 2. Columnas en usuarios
        $cols = $pdo->query("SHOW COLUMNS FROM `usuarios` LIKE 'id_grupo'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `rol`");
        }
        $cols = $pdo->query("SHOW COLUMNS FROM `usuarios` LIKE 'email_verificado'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `email_verificado` TINYINT(1) NOT NULL DEFAULT 1");
            $pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `codigo_verificacion_email` VARCHAR(255) DEFAULT NULL");
            $pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `codigo_verificacion_expira` DATETIME DEFAULT NULL");
            $pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `codigo_recuperacion` VARCHAR(255) DEFAULT NULL");
            $pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `codigo_recuperacion_expira` DATETIME DEFAULT NULL");
            $pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `recuperacion_intentos` INT NOT NULL DEFAULT 0");
        }

        // 3. Tablas del CRM
        $tablasCrm = [
            'contactos', 'leads', 'oportunidades', 'actividades',
            'presupuestos', 'facturas', 'facturas_recurrentes',
            'productos', 'proveedores', 'avisos'
        ];
        foreach ($tablasCrm as $tabla) {
            try {
                $c = $pdo->query("SHOW COLUMNS FROM `{$tabla}` LIKE 'id_grupo'")->fetchAll();
                if (empty($c)) {
                    $pdo->exec("ALTER TABLE `{$tabla}` ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1");
                }
            } catch (Throwable $e) {}
        }

        // 4. system_config multi-grupo
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `system_config` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `id_grupo` INT NOT NULL DEFAULT 1,
              `clave` VARCHAR(100) NOT NULL,
              `valor` TEXT DEFAULT NULL,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $c = $pdo->query("SHOW COLUMNS FROM `system_config` LIKE 'id_grupo'")->fetchAll();
            if (empty($c)) {
                $pdo->exec("ALTER TABLE `system_config` ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id`");
            }
        } catch (Throwable $e) {}

    } catch (Throwable $e) {
        error_log('[multitenant] Error asegurando esquema: ' . $e->getMessage());
    }
}

/**
 * Plantilla de email para código de activación de cuenta (Registro).
 */
function plantillaVerificacionRegistro(string $nombre, string $codigo): string
{
    $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $codigoSeguro = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Inter,-apple-system,BlinkMacSystemFont,Arial,sans-serif;">
  <div style="max-width:480px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:28px 32px;">
      <h1 style="margin:0;color:#fff;font-size:1.6rem;font-weight:700;letter-spacing:-0.01em;">L&amp;J CRM</h1>
      <p style="margin:4px 0 0;color:rgba(255,255,255,.85);font-size:.9rem;">Activa tu nueva cuenta</p>
    </div>
    <div style="padding:32px;">
      <p style="margin:0 0 8px;color:#1e293b;font-size:1rem;">¡Hola, <strong>{$nombreSeguro}</strong>!</p>
      <p style="margin:0 0 24px;color:#64748b;font-size:.9rem;line-height:1.5;">
        Gracias por registrarte en L&amp;J CRM. Para activar tu cuenta y configurar tu espacio de trabajo, introduce el siguiente código de verificación:
      </p>
      <div style="background:#f8fafc;border:2px dashed #667eea;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px;">
        <span style="font-size:2.4rem;font-weight:800;letter-spacing:.3em;color:#667eea;font-family:monospace;">{$codigoSeguro}</span>
      </div>
      <p style="margin:0;color:#94a3b8;font-size:.78rem;line-height:1.5;">
        Este código es válido durante <strong>15 minutos</strong>.<br>
        Si tú no has creado esta cuenta, puedes ignorar este correo de forma segura.
      </p>
    </div>
  </div>
</body>
</html>
HTML;
}

/**
 * Plantilla de email para restablecimiento de contraseña.
 */
function plantillaRecuperacionPassword(string $nombre, string $codigo): string
{
    $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $codigoSeguro = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Inter,-apple-system,BlinkMacSystemFont,Arial,sans-serif;">
  <div style="max-width:480px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <div style="background:linear-gradient(135deg,#ef4444,#dc2626);padding:28px 32px;">
      <h1 style="margin:0;color:#fff;font-size:1.6rem;font-weight:700;letter-spacing:-0.01em;">L&amp;J CRM</h1>
      <p style="margin:4px 0 0;color:rgba(255,255,255,.9);font-size:.9rem;">Recuperación de Contraseña</p>
    </div>
    <div style="padding:32px;">
      <p style="margin:0 0 8px;color:#1e293b;font-size:1rem;">Hola, <strong>{$nombreSeguro}</strong></p>
      <p style="margin:0 0 24px;color:#64748b;font-size:.9rem;line-height:1.5;">
        Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en L&amp;J CRM. Introduce este código de verificación:
      </p>
      <div style="background:#fef2f2;border:2px dashed #f87171;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px;">
        <span style="font-size:2.4rem;font-weight:800;letter-spacing:.3em;color:#dc2626;font-family:monospace;">{$codigoSeguro}</span>
      </div>
      <p style="margin:0;color:#94a3b8;font-size:.78rem;line-height:1.5;">
        Este código caduca en <strong>15 minutos</strong>.<br>
        Si no solicitaste este cambio, te recomendamos revisar la seguridad de tu cuenta. Tu contraseña actual no ha cambiado.
      </p>
    </div>
  </div>
</body>
</html>
HTML;
}
