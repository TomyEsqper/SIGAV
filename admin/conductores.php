<?php
/**
 * Gestión de Conductores - SIGAV
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
                        INSERT INTO conductores (
                            nombre, cedula, telefono, activo
                        ) VALUES (?, ?, ?, 1)
                    ");
                    
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['cedula'],
                        $_POST['telefono']
                    ]);
                    
                    $mensaje = "Conductor agregado exitosamente";
                    $tipo_mensaje = "success";
                    
                    // Log de actividad
                    registrarActividad("Agregó conductor: " . $_POST['nombre']);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al agregar conductor: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
            
        case 'editar':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                try {
                    $stmt = $db->prepare("
                        UPDATE conductores 
                        SET nombre = ?, cedula = ?, telefono = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['nombre'],
                        $_POST['cedula'],
                        $_POST['telefono'],
                        $_POST['conductor_id']
                    ]);
                    
                    $mensaje = "Conductor actualizado exitosamente";
                    $tipo_mensaje = "success";
                    
                    // Log de actividad
                    registrarActividad("Editó conductor ID: " . $_POST['conductor_id']);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al actualizar conductor: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
            
        case 'cambiar_estado':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                try {
                    $nuevo_estado = $_POST['nuevo_estado'] === 'activo' ? 1 : 0;
                    
                    $stmt = $db->prepare("UPDATE conductores SET activo = ? WHERE id = ?");
                    $stmt->execute([$nuevo_estado, $_POST['conductor_id']]);
                    
                    $estado_texto = $nuevo_estado ? 'activado' : 'desactivado';
                    $mensaje = "Conductor $estado_texto exitosamente";
                    $tipo_mensaje = "success";
                    
                    // Log de actividad
                    registrarActividad("Cambió estado de conductor ID: " . $_POST['conductor_id'] . " a $estado_texto");
                    
                } catch (Exception $e) {
                    $mensaje = "Error al cambiar estado: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
            
        case 'eliminar':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                try {
                    $stmt = $db->prepare("DELETE FROM conductores WHERE id = ?");
                    $stmt->execute([$_POST['conductor_id']]);
                    
                    $mensaje = "Conductor eliminado exitosamente";
                    $tipo_mensaje = "success";
                    
                    // Log de actividad
                    registrarActividad("Eliminó conductor ID: " . $_POST['conductor_id']);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al eliminar conductor: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta con filtros
$where_conditions = ["1=1"];
$params = [];

if ($filtro_estado !== '') {
    $where_conditions[] = "c.activo = ?";
    $params[] = $filtro_estado === 'activo' ? 1 : 0;
}

if ($busqueda) {
    $where_conditions[] = "(c.nombre LIKE ? OR c.cedula LIKE ? OR c.telefono LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param]);
}

$where_clause = implode(" AND ", $where_conditions);

// Obtener conductores
$stmt = $db->prepare("
    SELECT c.*, 
           CASE 
               WHEN c.activo = 1 THEN 'success'
               ELSE 'danger'
           END as estado_color
    FROM conductores c 
    WHERE $where_clause
    ORDER BY c.nombre ASC
");
$stmt->execute($params);
$conductores = $stmt->fetchAll();

// Obtener estadísticas
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos
    FROM conductores
")->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Conductores - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; position: relative; }
        body::before { content: ""; position: fixed; inset: 0; background: rgba(11, 30, 63, 0.55); pointer-events: none; z-index: 0; }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; }
        .sidebar { min-height: 100vh; position: sticky; top: 0; overflow: hidden; background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%); color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 10px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .card-stats {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .card-stats:hover { transform: translateY(-2px); }
        .badge-estado { font-size: 0.8em; }
        .btn-action { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
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
                            <a class="nav-link <?= $current === 'alistamientos.php' ? 'active' : '' ?>" href="alistamientos.php">
                                <i class="fas fa-clipboard-check"></i> Alistamientos
                            </a>
                            <a class="nav-link <?= $current === 'reportes.php' ? 'active' : '' ?>" href="reportes.php">
                                <i class="fas fa-chart-line"></i> Reportes
                            </a>
                            <a class="nav-link <?= $current === 'documentos.php' ? 'active' : '' ?>" href="documentos.php">
                                <i class="fas fa-file-alt"></i> Documentos
                            </a>
                            <a class="nav-link <?= $current === 'camaras.php' ? 'active' : '' ?>" href="camaras.php">
                                <i class="fas fa-video"></i> Cámaras
                            </a>
                            <a class="nav-link <?= $current === 'evasion.php' ? 'active' : '' ?>" href="evasion.php">
                                <i class="fas fa-shield-alt"></i> Evasión
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-users"></i> Gestión de Conductores</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalConductor">
                        <i class="fas fa-plus"></i> Agregar Conductor
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
                    <div class="col-md-4">
                        <div class="card card-stats border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="card-category text-muted">Total Conductores</p>
                                        <h3 class="card-title"><?= $stats['total'] ?></h3>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stats border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="card-category text-muted">Activos</p>
                                        <h3 class="card-title text-success"><?= $stats['activos'] ?></h3>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stats border-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="card-category text-muted">Inactivos</p>
                                        <h3 class="card-title text-danger"><?= $stats['inactivos'] ?></h3>
                                    </div>
                                    <div class="text-danger">
                                        <i class="fas fa-user-times fa-2x"></i>
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
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Búsqueda</label>
                                <input type="text" name="busqueda" class="form-control" placeholder="Nombre, cédula, teléfono..." value="<?= htmlspecialchars($busqueda) ?>">
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

                <!-- Tabla de conductores -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Conductores (<?= count($conductores) ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Cédula</th>
                                        <th>Teléfono</th>
                                        <th>Estado</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conductores as $conductor): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($conductor['nombre'] ?? '') ?></strong></td>
                                        <td><?= htmlspecialchars($conductor['cedula'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($conductor['telefono'] ?? '') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $conductor['estado_color'] ?> badge-estado">
                                                <?= $conductor['activo'] ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($conductor['fecha_creacion'])) ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-action" 
                                                        onclick="editarConductor(<?= $conductor['id'] ?>, '<?= htmlspecialchars($conductor['nombre'] ?? '') ?>', '<?= htmlspecialchars($conductor['cedula'] ?? '') ?>', '<?= htmlspecialchars($conductor['telefono'] ?? '') ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($conductor['activo']): ?>
                                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $conductor['id'] ?>, 'inactivo')">
                                                            <i class="fas fa-user-times text-danger me-2"></i>Desactivar
                                                        </a></li>
                                                        <?php else: ?>
                                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $conductor['id'] ?>, 'activo')">
                                                            <i class="fas fa-user-check text-success me-2"></i>Activar
                                                        </a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-action" 
                                                        onclick="eliminarConductor(<?= $conductor['id'] ?>, '<?= htmlspecialchars($conductor['nombre'] ?? '') ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($conductores)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No se encontraron conductores</p>
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

    <!-- Modal Agregar/Editar Conductor -->
    <div class="modal fade" id="modalConductor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConductorTitle">Agregar Conductor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formConductor">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                        <input type="hidden" name="accion" id="accionConductor" value="agregar">
                        <input type="hidden" name="conductor_id" id="conductorId" value="">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cedula" class="form-label">Cédula *</label>
                            <input type="text" class="form-control" id="cedula" name="cedula" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarConductor">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarConductor(id, nombre, cedula, telefono) {
            document.getElementById('modalConductorTitle').textContent = 'Editar Conductor';
            document.getElementById('accionConductor').value = 'editar';
            document.getElementById('conductorId').value = id;
            document.getElementById('nombre').value = nombre;
            document.getElementById('cedula').value = cedula;
            document.getElementById('telefono').value = telefono;
            document.getElementById('btnGuardarConductor').textContent = 'Actualizar';
            
            new bootstrap.Modal(document.getElementById('modalConductor')).show();
        }

        function cambiarEstado(id, nuevoEstado) {
            if (confirm('¿Está seguro de cambiar el estado de este conductor?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <input type="hidden" name="accion" value="cambiar_estado">
                    <input type="hidden" name="conductor_id" value="${id}">
                    <input type="hidden" name="nuevo_estado" value="${nuevoEstado}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function eliminarConductor(id, nombre) {
            if (confirm(`¿Está seguro de eliminar al conductor "${nombre}"? Esta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="conductor_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Limpiar modal al cerrarlo
        document.getElementById('modalConductor').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalConductorTitle').textContent = 'Agregar Conductor';
            document.getElementById('accionConductor').value = 'agregar';
            document.getElementById('btnGuardarConductor').textContent = 'Guardar';
            document.getElementById('formConductor').reset();
            document.getElementById('conductorId').value = '';
        });
    </script>

    <div class="text-center mt-4 py-3 border-top">
        <small class="text-muted">
            © 2025 <a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-decoration-none">BLACKCROWSOFT.COM</a> - Todos los derechos reservados
        </small>
    </div>
</body>
</html>