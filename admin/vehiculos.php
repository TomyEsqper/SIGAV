<?php
/**
 * Gestión de Vehículos - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2025 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación y permisos
verificarSesion(['admin']);

$db = getDB();
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'agregar':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO vehiculos (
                            numero_interno, placa, propietario, estado
                        ) VALUES (?, ?, ?, 'activo')
                    ");
                    
                    $stmt->execute([
                        $_POST['numero_interno'],
                        strtoupper($_POST['placa']),
                        $_POST['propietario']
                    ]);
                    
                    $mensaje = "Vehículo agregado exitosamente";
                    $tipo_mensaje = "success";
                    
                    // Log de actividad
                    registrarActividad("Agregó vehículo: " . $_POST['placa']);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al agregar vehículo: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
            
        case 'editar':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                try {
                    $stmt = $db->prepare("
                        UPDATE vehiculos SET 
                            numero_interno = ?, placa = ?, propietario = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['numero_interno'],
                        strtoupper($_POST['placa']),
                        $_POST['propietario'],
                        $_POST['id']
                    ]);
                    
                    $mensaje = "Vehículo actualizado exitosamente";
                    $tipo_mensaje = "success";
                    
                    registrarActividad("Editó vehículo ID: " . $_POST['id']);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al actualizar vehículo: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
            
        case 'eliminar':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                try {
                    $stmt = $db->prepare("UPDATE vehiculos SET estado = 'eliminado' WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    $mensaje = "Vehículo eliminado exitosamente";
                    $tipo_mensaje = "success";
                    
                    registrarActividad("Eliminó vehículo ID: " . $_POST['id']);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al eliminar vehículo: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
            
        case 'cambiar_estado':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                try {
                    $stmt = $db->prepare("UPDATE vehiculos SET estado = ? WHERE id = ?");
                    $stmt->execute([$_POST['estado'], $_POST['id']]);
                    
                    $mensaje = "Estado del vehículo actualizado";
                    $tipo_mensaje = "success";
                    
                    registrarActividad("Cambió estado de vehículo ID: " . $_POST['id'] . " a " . $_POST['estado']);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al cambiar estado: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
    }
}

// Obtener filtros
$filtro_estado = $_GET['estado'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta con filtros
$where_conditions = ["v.estado != 'eliminado'"]; // Excluir vehículos eliminados
$params = [];

if ($filtro_estado) {
    $where_conditions[] = "v.estado = ?";
    $params[] = $filtro_estado;
}

if ($busqueda) {
    $where_conditions[] = "(v.placa LIKE ? OR v.numero_interno LIKE ? OR v.propietario LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param]);
}

$where_clause = implode(" AND ", $where_conditions);

// Obtener vehículos
$stmt = $db->prepare("
    SELECT v.*, 
           CASE 
               WHEN v.estado = 'activo' THEN 'success'
               WHEN v.estado = 'inactivo' THEN 'warning'
               WHEN v.estado = 'mantenimiento' THEN 'info'
               WHEN v.estado = 'eliminado' THEN 'danger'
               WHEN v.estado IS NULL OR v.estado = '' THEN 'secondary'
               ELSE 'secondary'
           END as estado_color
    FROM vehiculos v 
    WHERE $where_clause
    ORDER BY v.numero_interno ASC
");
$stmt->execute($params);
$vehiculos = $stmt->fetchAll();

// Obtener estadísticas
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
        SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) as mantenimiento
    FROM vehiculos WHERE estado != 'eliminado'
")->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Vehículos - SIGAV</title>
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
        .table-card { background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border: none; }
        .table-card .card-header { background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-600) 100%); color: white; border-radius: 15px 15px 0 0; border: none; }
        .badge-estado { padding: 8px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-verde { background: #d4edda; color: #155724; }
        .badge-amarillo { background: #fff3cd; color: #856404; }
        .badge-rojo { background: #f8d7da; color: #721c24; }
        .footer-credits { background: white; padding: 15px; text-align: center; border-top: 1px solid #e9ecef; margin-top: 30px; border-radius: 10px; color: #6c757d; }

        /* Estilos específicos del módulo */
        .btn-action { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .qr-code { max-width: 50px; height: auto; }

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

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-truck"></i> Gestión de Vehículos</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalVehiculo">
                        <i class="fas fa-plus"></i> Agregar Vehículo
                    </button>
                </div>

                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
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
                                        <p class="card-category text-muted">Total Vehículos</p>
                                        <h3 class="card-title"><?= $stats['total'] ?></h3>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-truck fa-2x"></i>
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
                                        <p class="card-category text-muted">Activos</p>
                                        <h3 class="card-title text-success"><?= $stats['activos'] ?></h3>
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
                                        <p class="card-category text-muted">Inactivos</p>
                                        <h3 class="card-title text-warning"><?= $stats['inactivos'] ?></h3>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-pause-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats border-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="card-category text-muted">Mantenimiento</p>
                                        <h3 class="card-title text-info"><?= $stats['mantenimiento'] ?></h3>
                                    </div>
                                    <div class="text-info">
                                        <i class="fas fa-tools fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="activo" <?= $filtro_estado === 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="inactivo" <?= $filtro_estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                    <option value="mantenimiento" <?= $filtro_estado === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                                </select>
                            </div>
                            <div class="col-md-7">
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

                <!-- Tabla de vehículos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Vehículos (<?= count($vehiculos) ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>N° Interno</th>
                                        <th>Placa</th>
                                        <th>Propietario</th>
                                        <th>Estado</th>
                                        <th>QR</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehiculos as $vehiculo): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($vehiculo['numero_interno']) ?></strong></td>
                                        <td><?= htmlspecialchars($vehiculo['placa']) ?></td>
                                        <td><?= htmlspecialchars($vehiculo['propietario']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $vehiculo['estado_color'] ?> badge-estado">
                                                <?= empty($vehiculo['estado']) ? 'Sin Estado' : ucfirst(str_replace('_', ' ', $vehiculo['estado'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <button class="btn btn-sm btn-outline-secondary" onclick="generarQR(<?= $vehiculo['id'] ?>)" title="<?= $vehiculo['codigo_qr'] ? 'Regenerar código QR' : 'Generar código QR' ?>">
                                                    <i class="fas fa-qrcode"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-action" 
                                                        onclick="editarVehiculo(<?= htmlspecialchars(json_encode($vehiculo)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info btn-action" 
                                                        onclick="verDetalles(<?= $vehiculo['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-warning btn-action dropdown-toggle" 
                                                            data-bs-toggle="dropdown">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $vehiculo['id'] ?>, 'activo')">
                                                            <i class="fas fa-check-circle text-success me-2"></i>Activo
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $vehiculo['id'] ?>, 'inactivo')">
                                                            <i class="fas fa-pause-circle text-warning me-2"></i>Inactivo
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $vehiculo['id'] ?>, 'mantenimiento')">
                                                            <i class="fas fa-tools text-info me-2"></i>Mantenimiento
                                                        </a></li>
                                                    </ul>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-action" 
                                                        onclick="eliminarVehiculo(<?= $vehiculo['id'] ?>, '<?= htmlspecialchars($vehiculo['placa']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($vehiculos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No se encontraron vehículos</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Agregar/Editar Vehículo -->
    <div class="modal fade" id="modalVehiculo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVehiculoTitle">Agregar Vehículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formVehiculo" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <input type="hidden" name="accion" id="accionVehiculo" value="agregar">
                    <input type="hidden" name="id" id="vehiculoId">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Número Interno *</label>
                                    <input type="text" name="numero_interno" id="numero_interno" class="form-control" required placeholder="Ej: CO-0012">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Placa *</label>
                                    <input type="text" name="placa" id="placa" class="form-control" required style="text-transform: uppercase;" placeholder="Ej: ABC123">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Propietario *</label>
                            <input type="text" name="propietario" id="propietario" class="form-control" required placeholder="Nombre del propietario">
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarVehiculo(vehiculo) {
            document.getElementById('modalVehiculoTitle').textContent = 'Editar Vehículo';
            document.getElementById('accionVehiculo').value = 'editar';
            document.getElementById('vehiculoId').value = vehiculo.id;
            
            // Llenar campos
            document.getElementById('numero_interno').value = vehiculo.numero_interno;
            document.getElementById('placa').value = vehiculo.placa;
            document.getElementById('propietario').value = vehiculo.propietario;
            
            new bootstrap.Modal(document.getElementById('modalVehiculo')).show();
        }
        
        function cambiarEstado(id, estado) {
            if (confirm('¿Está seguro de cambiar el estado del vehículo?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <input type="hidden" name="accion" value="cambiar_estado">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="estado" value="${estado}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function eliminarVehiculo(id, placa) {
            if (confirm(`¿Está seguro de eliminar el vehículo ${placa}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function generarQR(id) {
            // Detectar si ya existe un QR para este vehículo
            const row = document.querySelector(`button[onclick="generarQR(${id})"]`).closest('tr');
            const hasQR = row.querySelector('img.qr-code') !== null;
            const message = hasQR ? 
                '¿Regenerar código QR? Esto creará un nuevo código para imprimir.' : 
                '¿Generar código QR para imprimir y pegar en el vehículo?';
            
            if (confirm(message)) {
                fetch('generar_qr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `vehiculo_id=${id}&csrf_token=<?= generarTokenCSRF() ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Abrir página de impresión en nueva ventana
                        const printWindow = window.open(
                            data.print_url + '&auto_print=1', 
                            'printQR', 
                            'width=800,height=900,scrollbars=yes,resizable=yes'
                        );
                        
                        // Recargar la página actual para mostrar el QR actualizado
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error al generar QR: ' + error);
                });
            }
        }
        
        function verDetalles(id) {
            window.location.href = 'vehiculo_detalle.php?id=' + id;
        }
        
        // Limpiar formulario al cerrar modal
        document.getElementById('modalVehiculo').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formVehiculo').reset();
            document.getElementById('modalVehiculoTitle').textContent = 'Agregar Vehículo';
            document.getElementById('accionVehiculo').value = 'agregar';
            document.getElementById('vehiculoId').value = '';
        });
    </script>

    <div class="text-center mt-4 py-3 border-top">
        <small class="text-muted">
            © 2025 <strong>BLACK CROWSOFT</strong> - Todos los derechos reservados
        </small>
    </div>
</body>
</html>