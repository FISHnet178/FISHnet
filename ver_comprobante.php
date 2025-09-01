<?php
require 'config.php';

if (!isset($_GET['id'])) {
    die("ID de comprobante no especificado.");
}

$stmt = $pdo->prepare("SELECT Comprobante FROM PagoCuota WHERE PagoID = ?");
$stmt->execute([ $_GET['id'] ]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Comprobante no encontrado.");
}

$comprobante = $row['Comprobante'];

if (substr($comprobante, 0, 4) === "%PDF") {
    header("Content-Type: application/pdf");
    echo $comprobante;
    exit;
}

header("Content-Type: image/jpeg");
echo $row['Comprobante'];
