<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$disco_ruta = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "C:" : "/";
$disco_total = disk_total_space($disco_ruta);
$disco_libre = disk_free_space($disco_ruta);
$porcentaje_disco = round((($disco_total - $disco_libre) / $disco_total) * 100, 1);

// Esto lee el tiempo total de CPU que el sistema ha procesado
function get_server_cpu_usage() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') return 0;
    
    $stats = file_get_contents("/proc/stat");
    if ($stats === false) return 0;
    
    $lines = explode("\n", $stats);
    $cpuStats = explode(" ", preg_replace("/ +/", " ", $lines[0]));
    
    // Esto nos da un valor "raw" acumulado real, similar al que entrega Docker
    return $cpuStats[2] + $cpuStats[3] + $cpuStats[4] + $cpuStats[5] + $cpuStats[6] + $cpuStats[7] + $cpuStats[8] + $cpuStats[9];
}

$cpu_total_real = get_server_cpu_usage();

function get_server_ram_usage() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') return 300;
    $memInfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($memInfo === false) {
        return 0;
    }

    $totalKb = 0;
    $availableKb = 0;
    foreach ($memInfo as $line) {
        if (strpos($line, 'MemTotal:') === 0) {
            $totalKb = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
        } elseif (strpos($line, 'MemAvailable:') === 0) {
            $availableKb = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
        }
    }

    if ($totalKb <= 0) {
        return 0;
    }

    $usedKb = max(0, $totalKb - $availableKb);
    return round($usedKb / 1024, 2); // Retorna memoria usada en MB
}

$ram_usada = get_server_ram_usage();


$contenedores = [
    [
        "nombre" => "Sistema_Global",
        "memoria_mb" => $ram_usada,
        "cpu_raw" => $cpu_total_real * 10000000 // Escalado para que el JS lo procese
    ]
];

echo json_encode([
    "status" => "success",
    "disco" => $porcentaje_disco,
    "contenedores" => $contenedores,
    "timestamp" => time()
]);