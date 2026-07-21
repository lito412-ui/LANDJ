<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

function ok(array $data): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}

if (!isset($_SESSION['user_id'])) {
    err('No autorizado', 401);
    exit;
}

$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

// ─── Disco ────────────────────────────────────────────────────────────────
$disco_ruta   = $isWindows ? 'C:' : '/';
$disco_total  = disk_total_space($disco_ruta);
$disco_libre  = disk_free_space($disco_ruta);
$disco_pct    = $disco_total > 0
    ? round(($disco_total - $disco_libre) / $disco_total * 100, 1)
    : 0;

// ─── CPU ──────────────────────────────────────────────────────────────────
function get_cpu_pct() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $out = @shell_exec('wmic cpu get loadpercentage /value 2>nul');
        if ($out && preg_match('/LoadPercentage=(\d+)/i', $out, $m)) {
            return (float) $m[1];
        }
        return 0.0;
    }

    // Linux: dos lecturas separadas 200 ms para calcular delta real
    $leer = function () {
        $fh   = @fopen('/proc/stat', 'r');
        if (!$fh) return null;
        $line = fgets($fh);
        fclose($fh);
        $cols = preg_split('/\s+/', trim($line));
        array_shift($cols); // quita "cpu"
        $idle  = (float)($cols[3] ?? 0) + (float)($cols[4] ?? 0); // idle + iowait
        $total = array_sum(array_map('floatval', $cols));
        return ['idle' => $idle, 'total' => $total];
    };

    $a = $leer();
    usleep(200000);
    $b = $leer();

    if (!$a || !$b) return 0.0;
    $dTotal = $b['total'] - $a['total'];
    $dIdle  = $b['idle']  - $a['idle'];

    return $dTotal > 0 ? round((1 - $dIdle / $dTotal) * 100, 1) : 0.0;
}

// ─── RAM ──────────────────────────────────────────────────────────────────
function get_ram_info() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $out = @shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /value 2>nul');
        if ($out) {
            preg_match('/FreePhysicalMemory=(\d+)/i',      $out, $free);
            preg_match('/TotalVisibleMemorySize=(\d+)/i',  $out, $total);
            $totalMb = isset($total[1]) ? round($total[1] / 1024, 1) : 0;
            $freeMb  = isset($free[1])  ? round($free[1]  / 1024, 1) : 0;
            return ['usada' => round($totalMb - $freeMb, 1), 'total' => $totalMb];
        }
        return ['usada' => 0, 'total' => 0];
    }

    $lines   = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $totalKb = 0;
    $availKb = 0;
    foreach ($lines as $line) {
        if (str_starts_with($line, 'MemTotal:'))     $totalKb = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
        if (str_starts_with($line, 'MemAvailable:')) $availKb = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
    }
    return [
        'usada' => round(max(0, $totalKb - $availKb) / 1024, 1),
        'total' => round($totalKb / 1024, 1),
    ];
}

$cpu = get_cpu_pct();
$ram = get_ram_info();

ok([
    'disco'     => $disco_pct,
    'cpu'       => $cpu,
    'ram_usada' => $ram['usada'],
    'ram_total' => $ram['total'],
    'timestamp' => time(),
]);
