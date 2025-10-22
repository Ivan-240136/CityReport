<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'root';           
$DB_NAME = 'city_report';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    die('Error de conexión: '.$mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
