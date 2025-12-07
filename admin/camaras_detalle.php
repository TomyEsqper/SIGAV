<?php
require_once '../config/auth.php';
require_once '../config/database.php';

verificarSesion(['admin']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="es"><body><p>ID inválido</p><a href="camaras.php">Volver</a></body></html>';
    exit;
}

$header = null;
try {
    $header = $db->fetch("SELECT ci.*, v.numero_interno, v.placa, u.nombre AS inspector_nombre, u.usuario AS inspector_usuario
        FROM camaras_inspecciones ci
        JOIN vehiculos v ON v.id = ci.vehiculo_id
        JOIN usuarios u ON u.id = ci.inspector_id
        WHERE ci.id = ?", [$id]);
} catch (Exception $e) {
    $header = null;
}
if (!$header) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="es"><body><p>Inspección no encontrada</p><a href="camaras.php">Volver</a></body></html>';
    exit;
}

$detalles = [];
try {
    $detalles = $db->fetchAll("SELECT * FROM camaras_inspeccion_detalle WHERE inspeccion_id = ? ORDER BY id ASC", [$id]);
} catch (Exception $e) {
    $detalles = [];
}

// Mapear evidencias por detalle
$evidenciasPorDetalle = [];
foreach ($detalles as $d) {
    try {
        $evs = $db->fetchAll("SELECT * FROM camaras_evidencias WHERE detalle_id = ? ORDER BY id ASC", [$d['id']]);
        $evidenciasPorDetalle[$d['id']] = $evs;
    } catch (Exception $e) {
        $evidenciasPorDetalle[$d['id']] = [];
    }
}
function sigav_quote($arg) { return '"' . str_replace('"', '\"', $arg) . '"'; }
function sigav_find_ffmpeg() {
    $local = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . (stripos(PHP_OS, 'WIN') === 0 ? 'ffmpeg.exe' : 'ffmpeg');
    if (file_exists($local)) return $local;
    $cands = ['C:\\ffmpeg\\bin\\ffmpeg.exe','C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe','C:\\Program Files\\FFmpeg\\bin\\ffmpeg.exe','/usr/bin/ffmpeg','/usr/local/bin/ffmpeg','/opt/homebrew/bin/ffmpeg'];
    foreach ($cands as $c) { if (file_exists($c)) return $c; }
    return null;
}
function sigav_convert_to_mp4($inputPath, $outDir) {
    $ff = sigav_find_ffmpeg(); if (!$ff || !file_exists($inputPath)) return null; @set_time_limit(120);
    $outfile = $outDir . DIRECTORY_SEPARATOR . pathinfo($inputPath, PATHINFO_FILENAME) . '_std.mp4';
    $cmd = sigav_quote($ff) . ' -y -i ' . sigav_quote($inputPath) . ' -loglevel error -c:v libx264 -preset veryfast -crf 23 -pix_fmt yuv420p -c:a aac -b:a 128k -movflags +faststart ' . sigav_quote($outfile);
    @exec($cmd, $o, $code);
    return (file_exists($outfile) && @filesize($outfile) > 0) ? $outfile : null;
}
foreach ($evidenciasPorDetalle as $did => $evs) {
    foreach ($evs as $idx => $e) {
        if (($e['tipo'] ?? 'foto') === 'video') {
            $url = trim((string)($e['archivo_url'] ?? ''));
            if ($url !== '') {
                $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($url, DIRECTORY_SEPARATOR);
                $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
                if (is_file($abs) && ($ext !== 'mp4' || stripos($abs, '_std.mp4') === false)) {
                    $out = sigav_convert_to_mp4($abs, dirname($abs));
                    if ($out) {
                        $newUrl = 'uploads/camaras/' . basename($out);
                        try { $db->execute('UPDATE camaras_evidencias SET archivo_url = ? WHERE id = ?', [$newUrl, (int)$e['id']]); } catch (Exception $er) {}
                        $evidenciasPorDetalle[$did][$idx]['archivo_url'] = $newUrl;
                    }
                }
            }
        }
    }
}

// Construir resumen textual del reporte
$itemsMalo = [];
$evidenciasMalo = [];
foreach ($detalles as $d) {
    if (strtolower($d['estado']) === 'malo') {
        $evs = $evidenciasPorDetalle[$d['id']] ?? [];
        $itemsMalo[] = [
            'key' => $d['item_key'],
            'obs' => $d['observaciones'] ?? '',
            'count' => count($evs),
            'id' => $d['id']
        ];
        // Guardar evidencias de tipo foto para el bloque documento
        foreach ($evs as $e) {
            if (($e['tipo'] ?? 'foto') === 'foto') {
                $evidenciasMalo[] = $e;
            }
        }
    }
}
$fechaFmt = htmlspecialchars(date('d/m/Y H:i', strtotime($header['fecha'] ?? 'now')));
$ni = htmlspecialchars($header['numero_interno'] ?? '');
$placa = htmlspecialchars($header['placa'] ?? '');
$insp = htmlspecialchars(($header['inspector_nombre'] ?: $header['inspector_usuario']) ?? '');
$estado = strtolower($header['estado_final'] ?? 'verde');
$estadoTxt = strtoupper($estado);
$manipulado = ((int)($header['manipulado_conductor'] ?? 0)) ? 'Sí' : 'No';
$tipoNovedad = htmlspecialchars($header['tipo_novedad'] ?? 'otro');
$obsGeneral = trim($header['observaciones'] ?? '');
$reporteText = "Se realiza inspección del sistema de cámaras del vehículo NI $ni placa $placa el $fechaFmt por el inspector $insp. Estado final: $estadoTxt.";
$reporteText .= " Manipulado por conductor: $manipulado.";
$reporteText .= " Tipo de novedad: $tipoNovedad.";
if ($obsGeneral !== '') { $reporteText .= " Observaciones: " . htmlspecialchars($obsGeneral) . "."; }
if (!empty($itemsMalo)) {
    $reporteText .= " Ítems con novedad (MALO): ";
    foreach ($itemsMalo as $it) {
        $cnt = (int)$it['count'];
        $obs = trim($it['obs'] ?? '');
        $reporteText .= strtoupper($it['key']);
        if ($obs !== '') { $reporteText .= " - Obs: " . preg_replace('/\s+/', ' ', htmlspecialchars($obs)); }
        $reporteText .= " (" . $cnt . " evidencia" . ($cnt===1?"":"s") . "); ";
    }
} else {
    $reporteText .= " No se registran ítems con novedad (MALO).";
}

// Versión HTML tipo documento corporativo
$reporteHTML = '<div class="doc-report">'
    . '<div class="doc-header">'
        . '<div class="doc-brand"><img src="../logo.png" alt="SIGAV"/><span>SIGAV • BLACKCROWSOFT.COM</span></div>'
        . '<div class="doc-title">REPORTE DE INSPECCIÓN DE CÁMARAS</div>'
        . '<div class="doc-subtitle">Vehículo NI ' . $ni . ' • Placa ' . $placa . ' • Fecha ' . $fechaFmt . '</div>'
    . '</div>'
    . '<div class="doc-meta">'
        . '<div><strong>Inspector:</strong> ' . $insp . '</div>'
        . '<div><strong>Estado final:</strong> ' . $estadoTxt . '</div>'
        . '<div><strong>Manipulado por conductor:</strong> ' . $manipulado . '</div>'
        . '<div><strong>Tipo de novedad:</strong> ' . $tipoNovedad . '</div>'
    . '</div>'
    . '<div class="doc-section">'
        . '<div class="doc-section-title">Descripción del caso</div>'
        . '<p>' . ($obsGeneral !== '' ? htmlspecialchars($obsGeneral) : 'Sin observaciones generales registradas.') . '</p>'
    . '</div>'
    . '<div class="doc-section">'
        . '<div class="doc-section-title">Ítems con novedad (MALO)</div>';
if (!empty($itemsMalo)) {
    $reporteHTML .= '<table class="doc-table"><thead><tr><th>Ítem</th><th>Observaciones</th><th>Evidencias</th></tr></thead><tbody>';
    foreach ($itemsMalo as $it) {
        $reporteHTML .= '<tr><td>' . htmlspecialchars(strtoupper($it['key'])) . '</td>'
            . '<td>' . ($it['obs'] ? nl2br(htmlspecialchars($it['obs'])) : '—') . '</td>'
            . '<td>' . (int)$it['count'] . '</td></tr>';
    }
    $reporteHTML .= '</tbody></table>';
} else {
    $reporteHTML .= '<div class="text-muted">No se registran ítems en estado MALO.</div>';
}
// Galería compacta de evidencias (fotos) para acompañar el documento
if (!empty($evidenciasMalo)) {
    $reporteHTML .= '<div class="doc-section"><div class="doc-section-title">Evidencias fotográficas</div><div class="doc-evidencias">';
    $maxFotos = 12; $i=0;
    foreach ($evidenciasMalo as $e) {
        if ($i >= $maxFotos) break;
        $url = '/' . ltrim($e['archivo_url'], '/');
        $reporteHTML .= '<a href="' . htmlspecialchars($url) . '" target="_blank"><img src="' . htmlspecialchars($url) . '" alt="Evidencia"></a>';
        $i++;
    }
    if (count($evidenciasMalo) > $maxFotos) {
        $reporteHTML .= '<div class="mt-2"><small class="text-muted">+' . (count($evidenciasMalo) - $maxFotos) . ' evidencias adicionales en el detalle.</small></div>';
    }
    $reporteHTML .= '</div></div>';
}
$reporteHTML .= '</div>'; // cierre doc-report
$class = $estado==='rojo' ? 'badge-rojo' : ($estado==='amarillo' ? 'badge-amarillo' : 'badge-verde');
$inicio = $header['foto_inicio_url'] ? ('/' . ltrim($header['foto_inicio_url'], '/')) : '';
$fin = $header['foto_fin_url'] ? ('/' . ltrim($header['foto_fin_url'], '/')) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Inspección Cámaras - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; position: relative; }
        body::before { content: ''; position: fixed; inset: 0; background: rgba(11, 30, 63, 0.55); z-index: 0; }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .sidebar { min-height: 100vh; position: sticky; top: 0; overflow: hidden; background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%); color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 10px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .badge-estado { padding: 6px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-verde { background: #d4edda; color: #155724; }
        .badge-amarillo { background: #fff3cd; color: #856404; }
        .badge-rojo { background: #f8d7da; color: #721c24; }
        .foto { max-width: 320px; max-height: 240px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
        .evidencia-img { width: 260px; height: 180px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
        .evidencia-video { width: 260px; height: 180px; border-radius: 8px; border: 1px solid #ddd; }
         /* Estilos documento corporativo */
         .doc-report { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); padding: 20px; }
         .doc-header { border-bottom: 2px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 16px; }
         .doc-brand { display:flex; align-items:center; gap:10px; color:#1f2937; font-weight:600; }
         .doc-brand img { height:32px; }
         .doc-title { font-size:20px; font-weight:700; color:#0b1e3f; margin-top:6px; }
         .doc-subtitle { font-size:12px; color:#4b5563; }
         .doc-meta { display:grid; grid-template-columns: repeat(2, 1fr); gap:8px; font-size:13px; color:#111827; }
         .doc-section { margin-top:16px; }
         .doc-section-title { font-weight:700; color:#0b1e3f; border-left:4px solid #1d4ed8; padding-left:8px; margin-bottom:8px; }
         .doc-table { width:100%; border-collapse: collapse; }
         .doc-table th { background:#1d4ed8; color:#fff; font-weight:600; padding:8px; border:1px solid #1d4ed8; }
         .doc-table td { padding:8px; border:1px solid #e5e7eb; vertical-align: top; }
         .doc-evidencias { display:flex; flex-wrap:wrap; gap:10px; }
         .doc-evidencias img { width:140px; height:100px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb; }
     </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky p-3 text-white">
                    <div class="text-center mb-4">
                        <img src="../logo.png" alt="SIGAV Logo" class="img-fluid" style="max-height: 60px;">
                        <h5 class="mt-2" style="letter-spacing: 2px;">SIGAV</h5>
                        <small>SIGAV • BLACKCROWSOFT.COM</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="vehiculos.php"><i class="fas fa-car me-2"></i>Vehículos</a></li>
                        <li class="nav-item"><a class="nav-link" href="conductores.php"><i class="fas fa-id-card me-2"></i>Conductores</a></li>
                        <li class="nav-item"><a class="nav-link" href="alistamientos.php"><i class="fas fa-clipboard-check me-2"></i>Alistamientos</a></li>
                        <li class="nav-item"><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line me-2"></i>Reportes</a></li>
                        <li class="nav-item"><a class="nav-link" href="documentos.php"><i class="fas fa-file-alt me-2"></i>Documentos</a></li>
                        <li class="nav-item"><a class="nav-link active" href="camaras.php"><i class="fas fa-video me-2"></i>Cámaras</a></li>
                    </ul>
                </div>
            </nav>
            <!-- Sidebar actualizado -->

             <!-- Main Content -->
             <main class="col-md-9 col-lg-10 px-md-4">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-video"></i> Detalle de Inspección</h1>
                    <a class="btn btn-secondary" href="camaras.php"><i class="fas fa-arrow-left me-1"></i> Volver al listado</a>
                </div>

                <!-- Encabezado -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="fw-bold mb-1">Vehículo</div>
                                <div>NI <?= htmlspecialchars($header['numero_interno'] ?? '') ?></div>
                                <div>Placa <?= htmlspecialchars($header['placa'] ?? '') ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="fw-bold mb-1">Inspector</div>
                                <div><?= htmlspecialchars($header['inspector_nombre'] ?: $header['inspector_usuario']) ?></div>
                                <div class="mt-2 fw-bold">Fecha</div>
                                <div><?= htmlspecialchars(date('Y-m-d H:i', strtotime($header['fecha'] ?? 'now'))) ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="fw-bold mb-1">Estado final</div>
                                <span class="badge <?= $class ?>"><?= strtoupper($estado) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fotos inicio/fin -->
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0 text-white"><i class="fas fa-image"></i> Fotos</h5></div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6>Foto inicial</h6>
                                <?php if ($inicio): ?>
                                    <a href="<?= htmlspecialchars($inicio) ?>" target="_blank"><img class="foto" src="<?= htmlspecialchars($inicio) ?>" alt="Foto inicial"></a>
                                <?php else: ?>
                                    <span class="text-muted">No disponible</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6>Foto final</h6>
                                <?php if ($fin): ?>
                                    <a href="<?= htmlspecialchars($fin) ?>" target="_blank"><img class="foto" src="<?= htmlspecialchars($fin) ?>" alt="Foto final"></a>
                                <?php else: ?>
                                    <span class="text-muted">No disponible</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reporte textual -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white"><i class="fas fa-file-alt"></i> Reporte de Inspección</h5>
                        <div class="btn-group">
                            <a class="btn btn-sm btn-outline-light" href="exportar_camaras_pdf.php?id=<?= $id ?>" target="_blank"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-2"><small class="text-muted">Documento generado automáticamente para uso corporativo.</small></div>
                        <?= $reporteHTML ?>
                        <div class="mt-3">
                            <button class="btn btn-outline-secondary" id="btnCopiarReporte"><i class="fas fa-copy me-1"></i> Copiar texto</button>
                        </div>
                    </div>
                </div>

                <!-- Detalle checklist y evidencias -->
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0 text-white"><i class="fas fa-clipboard-list"></i> Checklist y evidencias</h5></div>
                    <div class="card-body">
                        <?php if (empty($detalles)): ?>
                            <p class="text-muted">No hay detalle registrado.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($detalles as $d): ?>
                                    <?php $evs = $evidenciasPorDetalle[$d['id']] ?? []; ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="p-3 border rounded-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong><?= htmlspecialchars(strtoupper($d['item_key'])) ?></strong>
                                                <?php $est = strtolower($d['estado']); $cls = $est==='malo'?'badge-rojo':'badge-verde'; ?>
                                                <span class="badge <?= $cls ?>"><?= strtoupper($est) ?></span>
                                            </div>
                                            <?php if (trim($d['observaciones'])): ?>
                                                <div class="mb-2"><small class="text-muted">Obs:</small> <?= nl2br(htmlspecialchars($d['observaciones'])) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($evs)): ?>
                                                <div class="row g-3">
                                                    <?php foreach ($evs as $e): ?>
                                                        <?php $url = '/' . ltrim($e['archivo_url'], '/'); ?>
                                                        <div class="col-md-6">
                                                            <?php if (($e['tipo'] ?? 'foto') === 'video'): ?>
                                                                <div><a href="<?= htmlspecialchars($url) ?>" target="_blank">Abrir video</a></div>
                                                            <?php else: ?>
                                                                <a href="<?= htmlspecialchars($url) ?>" target="_blank"><img class="evidencia-img" src="<?= htmlspecialchars($url) ?>" alt="Evidencia"></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted">Sin evidencias adjuntas.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
    (function(){
        var btn = document.getElementById('btnCopiarReporte');
        if (btn) {
            btn.addEventListener('click', function(){
                var container = document.querySelector('.doc-report');
                var contenido = container ? (container.innerText || container.textContent) : '';
                if (contenido) {
                    navigator.clipboard.writeText(contenido).then(function(){
                        btn.textContent = 'Copiado';
                        setTimeout(function(){ btn.innerHTML = '<i class="fas fa-copy me-1"></i> Copiar texto'; }, 1500);
                    });
                }
            });
        }
    })();
    </script>
</body>
</html>
