<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables de entorno desde .env si existe
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

/**
 * Obtener variable de entorno con fallback a \\$_ENV y \\$_SERVER cuando getenv no devuelve valor.
 */
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

$SUPABASE_URL = rtrim((string)(env_get('SUPABASE_URL') ?: ''), '/');
$SUPABASE_ANON_KEY = env_get('SUPABASE_ANON_KEY');

if (empty($SUPABASE_URL) || empty($SUPABASE_ANON_KEY)) {
    // Si faltan las variables, dejamos un mensaje útil. El proyecto puede manejar esto de otra forma.
    // No terminamos el script en producción; aquí mostramos un aviso para el desarrollador.
    error_log('Aviso: SUPABASE_URL o SUPABASE_ANON_KEY no están definidas en las variables de entorno.');
}

/**
 * Realiza una petición HTTP a la API de Supabase Auth.
 * @param string $method GET|POST
 * @param string $path Ruta a partir de la URL base de Supabase (p.ej. '/auth/v1/token?grant_type=password')
 * @param array|null $data Datos que se enviarán como JSON
 * @param string|null $access_token Si se provee, se usará como Bearer token (user session)
 * @return array Decodificación JSON de la respuesta o array con 'error'
 */
function supabase_request(string $method, string $path, ?array $data = null, ?string $access_token = null): array
{
    global $SUPABASE_URL, $SUPABASE_ANON_KEY;

    $url = $SUPABASE_URL . $path;

    $ch = curl_init();
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $SUPABASE_ANON_KEY,
    ];

    if (!empty($access_token)) {
        $headers[] = 'Authorization: Bearer ' . $access_token;
    } else {
        // Para peticiones públicas o de autenticación usamos la anon key como Authorization también
        $headers[] = 'Authorization: Bearer ' . $SUPABASE_ANON_KEY;
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

    if ($data !== null) {
        $json = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['error' => 'curl_error', 'message' => $err];
    }

    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($decoded === null) {
        // Respuesta no JSON
        return ['error' => 'invalid_response', 'status' => $httpCode, 'body' => $response];
    }

    // Añadir código HTTP por si lo necesita quien llame
    $decoded['_http_code'] = $httpCode;
    return $decoded;
}

/**
 * Iniciar sesión con email/contraseña usando Supabase Auth.
 * @return array|false
 */
function supabase_signin(string $email, string $password)
{
    // Endpoint para iniciar sesión mediante password
    $path = '/auth/v1/token?grant_type=password';
    $body = ['email' => $email, 'password' => $password];

    $res = supabase_request('POST', $path, $body);

    if (isset($res['error']) || isset($res['status_code']) && $res['status_code'] >= 400) {
        return ['error' => $res['error'] ?? 'auth_failed', 'message' => $res['message'] ?? null];
    }

    // Respuesta esperada: access_token, refresh_token, expires_in, token_type, user
    if (isset($res['access_token'])) {
        return $res;
    }

    return ['error' => 'no_token', 'body' => $res];
}

/**
 * Obtener usuario a partir del access_token
 */
function supabase_get_user(string $access_token)
{
    $path = '/auth/v1/user';
    $res = supabase_request('GET', $path, null, $access_token);

    if (isset($res['error'])) {
        return ['error' => $res['error'], 'message' => $res['message'] ?? null];
    }

    return $res;
}

/**
 * Cerrar sesión (revocar token) — opcionalmente se puede llamar al endpoint de logout
 */
function supabase_signout(string $access_token)
{
    $path = '/auth/v1/logout';
    $res = supabase_request('POST', $path, null, $access_token);
    return $res;
}

$conn = null;
$databaseUrl = env_get('DATABASE_URL');
if ($databaseUrl && $databaseUrl !== false) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false) {
        $pg_host = $parts['host'] ?? null;
        $pg_port = $parts['port'] ?? ($parts['query']['port'] ?? '5432');
        $pg_db = isset($parts['path']) ? ltrim($parts['path'], '/') : null;
        $pg_user = $parts['user'] ?? null;
        $pg_pass = $parts['pass'] ?? null;
    } else {
        $pg_host = env_get('PG_HOST');
        $pg_db = env_get('PG_DB');
        $pg_user = env_get('PG_USER');
        $pg_pass = env_get('PG_PASS');
        $pg_port = env_get('PG_PORT') ?: '5432';
    }
} else {
    $pg_host = env_get('PG_HOST');
    $pg_db = env_get('PG_DB');
    $pg_user = env_get('PG_USER');
    $pg_pass = env_get('PG_PASS');
    $pg_port = env_get('PG_PORT') ?: '5432';
}

if ($pg_host && $pg_db && $pg_user) {
    try {
        // Usar sslmode=require para conexiones SSL sin verificación de certificado
        $dsn = "pgsql:host={$pg_host};port={$pg_port};dbname={$pg_db};sslmode=require";
        $conn = new PDO($dsn, $pg_user, $pg_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log('Conexión PDO establecida correctamente');
    } catch (PDOException $e) {
        error_log('No se pudo conectar a Postgres: ' . $e->getMessage());
        error_log('DSN usado: ' . preg_replace('/pass=([^;]+)/', 'pass=***', $dsn));
        $conn = null;
    }
}
?>
