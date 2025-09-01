<?php
require 'config.php';
session_start();

class Comprobantes
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function subirComprobante($habID, $archivoTmp, $nombreOriginal, $tipoMime)
    {

        if (!file_exists($archivoTmp)) {
            throw new Exception("No se encontró el archivo subido.");
        }

        $tiposPermitidos = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($tipoMime, $tiposPermitidos)) {
            throw new Exception("Tipo de archivo no permitido.");
        }

        $contenido = file_get_contents($archivoTmp);

        $stmt = $this->pdo->prepare("
            INSERT INTO PagoCuota (Comprobante, AprobadoP)
            VALUES (:comprobante, NULL)
        ");
        $stmt->bindParam(':comprobante', $contenido, PDO::PARAM_LOB);
        $stmt->execute();

        $pagoID = $this->pdo->lastInsertId();

        $stmt2 = $this->pdo->prepare("
            INSERT INTO Efectua_pago (HabID, PagoID)
            VALUES (:hab_id, :pago_id)
        ");
        $stmt2->bindParam(':hab_id', $habID, PDO::PARAM_INT);
        $stmt2->bindParam(':pago_id', $pagoID, PDO::PARAM_INT);
        $stmt2->execute();

        return $pagoID;
    }
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['comprobante'])) {
    try {
        if (!isset($_SESSION['HABID'])) {
            throw new Exception("No se encontró el ID del habitante en la sesión. Inicia sesión nuevamente.");
        }

        $habID = $_SESSION['HABID'];

        $comprobantes = new Comprobantes($pdo);
        $idComprobante = $comprobantes->subirComprobante(
            $habID,
            $_FILES['comprobante']['tmp_name'],
            $_FILES['comprobante']['name'],
            $_FILES['comprobante']['type']
        );

        $mensaje = "Comprobante subido con éxito, espera a ser aprobado por un administrador. ID: " . $idComprobante;
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Comprobante</title>
    <link rel="stylesheet" href="estilos/dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="contenedor">
    <div class="dashboard-content">
        <div class="center-block">
            <h2>Subir Comprobante</h2>

            <?php if (!empty($mensaje)): ?>
                <p><?php echo htmlspecialchars($mensaje); ?></p>
            <?php endif; ?>

            <form id="datos-form" action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="hab_id"
                       value="<?php echo isset($_SESSION['HABID']) ? $_SESSION['HABID'] : ''; ?>">

                <label>
                    Selecciona tu comprobante:
                    <input type="file" name="comprobante" accept="image/*,application/pdf" required>
                </label>

                <button type="submit">Subir Comprobante</button>
            </form>

            <p><a class="volver-btn" href="inicio.php">← Volver al inicio</a></p>
        </div>
    </div>
    <div class="decoracion"></div>
</div>
</body>
</html>
