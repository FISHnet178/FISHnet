<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['HABID'])) {
    echo "No has iniciado sesión.";
    echo "<a href='login.html'>Iniciar sesión</a>";
    exit;
}

$habID = intval($_SESSION['HABID']);

// Consultar jornadas realizadas
$sqlJornadas = "
    SELECT r.JorID, j.Tipo, j.FechaInicio, j.FechaFin
    FROM Realizan r
    INNER JOIN Jornadas j ON r.JorID = j.JorID
    WHERE r.HabID = ?
    ORDER BY r.JorID DESC
";
$stmtJornadas = $pdo->prepare($sqlJornadas);
$stmtJornadas->execute([$habID]);
$resultJornadas = $stmtJornadas->fetchAll(PDO::FETCH_ASSOC);

// Consultar comprobantes de pago
$sqlPagos = "
    SELECT ep.PagoID, pc.Comprobante, pc.AprobadoP, pc.fecha_aprobacionP
    FROM Efectua_pago ep
    INNER JOIN PagoCuota pc ON ep.PagoID = pc.PagoID
    WHERE ep.HabID = ?
    ORDER BY ep.PagoID DESC
";
$stmtPagos = $pdo->prepare($sqlPagos);
$stmtPagos->execute([$habID]);
$resultPagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Historial del Habitante</title>
    <link rel="stylesheet" href="estilos/estilo.css">
</head>
<body>
<div class="contenedor">
    <div class="decoracion"></div>
    <div class="dashboard-content">
        <div class="panel-columns">
            <div class="panel-column">
                <h2>Jornadas Realizadas</h2>
                <ul>
                    <?php foreach ($resultJornadas as $row): ?>
                    <li>
                        <strong>ID Jornada:</strong> <?= htmlspecialchars($row['JorID']) ?>
                        <strong>Tipo:</strong> <?= htmlspecialchars($row['Tipo']) ?>
                        <strong>Fecha Inicio:</strong> <?= htmlspecialchars($row['FechaInicio']) ?>
                        <strong>Fecha Fin:</strong> <?= htmlspecialchars($row['FechaFin']) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="panel-column">
                <h2>Comprobantes de Pago</h2>
                <ul>
                    <?php foreach ($resultPagos as $row): ?>
                    <li>
                        <strong>ID Pago:</strong> <?= htmlspecialchars($row['PagoID']) ?>
                        <strong>Comprobante:</strong>
                        <?php if (!empty($row['Comprobante'])): ?>
                            <a href="ver_comprobante.php?id=<?= urlencode($row['PagoID']) ?>" target="_blank">
                                <button>Ver Comprobante</button>
                            </a>
                        <?php else: ?>
                            No disponible
                        <?php endif; ?>
                        <strong>Aprobado:</strong> <?= $row['AprobadoP'] ? 'Sí' : 'No' ?>
                        <strong>Fecha Aprobación:</strong> <?= htmlspecialchars($row['fecha_aprobacionP']) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="action-buttons">
            <form action="inicio.php" method="get">
                <button type="submit">Volver al Inicio</button>
            </form>
        </div>
    </div>
    <div class="decoracion"></div>
</div>
</body>
</html>
