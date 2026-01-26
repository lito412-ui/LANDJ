<?php

$host = 'db';
$db   = 'mi_proyecto_db';
$user = 'root';
$pass = 'lolito412/';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$max_attempts = 15;
$attempts = 0;
$pdo = null;

while ($attempts < $max_attempts) {
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        echo "Conexión exitosa a la base de datos. Continuando...\n";
        break;
    } catch (\PDOException $e) {
        $attempts++;
        echo "Esperando a que la BD esté disponible... Intento $attempts/$max_attempts\n";
        if ($attempts == $max_attempts) {
            die("Error fatal: No se pudo conectar a la base de datos después de varios intentos. Detalles: " . $e->getMessage());
        }
        sleep(2);
    }
}


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
