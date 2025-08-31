<?php
require 'config.php';
session_start();

if (empty($_SESSION['HABID'])) {
    die('No se encontró el habitante asociado a la sesión. <a href="login.html">Iniciar sesión</a>');
}

$HabID    = $_SESSION['HABID'];
$Tipo     = trim($_POST['Tipo'] ?? '');
$Horas    = trim($_POST['Horas'] ?? '');
$FechaIni = trim($_POST['FechaInicio'] ?? '');
$FechaFin = trim($_POST['FechaFin'] ?? '');

if ($Tipo === '' || $Horas === '' || $FechaIni === '' || $FechaFin === '') {
    die('Completa todos los campos. <a href="JornadaT.html">Volver</a>');
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
        die('Esta jornada ya está asociada a este habitante. <a href="JornadaT.html">Volver</a>');
    } else {
        throw $e;
    }
}
