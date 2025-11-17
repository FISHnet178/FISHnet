<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/flash_set.php';

$currentHabId = $_SESSION['HABID'] ?? 0;
$stmt = $pdo->prepare("SELECT admin FROM Habitante WHERE HABID = ?");
$stmt->execute([$currentHabId]);
$isAdmin = (bool) $stmt->fetchColumn();

if (!$isAdmin) {
    header('Location: login.php');
    exit;
}

$unidades = $pdo->query("SELECT UnidadID, TerrID, Piso FROM UnidadHabitacional ORDER BY UnidadID ASC")->fetchAll();

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
        if (isset($_POST['user_id_for_unidad']) && isset($_POST['unidad_id'])) {
            $habId = (int) $_POST['user_id_for_unidad'];
            $unidadId = (int) $_POST['unidad_id'];

            if ($habId > 0 && $unidadId > 0) {
                $stmt = $pdo->prepare("UPDATE Habitante SET UnidadID = :unidad WHERE HabID = :hab");
                $stmt->execute([':unidad' => $unidadId, ':hab' => $habId]);
                set_flash('Unidad habitacional asignada correctamente.', 'success');
            } else {
                set_flash('Selecciona un usuario y una unidad válida.', 'error');
            }
        }

        if (isset($_POST['user_id'])) {
            $stmt = $pdo->prepare("UPDATE Habitante SET aprobado = 1, fecha_aprobacion = NOW() WHERE HabID = ? AND aprobado = 0");
            $stmt->execute([(int)$_POST['user_id']]);
            set_flash('Usuario aprobado.', 'success');
        }

        if (isset($_POST['postulacion_user_id'])) {
            $posID = intval($_POST['postulacion_user_id']);
            header('Location: mostrar_postulacion.php?postulacion_user_id=' . $posID . '&return=admin.php');
            exit;
        }

        if (isset($_POST['delete_user_id'])) {
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
        }

        if (isset($_POST['comprobante_id'])) {
            $stmt = $pdo->prepare("UPDATE PagoCuota SET AprobadoP = 1, fecha_aprobacionP = NOW() WHERE PagoID = ? AND AprobadoP IS NULL");
            $stmt->execute([(int)$_POST['comprobante_id']]);
            set_flash('Comprobante aprobado.', 'success');
        }

        if (isset($_POST['delete_comprobante_id'])) {
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

        if (isset($_POST['toggle_admin_id'])) {
            $habId = (int) $_POST['toggle_admin_id'];

            $stmt = $pdo->prepare("SELECT admin FROM Habitante WHERE HabID = ?");
            $stmt->execute([$habId]);
            $current = $stmt->fetchColumn();

            if ($current === false) {
                set_flash('Usuario no encontrado.', 'error');
            } else {
                $newStatus = $current ? 0 : 1;

                $isSelf = isset($_SESSION['HABID']) && ((int)$_SESSION['HABID'] === $habId);
                if ($isSelf && $newStatus === 0) {
                    if (empty($_POST['confirm_self_remove']) || $_POST['confirm_self_remove'] !== '1') {
                        set_flash('Advertencia: para quitarte el rol de administrador necesitás confirmarlo explícitamente.', 'error');
                    } else {
                        $stmt = $pdo->prepare("UPDATE Habitante SET admin = 0 WHERE HabID = ?");
                        $stmt->execute([$habId]);
                        set_flash('Te quitaste los permisos de administrador. Cerrando sesión...', 'success');

                        header('Location: logout.php');
                        exit;
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE Habitante SET admin = ? WHERE HabID = ?");
                    $stmt->execute([$newStatus, $habId]);
                    set_flash(
                        $newStatus ? 'Usuario ahora es administrador.' : 'Permisos de administrador retirados.',
                        'success'
                    );
                }
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
        h.HabID, h.Usuario, h.NombreH, h.ApellidoH, h.aprobado, h.fecha_creacion, h.admin, h.UnidadID,
        p.PosID, p.nombre AS postulacion_nombre, p.telefono AS postulacion_telefono,
        p.fecha_nacimiento AS postulacion_fecha_nacimiento,
        p.habitante_uruguay AS postulacion_habitante_uruguay,
        p.motivo AS postulacion_motivo, p.cantidad_ingresan AS postulacion_cantidad,
        p.fecha_postulacion
    FROM Habitante h
    LEFT JOIN Postulaciones p ON p.HabID = h.HabID
    ORDER BY h.fecha_creacion DESC
")->fetchAll();

$pendientesComprobantes = $pdo
    ->query("
        SELECT 
            pc.PagoID, 
            ep.HabID,
            h.Usuario,
            h.NombreH,
            h.ApellidoH
        FROM PagoCuota pc
        JOIN Efectua_pago ep ON ep.PagoID = pc.PagoID
        JOIN Habitante h ON h.HabID = ep.HabID
        WHERE pc.AprobadoP IS NULL
        ORDER BY pc.PagoID ASC
    ")
    ->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Aprobaciones</title>
    <link rel="stylesheet" href="estilos/admin.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<?php require_once __DIR__ . '/flash_set.php'; ?>
<div class="contenedor">
    <div class="decoracion"></div>

    <div class="dashboard-content">
        <h1 style="color:#004080; margin:0 0 16px 0;">Crear y editar</h1>
        <div class="action-buttons" style="margin-bottom:24px; gap:12px;">
            <a href="admin_salon.php"><button type="button" class="btn">Salones comunales</button></a>
            <a href="admin_unidad.php"><button type="button" class="btn">Unidades habitacionales</button></a>
            <a href="admin_terreno.php"><button type="button" class="btn">Terrenos</button></a>
        </div>

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
                                        <?php if ($u['admin']): ?>
                                            <span style="color:#fff;background:#007700;padding:2px 6px;border-radius:4px;font-size:12px;margin-left:8px;">Admin</span>
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

                                <div class="action-buttons" style="gap:8px; flex-wrap: wrap; align-items:center; justify-content:flex-start;">
                                    <?php if (!$u['aprobado']): ?>
                                        <form method="post" style="display:inline-block; margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$u['HabID']; ?>">
                                            <button type="submit">Aprobar</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!empty($u['PosID'])): ?>
                                        <form method="post" style="display:inline-block; margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="postulacion_user_id" value="<?php echo (int)$u['PosID']; ?>">
                                            <button type="submit" class="btn-secondary">Ver postulación</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($u['aprobado']): ?>
                                        <form method="post" class="toggle-admin-form" 
                                            data-habid="<?php echo (int)$u['HabID']; ?>" 
                                            data-is-self="<?php echo ((int)$u['HabID'] === $currentHabId) ? '1' : '0'; ?>" 
                                            style="display:inline-block; margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="toggle_admin_id" value="<?php echo (int)$u['HabID']; ?>">
                                            <button type="submit" class="btn-secondary">
                                                <?php echo $u['admin'] ? 'Quitar admin' : 'Hacer admin'; ?>
                                            </button>
                                        </form>

                                        <form method="post" style="display:flex; flex-direction:column; gap:4px; margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="user_id_for_unidad" value="<?= (int)$u['HabID'] ?>">
                                            <button type="submit">Asignar Unidad</button>
                                            <select name="unidad_id" required class="unidad-select">
                                                <option value="">Seleccionar unidad</option>
                                                <?php foreach ($unidades as $unidad): ?>
                                                    <option value="<?= (int)$unidad['UnidadID'] ?>">
                                                        <?= (int)$unidad['UnidadID'] ?> — TerrID <?= (int)$unidad['TerrID'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post"
                                        onsubmit="return confirm('¿Eliminar al usuario <?php echo addslashes(htmlspecialchars($u['Usuario'])); ?> y sus datos asociados?');"
                                        style="display:inline-block; margin:0;">
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

            <!-- ====== COMPROBANTES ====== -->

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
                                        <div class="small">
                                            Usuario ID <?php echo (int)$c['HabID']; ?> —
                                            <?php echo htmlspecialchars($c['Usuario']); ?>
                                            <?php if (!empty($c['NombreH']) || !empty($c['ApellidoH'])): ?>
                                                (<?php echo htmlspecialchars(trim($c['NombreH'].' '.$c['ApellidoH'])); ?>)
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="action-buttons" style="justify-content:flex-end; gap:8px;">
                                        <a href="ver_comprobante.php?id=<?php echo (int)$c['PagoID']; ?>" target="_blank">
                                            <button type="button">Ver</button>
                                        </a>

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
            <a href="logout.php"><button type="button" class="btn">Cerrar sesión</button></a>
            <a href="inicio.php"><button type="button" class="btn btn-secondary">Ir a inicio</button></a>
        </div>
    </div>
    <div class="decoracion"></div>
</div>

<script>
(function() {
    const WARNING_TEXT = "Estás por quitarte los permisos de administrador. " +
        "Si confirmás no tendrás acceso al panel de administración. ¿Estás seguro?";

    document.querySelectorAll('.toggle-admin-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const isSelf = form.getAttribute('data-is-self') === '1';
            const btn = form.querySelector('button[type="submit"]');
            const isRemoving = btn && btn.textContent.trim().toLowerCase().includes('quitar');

            if (isSelf && isRemoving) {
                const confirmed = confirm(WARNING_TEXT);
                if (!confirmed) {
                    e.preventDefault();
                    return;
                }
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'confirm_self_remove';
                input.value = '1';
                form.appendChild(input);
            }
        });
    });
})();
</script>

</body>
</html>
