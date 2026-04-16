<?php
require '/usr/share/nginx/html/conexion.php';

$json = json_decode(file_get_contents('/usr/share/nginx/html/data/db.json'), true);

foreach ($json['users'] as $u) {
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = ?");
    $stmt->execute([$u['username']]);
    if ($stmt->fetch()) continue;

    $hash = password_hash($u['password'], PASSWORD_ARGON2ID);
    $pdo->prepare("INSERT INTO usuarios (nombre, contraseña_hash, rol) VALUES (?, ?, 'usuario')")
        ->execute([$u['username'], $hash]);
    echo "Creado: {$u['username']}\n";
}

// Admin
$stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = 'admin'");
$stmt->execute();
if (!$stmt->fetch()) {
    $hash = password_hash('lolito412/', PASSWORD_ARGON2ID);
    $pdo->prepare("INSERT INTO usuarios (nombre, email, contraseña_hash, rol) VALUES ('admin', 'alvarogutierrez874@gmail.com', ?, 'administrador')")
        ->execute([$hash]);
    echo "Creado: admin\n";
}
