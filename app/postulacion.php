<?php
require 'config.php';
require 'flash_set.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $habID = $_SESSION['HABID'] ?? null;

    if (!$habID) {
        set_flash("Debes estar registrado para postularte.", "error");
        header("Location: index.html");
        exit;
    }

    $nombre = trim($_POST["nombre"] ?? '');
    $telefono = trim($_POST["telefono"] ?? '');
    $fecha_nacimiento = $_POST["fecha_nacimiento"] ?? '';
    $habitante_uruguay = $_POST["habitante_uruguay"] ?? '';
    $motivo = trim($_POST["motivo"] ?? '');
    $cantidad = max(1, intval($_POST["cantidad_ingresan"] ?? 1));

    if ($habitante_uruguay !== "si") {
        set_flash("Solo pueden postularse habitantes permanentes de Uruguay.", "error");
        header("Location: postulacion.php");
        exit;
    }

    if (!isset($_FILES["comprobante_ingreso"]) || $_FILES["comprobante_ingreso"]["error"] !== UPLOAD_ERR_OK) {
        set_flash("Error al subir el comprobante de ingreso.", "error");
        header("Location: postulacion.php");
        exit;
    }

    if ($_FILES["comprobante_ingreso"]["size"] > 10 * 1024 * 1024) {
        set_flash("El comprobante de ingreso no puede superar los 10 MB.", "error");
        header("Location: postulacion.php");
        exit;
    }

    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    $mime = mime_content_type($_FILES["comprobante_ingreso"]["tmp_name"]);
    if (!in_array($mime, $allowed_types)) {
        set_flash("El comprobante de ingreso debe ser PDF, JPG o PNG.", "error");
        header("Location: postulacion.php");
        exit;
    }

    $comprobante = file_get_contents($_FILES["comprobante_ingreso"]["tmp_name"]);

    try {
        $fecha_nacimiento_dt = new DateTime($fecha_nacimiento);
        $hoy = new DateTime();
        $edad = $fecha_nacimiento_dt->diff($hoy)->y;
    } catch (Exception $e) {
        set_flash("Fecha de nacimiento inválida. Error: " . $e->getMessage(), "error"); // Muestra el error directamente
        header("Location: postulacion.php");
        exit;
    }

    if ($edad < 18) {
        set_flash("Debes ser mayor de 18 años para postularte.", "error");
        header("Location: postulacion.php");
        exit;
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
        
        if ($cantidad > 1) {
            for ($i = 1; $i < $cantidad; $i++) {
                $nombre_i = trim($_POST["nombre_integrante_$i"] ?? '');
                $apellido_i = trim($_POST["apellido_integrante_$i"] ?? '');
                $edad_i = intval($_POST["edad_integrante_$i"] ?? 0);
                $ci_i = trim($_POST["ci_integrante_$i"] ?? '');

                if ($nombre_i && $apellido_i && $edad_i > 0 && $ci_i) {
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
            }
        }

        $pdo->commit();

        set_flash("Postulación enviada correctamente.", "success");
        header("Location: index.html");
        exit;

    } catch (Exception $e) {
        set_flash("Error en la postulación: " . $e->getMessage(), "error");
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

      <?php
$flash = get_flash();

if ($flash):
    $colors = [
        'success' => '#4CAF50',
        'error'   => '#f44336',
        'info'    => '#2196F3',
        'warning' => '#ff9800',
    ];

    $color = $colors[$flash['type']] ?? '#2196F3';
    $msg   = htmlspecialchars($flash['msg']);

    echo '<div class="flash-message" style="
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background:' . $color . ';
        color:#fff;
        padding:12px 20px;
        border-radius:6px;
        box-shadow:0 3px 8px rgba(0,0,0,0.2);
        font-size:15px;
        z-index:9999;
        animation: fadeInOut 4s ease forwards;
    ">' . $msg . '</div>

    <style>
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
        10% { opacity: 1; transform: translateY(0) translateX(-50%); }
        80% { opacity: 1; }
        100% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
    }
    </style>';
endif;
?>

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
