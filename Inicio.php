<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['nombreH'])) {
    header('Location: login.html');
    exit;
}
$nombreH = $_SESSION['nombreH'];

require_once __DIR__ . '/config.php'; // define $pdo

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
      WHERE f.ParentID IS NULL OR f.ParentID = 0
      ORDER BY f.ForoID DESC
    ";
    $stmt = $pdo->query($sqlPosts);
    $posts = $stmt->fetchAll();

    if (!empty($posts)) {
        $foroIds = array_column($posts, 'ForoID');
        $placeholders = implode(',', array_fill(0, count($foroIds), '?'));
        $sqlRes = "
          SELECT r.ForoID, r.ParentID, r.HabID, r.asunto, r.ForoID AS RespuestaID,
                 CONCAT(COALESCE(h.NombreH,''),' ',COALESCE(h.ApellidoH,'')) AS nombre_completo, h.Usuario
          FROM Foro r
          LEFT JOIN Habitante h ON r.HABID = h.HABID
          WHERE r.ParentID IN ($placeholders)
          ORDER BY r.ForoID ASC
        ";
        $stmtR = $pdo->prepare($sqlRes);
        $stmtR->execute($foroIds);
        $allRes = $stmtR->fetchAll();

        foreach ($allRes as $r) {
            $parent = isset($r['ParentID']) ? (int)$r['ParentID'] : 0;
            $respuestasByPost[$parent][] = $r;
        }
    }
} catch (PDOException $e) {
    $error_loading_posts = true;
    $error_message = $e->getMessage();
    error_log('[inicio.php] Error cargando posts: ' . $error_message);
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
        <div class="logo">LOGO</div>

        <div class="perfil">
            <?php echo htmlspecialchars($nombreH, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            <button class="menu-btn" onclick="toggleMenu()" aria-expanded="false" aria-controls="menu" style="border:none;background:transparent;padding:0;">
                <img src="mostrar_foto.php" alt="Foto de perfil" style="width:60px; height:60px; object-fit:cover; border-radius:50%;">
            </button>
            <div class="menu" id="menu" style="display:none;">
                <a href="guardar_datos.php">Cambiar datos</a><br>
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                    <a href="admin.php">Panel de administración</a><br>
                <?php endif; ?>
                <a href="logout.php">Cerrar sesión</a>
            </div>
        </div>
    </header>

    <main>
        <section class="botones">
            <div class="grid-botones">
                <a href="JornadaT.html" class="btn">Registrar horas de trabajo</a>
                <a href="comprobantes.php" class="btn">Subir comprobantes de pago</a>
                <a href="historial.php" class="btn">Historial de acciones</a>
                <a href="foro.html" class="btn">Crear publicación</a>
                <a href="pagina5.html" class="btn">Botón 5</a>
                <a href="pagina6.html" class="btn">Botón 6</a>
                <a href="pagina7.html" class="btn">Botón 7</a>
                <a href="pagina8.html" class="btn">Botón 8</a>
                <a href="pagina9.html" class="btn">Botón 9</a>
            </div>
        </section>

        <section class="actividades">
            <h2>Foro Cooperativa</h2>
            <div class="foro-list" aria-live="polite">
                <?php if (!empty($error_loading_posts)): ?>
                    <div class="sin-posts">Error al cargar publicaciones.</div>
                <?php elseif (empty($posts)): ?>
                    <div class="sin-posts">No hay publicaciones aún.</div>
                <?php else: ?>
                    <?php if (!empty($_SESSION['admin']) && $_SESSION['admin'] && !empty($foroId)): ?>
                        <form method="post" action="delete_post.php" onsubmit="return confirm('¿Eliminar publicación?');">
                            <input type="hidden" name="foro_id" value="<?php echo $foroId; ?>">
                            <button type="submit">Eliminar</button>
                        </form>
                    <?php endif; ?>

                    <?php foreach ($posts as $row): ?>
                        <?php
                            $foroId = (int)($row['ForoID'] ?? 0);
                            $titulo = htmlspecialchars($row['titulo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $asunto = nl2br(htmlspecialchars($row['asunto'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                            $usuario = htmlspecialchars($row['Usuario'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $nombre_completo = trim($row['nombre_completo'] ?? '');
                            $autor = $nombre_completo !== '' ? htmlspecialchars($nombre_completo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : ($usuario !== '' ? $usuario : 'Desconocido');
                        ?>
                        <div class="post" role="article" data-foro-id="<?php echo $foroId; ?>">
                            <?php if ($titulo !== ''): ?><div class="titulo"><?php echo $titulo; ?></div><?php endif; ?>
                            <div class="meta">Publicado por: <?php echo $autor; ?></div>
                            <div class="cuerpo"><?php echo $asunto; ?></div>

                            <div class="acciones" style="margin-top:8px;">
                                <button type="button" class="btn-responder-inline" data-foro="<?php echo $foroId; ?>">Responder</button>
                                <?php
                                $isAdmin = !empty($_SESSION['admin']) && $_SESSION['admin'];
                                $isAuthor = isset($_SESSION['HABID']) && (int)$_SESSION['HABID'] === (int)($row['HabId'] ?? 0);
                                if ($isAdmin || $isAuthor):
                                ?>
                                <form method="post" action="delete_post.php" style="display:inline-block;margin-left:10px;" onsubmit="return confirm('¿Eliminar publicación?');">
                                    <input type="hidden" name="foro_id" value="<?php echo $foroId; ?>">
                                    <button type="submit" style="background:#d9534f;color:#fff;border:none;padding:6px 10px;border-radius:4px;cursor:pointer;">Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </div>


                            <div class="reply-inline" id="reply-inline-<?php echo $foroId; ?>" style="display:none; margin-top:10px;">
                                <form method="post" action="foro.php" onsubmit="return validateInlineReply(this);">
                                    <input type="hidden" name="ParentID" value="<?php echo $foroId; ?>">
                                    <textarea name="Cuerpo" rows="4" required style="width:100%;"></textarea>
                                    <div style="margin-top:6px;">
                                        <button type="submit" style="background:#0073e6;color:#fff;border:none;padding:6px 10px;border-radius:4px;cursor:pointer;">Enviar respuesta</button>
                                        <button type="button" class="cancel-reply" data-foro="<?php echo $foroId; ?>" style="margin-left:8px;padding:6px 10px;border-radius:4px;">Cancelar</button>
                                    </div>
                                </form>
                            </div>

                            <?php if (!empty($respuestasByPost[$foroId])): ?>
                                <div class="respuestas" style="margin-top:10px;">
                                    <?php foreach ($respuestasByPost[$foroId] as $r): ?>
                                        <?php
                                            $rAutor = trim($r['nombre_completo']) !== '' ? htmlspecialchars($r['nombre_completo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : htmlspecialchars($r['Usuario'] ?? 'Desconocido', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                            $rContenido = nl2br(htmlspecialchars($r['asunto'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                                        ?>
                                        <div class="respuesta" style="border-left:3px solid #eee;padding:8px;margin-bottom:6px;background:#fafafa;">
                                            <div class="r-meta"><strong><?php echo $rAutor; ?></strong></div>
                                            <div class="r-contenido"><?php echo $rContenido; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('menu');
            const isOpen = menu.style.display === 'block';
            menu.style.display = isOpen ? 'none' : 'block';
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
