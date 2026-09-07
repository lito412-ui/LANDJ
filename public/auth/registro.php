<?php
/**
 * registro.php — Deshabilitado en producción
 * Los usuarios son creados por el administrador desde el panel de control.
 * Redirige a la página principal.
 */
header('HTTP/1.1 404 Not Found');
header('Location: /');
exit;
