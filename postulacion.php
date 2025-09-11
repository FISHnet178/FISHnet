<?php
require 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmtHab = $pdo->query("SELECT HABID FROM Habitante ORDER BY HABID DESC LIMIT 1");
    $habID = $stmtHab->fetchColumn();
    if (!$habID) { die("Debes estar registrado para postularte."); }
    $nombre = $_POST["nombre"];
    $telefono = $_POST["telefono"];
    $fecha_nacimiento = $_POST["fecha_nacimiento"];
    $habitante_uruguay = $_POST["habitante_uruguay"];
    $motivo = $_POST["motivo"];
    $cantidad = intval($_POST["cantidad_ingresan"]);

    if ($habitante_uruguay !== "si") {
        die("Solo pueden postularse habitantes permanentes de Uruguay.");
    }

    $comprobante = null;
    if (isset($_FILES["comprobante_ingreso"]) && $_FILES["comprobante_ingreso"]["error"] === UPLOAD_ERR_OK) {
        $comprobante = file_get_contents($_FILES["comprobante_ingreso"]["tmp_name"]);
    }

    $edades = [];
    for ($i = 1; $i < $cantidad; $i++) {
        $edad = intval($_POST["edad_integrante_$i"]);
        $edades[] = $edad;
    }
    if (count($edades) > 1) {
        $max = max($edades);
        $min = min($edades);
        if (($max - $min) > 6) {
            die("La diferencia de edad entre hijos no puede superar los 6 años.");
        }
    }

    $stmt = $pdo->prepare("INSERT INTO Postulaciones 
        (HabID, nombre, telefono, fecha_nacimiento, habitante_uruguay, motivo, comprobante_ingreso, cantidad_ingresan) 
        VALUES (:HabID, :nombre, :telefono, :fecha_nacimiento, :habitante_uruguay, :motivo, :comprobante, :cantidad)");

    $stmt->bindParam(":HabID", $habID);
    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":telefono", $telefono);
    $stmt->bindParam(":fecha_nacimiento", $fecha_nacimiento);
    $stmt->bindParam(":habitante_uruguay", $habitante_uruguay);
    $stmt->bindParam(":motivo", $motivo);
    $stmt->bindParam(":comprobante", $comprobante, PDO::PARAM_LOB);
    $stmt->bindParam(":cantidad", $cantidad);
    $stmt->execute();

    $PosID = $pdo->lastInsertId();

    for ($i = 1; $i < $cantidad; $i++) {
        $nombre_i = $_POST["nombre_integrante_$i"];
        $apellido_i = $_POST["apellido_integrante_$i"];
        $edad_i = $_POST["edad_integrante_$i"];
        $ci_i = $_POST["ci_integrante_$i"];

        $stmt_i = $pdo->prepare("INSERT INTO Integrantes 
            (PosID, nombre, apellido, edad, ci) 
            VALUES (:PosID, :nombre, :apellido, :edad, :ci)");

        $stmt_i->bindParam(":PosID", $PosID);
        $stmt_i->bindParam(":nombre", $nombre_i);
        $stmt_i->bindParam(":apellido", $apellido_i);
        $stmt_i->bindParam(":edad", $edad_i);
        $stmt_i->bindParam(":ci", $ci_i);
        $stmt_i->execute();
    }

    echo "Postulación enviada correctamente.<br>";
    echo "Espera a ser aprobado. <a href='index.html'>Volver</a>";
    }

?>