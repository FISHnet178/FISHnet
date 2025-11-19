<?php
require 'config.php';
require 'flash_set.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['foro_id'])) {
    set_flash('Acción no permitida.', 'error');
    header('Location: inicio.php');
    exit;
}

$foroId = (int)$_POST['foro_id'];

function eliminarPostRecursivo($pdo, $foroId) {
    $stmt = $pdo->prepare('SELECT ForoID FROM Foro WHERE ParentID = :id');
    $stmt->execute([':id' => $foroId]);
    $hijos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($hijos as $hijoId) {
        eliminarPostRecursivo($pdo, $hijoId);
    }

    $del = $pdo->prepare('DELETE FROM Foro WHERE ForoID = :id');
    $del->execute([':id' => $foroId]);
}

try {
    $check = $pdo->prepare('SELECT ForoID FROM Foro WHERE ForoID = :id');
    $check->execute([':id' => $foroId]);
    if (!$check->fetch()) {
        set_flash('Publicación no encontrada.', 'error');
        header('Location: inicio.php');
        exit;
    }

    eliminarPostRecursivo($pdo, $foroId);

    set_flash('Publicación eliminada.', 'success');
    header('Location: inicio.php');
    exit;

} catch (PDOException $e) {
    error_log("[eliminar_post.php] Error: " . $e->getMessage());
    set_flash('Error al eliminar la publicación.', 'error');
    header('Location: inicio.php');
    exit;
}
?>
