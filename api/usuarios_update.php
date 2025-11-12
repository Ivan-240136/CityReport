<?php
// Actualiza campos de un usuario por ID (usa service key y PostgREST)
header('Content-Type: application/json; charset=utf-8');

$SUPABASE_URL = getenv('SUPABASE_URL');
$SERVICE_KEY  = getenv('SUPABASE_SERVICE_KEY');

$id = $_POST['id'] ?? null;
$updatesJson = $_POST['updates'] ?? '';

if (!$id || !$updatesJson) {
  http_response_code(400);
  echo json_encode(['error'=>true,'message'=>'Faltan parámetros']); exit;
}

$ch = curl_init();
curl_setopt_array($ch, [
  CURLOPT_URL => $SUPABASE_URL . '/rest/v1/usuarios?id=eq.' . urlencode($id),
  CURLOPT_CUSTOMREQUEST => 'PATCH',
  CURLOPT_HTTPHEADER => [
    'apikey: ' . $SERVICE_KEY,
    'Authorization: Bearer ' . $SERVICE_KEY,
    'Content-Type: application/json',
    'Prefer: return=representation'
  ],
  CURLOPT_POSTFIELDS => $updatesJson,
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code >= 200 && $code < 300) {
  echo json_encode(['ok'=>true,'message'=>'Usuario actualizado']); 
} else {
  http_response_code($code ?: 500);
  echo json_encode(['error'=>true,'message'=>$resp ?: 'Error en actualización']);
}
