<?php
session_start();
require_once __DIR__ . '/conexion.php'; 

$err = $_GET['e'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = strtolower(trim($_POST['u'] ?? ''));
    $p = $_POST['p'] ?? '';

    if ($u !== '' && $p !== '') {
        $sql = "SELECT id, usuario, contrasena, id_rol, estado
                FROM usuarios
                WHERE usuario = ? LIMIT 1";
        $stm = $mysqli->prepare($sql);
        if ($stm) {
            $stm->bind_param('s', $u);
            $stm->execute();
            $res = $stm->get_result();

            if ($res && $res->num_rows === 1) {
                $row = $res->fetch_assoc();

                if ((int)$row['estado'] === 1 && $p === $row['contrasena']) {
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id'      => (int)$row['id'],
                        'usuario' => $row['usuario'],
                        'id_rol'  => (int)$row['id_rol']
                    ];
                    header('Location: index.php');
                    exit;
                }
            }
        }
    }
    header('Location: login.php?e=1');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Acceder</title>
  <style>
    body{font-family:system-ui;background:#0f3245;margin:0}
    .card{max-width:420px;margin:70px auto;background:#0b3a4b;color:#fff;padding:24px;border-radius:12px}
    label{display:block;margin:12px 0 6px;font-weight:700}
    input{width:100%;padding:10px;border:none;border-radius:6px}
    .btn{width:100%;margin-top:16px;padding:12px;border:0;border-radius:8px;background:#f6a615;color:#fff;font-weight:800;cursor:pointer}
    .msg{margin-top:10px;background:#ffdada;color:#5a0000;padding:10px;border-radius:6px}
  </style>
</head>
<body>
  <div class="card">
    <h2>Login</h2>
    <?php if($err==='1'): ?>
      <div class="msg">Usuario/contraseña inválidos o cuenta inactiva.</div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <label>Usuario</label>
      <input type="text" name="u" required>
      <label>Contraseña</label>
      <input type="password" name="p" required>
      <button class="btn" type="submit">Iniciar sesión</button>
    </form>
  </div>
</body>
</html>
