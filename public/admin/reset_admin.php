<?php
require __DIR__ . '/../config/conexion.php';

try {
    //Limpiamos cualquier usuario llamado 'admin' para evitar conflictos
    $pdo->exec("DELETE FROM usuarios WHERE nombre = 'admin'");

    //Definimos los datos
    $nombre = "admin";
    $email = "alvarogutierrez874@gmail.com";
    $rol = "administrador";
    $pass_plana = "lolito412/"; //Contraseña para el login
    
    //Generamos el hash limpio
    $hash = password_hash($pass_plana, PASSWORD_ARGON2ID);

    //Insertamos
    $sql = "INSERT INTO usuarios (nombre, email, contrasena_hash, rol) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $email, $hash, $rol]);

    echo "✅ Usuario 'admin' reseteado con éxito. Contraseña establecida: $pass_plana";

} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>