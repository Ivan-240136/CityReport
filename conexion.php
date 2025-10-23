<?php
$host     = getenv('DB_HOST') ?: '';
$port     = getenv('DB_PORT') ?: '5432';
$dbname   = getenv('DB_NAME') ?: '';
$user     = getenv('DB_USER') ?: '';
$password = getenv('DB_PASS') ?: '';

function getIPv4(string $hostname): ?string {
    $records = @dns_get_record($hostname, DNS_A);
    return ($records && isset($records[0]['ip'])) ? $records[0]['ip'] : null;
}

$ipv4 = $host ? getIPv4($host) : null;

$dsn = $ipv4
    ? "pgsql:host=$host;hostaddr=$ipv4;port=$port;dbname=$dbname;sslmode=require"
    : "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

try {
    $conn = new PDO(
        $dsn,
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]
    );
} catch (PDOException $e) {
    error_log('[DB] ' . $e->getMessage());
    http_response_code(500);
    exit;
}
