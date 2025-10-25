<?php
// Endpoint simple y seguro para actualizar un reporte desde el servidor.
// Usa la conexión definida en ../conexion.php y permite actualizar solo columnas permitidas.
// Nota: este endpoint no implementa autenticación. En producción, valida sesión/permiso.

// Evitar que warnings/notices salgan en la respuesta JSON
ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
// Permitir llamadas desde el mismo origen (AJAX desde el frontend). Ajusta si necesitas CORS cross-site.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../conexion.php';

// Debug: mostrar estado de conexión y variables
error_log(sprintf(
    "Debug conexion: PDO=%s, SUPABASE_URL=%s, KEY=%s, ENV=%s, DB_URL=%s",
    $conn ? 'OK' : 'NO',
    $SUPABASE_URL ? 'SI' : 'NO',
    $SUPABASE_ANON_KEY ? 'SI' : 'NO',
    file_exists(__DIR__ . '/../.env') ? 'SI' : 'NO',
    env_get('DATABASE_URL') ? 'SI' : 'NO'
));

if (!$conn) {
    // Si no hay PDO, intentar actualizar vía Supabase REST API
    if (!empty($SUPABASE_URL) && !empty($SUPABASE_ANON_KEY)) {
        error_log('Intentando actualización vía Supabase REST API...');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $id = isset($_POST['id']) ? $_POST['id'] : ($data['id'] ?? null);
        $updates = [];
        if (isset($_POST['updates'])) {
            $decoded = json_decode($_POST['updates'], true);
            if (is_array($decoded)) $updates = $decoded;
        } else {
            $updates = $data['updates'] ?? [];
        }

        if (!$id || empty($updates)) {
            echo json_encode(['error' => true, 'message' => 'ID y updates requeridos']);
            exit;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $SUPABASE_URL . '/rest/v1/reportes?id=eq.' . urlencode($id),
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($updates),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Deshabilitar verificación SSL
            CURLOPT_SSL_VERIFYHOST => 0,     // Deshabilitar verificación de hostname
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $SUPABASE_ANON_KEY,
                'Authorization: Bearer ' . $SUPABASE_ANON_KEY,
                'Prefer: return=minimal'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log(sprintf(
            "REST API Debug: code=%d, error=%s, response=%s, payload=%s",
            $httpCode,
            $curlError,
            $response,
            json_encode($updates)
        ));

        if ($httpCode >= 200 && $httpCode < 300) {
            echo json_encode(['error' => false, 'message' => 'Actualizado correctamente vía REST']);
            exit;
        }

        echo json_encode([
            'error' => true,
            'message' => 'Error actualizando vía REST API',
            'debug' => [
                'code' => $httpCode,
                'curl_error' => $curlError,
                'response' => $response,
                'request' => [
                    'url' => $SUPABASE_URL . '/rest/v1/reportes?id=eq.' . urlencode($id),
                    'updates' => $updates
                ]
            ]
        ]);
        exit;
    }

    echo json_encode([
        'error' => true,
        'message' => 'No hay conexión disponible',
        'debug' => [
            'pdo' => 'NO',
            'supabase_url' => $SUPABASE_URL ? 'SI' : 'NO',
            'supabase_key' => $SUPABASE_ANON_KEY ? 'SI' : 'NO',
            'env_exists' => file_exists(__DIR__ . '/../.env') ? 'SI' : 'NO',
            'db_url' => env_get('DATABASE_URL') ? 'SI' : 'NO'
        ]
    ]);
    http_response_code(500);
    exit;
}

// aceptar JSON en body o form-encoded (application/x-www-form-urlencoded)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if ((!$data || !isset($data['id'])) && !isset($_POST['id'])) {
    echo json_encode(['error' => true, 'message' => 'Payload inválido: se requiere id']);
    http_response_code(400);
    exit;
}

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $updates = [];
    if (isset($_POST['updates'])) {
        $decoded = json_decode($_POST['updates'], true);
        if (is_array($decoded)) $updates = $decoded;
    }
} else {
    $id = $data['id'];
    $updates = $data['updates'] ?? [];
}
if (!is_array($updates) || empty($updates)) {
    echo json_encode(['error' => true, 'message' => 'No hay campos para actualizar']);
    http_response_code(400);
    exit;
}

// Lista blanca de columnas permitidas para actualizar. Añade o modifica según tu esquema.
$allowed = [
    'remitente','calle','detalles','observaciones','id_clasificacion','clasificacion_id','id_clas','id_colonia','colonia_id','estatus','estado','status','imagen_url','fecha_actualizacion'
];

// Filtrar updates sólo a columnas permitidas
$sets = [];
$params = [];
foreach ($updates as $k => $v) {
    if (!in_array($k, $allowed, true)) continue;
    // Normalizar nombre de parámetro
    $paramName = ':'.preg_replace('/[^a-zA-Z0-9_]/', '_', $k);
    $sets[] = "$k = $paramName";
    $params[$paramName] = $v;
}

if (empty($sets)) {
    echo json_encode(['error' => true, 'message' => 'No se proporcionaron columnas válidas para actualizar']);
    http_response_code(400);
    exit;
}

try {
    $sql = 'UPDATE reportes SET ' . implode(', ', $sets) . ' WHERE id = :_id';
    $stmt = $conn->prepare($sql);
    // bind id
    $stmt->bindValue(':_id', $id);
    foreach ($params as $p => $val) {
        $stmt->bindValue($p, $val);
    }
    $stmt->execute();

    echo json_encode(['error' => false, 'message' => 'Actualizado correctamente']);
    http_response_code(200);
    exit;
} catch (PDOException $ex) {
    error_log('Error updating report: ' . $ex->getMessage());
    echo json_encode(['error' => true, 'message' => 'Error al actualizar: ' . $ex->getMessage()]);
    http_response_code(500);
    exit;
}

