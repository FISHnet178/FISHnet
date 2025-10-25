<?php
require 'config.php';
session_start();

$habId = $_SESSION['HABID'] ?? null;
if (!$habId) { http_response_code(403); exit("Acceso denegado."); }

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) exit('Token CSRF inválido.');

    $salonid = (int)($_POST['salonid'] ?? 0);
    $fecha = $_POST['fecha'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fin = $_POST['hora_fin'] ?? '';
    $comentario = $_POST['comentario'] ?? '';

    if ($salonid <= 0) $errors[] = 'Selecciona un salón.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $errors[] = 'Fecha inválida.';
    if (!preg_match('/^\d{2}:\d{2}$/', $hora_inicio)) $errors[] = 'Hora inicio inválida.';
    if (!preg_match('/^\d{2}:\d{2}$/', $hora_fin)) $errors[] = 'Hora fin inválida.';
    if ($hora_inicio >= $hora_fin) $errors[] = 'Hora inicio debe ser anterior a hora fin.';

    if (empty($errors)) {
        $stmtConf = $pdo->prepare("
            SELECT 1 FROM reservasalon
            WHERE SalonID=:sid AND Fecha=:fecha
            AND NOT (HoraFin<=:hi OR HoraInicio>=:hf)
            LIMIT 1
        ");
        $stmtConf->execute([
            ':sid' => $salonid,
            ':fecha' => $fecha,
            ':hi' => $hora_inicio,
            ':hf' => $hora_fin
        ]);
        if ($stmtConf->fetchColumn()) $errors[] = 'El horario seleccionado ya está ocupado.';
        else {
            $stmtIns = $pdo->prepare("
                INSERT INTO reservasalon (SalonID, HabID, Fecha, HoraInicio, HoraFin, Comentario)
                VALUES (:sid,:hid,:fecha,:hi,:hf,:coment)
            ");
            $stmtIns->execute([
                ':sid' => $salonid, ':hid' => $habId, ':fecha' => $fecha,
                ':hi' => $hora_inicio, ':hf' => $hora_fin, ':coment' => $comentario ?: null
            ]);
            $success = true;
        }
    }
}

$salones = $pdo->query("
    SELECT s.SalonID, s.TerrID, s.Estado, t.NombreT AS TerrenoNombre
    FROM SalonComunal s
    LEFT JOIN Terreno t ON s.TerrID = t.TerrID
    ORDER BY s.SalonID
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Reservar Salón</title>
<style>
body, html {margin:0; padding:0; height:100%; font-family: Arial, sans-serif;}
.contenedor {display:flex; height:100vh;}
.registro-form, .lista {flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:0 20px;}
.registro-form {background:#f2f2f2;}
.registro-form h2 {margin-bottom:20px;color:#004080;}
.registro-form form {display:flex; flex-direction:column; gap:15px; width:100%; max-width:300px;}
.registro-form label {display:flex; flex-direction:column; font-size:0.9em;}
.registro-form input, .registro-form select {padding:10px;border:1px solid #ccc; font-size:1em;}
.registro-form button {padding:10px; background:#004080; color:white; border:none; cursor:pointer; transition:0.3s;}
.registro-form button:hover {background:#003060;}
.lista {width:480px; background:#fafafa; padding:12px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow-y:auto; position:relative;}
.decoracion {position:absolute; top:0; left:0; width:100%; height:100%; z-index:0; background: repeating-linear-gradient(-45deg, #004080, #004080 15px, #84a3f7 15px, #84a3f7 30px);}
#calendario {position:relative; z-index:1; width:100%;}
.celda{flex:1; padding:5px; margin:1px; border:1px solid #ccc; text-align:center; font-size:12px; cursor:pointer; background:#e9ecef; user-select:none;}
.celda.ocupado{background:#dc3545; color:white; cursor:not-allowed;}
.celda.seleccionado{background:#007bff; color:white;}
.fila{display:flex; margin-bottom:2px;}
.errors{background:#f8d7da;color:#842029;padding:10px;margin-bottom:10px;border-radius:5px;}
.success{background:#d1e7dd;color:#0f5132;padding:10px;margin-bottom:10px;border-radius:5px;}
</style>
</head>
<body>
<div class="contenedor">
  <div class="registro-form">
    <h2>Reservar Salón</h2>
    <?php if($errors): ?>
      <div class="errors"><ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php endif; ?>
    <?php if($success): ?><div class="success">Reserva creada correctamente.</div><?php endif; ?>

    <form id="reservaForm" method="post">
      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
      <label>Salón
        <select id="salonid" name="salonid" required>
          <option value="">-- Seleccionar --</option>
          <?php foreach($salones as $s): ?>
            <option value="<?= $s['SalonID'] ?>" data-estado="<?= htmlspecialchars($s['Estado']) ?>">
                Sala #<?= $s['SalonID'] ?> — <?= htmlspecialchars($s['TerrenoNombre'] ?? 'Terreno') ?>
                <?php if(strtolower($s['Estado'])!=='disponible'){ echo " — ".htmlspecialchars($s['Estado']); } ?>
            </option>

          <?php endforeach; ?>
        </select>
      </label>
      <label>Fecha
        <input type="date" id="fecha" name="fecha" required value="<?=htmlspecialchars($_POST['fecha']??'')?>">
      </label>

      <input type="hidden" id="hora_inicio" name="hora_inicio">
      <input type="hidden" id="hora_fin" name="hora_fin">
      <button type="submit">Reservar</button>
    </form>
    <a href="inicio.php" style="margin-top:10px; color:#004080;">← Volver al inicio</a>
  </div>

  <div class="lista">
    <div class="decoracion"></div>
    <div id="calendario">
      <h3 style="color:white; text-align:center;">Calendario de Horarios</h3>
      <div class="fila" id="filaManana"></div>
      <div class="fila" id="filaTarde"></div>
    </div>
  </div>
</div>

<script>
const salonSelect=document.getElementById('salonid');
const fechaInput=document.getElementById('fecha');
const filaManana=document.getElementById('filaManana');
const filaTarde=document.getElementById('filaTarde');
const horaInicioInput=document.getElementById('hora_inicio');
const horaFinInput=document.getElementById('hora_fin');

let reservas=[];
let seleccionando=false;
let inicioSeleccion='';

function generarFila(fila,startHour,endHour){
    fila.innerHTML='';
    for(let h=startHour; h<endHour; h++){
        for(let m=0; m<60; m+=30){
            const hh=('0'+h).slice(-2), mm=('0'+m).slice(-2);
            const celda=document.createElement('div');
            celda.className='celda';
            celda.dataset.hora=`${hh}:${mm}`;
            celda.textContent=`${hh}:${mm}`;
            if(reservas.some(r=>r.inicio<=celda.dataset.hora && r.fin>celda.dataset.hora)){
                celda.classList.add('ocupado');
            }
            celda.addEventListener('mousedown',()=>{
                if(celda.classList.contains('ocupado')) return;
                seleccionando=true;
                inicioSeleccion=celda.dataset.hora;
                document.querySelectorAll('.celda.seleccionado').forEach(c=>c.classList.remove('seleccionado'));
                celda.classList.add('seleccionado');
                horaInicioInput.value=inicioSeleccion;
                horaFinInput.value=inicioSeleccion;
            });
            celda.addEventListener('mouseenter',()=>{
                if(!seleccionando) return;
                if(celda.classList.contains('ocupado')) return;
                document.querySelectorAll('.celda.seleccionado').forEach(c=>c.classList.remove('seleccionado'));
                let bloques=document.querySelectorAll('.celda');
                let seleccionados=false;
                for(let b of bloques){
                    if(b.dataset.hora===inicioSeleccion) seleccionados=true;
                    if(seleccionados) b.classList.add('seleccionado');
                    if(b.dataset.hora===celda.dataset.hora) break;
                }
                horaInicioInput.value=inicioSeleccion;
                let [h1,m1]=celda.dataset.hora.split(':');
                h1=parseInt(h1); m1=parseInt(m1)+30;
                if(m1>=60){m1-=60; h1+=1;}
                horaFinInput.value=('0'+h1).slice(-2)+':'+('0'+m1).slice(-2);
            });
            fila.appendChild(celda);
        }
    }
}

function generarCalendario(){
    generarFila(filaManana,8,14);
    generarFila(filaTarde,14,20);
}

function cargarReservas(){
    reservas=[];
    const salon=salonSelect.value;
    const fecha=fechaInput.value;
    if(!salon || !fecha){filaManana.innerHTML=filaTarde.innerHTML=''; return;}

    const salonObj = Array.from(salonSelect.options).find(o=>o.value===salon);
    const estado = salonObj.dataset.estado ?? 'disponible';

    fetch(`get_horarios_ocupados.php?salonid=${salon}&fecha=${fecha}`)
        .then(r=>r.json())
        .then(data=>{
            reservas=data;
            if(estado.toLowerCase()==='mantenimiento'){
                reservas=[];
                for(let h=8; h<20; h++){
                    for(let m=0; m<60; m+=30){
                        let hh=('0'+h).slice(-2);
                        let mm=('0'+m).slice(-2);
                        reservas.push({inicio: `${hh}:${mm}`, fin:`${hh}:${mm}`});
                    }
                }
            }
            generarCalendario();
        });
}

document.addEventListener('mouseup',()=>seleccionando=false);
salonSelect.addEventListener('change',cargarReservas);
fechaInput.addEventListener('change',cargarReservas);
</script>
</body>
</html>
