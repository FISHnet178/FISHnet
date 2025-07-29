<?php
session_start();

$usuario = trim($_POST['usuario']);
$password = trim($_POST['password']);

$archivo = "usuarios/$usuario.txt";

if (file_exists($archivo)) {
    $datos = file($archivo);
    $hashGuardado = trim($datos[1]);

    if (password_verify($password, $hashGuardado)) {
        $_SESSION['usuario'] = $usuario;
        header("Location: dashboard.html");
        exit();
    } else {
        echo "Contraseña incorrecta. <a href='index.html'>Volver</a>";
    }
} else {
    echo "El usuario no existe. <a href='index.html'>Volver</a>";
}
?>
