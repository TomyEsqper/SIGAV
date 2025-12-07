<?php
require_once '../config/auth.php';
require_once '../config/database.php';

verificarSesion(['admin']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: evasion.php'); exit; }

// Encabezado
$h = $db->fetch("SELECT e.*, v.numero_interno, v.placa, c.nombre AS conductor_nombre
    FROM evasion_inspecciones e
    JOIN vehiculos v ON v.id = e.vehiculo_id
    LEFT JOIN conductores c ON c.id = e.conductor_id
    WHERE e.id = ?", [$id]);
if (!$h) { header('Location: evasion.php'); exit; }

$det = $db->fetchAll("SELECT * FROM evasion_detalle WHERE inspeccion_id = ? ORDER BY id ASC", [$id]);
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
foreach ($det as $idx => $d) {
    $url = trim((string)($d['archivo_url'] ?? ''));
    if ($url !== '') {
        $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($url, DIRECTORY_SEPARATOR);
        $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        if (is_file($abs) && ($ext !== 'mp4' || stripos($abs, '_std.mp4') === false)) {
            $out = sigav_convert_to_mp4($abs, dirname($abs));
            if ($out) {
                $newUrl = 'uploads/evasion/' . basename($out);
                $db->execute('UPDATE evasion_detalle SET archivo_url = ? WHERE id = ?', [$newUrl, (int)$d['id']]);
                $det[$idx]['archivo_url'] = $newUrl;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Evasión #<?= htmlspecialchars($h['numero_informe']) ?> - SIGAV</title>
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
                <h3 class="text-white"><i class="fas fa-file-alt"></i> Detalle de revisión <?= htmlspecialchars($h['numero_informe']) ?></h3>
                <div class="btn-group">
                    <a href="evasion.php" class="btn btn-outline-light"><i class="fas fa-arrow-left"></i> Volver</a>
                    <a class="btn btn-outline-primary" href="exportar_evasion_pdf.php?id=<?= (int)$h['id'] ?>" target="_blank"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
                    <a class="btn btn-outline-success" href="exportar_evasion_docx.php?id=<?= (int)$h['id'] ?>" target="_blank"><i class="fas fa-file-word"></i> Exportar DOCX</a>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Encabezado</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3"><strong>Informe:</strong> <?= htmlspecialchars($h['numero_informe']) ?></div>
                        <div class="col-md-3"><strong>Fecha revisión:</strong> <?= htmlspecialchars($h['fecha_revision']) ?></div>
                        <div class="col-md-3"><strong>Vehículo:</strong> <?= htmlspecialchars(($h['numero_interno'] ?? '').' • '.($h['placa'] ?? '')) ?></div>
                        <div class="col-md-3"><strong>Conductor:</strong> <?= htmlspecialchars($h['conductor_nombre'] ?? '—') ?></div>
                        <div class="col-md-3"><strong>Ruta:</strong> <?= htmlspecialchars($h['ruta'] ?? '—') ?></div>
                        <div class="col-md-3"><strong>Días revisados:</strong> <?= htmlspecialchars($h['dias_revisados'] ?? '—') ?></div>
                        <div class="col-md-3"><strong>Total pasajeros:</strong> <span class="badge bg-info"><?= (int)$h['total_pasajeros'] ?></span></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Clips revisados</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Grabación</th>
                                    <th>Hora</th>
                                    <th>No. pasajeros</th>
                                    <th>Adjunto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($det as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['grabacion']) ?></td>
                                    <td><?= htmlspecialchars($d['hora'] ? date('H:i:s', strtotime($d['hora'])) : '—') ?></td>
                                    <td><span class="badge bg-secondary"><?= (int)$d['pasajeros'] ?></span></td>
                                    <td>
                                        <?php if (!empty($d['archivo_url'])): ?>
                                            <a href="../<?= htmlspecialchars($d['archivo_url']) ?>" target="_blank" class="btn btn-sm btn-outline-light"><i class="fas fa-paperclip"></i> Ver clip</a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($det)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Sin clips registrados</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr><td colspan="4" class="text-end px-3"><span class="badge bg-info">Total: <?= (int)$h['total_pasajeros'] ?></span></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
