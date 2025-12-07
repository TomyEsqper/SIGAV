<?php
require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación y permisos
verificarSesion(['admin']);

try {
    $db = getDB();
    
    // Obtener estadísticas principales del dashboard con consultas directas y tolerancia a esquemas
    // Contadores base
    $totalVehiculos = ($db->fetch("SELECT COUNT(*) AS c FROM vehiculos")['c'] ?? 0);
    $vehiculosOperativos = ($db->fetch("SELECT COUNT(*) AS c FROM vehiculos WHERE estado = 'activo'")['c'] ?? 0);
    $vehiculosObservacion = ($db->fetch("SELECT COUNT(*) AS c FROM vehiculos WHERE estado = 'mantenimiento'")['c'] ?? 0);
    $vehiculosDetenidos = ($db->fetch("SELECT COUNT(*) AS c FROM vehiculos_detenidos WHERE estado = 'detenido' AND (fecha_liberacion IS NULL)")['c'] ?? 0);
    $alistamientosHoy = ($db->fetch("SELECT COUNT(*) AS c FROM alistamientos WHERE DATE(fecha_alistamiento) = CURDATE()")['c'] ?? 0);

    // Intentar usar procedimiento almacenado GetDashboardStats si existe (override de métricas base)
    try {
        $pdo = $db->getConnection();
        $stmt = $pdo->query("CALL GetDashboardStats()");
        $sp = $stmt->fetch();
        $stmt->closeCursor();
        if ($sp) {
            $totalVehiculos = isset($sp['total_vehiculos']) ? (int)$sp['total_vehiculos'] : $totalVehiculos;
            $vehiculosOperativos = isset($sp['vehiculos_activos']) ? (int)$sp['vehiculos_activos'] : $vehiculosOperativos;
            $vehiculosObservacion = isset($sp['vehiculos_mantenimiento']) ? (int)$sp['vehiculos_mantenimiento'] : $vehiculosObservacion;
            $alistamientosHoy = isset($sp['alistamientos_hoy']) ? (int)$sp['alistamientos_hoy'] : $alistamientosHoy;
            // $documentosPorVencer = isset($sp['documentos_por_vencer']) ? (int)$sp['documentos_por_vencer'] : null;
        }
    } catch (Throwable $e) {
        // Fallback silencioso a consultas directas si el SP no existe o falla
    }

    // Conductores puede no existir según el instalador usado; verificamos presencia segura
    $tablaConductores = $db->fetch("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'conductores'");
    $totalConductores = 0;
    if (($tablaConductores['cnt'] ?? 0) > 0) {
        $totalConductores = ($db->fetch("SELECT COUNT(*) AS c FROM conductores")['c'] ?? 0);
    }

    $stats = [
        'total_vehiculos' => (int)$totalVehiculos,
        'total_conductores' => (int)$totalConductores,
        'vehiculos_operativos' => (int)$vehiculosOperativos,
        'vehiculos_observacion' => (int)$vehiculosObservacion,
        'vehiculos_detenidos' => (int)$vehiculosDetenidos,
        'alistamientos_hoy' => (int)$alistamientosHoy,
    ];
    
    // Obtener vehículos por estado
    $vehiculos_estado = $db->fetchAll("
        SELECT estado, COUNT(*) AS cantidad 
        FROM vehiculos 
        GROUP BY estado 
        ORDER BY estado
    ");
    
    // Obtener documentos próximos a vencer (30 días)
    $documentos_vencer = $db->fetchAll("
        SELECT d.*, v.numero_interno, v.placa,
            DATEDIFF(d.fecha_vencimiento, CURDATE()) as dias_restantes
        FROM documentos d
        JOIN vehiculos v ON d.vehiculo_id = v.id
        WHERE d.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)

        ORDER BY d.fecha_vencimiento ASC
        LIMIT 10
    ");
    
    // Obtener últimos alistamientos
    $ultimos_alistamientos = $db->fetchAll("
        SELECT a.*, v.numero_interno, v.placa, u.nombre as inspector
        FROM alistamientos a
        JOIN vehiculos v ON a.vehiculo_id = v.id
        JOIN usuarios u ON a.inspector_id = u.id
        ORDER BY a.fecha_alistamiento DESC
        LIMIT 10
    ");
    
    // Obtener estadísticas de alistamientos del día por estado
    $alistamientos_hoy = $db->fetchAll("
        SELECT 
            estado_final as estado,
            COUNT(*) as cantidad
        FROM alistamientos 
        WHERE DATE(fecha_alistamiento) = CURDATE()
        GROUP BY estado_final
        ORDER BY estado_final
    ");
    
    // Obtener estadísticas de vigencia de documentos SOAT
    $soat_vigencia = $db->fetchAll("
        SELECT 
            CASE 
                WHEN fecha_vencimiento < CURDATE() THEN 'rojo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'amarillo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'azul'
                ELSE 'verde'
            END as estado,
            COUNT(*) as cantidad
        FROM documentos 
        WHERE tipo_documento = 'soat'
        GROUP BY 
            CASE 
                WHEN fecha_vencimiento < CURDATE() THEN 'rojo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'amarillo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'azul'
                ELSE 'verde'
            END
        ORDER BY estado
    ");
    
    // Obtener estadísticas de vigencia de documentos Tecnomecánica
    $tecnomecanica_vigencia = $db->fetchAll("
        SELECT 
            CASE 
                WHEN fecha_vencimiento < CURDATE() THEN 'rojo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'amarillo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'azul'
                ELSE 'verde'
            END as estado,
            COUNT(*) as cantidad
        FROM documentos 
        WHERE tipo_documento IN ('tecnomecanica', 'tecnicomecanica', 'rtm')
        GROUP BY 
            CASE 
                WHEN fecha_vencimiento < CURDATE() THEN 'rojo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'amarillo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'azul'
                ELSE 'verde'
            END
        ORDER BY estado
    ");
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link href="../assets/css/chart.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body {
            background: url('../imagendefondo.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(11, 30, 63, 0.55);
            pointer-events: none;
            z-index: 0;
        }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .sidebar {
            min-height: 100vh;
            position: sticky;
            top: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stat-icon.green { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-icon.yellow { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .stat-icon.red { background: linear-gradient(135deg, #dc3545, #e83e8c); }
        .stat-icon.blue { background: linear-gradient(135deg, #007bff, #6f42c1); }
        .stat-icon.purple { background: linear-gradient(135deg, #6f42c1, #e83e8c); }
        
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
        }
        .table-card .card-header {
            background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-600) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        .badge-estado {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-verde { background: #d4edda; color: #155724; }
        .badge-amarillo { background: #fff3cd; color: #856404; }
        .badge-rojo { background: #f8d7da; color: #721c24; }
        
        .footer-credits {
            background: white;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            margin-top: 30px;
            border-radius: 10px;
            color: #6c757d;
        }
    </style>
    
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
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
            <div class="col-md-10">
                <div class="p-4">
                    <h1 class="mb-4">Dashboard Principal</h1>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Estadísticas Principales -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="stat-card text-center">
                                <div class="stat-icon green mx-auto mb-3">
                                    <i class="fas fa-bus"></i>
                                </div>
                                <h3 class="mb-1"><?= $stats['total_vehiculos'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Total Vehículos</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card text-center">
                                <div class="stat-icon blue mx-auto mb-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="mb-1"><?= $stats['total_conductores'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Conductores</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card text-center">
                                <div class="stat-icon green mx-auto mb-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 class="mb-1"><?= $stats['vehiculos_operativos'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Operativos</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card text-center">
                                <div class="stat-icon yellow mx-auto mb-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <h3 class="mb-1"><?= $stats['vehiculos_observacion'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Con Observación</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card text-center">
                                <div class="stat-icon red mx-auto mb-3">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <h3 class="mb-1"><?= $stats['vehiculos_detenidos'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Detenidos</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card text-center">
                                <div class="stat-icon purple mx-auto mb-3">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <h3 class="mb-1"><?= $stats['alistamientos_hoy'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Alistamientos Hoy</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Gráfico de Estado de Vehículos -->
                        <div class="col-md-4 mb-4">
                            <div class="table-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Estado de la Flota</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="estadoFlotaChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de Alistamientos del Día -->
                        <div class="col-md-4 mb-4">
                            <div class="table-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Alistamientos Hoy</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="alistamientosHoyChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Documentos por Vencer -->
                        <div class="col-md-4 mb-4">
                            <div class="table-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-calendar-times"></i> Documentos por Vencer</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($documentos_vencer)): ?>
                                        <p class="text-muted text-center py-3">No hay documentos próximos a vencer</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Vehículo</th>
                                                        <th>Documento</th>
                                                        <th>Vencimiento</th>
                                                        <th>Días</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($documentos_vencer as $doc): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($doc['numero_interno']) ?></td>
                                                            <td><?= htmlspecialchars($doc['tipo_documento']) ?></td>
                                                            <td><?= date('d/m/Y', strtotime($doc['fecha_vencimiento'])) ?></td>
                                                            <td>
                                                                <span class="badge <?= $doc['dias_restantes'] <= 7 ? 'bg-danger' : ($doc['dias_restantes'] <= 15 ? 'bg-warning' : 'bg-info') ?>">
                                                                    <?= $doc['dias_restantes'] ?> días
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos de Documentos -->
                    <div class="row">
                        <!-- Gráfico de Vigencia SOAT -->
                        <div class="col-md-6 mb-4">
                            <div class="table-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Vigencia SOAT</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="soatVigenciaChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de Vigencia Tecnomecánica -->
                        <div class="col-md-6 mb-4">
                            <div class="table-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-tools"></i> Vigencia Tecnomecánica</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="tecnomecanicaVigenciaChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Últimos Alistamientos -->
                    <div class="row">
                        <div class="col-12">
                            <div class="table-card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-history"></i> Últimos Alistamientos</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($ultimos_alistamientos)): ?>
                                        <p class="text-muted text-center py-3">No hay alistamientos registrados</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Vehículo</th>
                                                        <th>Inspector</th>
                                                        <th>Estado</th>
                                                        <th>Observaciones</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($ultimos_alistamientos as $alist): ?>
                                                        <tr>
                                                            <td><?= date('d/m/Y H:i', strtotime($alist['fecha_alistamiento'])) ?></td>
                                                            <td>
                                                                <strong><?= htmlspecialchars($alist['numero_interno']) ?></strong><br>
                                                                <small class="text-muted"><?= htmlspecialchars($alist['placa']) ?></small>
                                                            </td>
                                                            <td><?= htmlspecialchars($alist['inspector']) ?></td>
                                                            <td>
                                                                <span class="badge-estado badge-<?= $alist['estado'] ?>">
                                                                    <?= ucfirst($alist['estado']) ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars($alist['observaciones'] ?: 'Sin observaciones') ?></td>
                                                            <td>
                                                                <a href="alistamiento_detalle.php?id=<?= $alist['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer con créditos -->
                    <div class="footer-credits">
                        <i class="fas fa-code"></i> Desarrollado por <a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-decoration-none">BLACKCROWSOFT.COM</a> | 
        © 2025 <a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-decoration-none">BLACKCROWSOFT.COM</a> - Todos los derechos reservados
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/chart.min.js"></script>
    <script>
        // Gráfico de estado de la flota
        const ctx = document.getElementById('estadoFlotaChart').getContext('2d');
        const estadoFlotaChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($vehiculos_estado as $estado): ?>
                        '<?= $estado['estado'] ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($vehiculos_estado as $estado): ?>
                            <?= $estado['cantidad'] ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        <?php foreach ($vehiculos_estado as $estado): ?>
                            <?php 
                                $color = '#6c757d'; // default
                                if ($estado['estado'] === 'activo') $color = '#28a745';
                                elseif ($estado['estado'] === 'mantenimiento') $color = '#ffc107';
                                elseif ($estado['estado'] === 'inactivo') $color = '#dc3545';
                            ?>
                            '<?= $color ?>',
                        <?php endforeach; ?>
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de alistamientos del día
        const ctxAlistamientos = document.getElementById('alistamientosHoyChart').getContext('2d');
        const alistamientosHoyChart = new Chart(ctxAlistamientos, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php if (!empty($alistamientos_hoy)): ?>
                        <?php foreach ($alistamientos_hoy as $alistamiento): ?>
                            '<?= ucfirst($alistamiento['estado']) ?>',
                        <?php endforeach; ?>
                    <?php else: ?>
                        'Sin datos',
                    <?php endif; ?>
                ],
                datasets: [{
                    data: [
                        <?php if (!empty($alistamientos_hoy)): ?>
                            <?php foreach ($alistamientos_hoy as $alistamiento): ?>
                                <?= $alistamiento['cantidad'] ?>,
                            <?php endforeach; ?>
                        <?php else: ?>
                            1
                        <?php endif; ?>
                    ],
                    backgroundColor: [
                        <?php if (!empty($alistamientos_hoy)): ?>
                            <?php foreach ($alistamientos_hoy as $alistamiento): ?>
                                <?php 
                                    $color = '#6c757d'; // default
                                    if ($alistamiento['estado'] === 'verde') $color = '#28a745';
                                    elseif ($alistamiento['estado'] === 'amarillo') $color = '#ffc107';
                                    elseif ($alistamiento['estado'] === 'rojo') $color = '#dc3545';
                                ?>
                                '<?= $color ?>',
                            <?php endforeach; ?>
                        <?php else: ?>
                            '#e9ecef'
                        <?php endif; ?>
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                <?php if (!empty($alistamientos_hoy)): ?>
                                    return context.label + ': ' + context.parsed + ' alistamientos';
                                <?php else: ?>
                                    return 'No hay alistamientos hoy';
                                <?php endif; ?>
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de vigencia SOAT
        const ctxSoat = document.getElementById('soatVigenciaChart').getContext('2d');
        const soatVigenciaChart = new Chart(ctxSoat, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php if (!empty($soat_vigencia)): ?>
                        <?php foreach ($soat_vigencia as $soat): ?>
                            <?php 
                                $label = '';
                                if ($soat['estado'] === 'verde') $label = 'Más de 4 meses';
                                elseif ($soat['estado'] === 'azul') $label = 'Menos de 4 meses';
                                elseif ($soat['estado'] === 'amarillo') $label = 'Menos de 1 mes';
                                elseif ($soat['estado'] === 'rojo') $label = 'Vencido';
                            ?>
                            '<?= $label ?>',
                        <?php endforeach; ?>
                    <?php else: ?>
                        'Sin datos'
                    <?php endif; ?>
                ],
                datasets: [{
                    data: [
                        <?php if (!empty($soat_vigencia)): ?>
                            <?php foreach ($soat_vigencia as $soat): ?>
                                <?= $soat['cantidad'] ?>,
                            <?php endforeach; ?>
                        <?php else: ?>
                            1
                        <?php endif; ?>
                    ],
                    backgroundColor: [
                        <?php if (!empty($soat_vigencia)): ?>
                            <?php foreach ($soat_vigencia as $soat): ?>
                                <?php 
                                    $color = '#6c757d'; // default
                                    if ($soat['estado'] === 'verde') $color = '#28a745';
                                    elseif ($soat['estado'] === 'azul') $color = '#007bff';
                                    elseif ($soat['estado'] === 'amarillo') $color = '#ffc107';
                                    elseif ($soat['estado'] === 'rojo') $color = '#dc3545';
                                ?>
                                '<?= $color ?>',
                            <?php endforeach; ?>
                        <?php else: ?>
                            '#e9ecef'
                        <?php endif; ?>
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                <?php if (!empty($soat_vigencia)): ?>
                                    return context.label + ': ' + context.parsed + ' documentos';
                                <?php else: ?>
                                    return 'No hay documentos SOAT';
                                <?php endif; ?>
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de vigencia Tecnomecánica
        const ctxTecnomecanica = document.getElementById('tecnomecanicaVigenciaChart').getContext('2d');
        const tecnomecanicaVigenciaChart = new Chart(ctxTecnomecanica, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php if (!empty($tecnomecanica_vigencia)): ?>
                        <?php foreach ($tecnomecanica_vigencia as $tecno): ?>
                            <?php 
                                $label = '';
                                if ($tecno['estado'] === 'verde') $label = 'Más de 4 meses';
                                elseif ($tecno['estado'] === 'azul') $label = 'Menos de 4 meses';
                                elseif ($tecno['estado'] === 'amarillo') $label = 'Menos de 1 mes';
                                elseif ($tecno['estado'] === 'rojo') $label = 'Vencido';
                            ?>
                            '<?= $label ?>',
                        <?php endforeach; ?>
                    <?php else: ?>
                        'Sin datos'
                    <?php endif; ?>
                ],
                datasets: [{
                    data: [
                        <?php if (!empty($tecnomecanica_vigencia)): ?>
                            <?php foreach ($tecnomecanica_vigencia as $tecno): ?>
                                <?= $tecno['cantidad'] ?>,
                            <?php endforeach; ?>
                        <?php else: ?>
                            1
                        <?php endif; ?>
                    ],
                    backgroundColor: [
                        <?php if (!empty($tecnomecanica_vigencia)): ?>
                            <?php foreach ($tecnomecanica_vigencia as $tecno): ?>
                                <?php 
                                    $color = '#6c757d'; // default
                                    if ($tecno['estado'] === 'verde') $color = '#28a745';
                                    elseif ($tecno['estado'] === 'azul') $color = '#007bff';
                                    elseif ($tecno['estado'] === 'amarillo') $color = '#ffc107';
                                    elseif ($tecno['estado'] === 'rojo') $color = '#dc3545';
                                ?>
                                '<?= $color ?>',
                            <?php endforeach; ?>
                        <?php else: ?>
                            '#e9ecef'
                        <?php endif; ?>
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                <?php if (!empty($tecnomecanica_vigencia)): ?>
                                    return context.label + ': ' + context.parsed + ' documentos';
                                <?php else: ?>
                                    return 'No hay documentos Tecnomecánica';
                                <?php endif; ?>
                            }
                        }
                    }
                }
            }
        });

        // Auto-refresh cada 5 minutos
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>