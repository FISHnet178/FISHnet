<?php
session_start();

if (empty($_SESSION['nombreH'])) {
    header('Location: login.html');
    exit;
}

$nombreH = $_SESSION['nombreH'];
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
            <?php echo htmlspecialchars($nombreH); ?>
            <button class="menu-btn" onclick="toggleMenu()">
                <img src="mostrar_foto.php" alt="Foto de perfil" style="width:60px; height:60px; object-fit:cover; border-radius:50%;">
            </button>
            <div class="menu" id="menu">
                <a href="guardar_datos.php">Cambiar datos</a>
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                    <a href="admin.php">Panel de administración</a>
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
                <a href="pagina4.html" class="btn">Botón 4</a>
                <a href="pagina5.html" class="btn">Botón 5</a>
                <a href="pagina6.html" class="btn">Botón 6</a>
                <a href="pagina7.html" class="btn">Botón 7</a>
                <a href="pagina8.html" class="btn">Botón 8</a>
                <a href="pagina9.html" class="btn">Botón 9</a>
            </div>
        </section>

        <section class="actividades">
            <h2>Foro Cooperativa</h2>
            <div class="actividad">Actividad reciente 1</div>
            <div class="actividad">Actividad reciente 2</div>
        </section>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('menu');
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        }
    </script>
</body>
</html>
