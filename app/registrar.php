<?php
require 'config.php';
session_start();

function set_flash($msg, $type = 'info') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return "<div class='flash {$f['type']}'>{$f['msg']}</div>";
    }
    return '';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST['Usuario'] ?? '');
    $contrasena = trim($_POST['Contraseña'] ?? '');

    if ($usuario === '' || $contrasena === '') {
        set_flash("Por favor complete todos los campos.", "error");
        header("Location: registrar.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Habitante WHERE Usuario = ?");
    $stmt->execute([$usuario]);
    if ($stmt->fetchColumn() > 0) {
        set_flash("El nombre de usuario ya está registrado. Intente con otro.", "error");
        header("Location: registrar.php");
        exit;
    }

    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    $stmt = $pdo->query("SELECT COUNT(*) FROM Habitante WHERE admin = 1");
    $tieneAdmin = $stmt->fetchColumn() > 0;

    $esAdmin = $tieneAdmin ? 0 : 1;
    $aprobado = $esAdmin ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO Habitante (Usuario, Contrasena, admin, aprobado)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$usuario, $hash, $esAdmin, $aprobado]);

    $habID = $pdo->lastInsertId();
    $_SESSION['HABID'] = $habID;
    $_SESSION['Usuario'] = $usuario;
    $_SESSION['admin'] = $esAdmin;

    if ($esAdmin) {
        set_flash("Eres el primer usuario y administrador del sistema.", "success");
        header("Location: guardar_datos.php");
    } else {
        set_flash("Completa la postulación y espera a ser aprobado.", "success");
        header("Location: postulacion.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="estilos/dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="contenedor">
        <div class="datos-form">
            <h2>Registro</h2>

            <?= get_flash() ?>

            <form action="registrar.php" method="POST">
                <input type="text" name="Usuario" placeholder="Usuario" required>
                <input type="password" name="Contraseña" placeholder="Contraseña" required>
                <button type="submit">Registrarse</button>
            </form>
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
                <div class="action-buttons">
                    <p><button class="inicio" onclick="window.location.href='index.html'">← Volver al inicio</button></p>
                </div>
        </div>
        <div class="decoracion"></div>
    </div>
</body>
</html>
