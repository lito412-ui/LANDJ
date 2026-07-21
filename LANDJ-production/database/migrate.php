<?php
/**
 * Runner de migraciones incrementales.
 * Soporta archivos .sql y .php en database/migrations/, ordenados por nombre.
 * Lleva el registro en la tabla `_migraciones`.
 *
 * Uso: php database/migrate.php
 */

$host = getenv('DB_HOST')     !== false ? getenv('DB_HOST')     : 'db';
$port = getenv('DB_PORT')     !== false ? getenv('DB_PORT')     : '3306';
$name = getenv('DB_NAME')     !== false ? getenv('DB_NAME')     : 'landj_db';
$user = getenv('DB_USER')     !== false ? getenv('DB_USER')     : 'root';
$pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';

$dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

$intentos = 0;
$pdo = null;

while ($intentos < 10) {
    try {
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        break;
    } catch (PDOException $e) {
        $intentos++;
        echo "Esperando BD... intento $intentos\n";
        sleep(3);
    }
}

if (!$pdo) {
    echo "ERROR: No se pudo conectar a la base de datos.\n";
    exit(1);
}

// Tabla de control
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `_migraciones` (
        `id`           INT AUTO_INCREMENT PRIMARY KEY,
        `archivo`      VARCHAR(100) NOT NULL UNIQUE,
        `ejecutado_en` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$aplicadas = array_flip(
    $pdo->query("SELECT archivo FROM `_migraciones`")->fetchAll(PDO::FETCH_COLUMN)
);

$dir      = __DIR__ . '/migrations';
$archivos = array_merge(glob($dir . '/*.sql'), glob($dir . '/*.php'));
sort($archivos);

if (empty($archivos)) {
    echo "No hay archivos de migración en $dir\n";
    exit(0);
}

$nuevas = 0;

foreach ($archivos as $ruta) {
    $nombre = basename($ruta);

    if (isset($aplicadas[$nombre])) {
        echo "  [ya aplicada] $nombre\n";
        continue;
    }

    try {
        if (str_ends_with($nombre, '.sql')) {
            $pdo->exec(file_get_contents($ruta));
        } else {
            // Los .php reciben $pdo en scope y hacen echo de su progreso
            (static function (PDO $pdo, string $archivo): void {
                include $archivo;
            })($pdo, $ruta);
        }

        $pdo->prepare("INSERT INTO `_migraciones` (archivo) VALUES (?)")->execute([$nombre]);
        echo "  [OK] $nombre\n";
        $nuevas++;
    } catch (PDOException $e) {
        echo "  [ERROR] $nombre: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo $nuevas > 0
    ? "\n$nuevas migración(es) aplicada(s).\n"
    : "\nBase de datos al día, sin cambios.\n";
