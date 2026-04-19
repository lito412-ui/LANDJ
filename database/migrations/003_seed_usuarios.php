<?php
/** @var PDO $pdo */

$usuarios = [
    ['nombre' => 'admin',   'email' => 'alvarogutierrez874@gmail.com', 'password' => 'lolito412/', 'rol' => 'administrador'],
    ['nombre' => 'Samuel',  'email' => 'samuel@landj.local',           'password' => 'tuchulito96', 'rol' => 'usuario'],
    ['nombre' => 'lito412', 'email' => 'lito412@landj.local',          'password' => 'lolito412/', 'rol' => 'usuario'],
    ['nombre' => 'Cuervo',  'email' => 'cuervo@landj.local',           'password' => 'soyunchulo', 'rol' => 'usuario'],
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
