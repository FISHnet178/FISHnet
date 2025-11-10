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

<?php get_flash(); ?>

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
                <p><button class="inicio" onclick="window.location.href='Inicio.php'">← Volver al inicio</button></p>
            </div>
        </div>
        <div class="decoracion"></div>
    </div>

</body>
</html>
