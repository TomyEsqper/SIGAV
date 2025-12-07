<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
verificarSesion(['inspector_camaras', 'admin']);

$inspeccion_id = intval($_GET['inspeccion'] ?? 0);
if (!$inspeccion_id) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cierre de Inspección</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>video,canvas{max-width:100%;border:1px solid #ddd;border-radius:6px}</style>
</head>
<body class="p-4">
  <div class="container">
    <h1 class="h4 mb-3">Foto final del sistema</h1>
    <p class="text-muted">Inspección #<?= htmlspecialchars($inspeccion_id) ?></p>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="card p-3">
          <h2 class="h6">Cámara</h2>
          <video id="video" autoplay playsinline></video>
          <div class="mt-2 d-flex gap-2">
            <button id="btnCapturar" class="btn btn-primary">Capturar</button>
            <button id="btnEnviar" class="btn btn-success" disabled>Enviar y cerrar</button>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3">
          <h2 class="h6">Vista previa</h2>
          <canvas id="canvas" width="1280" height="720"></canvas>
        </div>
      </div>
    </div>

    <div class="card p-3 mt-3">
      <h2 class="h6">Estado final y citación</h2>
      <div class="mb-2">
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="estado_final" id="estadoVerde" value="verde" checked>
          <label class="form-check-label" for="estadoVerde">Verde (reparado por inspector)</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="estado_final" id="estadoRojo" value="rojo">
          <label class="form-check-label" for="estadoRojo">Rojo (requiere reparación en empresa)</label>
        </div>
      </div>
      <div id="citacionFields" style="display:none">
        <div class="mb-2">
          <label class="form-label" for="motivo">Motivo</label>
          <input type="text" class="form-control" id="motivo" placeholder="Describe la novedad que requiere taller">
        </div>
        <div class="mb-2">
          <label class="form-label" for="lugar">Lugar</label>
          <input type="text" class="form-control" id="lugar" placeholder="Sede o taller">
        </div>
        <div class="mb-2">
          <label class="form-label" for="fecha_programada">Fecha programada</label>
          <input type="datetime-local" class="form-control" id="fecha_programada">
        </div>
        <div class="mb-2">
          <label class="form-label" for="nota">Nota</label>
          <textarea class="form-control" id="nota" rows="2" placeholder="Observaciones adicionales"></textarea>
        </div>
      </div>
      <small class="text-muted">Si seleccionas rojo, se generará una citación automáticamente.</small>
    </div>

    <div id="msg" class="mt-3"></div>
  </div>

  <script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    const btnCapturar = document.getElementById('btnCapturar');
    const btnEnviar = document.getElementById('btnEnviar');
    const msg = document.getElementById('msg');
    let fotoBase64 = '';

    const estadoVerde = document.getElementById('estadoVerde');
    const estadoRojo = document.getElementById('estadoRojo');
    const citacionFields = document.getElementById('citacionFields');
    const motivo = document.getElementById('motivo');
    const lugar = document.getElementById('lugar');
    const fecha_programada = document.getElementById('fecha_programada');
    const nota = document.getElementById('nota');

    function toggleCitacion() {
      citacionFields.style.display = estadoRojo.checked ? 'block' : 'none';
    }
    estadoVerde.addEventListener('change', toggleCitacion);
    estadoRojo.addEventListener('change', toggleCitacion);

    async function initCam() {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        video.srcObject = stream;
      } catch (e) {
        msg.innerHTML = `<div class="alert alert-danger">No se pudo acceder a la cámara: ${e.message}</div>`;
      }
    }

    btnCapturar.addEventListener('click', () => {
      const w = video.videoWidth;
      const h = video.videoHeight;
      if (!w || !h) return;
      canvas.width = w; canvas.height = h;
      ctx.drawImage(video, 0, 0, w, h);
      fotoBase64 = canvas.toDataURL('image/jpeg', 0.9);
      btnEnviar.disabled = !fotoBase64;
    });

    btnEnviar.addEventListener('click', async () => {
      if (!fotoBase64) return;
      btnEnviar.disabled = true;
      msg.innerHTML = '<div class="alert alert-info">Enviando...</div>';
      try {
        const estado_final = estadoRojo.checked ? 'rojo' : 'verde';
        const payload = {
          inspeccion_id: <?= $inspeccion_id ?>,
          foto_base64: fotoBase64,
          estado_final,
          citacion: estado_final === 'rojo' ? {
            motivo: motivo.value || '',
            lugar: lugar.value || '',
            fecha_programada: fecha_programada.value || '',
            nota: nota.value || ''
          } : null
        };
        const resp = await fetch('api/cerrar_inspeccion.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (!data.ok) throw new Error(data.error || 'Error desconocido');
        msg.innerHTML = '<div class="alert alert-success">Inspección cerrada correctamente.</div>';
        setTimeout(() => { window.location.href = 'index.php'; }, 1200);
      } catch (e) {
        msg.innerHTML = `<div class="alert alert-danger">${e.message}</div>`;
        btnEnviar.disabled = false;
      }
    });

    initCam();
  </script>
</body>
</html>