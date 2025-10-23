<?php
session_start();

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['usuario'];
    $p = $_POST['contrasena'];

    $authenticated = false;
    $user = null;

    // Si existe conexión a la DB (PG_*), validar contra la tabla local 'usuarios'
    if (isset($conn) && $conn instanceof PDO) {
        try {
            $stmt = $conn->prepare('SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $u]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $stored = $row['contrasena'] ?? '';
                // Si la contraseña almacenada parece un hash (bcrypt / password_hash), usar password_verify
                if (password_needs_rehash($stored, PASSWORD_DEFAULT) || strpos($stored, '$2y$') === 0 || strpos($stored, '$2b$') === 0) {
                    // password_verify manejará hashes; si no coincide, falla
                    if (password_verify($p, $stored)) {
                        $authenticated = true;
                        $user = $row;
                    }
                } else {
                    // Comparación en texto plano (no recomendado), mantener compatibilidad
                    if (hash_equals($stored, $p)) {
                        $authenticated = true;
                        $user = $row;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error al validar usuario en DB: ' . $e->getMessage());
            // si falla la consulta, seguimos al flujo de Supabase Auth
            $authenticated = false;
            $user = null;
        }
    }

    // Si no autenticó con la DB local, intentar con Supabase Auth (email/password)
    if (!$authenticated) {
        $auth = supabase_signin($u, $p);
        if (isset($auth['access_token'])) {
            $authenticated = true;
            $user = $auth['user'] ?? supabase_get_user($auth['access_token']);
            // guardar tokens también
            $_SESSION['user_tokens'] = [
                'access_token' => $auth['access_token'],
                'refresh_token' => $auth['refresh_token'] ?? null,
            ];
        } else {
            if (isset($auth['error'])) {
                $err = $auth['message'] ?? $auth['error'];
            } else {
                $err = 'Usuario o contraseña incorrectos';
            }
        }
    }

    if ($authenticated && $user) {
        // Normalizar lo que almacenamos en la sesión
        $_SESSION['user'] = [
            'id' => $user['id'] ?? $user['id_usuario'] ?? null,
            'usuario' => $user['usuario'] ?? $user['email'] ?? $u,
        ];

        header('Location: index.php');
        exit;
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
