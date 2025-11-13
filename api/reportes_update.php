<?php
// Endpoint simple y seguro para actualizar un reporte desde el servidor.
// Usa la conexión definida en ../conexion.php y permite actualizar solo columnas permitidas.

// Evitar que warnings/notices salgan en la respuesta JSON
ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../conexion.php';

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
?>

