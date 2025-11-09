<?php
session_start();
require 'config.php';

function js_alert(string $message) {
    $msg = json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo "<script>alert($msg);</script>";
}

function js_alert_and_redirect(string $message, string $location) {
    $msg = json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $loc = json_encode($location, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo "<script>alert($msg); window.location.href = $loc;</script>";
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("
        SELECT HabID, Usuario, Contrasena, aprobado, NombreH, ApellidoH, CI
        FROM Habitante
        WHERE Usuario = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $_SESSION['HABID']   = $user['HabID']   ?? null;
    $_SESSION['usuario'] = $user['Usuario'] ?? null;
    $_SESSION['nombreH'] = $user['NombreH'] ?? null;

    if (!$user) {
        js_alert_and_redirect('Credenciales inválidas.', 'login.php');
    }

    if ((int)$user['aprobado'] === 0) {
        js_alert_and_redirect('Tu cuenta aún no ha sido aprobada. Por favor, espera la aprobación.', 'index.html');
    }

    if (!password_verify($password, $user['Contrasena'])) {
        js_alert_and_redirect('Credenciales inválidas.', 'login.php');
    }

    $_SESSION['perfil_actualizado'] = 
        !empty($user['NombreH']) && 
        !empty($user['ApellidoH']) && 
        !empty($user['CI']);

    if ($_SESSION['perfil_actualizado']) {
        header("Location: Inicio.php");
    } else {
        header("Location: guardar_datos.php");
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Cooperativa</title>
    <link rel="stylesheet" href="estilos/login.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="contenedor">
        <div class="login-form">
            <h2>INICIAR SESIÓN</h2>
            <form action="login.php" method="POST">
                <input type="text" name="usuario" placeholder="Usuario" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit">ENVIAR</button>
                <p>¿No tienes cuenta? <a href="registrar.php">Regístrate</a></p>
                <p><a href="index.html">Volver al inicio</a></p>
            </form>
        </div>
        <div class="decoracion"></div>
    </div>
</body>
</html>
