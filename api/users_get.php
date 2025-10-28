<?php
require_once '../conexion.php';
header('Content-Type: application/json');
session_start();

// Debug de sesión
error_log('SESSION: ' . print_r($_SESSION, true));

// Control de acceso: permitir sólo administradores
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado', 'debug' => 'No hay sesión de usuario']);
    exit;
}

if (!is_array($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado', 'debug' => 'Sesión inválida']);
    exit;
}

$currentUser = $_SESSION['user'];
error_log('CURRENT USER: ' . print_r($currentUser, true));

$isAdmin = false;
if (isset($currentUser['id_rol']) && $currentUser['id_rol'] == 1) {
    $isAdmin = true;
    error_log('Es admin por id_rol');
}
if (isset($currentUser['nombre_rol']) && $currentUser['nombre_rol'] === 'admin_sys') {
    $isAdmin = true;
    error_log('Es admin por nombre_rol');
}

if (!$isAdmin) {
    echo json_encode([
        'success' => false, 
        'message' => 'Acceso no autorizado. Se requieren permisos de administrador.',
        'debug' => [
            'id_rol' => $currentUser['id_rol'] ?? null,
            'nombre_rol' => $currentUser['nombre_rol'] ?? null
        ]
    ]);
    exit;
}

    // Usar la API REST de Supabase para obtener usuarios con sus roles
    $usersRes = supabase_request('GET', '/rest/v1/backup_usuarios_20250820?select=id,usuario,id_rol,estado');
    if (isset($usersRes['error'])) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios desde Supabase', 'detail' => $usersRes]);
        exit;
    }

    // Obtener roles
    $rolesRes = supabase_request('GET', '/rest/v1/roles?select=id,nombre_rol');
    if (isset($rolesRes['error'])) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener roles desde Supabase', 'detail' => $rolesRes]);
        exit;
    }

    // Mapear roles por id
    $rolesMap = [];
    foreach ($rolesRes as $r) {
        if (isset($r['id'])) $rolesMap[intval($r['id'])] = $r['nombre_rol'] ?? null;
    }$users = [];
foreach ($usersRes as $u) {
    $id_rol = isset($u['id_rol']) ? intval($u['id_rol']) : null;
    $users[] = [
        'id' => $u['id'] ?? null,
        'usuario' => $u['usuario'] ?? null,
        'id_rol' => $id_rol,
        'nombre_rol' => $id_rol !== null && isset($rolesMap[$id_rol]) ? $rolesMap[$id_rol] : null
    ];
}

echo json_encode(['success' => true, 'users' => $users]);
?>