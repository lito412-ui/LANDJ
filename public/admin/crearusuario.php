<?php
require __DIR__ . '/../config/conexion.php';


$nombre_usuario_nuevo = "admin";
$email_usuario_nuevo = "alvarogutierrez874@gmail.com";
$rol_usuario_nuevo = "administrador";

echo "Iniciando proceso de creación de usuario...\n";

$contrasena_plana = "lolito412/";
$contrasena_hasheada = password_hash($contrasena_plana, PASSWORD_ARGON2ID);

$sql = "INSERT INTO usuarios (nombre, email, contraseña_hash, rol) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([$nombre_usuario_nuevo, $email_usuario_nuevo, $contrasena_hasheada, $rol_usuario_nuevo]);
    echo "¡Éxito! El usuario '$email_usuario_nuevo' ha sido creado y su contraseña hasheada guardada en la BD.\n";
    echo "Ahora puedes usar este usuario para probar tu login.php.\n";

} catch (PDOException $e) {
    die("Error de base de datos al insertar usuario: " . $e->getMessage());
}
?>
