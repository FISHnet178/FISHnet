<?php
session_start();
require 'config.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$username = trim($_POST['usuario'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    die("Usuario y contraseña son obligatorios. <a href='login.html'>Volver</a>");
}

$stmt = $pdo->prepare("
    SELECT HabID, Usuario, Contrasena, aprobado, NombreH, ApellidoH, CI
    FROM Habitante
    WHERE Usuario = ?
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$_SESSION['HABID'] = $user['HabID'];
$_SESSION['usuario'] = $user['Usuario'];
$_SESSION['nombreH'] = $user['NombreH'];

if (!$user) {
    die("Credenciales inválidas. <a href='login.html'>Volver</a>");
}

$_SESSION['admin'] = ($user['HabID'] == 1);

if ((int)$user['aprobado'] === 0) {
    die("Usuario no aprobado. <a href='Index.html'>Inicio</a>");
}

if (!password_verify($password, $user['Contrasena'])) {
    die("Credenciales inválidas. <a href='login.html'>Volver</a>");
}



$_SESSION['perfil_actualizado'] = 
    !empty($user['NombreH']) && 
    !empty($user['ApellidoH']) && 
    !empty($user['CI']);

if ($_SESSION['perfil_actualizado']) {
    header("Location: Inicio.php");
    } else {
    header("Location: guardar_datos.php");
}


exit;
