<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/checklist_mapping.php';

verificarSesion(['inspector', 'admin']);

$vehiculoId = isset($_GET['vehiculo']) ? intval($_GET['vehiculo']) : 0;
if ($vehiculoId <= 0) {
    header('Location: /inspector/index.php');
    exit;
}

$db = getDB();

// Cargar vehículo y último alistamiento
$vehiculo = $db->fetch('SELECT id, numero_interno, placa FROM vehiculos WHERE id = ?', [$vehiculoId]);
if (!$vehiculo) {
    http_response_code(404);
    echo 'Vehículo no encontrado';
    exit;
}

// Permitir override explícito del alistamiento previo
$prevId = isset($_GET['prev_id']) ? intval($_GET['prev_id']) : 0;
if ($prevId <= 0) {
    $row = $db->fetch('SELECT id FROM alistamientos WHERE vehiculo_id = ? ORDER BY id DESC LIMIT 1', [$vehiculoId]);
    $prevId = intval($row['id'] ?? 0);
}
if ($prevId <= 0) {
    http_response_code(400);
    echo 'No hay alistamiento previo para este vehículo';
    exit;
}

$errores = [];
$exitos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación y persistencia del alistamiento parcial
    try {
        $usuario = obtenerUsuarioActual();
        $inspectorId = (int)($usuario['id'] ?? 0);
        if ($inspectorId <= 0) { throw new Exception('Inspector inválido'); }

        // Crear encabezado del alistamiento parcial
        $alistamientoId = $db->insert(
            'INSERT INTO alistamientos (vehiculo_id, inspector_id, estado_final, es_alistamiento_parcial, observaciones_generales) VALUES (?, ?, ?, ?, ?)',
            [$vehiculoId, $inspectorId, 'parcial', 1, 'Corrección de ítems MALO del alistamiento #' . $prevId]
        );

        $corregidos = 0; $pendientes = 0;
        $mapNewIds = [];

        foreach (($_POST['prev_detalle_ids'] ?? []) as $prevDetalleIdStr) {
            $prevDetalleId = intval($prevDetalleIdStr);
            if ($prevDetalleId <= 0) { continue; }
            // Estado seleccionado
            $sel = $_POST['estado_' . $prevDetalleId] ?? 'malo';
            $estado = ($sel === 'ok') ? 'ok' : 'malo';

            // Traer categoría del detalle previo
            $prev = $db->fetch('SELECT id, categoria_id FROM detalle_alistamiento WHERE id = ?', [$prevDetalleId]);
            if (!$prev) { continue; }
            $categoriaId = intval($prev['categoria_id'] ?? 0);

            // Crear nuevo detalle en el alistamiento parcial
            $newDetalleId = (int)$db->insert(
                'INSERT INTO detalle_alistamiento (alistamiento_id, categoria_id, estado, fecha_revision) VALUES (?, ?, ?, ?)',
                [$alistamientoId, $categoriaId, $estado, date('Y-m-d H:i:s')]
            );
            $mapNewIds[$prevDetalleId] = $newDetalleId;

            if ($estado === 'ok') {
                // Exigir foto de evidencia
                $input = 'foto_' . $prevDetalleId;
                if (!isset($_FILES[$input]) || ($_FILES[$input]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    throw new Exception('Falta evidencia para detalle previo #' . $prevDetalleId);
                }
                // Guardar foto en ruta definitiva y asociarla al detalle nuevo (DESPUÉS)
                saveEvidenceImage($newDetalleId, $_FILES[$input]);
                $corregidos++;
            } else {
                // Guardar evidencia si adjuntó foto aunque siga MALO (ANTES/seguimiento)
                $input = 'foto_' . $prevDetalleId;
                if (isset($_FILES[$input]) && ($_FILES[$input]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    try { saveEvidenceImage($newDetalleId, $_FILES[$input]); } catch (Exception $e) { /* ignorar errores menores */ }
                }
                $pendientes++;
            }
        }

        // Observación según resultado
        $obs = 'Parcial: ' . $corregidos . ' corregidos, ' . $pendientes . ' pendientes';
        $db->execute('UPDATE alistamientos SET observaciones_generales = ? WHERE id = ?', [$obs, $alistamientoId]);

        $exitos[] = 'Alistamiento parcial guardado (#' . $alistamientoId . ')';
        header('Location: /inspector/alistamiento.php?vehiculo=' . $vehiculoId);
        exit;
    } catch (Exception $e) {
        $errores[] = $e->getMessage();
    }
}

// Cargar detalles MALO del alistamiento previo
$detallesPrevios = $db->fetchAll(
    'SELECT da.id AS detalle_id, da.categoria_id, da.foto_url, cc.nombre AS categoria_nombre
     FROM detalle_alistamiento da
     LEFT JOIN categorias_checklist cc ON cc.id = da.categoria_id
     WHERE da.alistamiento_id = ? AND da.estado = "malo"
     ORDER BY da.id ASC',
    [$prevId]
);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Alistamiento Parcial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .preview-img { max-width: 140px; max-height: 140px; object-fit: cover; border: 1px solid #ddd; border-radius: 6px; }
        .req { color: #dc3545; font-weight: 600; }
    </style>
    <script>
    function toggleRequired(id){
        const ok = document.getElementById('estado_ok_'+id);
        const file = document.getElementById('foto_'+id);
        if(!file) return;
        const isOk = ok && ok.checked;
        file.required = !!isOk; // exigir foto si se marca OK
    }
    </script>
    </head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/inspector/index.php">SIGAVV Inspector</a>
            <div class="text-white">Vehículo: <?php echo htmlspecialchars($vehiculo['numero_interno'] ?? $vehiculo['placa']); ?></div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Alistamiento Parcial</h1>
            <a class="btn btn-outline-secondary" href="/inspector/alistamiento.php?vehiculo=<?php echo (int)$vehiculoId; ?>">Volver</a>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errores as $e): ?>
                    <div><?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($exitos)): ?>
            <div class="alert alert-success">
                <?php foreach ($exitos as $m): ?>
                    <div><?php echo htmlspecialchars($m); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($detallesPrevios)): ?>
            <div class="alert alert-info">No hay ítems previos en estado MALO para corregir.</div>
        <?php else: ?>
        <form method="post" enctype="multipart/form-data" action="/inspector/alistamiento_parcial.php?vehiculo=<?php echo (int)$vehiculoId; ?>&prev_id=<?php echo (int)$prevId; ?>">
            <input type="hidden" name="prev_id" value="<?php echo (int)$prevId; ?>" />
            <div class="row g-3">
                <?php foreach ($detallesPrevios as $d): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <strong><?php echo htmlspecialchars($d['categoria_nombre'] ?? 'Categoría'); ?></strong>
                            <div><small class="text-muted">Evidencia #<?php echo (int)$d['detalle_id']; ?></small></div>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="prev_detalle_ids[]" value="<?php echo (int)$d['detalle_id']; ?>" />
                            <div class="mb-2">
                                <label class="form-label">Estado actual</label><br />
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="estado_<?php echo (int)$d['detalle_id']; ?>" id="estado_ok_<?php echo (int)$d['detalle_id']; ?>" value="ok" onchange="toggleRequired(<?php echo (int)$d['detalle_id']; ?>)" required />
                                    <label class="form-check-label" for="estado_ok_<?php echo (int)$d['detalle_id']; ?>">Corregido (OK)</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="estado_<?php echo (int)$d['detalle_id']; ?>" id="estado_malo_<?php echo (int)$d['detalle_id']; ?>" value="malo" onchange="toggleRequired(<?php echo (int)$d['detalle_id']; ?>)" />
                                    <label class="form-check-label" for="estado_malo_<?php echo (int)$d['detalle_id']; ?>">Sigue MALO</label>
                                </div>
                            </div>
                            <div class="mb-2">
                                <?php if (!empty($d['foto_url'])): ?>
                                    <label class="form-label">Foto ANTES</label>
                                    <img class="preview-img mb-2" src="/<?php echo htmlspecialchars($d['foto_url']); ?>" alt="Foto antes" />
                                <?php endif; ?>
                                <label class="form-label">Evidencia de corrección <span class="req">(obligatoria si marca OK)</span></label>
                                <input class="form-control" type="file" accept="image/*" capture="environment" name="foto_<?php echo (int)$d['detalle_id']; ?>" id="foto_<?php echo (int)$d['detalle_id']; ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-3 d-flex justify-content-between">
                <a class="btn btn-secondary" href="/inspector/alistamiento.php?vehiculo=<?php echo (int)$vehiculoId; ?>">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Parcial</button>
            </div>
        </form>
        <?php endif; ?>
    </main>
    <script>
    function toggleRequired(detalleId) {
        const ok = document.getElementById('estado_ok_' + detalleId);
        const fileInp = document.getElementById('foto_' + detalleId);
        if (fileInp) { fileInp.required = !!(ok && ok.checked); }
    }

    // Vista previa rápida opcional
    document.addEventListener('change', (e) => {
        const inp = e.target;
        if (inp && inp.matches('input[type=file]')) {
            const file = inp.files && inp.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            let img = inp.parentElement.querySelector('img.preview-img');
            if (!img) {
                img = document.createElement('img');
                img.className = 'preview-img mt-2';
                inp.parentElement.appendChild(img);
            }
            img.src = url;
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
?>