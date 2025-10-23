<?php
$autoload = __DIR__ . '/vendor/autoload.php';
echo "autoload.php existe: " . (file_exists($autoload) ? 'sí' : 'no') . PHP_EOL;
if (file_exists($autoload)) {
    require $autoload;
}

$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo ".env no encontrado en " . __DIR__ . PHP_EOL;
} else {
    echo "Contenido bruto de .env:\n";
    echo "-------------------------\n";
    echo file_get_contents($envFile) . "\n";
    echo "-------------------------\n";

    if (class_exists('Dotenv\\Dotenv')) {
        echo "La clase Dotenv\\Dotenv está disponible. Intentando cargar .env...\n";
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
            $dotenv->safeLoad();
            echo "Dotenv::safeLoad() ejecutado.\n";
        } catch (Throwable $e) {
            echo "Error cargando .env con phpdotenv: " . $e->getMessage() . PHP_EOL;
        }
    } else {
        echo "La clase Dotenv\\Dotenv NO está disponible (phptdotenv no instalado).\n";
    }

    $keys = ['SUPABASE_URL','SUPABASE_ANON_KEY','PG_HOST','PG_PORT','PG_DB','PG_USER','PG_PASS'];
    $out = [];
    foreach ($keys as $k) {
        $out[$k] = getenv($k);
    }

    echo "Valores desde getenv():\n";
    var_export($out);
    echo PHP_EOL;

    echo "\nValores en \\$_ENV (solo claves relevantes):\n";
    $envSubset = [];
    foreach ($keys as $k) {
        $envSubset[$k] = array_key_exists($k, $_ENV) ? $_ENV[$k] : null;
    }
    var_export($envSubset);
    echo PHP_EOL;

    echo "\nValores en \\$_SERVER (solo claves relevantes):\n";
    $srvSubset = [];
    foreach ($keys as $k) {
        $srvSubset[$k] = array_key_exists($k, $_SERVER) ? $_SERVER[$k] : null;
    }
    var_export($srvSubset);
    echo PHP_EOL;
}
