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
        header('Location: admin_terreno.php');
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'delete' && isset($_POST['delete_terrid'])) {
        $delId = (int) $_POST['delete_terrid'];
        try {
            $stmtChk = $pdo->prepare("SELECT TerrID FROM Terreno WHERE TerrID = :tid LIMIT 1");
            $stmtChk->execute([':tid' => $delId]);
            if ($stmtChk->fetchColumn() === false) {
                set_flash('Terreno no encontrado o ya eliminado.', 'error');
            } else {
                $pdo->beginTransaction();
                $stmtDel = $pdo->prepare("DELETE FROM Terreno WHERE TerrID = :tid");
                $stmtDel->execute([':tid' => $delId]);
                $pdo->commit();
                set_flash('Terreno eliminado correctamente.', 'success');
            }
        } catch (PDOException $e) {
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $ex) {}
            if ($e->getCode() === '23000') {
                set_flash('No se puede eliminar el terreno porque existen registros relacionados.', 'error');
            } else {
                error_log('Error eliminando terreno: '.$e->getMessage());
                set_flash('Error al eliminar el terreno.', 'error');
            }
        }
        header('Location: admin_terreno.php');
        exit;
    }

    if ($action === 'save') {
        $terrid = isset($_POST['terrid']) && $_POST['terrid'] !== '' ? (int) $_POST['terrid'] : null;
        $nombreT = trim($_POST['nombreT'] ?? '');
        $fechaConstruccion = trim($_POST['fechaConstruccion'] ?? '');
        $tipoTerreno = trim($_POST['tipoTerreno'] ?? '');
        $calle = trim($_POST['calle'] ?? '');
        $numeroPuerta = isset($_POST['numeroPuerta']) ? (int) $_POST['numeroPuerta'] : 0;

        $errors = [];
        if ($nombreT === '') $errors[] = "Nombre del terreno es obligatorio.";
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaConstruccion)) $errors[] = "Fecha de construcción inválida (AAAA-MM-DD).";
        if ($tipoTerreno === '') $errors[] = "Tipo de terreno es obligatorio.";
        if ($calle === '') $errors[] = "Calle es obligatoria.";
        if ($numeroPuerta <= 0) $errors[] = "Número de puerta inválido.";

        if ($errors) {
            set_flash(implode("<br>", $errors), 'error');
            header('Location: admin_terreno.php' . ($terrid ? "?terrid=$terrid" : ''));
            exit;
        }

        try {
            if ($terrid === null) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO Terreno (NombreT, FechaConstruccion, TipoTerreno, Calle, NumeroPuerta)
                    VALUES (:nombreT, :fechaConstruccion, :tipoTerreno, :calle, :numeroPuerta)
                ");
                $stmtIns->execute([
                    ':nombreT' => $nombreT,
                    ':fechaConstruccion' => $fechaConstruccion,
                    ':tipoTerreno' => $tipoTerreno,
                    ':calle' => $calle,
                    ':numeroPuerta' => $numeroPuerta
                ]);
                set_flash('Terreno creado correctamente.', 'success');
            } else {
                $stmtUpd = $pdo->prepare("
                    UPDATE Terreno
                    SET NombreT = :nombreT, FechaConstruccion = :fechaConstruccion, TipoTerreno = :tipoTerreno, Calle = :calle, NumeroPuerta = :numeroPuerta
                    WHERE TerrID = :terrid
                ");
                $stmtUpd->execute([
                    ':nombreT' => $nombreT,
                    ':fechaConstruccion' => $fechaConstruccion,
                    ':tipoTerreno' => $tipoTerreno,
                    ':calle' => $calle,
                    ':numeroPuerta' => $numeroPuerta,
                    ':terrid' => $terrid
                ]);
                set_flash('Terreno actualizado correctamente.', 'success');
            }
        } catch (Exception $e) {
            error_log('Error guardando terreno: ' . $e->getMessage());
            set_flash('Error al guardar el terreno.', 'error');
        }
        header('Location: admin_terreno.php');
        exit;
    }
}

$listaTerrenos = [];
try {
    $stmtAll = $pdo->query("SELECT TerrID, NombreT, FechaConstruccion, TipoTerreno, Calle, NumeroPuerta FROM Terreno ORDER BY TerrID DESC");
    $listaTerrenos = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error listando terrenos: '.$e->getMessage());
    set_flash('No se pudo cargar la lista de terrenos.', 'error');
}

$editTerr = null;
if (isset($_GET['terrid'])) {
    $tid = (int) $_GET['terrid'];
    $stmtE = $pdo->prepare("SELECT TerrID, NombreT, FechaConstruccion, TipoTerreno, Calle, NumeroPuerta FROM Terreno WHERE TerrID = :tid LIMIT 1");
    $stmtE->execute([':tid' => $tid]);
    $editTerr = $stmtE->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administrar Terrenos</title>
<link rel="stylesheet" href="estilos/registro.css">
</head>
<body>

<?php get_flash(); ?>

<div class="contenedor">
  <div class="registro-form">
    <h1><?= $editTerr ? 'Editar Terreno' : 'Crear Terreno' ?></h1>

    <form method="post" action="admin_terreno.php" id="terrenoForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="terrid" value="<?= $editTerr ? (int)$editTerr['TerrID'] : '' ?>">

      <label for="nombreT">Nombre del terreno</label>
      <input type="text" name="nombreT" id="nombreT" maxlength="50" required value="<?= htmlspecialchars($editTerr['NombreT'] ?? '') ?>">

      <label for="fechaConstruccion">Fecha de construcción</label>
      <input type="date" name="fechaConstruccion" id="fechaConstruccion" required value="<?= htmlspecialchars($editTerr['FechaConstruccion'] ?? '') ?>">

      <label for="tipoTerreno">Tipo de terreno</label>
      <input type="text" name="tipoTerreno" id="tipoTerreno" maxlength="30" required value="<?= htmlspecialchars($editTerr['TipoTerreno'] ?? '') ?>">

      <label for="calle">Calle</label>
      <input type="text" name="calle" id="calle" maxlength="50" required value="<?= htmlspecialchars($editTerr['Calle'] ?? '') ?>">

      <label for="numeroPuerta">Número de puerta</label>
      <input type="number" name="numeroPuerta" id="numeroPuerta" required value="<?= htmlspecialchars($editTerr['NumeroPuerta'] ?? '') ?>">

      <div class="actions">
        <button type="submit"><?= $editTerr ? 'Actualizar terreno' : 'Crear terreno' ?></button>
        <?php if ($editTerr): ?>
          <a class="nuevo-btn" href="admin_terreno.php" style="background:#6c757d;color:#fff;padding:8px 10px;border-radius:4px;text-decoration:none;margin-left:8px;">Crear nuevo</a>
        <?php endif; ?>
      </div>

      <div class="error" id="clientError" aria-live="polite"></div>
    </form>

    <div class="action-buttons">
      <p><button class="inicio" onclick="window.location.href='admin.php'">← Volver al inicio</button></p>
    </div>
  </div>

  <div class="decoracion"></div>

  <div class="lista panel-column" aria-label="Terrenos registrados">
    <h2>Terrenos registrados</h2>

    <?php if (empty($listaTerrenos)): ?>
      <p>No hay terrenos.</p>
    <?php else: ?>
      <ul class="list-reset">
        <?php foreach ($listaTerrenos as $t): ?>
          <li>
            <div class="terreno-item" role="article">
              <div style="display:flex;justify-content:space-between;align-items:center;flex:1;">
                <div>
                  <strong>#<?= (int)$t['TerrID'] ?></strong> — <?= htmlspecialchars($t['NombreT']) ?>
                  <div class="terreno-meta"><?= htmlspecialchars($t['TipoTerreno']) ?> — <?= htmlspecialchars($t['Calle']) ?> #<?= htmlspecialchars($t['NumeroPuerta']) ?></div>
                </div>
                <div class="small"><?= htmlspecialchars($t['FechaConstruccion']) ?></div>
              </div>

              <div class="action-buttons" style="display:flex;gap:8px;margin-left:12px;">
                <a class="editar-link" href="admin_terreno.php?terrid=<?= (int)$t['TerrID'] ?>"><button>Editar</button></a>

                <form method="post" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="delete_terrid" value="<?= (int)$t['TerrID'] ?>">
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
const form = document.getElementById('terrenoForm');
const clientError = document.getElementById('clientError');
form.addEventListener('submit', (e) => {
  clientError.textContent = '';
  const nombre = form.nombreT.value.trim();
  const fecha = form.fechaConstruccion.value;
  const tipo = form.tipoTerreno.value.trim();
  const calle = form.calle.value.trim();
  const num = form.numeroPuerta.value;
  if (!nombre || !fecha || !tipo || !calle || !num) {
    clientError.textContent = 'Completa todos los campos obligatorios.';
    e.preventDefault();
    return;
  }
});
</script>
</body>
</html>