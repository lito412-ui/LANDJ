<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SESSION['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Solo los administradores pueden gestionar usuarios']);
    exit;
}

csrfValidar();

require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../config/auditoria.php';

$meId  = (int) $_SESSION['user_id'];
$grupoId = obtenerIdGrupoActual();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

function ok($data): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }
function clean(string $v): string { return trim($v); }

function validarUsuario(array $b, bool $esNuevo): array {
    $errors = [];

    $nombre = clean($b['nombre'] ?? '');
    if ($nombre === '') {
        $errors[] = 'El nombre es obligatorio';
    } elseif (strlen($nombre) > 100) {
        $errors[] = 'El nombre no puede superar 100 caracteres';
    } elseif (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s_\-\.]+$/u', $nombre)) {
        $errors[] = 'El nombre contiene caracteres no permitidos';
    }

    $email = strtolower(clean($b['email'] ?? ''));
    if ($email !== '') {
        if (strlen($email) > 255) $errors[] = 'El email no puede superar 255 caracteres';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'El email no es válido';
    }

    $rol = clean($b['rol'] ?? 'usuario');
    if (!in_array($rol, ['usuario', 'administrador'], true))
        $errors[] = 'Rol no válido';

    $pass = $b['password'] ?? '';
    if ($esNuevo && $pass === '') {
        $errors[] = 'La contraseña es obligatoria';
    } elseif ($pass !== '') {
        if (strlen($pass) < 8)  $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        if (strlen($pass) > 72) $errors[] = 'La contraseña no puede superar 72 caracteres';
        if (!preg_match('/[a-zA-Z]/', $pass) || !preg_match('/[0-9]/', $pass))
            $errors[] = 'La contraseña debe contener letras y números';
    }

    return [
        'errors' => $errors,
        'nombre' => $nombre,
        'email'  => $email !== '' ? $email : null,
        'rol'    => $rol,
        'pass'   => $pass,
    ];
}

try {
    switch ($method) {

        // ─── Listar ──────────────────────────────────────────────────────
        case 'GET':
            if ($id) {
                $s = $pdo->prepare("SELECT id_usuario, nombre, email, rol, created_at FROM usuarios WHERE id_usuario = ? AND id_grupo = ?");
                $s->execute([$id, $grupoId]);
                $row = $s->fetch();
                $row ? ok($row) : err('Usuario no encontrado', 404);
                break;
            }
            $buscar = clean($_GET['buscar'] ?? '');
            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $s = $pdo->prepare(
                    "SELECT id_usuario, nombre, email, rol, created_at FROM usuarios
                     WHERE id_grupo = ? AND (nombre LIKE ? OR email LIKE ?) ORDER BY created_at DESC"
                );
                $s->execute([$grupoId, $like, $like]);
            } else {
                $s = $pdo->prepare("SELECT id_usuario, nombre, email, rol, created_at FROM usuarios WHERE id_grupo = ? ORDER BY created_at DESC");
                $s->execute([$grupoId]);
            }
            ok($s->fetchAll());
            break;

        // ─── Crear ───────────────────────────────────────────────────────
        case 'POST':
            $v = validarUsuario(body(), true);
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $existe = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = ? AND id_grupo = ?");
            $existe->execute([$v['nombre'], $grupoId]);
            if ($existe->fetch()) { err('Ya existe un usuario con ese nombre'); break; }

            if ($v['email']) {
                $existeEmail = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_grupo = ?");
                $existeEmail->execute([$v['email'], $grupoId]);
                if ($existeEmail->fetch()) { err('Ya existe un usuario con ese email'); break; }
            }

            $hash = password_hash($v['pass'], PASSWORD_ARGON2ID);
            $s = $pdo->prepare(
                "INSERT INTO usuarios (nombre, email, contraseña_hash, rol, id_grupo) VALUES (?, ?, ?, ?, ?)"
            );
            $s->execute([$v['nombre'], $v['email'], $hash, $v['rol'], $grupoId]);
            $newId = (int) $pdo->lastInsertId();
            $s2 = $pdo->prepare("SELECT id_usuario, nombre, email, rol, created_at FROM usuarios WHERE id_usuario = ? AND id_grupo = ?");
            $s2->execute([$newId, $grupoId]);
            $nuevo = $s2->fetch();
            registrarAuditoria($pdo, 'usuarios', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        // ─── Actualizar ──────────────────────────────────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $v = validarUsuario(body(), false);
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            // No puede cambiar su propio rol
            if ($id === $meId && $v['rol'] !== $_SESSION['rol']) {
                err('No puedes cambiar tu propio rol'); break;
            }

            $existe = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = ? AND id_usuario != ? AND id_grupo = ?");
            $existe->execute([$v['nombre'], $id, $grupoId]);
            if ($existe->fetch()) { err('Ya existe un usuario con ese nombre'); break; }

            if ($v['email']) {
                $existeEmail = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ? AND id_grupo = ?");
                $existeEmail->execute([$v['email'], $id, $grupoId]);
                if ($existeEmail->fetch()) { err('Ya existe un usuario con ese email'); break; }
            }

            $sAntes = $pdo->prepare("SELECT id_usuario, nombre, email, rol, created_at FROM usuarios WHERE id_usuario = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Usuario no encontrado', 404); break; }

            if ($v['pass'] !== '') {
                $hash = password_hash($v['pass'], PASSWORD_ARGON2ID);
                $s = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, rol=?, contraseña_hash=? WHERE id_usuario=? AND id_grupo=?");
                $s->execute([$v['nombre'], $v['email'], $v['rol'], $hash, $id, $grupoId]);
            } else {
                $s = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, rol=? WHERE id_usuario=? AND id_grupo=?");
                $s->execute([$v['nombre'], $v['email'], $v['rol'], $id, $grupoId]);
            }

            $s2 = $pdo->prepare("SELECT id_usuario, nombre, email, rol, created_at FROM usuarios WHERE id_usuario = ? AND id_grupo = ?");
            $s2->execute([$id, $grupoId]);
            $despues = $s2->fetch() ?: null;
            registrarAuditoria($pdo, 'usuarios', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        // ─── Eliminar ────────────────────────────────────────────────────
        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            if ($id === $meId) { err('No puedes eliminar tu propia cuenta'); break; }

            // Proteger el último administrador
            $sAntes = $pdo->prepare("SELECT id_usuario, nombre, email, rol, created_at FROM usuarios WHERE id_usuario = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Usuario no encontrado', 404); break; }

            if ($antes['rol'] === 'administrador') {
                $countAdmin = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador' AND id_grupo = ?");
                $countAdmin->execute([$grupoId]);
                $countAdmin = $countAdmin->fetchColumn();
                if ($countAdmin <= 1) { err('No puedes eliminar el único administrador del sistema'); break; }
            }

            $s = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ? AND id_grupo = ?");
            $s->execute([$id, $grupoId]);
            registrarAuditoria($pdo, 'usuarios', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
