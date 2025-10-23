<?php
require 'vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

if (!function_exists('env_get')) {
    function env_get(string $key)
    {
        $v = getenv($key);
        if ($v !== false) {
            return $v;
        }
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== null) {
            return $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER) && $_SERVER[$key] !== null) {
            return $_SERVER[$key];
        }
        return false;
    }
}

$host = env_get('PG_HOST');
$port = env_get('PG_PORT') ?: 5432;
$db = env_get('PG_DB');
$user = env_get('PG_USER');
$pass = env_get('PG_PASS');

if (!$host || !$db || !$user) {
    echo "Faltan variables PG_* en .env\n";
    exit(1);
}

$dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Conexión a Postgres OK\n";
    $stmt = $pdo->query("SELECT now() as now");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Fecha del servidor DB: " . ($row['now'] ?? 'n/a') . "\n";
} catch (PDOException $e) {
    echo "Error al conectar: " . $e->getMessage() . "\n";
    exit(1);
}