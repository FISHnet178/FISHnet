<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['user_id'])) {
        $stmt = $pdo->prepare("
            UPDATE Habitante
               SET aprobado = 1,
                   fecha_aprobacion = NOW()
             WHERE HabID = ? 
               AND aprobado = 0
        ");
        $stmt->execute([ $_POST['user_id'] ]);

    } elseif (isset($_POST['delete_user_id'])) {
        $stmt = $pdo->prepare("
            DELETE FROM Habitante
                  WHERE HabID = ?
        ");
        $stmt->execute([ $_POST['delete_user_id'] ]);
    }
}

$pendientes = $pdo->query("
    SELECT HabID, Usuario, fecha_creacion
      FROM Habitante
     WHERE aprobado = 0
     ORDER BY fecha_creacion ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Usuarios Pendientes de Aprobación</title>
  <style>
    form { display: inline; margin: 0 4px; }
    button { cursor: pointer; }
  </style>
</head>
<body>

  <p>
  <a href="index.html">
    <button type="button">
      ← Volver
    </button>
  </a>
</p>


  <h2>Usuarios Pendientes de Aprobación</h2>
  <ul>
    <?php foreach ($pendientes as $u): ?>
      <li>

        <strong>ID <?= $u['HabID'] ?></strong> – 
        <?= htmlspecialchars($u['Usuario']) ?> 
        (Registrado el <?= $u['fecha_creacion'] ?>)

        <form method="post">
          <input type="hidden" name="user_id" value="<?= $u['HabID'] ?>">
          <button type="submit">Aprobar</button>
        </form>

        <form method="post" 
              onsubmit="return confirm('¿Estás seguro de eliminar al usuario <?= htmlspecialchars($u['Usuario']) ?>?');">
          <input type="hidden" name="delete_user_id" value="<?= $u['HabID'] ?>">
          <button type="submit">Eliminar</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>

</body>
</html>
