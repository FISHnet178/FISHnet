<?php
session_start();

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET' || $method === 'POST') {
    $rawId = $_REQUEST['postulacion_user_id'] ?? null;
    if ($rawId === null || !ctype_digit($rawId)) {
        die("ID de postulación inválido.");
    }
    $posID = (int) $rawId;
} else {
    die("Método no permitido: $method.");
}

require 'config.php';

$stmt = $pdo->prepare("
    SELECT HabID,
           nombre,
           telefono,
           fecha_nacimiento,
           habitante_uruguay,
           motivo,
           comprobante_ingreso,
           cantidad_ingresan
      FROM Postulaciones
     WHERE PosID = :posid
");
$stmt->execute([':posid' => $posID]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("No se encontró la postulación solicitada.");
}

$stmtInt = $pdo->prepare("
    SELECT nombre,
           apellido,
           edad,
           ci
      FROM Integrantes
     WHERE PosID = :posid
");
$stmtInt->execute([':posid' => $posID]);
$integrantes = $stmtInt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Postulación #<?= htmlspecialchars($posID) ?></title>
    <link rel="stylesheet" href="estilos/estilo.css">
</head>
<body>
    <div class="contenedor">
        <div class="decoracion"></div>
        <div class="dashboard-content">
            <h1>Postulación #<?= htmlspecialchars($posID) ?></h1>

            <p><strong>Nombre:</strong> <?= htmlspecialchars($post['nombre']) ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($post['telefono']) ?></p>
            <p><strong>Fecha de nacimiento:</strong> <?= htmlspecialchars($post['fecha_nacimiento']) ?></p>
            <p><strong>Habitante de Uruguay:</strong> <?= htmlspecialchars($post['habitante_uruguay']) ?></p>
            <p><strong>Motivo:</strong><br><?= nl2br(htmlspecialchars($post['motivo'])) ?></p>
            <p><strong>Total ingresan:</strong> <?= intval($post['cantidad_ingresan']) ?></p>

            <?php if ($post['comprobante_ingreso'] !== null): ?>
                <h2>Comprobante de ingreso</h2>
                <img
                    src="data:image/jpeg;base64,<?= base64_encode($post['comprobante_ingreso']) ?>"
                    alt="Comprobante de ingreso"
                >
            <?php endif; ?>

            <?php if (count($integrantes) > 0): ?>
                <h2>Integrantes asociados</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Edad</th>
                            <th>CI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($integrantes as $int): ?>
                            <tr>
                                <th><?= htmlspecialchars($int['nombre']) ?></th>
                                <th><?= htmlspecialchars($int['apellido']) ?></th>
                                <th><?= intval($int['edad']) ?></th>
                                <th><?= htmlspecialchars($int['ci']) ?></th>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <div class="action-buttons" style="margin-top: 15px;">
            <form action="admin.php" method="post">
                <button type="submit">Ir a inicio</button>
            </form>
        </div>
        </div>
    <div class="decoracion"></div>
</div>
</body>
</html>