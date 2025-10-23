<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php'); 
    exit;
}

$usuario = $_SESSION['user']['usuario']; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 100px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; }
        .welcome { text-align: center; font-size: 18px; }
        .logout { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Bienvenido a CityReport</h2>
        <p class="welcome">Hola, <?= htmlspecialchars($usuario) ?>. Has iniciado sesión correctamente.</p>
        <div class="logout">
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </div>
</body>
</html>
