<?php
session_start();
require_once 'conexion.php';

if (isset($_SESSION['user']['access_token'])) {
	$access = $_SESSION['user']['access_token'];
	try {
		supabase_signout($access);
	} catch (Exception $e) {
		error_log('Error al hacer signout en Supabase: ' . $e->getMessage());
	}
}

session_unset();
session_destroy();

header('Location: login.php');
exit;
?>
