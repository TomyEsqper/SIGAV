<?php
require_once '../config/auth.php';
require_once '../config/database.php';

verificarSesion(['admin']);

$db = getDB();

// Crear tablas si no existen
try {
    $db->execute("CREATE TABLE IF NOT EXISTS evasion_inspecciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero_informe VARCHAR(32) NOT NULL,
        vehiculo_id INT NOT NULL,
        conductor_id INT NULL,
        ruta VARCHAR(120) NULL,
        dias_revisados VARCHAR(120) NULL,
        fecha_revision DATE NOT NULL,
        fecha_reporte DATETIME NOT NULL,
        usuario_id INT NOT NULL,
        total_pasajeros INT DEFAULT 0,
        observaciones TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->execute("CREATE TABLE IF NOT EXISTS evasion_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inspeccion_id INT NOT NULL,
        grabacion VARCHAR(64) NOT NULL,
        hora TIME NULL,
        pasajeros INT NOT NULL,
        archivo_url VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (inspeccion_id) REFERENCES evasion_inspecciones(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) { /* noop */ }

function siguiente_consecutivo($db) {
    try {
        $last = $db->fetch("SELECT numero_informe FROM evasion_inspecciones WHERE numero_informe LIKE 'LO-%' ORDER BY id DESC LIMIT 1");
        if (!$last || empty($last['numero_informe'])) { return 'LO-0318'; }
        if (preg_match('/LO-(\\d+)/', $last['numero_informe'], $m)) {
            $n = intval($m[1]);
            return 'LO-' . str_pad($n + 1, 4, '0', STR_PAD_LEFT);
        }
    } catch (Exception $e) {}
    return 'LO-0318';
}

// Añadido: utilidades para convertir clips a un formato estándar reproducible en navegador
function sigav_quote($arg) { return '"' . str_replace('"', '\\"', $arg) . '"'; }
function sigav_find_ffmpeg() {
    $ff = null; $out = [];
    // Primero buscar un binario local dentro del proyecto (sigavv/bin/ffmpeg[.exe])
    $localBin = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . (stripos(PHP_OS, 'WIN') === 0 ? 'ffmpeg.exe' : 'ffmpeg');
    if (file_exists($localBin)) { return $localBin; }
    // Luego intentar PATH del sistema
    if (stripos(PHP_OS, 'WIN') === 0) { @exec('where ffmpeg', $out); if (!empty($out)) { $ff = trim($out[0]); } }
    else { $ff = trim(@shell_exec('which ffmpeg')); }
    // Candidatos usuales en Windows/Linux/macOS
    $cands = [
        'C:\\ffmpeg\\bin\\ffmpeg.exe',
        'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
        'C:\\Program Files\\FFmpeg\\bin\\ffmpeg.exe',
        '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/homebrew/bin/ffmpeg'
    ];
    if (!$ff || !file_exists($ff)) { foreach ($cands as $c) { if (file_exists($c)) { $ff = $c; break; } } }
    return ($ff && file_exists($ff)) ? $ff : null;
}
function sigav_convert_to_mp4($inputPath, $outDir) {
    $ff = sigav_find_ffmpeg();
    if (!$ff || !file_exists($inputPath)) { return null; }
    @set_time_limit(120);
    $outfile = $outDir . DIRECTORY_SEPARATOR . pathinfo($inputPath, PATHINFO_FILENAME) . '_std.mp4';
    $cmd = sigav_quote($ff) . ' -y -i ' . sigav_quote($inputPath) . ' -loglevel error -c:v libx264 -preset veryfast -crf 23 -pix_fmt yuv420p -c:a aac -b:a 128k -movflags +faststart ' . sigav_quote($outfile);
    @exec($cmd, $o, $code);
    if (file_exists($outfile) && @filesize($outfile) > 0) { return $outfile; }
    return null;
}

$mensaje = '';
$tipo_mensaje = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Campos de encabezado
    $vehiculo_id = intval($_POST['vehiculo_id'] ?? 0);
    $conductor_id = intval($_POST['conductor_id'] ?? 0);
    $ruta = trim($_POST['ruta'] ?? '');
    $fecha_revision = trim($_POST['fecha_revision'] ?? '');
    $dias_revisados = trim($_POST['dias_revisados'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $clips_grabacion = $_POST['clips_grabacion'] ?? [];
    $clips_hora = $_POST['clips_hora'] ?? [];
    $clips_pasajeros = $_POST['clips_pasajeros'] ?? [];
    $clips_urls = $_POST['clips_archivo_url'] ?? [];

    if ($vehiculo_id <= 0 || $fecha_revision === '' || empty($clips_pasajeros)) {
        $mensaje = 'Verifique vehículo, fecha y al menos un clip.';
        $tipo_mensaje = 'danger';
    } else {
        try {
            // Validar vehículo
            $v = $db->fetch("SELECT id FROM vehiculos WHERE id = ?", [$vehiculo_id]);
            if (!$v) { throw new Exception('Vehículo inválido'); }

            // Insertar inspección
            $numero_informe = siguiente_consecutivo($db);
            $total = 0;
            foreach ($clips_pasajeros as $p) { $total += intval($p); }

            $inspeccion_id = $db->insert(
                "INSERT INTO evasion_inspecciones (numero_informe, vehiculo_id, conductor_id, ruta, dias_revisados, fecha_revision, fecha_reporte, usuario_id, total_pasajeros, observaciones)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)",
                [
                    $numero_informe,
                    $vehiculo_id,
                    $conductor_id ?: null,
                    $ruta ?: null,
                    $dias_revisados ?: null,
                    $fecha_revision,
                    $_SESSION['user_id'],
                    $total,
                    $observaciones ?: null
                ]
            );

            // Manejo de uploads
            $baseUpload = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
            if ($baseUpload && is_dir($baseUpload)) {
                $dir = $baseUpload . DIRECTORY_SEPARATOR . 'evasion';
                if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
            } else {
                $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'evasion';
                if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
            }

            // Insertar detalle por clip
            $files = $_FILES['clips_archivo'] ?? null;
            for ($i = 0; $i < count($clips_pasajeros); $i++) {
                $grab = trim($clips_grabacion[$i] ?? (string)($i+1));
                $horaRaw = trim($clips_hora[$i] ?? '');
                $hora = $horaRaw !== '' ? date('H:i:s', strtotime($horaRaw)) : '';
                $pas = intval($clips_pasajeros[$i] ?? 0);
                $url = null;
                // Preferir rutas pre-subidas (asíncronas)
                if (!empty($clips_urls[$i])) {
                    $url = trim($clips_urls[$i]);
                }
                if ($files && isset($files['name'][$i]) && $files['name'][$i] !== '') {
                    $orig = basename($files['name'][$i]);
                    $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
                    $target = $dir . DIRECTORY_SEPARATOR . date('Ymd_His') . '_' . $safe;
                    if (@move_uploaded_file($files['tmp_name'][$i], $target)) {
                        // Convertir automáticamente a MP4/H.264 + AAC para compatibilidad con navegadores
                        if (!$url) {
                            $converted = sigav_convert_to_mp4($target, $dir);
                            if ($converted) {
                                $url = 'uploads/evasion/' . basename($converted);
                            } else {
                                // Si no se pudo convertir (FFmpeg no disponible), guardar el original
                                $url = 'uploads/evasion/' . basename($target);
                            }
                        }
                    }
                }
                $db->execute(
                    "INSERT INTO evasion_detalle (inspeccion_id, grabacion, hora, pasajeros, archivo_url) VALUES (?, ?, ?, ?, ?)",
                    [$inspeccion_id, $grab, $hora ?: null, $pas, $url]
                );
            }

            // Redirigir a detalle
            header('Location: evasion_detalle.php?id=' . intval($inspeccion_id));
            exit;
        } catch (Exception $e) {
            $mensaje = 'Error al guardar: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva revisión de Evasión - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; position: relative; }
        body::before { content: ""; position: fixed; inset: 0; background: rgba(11, 30, 63, 0.55); pointer-events: none; z-index: 0; }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .sidebar { min-height: 100vh; position: sticky; top: 0; overflow: hidden; background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%); color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.85); padding: 12px 20px; border-radius: 8px; margin: 2px 10px; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: rgba(255,255,255,0.12); color: #fff; }
        .sidebar-logo { width: 52px; height: 52px; object-fit: contain; }
        .card-header { background: rgba(11,30,63,0.35); color: #fff; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 d-md-block sidebar py-4">
            <div class="text-center mb-3">
                <img src="../logo.png" alt="SIGAV" class="sidebar-logo mb-2">
                <h4 class="text-white">SIGAV</h4>
                <small class="text-light"><a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-light text-decoration-none">BLACKCROWSOFT.COM</a></small>
            </div>
            <?php $current = basename($_SERVER['PHP_SELF']); ?>
            <nav class="nav flex-column">
                <a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a class="nav-link <?= $current === 'vehiculos.php' ? 'active' : '' ?>" href="vehiculos.php"><i class="fas fa-truck"></i> Vehículos</a>
                <a class="nav-link <?= $current === 'conductores.php' ? 'active' : '' ?>" href="conductores.php"><i class="fas fa-users"></i> Conductores</a>
                <a class="nav-link <?= $current === 'documentos.php' ? 'active' : '' ?>" href="documentos.php"><i class="fas fa-file-alt"></i> Documentos</a>
                <a class="nav-link <?= $current === 'alistamientos.php' ? 'active' : '' ?>" href="alistamientos.php"><i class="fas fa-clipboard-check"></i> Alistamientos</a>
                <a class="nav-link <?= $current === 'reportes.php' ? 'active' : '' ?>" href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a>
                <a class="nav-link <?= $current === 'camaras.php' ? 'active' : '' ?>" href="camaras.php"><i class="fas fa-video"></i> Cámaras</a>
                <a class="nav-link <?= $current === 'evasion.php' ? 'active' : '' ?>" href="evasion.php"><i class="fas fa-user-secret"></i> Evasión</a>
            </nav>
        </aside>

        <main class="col-md-9 col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                <h3 class="text-white"><i class="fas fa-plus"></i> Nueva revisión de evasión</h3>
                <a href="evasion.php" class="btn btn-outline-light"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>

            <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="card">
                <div class="card-header"><h5 class="mb-0">Encabezado</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vehículo (placa o NI)</label>
                            <div class="input-group position-relative">
                                <span class="input-group-text">CO-</span>
                                <input type="text" id="vehiculoNumero" class="form-control" placeholder="Escribe solo números" inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="btnBuscarVehiculo"><i class="fas fa-search"></i></button>
                                <div id="vehiculoSuggestions" class="list-group position-absolute w-100" style="top:100%; left:0; z-index:1000;"></div>
                            </div>
                            <div class="form-text" id="infoVehiculo">Sin selección</div>
                            <input type="hidden" name="vehiculo_id" id="vehiculo_id" value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Conductor (nombre o cédula)</label>
                            <div class="input-group position-relative">
                                <input type="text" id="buscarConductor" class="form-control" placeholder="Nombre o cédula" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="btnBuscarConductor"><i class="fas fa-search"></i></button>
                                <div id="conductorSuggestions" class="list-group position-absolute w-100" style="top:100%; left:0; z-index:1000;"></div>
                            </div>
                            <div class="form-text" id="infoConductor">Opcional</div>
                            <input type="hidden" name="conductor_id" id="conductor_id" value="">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ruta</label>
                            <input type="text" name="ruta" class="form-control" placeholder="Ruta o servicio">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de revisión</label>
                            <input type="date" name="fecha_revision" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Días revisados</label>
                            <input type="text" name="dias_revisados" class="form-control" placeholder="Ej: 20-25 de Octubre">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Descripción del caso, si aplica"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card-header"><h5 class="mb-0">Clips revisados</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted">Agregue cada clip con su hora y cantidad de pasajeros</div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarClip"><i class="fas fa-plus"></i> Agregar clip</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped" id="clipsTable">
                            <thead>
                                <tr>
                                    <th>Grabación</th>
                                    <th>Hora</th>
                                    <th>No. pasajeros</th>
                                    <th>Adjunto</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end">
                                        <span id="totalPasajeros" class="badge bg-info">Total pasajeros: 0</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Finalizar y guardar</button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
let clipIndex = 0;
function actualizarTotal() {
    let total = 0;
    document.querySelectorAll('input[name="clips_pasajeros[]"]').forEach(inp => { total += parseInt(inp.value || 0); });
    document.getElementById('totalPasajeros').textContent = 'Total pasajeros: ' + total;
}
function agregarFilaClip() {
    const tbody = document.querySelector('#clipsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="clips_grabacion[]" class="form-control" placeholder="Clip #" value="${clipIndex+1}"></td>
        <td><input type="time" name="clips_hora[]" class="form-control" step="1"></td>
        <td><input type="number" name="clips_pasajeros[]" class="form-control" min="0" value="0"></td>
        <td>
            <input type="file" name="clips_archivo[]" class="form-control" accept="video/*">
            <input type="hidden" name="clips_archivo_url[]" value="">
            <div class="progress mt-2" style="height: 6px; display:none;">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <small class="text-muted upload-status" style="display:none;">Preparando subida…</small>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btnRemove"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    clipIndex++;
    actualizarTotal();
    tr.querySelector('input[name="clips_pasajeros[]"]').addEventListener('input', actualizarTotal);
    tr.querySelector('.btnRemove').addEventListener('click', () => { tr.remove(); actualizarTotal(); });
    // Subida asíncrona al seleccionar archivo
    const fileInput = tr.querySelector('input[type="file"][name="clips_archivo[]"]');
    fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files[0]) {
            uploadClipFile(fileInput.files[0], tr);
        }
    });
}

document.getElementById('btnAgregarClip').addEventListener('click', agregarFilaClip);
// Inicial para que haya una fila
agregarFilaClip();

// Gestor de subidas asíncronas por chunks
let uploadsActivas = 0;
const btnSubmit = document.querySelector('button[type="submit"]');
function setSubmitEnabled() {
    if (btnSubmit) { btnSubmit.disabled = uploadsActivas > 0; }
}

async function uploadClipFile(file, tr) {
    const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB por chunk
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
    const uploadId = `${Date.now()}_${Math.random().toString(36).slice(2)}`;
    const progress = tr.querySelector('.progress');
    const bar = tr.querySelector('.progress-bar');
    const status = tr.querySelector('.upload-status');
    const hiddenUrl = tr.querySelector('input[name="clips_archivo_url[]"]');

    uploadsActivas++;
    setSubmitEnabled();
    progress.style.display = 'block';
    status.style.display = 'inline';
    status.textContent = 'Subiendo clip…';

    for (let i = 0; i < totalChunks; i++) {
        const start = i * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, file.size);
        const chunk = file.slice(start, end);

        const fd = new FormData();
        fd.append('uploadId', uploadId);
        fd.append('fileName', file.name);
        fd.append('chunkIndex', i);
        fd.append('totalChunks', totalChunks);
        fd.append('chunk', chunk, `${file.name}.part${i}`);

        const resp = await fetch('evasion_upload.php', { method: 'POST', body: fd });
        if (!resp.ok) {
            status.textContent = 'Error al subir';
            uploadsActivas--;
            setSubmitEnabled();
            return;
        }
        const j = await resp.json().catch(() => ({}));
        const pct = Math.round(((i + 1) / totalChunks) * 100);
        bar.style.width = pct + '%';
        if (i + 1 === totalChunks && j && j.finalUrl) {
            hiddenUrl.value = j.finalUrl;
        }
    }

    status.textContent = 'Subida completa';

    uploadsActivas--;
    setSubmitEnabled();
}

async function buscarVehiculo() {
    const num = document.getElementById('vehiculoNumero').value.trim();
    const b = num ? ('CO-' + num) : '';
    if (!b) return;
    const r = await fetch('buscar_vehiculo.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'busqueda=' + encodeURIComponent(b) });
    const j = await r.json();
    if (j.success) {
        document.getElementById('vehiculo_id').value = j.data.id;
        document.getElementById('infoVehiculo').textContent = `${j.data.placa} • ${j.data.numero_interno}`;
    } else {
        document.getElementById('infoVehiculo').textContent = 'Vehículo no encontrado';
        document.getElementById('vehiculo_id').value = '';
    }
}

// Render sugerencias vehículos
function renderVehiculoSuggestions(items) {
    const box = document.getElementById('vehiculoSuggestions');
    box.innerHTML = '';
    if (!items || !items.length) { box.style.display = 'none'; return; }
    items.forEach(it => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        a.textContent = `${it.numero_interno} • ${it.placa}`;
        a.addEventListener('click', (ev) => {
            ev.preventDefault();
            document.getElementById('vehiculo_id').value = it.id;
            document.getElementById('infoVehiculo').textContent = `${it.placa} • ${it.numero_interno}`;
            // Sincroniza el input con la selección para confirmar visualmente
            document.getElementById('vehiculoNumero').value = String(it.numero_interno).replace(/\D+/g, '');
            document.getElementById('vehiculoSuggestions').style.display = 'none';
        });
        box.appendChild(a);
    });
    box.style.display = 'block';
}

// Debounce helper
function debounce(fn, delay) {
    let t; return function(...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), delay); };
}

// Input live para vehículos (prefijo CO- + números)
const onVehiculoInput = debounce(async () => {
    const num = document.getElementById('vehiculoNumero').value.replace(/\D+/g, '').trim();
    document.getElementById('vehiculoNumero').value = num; // fuerza solo dígitos
    if (!num) { renderVehiculoSuggestions([]); return; }
    const q = 'busqueda=' + encodeURIComponent('CO-' + num) + '&sugerencias=1&limit=5';
    const r = await fetch('buscar_vehiculo.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: q });
    const j = await r.json();
    if (j.items) { renderVehiculoSuggestions(j.items); }
}, 200);

document.getElementById('vehiculoNumero').addEventListener('input', onVehiculoInput);
document.addEventListener('click', (ev) => {
    if (!document.getElementById('vehiculoSuggestions').contains(ev.target) && ev.target.id !== 'vehiculoNumero') {
        document.getElementById('vehiculoSuggestions').style.display = 'none';
    }
});

// Sugerencias conductor
function renderConductorSuggestions(items) {
    const box = document.getElementById('conductorSuggestions');
    box.innerHTML = '';
    if (!items || !items.length) { box.style.display = 'none'; return; }
    items.forEach(it => {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action';
        a.textContent = `${it.nombre} • ${it.cedula}`;
        a.addEventListener('click', (ev) => {
            ev.preventDefault();
            document.getElementById('conductor_id').value = it.id;
            document.getElementById('infoConductor').textContent = `${it.nombre} • ${it.cedula}`;
            // Sincroniza el input para facilitar confirmación visual
            document.getElementById('buscarConductor').value = it.nombre;
            document.getElementById('conductorSuggestions').style.display = 'none';
        });
        box.appendChild(a);
    });
    box.style.display = 'block';
}

const onConductorInput = debounce(async () => {
    const b = document.getElementById('buscarConductor').value.trim();
    if (!b) { renderConductorSuggestions([]); return; }
    const q = 'busqueda=' + encodeURIComponent(b) + '&sugerencias=1&limit=5';
    const r = await fetch('buscar_conductor.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: q });
    const j = await r.json();
    if (j.items) { renderConductorSuggestions(j.items); }
}, 200);

document.getElementById('buscarConductor').addEventListener('input', onConductorInput);
document.addEventListener('click', (ev) => {
    if (!document.getElementById('conductorSuggestions').contains(ev.target) && ev.target.id !== 'buscarConductor') {
        document.getElementById('conductorSuggestions').style.display = 'none';
    }
});

// Botones buscar siguen funcionando
document.getElementById('btnBuscarVehiculo').addEventListener('click', buscarVehiculo);

// Evitar re-subida en el submit: deshabilitar inputs de archivo y bloquear si hay subidas activas
const form = document.querySelector('form.card');
form.addEventListener('submit', (ev) => {
    if (uploadsActivas > 0) {
        ev.preventDefault();
        alert('Aún hay clips subiendo. Espera a que terminen.');
        return;
    }
    document.querySelectorAll('input[type="file"][name="clips_archivo[]"]').forEach(inp => { inp.disabled = true; });
});

document.getElementById('btnBuscarConductor').addEventListener('click', async () => {
    const b = document.getElementById('buscarConductor').value.trim();
    if (!b) return;
    const r = await fetch('buscar_conductor.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'busqueda=' + encodeURIComponent(b) });
    const j = await r.json();
    if (j.success) {
        document.getElementById('conductor_id').value = j.data.id;
        document.getElementById('infoConductor').textContent = `${j.data.nombre} • ${j.data.cedula}`;
    } else {
        document.getElementById('infoConductor').textContent = 'Conductor no encontrado';
        document.getElementById('conductor_id').value = '';
    }
});
</script>
</body>
</html>