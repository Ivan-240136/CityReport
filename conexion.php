<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASS');

function getIPv4($hostname) {
    $records = dns_get_record($hostname, DNS_A);
    return $records[0]['ip'] ?? null;
}

$ipv4 = getIPv4($host);

if ($ipv4) {
    $dsn = "pgsql:host=$host;hostaddr=$ipv4;port=$port;dbname=$dbname;sslmode=require";
} else {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
}

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo " Error de conexión: " . htmlspecialchars($e->getMessage());
    exit;
}
?>

