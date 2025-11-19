<?php
require 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

$habSession = $_SESSION['HABID'] ?? null;
if (!$habSession) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$salonid = isset($_GET['salonid']) ? (int)$_GET['salonid'] : 0;
$fecha = $_GET['fecha'] ?? '';

if ($salonid <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            TIME_FORMAT(r.HoraInicio, '%H:%i') AS inicio,
            TIME_FORMAT(r.HoraFin, '%H:%i') AS fin,
            COALESCE(
              NULLIF(CONCAT_WS(' ', TRIM(h.NombreH), TRIM(h.ApellidoH)), ''),
              NULLIF(h.Usuario, ''),
              CONCAT('HAB#', r.HabID)
            ) AS nombre,
            r.Comentario AS comentario
        FROM reservasalon r
        LEFT JOIN Habitante h ON r.HabID = h.HABID
        WHERE r.SalonID = :sid
          AND r.Fecha = :fecha
        ORDER BY r.HoraInicio ASC
    ");
    $stmt->execute([':sid' => $salonid, ':fecha' => $fecha]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        if (!isset($r['inicio']) || !isset($r['fin'])) continue;
        $in = substr($r['inicio'], 0, 5);
        $fin = substr($r['fin'], 0, 5);
        $nombre = $r['nombre'] ?? 'Usuario';
        $coment = (isset($r['comentario']) && $r['comentario'] !== '') ? $r['comentario'] : null;

        $out[] = [
            'inicio' => $in,
            'fin' => $fin,
            'nombre' => $nombre,
            'comentario' => $coment
        ];
    }

    echo json_encode($out);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
    exit;
}
