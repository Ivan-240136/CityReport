<?php
require_once '../conexion.php';
header('Content-Type: application/json');
session_start();

// Habilitar todos los errores para debug
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Debug
error_log('POST data: ' . print_r($_POST, true));

try {
    // Verificar método y datos
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        throw new Exception('Usuario y contraseña son requeridos');
    }

    // Usar la función supabase_request para obtener el usuario
    $users_response = supabase_request(
        'GET', 
        "/rest/v1/backup_usuarios_20250820?usuario=eq." . urlencode($usuario) . "&select=*"
    );

    if (isset($users_response['error'])) {
        throw new Exception('Error al consultar usuario: ' . $users_response['error']);
    }

    if (empty($users_response) || !is_array($users_response) || count($users_response) === 0) {
        throw new Exception('Usuario no encontrado');
    }

    $user = $users_response[0];

    // Verificar contraseña
    if ($password !== $user['contrasena']) {
        throw new Exception('Contraseña incorrecta');
    }

    // Obtener el rol
    $roles_response = supabase_request(
        'GET',
        "/rest/v1/roles?id=eq." . $user['id_rol'] . "&select=*"
    );

    $nombre_rol = null;
    if (!isset($roles_response['error']) && !empty($roles_response) && is_array($roles_response)) {
        $nombre_rol = $roles_response[0]['nombre_rol'] ?? null;
    }

    // Guardar en sesión
    $_SESSION['user'] = [
        'id' => $user['id'],
        'usuario' => $user['usuario'],
        'id_rol' => $user['id_rol'],
        'nombre_rol' => $nombre_rol,
        'estado' => $user['estado']
    ];

    // Debug
    error_log('SESSION after login: ' . print_r($_SESSION, true));

    echo json_encode([
        'success' => true,
        'user' => $_SESSION['user']
    ]);

} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}