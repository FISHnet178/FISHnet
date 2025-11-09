<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/flash_set.php';

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err !== null) {
        if (!headers_sent()) {
            set_flash('Ocurrió un error inesperado. Intenta nuevamente', 'error');
            error_log('[foro.php][FATAL] ' . $err['message'] . ' in ' . $err['file'] . ' on line ' . $err['line']);
            header('Location: inicio.php');
            exit;
        }
    }
});

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_SESSION['HABID'])) {
            header('Location: login.php');
            exit;
        }

        $HabID    = $_SESSION['HABID'];
        $parentId = isset($_POST['ParentID']) && $_POST['ParentID'] !== '' ? (int)$_POST['ParentID'] : 0;
        $cuerpo   = trim($_POST['Cuerpo'] ?? '');
        $titulo   = trim($_POST['Titulo'] ?? '');

        if ($cuerpo === '') {
            set_flash('El cuerpo no puede estar vacío', 'error');
            header('Location: foro.php' . ($parentId ? '?parent_id=' . $parentId : ''));
            exit;
        }

        if ($parentId > 0) {
            $stmt = $pdo->prepare('INSERT INTO Foro (titulo, asunto, HabID, ParentID) VALUES (?, ?, ?, ?)');
            $stmt->execute(['', $cuerpo, $HabID, $parentId]);
            set_flash('Respuesta publicada correctamente', 'success');
        } else {
            if ($titulo === '') {
                set_flash('El título es obligatorio para una nueva publicación', 'error');
                header('Location: foro.php');
                exit;
            }
            $stmt = $pdo->prepare('INSERT INTO Foro (titulo, asunto, HabID, ParentID) VALUES (?, ?, ?, ?)');
            $stmt->execute([$titulo, $cuerpo, $HabID, null]);
            set_flash('Publicación creada correctamente', 'success');
        }

        header('Location: inicio.php');
        exit;

    } catch (Throwable $e) {
        error_log('[foro.php][EXCEPTION] ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        if (!headers_sent()) {
            set_flash('No se pudo procesar la solicitud. Intenta nuevamente', 'error');
            header('Location: inicio.php');
        } else {
            echo '<script>sessionStorage.setItem("flash","error|No se pudo procesar la solicitud. Intenta nuevamente");window.location.href="inicio.php";</script>';
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Foro</title>
    <link rel="stylesheet" href="estilos/dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<?php get_flash();?>

<div class="contenedor">
    <div class="dashboard-content">
        <div class="center-block">
            <h2 id="form-title">Foro</h2>
            <form id="datos-form" action="foro.php" method="POST">
                <label id="tituloWrap">Titulo:
                    <input type="text" name="Titulo" id="Titulo">
                </label>
                <label>Cuerpo:
                    <textarea name="Cuerpo" id="Cuerpo" rows="6" required></textarea>
                </label>
                <input type="hidden" name="ParentID" id="ParentID" value="">
                <button type="submit">Enviar</button>
            </form>
        </div>
        <div class="action-buttons">
            <p><button class="inicio" onclick="window.location.href='Inicio.php'">← Volver al inicio</button></p>
        </div>
    </div>
    <div class="decoracion"></div>
</div>

<script>
(function () {
    const params = new URLSearchParams(window.location.search);
    const parent = params.get('parent_id');
    if (parent) {
        const tituloWrap = document.getElementById('tituloWrap');
        if (tituloWrap) tituloWrap.style.display = 'none';
        document.getElementById('ParentID').value = parent;
        document.getElementById('form-title').textContent = 'Responder publicación';
    }
})();
</script>
</body>
</html>
