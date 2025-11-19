<?php
require 'config.php';
require 'flash_set.php';

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

            <?php
$flash = get_flash();

if ($flash):
    $colors = [
        'success' => '#4CAF50',
        'error'   => '#f44336',
        'info'    => '#2196F3',
        'warning' => '#ff9800',
    ];

    $color = $colors[$flash['type']] ?? '#2196F3';
    $msg   = htmlspecialchars($flash['msg']);

    echo '<div class="flash-message" style="
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background:' . $color . ';
        color:#fff;
        padding:12px 20px;
        border-radius:6px;
        box-shadow:0 3px 8px rgba(0,0,0,0.2);
        font-size:15px;
        z-index:9999;
        animation: fadeInOut 4s ease forwards;
    ">' . $msg . '</div>

    <style>
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
        10% { opacity: 1; transform: translateY(0) translateX(-50%); }
        80% { opacity: 1; }
        100% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
    }
    </style>';
endif;
?>

            <form action="registrar.php" method="POST">
                <input type="text" name="Usuario" placeholder="Usuario" required>
                <input type="password" name="Contraseña" placeholder="Contraseña" required>
                <button type="submit">Registrarse</button>
            </form>
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
                <div class="action-buttons">
                    <p><button class="inicio" onclick="window.location.href='index.php'">← Volver al inicio</button></p>
                </div>
        </div>
        <div class="decoracion"></div>
    </div>
</body>
</html>
