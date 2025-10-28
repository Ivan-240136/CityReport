<?php
require_once '../conexion.php';
session_start();

// Verificar si el usuario está autenticado y es admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$conn = conectar();

$id = $_POST['id'];
$username = $_POST['username'];
$password = $_POST['password'];
$role = $_POST['role'];

// Validar el rol
if ($role !== 'admin' && $role !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Rol no válido']);
    exit;
}

// Si hay una nueva contraseña, actualizarla
if (!empty($password)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET username = ?, password = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $hashed_password, $role, $id);
} else {
    // Si no hay nueva contraseña, actualizar solo el nombre de usuario y rol
    $stmt = $conn->prepare("UPDATE usuarios SET username = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $role, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario']);
}

$conn->close();