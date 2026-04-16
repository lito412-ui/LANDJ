<?php
// registro.php
require __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password_plana = $_POST['password'];

    $password_hasheada = password_hash($password_plana, PASSWORD_ARGON2ID);
    
    $rol = 'usuario';
    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, contrasena_hash, rol) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $password_hasheada, $rol]);
        echo "Usuario registrado con éxito. <a href='/index.html'>Volver al inicio</a>";
    } catch (PDOException $e) {
        echo "Error al registrar: " . $e->getMessage();
    }
} else {
    header('Location: /index.html');
    exit;
}
?>
