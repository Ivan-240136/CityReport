<?php
declare(strict_types=1);

$host = 'db.ghywlqpirjqsmmlyomaw.supabase.co';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres';
$password = 'City_report24';

try {
    $conn = new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbname}",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (Throwable $e) {
    error_log('[DB ERROR] ' . $e->getMessage());
    http_response_code(500);
    exit;
}
