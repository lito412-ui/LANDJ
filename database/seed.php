<?php
require __DIR__ . '/../public/config/conexion.php';

$jsonPath = __DIR__ . '/db.json';
$jsonData = json_decode(file_get_contents($jsonPath), true);

if (!is_array($jsonData) || !isset($jsonData['users']) || !is_array($jsonData['users'])) {
    die("Formato invalido en database/db.json\n");
}

foreach ($jsonData['users'] as $u) {
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = ?");
    $stmt->execute([$u['username']]);
    $hash = password_hash($u['password'], PASSWORD_ARGON2ID);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE usuarios SET contraseña_hash = ? WHERE nombre = ?")
            ->execute([$hash, $u['username']]);
        echo "Actualizado: {$u['username']}\n";
    } else {
        $email = strtolower($u['username']) . '@landj.local';
        $pdo->prepare("INSERT INTO usuarios (nombre, email, contraseña_hash, rol) VALUES (?, ?, ?, 'usuario')")
            ->execute([$u['username'], $email, $hash]);
        echo "Creado: {$u['username']}\n";
    }
}

$stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = 'admin'");
$stmt->execute();
if (!$stmt->fetch()) {
    $hash = password_hash('lolito412/', PASSWORD_ARGON2ID);
    $pdo->prepare("INSERT INTO usuarios (nombre, email, contraseña_hash, rol) VALUES ('admin', 'alvarogutierrez874@gmail.com', ?, 'administrador')")
        ->execute([$hash]);
    echo "Creado: admin\n";
}
