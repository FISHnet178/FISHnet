<?php
require 'config.php';
require 'flash_set.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['HABID'])) {
        set_flash("Debes iniciar sesión primero.", 'error');
        header('Location: login.php');
        exit;
    }

    $HabID    = $_SESSION['HABID'];
    $Tipo     = trim($_POST['Tipo'] ?? '');
    $Horas    = trim($_POST['Horas'] ?? '');
    $FechaIni = trim($_POST['FechaInicio'] ?? '');
    $FechaFin = trim($_POST['FechaFin'] ?? '');

    if (strtotime($FechaIni) > strtotime($FechaFin)) {
        set_flash("La fecha de inicio tiene que ser anterior a la fecha de fin.", 'error');
        header('Location: JornadaT.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO Jornadas (Tipo, Horas, FechaInicio, FechaFin)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$Tipo, $Horas, $FechaIni, $FechaFin]);

        $JorID = $pdo->lastInsertId();
        $_SESSION['JorID'] = $JorID;

        $stmt = $pdo->prepare(
            'INSERT INTO Realizan (JorID, HABID)
             VALUES (?, ?)'
        );
        $stmt->execute([$JorID, $HabID]);

        set_flash("Jornada registrada con éxito.", 'success');
        header('Location: inicio.php');
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            set_flash("Esta jornada ya está asociada a este habitante.", 'error');
        } else {
            set_flash("Error: " . $e->getMessage(), 'error');
        }
        header('Location: JornadaT.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Jornada</title>
    <link rel="stylesheet" href="estilos/dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    
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

<div class="contenedor">
        <div class="datos-form">
            <h2>Registrar Jornada</h2>
            <form id="datos-form" action="JornadaT.php" method="POST">
                <input type="text" name="Tipo" maxlength="30" placeholder="Tipo" required>
                <input type="number" name="Horas" min="1" placeholder="Horas" required>
                Fecha de Inicio:
                    <input type="date" name="FechaInicio" required>
                Fecha de Fin:
                    <input type="date" name="FechaFin" required>
                <button type="submit">Registrar</button>
            </form>
            <div class="action-buttons">
                <p><button class="inicio" onclick="window.location.href='inicio.php'">← Volver al inicio</button></p>
            </div>
        </div>
        <div class="decoracion"></div>
    </div>

</body>
</html>
