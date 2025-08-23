<?php
session_start();
require 'config.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$username = trim($_POST['usuario']   ?? '');
$password = trim($_POST['password']  ?? '');

if ($username === '' || $password === '') {
    die(
        "Usuario y contraseña son obligatorios. "
        ."<a href='login.html'>Volver</a> "
    );
}

$stmt = $pdo->prepare("
    SELECT HabID, Contraseña, aprobado
      FROM Habitante
     WHERE Usuario = ?
");
$stmt->execute([$username]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {

    if ((int)$user['aprobado'] === 0) {
        echo "Usuario no aprobado. "
        ."<a href='Index.html'>Inicio</a> ";
        exit;
    }

    if (password_verify($password, $user['Contraseña'])) {
        $_SESSION['user_id'] = $user['HabID'];
        $_SESSION['usuario'] = $username;
        header("Location: dashboard.html");
        exit;
    } 
 
    echo "Credenciales inválidas. "
       ."<a href='login.html'>Volver</a> ";
    exit;
}

echo "Credenciales inválidas. "
   ."<a href='login.html'>Volver</a> ";
