<?php
require 'config.php';
session_start();

function set_flash($msg, $type = 'info') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return "<div class='flash {$f['type']}'>{$f['msg']}</div>";
    }
    return '';
}
function js_alert_and_redirect(string $message, string $location) {
    $msg = json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $loc = json_encode($location, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo "<script>alert($msg); window.location.href = $loc;</script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $habID = $_SESSION['HABID'] ?? null;

    if (!$habID) {
        set_flash("Debes estar registrado para postularte.", "error");
        header("Location: index.html");
        exit;
    }

    $nombre = $_POST["nombre"] ?? '';
    $telefono = $_POST["telefono"] ?? '';
    $fecha_nacimiento = $_POST["fecha_nacimiento"] ?? '';
    $habitante_uruguay = $_POST["habitante_uruguay"] ?? '';
    $motivo = $_POST["motivo"] ?? '';
    $cantidad = intval($_POST["cantidad_ingresan"] ?? 1);

    if ($habitante_uruguay !== "si") {
        set_flash("Solo pueden postularse habitantes permanentes de Uruguay.", "error");
        header("Location: postulacion.php");
        exit;
    }

    $comprobante = null;
    if (isset($_FILES["comprobante_ingreso"]) && $_FILES["comprobante_ingreso"]["error"] === UPLOAD_ERR_OK) {
        $comprobante = file_get_contents($_FILES["comprobante_ingreso"]["tmp_name"]);
    }

    $edades = [];
    for ($i = 1; $i < $cantidad; $i++) {
        $edad = intval($_POST["edad_integrante_$i"] ?? 0);
        $edades[] = $edad;
    }
    if (count($edades) > 1) {
        $max = max($edades);
        $min = min($edades);
        if (($max - $min) > 6) {
            set_flash("La diferencia de edad entre hijos no puede superar los 6 años.", "error");
            header("Location: postulacion.php");
            exit;
        }
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO Postulaciones 
            (HabID, nombre, telefono, fecha_nacimiento, habitante_uruguay, motivo, comprobante_ingreso, cantidad_ingresan) 
            VALUES (:HabID, :nombre, :telefono, :fecha_nacimiento, :habitante_uruguay, :motivo, :comprobante, :cantidad)");

        $stmt->bindParam(":HabID", $habID, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":telefono", $telefono);
        $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);
        $stmt->bindParam(":habitante_uruguay", $habitante_uruguay);
        $stmt->bindParam(":motivo", $motivo);
        $stmt->bindParam(":comprobante", $comprobante, PDO::PARAM_LOB);
        $stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
        $stmt->execute();

        $PosID = $pdo->lastInsertId();

        for ($i = 1; $i < $cantidad; $i++) {
            $nombre_i = $_POST["nombre_integrante_$i"] ?? '';
            $apellido_i = $_POST["apellido_integrante_$i"] ?? '';
            $edad_i = intval($_POST["edad_integrante_$i"] ?? 0);
            $ci_i = $_POST["ci_integrante_$i"] ?? '';

            $stmt_i = $pdo->prepare("INSERT INTO Integrantes 
                (PosID, nombre, apellido, edad, ci) 
                VALUES (:PosID, :nombre, :apellido, :edad, :ci)");

            $stmt_i->bindParam(":PosID", $PosID, PDO::PARAM_INT);
            $stmt_i->bindParam(":nombre", $nombre_i);
            $stmt_i->bindParam(":apellido", $apellido_i);
            $stmt_i->bindParam(":edad", $edad_i, PDO::PARAM_INT);
            $stmt_i->bindParam(":ci", $ci_i);
            $stmt_i->execute();
        }

        $pdo->commit();

        
        header("Location: index.html");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error en postulacion.php: " . $e->getMessage());
        set_flash("Error en la postulación. Intenta de nuevo.", "error");
        header("Location: postulacion.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Postulación a Cooperativa</title>
  <link rel="stylesheet" href="estilos/postulacion.css">
  <style>
    .flash { padding: 10px; margin-bottom: 15px; border-radius: 5px; font-weight: bold; text-align: center; }
    .flash.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .flash.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  </style>
  <script>
    function mostrarIntegrantes() {
      const cantidad = parseInt(document.getElementById("cantidad_ingresan").value);
      const contenedor = document.getElementById("integrantes_extra");
      contenedor.innerHTML = "";

      if (cantidad > 1) {
        for (let i = 1; i < cantidad; i++) {
          contenedor.innerHTML += `
            <fieldset>
              <legend>Integrante ${i}</legend>
              <label>Nombre:</label><input type="text" name="nombre_integrante_${i}" required><br>
              <label>Apellido:</label><input type="text" name="apellido_integrante_${i}" required><br>
              <label>Edad:</label><input type="number" name="edad_integrante_${i}" required><br>
              <label>Cédula:</label><input type="text" name="ci_integrante_${i}" required><br>
            </fieldset><br>
          `;
        }
      }
    }
  </script>
</head>
<body>
  <div class="contenedor">
    <div class="form">
      <h2>Formulario de Postulación</h2>

      <?= get_flash() ?>

      <form action="postulacion.php" method="POST" enctype="multipart/form-data">
        <label>Nombre completo:</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>Teléfono:</label><br>
        <input type="text" name="telefono"><br><br>

        <label>Fecha de nacimiento:</label><br>
        <input type="date" name="fecha_nacimiento" required><br><br>

        <label>¿Es habitante permanente de la República Oriental del Uruguay?</label><br>
        <select name="habitante_uruguay" required>
          <option value="">Seleccione</option>
          <option value="si">Sí</option>
          <option value="no">No</option>
        </select><br><br>

        <label>Motivo de postulación:</label><br>
        <textarea name="motivo" rows="4" cols="50" required></textarea><br><br>

        <label>Comprobante de ingreso familiar (PDF/JPG/PNG):</label><br>
        <input type="file" name="comprobante_ingreso" accept=".pdf,.jpg,.jpeg,.png" required><br><br>

        <label>Cantidad de personas que ingresarán:</label><br>
        <input type="number" name="cantidad_ingresan" id="cantidad_ingresan" min="1" required onchange="mostrarIntegrantes()"><br><br>

        <div id="integrantes_extra"></div>

        <button type="submit">Enviar postulación</button>
      </form>
    </div>
    <div class="decoracion"></div>
  </div>
</body>
</html>
