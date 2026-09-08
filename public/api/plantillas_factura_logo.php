<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }
csrfValidar();
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../config/modulos_visibilidad.php';
verificarModuloVisible($pdo, 'facturas');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['logo'])) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Selecciona una imagen']); exit; }
$file = $_FILES['logo'];
if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'La imagen debe pesar como máximo 2 MB']); exit; }
$info = @getimagesize($file['tmp_name']);
$tipos = ['image/png'=>'png', 'image/jpeg'=>'jpg', 'image/webp'=>'webp'];
if (!$info || !isset($tipos[$info['mime']])) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Solo se permiten PNG, JPG o WebP']); exit; }
$grupo = obtenerIdGrupoActual();
$dir = __DIR__ . '/../uploads/facturas/' . $grupo;
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'No se pudo preparar el almacenamiento']); exit; }
$name = bin2hex(random_bytes(16)) . '.' . $tipos[$info['mime']];
if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'No se pudo guardar la imagen']); exit; }
echo json_encode(['ok'=>true,'data'=>['url'=>'/uploads/facturas/' . $grupo . '/' . $name]]);
