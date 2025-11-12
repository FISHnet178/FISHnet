<?php
require 'config.php';
require 'flash_set.php';

$habId = $_SESSION['HABID'] ?? null;
if (!$habId) { http_response_code(403); exit("Acceso denegado."); }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        set_flash('Token CSRF inválido.','error');
        header('Location: reservar_salon.php');
        exit;
    }

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
            set_flash('Reserva creada correctamente.','success');
            header('Location: reservar_salon.php');
            exit;
        }
    }

    if($errors){
        set_flash(implode('<br>', $errors),'error');
        header('Location: reservar_salon.php');
        exit;
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
<link rel="stylesheet" href="estilos/dashboard.css">
</head>
<body>
<div class="contenedor">

  <div class="datos-form">
      <h2>Reservar Salón</h2>
      <?= get_flash() ?>
      
      <?php if($errors): ?>
        <div class="errors">
          <ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
        </div>
      <?php endif; ?>
      
      <?php if($success): ?>
        <div class="success">Reserva creada correctamente.</div>
      <?php endif; ?>
      
      <form id="datos-form" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        
        <label>
          <select id="salonid" name="salonid" required>
            <option value="">-- Seleccionar salón --</option>
            <?php foreach($salones as $s): ?>
              <option value="<?= $s['SalonID'] ?>"
                      data-estado="<?= htmlspecialchars($s['Estado']) ?>"
                      data-horinicio="<?= (int)$s['HorInicio'] ?>"
                      data-horfin="<?= (int)$s['HorFin'] ?>">
                Salón #<?= $s['SalonID'] ?> — <?= htmlspecialchars($s['TerrenoNombre'] ?? 'Terreno') ?>
                <?php if(strtolower($s['Estado'])!=='disponible'){ echo " — ".htmlspecialchars($s['Estado']); } ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>Fecha:
          <input type="date" id="fecha" name="fecha" required 
                 value="<?= htmlspecialchars($_POST['fecha'] ?? '') ?>">
        </label>

        <label>
          <textarea name="comentario" rows="3" placeholder="Comentario / razón (opcional):"><?= htmlspecialchars($_POST['comentario'] ?? '') ?></textarea>
        </label>
        <input type="hidden" id="hora_inicio" name="hora_inicio">
        <input type="hidden" id="hora_fin" name="hora_fin">
        <button type="submit">Reservar</button>
      </form>
      <div class="action-buttons" style="margin-top:12px;">
        <button onclick="window.location.href='Inicio.php'">← Volver al inicio</button>
      </div>
  </div>

  <div class="decoracion">
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

<div id="reserva-tooltip" aria-hidden="true"></div>

<script>
const salonSelect = document.getElementById('salonid');
const fechaInput = document.getElementById('fecha');
const filaHorario = document.getElementById('filaHorario');
const horaInicioInput = document.getElementById('hora_inicio');
const horaFinInput = document.getElementById('hora_fin');

let reservas = [];
let seleccionando = false;
let inicioSeleccion = '';

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
    const m = ((total % 60) + 60) % 60;
    return {h, m};
}
function minutesOf(p){ return p.h*60 + p.m; }
function isBefore(a, b){ return (a.h < b.h) || (a.h === b.h && a.m < b.m); }
function isBeforeOrEqual(a, b){ return minutesOf(a) <= minutesOf(b); }
const STEP_MIN = 30;
const MIN_DURATION = 60;
function toMinutesStr(hhmm){ const [h,m]=hhmm.split(':').map(Number); return h*60 + m; }

function generarFilaDynamicExact(startParts, endParts){
    filaHorario.innerHTML = '';
    if (!isBefore(startParts, endParts)) return;
    let cursor = {h: startParts.h, m: startParts.m};
    while (true){
        if (minutesOf(cursor) >= 24*60) break;
        const etiqueta = partsToLabel(cursor);
        const celda = document.createElement('div');
        celda.className = 'celda';
        celda.dataset.hora = etiqueta;
        celda.textContent = etiqueta;

        const cursorMin = minutesOf(cursor);
        const covering = reservas.find(r => r.inicioMin <= cursorMin && r.finMin > cursorMin);
        if (covering){
            celda.classList.add('ocupado');
            celda.dataset.resNombre = covering.nombre;
            if (covering.comentario) celda.dataset.resComent = covering.comentario;

            celda.addEventListener('mouseenter', (ev) => {
                showReservaTooltip(ev, celda.dataset.resNombre, celda.dataset.resComent);
            });
            celda.addEventListener('mousemove', (ev) => {
                moveReservaTooltip(ev);
            });
            celda.addEventListener('mouseleave', () => {
                hideReservaTooltip();
            });
        }

        celda.addEventListener('mousedown', (ev) => {
            if (celda.classList.contains('ocupado')) return;
            seleccionando = true;
            inicioSeleccion = celda.dataset.hora;
            document.querySelectorAll('.celda.seleccionado').forEach(c=>c.classList.remove('seleccionado'));
            celda.classList.add('seleccionado');
            horaInicioInput.value = inicioSeleccion;
            horaFinInput.value = inicioSeleccion;
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

        cursor = addMinutes(cursor, STEP_MIN);
        if (!isBeforeOrEqual(cursor, endParts)) break;
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
    reservas = [];
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

    if(estado === 'mantenimiento'){
        generarCalendarioConSalon(salonObj);
        const bloques = Array.from(document.querySelectorAll('.celda'));
        bloques.forEach(b => b.classList.add('ocupado'));
        return;
    }

    fetch(`get_horarios_ocupados.php?salonid=${encodeURIComponent(salon)}&fecha=${encodeURIComponent(fecha)}`)
        .then(r => { if(!r.ok) throw new Error('Error'); return r.json(); })
        .then(data => {
            reservas = Array.isArray(data) ? data.map(d => {
                return {
                    inicio: d.inicio,
                    fin: d.fin,
                    inicioMin: toMinutesStr(d.inicio),
                    finMin: toMinutesStr(d.fin),
                    nombre: d.nombre ?? 'Usuario',
                    comentario: d.comentario ?? null
                };
            }) : [];
            generarCalendarioConSalon(salonObj);
        })
        .catch(err => {
            console.error(err);
            generarCalendarioConSalon(salonObj);
        });
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
if (salonSelect.value && fechaInput.value) cargarReservas();

let reservaTooltip = document.getElementById('reserva-tooltip');

function escapeHtml(s){
    if (s === undefined || s === null) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showReservaTooltip(ev, nombre, comentario){
    if (!reservaTooltip) reservaTooltip = document.getElementById('reserva-tooltip');
    const title = '<strong>' + escapeHtml(nombre) + '</strong>';
    const coment = comentario ? ('<div style="font-size:13px;line-height:1.2;">' + escapeHtml(comentario) + '</div>') : '<div style="opacity:0.8;font-style:italic;">Sin comentario</div>';
    reservaTooltip.innerHTML = title + coment;
    reservaTooltip.style.display = 'block';
    moveReservaTooltip(ev);
}

function moveReservaTooltip(ev){
    if (!reservaTooltip || reservaTooltip.style.display === 'none') return;
    const pad = 12;
    const tw = reservaTooltip.offsetWidth;
    const th = reservaTooltip.offsetHeight;
    let x = ev.clientX + pad;
    let y = ev.clientY + pad;
    if (x + tw > window.innerWidth) x = ev.clientX - tw - pad;
    if (y + th > window.innerHeight) y = ev.clientY - th - pad;
    reservaTooltip.style.left = x + 'px';
    reservaTooltip.style.top = y + 'px';
}

function hideReservaTooltip(){
    if (!reservaTooltip) return;
    reservaTooltip.style.display = 'none';
}
</script>
</body>
</html>