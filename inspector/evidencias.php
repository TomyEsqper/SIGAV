<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/checklist_mapping.php';

verificarSesion(['inspector', 'admin']);

$alistamientoId = isset($_GET['alistamiento_id']) ? intval($_GET['alistamiento_id']) : 0;
if ($alistamientoId <= 0) {
    header('Location: /inspector/index.php');
    exit;
}

$db = getDB();

// Obtener resumen de alistamiento y vehículo
$alist = $db->fetch('SELECT a.id, a.vehiculo_id, a.estado_final, v.numero_interno, v.placa FROM alistamientos a JOIN vehiculos v ON a.vehiculo_id = v.id WHERE a.id = ?', [$alistamientoId]);
if (!$alist) {
    http_response_code(404);
    echo 'Alistamiento no encontrado';
    exit;
}

$errores = [];
$exitos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar todas las fotos subidas para ítems MALO vitales
    try {
        foreach ($_POST['detalle_ids'] ?? [] as $detalleIdStr) {
            $detalleId = intval($detalleIdStr);
            $inputName = 'foto_' . $detalleId;
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $url = saveEvidenceImage($detalleId, $_FILES[$inputName]);
                    $exitos[] = 'Evidencia guardada para detalle #' . $detalleId;
                } catch (Exception $e) {
                    $errores[] = 'Error en detalle #' . $detalleId . ': ' . $e->getMessage();
                }
            } else {
                // Si no hay archivo, marcamos error para forzar evidencia
                $errores[] = 'Falta evidencia para detalle #' . $detalleId;
            }
        }

        if (empty($errores)) {
            $_SESSION['alistamiento_wizard'][$alist['vehiculo_id']]['evidencias_ok'] = true;
            header('Location: /inspector/alistamiento.php?vehiculo=' . (int)$alist['vehiculo_id']);
            exit;
        }
    } catch (Exception $e) {
        $errores[] = 'Error general al procesar evidencias: ' . $e->getMessage();
    }
}

// Consultar todos los detalles con estado MALO (vitales y no vitales)
$detalles = $db->fetchAll(
    'SELECT da.id as detalle_id, ic.nombre as item_nombre, cc.nombre as categoria_nombre, da.foto_url
     FROM detalle_alistamiento da
     LEFT JOIN items_checklist ic ON da.item_id = ic.id
     LEFT JOIN categorias_checklist cc ON ic.categoria_id = cc.id
     WHERE da.alistamiento_id = ? AND da.estado = "malo"
     ORDER BY cc.orden ASC, ic.orden ASC, da.id ASC',
    [$alistamientoId]
);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Evidencias Fotográficas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .preview-img { max-width: 140px; max-height: 140px; object-fit: cover; border: 1px solid #ddd; border-radius: 6px; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/inspector/index.php">SIGAVV Inspector</a>
            <div class="text-white">Vehículo: <?php echo htmlspecialchars($alist['numero_interno'] ?? $alist['placa']); ?></div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Evidencias Fotográficas de Ítems MALO</h1>
            <a class="btn btn-outline-secondary" href="/inspector/alistamiento.php?vehiculo=<?php echo (int)$alist['vehiculo_id']; ?>">Volver</a>
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

        <?php if (empty($detalles)): ?>
            <div class="alert alert-warning">No hay ítems en estado MALO para este alistamiento.</div>
        <?php else: ?>
            <form method="post" enctype="multipart/form-data" action="/inspector/evidencias.php?alistamiento_id=<?php echo (int)$alistamientoId; ?>">
                <input type="hidden" name="alistamiento_id" value="<?php echo (int)$alistamientoId; ?>" />
                <div class="row g-3">
                    <?php foreach ($detalles as $d): ?>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <strong><?php echo htmlspecialchars($d['categoria_nombre']); ?></strong><br />
                                    <small><?php echo htmlspecialchars($d['item_nombre'] ?? ('Ítem #' . (int)$d['detalle_id'])); ?></small>
                                </div>
                                <div class="card-body">
                                    <input type="hidden" name="detalle_ids[]" value="<?php echo (int)$d['detalle_id']; ?>" />
                                    <?php if (!empty($d['foto_url'])): ?>
                                        <div class="mb-2">
                                            <img class="preview-img" src="/<?php echo htmlspecialchars($d['foto_url']); ?>" alt="Foto actual" />
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-2">
                                        <label class="form-label">Subir evidencia (imagen)</label>
                                        <input class="form-control" type="file" accept="image/*" capture="environment" name="foto_<?php echo (int)$d['detalle_id']; ?>" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 d-flex justify-content-between">
                    <a class="btn btn-secondary" href="/inspector/alistamiento.php?vehiculo=<?php echo (int)$alist['vehiculo_id']; ?>">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Evidencias</button>
                </div>
            </form>
        <?php endif; ?>
    </main>
    <script>
        // Vista previa rápida opcional
        document.querySelectorAll('input[type=file]').forEach(inp => {
            inp.addEventListener('change', () => {
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
            });
        });
    </script>
</body>
</html>
<?php
?>
