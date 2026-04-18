<?php

/**
 * Registra una entrada en la tabla `auditoria`.
 *
 * @param PDO        $pdo        Conexión activa
 * @param string     $tabla      Nombre de la tabla afectada (ej: 'contactos')
 * @param int        $registroId PK del registro afectado
 * @param string     $accion     'crear' | 'editar' | 'eliminar'
 * @param array|null $antes      Estado previo (null en crear)
 * @param array|null $despues    Estado nuevo  (null en eliminar)
 */
function registrarAuditoria(
    PDO     $pdo,
    string  $tabla,
    int     $registroId,
    string  $accion,
    ?array  $antes,
    ?array  $despues
): void {
    $usuarioId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $ip        = $_SERVER['HTTP_X_FORWARDED_FOR']
               ?? $_SERVER['REMOTE_ADDR']
               ?? null;

    $s = $pdo->prepare(
        "INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_antes, datos_despues, ip)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $s->execute([
        $tabla,
        $registroId,
        $accion,
        $usuarioId,
        $antes   !== null ? json_encode($antes,   JSON_UNESCAPED_UNICODE) : null,
        $despues !== null ? json_encode($despues,  JSON_UNESCAPED_UNICODE) : null,
        $ip,
    ]);
}
