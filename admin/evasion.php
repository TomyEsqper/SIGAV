<?php
require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación y permisos
verificarSesion(['admin']);

$db = getDB();
$current = 'evasion.php';

// Crear tablas si no existen (tolerante)
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
} catch (Exception $e) { /* silencioso */ }

// Listado simple
$rows = [];
try {
    $rows = $db->fetchAll("SELECT e.*, v.numero_interno, v.placa, c.nombre AS conductor_nombre
        FROM evasion_inspecciones e
        JOIN vehiculos v ON v.id = e.vehiculo_id
        LEFT JOIN conductores c ON c.id = e.conductor_id
        ORDER BY e.id DESC LIMIT 50");
} catch (Exception $e) { $rows = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evasión de Pasajeros - SIGAV</title>
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
                <h3 class="text-white"><i class="fas fa-user-secret"></i> Módulo de Evasión de Pasajeros</h3>
                <div>
                    <a href="evasion_nueva.php" class="btn btn-outline-light"><i class="fas fa-plus"></i> Nueva revisión</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-list"></i> Últimas revisiones (<?= count($rows) ?>)</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Informe</th>
                                    <th>Fecha revisión</th>
                                    <th>Vehículo</th>
                                    <th>Conductor</th>
                                    <th>Ruta</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['numero_informe']) ?></td>
                                    <td><?= htmlspecialchars($r['fecha_revision']) ?></td>
                                    <td><?= htmlspecialchars(($r['numero_interno'] ?? '').' • '.($r['placa'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($r['conductor_nombre'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($r['ruta'] ?? '—') ?></td>
                                    <td><span class="badge bg-info"><?= (int)$r['total_pasajeros'] ?></span></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="evasion_detalle.php?id=<?= (int)$r['id'] ?>"><i class="fas fa-eye"></i> Ver</a>
                                        <a class="btn btn-sm btn-outline-light" href="exportar_evasion_pdf.php?id=<?= (int)$r['id'] ?>" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($rows)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No hay revisiones registradas</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>