<?php
session_start();

if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['flash'])) $_SESSION['flash'] = null;
function set_flash($message, $type = 'info') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
function get_flash() {
    $f = $_SESSION['flash'];
    $_SESSION['flash'] = null;
    return $f;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $postedToken)) {
        set_flash('Token inválido.', 'error');
        header('Location: admin.php');
        exit;
    }

    try {
        if (isset($_POST['user_id'])) {
            $stmt = $pdo->prepare("
                UPDATE Habitante
                   SET aprobado = 1,
                       fecha_aprobacion = NOW()
                 WHERE HabID = ?
                   AND aprobado = 0
            ");
            $stmt->execute([ (int)$_POST['user_id'] ]);
            set_flash('Usuario aprobado.', 'success');

        } elseif (isset($_POST['postulacion_user_id'])) {
            $posID = intval($_POST['postulacion_user_id']);
            header('Location: mostrar_postulacion.php?postulacion_user_id=' . $posID . '&return=admin.php');
            exit;


        } elseif (isset($_POST['delete_user_id'])) {
            $deleteHabId = (int) $_POST['delete_user_id'];

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("DELETE FROM Efectua_pago WHERE HabID = ?");
                $stmt->execute([$deleteHabId]);

                $stmt = $pdo->prepare("DELETE FROM Habitante WHERE HabID = ?");
                $stmt->execute([$deleteHabId]);

                $pdo->commit();
                set_flash('Usuario y datos asociados eliminados.', 'success');
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('[admin_panel] Error eliminando usuario ' . $deleteHabId . ': ' . $e->getMessage());
                set_flash('Error al eliminar usuario. Revisa el log.', 'error');
            }

        } elseif (isset($_POST['comprobante_id'])) {
            $stmt = $pdo->prepare("
                UPDATE PagoCuota
                   SET AprobadoP = 1,
                       fecha_aprobacionP = NOW()
                 WHERE PagoID = ? 
                   AND AprobadoP IS NULL
            ");
            $stmt->execute([ (int)$_POST['comprobante_id'] ]);
            set_flash('Comprobante aprobado.', 'success');

        } elseif (isset($_POST['delete_comprobante_id'])) {
            $delId = (int) $_POST['delete_comprobante_id'];
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("DELETE FROM Efectua_pago WHERE PagoID = ?");
                $stmt->execute([$delId]);

                $stmt = $pdo->prepare("DELETE FROM PagoCuota WHERE PagoID = ?");
                $stmt->execute([$delId]);

                $pdo->commit();
                set_flash('Comprobante eliminado.', 'success');
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('[admin_panel] Error eliminando comprobante ' . $delId . ': ' . $e->getMessage());
                set_flash('Error al eliminar comprobante. Revisa el log.', 'error');
            }
        }
    } catch (PDOException $e) {
        error_log('[admin_panel] PDOException en POST: ' . $e->getMessage());
        set_flash('Ocurrió un error al procesar la petición.', 'error');
    }

    header('Location: admin.php');
    exit;
}

$usuarios = $pdo->query("
    SELECT
        h.HabID,
        h.Usuario,
        h.NombreH,
        h.ApellidoH,
        h.aprobado,
        h.fecha_creacion,
        p.PosID,
        p.nombre AS postulacion_nombre,
        p.telefono AS postulacion_telefono,
        p.fecha_nacimiento AS postulacion_fecha_nacimiento,
        p.habitante_uruguay AS postulacion_habitante_uruguay,
        p.motivo AS postulacion_motivo,
        p.cantidad_ingresan AS postulacion_cantidad,
        p.fecha_postulacion
    FROM Habitante h
    LEFT JOIN Postulaciones p ON p.HabID = h.HabID
    ORDER BY h.fecha_creacion DESC
")->fetchAll();

$pendientesComprobantes = $pdo
    ->query("
        SELECT PagoID
          FROM PagoCuota
         WHERE AprobadoP IS NULL
      ORDER BY PagoID ASC
    ")
    ->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Aprobaciones</title>
    <link rel="stylesheet" href="estilos/estilo.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
      .flash { padding:10px; border-radius:6px; margin-bottom:12px; }
      .flash.success { background:#e6f7ea; color:#0b6b2d; border:1px solid #98e0b2; }
      .flash.error { background:#fdecea; color:#8a1f1f; border:1px solid #f2b8b8; }
      .usuario-item { display:flex; flex-direction:column; gap:8px; }
      .postulacion { background:#f9f9f9; padding:10px; border-radius:6px; }
      .small { font-size:0.9rem; color:#666; }
      .btn-danger { background-color:#dc3545; border:none; padding:8px 12px; color:#fff; border-radius:6px; cursor:pointer; }
      .btn-secondary { background-color:#6c757d; border:none; padding:8px 12px; color:#fff; border-radius:6px; cursor:pointer; }
    </style>
</head>
<body>
<div class="contenedor">
    <div class="decoracion"></div>

    <div class="dashboard-content">
        <h1 style="color:#004080; margin:0 0 16px 0;">Panel de Aprobaciones y Gestión</h1>

        <div class="panel-columns">
            <div class="panel-column" aria-label="Usuarios registrados">
                <h2>Usuarios registrados</h2>

                <?php if (empty($usuarios)): ?>
                    <p>No hay usuarios.</p>
                <?php else: ?>
                    <ul>
                    <?php foreach ($usuarios as $u): ?>
                        <li>
                            <div class="usuario-item" role="article">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <div>
                                        <strong>#<?php echo (int)$u['HabID']; ?></strong> — <?php echo htmlspecialchars($u['Usuario']); ?>
                                        <?php if (!empty($u['NombreH']) || !empty($u['ApellidoH'])): ?>
                                            (<?php echo htmlspecialchars(trim($u['NombreH'] . ' ' . $u['ApellidoH'])); ?>)
                                        <?php endif; ?>
                                    </div>
                                    <div class="small">Aprobado: <?php echo $u['aprobado'] ? 'Sí' : 'No'; ?></div>
                                </div>

                                <div class="small">Creado: <?php echo htmlspecialchars($u['fecha_creacion'] ?? ''); ?></div>

                                <?php if (!empty($u['PosID'])): ?>
                                    <div class="postulacion" aria-label="Postulación">
                                        <div><strong>Postulación #<?php echo (int)$u['PosID']; ?></strong></div>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($u['postulacion_nombre'] ?? ''); ?></div>
                                        <div class="small">Tel: <?php echo htmlspecialchars($u['postulacion_telefono'] ?? ''); ?> — Fecha nacimiento: <?php echo htmlspecialchars($u['postulacion_fecha_nacimiento'] ?? ''); ?></div>
                                        <div style="margin-top:6px;color:#333;"><?php echo nl2br(htmlspecialchars($u['postulacion_motivo'] ?? '')); ?></div>
                                        <div class="small" style="margin-top:6px;">Cantidad que ingresan: <?php echo htmlspecialchars($u['postulacion_cantidad'] ?? ''); ?> — Fecha: <?php echo htmlspecialchars($u['fecha_postulacion'] ?? ''); ?></div>
                                    </div>
                                <?php else: ?>
                                    <div class="small" style="margin-top:6px;">No tiene postulación registrada.</div>
                                <?php endif; ?>

                                <div class="action-buttons">
                                    <?php if (!$u['aprobado']): ?>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$u['HabID']; ?>">
                                            <button type="submit">Aprobar</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!empty($u['PosID'])): ?>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="postulacion_user_id" value="<?php echo (int)$u['PosID']; ?>">
                                            <button type="submit" class="btn-secondary">Ver postulación</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" onsubmit="return confirm('¿Eliminar al usuario <?php echo addslashes(htmlspecialchars($u['Usuario'])); ?> y sus datos asociados?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="delete_user_id" value="<?php echo (int)$u['HabID']; ?>">
                                        <button type="submit" class="btn-danger">Eliminar usuario</button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="panel-column" aria-label="Comprobantes pendientes">
                <h2>Comprobantes Pendientes de Aprobación</h2>

                <?php if (empty($pendientesComprobantes)): ?>
                    <p>No hay comprobantes pendientes.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($pendientesComprobantes as $c): ?>
                            <li>
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <div>
                                        <strong>Comprobante ID <?php echo (int)$c['PagoID']; ?></strong>
                                        <div class="small">Usuario ID <?php echo (int)($c['HabID'] ?? 0); ?></div>
                                    </div>

                                    <div class="action-buttons" style="justify-content:flex-end;">
                                        <a href="ver_comprobante.php?id=<?php echo (int)$c['PagoID']; ?>" target="_blank"><button type="button">Ver</button></a>

                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="comprobante_id" value="<?php echo (int)$c['PagoID']; ?>">
                                            <button type="submit">Aprobar</button>
                                        </form>

                                        <form method="post" onsubmit="return confirm('¿Eliminar este comprobante?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="delete_comprobante_id" value="<?php echo (int)$c['PagoID']; ?>">
                                            <button type="submit" class="btn-danger">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="action-buttons" style="margin-top:24px; gap:12px;">
            <a href="index.html"><button type="button" class="btn">Cerrar sesión</button></a>
            <a href="Inicio.php"><button type="button" class="btn btn-secondary">Ir a inicio</button></a>
        </div>
    </div>
    <div class="decoracion"></div>
</div>
</body>
</html>
