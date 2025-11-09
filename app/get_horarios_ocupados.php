<?php
require 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');
$salonid = isset($_GET['salonid']) ? (int)$_GET['salonid'] : 0;
$fecha = $_GET['fecha'] ?? '';
if (!$salonid || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode([]);
    exit;
}
$stmt = $pdo->prepare("SELECT HoraInicio, HoraFin FROM reservasalon WHERE SalonID=:sid AND Fecha=:fecha");
$stmt->execute([':sid'=>$salonid, ':fecha'=>$fecha]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$out = [];
foreach ($rows as $r) {
    $hi = $r['HoraInicio'];
    $hf = $r['HoraFin'];
    $normalize = function($v){
        if ($v === null) return null;
        $s = trim((string)$v);
        if (preg_match('/^\d{1,2}:\d{2}$/', $s)) return $s;
        if (preg_match('/^\d{3,4}$/', $s)) {
            $len = strlen($s);
            $hh = substr($s, 0, $len-2);
            $mm = substr($s, -2);
            return str_pad($hh,2,'0',STR_PAD_LEFT).':'.str_pad($mm,2,'0',STR_PAD_LEFT);
        }
        if (is_numeric($s) && (int)$s >= 0 && (int)$s <= 24) {
            return str_pad((int)$s,2,'0',STR_PAD_LEFT).':00';
        }
        return null;
    };
    $hin = $normalize($hi);
    $hfn = $normalize($hf);
    if ($hin && $hfn) {
        $out[] = ['inicio' => $hin, 'fin' => $hfn];
    }
}
echo json_encode($out);
