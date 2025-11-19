<?php
session_start();
$usuario = $_SESSION['usuario'] ?? null;

if ($usuario) {
    $archivo = "usuarios/$usuario.txt";
    if (file_exists($archivo)) {
        unlink($archivo);
        session_destroy();
        echo "Usuario eliminado correctamente. <a href='index.php'>Volver al inicio</a>";
    } else {
        echo "No se encontró el archivo del usuario.";
    }
} else {
    echo "No has iniciado sesión. <a href='index.php'>Iniciar sesión</a>";
}
?>
