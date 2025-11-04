<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['HABID'])) {
        header('Location: login.php');
        exit;
    }

    $HabID    = $_SESSION['HABID'];
    $Tipo     = trim($_POST['Tipo'] ?? '');
    $Horas    = trim($_POST['Horas'] ?? '');
    $FechaIni = trim($_POST['FechaInicio'] ?? '');
    $FechaFin = trim($_POST['FechaFin'] ?? '');

    if ($Tipo === '' || $Horas === '' || $FechaIni === '' || $FechaFin === '') {
        die('Completa todos los campos. <a href="JornadaT.php">Volver</a>');
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

        header('Location: Inicio.php');
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            die('Esta jornada ya está asociada a este habitante. <a href="JornadaT.php">Volver</a>');
        } else {
            throw $e;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Jornada</title>
    <link rel="stylesheet" href="estilos/JornadaT.css">
</head>
<body>
    <div class="contenedor">
        <div class="dashboard-content">
            <div class="center-block">
                <h2>Registrar Jornada</h2>
                <form id="datos-form" action="JornadaT.php" method="POST">
                    <label>Tipo:
                        <input type="text" name="Tipo" maxlength="30" required>
                    </label>
                    <label>Horas:<br>
                        <input type="number" name="Horas" min="1" required>
                    </label>
                    <label>Fecha de Inicio:<br>
                        <input type="date" name="FechaInicio" required>
                    </label>
                    <label>Fecha de Fin:<br>
                        <input type="date" name="FechaFin">
                    </label>
                    <button type="submit">Registrar</button>
                </form>
            </div>
            <br>
            
            <div class="action-buttons">
                <p><button class="inicio" onclick="window.location.href='Inicio.php'">← Volver al inicio</button></p>
            </div>

        </div>
        <div class="decoracion"></div>
    </div>
</body>
</html>
