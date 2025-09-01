<?php
require_once __DIR__ . '/config.php';
session_start();

if (empty($_SESSION['HABID'])) {
    http_response_code(403);
    exit('No autorizado');
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('DB no inicializada: $pdo no existe o no es PDO');
}

$HabID = (int)$_SESSION['HABID'];

$stmt = $pdo->prepare("SELECT foto_perfil FROM Habitante WHERE HabID = ?");
$stmt->execute([$HabID]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && !empty($row['foto_perfil'])) {
    header("Content-Type: image/jpeg" || "image/png");
    header("Cache-Control: private, max-age=86400");
    echo $row['foto_perfil'];
    exit;
}

header("Content-Type: image/jpeg");
readfile(__DIR__ . "/estilos/usuario.jpg");
