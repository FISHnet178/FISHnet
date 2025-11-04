<?php
require 'config.php';
session_start();

$habid = $_SESSION['HABID'] ?? null;
if (!$habid) {
    http_response_code(401);
    exit('Debes iniciar sesión para reservar.');
}

$salonid = isset($_POST['salonid']) ? (int) $_POST['salonid'] : 0;
$fecha = $_POST['fecha'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fin = $_POST['hora_fin'] ?? '';
$comentario = trim($_POST['comentario'] ?? '');
if ($salonid <= 0) { http_response_code(400); exit('Salón inválido.'); }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { http_response_code(400); exit('Fecha inválida.'); }
if (!preg_match('/^\d{2}:\d{2}$/', $hora_inicio) || !preg_match('/^\d{2}:\d{2}$/', $hora_fin)) { http_response_code(400); exit('Formato de hora inválido.'); }

try {
    $dtInicio = new DateTime($fecha . ' ' . $hora_inicio);
    $dtFin = new DateTime($fecha . ' ' . $hora_fin);
} catch (Exception $e) {
    http_response_code(400); exit('Fecha u hora inválida.');
}
if ($dtInicio >= $dtFin) { http_response_code(400); exit('La hora de inicio debe ser anterior a la hora de fin.'); }

$hora_inicio_sql = $dtInicio->format('H:i:s');
$hora_fin_sql   = $dtFin->format('H:i:s');

try {
    $stmt = $pdo->prepare("SELECT SalonID FROM SalonComunal WHERE SalonID = :sid LIMIT 1");
    $stmt->execute([':sid' => $salonid]);
    if ($stmt->fetchColumn() === false) {
        http_response_code(404); exit('Salón no encontrado.');
    }

    $pdo->beginTransaction();

    $sqlLock = "
      SELECT ReservaID, HoraInicio, HoraFin
      FROM reservasalon
      WHERE SalonID = :salonid
        AND Fecha = :fecha
      FOR UPDATE
    ";
    $stmtLock = $pdo->prepare($sqlLock);
    $stmtLock->execute([
        ':salonid' => $salonid,
        ':fecha'   => $fecha
    ]);

    $conflict = false;
    while ($row = $stmtLock->fetch(PDO::FETCH_ASSOC)) {
        $existInicio = new DateTime($fecha . ' ' . $row['HoraInicio']);
        $existFin    = new DateTime($fecha . ' ' . $row['HoraFin']);

        if (!($existFin <= $dtInicio || $existInicio >= $dtFin)) {
            $conflict = true;
            break;
        }
    }

    if ($conflict) {
        $pdo->rollBack();
        http_response_code(409);
        exit('El horario solicitado se solapa con otra reserva. Elige otro horario.');
    }
    $sqlInsert = "
      INSERT INTO reservasalon (SalonID, HabID, Fecha, HoraInicio, HoraFin, Comentario)
      VALUES (:salonid, :habid, :fecha, :hora_inicio, :hora_fin, :comentario)
    ";
    $stmtIns = $pdo->prepare($sqlInsert);
    $stmtIns->execute([
        ':salonid'    => $salonid,
        ':habid'      => $habid,
        ':fecha'      => $fecha,
        ':hora_inicio'=> $hora_inicio_sql,
        ':hora_fin'   => $hora_fin_sql,
        ':comentario' => $comentario
    ]);

    $pdo->commit();
    header('Location: reservar_salon.php?success=1');
    exit();

} catch (Exception $e) {
    try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $ex) { error_log('Rollback fallo: '.$ex->getMessage()); }
    error_log('Error al crear reserva: '.$e->getMessage());
    http_response_code(500);
    exit('Error al crear la reserva. Intenta nuevamente.');
}
