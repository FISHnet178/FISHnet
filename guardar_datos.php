<?php
require 'config.php';
session_start();

if (empty($_SESSION['HABID'])) {
    header('Location: login.html');
    exit;
}

$HabID = $_SESSION['HABID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $NombreH   = trim($_POST['NombreH']   ?? '');
    $ApellidoH = trim($_POST['ApellidoH'] ?? '');
    $CI        = trim($_POST['CI']        ?? '');

    $fotoBinaria = null;
    if (!empty($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
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

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Datos guardados correctamente'
        ];
    } catch (Exception $e) {
        error_log('Error actualizando Habitante ID ' . $HabID . ': ' . $e->getMessage());
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'No se pudieron guardar los datos'
        ];
    }

    header('Location: inicio.php');
    exit;
}

// GET: leer datos para mostrar en el formulario
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
  <!-- SweetAlert2 opcional
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  -->
</head>
<body>
  <div class="contenedor">
    <div class="dashboard-content">
      <div class="center-block">
        <h2>Datos Personales</h2>
        <form id="datos-form" action="" method="POST" enctype="multipart/form-data" onsubmit="return validarCedula()">
          <label>
            Primer nombre:
            <input type="text" name="NombreH" value="<?php echo htmlspecialchars($datos['NombreH'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
          </label>
          <label>
            Primer apellido:
            <input type="text" name="ApellidoH" value="<?php echo htmlspecialchars($datos['ApellidoH'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
          </label>
          <label>
            Cédula de identidad:
            <input type="number" name="CI" id="cedula" value="<?php echo htmlspecialchars($datos['CI'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
          </label>
          <label>
            Foto de perfil:
            <input type="file" name="foto" accept="image/*">
          </label>

          <?php if (!empty($datos['foto_perfil'] ?? null)): ?>
            <p>Foto actual:</p>
            <img src="mostrar_foto.php?id=<?php echo $HabID; ?>" alt="Foto de perfil" style="max-width:150px;">
          <?php endif; ?>

          <button type="submit">Guardar Datos</button>
        </form>
      </div>
    </div>
    <div class="decoracion"></div>
  </div>

  <script>
    function validarCedula() {
      const cedula = document.getElementById("cedula").value;
      if (!/^\d{8}$/.test(cedula)) {
        alert("La cédula debe contener exactamente 8 dígitos.");
        return false;
      }
      return true;
    }
  </script>
</body>
</html>
