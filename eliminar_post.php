<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/flash_set.php';

if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    set_flash('No tienes permisos para eliminar publicaciones.', 'error');
    header('Location: inicio.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['foro_id'])) {
    header('Location: inicio.php');
    exit;
}

$foroId = (int)$_POST['foro_id'];

try {
    $check = $pdo->prepare('SELECT titulo FROM Foro WHERE ForoID = :id');
    $check->execute([':id' => $foroId]);
    $row = $check->fetch();

    if (!$row) {
        set_flash('Publicación no encontrada.', 'error');
        header('Location: inicio.php');
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM Foro WHERE ForoID = :id OR ParentID = :id');
    $stmt->execute([':id' => $foroId]);

    set_flash('Publicación eliminada correctamente.', 'success');
    header('Location: inicio.php');
    exit;

} catch (PDOException $e) {
    error_log("[eliminar_post.php] Error: " . $e->getMessage());
    set_flash('Error al eliminar la publicación.', 'error');
    header('Location: inicio.php');
    exit;
}
