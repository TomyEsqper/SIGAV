<?php
/**
 * Detalle de Alistamiento con Evidencias Fotográficas - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2024 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación
verificarSesion(['admin']);

$detalles = [];
$categorias = [];
$error = null;

$alistamiento_id = $_GET['id'] ?? 0;

if (!$alistamiento_id) {
    header('Location: dashboard.php');
    exit;
}

try {
    $db = getDB();
    
    // Obtener datos del alistamiento
    $stmt = $db->prepare(
    "
        SELECT a.*, v.numero_interno, v.placa, v.propietario, u.nombre as inspector_nombre, c.nombre AS conductor_nombre
        FROM alistamientos a
        LEFT JOIN vehiculos v ON a.vehiculo_id = v.id
        LEFT JOIN usuarios u ON a.inspector_id = u.id
        LEFT JOIN conductores c ON a.conductor_id = c.id
        WHERE a.id = ?
    ");
    $stmt->execute([$alistamiento_id]);
    $alistamiento = $stmt->fetch();
    
    if (!$alistamiento) {
        header('Location: dashboard.php');
        exit;
    }
    $conductorNombre = trim((string)($alistamiento['conductor_nombre'] ?? ''));
    if ($conductorNombre === '') {
        $cid = isset($alistamiento['conductor_id']) ? intval($alistamiento['conductor_id']) : 0;
        if ($cid > 0) {
            try {
                $r = $db->fetch('SELECT nombre FROM conductores WHERE id = ?', [$cid]);
                if ($r && isset($r['nombre'])) { $conductorNombre = trim((string)$r['nombre']); }
            } catch (Exception $e) { }
        }
        if ($conductorNombre === '' && !empty($alistamiento['vehiculo_id'])) {
            try {
                $r2 = $db->fetch('SELECT c.nombre FROM alistamientos a LEFT JOIN conductores c ON c.id = a.conductor_id WHERE a.vehiculo_id = ? AND a.id <= ? AND a.conductor_id IS NOT NULL ORDER BY a.id DESC LIMIT 1', [$alistamiento['vehiculo_id'], $alistamiento_id]);
                if ($r2 && isset($r2['nombre'])) { $conductorNombre = trim((string)$r2['nombre']); }
            } catch (Exception $e) { }
        }
    }
    if ($conductorNombre !== '') { $alistamiento['conductor_nombre'] = $conductorNombre; }
    
    // Obtener detalles del alistamiento con evidencias
    $stmt = $db->prepare("
        SELECT da.*, ic.nombre as item_nombre, ic.descripcion as item_descripcion, 
               cc.nombre as categoria_nombre, ic.es_vital
        FROM detalle_alistamiento da
        JOIN items_checklist ic ON da.item_id = ic.id
        JOIN categorias_checklist cc ON ic.categoria_id = cc.id
        WHERE da.alistamiento_id = ?
        ORDER BY cc.orden, ic.orden
    ");
    $stmt->execute([$alistamiento_id]);
    $detalles = $stmt->fetchAll();
    
    // Fallback: si el esquema completo no devuelve resultados, usar detalle_alistamiento simplificado
    if (empty($detalles)) {
        $stmt = $db->prepare("SELECT id, alistamiento_id, categoria_id, estado, fecha_revision, foto_url FROM detalle_alistamiento WHERE alistamiento_id = ? ORDER BY id ASC");
        $stmt->execute([$alistamiento_id]);
        $detalles = $stmt->fetchAll();
    }
    
    // Agrupar por categorías
    $categorias = [];
    foreach ($detalles as $detalle) {
        $categorias[$detalle['categoria_nombre']][] = $detalle;
    }
    
} catch (Exception $e) {
    $error = "Error al cargar el alistamiento: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Alistamiento - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; position: relative; }
        body::before { content: ""; position: fixed; inset: 0; background: rgba(11, 30, 63, 0.55); pointer-events: none; z-index: 0; }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .sidebar { min-height: 100vh; position: sticky; top: 0; overflow: hidden; background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%); color: white; }
        .alistamiento-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .estado-badge { font-size: 1.2rem; padding: 0.5rem 1rem; }
        
        .categoria-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .categoria-header { background: linear-gradient(45deg, #f8f9fa, #e9ecef); padding: 1rem; border-bottom: 2px solid #dee2e6; }
        
        .item-row { padding: 1rem; border-bottom: 1px solid #f8f9fa; transition: background-color 0.3s; }
        .item-row:hover { background-color: #f8f9fa; }
        .item-row:last-child { border-bottom: none; }
        
        .estado-ok { color: #28a745; background-color: #d4edda; border-color: #c3e6cb; }
        .estado-malo { color: #dc3545; background-color: #f8d7da; border-color: #f5c6cb; }
        
        .evidencia-foto { max-width: 200px; max-height: 150px; border-radius: 8px; cursor: pointer; transition: transform 0.3s; }
        .evidencia-foto:hover { transform: scale(1.05); }
        
        .vital-badge { background: linear-gradient(45deg, #ff6b6b, #ee5a24); color: white; font-size: 0.8rem; padding: 0.2rem 0.5rem; border-radius: 20px; }
        
        .modal-evidencia .modal-dialog { max-width: 90vw; }
        .modal-evidencia img { max-width: 100%; max-height: 80vh; object-fit: contain; }
    </style>
</head>
<body>
    <?php
    // Sidebar opcional: incluir si existe, evitar warnings si falta
    $sidebarPath = __DIR__ . '/../includes/admin_sidebar.php';
    if (is_file($sidebarPath)) {
        include $sidebarPath;
    }
    ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header del Alistamiento -->
            <div class="alistamiento-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-clipboard-check me-2"></i>Detalle de Alistamiento</h2>
                        <p class="mb-2"><strong>Vehículo:</strong> <?= htmlspecialchars($alistamiento['numero_interno'] ?? '-') ?> - <?= htmlspecialchars($alistamiento['placa'] ?? '-') ?></p>
                        <p class="mb-2"><strong>Propietario:</strong> <?= htmlspecialchars(($alistamiento['propietario'] ?? '') !== '' ? $alistamiento['propietario'] : '-') ?></p>
                        <p class="mb-2"><strong>Conductor:</strong> <?= htmlspecialchars($alistamiento['conductor_nombre'] ?? 'No asignado') ?></p>
                        <p class="mb-0"><strong>Inspector:</strong> <?= htmlspecialchars($alistamiento['inspector_nombre'] ?? '-') ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="mb-2">
                            <span class="badge estado-badge bg-<?= (($alistamiento['estado_final'] ?? '') == 'verde') ? 'success' : ((($alistamiento['estado_final'] ?? '') == 'amarillo') ? 'warning' : 'danger') ?>">
                                <?= ($alistamiento['estado_final'] ?? '') !== '' ? strtoupper($alistamiento['estado_final']) : '-' ?>
                            </span>
                        </div>
                        <p class="mb-1"><strong>Fecha:</strong> <?= !empty($alistamiento['fecha_alistamiento']) ? date('d/m/Y H:i', strtotime($alistamiento['fecha_alistamiento'])) : '-' ?></p>
                        <?php if (!empty($alistamiento['es_alistamiento_parcial'])): ?>
                            <span class="badge bg-info">Alistamiento Parcial</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="mb-3 d-flex gap-2">
                <a href="<?= !empty($alistamiento['vehiculo_id']) ? 'vehiculo_detalle.php?id=' . urlencode($alistamiento['vehiculo_id']) : '#' ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Vehículo
                </a>
            </div>
            
            <!-- Observaciones Generales -->
            <?php if (!empty($alistamiento['observaciones_generales'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-comment-alt me-2"></i>Observaciones Generales</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($alistamiento['observaciones_generales'] ?? '')) ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Evidencias Fotográficas -->
            <?php
            $fotos = [];
            try {
                if (isset($db) && $db) {
                    $stmtFotos = $db->prepare("SELECT da.id, da.estado, da.categoria_id, da.foto_url, ic.nombre AS item_nombre FROM detalle_alistamiento da LEFT JOIN items_checklist ic ON da.item_id = ic.id WHERE da.alistamiento_id = ? AND da.foto_url IS NOT NULL AND da.foto_url <> '' ORDER BY da.id ASC");
                    $stmtFotos->execute([$alistamiento_id]);
                    $fotos = $stmtFotos->fetchAll();

                    if (empty($fotos)) {
                        $stmtFotos = $db->prepare("SELECT id, estado, categoria_id, foto_url, NULL AS item_nombre FROM detalle_alistamiento WHERE alistamiento_id = ? AND foto_url IS NOT NULL AND foto_url <> '' ORDER BY id ASC");
                        $stmtFotos->execute([$alistamiento_id]);
                        $fotos = $stmtFotos->fetchAll();
                    }

                    if (!empty($alistamiento['es_alistamiento_parcial'])) {
                        $okIds = [];
                        try {
                            $stmtOk = $db->prepare("SELECT DISTINCT da.item_id FROM detalle_alistamiento da WHERE da.alistamiento_id = ? AND da.estado = 'ok' AND da.item_id IS NOT NULL");
                            $stmtOk->execute([$alistamiento_id]);
                            foreach ($stmtOk->fetchAll() as $r) { if (isset($r['item_id'])) { $okIds[(int)$r['item_id']] = true; } }
                        } catch (Exception $e) { $okIds = []; }

                        $maloIdsConFoto = [];
                        try {
                            $stmtMaloCur = $db->prepare("SELECT DISTINCT da.item_id FROM detalle_alistamiento da WHERE da.alistamiento_id = ? AND da.estado = 'malo' AND da.foto_url IS NOT NULL AND da.foto_url <> '' AND da.item_id IS NOT NULL");
                            $stmtMaloCur->execute([$alistamiento_id]);
                            foreach ($stmtMaloCur->fetchAll() as $r) { if (isset($r['item_id'])) { $maloIdsConFoto[(int)$r['item_id']] = true; } }
                        } catch (Exception $e) { $maloIdsConFoto = []; }

                        try {
                            $stmtPrev = $db->prepare("SELECT da.id AS detalle_id, da.item_id, da.foto_url, ic.nombre AS item_nombre FROM detalle_alistamiento da LEFT JOIN items_checklist ic ON da.item_id = ic.id WHERE da.alistamiento_id = (SELECT id FROM alistamientos WHERE vehiculo_id = ? AND id < ? ORDER BY id DESC LIMIT 1) AND da.estado = 'malo' AND da.foto_url IS NOT NULL AND da.foto_url <> ''");
                            $stmtPrev->execute([$alistamiento['vehiculo_id'], $alistamiento_id]);
                            foreach ($stmtPrev->fetchAll() as $pr) {
                                $iid = isset($pr['item_id']) ? (int)$pr['item_id'] : 0;
                                if ($iid > 0 && empty($okIds[$iid]) && empty($maloIdsConFoto[$iid])) {
                                    $fotos[] = ['id' => (int)($pr['detalle_id'] ?? 0), 'estado' => 'malo', 'foto_url' => $pr['foto_url'], 'item_nombre' => ($pr['item_nombre'] ?? null)];
                                }
                            }
                        } catch (Exception $e) {
                            try {
                                $stmtPrev = $db->prepare("SELECT da.id AS detalle_id, da.item_id, da.foto_url FROM detalle_alistamiento da WHERE da.alistamiento_id = (SELECT id FROM alistamientos WHERE vehiculo_id = ? AND id < ? ORDER BY id DESC LIMIT 1) AND da.estado = 'malo' AND da.foto_url IS NOT NULL AND da.foto_url <> ''");
                                $stmtPrev->execute([$alistamiento['vehiculo_id'], $alistamiento_id]);
                                foreach ($stmtPrev->fetchAll() as $pr) {
                                    $iid = isset($pr['item_id']) ? (int)$pr['item_id'] : 0;
                                    if ($iid > 0 && empty($okIds[$iid]) && empty($maloIdsConFoto[$iid])) {
                                        $fotos[] = ['id' => (int)($pr['detalle_id'] ?? 0), 'estado' => 'malo', 'foto_url' => $pr['foto_url'], 'item_nombre' => null];
                                    }
                                }
                            } catch (Exception $e2) { }
                        }
                    }
                }
            } catch (Exception $e) { }
            // Detectar ítems MALO sin evidencia
            $malosSinFoto = [];
            try {
                $stmtSin = $db->prepare("SELECT da.id, ic.nombre AS item_nombre FROM detalle_alistamiento da LEFT JOIN items_checklist ic ON da.item_id = ic.id WHERE da.alistamiento_id = ? AND da.estado = 'malo' AND (da.foto_url IS NULL OR da.foto_url = '') ORDER BY da.id ASC");
                $stmtSin->execute([$alistamiento_id]);
                $malosSinFoto = $stmtSin->fetchAll();
            } catch (Exception $e) { $malosSinFoto = []; }
            if (!empty($fotos) || !empty($malosSinFoto)):
            ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Evidencias Fotográficas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // Preparar mapa de fotos ANTES por categoría del alistamiento previo, para emparejar
                        $prevIdPair = 0; $prevCatPhotos = []; $prevIdxByCat = [];
                        if (!empty($alistamiento['es_alistamiento_parcial'])) {
                            try {
                                $stmtPrev = $db->prepare("SELECT id FROM alistamientos WHERE vehiculo_id = ? AND id < ? ORDER BY id DESC LIMIT 1");
                                $stmtPrev->execute([$alistamiento['vehiculo_id'], $alistamiento_id]);
                                $prevRow = $stmtPrev->fetch();
                                $prevIdPair = intval($prevRow['id'] ?? 0);
                            } catch (Exception $e) { $prevIdPair = 0; }
                            if ($prevIdPair > 0) {
                                try {
                                    $stmtPrevF = $db->prepare("SELECT categoria_id, foto_url FROM detalle_alistamiento WHERE alistamiento_id = ? AND estado = 'malo' AND foto_url IS NOT NULL AND foto_url <> '' ORDER BY id ASC");
                                    $stmtPrevF->execute([$prevIdPair]);
                                    foreach ($stmtPrevF->fetchAll() as $pf) {
                                        $cid = (int)($pf['categoria_id'] ?? 0);
                                        if (!isset($prevCatPhotos[$cid])) { $prevCatPhotos[$cid] = []; $prevIdxByCat[$cid] = 0; }
                                        $prevCatPhotos[$cid][] = $pf['foto_url'];
                                    }
                                } catch (Exception $e) { $prevCatPhotos = []; $prevIdxByCat = []; }
                            }
                        }
                        foreach ($fotos as $foto): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <?php
                                $estadoCur = strtolower($foto['estado'] ?? '');
                                $cidCur = (int)($foto['categoria_id'] ?? 0);
                                $hasPrev = ($prevIdPair > 0 && $cidCur > 0 && !empty($prevCatPhotos[$cidCur]));
                                if ($hasPrev && ($estadoCur === 'ok' || $estadoCur === 'malo')) {
                                    $idx = (int)($prevIdxByCat[$cidCur] ?? 0);
                                    $prevUrl = $prevCatPhotos[$cidCur][$idx] ?? null;
                                    if ($prevUrl !== null) { $prevIdxByCat[$cidCur] = $idx + 1; }
                                    echo '<div class="card-body">';
                                    echo '<div class="row">';
                                    echo '<div class="col-6">';
                                    if ($prevUrl) {
                                        echo '<strong>ANTES</strong><br>';
                                        echo '<img src="../' . htmlspecialchars($prevUrl) . '" class="img-fluid" style="height: 180px; object-fit: cover;" onclick="mostrarEvidencia(\'' . htmlspecialchars($prevUrl) . '\', \'' . htmlspecialchars($foto['item_nombre'] ?? ('Evidencia #' . (int)($foto['id'] ?? 0))) . ' (ANTES)\')">';
                                    } else {
                                        echo '<div class="text-muted"><strong>ANTES</strong><br>Sin foto</div>';
                                    }
                                    echo '</div>'; // col-6
                                    echo '<div class="col-6">';
                                    $label = ($estadoCur === 'ok') ? 'DESPUÉS' : 'SIGUE MALO';
                                    echo '<strong>' . $label . '</strong><br>';
                                    echo '<img src="../' . htmlspecialchars($foto['foto_url']) . '" class="img-fluid" style="height: 180px; object-fit: cover;" onclick="mostrarEvidencia(\'' . htmlspecialchars($foto['foto_url']) . '\', \'' . htmlspecialchars($foto['item_nombre'] ?? ('Evidencia #' . (int)($foto['id'] ?? 0))) . ' (' . $label . ')\')">';
                                    echo '</div>'; // col-6
                                    echo '</div>'; // row
                                    echo '<div class="mt-2">';
                                    echo '<span class="badge ' . (($estadoCur === 'ok') ? 'bg-success' : 'bg-danger') . '">' . strtoupper($estadoCur) . '</span> ';
                                    echo '<h6 class="card-title d-inline-block ms-2">' . htmlspecialchars($foto['item_nombre'] ?? ('Evidencia #' . (int)($foto['id'] ?? 0))) . '</h6>';
                                    echo '</div>';
                                    echo '</div>'; // card-body
                                } else {
                                    echo '<img src="../' . htmlspecialchars($foto['foto_url']) . '" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Evidencia" onclick="mostrarEvidencia(\'' . htmlspecialchars($foto['foto_url']) . '\', \'' . htmlspecialchars($foto['item_nombre'] ?? ('Evidencia #' . (int)($foto['id'] ?? 0))) . '\')">';
                                    echo '<div class="card-body">';
                                    echo '<span class="badge ' . (($estadoCur === 'ok') ? 'bg-success' : 'bg-danger') . '">' . strtoupper($estadoCur) . '</span>';
                                    echo '<h6 class="card-title mt-2">' . htmlspecialchars($foto['item_nombre'] ?? ('Evidencia #' . (int)($foto['id'] ?? 0))) . '</h6>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php foreach ($malosSinFoto as $sf): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border-danger">
                                <div class="card-body">
                                    <span class="badge bg-danger">MALO</span>
                                    <h6 class="card-title mt-2"><?= htmlspecialchars($sf['item_nombre'] ?? ('Ítem #' . (int)($sf['id'] ?? 0))) ?></h6>
                                    <div class="text-muted">Sin evidencia</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php
            if (!empty($alistamiento['es_alistamiento_parcial'])) {
                $prevId = 0;
                try {
                    $stmtPrev = $db->prepare("SELECT id FROM alistamientos WHERE vehiculo_id = ? AND id < ? ORDER BY id DESC LIMIT 1");
                    $stmtPrev->execute([$alistamiento['vehiculo_id'], $alistamiento_id]);
                    $prevRow = $stmtPrev->fetch();
                    $prevId = intval($prevRow['id'] ?? 0);
                } catch (Exception $e) { $prevId = 0; }

                if ($prevId > 0) {
                    $prevPorCat = [];
                    $despuesPorCat = [];
                    $pendientePorCat = [];
                    try {
                        $stmtA = $db->prepare("SELECT da.categoria_id, da.foto_url, cc.nombre AS categoria_nombre FROM detalle_alistamiento da LEFT JOIN categorias_checklist cc ON cc.id = da.categoria_id WHERE da.alistamiento_id = ? AND da.estado = 'malo' AND da.foto_url IS NOT NULL AND da.foto_url <> '' ORDER BY da.categoria_id, da.id");
                        $stmtA->execute([$prevId]);
                        foreach ($stmtA->fetchAll() as $row) { $cid = (int)($row['categoria_id'] ?? 0); $prevPorCat[$cid]['nombre'] = ($row['categoria_nombre'] ?? 'Categoría'); $prevPorCat[$cid]['items'][] = $row['foto_url']; }

                        $stmtD = $db->prepare("SELECT da.categoria_id, da.foto_url, cc.nombre AS categoria_nombre FROM detalle_alistamiento da LEFT JOIN categorias_checklist cc ON cc.id = da.categoria_id WHERE da.alistamiento_id = ? AND da.estado = 'ok' AND da.foto_url IS NOT NULL AND da.foto_url <> '' ORDER BY da.categoria_id, da.id");
                        $stmtD->execute([$alistamiento_id]);
                        foreach ($stmtD->fetchAll() as $row) { $cid = (int)($row['categoria_id'] ?? 0); $despuesPorCat[$cid]['nombre'] = ($row['categoria_nombre'] ?? 'Categoría'); $despuesPorCat[$cid]['items'][] = $row['foto_url']; }

                        $stmtM = $db->prepare("SELECT da.categoria_id, da.foto_url, cc.nombre AS categoria_nombre FROM detalle_alistamiento da LEFT JOIN categorias_checklist cc ON cc.id = da.categoria_id WHERE da.alistamiento_id = ? AND da.estado = 'malo' AND da.foto_url IS NOT NULL AND da.foto_url <> '' ORDER BY da.categoria_id, da.id");
                        $stmtM->execute([$alistamiento_id]);
                        foreach ($stmtM->fetchAll() as $row) { $cid = (int)($row['categoria_id'] ?? 0); $pendientePorCat[$cid]['nombre'] = ($row['categoria_nombre'] ?? 'Categoría'); $pendientePorCat[$cid]['items'][] = $row['foto_url']; }
                    } catch (Exception $e) { }

                    $catIds = array_unique(array_merge(array_keys($prevPorCat), array_keys($despuesPorCat), array_keys($pendientePorCat)));
                    if (!empty($catIds)) {
                        echo '<div class="card mb-4">';
                        echo '<div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-sync-alt me-2"></i>Comparativa ANTES / DESPUÉS por Categoría</h5></div>';
                        echo '<div class="card-body">';
                        foreach ($catIds as $cid) {
                            $nombreCat = htmlspecialchars($prevPorCat[$cid]['nombre'] ?? $despuesPorCat[$cid]['nombre'] ?? $pendientePorCat[$cid]['nombre'] ?? 'Categoría');
                            echo '<div class="mb-3">';
                            echo '<h6 class="mb-2"><i class="fas fa-list me-2"></i>' . $nombreCat . '</h6>';
                            $antesList = $prevPorCat[$cid]['items'] ?? [];
                            $despList = $despuesPorCat[$cid]['items'] ?? [];
                            $pendList = $pendientePorCat[$cid]['items'] ?? [];
                            $max = max(count($antesList), count($despList), count($pendList));
                            for ($i = 0; $i < $max; $i++) {
                                echo '<div class="row align-items-start mb-2">';
                                echo '<div class="col-md-6">';
                                if (isset($antesList[$i])) { echo '<div><strong>ANTES</strong><br><img src="../' . htmlspecialchars($antesList[$i]) . '" class="img-fluid" style="height:200px;object-fit:cover;" /></div>'; } else { echo '<div class="text-muted"><strong>ANTES</strong><br>Sin foto</div>'; }
                                echo '</div>';
                                echo '<div class="col-md-6">';
                                if (isset($despList[$i])) { echo '<div><strong>DESPUÉS</strong><br><img src="../' . htmlspecialchars($despList[$i]) . '" class="img-fluid" style="height:200px;object-fit:cover;" /></div>'; }
                                elseif (isset($pendList[$i])) { echo '<div><strong>SIGUE MALO</strong><br><img src="../' . htmlspecialchars($pendList[$i]) . '" class="img-fluid" style="height:200px;object-fit:cover;" /></div>'; }
                                else { echo '<div class="text-muted"><strong>DESPUÉS</strong><br>Sin foto</div>'; }
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                }
            }
            ?>

            <!-- Detalles por Categoría -->
            <?php foreach ($categorias as $categoria_nombre => $items): ?>
            <div class="categoria-card">
                <div class="categoria-header">
                    <h4 class="mb-0">
                        <i class="fas fa-list-check me-2"></i><?= htmlspecialchars($categoria_nombre) ?>
                        <span class="badge bg-secondary ms-2"><?= count($items) ?> ítems</span>
                    </h4>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <span class="badge <?= $item['estado'] == 'ok' ? 'estado-ok' : 'estado-malo' ?> me-2">
                                        <i class="fas fa-<?= $item['estado'] == 'ok' ? 'check' : 'times' ?>"></i>
                                        <?= strtoupper($item['estado']) ?>
                                    </span>
                                    <?php if ($item['es_vital']): ?>
                                        <span class="vital-badge me-2">VITAL</span>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($item['item_nombre']) ?></strong>
                                        <?php if ($item['item_descripcion']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($item['item_descripcion']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <?php if ($item['observaciones']): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-comment me-1"></i>
                                        <?= htmlspecialchars($item['observaciones']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 text-end">
                                <?php if ($item['foto_url']): ?>
                                    <img src="../<?= htmlspecialchars($item['foto_url']) ?>" 
                                         class="evidencia-foto" 
                                         alt="Evidencia fotográfica"
                                         onclick="mostrarEvidencia('<?= htmlspecialchars($item['foto_url']) ?>', '<?= htmlspecialchars($item['item_nombre']) ?>')">
                                    <br><small class="text-muted">Click para ampliar</small>
                                <?php else: ?>
                                    <span class="text-muted">Sin evidencia</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Resumen de Estados -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Resumen del Alistamiento</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <?php
                        // Resumen robusto directo desde BD (compatible con esquema simplificado)
                        $total_items = 0;
                        $count_ok = 0;
                        $count_malo = 0;
                        $count_vitales_malo = 0; // 0 si no existe items_checklist
                        try {
                            // Total de ítems
                            $stmtTot = $db->prepare("SELECT COUNT(*) FROM detalle_alistamiento WHERE alistamiento_id = ?");
                            $stmtTot->execute([$alistamiento_id]);
                            $total_items = (int)$stmtTot->fetchColumn();
                            // Conteo por estado
                            $stmtGrp = $db->prepare("SELECT estado, COUNT(*) AS c FROM detalle_alistamiento WHERE alistamiento_id = ? GROUP BY estado");
                            $stmtGrp->execute([$alistamiento_id]);
                            foreach ($stmtGrp->fetchAll() as $r) {
                                if (($r['estado'] ?? '') === 'ok') { $count_ok = (int)$r['c']; }
                                if (($r['estado'] ?? '') === 'malo') { $count_malo = (int)$r['c']; }
                            }
                            // Vitales malos (si existe tabla items_checklist)
                            try {
                                $stmtVital = $db->prepare("SELECT COUNT(*) FROM detalle_alistamiento da JOIN items_checklist ic ON da.item_id = ic.id WHERE da.alistamiento_id = ? AND da.estado = 'malo' AND ic.es_vital = 1");
                                $stmtVital->execute([$alistamiento_id]);
                                $count_vitales_malo = (int)$stmtVital->fetchColumn();
                            } catch (Exception $e) { /* esquema simplificado: mantener 0 */ }
                        } catch (Exception $e) { /* mantener valores por defecto */ }
                        ?>
                        <div class="col-md-3">
                            <div class="stat-card bg-success text-white">
                                <h3><?= $count_ok ?></h3>
                                <p>Ítems OK</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-danger text-white">
                                <h3><?= $count_malo ?></h3>
                                <p>Ítems Malos</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-warning text-white">
                                <h3><?= $count_vitales_malo ?></h3>
                                <p>Vitales Malos</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-info text-white">
                                <h3><?= $total_items ?></h3>
                                <p>Total Ítems</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para Evidencias -->
    <div class="modal fade modal-evidencia" id="modalEvidencia" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEvidenciaTitle">Evidencia Fotográfica</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalEvidenciaImg" src="" alt="Evidencia" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a id="modalEvidenciaDownload" href="" download class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Descargar
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function mostrarEvidencia(fotoUrl, itemNombre) {
            const baseUrl = '../';
            document.getElementById('modalEvidenciaTitle').textContent = 'Evidencia: ' + itemNombre;
            document.getElementById('modalEvidenciaImg').src = baseUrl + fotoUrl;
            document.getElementById('modalEvidenciaDownload').href = baseUrl + fotoUrl;
            
            new bootstrap.Modal(document.getElementById('modalEvidencia')).show();
        }
    </script>
    
    <?php
    // Soporte AJAX para cargar fotos desde alistamientos.php
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'fotos') {
        // Obtener fotos del alistamiento con tolerancia a esquemas (fallback sin items_checklist)
        $fotos = [];
        try {
            $stmt = $db->prepare("
                SELECT da.foto_url, da.estado, ic.nombre as item_nombre
                FROM detalle_alistamiento da
                LEFT JOIN items_checklist ic ON da.item_id = ic.id
                WHERE da.alistamiento_id = ? AND da.foto_url IS NOT NULL AND da.foto_url != ''
                ORDER BY da.id ASC
            ");
            $stmt->execute([$alistamiento_id]);
            $fotos = $stmt->fetchAll();
        } catch (Exception $e) {
            $fotos = [];
        }

        if (empty($fotos)) {
            // Fallback directo desde detalle_alistamiento
            $stmt = $db->prepare("
                SELECT da.foto_url, da.estado, CONCAT('Evidencia #', da.id) AS item_nombre
                FROM detalle_alistamiento da
                WHERE da.alistamiento_id = ? AND da.foto_url IS NOT NULL AND da.foto_url <> ''
                ORDER BY da.id ASC
            ");
            $stmt->execute([$alistamiento_id]);
            $fotos = $stmt->fetchAll();
        }

        echo '<div class="row">';
        foreach ($fotos as $foto) {
            echo '<div class="col-md-6 mb-3">';
            echo '<div class="card">';
            echo '<img src="../' . htmlspecialchars($foto['foto_url']) . '" class="card-img-top" style="height: 200px; object-fit: cover;" onclick="mostrarEvidencia(\'' . htmlspecialchars($foto['foto_url']) . '\', \'' . htmlspecialchars($foto['item_nombre']) . '\')">';
            echo '<div class="card-body">';
            $estado = strtoupper((string)($foto['estado'] ?? ''));
            if ($estado) { echo '<span class="badge ' . ($estado === 'OK' ? 'bg-success' : ($estado === 'MALO' ? 'bg-danger' : 'bg-secondary')) . '\">' . $estado . '</span> '; }
            echo '<h6 class="card-title d-inline-block ms-2">' . htmlspecialchars($foto['item_nombre']) . '</h6>';
            if (!empty($foto['observaciones'] ?? '')) {
                echo '<p class="card-text"><small class="text-muted">' . htmlspecialchars($foto['observaciones']) . '</small></p>';
            }
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        // Ítems MALO sin evidencia
        try {
            $stmtSin = $db->prepare("SELECT da.id, ic.nombre AS item_nombre FROM detalle_alistamiento da LEFT JOIN items_checklist ic ON da.item_id = ic.id WHERE da.alistamiento_id = ? AND da.estado = 'malo' AND (da.foto_url IS NULL OR da.foto_url = '') ORDER BY da.id ASC");
            $stmtSin->execute([$alistamiento_id]);
            $malosSinFoto = $stmtSin->fetchAll();
        } catch (Exception $e) { $malosSinFoto = []; }
        foreach ($malosSinFoto as $sf) {
            echo '<div class="col-md-6 mb-3">';
            echo '<div class="card border-danger">';
            echo '<div class="card-body">';
            echo '<span class="badge bg-danger">MALO</span>';
            echo '<h6 class="card-title mt-2">' . htmlspecialchars($sf['item_nombre'] ?? ('Ítem #' . (int)($sf['id'] ?? 0))) . '</h6>';
            echo '<div class="text-muted">Sin evidencia</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';

        // Añadir comparativa y evidencias de primera revisión cuando es alistamiento parcial
        $prevId = 0;
        try {
            $stmtPrev = $db->prepare("SELECT id FROM alistamientos WHERE vehiculo_id = (SELECT vehiculo_id FROM alistamientos WHERE id = ?) AND id < ? ORDER BY id DESC LIMIT 1");
            $stmtPrev->execute([$alistamiento_id, $alistamiento_id]);
            $prevRow = $stmtPrev->fetch();
            $prevId = intval($prevRow['id'] ?? 0);
        } catch (Exception $e) { $prevId = 0; }

        if (!empty($alistamiento['es_alistamiento_parcial']) && $prevId > 0) {
            // Comparativa ANTES/DESPUÉS por ítem
            $antes = [];
            $despues = [];
            $pendiente = [];
            try {
                $stmtA = $db->prepare("SELECT da.item_id, da.foto_url, ic.nombre AS item_nombre FROM detalle_alistamiento da JOIN items_checklist ic ON da.item_id = ic.id WHERE da.alistamiento_id = ? AND da.estado = 'malo' AND da.foto_url IS NOT NULL AND da.foto_url <> ''");
                $stmtA->execute([$prevId]);
                foreach ($stmtA->fetchAll() as $row) { $antes[$row['item_id']] = ['url' => $row['foto_url'], 'nombre' => $row['item_nombre']]; }

                $stmtD = $db->prepare("SELECT da.item_id, da.foto_url, ic.nombre AS item_nombre FROM detalle_alistamiento da JOIN items_checklist ic ON da.item_id = ic.id WHERE da.alistamiento_id = ? AND da.estado = 'ok' AND da.foto_url IS NOT NULL AND da.foto_url <> ''");
                $stmtD->execute([$alistamiento_id]);
                foreach ($stmtD->fetchAll() as $row) { $despues[$row['item_id']] = ['url' => $row['foto_url'], 'nombre' => $row['item_nombre']]; }

                // En el alistamiento actual, ítems que siguen en MALO con evidencia
                $stmtM = $db->prepare("SELECT da.item_id, da.foto_url, ic.nombre AS item_nombre FROM detalle_alistamiento da JOIN items_checklist ic ON da.item_id = ic.id WHERE da.alistamiento_id = ? AND da.estado = 'malo' AND da.foto_url IS NOT NULL AND da.foto_url <> ''");
                $stmtM->execute([$alistamiento_id]);
                foreach ($stmtM->fetchAll() as $row) { $pendiente[$row['item_id']] = ['url' => $row['foto_url'], 'nombre' => $row['item_nombre']]; }
            } catch (Exception $e) { /* ignorar */ }

            $cats = array_unique(array_merge(array_keys($antes), array_keys($despues)));
            if (!empty($cats)) {
                echo '<div class="mb-3">';
                echo '<h5 class="mb-3 text-success"><i class="fas fa-sync-alt me-2"></i>Comparativa ANTES / DESPUÉS (Parcial) por Ítem</h5>';
                echo '<div class="row">';
                foreach ($cats as $iid) {
                    echo '<div class="col-md-6 mb-3">';
                    echo '<div class="card h-100"><div class="card-body">';
                    $nombreItem = htmlspecialchars(($antes[$iid]['nombre'] ?? $despues[$iid]['nombre'] ?? 'Ítem'));
                    echo '<h6 class="mb-3"><i class="fas fa-tag me-2"></i>' . $nombreItem . '</h6>';
                    echo '<div class="row">';
                    echo '<div class="col-6">';
                    if (isset($antes[$iid])) {
                        echo '<div class="mb-2"><strong>ANTES</strong><br><img src="../' . htmlspecialchars($antes[$iid]['url']) . '" class="img-fluid" style="height:200px;object-fit:cover;" /></div>';
                    } else {
                        echo '<div class="mb-2 text-muted"><strong>ANTES</strong><br>Sin foto</div>';
                    }
                    echo '</div>';
                    echo '<div class="col-6">';
                    if (isset($despues[$iid])) {
                        echo '<div class="mb-2"><strong>DESPUÉS</strong><br><img src="../' . htmlspecialchars($despues[$iid]['url']) . '" class="img-fluid" style="height:200px;object-fit:cover;" /></div>';
                    } elseif (isset($pendiente[$iid])) {
                        echo '<div class="mb-2"><strong>SIGUE MALO</strong><br><img src="../' . htmlspecialchars($pendiente[$iid]['url']) . '" class="img-fluid" style="height:200px;object-fit:cover;" /></div>';
                    } elseif (isset($antes[$iid])) {
                        echo '<div class="mb-2"><strong>SIGUE MALO (foto previa)</strong><br><img src="../' . htmlspecialchars($antes[$iid]['url']) . '" class="img-fluid" style="height:200px;object-fit:cover;" /></div>';
                    } else {
                        echo '<div class="mb-2 text-muted"><strong>DESPUÉS</strong><br>Sin foto</div>';
                    }
                    echo '</div>';
                    echo '</div>'; // row
                    echo '</div></div></div>';
                }
                echo '</div>';
                echo '</div>';
            }

            // Evidencias de la primera revisión
            try {
                $prevFotos = [];
                try {
                    $stmtPF = $db->prepare("\n                        SELECT da.foto_url, ic.nombre as item_nombre\n                        FROM detalle_alistamiento da\n                        JOIN items_checklist ic ON da.item_id = ic.id\n                        WHERE da.alistamiento_id = ? AND da.foto_url IS NOT NULL AND da.foto_url <> ''\n                        ORDER BY ic.categoria, ic.nombre\n                    ");
                    $stmtPF->execute([$prevId]);
                    $prevFotos = $stmtPF->fetchAll();
                } catch (Exception $e) {
                    $prevFotos = [];
                }
                if (empty($prevFotos)) {
                    $stmtPF = $db->prepare("\n                        SELECT da.foto_url, CONCAT('Evidencia #', da.id) AS item_nombre\n                        FROM detalle_alistamiento da\n                        WHERE da.alistamiento_id = ? AND da.foto_url IS NOT NULL AND da.foto_url <> ''\n                        ORDER BY da.id ASC\n                    ");
                    $stmtPF->execute([$prevId]);
                    $prevFotos = $stmtPF->fetchAll();
                }
                if (!empty($prevFotos)) {
                    echo '<div class="mb-3">';
                    echo '<h5 class="mb-3"><i class="fas fa-layer-group me-2"></i>Evidencias de la primera revisión</h5>';
                    echo '<div class="row">';
                    foreach ($prevFotos as $foto) {
                        echo '<div class="col-md-6 mb-3">';
                        echo '<div class="card">';
                        echo '<img src="../' . htmlspecialchars($foto['foto_url']) . '" class="card-img-top" style="height: 200px; object-fit: cover;" onclick="mostrarEvidencia(\'' . htmlspecialchars($foto['foto_url']) . '\', \'' . htmlspecialchars($foto['item_nombre']) . '\')">';
                        echo '<div class="card-body">';
                        echo '<h6 class="card-title">' . htmlspecialchars($foto['item_nombre']) . '</h6>';
                        if (!empty($foto['observaciones'] ?? '')) {
                            echo '<p class="card-text"><small class="text-muted">' . htmlspecialchars($foto['observaciones'] ?? '') . '</small></p>';
                        }
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            } catch (Exception $e) { /* ignorar */ }
        }

        // Si nada se imprimió, informar
        if (empty($fotos) && (!isset($cats) || empty($cats)) && (!isset($prevFotos) || empty($prevFotos))) {
            echo '<div class="alert alert-info">No hay evidencias fotográficas para este alistamiento</div>';
        }

        exit;
    }
    ?>
    
    <div class="text-center mt-4 py-3 border-top">
        <small class="text-muted">
            © 2025 <strong>BLACK CROWSOFT</strong> - Todos los derechos reservados
        </small>
    </div>
</body>
</html>
