<?php

$SUPABASE_URL = getenv('SUPABASE_URL');  
$SUPABASE_ANON_KEY = getenv('SUPABASE_ANON_KEY');  

try {
    
    $conn = new PDO("pgsql:host=$SUPABASE_URL;dbname=postgres", "postgres", $SUPABASE_ANON_KEY); 
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
