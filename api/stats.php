<?php
header('Content-Type: application/json; charset=utf-8');
// Endpoint servidor para calcular métricas de estadísticas
require_once __DIR__ . '/../conexion.php';

// Si no hay conexión PDO disponible intentaremos un fallback por REST usando la Service Role Key
function supabase_rest_request(string $path, string $method = 'GET', array $params = []){
    // $path: ruta relativa en /rest/v1 (sin base)
    global $SUPABASE_URL;
    $serviceKey = env_get('SUPABASE_SERVICE_ROLE') ?: env_get('SUPABASE_SERVICE_KEY');
    if (!$serviceKey) return ['error' => 'no_service_key', 'message' => 'No se encontró SUPABASE_SERVICE_ROLE en las variables de entorno.'];

    $url = rtrim($SUPABASE_URL, '/') . '/rest/v1/' . ltrim($path, '/');
    if (!empty($params) && $method === 'GET') {
        $qs = http_build_query($params);
        $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
    }

    $ch = curl_init();
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $serviceKey,
        'Authorization: Bearer ' . $serviceKey,
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

    if ($method !== 'GET' && !empty($params)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['error' => 'curl_error', 'message' => $err];
    }
    curl_close($ch);

    $decoded = json_decode($resp, true);
    return ['status' => $code, 'body' => $decoded, 'raw' => $resp];
}

if (!isset($conn) || $conn === null) {
    // Intentaremos fallback por REST
    // Primero intentamos obtener todos los reportes mínimos
    $reportsResp = supabase_rest_request('reportes', 'GET', ['select' => 'id,estado,colonia_id,clasificacion_id,created_at', 'limit' => '10000']);
    if (isset($reportsResp['error'])){
        http_response_code(500);
        echo json_encode(['error' => 'db_connection', 'message' => 'No se pudo conectar a la base de datos desde el servidor.', 'details' => $reportsResp]);
        exit;
    }
    if (!is_array($reportsResp['body'])){
        http_response_code(500);
        echo json_encode(['error' => 'rest_invalid', 'message' => 'Respuesta inválida de Supabase REST', 'details' => $reportsResp]);
        exit;
    }

    $reports = $reportsResp['body'];

    // Calcular métricas a partir de $reports
    $total = count($reports);
    $pend = 0; $proc = 0; $res = 0;
    $colCount = []; $clasCount = []; $dayCount = [];
    foreach ($reports as $r){
        $estado = $r['estado'] ?? null;
        if ($estado === 'Pendiente') $pend++;
        if ($estado === 'En Proceso') $proc++;
        if ($estado === 'Resuelto') $res++;
        if (!empty($r['colonia_id'])) $colCount[$r['colonia_id']] = ($colCount[$r['colonia_id']] ?? 0) + 1;
        if (!empty($r['clasificacion_id'])) $clasCount[$r['clasificacion_id']] = ($clasCount[$r['clasificacion_id']] ?? 0) + 1;
        if (!empty($r['created_at'])){
            $dow = (int) (new DateTime($r['created_at']))->format('w');
            $dayCount[$dow] = ($dayCount[$dow] ?? 0) + 1;
        }
    }

    // Resolver nombres top usando REST
    $topCol = null; $topClas = null; $topDay = null;
    if (!empty($colCount)){
        arsort($colCount);
        $topId = array_key_first($colCount);
        $colResp = supabase_rest_request('colonias', 'GET', ['select' => 'nombre_colonia', 'id' => 'eq.' . $topId]);
        $topCol = is_array($colResp['body']) && count($colResp['body']) ? ['nombre_colonia' => $colResp['body'][0]['nombre_colonia'], 'cnt' => $colCount[$topId]] : null;
    }
    if (!empty($clasCount)){
        arsort($clasCount);
        $topId = array_key_first($clasCount);
        $clResp = supabase_rest_request('clasificaciones', 'GET', ['select' => 'nombre', 'id' => 'eq.' . $topId]);
        $topClas = is_array($clResp['body']) && count($clResp['body']) ? ['nombre' => $clResp['body'][0]['nombre'], 'cnt' => $clasCount[$topId]] : null;
    }
    if (!empty($dayCount)){
        arsort($dayCount);
        $top = array_key_first($dayCount);
        $topDay = ['dow' => (int)$top, 'cnt' => $dayCount[$top]];
    }

    $tasa = ($total > 0) ? (int) round(($res / $total) * 100) : 0;
    $response = [
        'total' => $total,
        'pendientes' => $pend,
        'en_proceso' => $proc,
        'resueltos' => $res,
        'colonia_mas_frecuente' => $topCol,
        'clasificacion_mas_comun' => $topClas,
        'pico_dia' => $topDay,
        'tasa_resolucion' => ['percent' => $tasa, 'resueltos' => $res, 'total' => $total],
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Totales por estado
    $stmt = $conn->query("SELECT COUNT(*)::int AS total FROM reportes");
    $total = (int) $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*)::int AS cnt FROM reportes WHERE estado = :estado");
    $stmt->execute([':estado' => 'Pendiente']);
    $pend = (int) $stmt->fetchColumn();

    $stmt->execute([':estado' => 'En Proceso']);
    $proc = (int) $stmt->fetchColumn();

    $stmt->execute([':estado' => 'Resuelto']);
    $res = (int) $stmt->fetchColumn();

    // Colonia más frecuente
    $sql = "SELECT c.nombre_colonia, COUNT(r.id)::int AS cnt
            FROM reportes r
            JOIN colonias c ON c.id = r.colonia_id
            GROUP BY c.nombre_colonia
            ORDER BY cnt DESC
            LIMIT 1";
    $stmt = $conn->query($sql);
    $topCol = $stmt->fetch(PDO::FETCH_ASSOC);

    // Clasificación más común
    $sql = "SELECT cl.nombre, COUNT(r.id)::int AS cnt
            FROM reportes r
            JOIN clasificaciones cl ON cl.id = r.clasificacion_id
            GROUP BY cl.nombre
            ORDER BY cnt DESC
            LIMIT 1";
    $stmt = $conn->query($sql);
    $topClas = $stmt->fetch(PDO::FETCH_ASSOC);

    // Pico de actividad semanal (día con más reportes)
    $sql = "SELECT EXTRACT(DOW FROM created_at)::int AS dow, COUNT(*)::int AS cnt
            FROM reportes
            WHERE created_at IS NOT NULL
            GROUP BY dow
            ORDER BY cnt DESC
            LIMIT 1";
    $stmt = $conn->query($sql);
    $topDay = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tasa de resolución
    $tasa = ($total > 0) ? (int) round(($res / $total) * 100) : 0;

    $response = [
        'total' => $total,
        'pendientes' => $pend,
        'en_proceso' => $proc,
        'resueltos' => $res,
        'colonia_mas_frecuente' => $topCol ?: null,
        'clasificacion_mas_comun' => $topClas ?: null,
        'pico_dia' => $topDay ?: null,
        'tasa_resolucion' => ['percent' => $tasa, 'resueltos' => $res, 'total' => $total],
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'query_failed', 'message' => $e->getMessage()]);
}

?>
