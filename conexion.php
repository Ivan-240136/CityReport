<?php
define('SUPABASE_URL', getenv('SUPABASE_URL')); 
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY')); 

function supabaseConnect() {
    $url = SUPABASE_URL . '/rest/v1/';
    $headers = [
        'apikey' => SUPABASE_ANON_KEY,
        'Authorization' => 'Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type' => 'application/json',
    ];

    return [
        'url' => $url,
        'headers' => $headers
    ];
}

?>
