<?php
session_start();
// Conexión a Docker (Asegúrate de que conexion.php usa root y lolito412/)
require 'conexion.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_form = $_POST['username'] ?? '';
    $pass_form = $_POST['password'] ?? '';

    try {
        // Buscamos en la base de datos MySQL
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre = ? LIMIT 1");
        $stmt->execute([$user_form]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass_form, $user['contraseña_hash'])) {
            // ÉXITO
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            
            header("Location: cpanel.html");
            exit;
        } else {
            // ERROR
            echo "<script>alert('Usuario o contraseña incorrectos'); window.location.href='login.html';</script>";
        }
    } catch (PDOException $e) {
        die("Error de base de datos: " . $e->getMessage());
    }
} else {
    header("Location: login.html");
}
?>