<?php
require 'config.php';
session_start();

$Usuario    = trim($_POST['Usuario']    ?? '');
$Contrasena = trim($_POST['Contraseña'] ?? '');

function js_alert(string $message) {
    $msg = json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo "<script>alert($msg);</script>";
}

function js_alert_and_redirect(string $message, string $location) {
    $msg = json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $loc = json_encode($location, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo "<script>alert($msg); window.location.href = $loc;</script>";
    exit;
}

if ($Usuario === '' || $Contrasena === '') {
    js_alert('Completa usuario y contraseña.');
    exit;
}

$stmt = $pdo->prepare('SELECT HABID, Usuario, Contrasena, aprobado, admin FROM Habitante WHERE Usuario = ? LIMIT 1');
$stmt->execute([$Usuario]);
$habitante = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    if ($habitante) {
        $existingHabId = (int)$habitante['HABID'];
        $existingAprobado = (int)$habitante['aprobado'];
        $esAdmin = (int)$habitante['admin'] === 1;
        $hashStored = $habitante['Contrasena'];

        if (!password_verify($Contrasena, $hashStored)) {
            js_alert('Contraseña incorrecta.');
            exit;
        }

        $pdo->beginTransaction();

        if ($existingAprobado !== 1) {
            $stmt = $pdo->prepare('UPDATE Habitante SET aprobado = 1 WHERE HABID = ?');
            $stmt->execute([$existingHabId]);
        }

        $stmt = $pdo->prepare('SELECT PosID FROM Postula WHERE HABID = ? LIMIT 1');
        $stmt->execute([$existingHabId]);
        $postula = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$postula) {
            $stmt = $pdo->prepare('INSERT INTO Postula (HABID, PosID) VALUES (?, NULL)');
            $stmt->execute([$existingHabId]);
        }

        $pdo->commit();

        $_SESSION['admin'] = $esAdmin;
        $_SESSION['habid'] = $existingHabId;
        $_SESSION['usuario'] = $habitante['Usuario'];

        if ($esAdmin) {
            js_alert_and_redirect('Bienvenido administrador.', 'admin_dashboard.php');
        } else {
            js_alert_and_redirect('Inicio de sesión correcto.', 'index.html');
        }

        exit;

    } else {
        $pdo->beginTransaction();

        $hash = password_hash($Contrasena, PASSWORD_DEFAULT);

        $hayAdmins = $pdo->query('SELECT COUNT(*) FROM Habitante WHERE admin = 1')->fetchColumn();
        $esPrimerAdmin = $hayAdmins == 0 ? 1 : 0;

        $stmt = $pdo->prepare('INSERT INTO Habitante (Usuario, Contrasena, aprobado, admin) VALUES (?, ?, 1, ?)');
        $stmt->execute([$Usuario, $hash, $esPrimerAdmin]);

        $habid = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO Postula (HABID, PosID) VALUES (?, NULL)');
        $stmt->execute([$habid]);

        $pdo->commit();

        $_SESSION['admin'] = $esPrimerAdmin === 1;
        $_SESSION['habid'] = $habid;
        $_SESSION['usuario'] = $Usuario;

        if ($esPrimerAdmin) {
            js_alert_and_redirect('Administrador creado correctamente.', 'inicio.php');
        } else {
            js_alert_and_redirect('Registro completado y aprobado. No necesitas hacer la postulación.', 'index.html');
        }

        exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    js_alert('Error al procesar el login. Intenta de nuevo.');
    exit;
}
?>
