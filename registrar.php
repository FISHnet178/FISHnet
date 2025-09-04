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

echo '<p>¡Registro exitoso!</p>';
echo '<p>Espera a ser aprobado.</p>';
echo '<p><a href="index.html">Volver</a></p>';
exit;
