<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/flash_set.php';

$currentHabId = $_SESSION['HABID'] ?? 0;
$stmt = $pdo->prepare("SELECT admin FROM Habitante WHERE HABID = ?");
$stmt->execute([$currentHabId]);
$isAdmin = (bool) $stmt->fetchColumn();
if (!$isAdmin) {
    set_flash("Debes iniciar sesión como administrador.", 'error');
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

// Función para convertir horario HH:MM a entero
function timeInputToInt(string $hhmm): ?int {
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hhmm)) return null;
    $parts = explode(':', $hhmm);
    $hh = (int)$parts[0];
    $mm = (int)$parts[1];
    if ($hh < 0 || $hh > 23 || $mm < 0 || $mm > 59) return null;
    return $hh * 100 + $mm;
}

// Procesamiento de POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        set_flash("Token CSRF inválido.", 'error');
        header('Location: admin_salon.php');
        exit;
    }

    $action = $_POST['action'];

    // Eliminar salón
    if ($action === 'delete' && isset($_POST['delete_salonid'])) {
        $delId = (int) $_POST['delete_salonid'];
        try {
            $stmtChk = $pdo->prepare("SELECT SalonID FROM SalonComunal WHERE SalonID = :sid LIMIT 1");
            $stmtChk->execute([':sid' => $delId]);
            if ($stmtChk->fetchColumn() === false) {
                set_flash("Salón no encontrado o ya eliminado.", 'error');
            } else {
                $pdo->beginTransaction();
                $stmtDel = $pdo->prepare("DELETE FROM SalonComunal WHERE SalonID = :sid");
                $stmtDel->execute([':sid' => $delId]);
                $pdo->commit();
                set_flash("Salón eliminado correctamente.", 'success');
            }
        } catch (PDOException $e) {
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $ex) {}
            if ($e->getCode() === '23000') {
                set_flash("No se puede eliminar el salón porque existen registros relacionados.", 'error');
            } else {
                error_log('Error eliminando salón: '.$e->getMessage());
                set_flash("Error al eliminar el salón.", 'error');
            }
        }
        header('Location: admin_salon.php');
        exit;
    }

    // Crear/actualizar salón
    if ($action === 'save') {
        $salonid = isset($_POST['salonid']) && $_POST['salonid'] !== '' ? (int) $_POST['salonid'] : null;
        $terrid = isset($_POST['terrid']) ? (int) $_POST['terrid'] : 0;
        $estado = trim($_POST['estado'] ?? 'disponible');
        $horario_inicio_raw = trim($_POST['horario_inicio'] ?? '');
        $horario_fin_raw = trim($_POST['horario_fin'] ?? '');

        $errors = [];

        if ($terrid <= 0) $errors[] = "Terreno inválido.";
        $hi_int = timeInputToInt($horario_inicio_raw);
        $hf_int = timeInputToInt($horario_fin_raw);
        if ($hi_int === null) $errors[] = "Formato horario inicio inválido (HH:MM).";
        if ($hf_int === null) $errors[] = "Formato horario fin inválido (HH:MM).";
        if ($hi_int !== null && $hf_int !== null && $hi_int >= $hf_int) $errors[] = "El horario inicio debe ser anterior al horario fin.";

        if (!empty($errors)) {
            set_flash(implode("<br>", $errors), 'error');
            header('Location: admin_salon.php' . ($salonid ? "?salonid=$salonid" : ''));
            exit;
        }

        try {
            if ($salonid === null) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO SalonComunal (TerrID, Estado, HorInicio, HorFin)
                    VALUES (:terrid, :estado, :horIni, :horFin)
                ");
                $stmtIns->execute([
                    ':terrid' => $terrid,
                    ':estado' => $estado,
                    ':horIni' => $hi_int,
                    ':horFin' => $hf_int
                ]);
                set_flash("Salón creado correctamente.", 'success');
            } else {
                $stmtUpd = $pdo->prepare("
                    UPDATE SalonComunal
                    SET TerrID = :terrid, Estado = :estado, HorInicio = :horIni, HorFin = :horFin
                    WHERE SalonID = :salonid
                ");
                $stmtUpd->execute([
                    ':terrid' => $terrid,
                    ':estado' => $estado,
                    ':horIni' => $hi_int,
                    ':horFin' => $hf_int,
                    ':salonid' => $salonid
                ]);
                set_flash("Salón actualizado correctamente.", 'success');
            }
            header('Location: admin_salon.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                set_flash("Violación de integridad. Revisa los datos y relaciones.", 'error');
            } else {
                error_log('Error guardando salón: ' . $e->getMessage());
                set_flash("Error al guardar el salón.", 'error');
            }
            header('Location: admin_salon.php' . ($salonid ? "?salonid=$salonid" : ''));
            exit;
        }
    }
}

// Listado de salones
$listaSalones = [];
try {
    $stmtAll = $pdo->query("
        SELECT s.SalonID, s.TerrID, s.Estado, s.HorInicio, s.HorFin, t.NombreT
        FROM SalonComunal s
        LEFT JOIN Terreno t ON t.TerrID = s.TerrID
        ORDER BY s.SalonID DESC
    ");
    $listaSalones = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    set_flash('No se pudo cargar la lista de salones.', 'error');
}

// Terrenos
$terrenos = [];
try {
    $stmtT = $pdo->query("SELECT TerrID, NombreT FROM Terreno ORDER BY TerrID");
    $terrenos = $stmtT->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Edición
$editSalon = null;
if (isset($_GET['salonid'])) {
    $sid = (int) $_GET['salonid'];
    $stmtE = $pdo->prepare("SELECT SalonID, TerrID, Estado, HorInicio, HorFin FROM SalonComunal WHERE SalonID = :sid LIMIT 1");
    $stmtE->execute([':sid' => $sid]);
    $s = $stmtE->fetch(PDO::FETCH_ASSOC);
    if ($s) {
        $formatTime = function($intVal) {
            $v = (int)$intVal;
            $hh = floor($v / 100);
            $mm = $v % 100;
            return sprintf('%02d:%02d', $hh, $mm);
        };
        if (isset($s['HorInicio'])) $s['HorarioInicioFmt'] = $formatTime($s['HorInicio']);
        if (isset($s['HorFin']))   $s['HorarioFinFmt']   = $formatTime($s['HorFin']);
        $editSalon = $s;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Administrar Salones</title>
  <link rel="stylesheet" href="estilos/registro.css">
</head>
<body>

<?php get_flash(); ?> <!-- flashes -->

<div class="contenedor">
  <div class="registro-form">
    <h1><?= $editSalon ? 'Editar Salón' : 'Crear Salón' ?></h1>

    <form method="post" action="admin_salon.php" id="salonForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="salonid" value="<?= $editSalon ? (int)$editSalon['SalonID'] : '' ?>">

      <label for="terrid">Terreno</label>
      <select name="terrid" id="terrid" required>
        <option value="">-- seleccionar --</option>
        <?php foreach ($terrenos as $t): ?>
          <option value="<?= (int)$t['TerrID'] ?>" <?= ($editSalon && (int)$editSalon['TerrID'] === (int)$t['TerrID']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['TerrID'] . ' - ' . $t['NombreT']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="estado">Estado</label>
      <select name="estado" id="estado" required>
        <option value="disponible" <?= ($editSalon && $editSalon['Estado'] === 'disponible') ? 'selected' : '' ?>>disponible</option>
        <option value="mantenimiento" <?= ($editSalon && $editSalon['Estado'] === 'mantenimiento') ? 'selected' : '' ?>>mantenimiento</option>
        <option value="cerrado" <?= ($editSalon && $editSalon['Estado'] === 'cerrado') ? 'selected' : '' ?>>cerrado</option>
      </select>

      <label for="horario_inicio">Horario permitido - Inicio</label>
      <input type="time" name="horario_inicio" id="horario_inicio" required value="<?= htmlspecialchars($editSalon['HorarioInicioFmt'] ?? '08:00') ?>">

      <label for="horario_fin">Horario permitido - Fin</label>
      <input type="time" name="horario_fin" id="horario_fin" required value="<?= htmlspecialchars($editSalon['HorarioFinFmt'] ?? '22:00') ?>">

      <div class="actions">
        <button type="submit"><?= $editSalon ? 'Actualizar salón' : 'Crear salón' ?></button>
        <?php if ($editSalon): ?>
          <a class="nuevo-btn" href="admin_salon.php" style="background:#6c757d;color:#fff;padding:8px 10px;border-radius:4px;text-decoration:none;margin-left:8px;">Crear nuevo</a>
        <?php endif; ?>
      </div>

      <div class="error" id="clientError" aria-live="polite"></div>
    </form>

    <p><a class="volver-btn" href="admin.php">← Volver al inicio</a></p>
  </div>

  <div class="decoracion"></div>

  <div class="lista panel-column" aria-label="Salones registrados">
    <h2>Salones registrados</h2>

    <?php if (empty($listaSalones)): ?>
      <p>No hay salones.</p>
    <?php else: ?>
      <ul class="list-reset">
        <?php foreach ($listaSalones as $s): ?>
          <li>
            <div class="salon-item" role="article">
              <div style="display:flex;justify-content:space-between;align-items:center;flex:1;">
                <div>
                  <strong>#<?= (int)$s['SalonID'] ?></strong>
                  <?php if (!empty($s['NombreT'])): ?>
                    — <?= htmlspecialchars($s['NombreT']) ?>
                  <?php else: ?>
                    — TerrID <?= (int)$s['TerrID'] ?>
                  <?php endif; ?>
                  <div class="salon-meta">Estado: <?= htmlspecialchars($s['Estado']) ?></div>
                </div>
                <?php
                  $fmt = function($v){
                    $iv = (int)$v;
                    $hh = floor($iv/100);
                    $mm = $iv % 100;
                    return sprintf('%02d:%02d',$hh,$mm);
                  };
                ?>
                <div class="small"><?= htmlspecialchars($s['HorInicio'] ? $fmt($s['HorInicio']) : '08:00') ?> - <?= htmlspecialchars($s['HorFin'] ? $fmt($s['HorFin']) : '22:00') ?></div>
              </div>

              <div class="action-buttons" style="display:flex;gap:8px;margin-left:12px;">
                <a class="editar-link" href="admin_salon.php?salonid=<?= (int)$s['SalonID'] ?>"><button>Editar</button></a>

                <form method="post" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="delete_salonid" value="<?= (int)$s['SalonID'] ?>">
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

<script>
  const form = document.getElementById('salonForm');
  const clientError = document.getElementById('clientError');
  form.addEventListener('submit', (e) => {
    clientError.textContent = '';
    const terr = form.terrid.value;
    const hi = form.horario_inicio.value;
    const hf = form.horario_fin.value;
    if (!terr || !hi || !hf) {
      clientError.textContent = 'Completa todos los campos obligatorios.';
      e.preventDefault();
      return;
    }
    if (hi >= hf) {
      clientError.textContent = 'El horario inicio debe ser anterior al horario fin.';
      e.preventDefault();
      return;
    }
  });
</script>
</body>
</html>