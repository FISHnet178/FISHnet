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

// Seleccionamos HABID, Usuario y aprobado
$stmt = $pdo->prepare('SELECT HABID, Usuario, Contrasena, aprobado FROM Habitante WHERE Usuario = ? LIMIT 1');
$stmt->execute([$Usuario]);
$habitante = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    if ($habitante) {
        $existingHabId = (int)$habitante['HABID'];
        $existingAprobado = (int)$habitante['aprobado'];
        $hashStored = $habitante['Contrasena'];

        // Verificamos contraseña
        if (!password_verify($Contrasena, $hashStored)) {
            js_alert('Contraseña incorrecta.');
            exit;
        }

        $pdo->beginTransaction();

        // 👑 Si el usuario es el admin (HABID = 1)
        if ($existingHabId === 1) {
            if ($existingAprobado !== 1) {
                $stmt = $pdo->prepare('UPDATE Habitante SET aprobado = 1 WHERE HABID = ?');
                $stmt->execute([$existingHabId]);
            }

            $pdo->commit();

            $_SESSION['admin'] = true;
            $_SESSION['habid'] = $existingHabId;
            $_SESSION['usuario'] = $habitante['Usuario'];

            js_alert_and_redirect('Bienvenido administrador. Inicia sesión para seguir.', 'index.html');
            exit;
        }

        // 🔹 Para usuarios normales
        if ($existingAprobado !== 1) {
            $stmt = $pdo->prepare('UPDATE Habitante SET aprobado = 1 WHERE HABID = ?');
            $stmt->execute([$existingHabId]);
        }

        // Crear entrada en Postula si no existe
        $stmt = $pdo->prepare('SELECT PosID FROM Postula WHERE HABID = ? LIMIT 1');
        $stmt->execute([$existingHabId]);
        $postula = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$postula) {
            $stmt = $pdo->prepare('INSERT INTO Postula (HABID, PosID) VALUES (?, NULL)');
            $stmt->execute([$existingHabId]);
        }

        $pdo->commit();

        $_SESSION['admin'] = false;
        $_SESSION['habid'] = $existingHabId;
        $_SESSION['usuario'] = $habitante['Usuario'];

        js_alert_and_redirect('Usuario aprobado y postulación creada automáticamente.', 'index.html');
        exit;

    } else {
        $pdo->beginTransaction();

        $hash = password_hash($Contrasena, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO Habitante (Usuario, Contrasena, aprobado) VALUES (?, ?, 1)');
        $stmt->execute([$Usuario, $hash]);

        $habid = (int)$pdo->lastInsertId();

        if ($habid === 1) {
            $pdo->commit();

            $_SESSION['admin'] = true;
            $_SESSION['habid'] = $habid;
            $_SESSION['usuario'] = $Usuario;

            js_alert_and_redirect('Administrador creado correctamente.', 'admin_dashboard.php');
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO Postula (HABID, PosID) VALUES (?, NULL)');
        $stmt->execute([$habid]);

        $pdo->commit();

        $_SESSION['admin'] = false;
        $_SESSION['habid'] = $habid;
        $_SESSION['usuario'] = $Usuario;

        js_alert_and_redirect('Registro completado y aprobado. No necesitas hacer la postulación.', 'index.html');
        exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    js_alert('Error al procesar el login. Intenta de nuevo.');
    exit;
}
