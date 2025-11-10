<?php
require 'config.php';
require 'flash_set.php';

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
            throw new Exception("Solo se admiten archivos JPEG, JPG, PNG y PDF.");
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

        set_flash("Comprobante subido con éxito, espera a ser aprobado por un administrador. ID: $idComprobante", 'success');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        set_flash("Error: " . $e->getMessage(), 'error');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
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

<?php get_flash();?>

<div class="contenedor">
    <div class="dashboard-content">
        <div class="center-block">
            <h2>Subir Comprobante</h2>

            <form id="datos-form" action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="hab_id"
                       value="<?php echo isset($_SESSION['HABID']) ? $_SESSION['HABID'] : ''; ?>">

                <label>
                    Selecciona tu comprobante:
                    <input type="file" name="comprobante" accept="image/*,application/pdf" required>
                </label>

                <button type="submit">Subir Comprobante</button>
            </form> 
        </div>
        <div class="action-buttons">
                <p><button class="inicio" onclick="window.location.href='inicio.php'">← Volver al inicio</button></p>
        </div>
    </div>
    <div class="decoracion"></div>
</div>
</body>
</html>
