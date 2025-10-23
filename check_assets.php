<?php
// Diagnostic page to check whether important static assets exist and are reachable
// Usage: open https://your-site.example/check_assets.php

header('Content-Type: text/html; charset=utf-8');
echo "<meta name=viewport content='width=device-width,initial-scale=1'>";
echo "<style>body{font-family:system-ui,Segoe UI,Arial; padding:20px;background:#f6f8f9;color:#111}h1{margin-top:0}pre{background:#fff;padding:12px;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,0.06);}</style>";

$files = [
    'css/estilos.css',
    'img/logo_city.png',
];

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $proto . '://' . $host . '/';

echo "<h1>Comprobación de assets</h1>";
echo "<p>Base pública detectada: <strong>" . htmlspecialchars($base) . "</strong></p>";

foreach ($files as $f) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $f);
    $exists = file_exists($path);
    $readable = is_readable($path) ? 'sí' : 'no';
    echo "<section style='margin-bottom:18px;padding:12px;background:#fff;border-radius:8px;'>";
    echo "<h3>$f</h3>";
    echo "<div>Existe en disco: <strong>" . ($exists ? 'sí' : 'no') . "</strong></div>";
    echo "<div>Legible: <strong>" . ($exists ? $readable : '-') . "</strong></div>";
    $url = $base . $f;
    echo "<div>URL pública sugerida: <a href='" . htmlspecialchars($url) . "' target='_blank'>" . htmlspecialchars($url) . "</a></div>";

    if ($exists) {
        $ext = pathinfo($f, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['css', 'txt', 'html', 'js'])) {
            $content = @file_get_contents($path);
            $preview = htmlspecialchars(mb_substr($content, 0, 400));
            echo "<details style='margin-top:8px'><summary>Previsualización (primeros 400 caracteres)</summary><pre>$preview</pre></details>";
        } elseif (in_array(strtolower($ext), ['png','jpg','jpeg','gif','svg'])) {
            // show the image (small) to verify correct file
            $imgUrl = htmlspecialchars($url);
            echo "<div style='margin-top:8px'>Previsualización de imagen:<br><img src='$imgUrl' alt='preview' style='max-width:220px;border-radius:6px;border:1px solid #eee;'></div>";
        }
    }

    echo "</section>";
}

echo "<h2>Siguientes pasos</h2>";
echo "<ul>";
echo "<li>Abre la pestaña Network en las herramientas del desarrollador (F12) y recarga la página principal (<code>/index.php</code>) para ver si <code>/css/estilos.css</code> o <code>/img/logo_city.png</code> devuelven 404.</li>";
echo "<li>Si los archivos no existen en disco en este servidor, confirma que las carpetas <code>css/</code> y <code>img/</code> están incluidas en el repositorio y en la imagen Docker. Revisa si hay un <code>.dockerignore</code> que los excluya.</li>";
echo "<li>Puedes probar localmente con: <pre>php -S localhost:8000</pre> y abrir <code>http://localhost:8000/check_assets.php</code> para comparar comportamiento.</li>";
echo "</ul>";

?>
