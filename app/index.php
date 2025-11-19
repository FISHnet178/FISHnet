<?php
require 'flash_set';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cooperativa</title>
  <link rel="stylesheet" href="estilos/index.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
  <?php
  $flash = get_flash();
  if ($flash): ?>
      <div class="flash-message" style="
          position: fixed;
          top: 20px;
          left: 50%;
          transform: translateX(-50%);
          background: <?= htmlspecialchars($flash['type'] == 'error' ? '#f44336' : ($flash['type'] == 'warning' ? '#ff9800' : '#2196F3')) ?>;
          color: #fff;
          padding: 12px 20px;
          border-radius: 6px;
          box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
          font-size: 15px;
          z-index: 9999;
          animation: fadeInOut 4s ease forwards;
      "><?= htmlspecialchars($flash['msg']); ?></div>

      <style>
      @keyframes fadeInOut {
          0% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
          10% { opacity: 1; transform: translateY(0) translateX(-50%); }
          80% { opacity: 1; }
          100% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
      }
      </style>
  <?php endif; ?>
  <header>
    <div class="info">
      <div class="logo"><img src="estilos/Logo.png"></div>
      <div class="nombre">El Rincón del Mundo</div>
    </div>

    <button class="menu-btn" onclick="toggleMenu()">☰</button> 
    <div class="menu" id="menu">
      <a href="login.php">Iniciar Sesión</a>
      <a href="registrar.php">Regístrate</a>
    </div>
  </header>

  <main>
    <div class="recuadro1"><img src="estilos/cooperativa.jpeg"></div>
    <div class="recuadro2">
      <h1 id="bienvenida-title">Bienvenidos a El Rincón del Mundo</h1>
      <p>Somos una cooperativa de ayuda mutua dedicada a fomentar la solidaridad, el cuidado y el desarrollo conjunto. Aquí trabajamos mano a mano para compartir recursos, conocimientos y oportunidades, poniendo a las personas y la comunidad en el centro.</p>
      <p>Nuestros espacios ofrecen apoyo, talleres, intercambios y actividades participativas donde todas las voces son valoradas. Creemos que juntos podemos construir un entorno más justo, sostenible y humano.</p>
      <p><strong>Participa:</strong> únete a nuestras actividades, aporta tu experiencia o contáctanos para colaborar.</p>
    </div>
  </main>

  <footer>
    <p>Contáctanos: ElRincóndelMundo@contacto.com | +598-xxx-xxxx | ¿Quieres saber que hacemos con tu información? <a href="info.php">click aquí</a></p>
  </footer>

  <script>
    function toggleMenu() {
      const menu = document.getElementById('menu');
      menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }
  </script>
</body>
</html>
