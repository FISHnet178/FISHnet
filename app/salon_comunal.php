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
        function minutosDesde($hhmm){
            [$h,$m] = explode(':', $hhmm);
            return ((int)$h)*60 + ((int)$m);
        }
        $dur = minutosDesde($hora_fin) - minutosDesde($hora_inicio);
        if ($dur < 60) $errors[] = 'La reserva debe ser por intervalos mínimos de 1 hora.';
        if (($dur % 30) !== 0) $errors[] = 'La duración debe ser un múltiplo de 30 minutos.';
    }
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
    SELECT s.SalonID, s.TerrID, s.Estado, s.HorInicio, s.HorFin, t.NombreT AS TerrenoNombre
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
.registro-form form {display:flex; flex-direction:column; gap:15px; width:100%; max-width:320px;}
.registro-form label {display:flex; flex-direction:column; font-size:0.9em;}
.registro-form input, .registro-form select, .registro-form textarea {padding:10px;border:1px solid #ccc; font-size:1em;}
.registro-form button {padding:10px; background:#004080; color:white; border:none; cursor:pointer; transition:0.3s;}
.registro-form button:hover {background:#003060;}
.lista {width:480px; background:#fafafa; padding:12px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow-y:auto; position:relative;}
.decoracion {position:absolute; top:0; left:0; width:100%; height:100%; z-index:0; background: repeating-linear-gradient(-45deg, #004080, #004080 15px, #84a3f7 15px, #84a3f7 30px);}
#calendario {position:relative; z-index:1; width:100%;}
.celda{flex:1; padding:5px; margin:1px; border:1px solid #ccc; text-align:center; font-size:12px; cursor:pointer; background:#e9ecef; user-select:none; min-width:60px;}
.celda.ocupado{background:#dc3545; color:white; cursor:not-allowed;}
.celda.seleccionado{background:#007bff; color:white;}
.fila{display:flex; flex-wrap:wrap; margin-bottom:6px;}
.errors{background:#f8d7da;color:#842029;padding:10px;margin-bottom:10px;border-radius:5px;}
.success{background:#d1e7dd;color:#0f5132;padding:10px;margin-bottom:10px;border-radius:5px;}
.info-line{color:white; text-align:center; margin-bottom:8px;}
.legend {display:flex; gap:10px; margin-top:8px; color:#fff; justify-content:center;}
.legend span{padding:4px 8px; border-radius:4px; font-size:12px;}
.legend .ocup{background:#dc3545;}
.legend .libre{background:#007bff;}
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
            <option value="<?= $s['SalonID'] ?>"
                    data-estado="<?= htmlspecialchars($s['Estado']) ?>"
                    data-horinicio="<?= (int)$s['HorInicio'] ?>"
                    data-horfin="<?= (int)$s['HorFin'] ?>">
                Sala #<?= $s['SalonID'] ?> — <?= htmlspecialchars($s['TerrenoNombre'] ?? 'Terreno') ?>
                <?php if(strtolower($s['Estado'])!=='disponible'){ echo " — ".htmlspecialchars($s['Estado']); } ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Fecha
        <input type="date" id="fecha" name="fecha" required value="<?=htmlspecialchars($_POST['fecha']??'')?>">
      </label>
      <label>Comentario (opcional)
        <textarea name="comentario" rows="3"><?=htmlspecialchars($_POST['comentario']??'')?></textarea>
      </label>
      <input type="hidden" id="hora_inicio" name="hora_inicio">
      <input type="hidden" id="hora_fin" name="hora_fin">
      <button type="submit">Reservar</button>
    </form>
    <br>
    <div class="action-buttons">
        <p><button class="inicio" onclick="window.location.href='Inicio.php'">← Volver al inicio</button></p>
    </div>
  </div>
  <div class="lista">
    <div class="decoracion"></div>
    <div id="calendario">
      <h3 class="info-line">Calendario de Horarios</h3>
      <div class="legend">
        <span class="libre">Seleccionado</span>
        <span class="ocup">Ocupado / Mantenimiento</span>
      </div>
      <div class="fila" id="filaHorario"></div>
    </div>
  </div>
</div>
<script>
const salonSelect=document.getElementById('salonid');
const fechaInput=document.getElementById('fecha');
const filaHorario=document.getElementById('filaHorario');
const horaInicioInput=document.getElementById('hora_inicio');
const horaFinInput=document.getElementById('hora_fin');
let reservas=[];
let seleccionando=false;
let inicioSeleccion='';
function hhmmToParts(v){
    if (v === undefined || v === null) return null;
    const s = String(v).trim();
    if (s.includes(':')){
        const [hh,mm] = s.split(':').map(Number);
        if (Number.isNaN(hh) || Number.isNaN(mm)) return null;
        return {h: hh, m: mm};
    }
    const n = Number(s);
    if (Number.isNaN(n)) return null;
    if (n >= 100){
        const hh = Math.floor(n / 100);
        const mm = n % 100;
        if (mm >= 60) return null;
        return {h: hh, m: mm};
    }
    if (n >= 0 && n <= 24) return {h: Math.floor(n), m: 0};
    return null;
}
function partsToLabel(p){ return ('0'+p.h).slice(-2) + ':' + ('0'+p.m).slice(-2); }
function addMinutes(p, minutes){
    let total = p.h * 60 + p.m + minutes;
    const h = Math.floor(total / 60);
    const m = total % 60;
    return {h, m};
}
function minutesOf(p){ return p.h*60 + p.m; }
function isBefore(a, b){ return (a.h < b.h) || (a.h === b.h && a.m < b.m); }
function isBeforeOrEqual(a, b){ return minutesOf(a) <= minutesOf(b); }
const STEP_MIN = 30;
const MIN_DURATION = 60;
function generarFilaDynamicExact(startParts, endParts){
    filaHorario.innerHTML = '';
    if (!isBefore(startParts, endParts)) return;
    let cursor = {h: startParts.h, m: startParts.m};
    while (true){
        if (minutesOf(cursor) >= 24*60) break;
        const etiqueta = partsToLabel(cursor);
        const celda=document.createElement('div');
        celda.className='celda';
        celda.dataset.hora=etiqueta;
        celda.textContent=etiqueta;
        if(reservas.some(r => r.inicio <= celda.dataset.hora && r.fin > celda.dataset.hora)){
            celda.classList.add('ocupado');
        }
        celda.addEventListener('mousedown', (ev) => {
            if(celda.classList.contains('ocupado')) return;
            seleccionando=true;
            inicioSeleccion=celda.dataset.hora;
            document.querySelectorAll('.celda.seleccionado').forEach(c=>c.classList.remove('seleccionado'));
            celda.classList.add('seleccionado');
            horaInicioInput.value=inicioSeleccion;
            horaFinInput.value=inicioSeleccion;
            ev.preventDefault();
        });
        celda.addEventListener('mouseenter', () => {
            if(!seleccionando) return;
            if(celda.classList.contains('ocupado')) return;
            document.querySelectorAll('.celda.seleccionado').forEach(c=>c.classList.remove('seleccionado'));
            let bloques = Array.from(document.querySelectorAll('.celda'));
            let adding = false;
            for(let b of bloques){
                if(b.dataset.hora === inicioSeleccion) adding = true;
                if(adding) b.classList.add('seleccionado');
                if(b.dataset.hora === celda.dataset.hora) break;
            }
            let [h1,m1] = celda.dataset.hora.split(':').map(Number);
            m1 += STEP_MIN;
            if(m1 >= 60){ m1 -= 60; h1 += 1; }
            if (h1 >= 24){ h1 = 24; m1 = 0; }
            horaInicioInput.value = inicioSeleccion;
            horaFinInput.value = ('0'+h1).slice(-2) + ':' + ('0'+m1).slice(-2);
        });
        filaHorario.appendChild(celda);
        if (!isBeforeOrEqual(cursor, endParts)) break;
        cursor = addMinutes(cursor, STEP_MIN);
        if (!isBeforeOrEqual(cursor, endParts)) {
            break;
        }
    }
}
function generarCalendarioConSalon(salonOption){
    const rawHi = salonOption?.dataset?.horinicio ?? null;
    const rawHf = salonOption?.dataset?.horfin ?? null;
    let startParts = hhmmToParts(rawHi) || {h:8,m:0};
    let endParts = hhmmToParts(rawHf) || {h:20,m:0};
    if (startParts.h < 0) startParts.h = 0;
    if (startParts.h > 23) startParts.h = 23;
    if (endParts.h < 1) endParts.h = Math.max(startParts.h+1, 1);
    if (endParts.h > 24) endParts.h = 24;
    if (endParts.h === 24) endParts.m = 0;
    const startMinutes = Math.floor(minutesOf(startParts) / STEP_MIN) * STEP_MIN;
    startParts = {h: Math.floor(startMinutes/60), m: startMinutes%60};
    generarFilaDynamicExact(startParts, endParts);
}
function cargarReservas(){
    reservas=[];
    horaInicioInput.value = '';
    horaFinInput.value = '';
    document.querySelectorAll('.celda.seleccionado').forEach(c=>c.classList.remove('seleccionado'));
    const salon = salonSelect.value;
    const fecha = fechaInput.value;
    if(!salon || !fecha){
        filaHorario.innerHTML = '';
        return;
    }
    const salonObj = Array.from(salonSelect.options).find(o=>o.value===salon);
    const estado = (salonObj?.dataset?.estado ?? 'disponible').toLowerCase();
    generarCalendarioConSalon(salonObj);
    if(estado === 'mantenimiento'){
        const bloques = Array.from(document.querySelectorAll('.celda'));
        bloques.forEach(b => b.classList.add('ocupado'));
        return;
    }
    fetch(`get_horarios_ocupados.php?salonid=${encodeURIComponent(salon)}&fecha=${encodeURIComponent(fecha)}`)
        .then(r => { if(!r.ok) throw new Error('Error'); return r.json(); })
        .then(data => {
            reservas = Array.isArray(data) ? data : [];
            generarCalendarioConSalon(salonObj);
        })
        .catch(err => console.error(err));
}
document.addEventListener('mouseup', ()=> {
    if (!seleccionando) return;
    seleccionando = false;
    if (!horaInicioInput.value || !horaFinInput.value) return;
    function toMinutes(hhmm){ const [h,m] = hhmm.split(':').map(Number); return h*60 + m; }
    const sMin = toMinutes(horaInicioInput.value);
    const fMin = toMinutes(horaFinInput.value);
    const duration = fMin - sMin;
    if (duration < MIN_DURATION){
        document.querySelectorAll('.celda.seleccionado').forEach(c=>c.classList.remove('seleccionado'));
        horaInicioInput.value = '';
        horaFinInput.value = '';
        alert('La reserva mínima es de ' + (MIN_DURATION/60) + ' hora(s). Selecciona al menos ese intervalo.');
        return;
    }
    if ((duration % STEP_MIN) !== 0){
        const needed = Math.ceil(duration / STEP_MIN) * STEP_MIN;
        const newFin = addMinutes({h: Math.floor(sMin/60), m: sMin%60}, needed);
        if (minutesOf(newFin) > 24*60) {
            alert('La selección excede las 24:00, ajusta el rango.');
            document.querySelectorAll('.celda.seleccionado').forEach(c=>c.classList.remove('seleccionado'));
            horaInicioInput.value = '';
            horaFinInput.value = '';
            return;
        }
        horaFinInput.value = ('0'+newFin.h).slice(-2) + ':' + ('0'+newFin.m).slice(-2);
    }
});
salonSelect.addEventListener('change', cargarReservas);
fechaInput.addEventListener('change', cargarReservas);
if(salonSelect.value && fechaInput.value) cargarReservas();
</script>
</body>
</html>
