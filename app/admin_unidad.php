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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        set_flash("Token CSRF inválido.", 'error');
        header('Location: admin_unidad.php');
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'delete' && isset($_POST['delete_unidadid'])) {
        $delId = (int) $_POST['delete_unidadid'];
        try {
            $stmtChk = $pdo->prepare("SELECT UnidadID FROM UnidadHabitacional WHERE UnidadID = :uid LIMIT 1");
            $stmtChk->execute([':uid' => $delId]);
            if ($stmtChk->fetchColumn() === false) {
                set_flash('Unidad no encontrada o ya eliminada.', 'error');
            } else {
                $pdo->beginTransaction();
                $stmtDel = $pdo->prepare("DELETE FROM UnidadHabitacional WHERE UnidadID = :uid");
                $stmtDel->execute([':uid' => $delId]);
                $pdo->commit();
                set_flash('Unidad eliminada correctamente.', 'success');
            }
        } catch (PDOException $e) {
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $ex) {}
            if ($e->getCode() === '23000') {
                set_flash('No se puede eliminar la unidad porque existen registros relacionados.', 'error');
            } else {
                error_log('Error eliminando unidad: '.$e->getMessage());
                set_flash('Error al eliminar la unidad.', 'error');
            }
        }
        header('Location: admin_unidad.php');
        exit;
    }

    if ($action === 'save') {
        $unidadid = isset($_POST['unidadid']) && $_POST['unidadid'] !== '' ? (int) $_POST['unidadid'] : null;
        $terrid = isset($_POST['terrid']) ? (int) $_POST['terrid'] : 0;
        $estado = trim($_POST['estado'] ?? 'disponible');
        $piso = isset($_POST['piso']) ? (int) $_POST['piso'] : 0;

        $errors = [];
        if ($terrid <= 0) $errors[] = "Terreno inválido.";
        if ($piso < 0) $errors[] = "Piso inválido.";
        if ($estado === '') $errors[] = "Estado es obligatorio.";

        if ($errors) {
            set_flash(implode("<br>", $errors), 'error');
            header('Location: admin_unidad.php' . ($unidadid ? "?unidadid=$unidadid" : ''));
            exit;
        }

        try {
            if ($unidadid === null) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO UnidadHabitacional (TerrID, Estado, Piso)
                    VALUES (:terrid, :estado, :piso)
                ");
                $stmtIns->execute([
                    ':terrid' => $terrid,
                    ':estado' => $estado,
                    ':piso' => $piso
                ]);
                set_flash('Unidad creada correctamente.', 'success');
            } else {
                $stmtUpd = $pdo->prepare("
                    UPDATE UnidadHabitacional
                    SET TerrID = :terrid, Estado = :estado, Piso = :piso
                    WHERE UnidadID = :unidadid
                ");
                $stmtUpd->execute([
                    ':terrid' => $terrid,
                    ':estado' => $estado,
                    ':piso' => $piso,
                    ':unidadid' => $unidadid
                ]);
                set_flash('Unidad actualizada correctamente.', 'success');
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                set_flash('Violación de integridad. Revisa datos y relaciones (FK, UNIQUE, etc.)', 'error');
            } else {
                error_log('Error guardando unidad: ' . $e->getMessage());
                set_flash('Error al guardar la unidad.', 'error');
            }
        } catch (Exception $e) {
            error_log('Error guardando unidad: ' . $e->getMessage());
            set_flash('Error al guardar la unidad.', 'error');
        }
        header('Location: admin_unidad.php');
        exit;
    }
}

$listaUnidades = [];
try {
    $stmtAll = $pdo->query("
        SELECT u.UnidadID, u.TerrID, u.Estado, u.Piso, t.NombreT
        FROM UnidadHabitacional u
        LEFT JOIN Terreno t ON t.TerrID = u.TerrID
        ORDER BY u.UnidadID DESC
    ");
    $listaUnidades = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error listando unidades: '.$e->getMessage());
    set_flash('No se pudo cargar la lista de unidades.', 'error');
}

$terrenos = [];
try {
    $stmtT = $pdo->query("SELECT TerrID, NombreT FROM Terreno ORDER BY TerrID");
    $terrenos = $stmtT->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$editUnidad = null;
if (isset($_GET['unidadid'])) {
    $uid = (int) $_GET['unidadid'];
    $stmtE = $pdo->prepare("SELECT UnidadID, TerrID, Estado, Piso FROM UnidadHabitacional WHERE UnidadID = :uid LIMIT 1");
    $stmtE->execute([':uid' => $uid]);
    $editUnidad = $stmtE->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administrar Unidades Habitacionales</title>
<link rel="stylesheet" href="estilos/registro.css">
</head>
<body>

<?php get_flash(); ?>

<div class="contenedor">
  <div class="registro-form">
    <h1><?= $editUnidad ? 'Editar Unidad' : 'Crear Unidad' ?></h1>

    <form method="post" action="admin_unidad.php" id="unidadForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="unidadid" value="<?= $editUnidad ? (int)$editUnidad['UnidadID'] : '' ?>">

      <label for="terrid">Terreno</label>
      <select name="terrid" id="terrid" required>
        <option value="">-- seleccionar --</option>
        <?php foreach ($terrenos as $t): ?>
          <option value="<?= (int)$t['TerrID'] ?>" <?= ($editUnidad && (int)$editUnidad['TerrID'] === (int)$t['TerrID']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['TerrID'] . ' - ' . $t['NombreT']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="estado">Estado</label>
      <select name="estado" id="estado" required>
        <option value="disponible" <?= ($editUnidad && $editUnidad['Estado'] === 'disponible') ? 'selected' : '' ?>>disponible</option>
        <option value="ocupado" <?= ($editUnidad && $editUnidad['Estado'] === 'ocupado') ? 'selected' : '' ?>>ocupado</option>
        <option value="mantenimiento" <?= ($editUnidad && $editUnidad['Estado'] === 'mantenimiento') ? 'selected' : '' ?>>mantenimiento</option>
        <option value="cerrado" <?= ($editUnidad && $editUnidad['Estado'] === 'cerrado') ? 'selected' : '' ?>>cerrado</option>
      </select>

      <label for="piso">Piso</label>
      <input type="number" name="piso" id="piso" required value="<?= htmlspecialchars($editUnidad['Piso'] ?? '') ?>">

      <div class="actions">
        <button type="submit"><?= $editUnidad ? 'Actualizar unidad' : 'Crear unidad' ?></button>
        <?php if ($editUnidad): ?>
          <a class="nuevo-btn" href="admin_unidad.php" style="background:#6c757d;color:#fff;padding:8px 10px;border-radius:4px;text-decoration:none;margin-left:8px;">Crear nuevo</a>
        <?php endif; ?>
      </div>

      <div class="error" id="clientError" aria-live="polite"></div>
    </form>

    <div class="action-buttons">
      <p><button class="inicio" onclick="window.location.href='admin.php'">← Volver al inicio</button></p>
    </div>
  </div>
          
  <div class="decoracion"></div>

  <div class="lista panel-column" aria-label="Unidades registradas">
    <h2>Unidades registradas</h2>

    <?php if (empty($listaUnidades)): ?>
      <p>No hay unidades.</p>
    <?php else: ?>
      <ul class="list-reset">
        <?php foreach ($listaUnidades as $u): ?>
          <li>
            <div class="unidad-item" role="article">
              <div style="display:flex;justify-content:space-between;align-items:center;flex:1;">
                <div>
                  <strong>#<?= (int)$u['UnidadID'] ?></strong>
                  <?php if (!empty($u['NombreT'])): ?>
                    — <?= htmlspecialchars($u['NombreT']) ?>
                  <?php else: ?>
                    — TerrID <?= (int)$u['TerrID'] ?>
                  <?php endif; ?>
                  <div class="unidad-meta">Piso <?= htmlspecialchars($u['Piso']) ?> — <?= htmlspecialchars($u['Estado']) ?></div>
                </div>
              </div>

              <div class="action-buttons" style="display:flex;gap:8px;margin-left:12px;">
                <a class="editar-link" href="admin_unidad.php?unidadid=<?= (int)$u['UnidadID'] ?>"><button>Editar</button></a>

                <form method="post" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="delete_unidadid" value="<?= (int)$u['UnidadID'] ?>">
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
const form = document.getElementById('unidadForm');
const clientError = document.getElementById('clientError');
form.addEventListener('submit', (e) => {
  clientError.textContent = '';
  const terr = form.terrid.value;
  const piso = form.piso.value;
  const estado = form.estado.value;
  if (!terr || !piso || !estado) {
    clientError.textContent = 'Completa todos los campos obligatorios.';
    e.preventDefault();
    return;
  }
});
</script>
</body>
</html>