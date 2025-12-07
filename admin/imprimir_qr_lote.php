<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

verificarSesion(['admin']);

$db = getDB();
$soloActivos = isset($_GET['solo_activos']) ? (int)$_GET['solo_activos'] : 1;
$tam = isset($_GET['tam']) ? (int)$_GET['tam'] : 180; // tamaño del QR en px

$rows = [];
try {
    if ($soloActivos) {
        $rows = $db->fetchAll("SELECT id, numero_interno, placa, codigo_qr FROM vehiculos WHERE estado = 'activo' ORDER BY numero_interno ASC", []);
    } else {
        $rows = $db->fetchAll("SELECT id, numero_interno, placa, codigo_qr FROM vehiculos WHERE estado != 'eliminado' ORDER BY numero_interno ASC", []);
    }
} catch (Exception $e) { $rows = []; }

// Contadores
$totalVehiculos = count($rows);
$generados = 0;
foreach ($rows as $v) { if (!empty($v['codigo_qr'])) { $generados++; } }
$faltantes = max(0, $totalVehiculos - $generados);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Códigos QR - SIGAV</title>
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

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .qr-card { background: #23354f; border-radius: 10px; padding: 10px; text-align: center; color: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.15); }
        .qr-card .qr-box { background: #fff; border-radius: 8px; padding: 10px; display: inline-block; }
        .qr-card img { width: <?= max(120, $tam) ?>px; height: <?= max(120, $tam) ?>px; object-fit: contain; }
        .qr-card .label { margin-top: 8px; background: #fff; color: #000; border-radius: 8px; padding: 6px 10px; display: inline-block; font-weight: 600; }
        .qr-card .label .ico { margin-right: 6px; color: #0b1e3f; }

        @media print {
            body { background: none; }
            .sidebar, .no-print { display: none !important; }
            .grid { gap: 10px; }
            .qr-card { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
    <?php if (isset($_GET['auto_print']) && $_GET['auto_print'] == '1'): ?>
    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 800));
    </script>
    <?php endif; ?>
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
                <h3 class="text-white"><i class="fas fa-qrcode"></i> Imprimir Códigos QR de Vehículos</h3>
                <div class="no-print d-flex gap-2">
                    <a href="?solo_activos=<?= $soloActivos ? 0 : 1 ?>" class="btn btn-outline-light"><i class="fas fa-filter"></i> <?= $soloActivos ? 'Mostrar todos' : 'Solo activos' ?></a>
                    <button id="btnGenFaltantes" class="btn btn-warning" title="Generar QR para vehículos sin código" onclick="generarFaltantes()"><i class="fas fa-wand-magic-sparkles"></i> Generar faltantes</button>
                    <button id="btnRegenTodos" class="btn btn-danger" title="Regenerar todos los QR del listado" onclick="regenerarTodos()"><i class="fas fa-rotate"></i> Regenerar todos</button>
                    <a href="?solo_activos=<?= $soloActivos ?>&tam=<?= $tam ?>&auto_print=1" class="btn btn-outline-success"><i class="fas fa-print"></i> Imprimir</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Vehículos: <?= $totalVehiculos ?> — Generados: <?= $generados ?> — Faltantes: <?= $faltantes ?></h5></div>
                <div class="card-body">
                    <div class="grid">
                        <?php foreach ($rows as $v): ?>
                        <div class="qr-card">
                            <div class="qr-box">
                                <?php if (!empty($v['codigo_qr'])): ?>
                                    <img src="../<?= htmlspecialchars($v['codigo_qr']) ?>" alt="QR <?= htmlspecialchars($v['numero_interno']) ?>">
                                <?php else: ?>
                                    <div style="width: <?= max(120, $tam) ?>px; height: <?= max(120, $tam) ?>px; display:flex; align-items:center; justify-content:center; background:#eee; color:#666; border-radius:8px;">
                                        QR no generado
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="label"><i class="fas fa-id-card ico"></i><?= htmlspecialchars($v['numero_interno']) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?>
                        <div class="text-muted">No hay vehículos para mostrar.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
 </div>
</body>
<script>
function generarFaltantes() {
    const btn = document.getElementById('btnGenFaltantes');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

    const params = new URLSearchParams({
        solo_activos: '<?= $soloActivos ?>',
        tam: '<?= $tam ?>',
        csrf_token: '<?= generarTokenCSRF() ?>'
    });

    fetch('generar_qr_lote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            alert(`Generados: ${data.generados} de ${data.total}`);
            location.reload();
        } else {
            alert('Error al generar QR: ' + (data.error || 'Desconocido'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generar faltantes';
        }
    })
    .catch(err => {
        alert('Fallo de red: ' + err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generar faltantes';
    });
}
</script>
<script>
function regenerarTodos() {
    if (!confirm('Esto regenerará TODOS los QR del listado. ¿Continuar?')) return;
    const btn = document.getElementById('btnRegenTodos');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Regenerando...';

    const params = new URLSearchParams({
        solo_activos: '<?= $soloActivos ?>',
        tam: '<?= $tam ?>',
        regenerar_todos: '1',
        csrf_token: '<?= generarTokenCSRF() ?>'
    });

    fetch('generar_qr_lote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            alert(`Regenerados: ${data.regenerados} de ${data.total}`);
            location.reload();
        } else {
            alert('Error al regenerar QR: ' + (data.error || 'Desconocido'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rotate"></i> Regenerar todos';
        }
    })
    .catch(err => {
        alert('Fallo de red: ' + err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-rotate"></i> Regenerar todos';
    });
}
</script>
</html>