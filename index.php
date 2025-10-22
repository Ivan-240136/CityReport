<?php
require_once 'auth.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CityReport | Inicio</title>
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<header class="encabezado">
  <div class="contenedor">
    <div class="logo-wrap">
      <img src="img/logo_city.png" alt="Logo CityReport" class="logo">
    </div>
    <h1>CityReport</h1>
    <p class="subtitulo">Sistema de Reportes Urbanos</p>
  </div>
</header>

<nav class="menu">
  <ul>
    <li><a href="index.php" class="activo">Inicio</a></li>
    <li><a href="panel/dashboard.php">Panel</a></li>
    <li><a href="logout.php">Cerrar sesión (<?=htmlspecialchars($_SESSION['user']['usuario'])?>)</a></li>
  </ul>
</nav>

<main class="principal">
  <div class="contenedor">
    <h2>Bienvenido a CityReport</h2>
    <p>Has iniciado sesión como <strong><?=htmlspecialchars($_SESSION['user']['usuario'])?></strong> 
      </p>

    <div class="botones">
      <a href="panel/dashboard.php" class="btn">Ir al Panel</a>
    </div>
  </div>
</main>

<footer class="pie">
  <p>© 2025 CityReport | Proyecto Integrador UTXJ</p>
</footer>
</body>
</html>