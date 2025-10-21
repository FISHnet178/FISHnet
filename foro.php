<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err !== null) {
        if (!headers_sent()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Ocurrió un error inesperado. Intenta nuevamente'];
            error_log('[foro.php][FATAL] ' . $err['message'] . ' in ' . $err['file'] . ' on line ' . $err['line']);
            header('Location: inicio.php');
            exit;
        }
    }
});

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    if (empty($_SESSION['HABID'])) {
        header('Location: login.html');
        exit;
    }

    $HabID = $_SESSION['HABID'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: inicio.php');
        exit;
    }

    $parentId = isset($_POST['ParentID']) && $_POST['ParentID'] !== '' ? (int)$_POST['ParentID'] : 0;
    $cuerpo   = trim($_POST['Cuerpo']   ?? '');
    $titulo   = trim($_POST['Titulo']   ?? '');

    if ($cuerpo === '') {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'El cuerpo no puede estar vacío'];
        header('Location: foro.html' . ($parentId ? '?parent_id=' . $parentId : ''));
        exit;
    }

    if ($parentId > 0) {
        $stmt = $pdo->prepare('INSERT INTO Foro (titulo, asunto, HabID, ParentID) VALUES (?, ?, ?, ?)');
        $stmt->execute([null, $cuerpo, $HabID, $parentId]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Respuesta publicada correctamente'];
    } else {
        if ($titulo === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'El título es obligatorio para una nueva publicación'];
            header('Location: foro.html');
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO Foro (titulo, asunto, HabID, ParentID) VALUES (?, ?, ?, ?)');
        $stmt->execute([$titulo, $cuerpo, $HabID, null]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Publicación creada correctamente'];
    }

    header('Location: inicio.php');
    exit;

} catch (Throwable $e) {
    error_log('[foro.php][EXCEPTION] ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    if (!headers_sent()) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'No se pudo procesar la solicitud. Intenta nuevamente'];
        header('Location: inicio.php');
    } else {
        echo '<script>sessionStorage.setItem("flash","error|No se pudo procesar la solicitud. Intenta nuevamente");window.location.href="inicio.php";</script>';
    }
    exit;
}
