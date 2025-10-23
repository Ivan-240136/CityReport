<?php
session_start();

if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
}

$usuario = isset($_SESSION['user']['usuario']) ? $_SESSION['user']['usuario'] : 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bienvenido - CityReport</title>
        <link rel="stylesheet" href="css/estilos.css">
        <style>
            /* Small overrides to ensure the welcome box looks good within the theme */
            .welcome-box { max-width: 900px; margin: 40px auto; background: #fff; padding: 28px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); text-align: center; }
            .welcome-box h2 { margin: 0 0 10px; }
            .logout a { color: var(--azul); font-weight: 700; text-decoration: none; }
        </style>
</head>
<body>
        <header class="encabezado">
            <div class="contenedor">
                <a class="logo-wrap" href="index.php">
                    <img src="img/logo_city.png" alt="CityReport" class="logo">
                </a>
                <h1>CityReport</h1>
                <p class="subtitulo">Panel de administración</p>
            </div>
        </header>

        <nav class="menu">
            <ul>
                <li><a class="activo" href="index.php">Inicio</a></li>
                <li><a href="#">Reportes</a></li>
                <li><a href="#">Usuarios</a></li>
                <li><a href="logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>

        <main class="principal">
            <div class="welcome-box">
                <h2>Bienvenido a CityReport</h2>
                <p class="lead">Hola, <?= htmlspecialchars($usuario) ?>. Has iniciado sesión correctamente.</p>
                <div class="botones" style="justify-content:center; margin-top:18px;">
                    <a class="btn" href="#">Ver reportes</a>
                    <a class="btn2" href="#">Crear reporte</a>
                </div>
                <div class="logout" style="margin-top:18px;">
                    <a href="logout.php">Cerrar sesión</a>
                </div>
            </div>
        </main>

        <footer class="pie">
            <div class="contenedor">&copy; <?= date('Y') ?> CityReport — Todos los derechos reservados</div>
        </footer>
</body>
</html>
