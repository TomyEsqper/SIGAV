<?php
/**
 * Gestión de Documentos - SIGAV
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
        case 'actualizar_documento':
            if (verificarTokenCSRF($_POST['csrf_token'])) {
                // Validaciones
                $vehiculo_id = intval($_POST['vehiculo_id']);
                $tipo_documento = trim($_POST['tipo_documento']);
                $fecha_vencimiento = $_POST['fecha_vencimiento'];
                
                // Validar que el vehículo existe y está activo
                $stmt = $db->prepare("SELECT id FROM vehiculos WHERE id = ? AND estado = 'activo'");
                $stmt->execute([$vehiculo_id]);
                if (!$stmt->fetch()) {
                    $mensaje = "El vehículo seleccionado no existe o no está activo";
                    $tipo_mensaje = "danger";
                    break;
                }
                
                // Validar tipo de documento
                $tipos_validos = ['soat', 'tecnomecanica', 'tarjeta_operacion', 'extintor'];
                if (!in_array($tipo_documento, $tipos_validos)) {
                    $mensaje = "Tipo de documento no válido";
                    $tipo_mensaje = "danger";
                    break;
                }
                
                // Validar fecha
                $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_vencimiento);
                if (!$fecha_obj) {
                    $mensaje = "Fecha de vencimiento no válida";
                    $tipo_mensaje = "danger";
                    break;
                }
                
                // Validar que la fecha no sea muy antigua (más de 1 año atrás)
                $hace_un_ano = new DateTime('-1 year');
                if ($fecha_obj < $hace_un_ano) {
                    $mensaje = "La fecha de vencimiento no puede ser anterior a " . $hace_un_ano->format('d/m/Y');
                    $tipo_mensaje = "warning";
                    break;
                }
                
                try {
                    // Verificar si el documento ya existe
                    $stmt = $db->prepare("
                        SELECT id FROM documentos 
                        WHERE vehiculo_id = ? AND tipo_documento = ?
                    ");
                    $stmt->execute([$vehiculo_id, $tipo_documento]);
                    $documento_existe = $stmt->fetch();
                    
                    if ($documento_existe) {
                        // Actualizar documento existente
                        $stmt = $db->prepare("
                            UPDATE documentos SET 
                                fecha_vencimiento = ?
                            WHERE vehiculo_id = ? AND tipo_documento = ?
                        ");
                        $stmt->execute([
                            $fecha_vencimiento,
                            $vehiculo_id,
                            $tipo_documento
                        ]);
                        $accion_realizada = "actualizado";
                    } else {
                        // Insertar nuevo documento
                        $stmt = $db->prepare("
                            INSERT INTO documentos (vehiculo_id, tipo_documento, fecha_vencimiento)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([
                            $vehiculo_id,
                            $tipo_documento,
                            $fecha_vencimiento
                        ]);
                        $accion_realizada = "creado";
                    }
                    
                    $mensaje = "Documento " . $accion_realizada . " exitosamente";
                    $tipo_mensaje = "success";
                    
                    // Log de actividad
                    registrarActividad("Documento " . $tipo_documento . " " . $accion_realizada . " para vehículo ID: " . $vehiculo_id);
                    
                } catch (Exception $e) {
                    $mensaje = "Error al procesar documento: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            break;
    }
}

// Función para calcular el estado del documento
function calcularEstadoDocumento($fecha_vencimiento) {
    $hoy = new DateTime();
    $vencimiento = new DateTime($fecha_vencimiento);
    $diferencia = $hoy->diff($vencimiento);
    $dias = $diferencia->days;
    
    if ($vencimiento < $hoy) {
        return ['estado' => 'rojo', 'dias' => -$dias, 'texto' => 'Vencido'];
    } elseif ($dias <= 30) {
        return ['estado' => 'amarillo', 'dias' => $dias, 'texto' => 'Por vencer'];
    } elseif ($dias <= 90) {
        return ['estado' => 'azul', 'dias' => $dias, 'texto' => 'Próximo a vencer'];
    } else {
        return ['estado' => 'verde', 'dias' => $dias, 'texto' => 'Vigente'];
    }
}

// Obtener estadísticas de documentos
$estadisticas = [
    'soat' => ['verde' => 0, 'azul' => 0, 'amarillo' => 0, 'rojo' => 0],
    'tecnomecanica' => ['verde' => 0, 'azul' => 0, 'amarillo' => 0, 'rojo' => 0],
    'tarjeta_operacion' => ['verde' => 0, 'azul' => 0, 'amarillo' => 0, 'rojo' => 0],
    'extintor' => ['verde' => 0, 'azul' => 0, 'amarillo' => 0, 'rojo' => 0]
];

// Consultar todos los documentos
$stmt = $db->query("
    SELECT d.*, v.placa, v.numero_interno, v.propietario 
    FROM documentos d 
    JOIN vehiculos v ON d.vehiculo_id = v.id 
    WHERE v.estado = 'activo'
    ORDER BY v.placa, d.tipo_documento
");
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas
foreach ($documentos as &$doc) {
    $estado_info = calcularEstadoDocumento($doc['fecha_vencimiento']);
    $doc['estado_calculado'] = $estado_info;
    
    $tipo = $doc['tipo_documento'];
    if ($tipo === 'tecnomecanica') $tipo = 'tecnomecanica';
    
    if (isset($estadisticas[$tipo])) {
        $estadisticas[$tipo][$estado_info['estado']]++;
    }
}

// Filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Aplicar filtros
$documentos_filtrados = $documentos;
if ($filtro_tipo) {
    $documentos_filtrados = array_filter($documentos_filtrados, function($doc) use ($filtro_tipo) {
        return $doc['tipo_documento'] === $filtro_tipo;
    });
}
if ($filtro_estado) {
    $documentos_filtrados = array_filter($documentos_filtrados, function($doc) use ($filtro_estado) {
        return $doc['estado_calculado']['estado'] === $filtro_estado;
    });
}
if ($busqueda) {
    $documentos_filtrados = array_filter($documentos_filtrados, function($doc) use ($busqueda) {
        return stripos($doc['placa'], $busqueda) !== false || 
               stripos($doc['numero_interno'], $busqueda) !== false ||
               stripos($doc['propietario'], $busqueda) !== false;
    });
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Documentos - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link href="../assets/css/chart.min.css" rel="stylesheet">
    <style>
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; position: relative; }
        body::before { content: ""; position: fixed; inset: 0; background: rgba(11, 30, 63, 0.55); pointer-events: none; z-index: 0; }
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
        .estado-verde { background-color: #d4edda; border-left: 4px solid #28a745; }
        .estado-azul { background-color: #d1ecf1; border-left: 4px solid #17a2b8; }
        .estado-amarillo { background-color: #fff3cd; border-left: 4px solid #ffc107; }
        .estado-rojo { background-color: #f8d7da; border-left: 4px solid #dc3545; }
        .card-estadistica { transition: transform 0.2s; cursor: pointer; }
        .card-estadistica:hover { transform: translateY(-2px); }

        /* Mejora de legibilidad: fondo sutil detrás de títulos */
        h1, h2, h5 {
            display: inline-block;
            background: rgba(255, 255, 255, 0.85);
            padding: 4px 12px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            backdrop-filter: blur(2px);
        }
        /* Fondo más ligero para h3, útil fuera de tarjetas */
        h3 {
            display: inline-block;
            background: rgba(255, 255, 255, 0.75);
            padding: 3px 10px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.12);
            backdrop-filter: blur(1.5px);
        }
        /* Evitar chips dentro de tarjetas/headers para no alterar diseño interno */
        .card-body h3, .card-header h5 {
            background: transparent !important;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
            display: inline;
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
                     <h1 class="h2"><i class="fas fa-file-alt"></i> Gestión de Documentos</h1>
                     <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalActualizar">
                          <i class="fas fa-plus"></i> Actualizar Documento
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
                     <div class="col-12">
                         <h2><i class="fas fa-chart-bar me-2"></i>Estadísticas de Documentos</h2>
                     </div>
                 </div>

                 <!-- SOAT -->
                 <div class="row mb-3">
                     <div class="col-12">
                         <h5><i class="fas fa-shield-alt me-2"></i>SOAT</h5>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-verde" onclick="filtrarPor('soat', 'verde')">
                             <div class="card-body text-center">
                                 <h3 class="text-success"><?= $estadisticas['soat']['verde'] ?></h3>
                                 <p class="mb-0">Vigentes</p>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-azul" onclick="filtrarPor('soat', 'azul')">
                             <div class="card-body text-center">
                                 <h3 class="text-info"><?= $estadisticas['soat']['azul'] ?></h3>
                                 <p class="mb-0">Próximos a vencer</p>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-amarillo" onclick="filtrarPor('soat', 'amarillo')">
                             <div class="card-body text-center">
                                 <h3 class="text-warning"><?= $estadisticas['soat']['amarillo'] ?></h3>
                                 <p class="mb-0">Por vencer</p>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-rojo" onclick="filtrarPor('soat', 'rojo')">
                             <div class="card-body text-center">
                                 <h3 class="text-danger"><?= $estadisticas['soat']['rojo'] ?></h3>
                                 <p class="mb-0">Vencidos</p>
                             </div>
                         </div>
                     </div>
                 </div>

                 <!-- Tecnomecánica -->
                 <div class="row mb-3">
                     <div class="col-12">
                         <h5><i class="fas fa-cog me-2"></i>Tecnomecánica</h5>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-verde" onclick="filtrarPor('tecnomecanica', 'verde')">
                             <div class="card-body text-center">
                                 <h3 class="text-success"><?= $estadisticas['tecnomecanica']['verde'] ?></h3>
                                 <p class="mb-0">Vigentes</p>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-azul" onclick="filtrarPor('tecnomecanica', 'azul')">
                             <div class="card-body text-center">
                                 <h3 class="text-info"><?= $estadisticas['tecnomecanica']['azul'] ?></h3>
                                 <p class="mb-0">Próximos a vencer</p>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-amarillo" onclick="filtrarPor('tecnomecanica', 'amarillo')">
                             <div class="card-body text-center">
                                 <h3 class="text-warning"><?= $estadisticas['tecnomecanica']['amarillo'] ?></h3>
                                 <p class="mb-0">Por vencer</p>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-rojo" onclick="filtrarPor('tecnomecanica', 'rojo')">
                             <div class="card-body text-center">
                                 <h3 class="text-danger"><?= $estadisticas['tecnomecanica']['rojo'] ?></h3>
                                 <p class="mb-0">Vencidos</p>
                             </div>
                         </div>
                     </div>
                 </div>

                 <!-- Tarjeta de Operación -->
                 <div class="row mb-4">
                     <div class="col-12">
                         <h5><i class="fas fa-id-card me-2"></i>Tarjeta de Operación</h5>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-verde" onclick="filtrarPor('tarjeta_operacion', 'verde')">
                             <div class="card-body text-center">
                                 <h3 class="text-success"><?= $estadisticas['tarjeta_operacion']['verde'] ?></h3>
                                 <p class="mb-0">Vigentes</p>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-azul" onclick="filtrarPor('tarjeta_operacion', 'azul')">
                             <div class="card-body text-center">
                                 <h3 class="text-info"><?= $estadisticas['tarjeta_operacion']['azul'] ?></h3>
                                 <p class="mb-0">Próximos a vencer</p>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-amarillo" onclick="filtrarPor('tarjeta_operacion', 'amarillo')">
                             <div class="card-body text-center">
                                 <h3 class="text-warning"><?= $estadisticas['tarjeta_operacion']['amarillo'] ?></h3>
                                 <p class="mb-0">Por vencer</p>
                             </div>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="card card-estadistica estado-rojo" onclick="filtrarPor('tarjeta_operacion', 'rojo')">
                             <div class="card-body text-center">
                                 <h3 class="text-danger"><?= $estadisticas['tarjeta_operacion']['rojo'] ?></h3>
                                 <p class="mb-0">Vencidos</p>
                             </div>
                         </div>
                     </div>
                 </div>

                 <!-- Extintor -->
                 <div class="col-md-3">
                     <h5><i class="fas fa-fire-extinguisher me-2"></i>Extintor</h5>
                     <div class="row g-2">
                         <div class="col-6">
                             <div class="card card-estadistica estado-verde" onclick="filtrarPor('extintor', 'verde')">
                                 <div class="card-body text-center">
                                     <h3 class="text-success"><?= $estadisticas['extintor']['verde'] ?></h3>
                                     <p class="mb-0">Vigentes</p>
                                 </div>
                             </div>
                         </div>
                         <div class="col-6">
                             <div class="card card-estadistica estado-azul" onclick="filtrarPor('extintor', 'azul')">
                                 <div class="card-body text-center">
                                     <h3 class="text-info"><?= $estadisticas['extintor']['azul'] ?></h3>
                                     <p class="mb-0">Próximos a vencer</p>
                                 </div>
                             </div>
                         </div>
                         <div class="col-6">
                             <div class="card card-estadistica estado-amarillo" onclick="filtrarPor('extintor', 'amarillo')">
                                 <div class="card-body text-center">
                                     <h3 class="text-warning"><?= $estadisticas['extintor']['amarillo'] ?></h3>
                                     <p class="mb-0">Por vencer</p>
                                 </div>
                             </div>
                         </div>
                         <div class="col-6">
                             <div class="card card-estadistica estado-rojo" onclick="filtrarPor('extintor', 'rojo')">
                                 <div class="card-body text-center">
                                     <h3 class="text-danger"><?= $estadisticas['extintor']['rojo'] ?></h3>
                                     <p class="mb-0">Vencidos</p>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>

        <!-- Controles y filtros -->
        <div class="row mb-3">
            <div class="col-md-6">
                <h3><i class="fas fa-list me-2"></i>Lista de Documentos</h3>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalActualizar">
                    <i class="fas fa-plus me-2"></i>Actualizar Documento
                </button>
                <button class="btn btn-secondary" onclick="limpiarFiltros()">
                    <i class="fas fa-times me-2"></i>Limpiar Filtros
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-3">
                <select class="form-select" id="filtroTipo" onchange="aplicarFiltros()">
                    <option value="">Todos los tipos</option>
                    <option value="soat" <?= $filtro_tipo === 'soat' ? 'selected' : '' ?>>SOAT</option>
                    <option value="tecnomecanica" <?= $filtro_tipo === 'tecnomecanica' ? 'selected' : '' ?>>Tecnomecánica</option>
                    <option value="tarjeta_operacion" <?= $filtro_tipo === 'tarjeta_operacion' ? 'selected' : '' ?>>Tarjeta de Operación</option>
                    <option value="extintor" <?= $filtro_tipo === 'extintor' ? 'selected' : '' ?>>Extintor</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroEstado" onchange="aplicarFiltros()">
                    <option value="">Todos los estados</option>
                    <option value="verde" <?= $filtro_estado === 'verde' ? 'selected' : '' ?>>Vigentes</option>
                    <option value="azul" <?= $filtro_estado === 'azul' ? 'selected' : '' ?>>Próximos a vencer</option>
                    <option value="amarillo" <?= $filtro_estado === 'amarillo' ? 'selected' : '' ?>>Por vencer</option>
                    <option value="rojo" <?= $filtro_estado === 'rojo' ? 'selected' : '' ?>>Vencidos</option>
                </select>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" id="busqueda" placeholder="Buscar por placa, número interno o propietario..." value="<?= htmlspecialchars($busqueda) ?>">
                    <button class="btn btn-outline-secondary" onclick="aplicarFiltros()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de documentos -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Vehículo</th>
                        <th>Placa</th>
                        <th>Propietario</th>
                        <th>Tipo Documento</th>
                        <th>Fecha Vencimiento</th>
                        <th>Estado</th>
                        <th>Días</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos_filtrados as $doc): ?>
                        <tr class="estado-<?= $doc['estado_calculado']['estado'] ?>">
                            <td><?= htmlspecialchars($doc['numero_interno']) ?></td>
                            <td><strong><?= htmlspecialchars($doc['placa']) ?></strong></td>
                            <td><?= htmlspecialchars($doc['propietario']) ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= strtoupper(str_replace('_', ' ', $doc['tipo_documento'])) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($doc['fecha_vencimiento'])) ?></td>
                            <td>
                                <span class="badge badge-estado bg-<?= $doc['estado_calculado']['estado'] === 'verde' ? 'success' : ($doc['estado_calculado']['estado'] === 'azul' ? 'info' : ($doc['estado_calculado']['estado'] === 'amarillo' ? 'warning' : 'danger')) ?>">
                                    <?= $doc['estado_calculado']['texto'] ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $estado = $doc['estado_calculado']['estado'] ?? 'verde';
                                    $dias = (int)($doc['estado_calculado']['dias'] ?? 0);
                                    $class = 'text-success';
                                    if ($estado === 'rojo') { $class = 'text-danger'; }
                                    elseif ($estado === 'amarillo') { $class = 'text-warning'; }
                                    elseif ($estado === 'azul') { $class = 'text-info'; }
                                ?>
                                <span class="<?= $class ?>"><?= $dias < 0 ? '-' . abs($dias) : $dias ?> días</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editarDocumento(<?= $doc['vehiculo_id'] ?>, '<?= $doc['tipo_documento'] ?>', '<?= $doc['fecha_vencimiento'] ?>', '<?= htmlspecialchars($doc['placa']) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($documentos_filtrados)): ?>
            <div class="text-center py-4">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron documentos</h5>
                <p class="text-muted">No hay documentos que coincidan con los filtros aplicados.</p>
            </div>
        <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal para actualizar documento -->
    <div class="modal fade" id="modalActualizar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Actualizar Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="actualizar_documento">
                        <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                        <input type="hidden" name="vehiculo_id" id="vehiculo_id">
                        <input type="hidden" name="tipo_documento" id="tipo_documento">
                        
                        <div class="mb-3">
                            <label class="form-label">Vehículo</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="buscar_vehiculo" placeholder="Buscar por placa o número interno...">
                                <button type="button" class="btn btn-outline-secondary" onclick="buscarVehiculo()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="vehiculo_seleccionado" class="mt-2" style="display: none;">
                                <div class="alert alert-info">
                                    <strong>Vehículo seleccionado:</strong> <span id="info_vehiculo"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de Documento</label>
                            <select class="form-select" name="tipo_documento_select" id="tipo_documento_select" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="soat">SOAT</option>
                                <option value="tecnomecanica">Tecnomecánica</option>
                                <option value="tarjeta_operacion">Tarjeta de Operación</option>
                                <option value="extintor">Extintor</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nueva Fecha de Vencimiento</label>
                            <input type="date" class="form-control" name="fecha_vencimiento" id="fecha_vencimiento" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Documento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filtrarPor(tipo, estado) {
            const url = new URL(window.location);
            url.searchParams.set('tipo', tipo);
            url.searchParams.set('estado', estado);
            window.location.href = url.toString();
        }
        
        function limpiarFiltros() {
            window.location.href = 'documentos.php';
        }
        
        function aplicarFiltros() {
            const url = new URL(window.location);
            const tipo = document.getElementById('filtroTipo').value;
            const estado = document.getElementById('filtroEstado').value;
            const busqueda = document.getElementById('busqueda').value;
            
            if (tipo) url.searchParams.set('tipo', tipo);
            else url.searchParams.delete('tipo');
            
            if (estado) url.searchParams.set('estado', estado);
            else url.searchParams.delete('estado');
            
            if (busqueda) url.searchParams.set('busqueda', busqueda);
            else url.searchParams.delete('busqueda');
            
            window.location.href = url.toString();
        }
        
        function editarDocumento(vehiculoId, tipoDoc, fechaVenc, placa) {
            document.getElementById('vehiculo_id').value = vehiculoId;
            document.getElementById('tipo_documento').value = tipoDoc;
            document.getElementById('tipo_documento_select').value = tipoDoc;
            document.getElementById('fecha_vencimiento').value = fechaVenc;
            document.getElementById('info_vehiculo').textContent = placa;
            document.getElementById('vehiculo_seleccionado').style.display = 'block';
            document.getElementById('buscar_vehiculo').value = placa;
            
            const modal = new bootstrap.Modal(document.getElementById('modalActualizar'));
            modal.show();
        }
        
        async function buscarVehiculo() {
            const busqueda = document.getElementById('buscar_vehiculo').value;
            if (!busqueda) return;
            
            try {
                const response = await fetch('buscar_vehiculo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `busqueda=${encodeURIComponent(busqueda)}`
                });
                
                const vehiculo = await response.json();
                
                if (vehiculo.success) {
                    document.getElementById('vehiculo_id').value = vehiculo.data.id;
                    document.getElementById('info_vehiculo').textContent = `${vehiculo.data.placa} - ${vehiculo.data.numero_interno} - ${vehiculo.data.propietario}`;
                    document.getElementById('vehiculo_seleccionado').style.display = 'block';
                } else {
                    alert('Vehículo no encontrado');
                    document.getElementById('vehiculo_seleccionado').style.display = 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al buscar vehículo');
            }
        }
        
        // Actualizar tipo de documento cuando se selecciona
        document.getElementById('tipo_documento_select').addEventListener('change', function() {
            document.getElementById('tipo_documento').value = this.value;
        });
        
        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const vehiculoId = document.getElementById('vehiculo_id').value;
            const tipoDoc = document.getElementById('tipo_documento_select').value;
            const fechaVenc = document.getElementById('fecha_vencimiento').value;
            
            if (!vehiculoId) {
                e.preventDefault();
                alert('Debe seleccionar un vehículo');
                return;
            }
            
            if (!tipoDoc) {
                e.preventDefault();
                alert('Debe seleccionar un tipo de documento');
                return;
            }
            
            if (!fechaVenc) {
                e.preventDefault();
                alert('Debe ingresar una fecha de vencimiento');
                return;
            }
            
            // Validar que la fecha no sea muy antigua
            const fechaIngresada = new Date(fechaVenc);
            const haceUnAno = new Date();
            haceUnAno.setFullYear(haceUnAno.getFullYear() - 1);
            
            if (fechaIngresada < haceUnAno) {
                e.preventDefault();
                alert('La fecha de vencimiento no puede ser anterior a hace un año');
                return;
            }
            
            // Confirmar la acción
            const confirmacion = confirm('¿Está seguro de actualizar este documento?');
            if (!confirmacion) {
                e.preventDefault();
            }
        });
        
        // Buscar con Enter
        document.getElementById('busqueda').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                aplicarFiltros();
            }
        });
        
        document.getElementById('buscar_vehiculo').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarVehiculo();
            }
        });
    </script>
</body>
</html>