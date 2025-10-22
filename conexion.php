<?php
$mysqli = new mysqli('127.0.0.1', 'root', 'root', 'city_report', 3306);
if ($mysqli->connect_errno) {
    die('Error de conexión: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
?>