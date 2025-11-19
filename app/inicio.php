<?php
require 'config.php';
require 'flash_set.php';
if (!isset($_SESSION['HABID'])) {
    header('Location: login.php');
    exit;
}

$nombreH = $_SESSION['nombreH'];
$currentHabId = $_SESSION['HABID'];

if (!isset($_SESSION['isAdmin'])) {
    $stmt = $pdo->prepare("SELECT admin FROM Habitante WHERE HabID = ?");
    $stmt->execute([$currentHabId]);
    $_SESSION['isAdmin'] = (bool) $stmt->fetchColumn();
}
$isAdmin = $_SESSION['isAdmin'];

$posts = [];
$respuestasByPost = [];
$error_loading_posts = false;
$error_message = '';

try {
    $sqlPosts = "
      SELECT
        f.ForoID, f.titulo, f.asunto, f.HabId, f.ParentID,
        h.Usuario,
        CONCAT(COALESCE(h.NombreH,''), ' ', COALESCE(h.ApellidoH,'')) AS nombre_completo
      FROM Foro f
      LEFT JOIN Habitante h ON f.HabId = h.HABID
      ORDER BY f.ForoID ASC
    ";
    $stmt = $pdo->query($sqlPosts);
    $posts = $stmt->fetchAll();

    foreach ($posts as $r) {
        $parent = isset($r['ParentID']) ? (int)$r['ParentID'] : 0;
        $respuestasByPost[$parent][] = $r;
    }
} catch (PDOException $e) {
    $error_loading_posts = true;
    $error_message = $e->getMessage();
    error_log('[inicio.php] Error cargando posts: ' . $error_message);
}

function renderPost($post, $respuestasByPost, $isAdmin, $level = 0) {
    $foroId = (int)($post['ForoID'] ?? 0);
    $titulo = htmlspecialchars($post['titulo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $asunto = nl2br(htmlspecialchars($post['asunto'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $usuario = htmlspecialchars($post['Usuario'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $nombre_completo = trim($post['nombre_completo'] ?? '');
    $autor = $nombre_completo !== '' ? htmlspecialchars($nombre_completo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : ($usuario !== '' ? $usuario : 'Desconocido');

    $isAuthor = isset($_SESSION['HABID']) && (int)$_SESSION['HABID'] === (int)($post['HabId'] ?? 0);

    $margin = $level * 30;
    echo '<div class="post" role="article" data-foro-id="' . $foroId . '" style="margin-left:' . $margin . 'px;margin-top:10px;padding:6px;border:1px solid #ddd;border-radius:6px;background:#fff;">';
    
    if ($titulo !== '') echo '<div class="titulo"><strong>' . $titulo . '</strong></div>';
    echo '<div class="meta" style="font-size:0.9em;color:#555;">Publicado por: ' . $autor . '</div>';
    echo '<div class="cuerpo" style="margin-top:4px;">' . $asunto . '</div>';

    echo '<div class="acciones" style="margin-top:6px;">';
    echo '<button type="button" class="btn-responder-inline" data-foro="' . $foroId . '" style="padding:4px 8px;margin-right:6px;">Responder</button>';
    if ($isAdmin || $isAuthor) {
        echo '<form method="post" action="eliminar_post.php" style="display:inline-block;" onsubmit="return confirm(\'¿Eliminar publicación?\');">';
        echo '<input type="hidden" name="foro_id" value="' . $foroId . '">';
        echo '<button type="submit" style="background:#d9534f;color:#fff;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;">Eliminar</button>';
        echo '</form>';
    }
    echo '</div>';

    echo '<div class="reply-inline" id="reply-inline-' . $foroId . '" style="display:none; margin-top:6px;">';
    echo '<form method="post" action="foro.php" onsubmit="return validateInlineReply(this);">';
    echo '<input type="hidden" name="ParentID" value="' . $foroId . '">';
    echo '<textarea name="Cuerpo" rows="3" required style="width:100%;margin-top:4px;"></textarea>';
    echo '<div style="margin-top:4px;">';
    echo '<button type="submit" style="background:#0073e6;color:#fff;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;">Enviar respuesta</button>';
    echo '<button type="button" class="cancel-reply" data-foro="' . $foroId . '" style="margin-left:6px;padding:4px 8px;border-radius:4px;">Cancelar</button>';
    echo '</div></form></div>';

    if (!empty($respuestasByPost[$foroId])) {
        foreach ($respuestasByPost[$foroId] as $r) {
            renderPost($r, $respuestasByPost, $isAdmin, $level + 1);
        }
    }

    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Principal</title>
    <link rel="stylesheet" href="estilos/inicio.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header>
        <div class="info">
            <div class="logo"><img src="estilos/Logo.png"></div>
            <div class="nombre">El Rincón del Mundo</div>
        </div>  
        <div class="perfil">
            <div class="nombre-user">
                <?php echo htmlspecialchars($nombreH, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            </div>
            <button class="menu-btn" onclick="toggleMenu()" aria-expanded="false" aria-controls="menu" style="border:none;background:transparent;padding:0;">
                <img src="mostrar_foto.php" alt="Foto de perfil" style="width:60px; height:60px; object-fit:cover; border-radius:50%;">
            </button>
            <div class="menu" id="menu" style="display:none;">
                <a href="guardar_datos.php">Cambiar datos</a><br>
                <?php if ($isAdmin): ?>
                    <a href="admin.php">Panel de administración</a><br>
                <?php endif; ?>
                <a href="logout.php">Cerrar sesión</a>
            </div>
        </div>
    </header>

    <main>
        <section class="botones">
            <h1>Accesos Rápidos</h1>
            <div class="grid-botones">
                <a href="JornadaT.php" class="btn">Registrar horas de trabajo</a>
                <a href="comprobantes.php" class="btn">Subir comprobantes de pago</a>
                <a href="historial.php" class="btn">Historial de acciones</a>
                <a href="foro.php" class="btn">Crear publicación</a>
                <a href="reservar_salon.php" class="btn">Reserva de salones comunes</a>
                <a href="logout.php" class="btn">Cerrar sesión</a>
            </div>
        </section>

        <section class="actividades">
            <h1>Foro Cooperativa</h1>
            <div class="foro-list" aria-live="polite">
                <?php
                if ($error_loading_posts) {
                    echo '<div class="sin-posts">Error al cargar publicaciones.</div>';
                } elseif (empty($posts)) {
                    echo '<div class="sin-posts">No hay publicaciones aún.</div>';
                } else {
                    foreach ($posts as $post) {
                        if (empty($post['ParentID']) || $post['ParentID'] == 0) {
                            renderPost($post, $respuestasByPost, $isAdmin);
                        }
                    }
                }
                ?>
            </div>
        </section>
    </main>

    <footer>
        <p>Contáctanos: ElRincóndelMundo@contacto.com | +598-xxx-xxxx | ¿Quieres saber que hacemos con tu información? <a href="info.php">click aquí</a></p>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('menu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        document.addEventListener('click', function(e){
            if (e.target.matches('.btn-responder-inline')) {
                const id = e.target.getAttribute('data-foro');
                const el = document.getElementById('reply-inline-' + id);
                if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
            }
            if (e.target.matches('.cancel-reply')) {
                const id = e.target.getAttribute('data-foro');
                const el = document.getElementById('reply-inline-' + id);
                if (el) el.style.display = 'none';
            }
        });

        function validateInlineReply(form) {
            const txt = form.querySelector('textarea[name="Cuerpo"]');
            if (!txt || !txt.value.trim()) {
                alert('Escribe una respuesta antes de enviar.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
