<?php
/**
 * API Pública de Autenticación:
 * - Registro de nuevas cuentas (Admin con grupo propio / multitenant)
 * - Verificación de correo electrónico
 * - Recuperación de contraseña olvidada
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/multitenant.php';
require_once __DIR__ . '/../config/auditoria.php';

$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

function ok($data = null): void { echo json_encode(['ok' => true, 'data' => $data]); exit; }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }

function ofuscarEmail(string $email): string {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return '***';
    $name = $parts[0];
    $domain = $parts[1];
    $visible = substr($name, 0, min(3, strlen($name)));
    return $visible . '***@' . $domain;
}

if ($method !== 'POST') {
    err('Método no permitido', 405);
}

try {
    // ══════════════════════════════════════════════════════════════════════════
    // 1. REGISTRO DE NUEVA CUENTA
    // ══════════════════════════════════════════════════════════════════════════
    if ($accion === 'registro') {
        $b = body();

        $nombre        = trim($b['nombre'] ?? '');
        $email         = strtolower(trim($b['email'] ?? ''));
        $pass          = $b['password'] ?? '';
        $nombreEmpresa = trim($b['nombre_empresa'] ?? '');

        if ($nombre === '') { err('El nombre de usuario es obligatorio'); }
        if (strlen($nombre) < 3 || strlen($nombre) > 100) { err('El nombre debe tener entre 3 y 100 caracteres'); }
        if (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s_\-\.]+$/u', $nombre)) {
            err('El nombre contiene caracteres no válidos');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            err('Por favor introduce un correo electrónico válido');
        }

        if (strlen($pass) < 8 || strlen($pass) > 72) {
            err('La contraseña debe tener entre 8 y 72 caracteres');
        }
        if (!preg_match('/[a-zA-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
            err('La contraseña debe contener letras y números');
        }

        // Comprobar que no exista ya el usuario o email
        $chk = $pdo->prepare("SELECT id_usuario, nombre, email FROM usuarios WHERE LOWER(nombre) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
        $chk->execute([$nombre, $email]);
        $existente = $chk->fetch();
        if ($existente) {
            if (strtolower($existente['nombre']) === strtolower($nombre)) {
                err('El nombre de usuario ya está registrado');
            } else {
                err('El correo electrónico ya está registrado');
            }
        }

        // Crear código de verificación de 6 dígitos
        $codigo     = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);
        $expira     = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $pdo->beginTransaction();

        // Crear nuevo grupo (tenant) independiente para este administrador
        $tituloGrupo = $nombreEmpresa !== '' ? $nombreEmpresa : "Organización de " . $nombre;
        $stmtGrupo = $pdo->prepare("INSERT INTO grupos (nombre) VALUES (?)");
        $stmtGrupo->execute([$tituloGrupo]);
        $nuevoGrupoId = (int) $pdo->lastInsertId();

        // Crear el usuario administrador asociado a su nuevo grupo
        $hashPass = password_hash($pass, PASSWORD_ARGON2ID);
        $stmtUser = $pdo->prepare("
            INSERT INTO usuarios
                (nombre, email, contraseña_hash, rol, id_grupo, email_verificado, codigo_verificacion_email, codigo_verificacion_expira)
            VALUES
                (?, ?, ?, 'administrador', ?, 0, ?, ?)
        ");
        $stmtUser->execute([$nombre, $email, $hashPass, $nuevoGrupoId, $codigoHash, $expira]);
        $nuevoUserId = (int) $pdo->lastInsertId();

        $pdo->commit();

        // Enviar correo de activación con el código
        $html = plantillaVerificacionRegistro($nombre, $codigo);
        $enviado = enviarEmailSistema($email, 'Activa tu cuenta en L&J CRM', $html);

        if (!$enviado) {
            error_log("[registro] No se pudo enviar el correo de activación a {$email}. Comprueba el .env");
        }

        // Guardar sesión pendiente de verificación
        $_SESSION['registro_pendiente'] = [
            'id_usuario' => $nuevoUserId,
            'nombre'     => $nombre,
            'email'      => $email,
            'last_sent'  => time(),
        ];

        ok([
            'user_id' => $nuevoUserId,
            'email'   => $email,
            'nombre'  => $nombre,
            'email_enviado' => $enviado,
            'message' => 'Cuenta creada. Introduce el código de 6 dígitos enviado a tu correo.'
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 2. VERIFICAR CÓDIGO DE REGISTRO
    // ══════════════════════════════════════════════════════════════════════════
    if ($accion === 'verificar-registro') {
        $b = body();

        $userId = (int) ($b['user_id'] ?? ($_SESSION['registro_pendiente']['id_usuario'] ?? 0));
        $codigo = trim($b['codigo'] ?? '');

        if ($userId <= 0) { err('ID de usuario no especificado'); }
        if ($codigo === '' || strlen($codigo) < 6) { err('Introduce el código de 6 dígitos'); }

        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre, email, rol, id_grupo, email_verificado,
                   codigo_verificacion_email, codigo_verificacion_expira
            FROM usuarios WHERE id_usuario = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) { err('Usuario no encontrado', 404); }

        if (!empty($user['email_verificado'])) {
            // Ya verificado
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id_usuario'];
            $_SESSION['nombre']   = $user['nombre'];
            $_SESSION['rol']      = $user['rol'];
            $_SESSION['id_grupo'] = (int) ($user['id_grupo'] ?? 1);
            unset($_SESSION['registro_pendiente']);
            ok(['redirect' => '/admin/cpanel.php', 'message' => 'Tu cuenta ya estaba verificada']);
        }

        if (empty($user['codigo_verificacion_expira']) || new DateTime() > new DateTime($user['codigo_verificacion_expira'])) {
            err('El código de verificación ha caducado. Pulsa en reenviar código.');
        }

        if (!password_verify($codigo, (string) $user['codigo_verificacion_email'])) {
            err('El código de verificación es incorrecto');
        }

        // Activación de la cuenta
        $pdo->prepare("
            UPDATE usuarios
            SET email_verificado = 1,
                codigo_verificacion_email = NULL,
                codigo_verificacion_expira = NULL
            WHERE id_usuario = ?
        ")->execute([$userId]);

        // Autologin
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id_usuario'];
        $_SESSION['nombre']   = $user['nombre'];
        $_SESSION['rol']      = $user['rol'];
        $_SESSION['id_grupo'] = (int) ($user['id_grupo'] ?? 1);
        unset($_SESSION['registro_pendiente']);

        registrarAuditoria($pdo, 'usuarios', $userId, 'crear', null, [
            'nombre' => $user['nombre'],
            'email'  => $user['email'],
            'rol'    => $user['rol'],
            'id_grupo' => $user['id_grupo']
        ]);

        ok([
            'redirect' => '/admin/cpanel.php',
            'message'  => '¡Cuenta verificada con éxito! Bienvenido a tu panel.'
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 3. REENVIAR CÓDIGO DE REGISTRO
    // ══════════════════════════════════════════════════════════════════════════
    if ($accion === 'reenviar-registro') {
        $b = body();
        $userId = (int) ($b['user_id'] ?? ($_SESSION['registro_pendiente']['id_usuario'] ?? 0));

        if ($userId <= 0) { err('ID de usuario requerido'); }

        $stmt = $pdo->prepare("SELECT id_usuario, nombre, email, email_verificado FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) { err('Usuario no encontrado', 404); }
        if (!empty($user['email_verificado'])) { err('La cuenta ya está activada'); }

        $ahora = time();
        $ultimoEnvio = (int) ($_SESSION['registro_pendiente']['last_sent'] ?? 0);
        $restante = 60 - ($ahora - $ultimoEnvio);
        if ($restante > 0) {
            err("Debes esperar {$restante} segundo(s) antes de solicitar otro código.");
        }

        $codigo     = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);
        $expira     = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $pdo->prepare("
            UPDATE usuarios
            SET codigo_verificacion_email = ?, codigo_verificacion_expira = ?
            WHERE id_usuario = ?
        ")->execute([$codigoHash, $expira, $userId]);

        $_SESSION['registro_pendiente']['last_sent'] = $ahora;

        $html = plantillaVerificacionRegistro($user['nombre'], $codigo);
        $enviado = enviarEmailSistema($user['email'], 'Nuevo código de activación — L&J CRM', $html);

        if (!$enviado) {
            err('No se pudo enviar el correo de verificación. Revisa la configuración del servidor.');
        }

        ok(['message' => 'Hemos enviado un nuevo código a tu correo electrónico.']);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 4. SOLICITAR RECUPERACIÓN DE CONTRASEÑA
    // ══════════════════════════════════════════════════════════════════════════
    if ($accion === 'solicitar-recuperacion') {
        $b = body();
        $identificador = trim($b['identificador'] ?? '');

        if ($identificador === '') {
            err('Introduce tu nombre de usuario o correo electrónico');
        }

        $stmt = $pdo->prepare("SELECT id_usuario, nombre, email FROM usuarios WHERE LOWER(nombre) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$identificador, $identificador]);
        $user = $stmt->fetch();

        // Por seguridad, si el usuario no existe no lo confirmamos abiertamente
        if (!$user || empty($user['email'])) {
            ok([
                'enviado' => true,
                'email_ofuscado' => 'tu correo registrado',
                'message' => 'Si la cuenta existe, se ha enviado un código de recuperación a su correo asociado.'
            ]);
        }

        $codigo     = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);
        $expira     = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $pdo->prepare("
            UPDATE usuarios
            SET codigo_recuperacion = ?,
                codigo_recuperacion_expira = ?,
                recuperacion_intentos = 0
            WHERE id_usuario = ?
        ")->execute([$codigoHash, $expira, $user['id_usuario']]);

        $html = plantillaRecuperacionPassword($user['nombre'], $codigo);
        $enviado = enviarEmailSistema($user['email'], 'Recuperación de contraseña — L&J CRM', $html);

        if (!$enviado) {
            err('No se pudo enviar el correo de recuperación. El servidor de correo no está disponible.');
        }

        ok([
            'enviado'        => true,
            'identificador'  => $user['nombre'],
            'email_ofuscado' => ofuscarEmail($user['email']),
            'message'        => 'Código de recuperación enviado.'
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 5. RESTABLECER CONTRASEÑA
    // ══════════════════════════════════════════════════════════════════════════
    if ($accion === 'restablecer-password') {
        $b = body();

        $identificador = trim($b['identificador'] ?? '');
        $codigo        = trim($b['codigo'] ?? '');
        $nuevaPass     = $b['nueva_password'] ?? '';

        if ($identificador === '') { err('Identificador de cuenta requerido'); }
        if ($codigo === '' || strlen($codigo) < 6) { err('Introduce el código de 6 dígitos'); }
        if (strlen($nuevaPass) < 8 || strlen($nuevaPass) > 72) {
            err('La nueva contraseña debe tener entre 8 y 72 caracteres');
        }
        if (!preg_match('/[a-zA-Z]/', $nuevaPass) || !preg_match('/[0-9]/', $nuevaPass)) {
            err('La nueva contraseña debe contener letras y números');
        }

        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre, email, codigo_recuperacion,
                   codigo_recuperacion_expira, recuperacion_intentos
            FROM usuarios
            WHERE LOWER(nombre) = LOWER(?) OR LOWER(email) = LOWER(?)
            LIMIT 1
        ");
        $stmt->execute([$identificador, $identificador]);
        $user = $stmt->fetch();

        if (!$user || empty($user['codigo_recuperacion'])) {
            err('No hay ninguna solicitud de recuperación activa para esta cuenta');
        }

        if ((int) $user['recuperacion_intentos'] >= 5) {
            err('Demasiados intentos fallidos. Solicita un nuevo código de recuperación.');
        }

        if (empty($user['codigo_recuperacion_expira']) || new DateTime() > new DateTime($user['codigo_recuperacion_expira'])) {
            err('El código de recuperación ha caducado. Solicita uno nuevo.');
        }

        if (!password_verify($codigo, (string) $user['codigo_recuperacion'])) {
            $pdo->prepare("UPDATE usuarios SET recuperacion_intentos = recuperacion_intentos + 1 WHERE id_usuario = ?")
                ->execute([$user['id_usuario']]);
            err('El código introducido es incorrecto');
        }

        // Actualizar contraseña
        $nuevoHash = password_hash($nuevaPass, PASSWORD_ARGON2ID);
        $pdo->prepare("
            UPDATE usuarios
            SET contraseña_hash = ?,
                codigo_recuperacion = NULL,
                codigo_recuperacion_expira = NULL,
                recuperacion_intentos = 0
            WHERE id_usuario = ?
        ")->execute([$nuevoHash, $user['id_usuario']]);

        registrarAuditoria($pdo, 'usuarios', $user['id_usuario'], 'editar', null, ['accion' => 'recuperacion_password']);

        ok([
            'message' => '¡Contraseña restablecida correctamente! Ya puedes iniciar sesión con tu nueva clave.'
        ]);
    }

    err('Acción no reconocida', 404);

} catch (Throwable $e) {
    error_log('[auth_api] ' . $e->getMessage());
    err('Error interno del servidor: ' . $e->getMessage(), 500);
}
