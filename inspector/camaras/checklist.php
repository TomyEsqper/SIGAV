<?php
/**
 * Módulo Inspector de Cámaras - Checklist
 */
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

verificarSesion(['inspector_camaras', 'admin']);

$inspeccion_id = intval($_GET['inspeccion'] ?? 0);
if (!$inspeccion_id) { header('Location: index.php'); exit; }

// Ítems base (pueden venir luego de la BD); claves estables
$ITEMS = [
    'cableado' => 'Cableado general y conexiones',
    'mdvr' => 'MDVR/DVR (estado físico y funcionamiento)',
    'fusibles' => 'Fusibles y protección',
    'alimentacion' => 'Alimentación/voltaje',
    'camara_delantera' => 'Cámara delantera',
    'camara_puerta' => 'Cámara de puerta',
    'camara_posterior' => 'Cámara posterior'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checklist Cámaras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>.evi{display:none}</style>
</head>
<body class="p-4">
    <div class="container">
        <h1 class="h4 mb-3">Checklist de Cámaras</h1>
        <p class="text-muted">Inspección #<?= htmlspecialchars($inspeccion_id) ?></p>
        <form id="formChecklist" class="card p-3" method="POST" action="api/guardar_checklist.php" enctype="multipart/form-data">
            <input type="hidden" name="inspeccion_id" value="<?= htmlspecialchars($inspeccion_id) ?>" />
            <?php foreach ($ITEMS as $key => $label): ?>
            <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars($label) ?></label>
                <div class="d-flex gap-3 align-items-center">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input estado" type="radio" name="estado[<?= $key ?>]" value="ok" id="<?= $key ?>_ok" required>
                        <label class="form-check-label" for="<?= $key ?>_ok">OK</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input estado" type="radio" name="estado[<?= $key ?>]" value="malo" id="<?= $key ?>_malo">
                        <label class="form-check-label" for="<?= $key ?>_malo">MALO</label>
                    </div>
                    <div class="evi flex-grow-1" id="evi_<?= $key ?>">
                        <input class="form-control" type="file" name="evidencia[<?= $key ?>][]" accept="image/*,video/*" capture="environment" />
                        <small class="text-muted">Adjunte evidencia (foto/video). Obligatorio si está MALO.</small>
                    </div>
                </div>
                <textarea class="form-control mt-2" name="obs[<?= $key ?>]" rows="2" placeholder="Observaciones (opcional)"></textarea>
            </div>
            <?php endforeach; ?>
            <div class="mt-3">
                <button class="btn btn-primary" type="submit">Guardar Checklist</button>
                <a class="btn btn-secondary" href="iniciar.php?vehiculo=<?= htmlspecialchars($_GET['vehiculo'] ?? '') ?>">Volver</a>
            </div>
        </form>
    </div>
    <script>
        document.querySelectorAll('.estado').forEach(r => {
            r.addEventListener('change', (e) => {
                const name = e.target.name.replace('estado[','').replace(']','');
                const malo = e.target.value === 'malo';
                const box = document.getElementById('evi_' + name);
                if (box) { box.style.display = malo ? 'block' : 'none'; }
                if (malo) {
                    const input = box.querySelector('input[type="file"]');
                    if (input) { input.setAttribute('required', 'required'); }
                } else {
                    const input = box.querySelector('input[type="file"]');
                    if (input) { input.removeAttribute('required'); }
                }
            });
        });
    </script>
</body>
</html>