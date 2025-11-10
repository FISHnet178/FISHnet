<?php
// get_horarios_ocupados.php
// Devuelve JSON con las reservas de un salón en una fecha.
// Formato: [ { inicio: "HH:MM", fin: "HH:MM", nombre: "Nombre Reservante", comentario: "..." }, ... ]

require 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// Opcional: exigir usuario logueado (ajusta según tu lógica)
$habSession = $_SESSION['HABID'] ?? null;
if (!$habSession) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Parámetros esperados
$salonid = isset($_GET['salonid']) ? (int)$_GET['salonid'] : 0;
$fecha = $_GET['fecha'] ?? '';

// Validaciones básicas
if ($salonid <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

try {
    // Consulta reservas del salón en la fecha dada, trayendo nombre y comentario.
    // Ajusta los nombres de columnas si tu esquema difiere.
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
        // Normalizar/filtrar filas incompletas
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
    // No revelar detalles del error en producción
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
    exit;
}
