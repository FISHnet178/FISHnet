<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/flash_set.php';

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    set_flash('No tienes permisos para eliminar publicaciones.', 'error');
    header('Location: inicio.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inicio.php');
    exit;
}

$foroId = isset($_POST['foro_id']) ? (int) $_POST['foro_id'] : 0;
if ($foroId <= 0) {
    set_flash('Identificador de publicación inválido.', 'error');
    header('Location: inicio.php');
    exit;
}

try {
    $check = $pdo->prepare('SELECT titulo FROM Foro WHERE ForoID = :id');
    $check->execute([':id' => $foroId]);
    $row = $check->fetch();
    if (!$row) {
        set_flash('Publicación no encontrada.', 'error');
        header('Location: inicio.php');
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM Foro WHERE ForoID = :id');
    $stmt->execute([':id' => $foroId]);

    set_flash('Publicación eliminada correctamente.', 'success');
    header('Location: inicio.php');
    exit;
} catch (PDOException $e) {
    set_flash('Error al eliminar la publicación.', 'error');
    header('Location: inicio.php');
    exit;
}
