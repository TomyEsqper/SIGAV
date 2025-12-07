<?php
/**
 * Módulo Administrativo - Gestión de Alistamientos - SIGAV
 * Control y supervisión de alistamientos vehiculares
 */

require_once '../config/database.php';
require_once '../config/auth.php';

verificarSesion();

$db = getDB();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['accion']) {
        case 'cambiar_estado':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                try {
                    $stmt = $db->prepare("UPDATE alistamientos SET estado_final = ? WHERE id = ?");
                    $stmt->execute([$_POST['nuevo_estado'], $_POST['alistamiento_id']]);
                    
                    $mensaje = "Estado del alistamiento actualizado exitosamente";
                    $tipo_mensaje = "success";
                    
                    registrarActividad("Cambió estado de alistamiento ID: " . $_POST['alistamiento_id'] . " a " . $_POST['nuevo_estado']);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al cambiar estado: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
            
        case 'liberar_vehiculo':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                try {
                    $stmt = $db->prepare("
                        UPDATE vehiculos_detenidos 
                        SET estado = 'liberado', fecha_liberacion = NOW() 
                        WHERE vehiculo_id = ? AND estado = 'detenido'
                    ");
                    $stmt->execute([$_POST['vehiculo_id']]);
                    
                    $mensaje = "Vehículo liberado exitosamente";
                    $tipo_mensaje = "success";
                    
                    registrarActividad("Liberó vehículo ID: " . $_POST['vehiculo_id']);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al liberar vehículo: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
    }
}

// Obtener filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$filtro_inspector = $_GET['inspector'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta con filtros
$where_conditions = ["1=1"];
$params = [];

if ($filtro_estado) {
    $where_conditions[] = "a.estado_final = ?";
    $params[] = $filtro_estado;
}

if ($filtro_fecha) {
    $where_conditions[] = "DATE(a.fecha_alistamiento) = ?";
    $params[] = $filtro_fecha;
}

if ($filtro_inspector) {
    $where_conditions[] = "a.inspector_id = ?";
    $params[] = $filtro_inspector;
}

if ($busqueda) {
    $where_conditions[] = "(v.placa LIKE ? OR v.numero_interno LIKE ? OR v.propietario LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param]);
}

$where_clause = implode(" AND ", $where_conditions);

// Obtener alistamientos
$stmt = $db->prepare("
    SELECT a.*, 
           v.placa, v.numero_interno, v.propietario,
           u.nombre as inspector_nombre,
           CASE 
               WHEN a.estado_final = 'verde' THEN 'success'
               WHEN a.estado_final = 'amarillo' THEN 'warning'
               WHEN a.estado_final = 'rojo' THEN 'danger'
               ELSE 'secondary'
           END as estado_color,
           vd.id as vehiculo_detenido_id,
           vd.estado as estado_detencion
    FROM alistamientos a
    JOIN vehiculos v ON a.vehiculo_id = v.id
    JOIN usuarios u ON a.inspector_id = u.id
    LEFT JOIN vehiculos_detenidos vd ON a.vehiculo_id = vd.vehiculo_id AND vd.estado = 'detenido'
    WHERE $where_clause
    ORDER BY a.fecha_alistamiento DESC
");
$stmt->execute($params);
$alistamientos = $stmt->fetchAll();

// Obtener estadísticas
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado_final = 'verde' THEN 1 ELSE 0 END) as verdes,
        SUM(CASE WHEN estado_final = 'amarillo' THEN 1 ELSE 0 END) as amarillos,
        SUM(CASE WHEN estado_final = 'rojo' THEN 1 ELSE 0 END) as rojos,
        SUM(CASE WHEN DATE(fecha_alistamiento) = CURDATE() THEN 1 ELSE 0 END) as hoy
    FROM alistamientos
")->fetch();

// Obtener inspectores para filtro
$inspectores = $db->query("
    SELECT DISTINCT u.id, u.nombre 
    FROM usuarios u 
    JOIN alistamientos a ON u.id = a.inspector_id 
    ORDER BY u.nombre
")->fetchAll();

// Obtener vehículos detenidos
$vehiculos_detenidos = $db->query("
    SELECT vd.*, v.placa, v.numero_interno, a.fecha_alistamiento
    FROM vehiculos_detenidos vd
    JOIN vehiculos v ON vd.vehiculo_id = v.id
    JOIN alistamientos a ON vd.alistamiento_id = a.id
    WHERE vd.estado = 'detenido'
    ORDER BY vd.fecha_detencion DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Alistamientos - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link href="../assets/css/chart.min.css" rel="stylesheet">
    <style>
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; position: relative; }
        body::before { content: ""; position: fixed; inset: 0; background: rgba(11, 30, 63, 0.55); z-index: 0; }
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
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-icon.yellow { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .stat-icon.red { background: linear-gradient(135deg, #dc3545, #e83e8c); }
        .stat-icon.blue { background: linear-gradient(135deg, #007bff, #6f42c1); }
        .table-card { background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border: none; }
        .table-card .card-header { background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-600) 100%); color: white; border-radius: 15px 15px 0 0; border: none; }
        .badge-estado { padding: 8px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-verde { background: #d4edda; color: #155724; }
        .badge-amarillo { background: #fff3cd; color: #856404; }
        .badge-rojo { background: #f8d7da; color: #721c24; }
        .footer-credits { background: white; padding: 15px; text-align: center; border-top: 1px solid #e9ecef; margin-top: 30px; border-radius: 10px; color: #6c757d; }

        /* Estilos específicos del módulo */
        .card-stats { border-left: 4px solid; transition: transform 0.2s; }
        .card-stats:hover { transform: translateY(-2px); }
        .btn-action { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .foto-thumbnail { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; cursor: pointer; }
        .vehiculo-detenido { background-color: #fff3cd; border-left: 4px solid #ffc107; }
        .modal-foto img { max-width: 100%; height: auto; }

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
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (copiado exactamente del Dashboard) -->
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

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-clipboard-check me-2"></i>Gestión de Alistamientos</h1>
                </div>

                <!-- Mensajes -->
                <?php if (isset($mensaje)): ?>
                <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                    <?= $mensaje ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card card-stats border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="card-category text-muted">Total Alistamientos</p>
                                        <h3 class="card-title"><?= $stats['total'] ?></h3>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-clipboard-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="card-category text-muted">Estado Verde</p>
                                        <h3 class="card-title text-success"><?= $stats['verdes'] ?></h3>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats border-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="card-category text-muted">Estado Amarillo</p>
                                        <h3 class="card-title text-warning"><?= $stats['amarillos'] ?></h3>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats border-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="card-category text-muted">Estado Rojo</p>
                                        <h3 class="card-title text-danger"><?= $stats['rojos'] ?></h3>
                                    </div>
                                    <div class="text-danger">
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehículos Detenidos -->
                <?php if (!empty($vehiculos_detenidos)): ?>
                <div class="alert alert-warning vehiculo-detenido mb-4">
                    <h5><i class="fas fa-ban me-2"></i>Vehículos Detenidos (<?= count($vehiculos_detenidos) ?>)</h5>
                    <div class="row">
                        <?php foreach ($vehiculos_detenidos as $detenido): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($detenido['numero_interno']) ?></strong> - 
                                    <?= htmlspecialchars($detenido['placa']) ?>
                                    <br><small>Detenido: <?= date('d/m/Y H:i', strtotime($detenido['fecha_detencion'])) ?></small>
                                </div>
                                <button class="btn btn-sm btn-success" onclick="liberarVehiculo(<?= $detenido['vehiculo_id'] ?>)">
                                    <i class="fas fa-unlock"></i> Liberar
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="verde" <?= $filtro_estado === 'verde' ? 'selected' : '' ?>>Verde</option>
                                    <option value="amarillo" <?= $filtro_estado === 'amarillo' ? 'selected' : '' ?>>Amarillo</option>
                                    <option value="rojo" <?= $filtro_estado === 'rojo' ? 'selected' : '' ?>>Rojo</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($filtro_fecha) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Inspector</label>
                                <select name="inspector" class="form-select">
                                    <option value="">Todos</option>
                                    <?php foreach ($inspectores as $inspector): ?>
                                    <option value="<?= $inspector['id'] ?>" <?= $filtro_inspector == $inspector['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($inspector['nombre']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Búsqueda</label>
                                <input type="text" name="busqueda" class="form-control" placeholder="Placa, número interno, propietario..." value="<?= htmlspecialchars($busqueda) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de alistamientos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Alistamientos (<?= count($alistamientos) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($alistamientos)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No se encontraron alistamientos</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Vehículo</th>
                                            <th>Inspector</th>
                                            <th>Estado</th>
                                            <th>Tipo</th>
                                            <th>Fotos</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($alistamientos as $alistamiento): ?>
                                        <tr class="<?= $alistamiento['vehiculo_detenido_id'] ? 'table-warning' : '' ?>">
                                            <td>
                                                <?= date('d/m/Y', strtotime($alistamiento['fecha_alistamiento'])) ?><br>
                                                <small class="text-muted"><?= date('H:i', strtotime($alistamiento['fecha_alistamiento'])) ?></small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($alistamiento['numero_interno']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($alistamiento['placa']) ?></small>
                                                <?php if ($alistamiento['vehiculo_detenido_id']): ?>
                                                    <br><span class="badge bg-warning">DETENIDO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($alistamiento['inspector_nombre']) ?></td>
                                            <td>
                                                <span class="badge badge-estado bg-<?= $alistamiento['estado_color'] ?>">
                                                    <?= strtoupper($alistamiento['estado_final']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($alistamiento['es_alistamiento_parcial']): ?>
                                                    <span class="badge bg-info">Parcial</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Completo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="verFotos(<?= $alistamiento['id'] ?>)">
                                                    <i class="fas fa-images"></i> Ver Fotos
                                                </button>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="alistamiento_detalle.php?id=<?= $alistamiento['id'] ?>" 
                                                       class="btn btn-sm btn-outline-info btn-action">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle btn-action" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $alistamiento['id'] ?>, 'verde')">
                                                                <i class="fas fa-check-circle text-success me-2"></i>Verde
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $alistamiento['id'] ?>, 'amarillo')">
                                                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>Amarillo
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $alistamiento['id'] ?>, 'rojo')">
                                                                <i class="fas fa-times-circle text-danger me-2"></i>Rojo
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para ver fotos -->
    <div class="modal fade" id="modalFotos" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Evidencias Fotográficas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoFotos">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formularios ocultos para acciones -->
    <form id="formCambiarEstado" method="POST" style="display: none;">
        <input type="hidden" name="accion" value="cambiar_estado">
        <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
        <input type="hidden" name="alistamiento_id" id="alistamiento_id">
        <input type="hidden" name="nuevo_estado" id="nuevo_estado">
    </form>

    <form id="formLiberarVehiculo" method="POST" style="display: none;">
        <input type="hidden" name="accion" value="liberar_vehiculo">
        <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
        <input type="hidden" name="vehiculo_id" id="vehiculo_id_liberar">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cambiarEstado(alistamientoId, nuevoEstado) {
            if (confirm(`¿Cambiar el estado del alistamiento a ${nuevoEstado.toUpperCase()}?`)) {
                document.getElementById('alistamiento_id').value = alistamientoId;
                document.getElementById('nuevo_estado').value = nuevoEstado;
                document.getElementById('formCambiarEstado').submit();
            }
        }

        function liberarVehiculo(vehiculoId) {
            if (confirm('¿Liberar este vehículo detenido?')) {
                document.getElementById('vehiculo_id_liberar').value = vehiculoId;
                document.getElementById('formLiberarVehiculo').submit();
            }
        }

        function verFotos(alistamientoId) {
            const modal = new bootstrap.Modal(document.getElementById('modalFotos'));
            const contenido = document.getElementById('contenidoFotos');
            
            // Mostrar loading
            contenido.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            
            // Cargar fotos via AJAX
            fetch(`alistamiento_detalle.php?id=${alistamientoId}&ajax=fotos`)
                .then(response => response.text())
                .then(data => {
                    contenido.innerHTML = data;
                })
                .catch(error => {
                    contenido.innerHTML = '<div class="alert alert-danger">Error al cargar las fotos</div>';
                });
        }
    </script>
</body>
</html>