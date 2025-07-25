<?php
$usuario = trim($_POST['usuario']);
$password = trim($_POST['password']);

if (!is_dir("usuarios")) {
    mkdir("usuarios", 0777, true);
}

$archivo = "usuarios/$usuario.txt";

if (file_exists($archivo)) {
    echo "El usuario ya existe. <a href='registro.html'>Volver</a>";
} else {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    file_put_contents($archivo, "$usuario\n$password_hash\n");
    header("Location: index.html");
    exit();
}
?>
