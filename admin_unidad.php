<?php
// admin_unidad.php
require 'config.php'; // debe exponer $pdo (PDO con ERRMODE_EXCEPTION, EMULATE_PREPARES = false)
session_start();

if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// CSRF simple
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

$errors = [];
$success = false;

// Manejo POST (save / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        http_response_code(400);
        exit('Token CSRF inválido.');
    }

    $action = $_POST['action'];

    // Eliminación
    if ($action === 'delete' && isset($_POST['delete_unidadid'])) {
        $delId = (int) $_POST['delete_unidadid'];
        try {
            $stmtChk = $pdo->prepare("SELECT UnidadID FROM UnidadHabitacional WHERE UnidadID = :uid LIMIT 1");
            $stmtChk->execute([':uid' => $delId]);
            if ($stmtChk->fetchColumn() === false) {
                $errors[] = 'Unidad no encontrada o ya eliminada.';
            } else {
                $pdo->beginTransaction();
                $stmtDel = $pdo->prepare("DELETE FROM UnidadHabitacional WHERE UnidadID = :uid");
                $stmtDel->execute([':uid' => $delId]);
                $rows = $stmtDel->rowCount();
                $pdo->commit();

                if ($rows === 0) $errors[] = 'No se eliminó la unidad (sin filas afectadas).';
                else $success = true;
            }
        } catch (PDOException $e) {
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $ex) {}
            if ($e->getCode() === '23000') {
                $errors[] = 'No se puede eliminar la unidad porque existen registros relacionados. Desvincula dependencias primero.';
            } else {
                error_log('Error eliminando unidad: '.$e->getMessage());
                $errors[] = 'Error al eliminar la unidad.';
            }
        }
    }

    // Guardar (crear o actualizar) -- TerrID no UNIQUE: permite varias unidades por Terreno
    if ($action === 'save') {
        $unidadid = isset($_POST['unidadid']) && $_POST['unidadid'] !== '' ? (int) $_POST['unidadid'] : null;
        $terrid = isset($_POST['terrid']) ? (int) $_POST['terrid'] : 0;
        $estado = trim($_POST['estado'] ?? 'disponible');
        $piso = isset($_POST['piso']) ? (int) $_POST['piso'] : 0;

        // Validaciones
        if ($terrid <= 0) $errors[] = "Terreno inválido.";
        if ($piso < 0) $errors[] = "Piso inválido.";
        if ($estado === '') $errors[] = "Estado es obligatorio.";

        if (empty($errors)) {
            try {
                if ($unidadid === null) {
                    // Insertar nueva unidad (no usamos NumeroU)
                    $stmtIns = $pdo->prepare("
                        INSERT INTO UnidadHabitacional (TerrID, Estado, Piso)
                        VALUES (:terrid, :estado, :piso)
                    ");
                    $stmtIns->execute([
                        ':terrid' => $terrid,
                        ':estado' => $estado,
                        ':piso' => $piso
                    ]);
                    $success = true;
                    $unidadid = (int)$pdo->lastInsertId();
                } else {
                    // Actualizar existente
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
                    $success = true;
                }
            } catch (PDOException $e) {
                // captura violaciones de integridad (FK, unique, etc.)
                if ($e->getCode() === '23000') {
                    $errors[] = 'Violación de integridad. Revisa datos y relaciones (FK, UNIQUE, etc.).';
                } else {
                    error_log('Error guardando unidad: ' . $e->getMessage());
                    $errors[] = 'Error al guardar la unidad.';
                }
            } catch (Exception $e) {
                error_log('Error guardando unidad: ' . $e->getMessage());
                $errors[] = 'Error al guardar la unidad.';
            }
        }
    }
}

// Cargar lista de unidades
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
    $errors[] = 'No se pudo cargar la lista de unidades.';
}

// Obtener terrenos para el select
$terrenos = [];
try {
    $stmtT = $pdo->query("SELECT TerrID, NombreT FROM Terreno ORDER BY TerrID");
    $terrenos = $stmtT->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignorar
}

// Cargar datos para edición si viene unidadid en GET
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
<div class="contenedor">
  <div class="registro-form">
    <h1><?= $editUnidad ? 'Editar Unidad' : 'Crear Unidad' ?></h1>

    <?php if ($errors): ?>
      <div class="errors">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success">Operación realizada correctamente.</div>
    <?php endif; ?>

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

    <p><a class="volver-btn" href="admin.php">← Volver al inicio</a></p>
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

                <form method="post" onsubmit="return confirm('¿Eliminar la unidad <?= addslashes(htmlspecialchars($u['NombreT'] ?? ('ID '.$u['UnidadID']))) ?>?');" style="display:inline;">
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