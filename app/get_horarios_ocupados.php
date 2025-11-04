<?php
require 'config.php';
$salonid = (int)($_GET['salonid'] ?? 0);
$fecha = $_GET['fecha'] ?? '';
if($salonid<=0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)){
    echo json_encode([]);
    exit;
}
$stmt = $pdo->prepare("SELECT HoraInicio, HoraFin FROM reservasalon WHERE SalonID=:sid AND Fecha=:fecha ORDER BY HoraInicio");
$stmt->execute([':sid'=>$salonid,':fecha'=>$fecha]);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$result = [];
foreach($reservas as $r){
    $hi = $r['HoraInicio'] ? substr($r['HoraInicio'],0,5) : '00:00';
    $hf = $r['HoraFin'] ? substr($r['HoraFin'],0,5) : '00:00';
    $result[]=['inicio'=>$hi,'fin'=>$hf];
}
header('Content-Type: application/json');
echo json_encode($result);
