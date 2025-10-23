<?php
session_start();

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['usuario'];
    $p = $_POST['contrasena'];

    $stmt = $conn->prepare('SELECT * FROM usuarios WHERE usuario = :usuario AND contrasena = :contrasena');
    $stmt->execute([':usuario' => $u, ':contrasena' => $p]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'usuario' => $user['usuario'],
            'id_rol' => $user['id_rol'],
        ];
        header('Location: index.php');
        exit;  
    } else {
        $err = 'Usuario o contraseña incorrectos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 400px; margin: 100px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        button { width: 100%; padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Iniciar Sesión</h2>
        <?php if (isset($err)): ?>
            <p class="error"><?= $err ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required>
            <label for="contrasena">Contraseña</label>
            <input type="password" id="contrasena" name="contrasena" required>
            <button type="submit">Iniciar sesión</button>
        </form>
    </div>
</body>
</html>
