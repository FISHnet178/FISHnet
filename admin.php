<?php
session_start();

if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'])) {
        $stmt = $pdo->prepare("
            UPDATE Habitante
               SET aprobado         = 1,
                   fecha_aprobacion = NOW()
             WHERE HabID = ? 
               AND aprobado = 0
        ");
        $stmt->execute([$_POST['user_id']]);

    } elseif (isset($_POST['postulacion_user_id'])) {
    $posID = intval($_POST['postulacion_user_id']);
    header('Location: mostrar_postulacion.php?postulacion_user_id=' . $posID);
    exit;

    } elseif (isset($_POST['delete_user_id'])) {
        $stmt = $pdo->prepare("
            DELETE FROM Habitante 
             WHERE HabID = ?
        ");
        $stmt->execute([$_POST['delete_user_id']]);
    }

    if (isset($_POST['comprobante_id'])) {
        $stmt = $pdo->prepare("
            UPDATE PagoCuota
               SET AprobadoP         = 1,
                   fecha_aprobacionP = NOW()
             WHERE PagoID = ? 
               AND AprobadoP IS NULL
        ");
        $stmt->execute([$_POST['comprobante_id']]);

    } elseif (isset($_POST['delete_comprobante_id'])) {
        $stmt = $pdo->prepare("
            DELETE FROM Efectua_pago 
             WHERE PagoID = ?
        ");
        $stmt->execute([$_POST['delete_comprobante_id']]);

        $stmt = $pdo->prepare("
            DELETE FROM PagoCuota 
             WHERE PagoID = ?
        ");
        $stmt->execute([$_POST['delete_comprobante_id']]);
    }
}

$pendientes = $pdo
    ->query("
        SELECT 
            h.HabID,
            h.Usuario,
            h.fecha_creacion,
            p.PosID
        FROM Habitante AS h
        LEFT JOIN Postulaciones AS p
          ON p.HabID = h.HabID
        WHERE h.aprobado = 0
        ORDER BY h.fecha_creacion ASC
    ")
    ->fetchAll();

$pendientesComprobantes = $pdo
    ->query("
        SELECT PagoID
          FROM PagoCuota
         WHERE AprobadoP IS NULL
      ORDER BY PagoID ASC
    ")
    ->fetchAll();
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Aprobaciones</title>
    <link rel="stylesheet" href="estilos/estilo.css">
</head>
<body>
<div class="contenedor">
    <div class="decoracion"></div>
    <div class="dashboard-content">
        <div class="panel-columns">
            <div class="panel-column">
                <h2>Usuarios Pendientes de Aprobación</h2>
                <ul>
                    <?php foreach ($pendientes as $u): ?>
                        <li>
                            <strong>ID <?= $u['HabID'] ?></strong>
                            <?= htmlspecialchars($u['Usuario']) ?> 
                            (Registrado el <?= $u['fecha_creacion'] ?>)

                            <div class="action-buttons">
                                <form method="post">
                                    <input type="hidden" name="user_id" value="<?= $u['HabID'] ?>">
                                    <button type="submit">Aprobar</button>
                                </form>
                                <?php if (!empty($u['PosID'])): ?>
                                    <form method="post">
                                        <input 
                                            type="hidden" 
                                            name="postulacion_user_id" 
                                            value="<?= $u['PosID'] ?>"
                                        >
                                        <button type="submit">
                                            Ver postulación #<?= $u['PosID'] ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button disabled>No tiene postulación</button>
                                <?php endif; ?>
                                <form method="post" onsubmit="return confirm('¿Eliminar al usuario <?= htmlspecialchars($u['Usuario']) ?>?');">
                                    <input type="hidden" name="delete_user_id" value="<?= $u['HabID'] ?>">
                                    <button type="submit">Eliminar</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>

                </ul>
            </div>

            <div class="panel-column">
                <h2>Comprobantes Pendientes de Aprobación</h2>
                <ul>
                    <?php foreach ($pendientesComprobantes as $c): ?>
                    <li>
                        <div class="action-buttons">
                            <strong>Comprobante ID <?= $c['PagoID'] ?></strong>

                            <a href="ver_comprobante.php?id=<?= $c['PagoID'] ?>" target="_blank">
                                <button type="button">Ver</button>
                            </a>

                            <form method="post">
                                <input type="hidden" name="comprobante_id" value="<?= $c['PagoID'] ?>">
                                <button type="submit">Aprobar</button>
                            </form>

                            <form method="post" onsubmit="return confirm('¿Eliminar este comprobante?');">
                                <input type="hidden" name="delete_comprobante_id" value="<?= $c['PagoID'] ?>">
                                <button type="submit">Eliminar</button>
                            </form>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="action-buttons" style="margin-top: 20px;">
            <form action="index.html" method="get">
                <button type="button" onclick="location.href='index.html'">Cerrar sesión</button>
            </form>
            <form action="Inicio.php" method="post">
                <button type="submit">Ir a inicio</button>
            </form>

        </div>
    </div>
    <div class="decoracion"></div>
</div>
</body>
</html>
