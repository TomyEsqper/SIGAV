<?php
/**
 * Módulo Administrativo - Reportes - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2025 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación y permisos
verificarSesion(['admin']);

$db = getDB();
$current = 'reportes.php';

// Filtros coherentes para reportes
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$tipo_doc = trim($_GET['tipo_doc'] ?? ''); // soat, tecnomecanica, tarjeta_operacion, extintor
$estado_doc = trim($_GET['estado_doc'] ?? ''); // verde, azul, amarillo, rojo
$estado_veh = trim($_GET['estado_veh'] ?? ''); // activo, detenido, mantenimiento
$buscar = trim($_GET['buscar'] ?? ''); // placa o número interno

// Construir querystring para exportaciones conservando filtros
$queryStr = http_build_query([
    'desde' => $desde,
    'hasta' => $hasta,
    'tipo_doc' => $tipo_doc,
    'estado_doc' => $estado_doc,
    'estado_veh' => $estado_veh,
    'buscar' => $buscar,
]);
// Métricas base con tolerancia a esquemas
$alistamientosHoy = 0;
$vehiculosActivos = 0;
$documentosPorVencer = 0;
$documentosVencidos = 0;
$chartLabels = [];
$chartData = [];
$errorMsg = '';

try {
    $alistamientosHoy = (int)($db->fetch("SELECT COUNT(*) AS c FROM alistamientos WHERE DATE(fecha_alistamiento) = CURDATE()")['c'] ?? 0);
} catch (Exception $e) { $errorMsg = $errorMsg ?: 'Error cargando métrica de alistamientos.'; }

try {
    $vehiculosActivos = (int)($db->fetch("SELECT COUNT(*) AS c FROM vehiculos WHERE estado = 'activo'")['c'] ?? 0);
} catch (Exception $e) { $errorMsg = $errorMsg ?: 'Error cargando métrica de vehículos.'; }

try {
    $documentosPorVencer = (int)($db->fetch("SELECT COUNT(*) AS c FROM documentos WHERE fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")['c'] ?? 0);
    $documentosVencidos = (int)($db->fetch("SELECT COUNT(*) AS c FROM documentos WHERE fecha_vencimiento < CURDATE()")['c'] ?? 0);
} catch (Exception $e) { $errorMsg = $errorMsg ?: 'Error cargando métricas de documentos.'; }

// Datos para gráfico: alistamientos últimos 7 días (con filtros)
try {
    $params = [];
    $where = [];
    if ($desde !== '' && $hasta !== '') {
        $where[] = "DATE(fecha_alistamiento) BETWEEN ? AND ?";
        $params[] = $desde; $params[] = $hasta;
    } else {
        $where[] = "fecha_alistamiento >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
    }
    if ($buscar !== '') {
        $where[] = "vehiculo_id IN (SELECT id FROM vehiculos WHERE placa LIKE ? OR numero_interno LIKE ?)";
        $params[] = "%$buscar%"; $params[] = "%$buscar%";
    }
    if ($estado_veh !== '') {
        $where[] = "vehiculo_id IN (SELECT id FROM vehiculos WHERE estado = ?)";
        $params[] = $estado_veh;
    }
    $sql = "SELECT DATE(fecha_alistamiento) AS dia, COUNT(*) AS cnt FROM alistamientos";
    if ($where) { $sql .= " WHERE " . implode(" AND ", $where); }
    $sql .= " GROUP BY DATE(fecha_alistamiento) ORDER BY dia ASC";
    $rows = $db->fetchAll($sql, $params);

    // Construir labels desde los últimos 7 días o rango
    $labelsBase = [];
    if ($desde !== '' && $hasta !== '') {
        $start = new DateTime($desde);
        $end = new DateTime($hasta);
        $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
        foreach ($period as $d) { $labelsBase[] = $d; }
    } else {
        for ($i = 6; $i >= 0; $i--) { $labelsBase[] = (new DateTime())->modify("-$i day"); }
    }
    foreach ($labelsBase as $d) {
        $label = $d->format('d/m');
        $key = $d->format('Y-m-d');
        $chartLabels[] = $label;
        $match = array_values(array_filter($rows, fn($r) => ($r['dia'] ?? '') === $key));
        $chartData[] = (int)($match[0]['cnt'] ?? 0);
    }
} catch (Exception $e) {
    $chartLabels = [];
    $chartData = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link href="../assets/css/chart.min.css" rel="stylesheet">
    <style>
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; position: relative; }
        body::before { content: ""; position: fixed; inset: 0; background: rgba(11, 30, 63, 0.55); z-index: 0; }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; }
        .sidebar { min-height: 100vh; position: sticky; top: 0; overflow: hidden; background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%); color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 10px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .stat-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border: none; transition: transform 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-icon.yellow { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .stat-icon.red { background: linear-gradient(135deg, #dc3545, #e83e8c); }
        .stat-icon.blue { background: linear-gradient(135deg, #007bff, #6f42c1); }
        .chart-container { position: relative; height: 320px; }
        .chart-container canvas { max-height: 100%; }

        /* Mejora de legibilidad de títulos sobre el fondo */
        h1, h2, h5 {
            display: inline-block;
            background: rgba(255, 255, 255, 0.85);
            padding: 6px 12px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.2);
            backdrop-filter: saturate(150%) blur(2px);
        }
        h3 {
            display: inline-block;
            background: rgba(255, 255, 255, 0.75);
            padding: 6px 12px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.18);
            backdrop-filter: saturate(150%) blur(2px);
        }
        /* Evitar aplicar estilo dentro de tarjetas/headers */
        .card-body h3,
        .card-header h5 {
            background: transparent;
            box-shadow: none;
            padding: 0;
            border-radius: 0;
        }
    </style>
    <script defer src="../assets/js/chart.min.js"></script>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar consistente con el Dashboard -->
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <div class="p-3">
                        <div class="text-center mb-3">
                            <img src="../logo.png" alt="SIGAV" class="sidebar-logo mb-2">
                            <h4 class="text-white">SIGAV</h4>
                            <small class="text-light"><a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-light text-decoration-none">BLACKCROWSOFT.COM</a></small>
                        </div>
                        <?php $current = basename($_SERVER['PHP_SELF']); ?>
                        <nav class="nav flex-column">
                            <a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a class="nav-link <?= $current === 'vehiculos.php' ? 'active' : '' ?>" href="vehiculos.php">
                                <i class="fas fa-truck"></i> Vehículos
                            </a>
                            <a class="nav-link <?= $current === 'conductores.php' ? 'active' : '' ?>" href="conductores.php">
                                <i class="fas fa-users"></i> Conductores
                            </a>
                            <a class="nav-link <?= $current === 'documentos.php' ? 'active' : '' ?>" href="documentos.php">
                                <i class="fas fa-file-alt"></i> Documentos
                            </a>
                            <a class="nav-link <?= $current === 'alistamientos.php' ? 'active' : '' ?>" href="alistamientos.php">
                                <i class="fas fa-clipboard-check"></i> Alistamientos
                            </a>
                            <a class="nav-link <?= $current === 'reportes.php' ? 'active' : '' ?>" href="reportes.php">
                                <i class="fas fa-chart-bar"></i> Reportes
                            </a>
                            <a class="nav-link <?= $current === 'camaras.php' ? 'active' : '' ?>" href="camaras.php">
                                <i class="fas fa-video"></i> Cámaras
                            </a>
                            <a class="nav-link <?= $current === 'evasion.php' ? 'active' : '' ?>" href="evasion.php">
                                <i class="fas fa-user-secret"></i> Evasión
                            </a>
                            <hr class="text-light">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-10 px-md-4">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-bar"></i> Reportes</h1>
                </div>

                <!-- Filtros -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form class="row g-3" method="get" action="reportes.php">
                            <div class="col-md-3">
                                <label class="form-label">Desde</label>
                                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo de documento</label>
                                <select name="tipo_doc" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="soat" <?= $tipo_doc==='soat'?'selected':'' ?>>SOAT</option>
                                    <option value="tecnomecanica" <?= $tipo_doc==='tecnomecanica'?'selected':'' ?>>Tecnomecánica</option>
                                    <option value="tarjeta_operacion" <?= $tipo_doc==='tarjeta_operacion'?'selected':'' ?>>Tarjeta de operación</option>
                                    <option value="extintor" <?= $tipo_doc==='extintor'?'selected':'' ?>>Extintor</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado de documentos</label>
                                <select name="estado_doc" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="verde" <?= $estado_doc==='verde'?'selected':'' ?>>Vigente</option>
                                    <option value="azul" <?= $estado_doc==='azul'?'selected':'' ?>>Próximo a vencer</option>
                                    <option value="amarillo" <?= $estado_doc==='amarillo'?'selected':'' ?>>Por vencer</option>
                                    <option value="rojo" <?= $estado_doc==='rojo'?'selected':'' ?>>Vencido</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado del vehículo</label>
                                <select name="estado_veh" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="activo" <?= $estado_veh==='activo'?'selected':'' ?>>Activo</option>
                                    <option value="detenido" <?= $estado_veh==='detenido'?'selected':'' ?>>Detenido</option>
                                    <option value="mantenimiento" <?= $estado_veh==='mantenimiento'?'selected':'' ?>>Mantenimiento</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Buscar vehículo (placa/interno)</label>
                                <input type="text" name="buscar" class="form-control" placeholder="ABC123 / 001" value="<?= htmlspecialchars($buscar) ?>">
                            </div>
                            <div class="col-md-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar filtros</button>
                                <a class="btn btn-outline-secondary" href="reportes.php"><i class="fas fa-eraser"></i> Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tarjetas de ejemplo -->
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Alistamientos Hoy</p>
                                    <h3 class="mb-0"><?= htmlspecialchars((string)$alistamientosHoy) ?></h3>
                                </div>
                                <div class="stat-icon blue">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Vehículos Activos</p>
                                    <h3 class="mb-0"><?= htmlspecialchars((string)$vehiculosActivos) ?></h3>
                                </div>
                                <div class="stat-icon blue">
                                    <i class="fas fa-bus"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Documentos por Vencer</p>
                                    <h3 class="mb-0"><?= htmlspecialchars((string)$documentosPorVencer) ?></h3>
                                </div>
                                <div class="stat-icon blue">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Documentos Vencidos</p>
                                    <h3 class="mb-0"><?= htmlspecialchars((string)$documentosVencidos) ?></h3>
                                </div>
                                <div class="stat-icon blue">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Alistamientos -->
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Alistamientos últimos 7 días</h5>
                            <div class="btn-group">
                                <a class="btn btn-sm btn-outline-secondary" href="exportar_reporte.php?tipo=alistamientos7&formato=csv<?= $queryStr ? '&'.$queryStr : '' ?>">
                                    <i class="fas fa-file-csv"></i> Exportar CSV
                                </a>
                                <a class="btn btn-sm btn-outline-primary" href="exportar_reporte.php?tipo=alistamientos7&formato=pdf<?= $queryStr ? '&'.$queryStr : '' ?>">
                                    <i class="fas fa-file-pdf"></i> Descargar PDF
                                </a>
                            </div>
                        </div>
                        <div class="chart-container mt-3">
                            <canvas id="reportesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Documentos por estado -->
                <?php
                // Estadísticas adicionales
                $docsEstados = ['verde'=>0,'azul'=>0,'amarillo'=>0,'rojo'=>0];
                try {
                    $params = [];
                    $where = [];
                    if ($tipo_doc !== '') { $where[] = "d.tipo = ?"; $params[] = $tipo_doc; }
                    if ($desde !== '') { $where[] = "DATE(d.fecha_vencimiento) >= ?"; $params[] = $desde; }
                    if ($hasta !== '') { $where[] = "DATE(d.fecha_vencimiento) <= ?"; $params[] = $hasta; }
                    if ($buscar !== '') { $where[] = "(v.placa LIKE ? OR v.numero_interno LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
                    if ($estado_veh !== '') { $where[] = "v.estado = ?"; $params[] = $estado_veh; }
                    $sql = "SELECT d.fecha_vencimiento FROM documentos d JOIN vehiculos v ON d.vehiculo_id = v.id";
                    if ($where) { $sql .= " WHERE ".implode(" AND ", $where); }
                    $rows = $db->fetchAll($sql, $params);
                    foreach ($rows as $r) {
                        $fv = $r['fecha_vencimiento'];
                        $estadoCalc = 'verde';
                        if ($fv < date('Y-m-d')) { $estadoCalc = 'rojo'; }
                        elseif ($fv <= date('Y-m-d', strtotime('+1 month'))) { $estadoCalc = 'amarillo'; }
                        elseif ($fv <= date('Y-m-d', strtotime('+2 months'))) { $estadoCalc = 'azul'; }
                        if ($estado_doc !== '' && $estadoCalc !== $estado_doc) { continue; }
                        $docsEstados[$estadoCalc]++;
                    }
                } catch (Exception $e) {}

                $vehiculosEstados = ['activo'=>0,'detenido'=>0,'mantenimiento'=>0];
                try {
                    $rows = $db->fetchAll("SELECT estado, COUNT(*) AS c FROM vehiculos GROUP BY estado");
                    foreach ($rows as $r) { $vehiculosEstados[$r['estado']] = (int)$r['c']; }
                } catch (Exception $e) {}

                $conductoresEstados = ['activos'=>0,'inactivos'=>0];
                try {
                    $rows = $db->fetchAll("SELECT activo, COUNT(*) AS c FROM conductores GROUP BY activo");
                    foreach ($rows as $r) { if (($r['activo'] ?? 0)==1) $conductoresEstados['activos']=(int)$r['c']; else $conductoresEstados['inactivos']=(int)$r['c']; }
                } catch (Exception $e) {}
                ?>

                <div class="row mt-4 g-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Documentos por estado</h5>
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-secondary" href="exportar_reporte.php?tipo=documentos_estado&formato=csv<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-csv"></i> CSV</a>
                                        <a class="btn btn-sm btn-outline-primary" href="exportar_reporte.php?tipo=documentos_estado&formato=pdf<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-pdf"></i> PDF</a>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between"><span>Vigentes</span><strong><?= $docsEstados['verde'] ?></strong></li>
                                        <li class="list-group-item d-flex justify-content-between"><span>Próximos a vencer</span><strong><?= $docsEstados['azul'] ?></strong></li>
                                        <li class="list-group-item d-flex justify-content-between"><span>Por vencer</span><strong><?= $docsEstados['amarillo'] ?></strong></li>
                                        <li class="list-group-item d-flex justify-content-between"><span>Vencidos</span><strong><?= $docsEstados['rojo'] ?></strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Vehículos por estado</h5>
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-secondary" href="exportar_reporte.php?tipo=vehiculos_estado&formato=csv<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-csv"></i> CSV</a>
                                        <a class="btn btn-sm btn-outline-primary" href="exportar_reporte.php?tipo=vehiculos_estado&formato=pdf<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-pdf"></i> PDF</a>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between"><span>Activos</span><strong><?= $vehiculosEstados['activo'] ?></strong></li>
                                        <li class="list-group-item d-flex justify-content-between"><span>Detenidos</span><strong><?= $vehiculosEstados['detenido'] ?></strong></li>
                                        <li class="list-group-item d-flex justify-content-between"><span>Mantenimiento</span><strong><?= $vehiculosEstados['mantenimiento'] ?></strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Conductores</h5>
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-secondary" href="exportar_reporte.php?tipo=conductores_estado&formato=csv<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-csv"></i> CSV</a>
                                        <a class="btn btn-sm btn-outline-primary" href="exportar_reporte.php?tipo=conductores_estado&formato=pdf<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-pdf"></i> PDF</a>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between"><span>Activos</span><strong><?= $conductoresEstados['activos'] ?></strong></li>
                                        <li class="list-group-item d-flex justify-content-between"><span>Inactivos</span><strong><?= $conductoresEstados['inactivos'] ?></strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reportes útiles con detalle -->
                <div class="row mt-4 g-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Documentos por vencer (Top 10)</h5>
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-secondary" href="exportar_reporte.php?tipo=documentos_vencer_top&formato=csv<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-csv"></i> CSV</a>
                                        <a class="btn btn-sm btn-outline-primary" href="exportar_reporte.php?tipo=documentos_vencer_top&formato=pdf<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-pdf"></i> PDF</a>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <?php
                                    $params = [];
                                    $where = ["d.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"];
                                    if ($tipo_doc !== '') { $where[] = "d.tipo = ?"; $params[] = $tipo_doc; }
                                    if ($buscar !== '') { $where[] = "(v.placa LIKE ? OR v.numero_interno LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
                                    if ($estado_veh !== '') { $where[] = "v.estado = ?"; $params[] = $estado_veh; }
                                    if ($desde !== '') { $where[] = "DATE(d.fecha_vencimiento) >= ?"; $params[] = $desde; }
                                    if ($hasta !== '') { $where[] = "DATE(d.fecha_vencimiento) <= ?"; $params[] = $hasta; }
                                    $sql = "SELECT d.*, v.numero_interno, v.placa, DATEDIFF(d.fecha_vencimiento, CURDATE()) as dias_restantes FROM documentos d JOIN vehiculos v ON d.vehiculo_id = v.id";
                                    if ($where) { $sql .= " WHERE ".implode(" AND ", $where); }
                                    $sql .= " ORDER BY d.fecha_vencimiento ASC LIMIT 10";
                                    $docsvencer = $db->fetchAll($sql, $params);
                                    ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($docsvencer as $d): ?>
                                            <?php 
                                                $dias = (int)($d['dias_restantes'] ?? 0);
                                                $diasClass = ($dias < 0) ? 'text-danger' : (($dias <= 7) ? 'text-warning' : (($dias <= 30) ? 'text-info' : 'text-success'));
                                            ?>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>
                                                    <?= htmlspecialchars($d['tipo']) ?> • <?= htmlspecialchars($d['placa']) ?> (<?= htmlspecialchars($d['numero_interno']) ?>)
                                                </span>
                                                <span class="<?= $diasClass ?>">Vence: <?= htmlspecialchars($d['fecha_vencimiento']) ?> (<?= $dias ?> días)</span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (empty($docsvencer)): ?>
                                            <li class="list-group-item text-muted">Sin resultados con los filtros actuales.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Vehículos con extintor vencido</h5>
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-secondary" href="exportar_reporte.php?tipo=extintor_vencido&formato=csv<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-csv"></i> CSV</a>
                                        <a class="btn btn-sm btn-outline-primary" href="exportar_reporte.php?tipo=extintor_vencido&formato=pdf<?= $queryStr ? '&'.$queryStr : '' ?>"><i class="fas fa-file-pdf"></i> PDF</a>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <?php
                                    $params = [];
                                    $where = ["d.tipo = 'extintor'", "d.fecha_vencimiento < CURDATE()"];
                                    if ($buscar !== '') { $where[] = "(v.placa LIKE ? OR v.numero_interno LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
                                    if ($estado_veh !== '') { $where[] = "v.estado = ?"; $params[] = $estado_veh; }
                                    $sql = "SELECT v.placa, v.numero_interno, d.fecha_vencimiento FROM documentos d JOIN vehiculos v ON d.vehiculo_id = v.id";
                                    $sql .= " WHERE ".implode(" AND ", $where)." ORDER BY d.fecha_vencimiento ASC";
                                    $extVencidos = $db->fetchAll($sql, $params);
                                    ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($extVencidos as $ev): ?>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span><?= htmlspecialchars($ev['placa']) ?> (<?= htmlspecialchars($ev['numero_interno']) ?>)</span>
                                                <span class="text-danger">Vencido: <?= htmlspecialchars($ev['fecha_vencimiento']) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (empty($extVencidos)): ?>
                                            <li class="list-group-item text-muted">Sin resultados con los filtros actuales.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('reportesChart');
            const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
            const dataVals = <?= json_encode($chartData, JSON_NUMERIC_CHECK) ?>;
            if (el && window.Chart) {
                const ctx = el.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Alistamientos últimos 7 días',
                            data: dataVals,
                            borderColor: 'rgba(13, 110, 253, 1)',
                            backgroundColor: 'rgba(13, 110, 253, 0.25)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }
        });
    </script>
    <?php if ($errorMsg): ?>
    <script>console.warn("<?= addslashes($errorMsg) ?>");</script>
    <?php endif; ?>
</body>
</html>