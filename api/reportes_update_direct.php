<?php
// Endpoint directo a PostgreSQL para actualizar reportes
// Evita problemas con REST API de Supabase

ini_set('display_errors', '0');
error_reporting(E_ALL);

// Headers CORS permisivos
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 3600');
header('Content-Type: application/json; charset=utf-8');

// Responder a preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Solo se acepta POST']);
    exit;
}

require_once __DIR__ . '/../conexion.php';

// Obtener datos del POST
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

error_log('reportes_update_direct.php - ID: ' . $id . ', Updates: ' . json_encode($updates));

if (!$id || empty($updates)) {
    echo json_encode(['error' => true, 'message' => 'ID y updates requeridos']);
    http_response_code(400);
    exit;
}

// Columnas permitidas para actualizar
$allowed = [
    'remitente', 'calle', 'detalles', 'observaciones', 
    'id_clasificacion', 'clasificacion_id', 'id_clas', 
    'id_colonia', 'colonia_id', 
    'estatus', 'estado', 'status', 
    'imagen_url', 'fecha_actualizacion'
];

// Construir sentencia UPDATE
$sets = [];
$params = [];

foreach ($updates as $k => $v) {
    if (!in_array($k, $allowed, true)) continue;
    
    $paramName = ':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $k);
    $sets[] = "$k = $paramName";
    $params[$paramName] = $v;
}

if (empty($sets)) {
    echo json_encode(['error' => true, 'message' => 'No se proporcionaron columnas válidas para actualizar']);
    http_response_code(400);
    exit;
}

try {
    if (!$conn) {
        throw new Exception('No hay conexión a la base de datos');
    }

    $sql = 'UPDATE reportes SET ' . implode(', ', $sets) . ' WHERE id = :_id';
    error_log('Ejecutando SQL: ' . $sql);
    error_log('Parámetros: ' . json_encode(array_merge(['_id' => $id], $params)));

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':_id', $id, PDO::PARAM_STR);
    
    foreach ($params as $p => $val) {
        $stmt->bindValue($p, $val);
    }
    
    $stmt->execute();
    
    $rowsAffected = $stmt->rowCount();
    error_log("Filas afectadas: $rowsAffected");

    echo json_encode([
        'error' => false, 
        'message' => 'Actualizado correctamente',
        'rows_affected' => $rowsAffected
    ]);
    http_response_code(200);
    exit;

} catch (Exception $ex) {
    error_log('Error actualizando reporte: ' . $ex->getMessage());
    echo json_encode([
        'error' => true, 
        'message' => 'Error al actualizar: ' . $ex->getMessage()
    ]);
    http_response_code(500);
    exit;
}
?>
