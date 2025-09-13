<?php
require 'config.php';

$Usuario    = trim($_POST['Usuario']    ?? '');
$Contrasena = trim($_POST['Contraseña'] ?? '');

if ($Usuario === '' || $Contrasena === '') {
    die('Completa usuario y contraseña. <a href="registro.html">Volver</a>');
}

$stmt = $pdo->prepare('SELECT 1 FROM Habitante WHERE Usuario = ?');
$stmt->execute([$Usuario]);
if ($stmt->fetch()) {
    die('Usuario ya existe. <a href="registro.html">Volver</a>');
}

$hash = password_hash($Contrasena, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO Habitante (Usuario, Contrasena) VALUES (?, ?)');
$stmt->execute([$Usuario, $hash]);

$stmt = $pdo->prepare('UPDATE Habitante SET aprobado = 1 WHERE HABID = 1');
$stmt->execute();
$stmt = $pdo->prepare('SELECT aprobado FROM Habitante WHERE Usuario = ?');
$stmt->execute([$Usuario]);
$row = $stmt->fetch();
if ($row && $row['aprobado'] == 1) {
    echo '<p>Tu cuenta ya está aprobada. Puedes iniciar sesión.</p>';
    echo '<p><a href="index.html">Volver</a></p>';
    exit;
}else {
    header ('Location: postulacion.html');
    exit;
}