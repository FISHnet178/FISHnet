<?php
require 'config.php';
session_start();

$posid = isset($_GET['posid']) ? (int) $_GET['posid'] : 0;
if ($posid <= 0) {
    http_response_code(400);
    exit('Parámetro inválido.');
}

$stmt = $pdo->prepare("SELECT comprobante_ingreso FROM Postulaciones WHERE PosID = :posid LIMIT 1");
$stmt->execute([':posid' => $posid]);
$blob = $stmt->fetchColumn();

if ($blob === false || $blob === null) {
    http_response_code(404);
    exit('No se encontró comprobante.');
}

if (substr($blob, 0, 4) === "%PDF") {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="comprobante_' . $posid . '.pdf"');
    header('Content-Length: ' . strlen($blob));
    echo $blob;
    exit;
}

$header = substr($blob, 0, 8);
if (substr($header, 0, 3) === "\xFF\xD8\xFF") {
    header('Content-Type: image/jpeg');
} elseif (substr($header, 0, 8) === "\x89PNG\x0D\x0A\x1A\x0A") {
    header('Content-Type: image/png');
} elseif (substr($header, 0, 6) === "GIF87a" || substr($header, 0, 6) === "GIF89a") {
    header('Content-Type: image/gif');
} else {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="comprobante_' . $posid . '"');
}
header('Content-Length: ' . strlen($blob));
echo $blob;
exit;
