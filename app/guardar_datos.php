<?php
require 'config.php';
require 'flash_set.php';

if (empty($_SESSION['HABID'])) {
    header('Location: login.php');
    exit;
}

$HabID = $_SESSION['HABID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $NombreH   = trim($_POST['NombreH']   ?? '');
    $ApellidoH = trim($_POST['ApellidoH'] ?? '');
    $CI        = trim($_POST['CI']        ?? '');

    if (!preg_match('/^\d{8}$/', $CI)) {
        set_flash('La cédula debe contener exactamente 8 dígitos.', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $fotoBinaria = null;
    if (!empty($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $tipoArchivo = mime_content_type($_FILES['foto']['tmp_name']);
        $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png'];

        if (!in_array($tipoArchivo, $tiposPermitidos)) {
            set_flash('Solo se admiten archivos JPEG, JPG y PNG.', 'error');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $fotoBinaria = file_get_contents($_FILES['foto']['tmp_name']);
    }

    try {
        if ($fotoBinaria !== null) {
            $stmt = $pdo->prepare(
                'UPDATE Habitante 
                  SET NombreH = ?, 
                      ApellidoH = ?, 
                      CI = ?, 
                      foto_perfil = ?
                  WHERE HabID = ?'
            );
            $stmt->bindParam(1, $NombreH);
            $stmt->bindParam(2, $ApellidoH);
            $stmt->bindParam(3, $CI);
            $stmt->bindParam(4, $fotoBinaria, PDO::PARAM_LOB);
            $stmt->bindParam(5, $HabID, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE Habitante 
                  SET NombreH = ?, 
                      ApellidoH = ?, 
                      CI = ?
                  WHERE HabID = ?'
            );
            $stmt->execute([$NombreH, $ApellidoH, $CI, $HabID]);
        }

        $_SESSION['nombreH'] = $NombreH;

        set_flash('Datos guardados correctamente.', 'success');
    } catch (Exception $e) {
        error_log('Error actualizando Habitante ID ' . $HabID . ': ' . $e->getMessage());
        set_flash('No se pudieron guardar los datos.', 'error');
    }

    header('Location: inicio.php');
    exit;
}

$stmt = $pdo->prepare('SELECT NombreH, ApellidoH, CI, foto_perfil FROM Habitante WHERE HabID = ?');
$stmt->execute([$HabID]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    die("Usuario no encontrado");
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Datos Personales</title>
  <link rel="stylesheet" href="estilos/dashboard.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
  <div class="contenedor">
    <div class="datos-form">
      <h2>Datos Personales</h2>

      <form id="datos-form" action="" method="POST" enctype="multipart/form-data">
        <label>
          <input type="text" name="NombreH" placeholder="Nombre"
                 value="<?php echo htmlspecialchars($datos['NombreH'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                 required>
        </label>

        <label>
          <input type="text" name="ApellidoH" placeholder="Apellido"
                 value="<?php echo htmlspecialchars($datos['ApellidoH'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                 required>
        </label>

        <label>
          <input type="number" name="CI" id="cedula" placeholder="Cédula de Identidad"
                 value="<?php echo htmlspecialchars($datos['CI'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                 required>
        </label>

        <label>
          Foto de perfil:
          <input type="file" name="foto" accept="image/*">
        </label>

        <?php if (!empty($datos['foto_perfil'] ?? null)): ?>
          <p>Foto actual:</p>
          <img src="mostrar_foto.php?id=<?php echo $HabID; ?>" 
               alt="Foto de perfil" 
               style="max-width:150px;">
        <?php endif; ?>

        <button type="submit">Guardar Datos</button>
      </form>

      <?php if (!empty($datos['NombreH'])): ?>
        <div class="action-buttons" style="margin-top:12px;">
        <button onclick="window.location.href='inicio.php'">← Volver al inicio</button>
      </div>
      <?php endif; ?>
    </div>
    <div class="decoracion"></div>
  </div>
</body>
</html>
