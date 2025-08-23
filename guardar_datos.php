<?php
require 'config.php';
session_start();

if (empty($_SESSION['habid'])) {
    header('Location: dashboard.html');
    exit;
}
$HABID = $_SESSION['habid'];

$NombreH   = trim($_POST['NombreH']   ?? '');
$ApellidoH = trim($_POST['ApellidoH'] ?? '');
$CI        = trim($_POST['CI']        ?? '');

if ($NombreH === '' || $ApellidoH === '' || $CI === '') {
    die('Completa todos los campos. <a href="dashboard.html">Volver</a>');
}

$stmt = $pdo->prepare(
  'UPDATE Habitante 
      SET NombreH   = ?, 
          ApellidoH = ?, 
          CI        = ?
    WHERE HABID = ?'
);
$stmt->execute([$NombreH, $ApellidoH, $CI, $HABID]);

echo 'Perfil actualizado. <a href="dashboard.html">Ir al inicio</a>';

