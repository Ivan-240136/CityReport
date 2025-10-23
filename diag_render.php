<?php
// Mostrar diagnóstico NO sensible (elimínalo al terminar)
header('Content-Type: text/plain; charset=utf-8');

echo "PHP: " . PHP_VERSION . PHP_EOL;
echo "PDO cargado: " . (extension_loaded('pdo') ? "SI" : "NO") . PHP_EOL;
echo "PDO_PGSQL cargado: " . (extension_loaded('pdo_pgsql') ? "SI" : "NO") . PHP_EOL;

// Verifica que las ENV existen (sin imprimir secretos)
$keys = ['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS'];
foreach ($keys as $k) {
  $v = getenv($k);
  $mask = ($k === 'DB_PASS') ? '***' : ($v ?: '(vacío)');
  echo "$k: " . ($v ? ($k === 'DB_PASS' ? $mask : $v) : '(no definida)') . PHP_EOL;
}

echo str_repeat('-', 40) . PHP_EOL;

try {
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
        getenv('DB_HOST'),
        getenv('DB_PORT') ?: '5432',
        getenv('DB_NAME')
    );
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $row = $pdo->query("SELECT NOW()")->fetch(PDO::FETCH_NUM);
    echo "Conexión OK. NOW(): " . $row[0] . PHP_EOL;
} catch (Throwable $e) {
    echo "FALLO de conexión: " . $e->getMessage() . PHP_EOL;
}
