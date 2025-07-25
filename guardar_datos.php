<?php
session_start();
$usuario = $_SESSION['usuario'] ?? null;

if (!$usuario) {
    die("No has iniciado sesión. <a href='index.html'>Iniciar sesión</a>");
}

$nombre = trim($_POST['nombre']);
$fecha = trim($_POST['fecha']);
$cedula = trim($_POST['cedula']);

$archivo = "usuarios/$usuario.txt";

$datos = file($archivo);
$passwordHash = trim($datos[1]);

$contenido = "$usuario\n$passwordHash\n$nombre\n$fecha\n$cedula\n";
file_put_contents($archivo, $contenido);

echo "Datos guardados correctamente. <a href='dashboard.html'>Volver</a>";
?>
