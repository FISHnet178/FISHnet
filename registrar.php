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

// 1) Buscar si el usuario ya existe y obtener HABID y aprobado
$stmt = $pdo->prepare('SELECT HABID, aprobado FROM Habitante WHERE Usuario = ? LIMIT 1');
$stmt->execute([$Usuario]);
$habitante = $stmt->fetch(PDO::FETCH_ASSOC);

if ($habitante) {
    $habid = (int)$habitante['HABID'];
    $aprobado = (int)$habitante['aprobado'];

    // 2) Comprobar si existe una fila en Postula relacionada con este HABID
    $stmt = $pdo->prepare('SELECT PosID FROM Postula WHERE HABID = ? LIMIT 1');
    $stmt->execute([$habid]);
    $postula = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($postula && !empty($postula['PosID'])) {
        // Ya tiene una postulación asociada
        js_alert_and_redirect('Usuario ya existe, espera a ser aprobado.', 'index.html');
        exit;
    }

    if ($aprobado === 1) {
        js_alert_and_redirect('Tu cuenta ya está aprobada. Puedes iniciar sesión.', 'login.html');
        exit;
    }

    // Usuario existe pero sin postulación ni aprobación
    js_alert_and_redirect('Usuario ya existe. Completa la postulación para terminar el proceso.', 'postulacion.html');
    exit;
}

// 3) Usuario no existe: crear en transacción y devolver mensaje para postular
try {
    $pdo->beginTransaction();

    $hash = password_hash($Contrasena, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO Habitante (Usuario, Contrasena, aprobado) VALUES (?, ?, 0)');
    $stmt->execute([$Usuario, $hash]);

    $habid = (int)$pdo->lastInsertId();

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    // Loguea $e->getMessage() en tu sistema de logs en producción
    js_alert('Error al registrar usuario. Intenta de nuevo.');
    exit;
}

js_alert_and_redirect('Registro completado. Continúa con la postulación.', 'postulacion.html');
exit;
