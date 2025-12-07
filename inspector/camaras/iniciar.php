<?php
/**
 * Módulo Inspector de Cámaras - Inicio (Foto obligatoria)
 */
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

verificarSesion(['inspector_camaras', 'admin']);

$vehiculo_id = intval($_GET['vehiculo'] ?? 0);
if (!$vehiculo_id) { header('Location: index.php'); exit; }

// Obtener datos básicos del vehículo
$vehiculo = null;
try {
    $db = getDB();
    $vehiculo = $db->fetch("SELECT id, numero_interno, placa FROM vehiculos WHERE id = ?", [$vehiculo_id]);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inicio Inspección Cámaras - Foto Obligatoria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        video, canvas { max-width: 100%; border: 1px solid #ddd; border-radius: 6px; }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <h1 class="h4 mb-3">Inicio de Inspección - Foto Obligatoria</h1>
        <p class="mb-3">Vehículo: <strong><?= htmlspecialchars($vehiculo['numero_interno'] ?? ('ID ' . $vehiculo_id)) ?></strong> - Placa: <strong><?= htmlspecialchars($vehiculo['placa'] ?? '-') ?></strong></p>
        <div class="alert alert-warning">Debe capturar una foto en este momento de la caja del MDVR abierta y/o pantalla conectada. No se permite cargar desde la galería.</div>
        <div class="row g-3">
            <div class="col-md-6">
                <video id="preview" autoplay playsinline></video>
            </div>
            <div class="col-md-6">
                <canvas id="snapshot" width="1280" height="720" style="display:none"></canvas>
                <img id="snapshotImg" class="img-fluid" style="display:none" alt="Captura" />
            </div>
        </div>
        <div class="mt-3">
            <button id="btnCapturar" class="btn btn-primary">Capturar</button>
            <button id="btnReintentar" class="btn btn-secondary" style="display:none">Reintentar</button>
            <button id="btnContinuar" class="btn btn-success" style="display:none">Guardar y Continuar</button>
        </div>
        <div id="status" class="mt-3"></div>
        <a href="index.php" class="btn btn-link mt-3">Volver</a>
    </div>

    <script>
        const vehiculoId = <?= json_encode($vehiculo_id) ?>;
        const video = document.getElementById('preview');
        const canvas = document.getElementById('snapshot');
        const img = document.getElementById('snapshotImg');
        const ctx = canvas.getContext('2d');
        const btnCapturar = document.getElementById('btnCapturar');
        const btnReintentar = document.getElementById('btnReintentar');
        const btnContinuar = document.getElementById('btnContinuar');
        const status = document.getElementById('status');

        async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                video.srcObject = stream;
            } catch (e) {
                status.className = 'alert alert-danger';
                status.textContent = 'No fue posible acceder a la cámara. Verifique permisos.';
            }
        }
        initCamera();

        btnCapturar.addEventListener('click', () => {
            try {
                const w = video.videoWidth || 1280;
                const h = video.videoHeight || 720;
                canvas.width = w; canvas.height = h;
                ctx.drawImage(video, 0, 0, w, h);
                const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                img.src = dataUrl;
                img.style.display = 'block';
                canvas.style.display = 'none';
                btnContinuar.style.display = 'inline-block';
                btnReintentar.style.display = 'inline-block';
                status.className = 'alert alert-info';
                status.textContent = 'Captura lista para guardar.';
            } catch (e) {
                status.className = 'alert alert-danger';
                status.textContent = 'Error capturando la imagen.';
            }
        });

        btnReintentar.addEventListener('click', () => {
            img.style.display = 'none';
            img.src = '';
            status.textContent = '';
            btnContinuar.style.display = 'none';
            btnReintentar.style.display = 'none';
        });

        btnContinuar.addEventListener('click', async () => {
            try {
                status.className = 'alert alert-warning';
                status.textContent = 'Guardando captura...';
                const res = await fetch('api/crear_inspeccion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ vehiculo_id: vehiculoId, foto_base64: img.src })
                });
                const json = await res.json();
                if (json && json.ok) {
                    window.location.href = 'checklist.php?inspeccion=' + json.inspeccion_id;
                } else {
                    status.className = 'alert alert-danger';
                    status.textContent = (json && json.error) ? json.error : 'Error guardando la captura.';
                }
            } catch (e) {
                status.className = 'alert alert-danger';
                status.textContent = 'Error de red guardando la captura.';
            }
        });
    </script>
</body>
</html>