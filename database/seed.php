<?php
require __DIR__ . '/../public/config/conexion.php';

// ─── Usuarios ────────────────────────────────────────────────────────────────
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
        echo "Actualizado usuario: {$u['username']}\n";
    } else {
        $email = strtolower($u['username']) . '@landj.local';
        $pdo->prepare("INSERT INTO usuarios (nombre, email, contraseña_hash, rol) VALUES (?, ?, ?, 'usuario')")
            ->execute([$u['username'], $email, $hash]);
        echo "Creado usuario: {$u['username']}\n";
    }
}

$stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = 'admin'");
$stmt->execute();
if (!$stmt->fetch()) {
    $hash = password_hash('lolito412/', PASSWORD_ARGON2ID);
    $pdo->prepare("INSERT INTO usuarios (nombre, email, contraseña_hash, rol) VALUES ('admin', 'alvarogutierrez874@gmail.com', ?, 'administrador')")
        ->execute([$hash]);
    echo "Creado usuario: admin\n";
}

$adminId = $pdo->query("SELECT id_usuario FROM usuarios WHERE nombre = 'admin'")->fetchColumn();

// ─── Contactos ───────────────────────────────────────────────────────────────
$contactosSeed = [
    ['nombre' => 'María', 'apellidos' => 'García López',  'email' => 'maria.garcia@ejemplo.com',  'telefono' => '612 345 678', 'empresa' => 'Soluciones Tech SL'],
    ['nombre' => 'Carlos', 'apellidos' => 'Martínez Ruiz', 'email' => 'carlos.martinez@empresa.es', 'telefono' => '623 456 789', 'empresa' => 'Grupo Comercial Norte'],
    ['nombre' => 'Ana',    'apellidos' => 'Fernández Gil',  'email' => 'ana.fernandez@pyme.es',     'telefono' => '634 567 890', 'empresa' => 'Diseños Creativos'],
];

$contactoIds = [];
foreach ($contactosSeed as $c) {
    $stmt = $pdo->prepare("SELECT id_contacto FROM contactos WHERE email = ?");
    $stmt->execute([$c['email']]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        $contactoIds[] = $existing;
        echo "Ya existe contacto: {$c['nombre']}\n";
    } else {
        $pdo->prepare("INSERT INTO contactos (nombre, apellidos, email, telefono, empresa, creado_por) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$c['nombre'], $c['apellidos'], $c['email'], $c['telefono'], $c['empresa'], $adminId]);
        $contactoIds[] = $pdo->lastInsertId();
        echo "Creado contacto: {$c['nombre']} {$c['apellidos']}\n";
    }
}

// ─── Leads ───────────────────────────────────────────────────────────────────
$leadsSeed = [
    ['nombre' => 'Roberto Sanz',   'email' => 'roberto.sanz@nuevo.es',   'empresa' => 'Importaciones Sanz',  'origen' => 'web',      'estado' => 'nuevo'],
    ['nombre' => 'Laura Vidal',    'email' => 'laura.vidal@contacto.es', 'empresa' => 'Eventos Vidal',       'origen' => 'referido', 'estado' => 'contactado'],
    ['nombre' => 'Pedro Romero',   'email' => 'pedro.romero@leads.es',   'empresa' => 'Romero & Asociados',  'origen' => 'web',      'estado' => 'calificado'],
];

$leadIds = [];
foreach ($leadsSeed as $l) {
    $stmt = $pdo->prepare("SELECT id_lead FROM leads WHERE email = ?");
    $stmt->execute([$l['email']]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        $leadIds[] = $existing;
        echo "Ya existe lead: {$l['nombre']}\n";
    } else {
        $pdo->prepare("INSERT INTO leads (nombre, email, empresa, origen, estado, creado_por) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$l['nombre'], $l['email'], $l['empresa'], $l['origen'], $l['estado'], $adminId]);
        $leadIds[] = $pdo->lastInsertId();
        echo "Creado lead: {$l['nombre']}\n";
    }
}

// ─── Oportunidades ───────────────────────────────────────────────────────────
$oportunidadesSeed = [
    [
        'titulo'    => 'Proyecto web corporativo',
        'valor'     => 4500.00,
        'etapa'     => 'propuesta',
        'contacto'  => 0,
        'cierre'    => date('Y-m-d', strtotime('+30 days')),
    ],
    [
        'titulo'    => 'Mantenimiento anual software',
        'valor'     => 1200.00,
        'etapa'     => 'negociacion',
        'contacto'  => 1,
        'cierre'    => date('Y-m-d', strtotime('+15 days')),
    ],
    [
        'titulo'    => 'Consultoría estratégica',
        'valor'     => 2800.00,
        'etapa'     => 'prospecto',
        'contacto'  => 2,
        'cierre'    => date('Y-m-d', strtotime('+60 days')),
    ],
];

$oportunidadIds = [];
foreach ($oportunidadesSeed as $o) {
    $stmt = $pdo->prepare("SELECT id_oportunidad FROM oportunidades WHERE titulo = ?");
    $stmt->execute([$o['titulo']]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        $oportunidadIds[] = $existing;
        echo "Ya existe oportunidad: {$o['titulo']}\n";
    } else {
        $cid = $contactoIds[$o['contacto']] ?? null;
        $pdo->prepare("INSERT INTO oportunidades (titulo, valor, etapa, contacto_id, asignado_a, creado_por, fecha_cierre_esperada) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$o['titulo'], $o['valor'], $o['etapa'], $cid, $adminId, $adminId, $o['cierre']]);
        $oportunidadIds[] = $pdo->lastInsertId();
        echo "Creada oportunidad: {$o['titulo']}\n";
    }
}

// ─── Actividades ─────────────────────────────────────────────────────────────
$actividadesSeed = [
    ['tipo' => 'llamada', 'descripcion' => 'Llamada de presentación inicial.',          'contacto' => 0, 'lead' => null, 'opor' => null],
    ['tipo' => 'nota',    'descripcion' => 'Interesado en el servicio premium.',        'contacto' => null, 'lead' => 1,   'opor' => null],
    ['tipo' => 'reunion', 'descripcion' => 'Reunión de seguimiento del presupuesto.',   'contacto' => 1, 'lead' => null, 'opor' => 0],
    ['tipo' => 'tarea',   'descripcion' => 'Enviar propuesta detallada por email.',     'contacto' => null, 'lead' => null, 'opor' => 1],
];

foreach ($actividadesSeed as $a) {
    $cid = isset($a['contacto'])    ? ($contactoIds[$a['contacto']]      ?? null) : null;
    $lid = isset($a['lead'])        ? ($leadIds[$a['lead']]              ?? null) : null;
    $oid = isset($a['opor'])        ? ($oportunidadIds[$a['opor']]       ?? null) : null;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM actividades WHERE descripcion = ? AND creado_por = ?");
    $stmt->execute([$a['descripcion'], $adminId]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO actividades (tipo, descripcion, fecha, contacto_id, lead_id, oportunidad_id, creado_por) VALUES (?, ?, NOW(), ?, ?, ?, ?)")
            ->execute([$a['tipo'], $a['descripcion'], $cid, $lid, $oid, $adminId]);
        echo "Creada actividad: {$a['tipo']}\n";
    }
}

echo "\nSeed completado.\n";
