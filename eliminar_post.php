<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>
        alert('Acceso no válido.');
        window.location.href = 'inicio.php';
    </script>";
    exit;
}

$foroId = isset($_POST['foro_id']) ? (int) $_POST['foro_id'] : 0;
if ($foroId <= 0) {
    echo "<script>
        alert('Identificador de publicación inválido.');
        window.location.href = 'inicio.php';
    </script>";
    exit;
}

try {
    $check = $pdo->prepare('SELECT titulo FROM Foro WHERE ForoID = :id');
    $check->execute([':id' => $foroId]);
    $row = $check->fetch();

    if (!$row) {
        echo "<script>
            alert('Publicación no encontrada.');
            window.location.href = 'inicio.php';
        </script>";
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM Foro WHERE ForoID = :id');
    $stmt->execute([':id' => $foroId]);

    echo "<script>
        alert('Publicación eliminada correctamente.');
        window.location.href = 'inicio.php';
    </script>";
    exit;

} catch (PDOException $e) {
    echo "<script>
        alert('Error al eliminar la publicación.');
        window.location.href = 'inicio.php';
    </script>";
    exit;
}
?>
