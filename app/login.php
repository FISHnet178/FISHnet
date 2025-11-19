<?php
require 'config.php';
require 'flash_set.php';

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
        set_flash('Credenciales inválidas.', 'error');
        header('Location: login.php');
        exit;
    }

    if ((int)$user['aprobado'] === 0) {
        set_flash('Tu cuenta aún no ha sido aprobada. Por favor, espera la aprobación.', 'warning');
        header('Location: login.php');
        exit;
    }

    if (!password_verify($password, $user['Contrasena'])) {
        set_flash('Credenciales inválidas.', 'error');
        header('Location: login.php');
        exit;
    }

    $_SESSION['perfil_actualizado'] = 
        !empty($user['NombreH']) && 
        !empty($user['ApellidoH']) && 
        !empty($user['CI']);

    if ($_SESSION['perfil_actualizado']) {
        header("Location: inicio.php");
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
    <link rel="stylesheet" href="estilos/dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="contenedor">
        <div class="datos-form">
            <h2>Iniciar sesión</h2>
            <form action="login.php" method="POST">
                <input type="text" name="usuario" placeholder="Usuario" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit">Iniciar sesión</button>
            </form>
            <p>¿No tienes cuenta? <a href="registrar.php">Regístrate</a></p>

            <div class="action-buttons">
                <p><button class="inicio" onclick="window.location.href='index.html'">← Volver al inicio</button></p>
            </div>

            <?php
            $flash = get_flash();
            if ($flash): ?>
                <div class="flash-message" style="
                    position: fixed;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: <?= htmlspecialchars($flash['type'] == 'error' ? '#f44336' : ($flash['type'] == 'warning' ? '#ff9800' : '#2196F3')) ?>;
                    color: #fff;
                    padding: 12px 20px;
                    border-radius: 6px;
                    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
                    font-size: 15px;
                    z-index: 9999;
                    animation: fadeInOut 4s ease forwards;
                "><?= htmlspecialchars($flash['msg']); ?></div>

                <style>
                @keyframes fadeInOut {
                    0% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
                    10% { opacity: 1; transform: translateY(0) translateX(-50%); }
                    80% { opacity: 1; }
                    100% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
                }
                </style>
            <?php endif; ?>

        </div>
        <div class="decoracion"></div>
    </div>
</body>
</html>
