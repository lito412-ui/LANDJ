<?php
/** @var PDO $pdo */

$adminPass = getenv('INITIAL_ADMIN_PASSWORD') ?: 'Admin_LandJ#2026!';
$userPass  = getenv('INITIAL_USER_PASSWORD')  ?: 'User_LandJ#2026!';

$usuarios = [
    ['nombre' => 'admin',   'email' => 'admin@landj.local',   'password' => $adminPass, 'rol' => 'administrador'],
    ['nombre' => 'Samuel',  'email' => 'samuel@landj.local',  'password' => $userPass,  'rol' => 'usuario'],
    ['nombre' => 'lito412', 'email' => 'lito412@landj.local', 'password' => $userPass,  'rol' => 'usuario'],
    ['nombre' => 'Cuervo',  'email' => 'cuervo@landj.local',  'password' => $userPass,  'rol' => 'usuario'],
];

$insert = $pdo->prepare(
    "INSERT IGNORE INTO usuarios (nombre, email, contraseña_hash, rol) VALUES (?, ?, ?, ?)"
);

foreach ($usuarios as $u) {
    $hash = password_hash($u['password'], PASSWORD_ARGON2ID);
    $insert->execute([$u['nombre'], $u['email'], $hash, $u['rol']]);
    $rows = $insert->rowCount();
    echo "    usuario '{$u['nombre']}': " . ($rows ? 'creado' : 'ya existía') . "\n";
}
