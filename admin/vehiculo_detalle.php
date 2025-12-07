<?php
/**
 * Detalles del Vehículo - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2025 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación
verificarSesion(['admin']);

$vehiculo_id = $_GET['id'] ?? 0;

if (!$vehiculo_id) {
    header('Location: vehiculos.php');
    exit;
}

$db = getDB();

// Obtener datos del vehículo
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
    WHERE v.id = ?
");
$stmt->execute([$vehiculo_id]);
$vehiculo = $stmt->fetch();

if (!$vehiculo) {
    header('Location: vehiculos.php');
    exit;
}

// Solo información del vehículo - sin alistamientos

// Obtener documentos del vehículo
$stmt = $db->prepare("
    SELECT d.*, 
           CASE 
               WHEN d.fecha_vencimiento < CURDATE() THEN 'danger'
               WHEN d.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'warning'
               ELSE 'success'
           END as estado_color,
           DATEDIFF(d.fecha_vencimiento, CURDATE()) as dias_vencimiento
    FROM documentos d
    WHERE d.vehiculo_id = ?
    ORDER BY d.fecha_vencimiento ASC
");
$stmt->execute([$vehiculo_id]);
$documentos = $stmt->fetchAll();

// Solo estadísticas básicas del vehículo (sin alistamientos)
$stats = [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Vehículo - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; position: relative; }
        body::before { content: ""; position: fixed; inset: 0; background: rgba(11, 30, 63, 0.55); pointer-events: none; z-index: 0; }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .sidebar { min-height: 100vh; position: sticky; top: 0; overflow: hidden; background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%); color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 10px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .vehicle-header { background: rgba(255,255,255,0.95); border-radius: 15px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .qr-code { background: white; border-radius: 10px; padding: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .stat-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border: none; transition: transform 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); }
        .timeline { position: relative; padding-left: 2rem; }
        .timeline::before { content: ''; position: absolute; top: 0; bottom: 0; left: 10px; width: 4px; background: #1d4ed8; border-radius: 2px; }
        .timeline-item { position: relative; padding: 1rem 1rem 1rem 2rem; margin-bottom: 1rem; background: rgba(255,255,255,0.9); border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.06); }
        .timeline-item::before { content: ''; position: absolute; top: 1rem; left: 2px; width: 14px; height: 14px; background: white; border: 3px solid #1d4ed8; border-radius: 50%; }
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
                            <small class="text-light">BLACK CROWSOFT</small>
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
                    <h1 class="h2">
                        <a href="vehiculos.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        Detalles del Vehículo
                    </h1>
                    <div class="btn-toolbar">
                        <button type="button" class="btn btn-outline-primary me-2" onclick="editarVehiculo()">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                </div>

                <!-- Header del vehículo -->
                <div class="vehicle-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-1">
                                Vehículo <?= htmlspecialchars($vehiculo['numero_interno']) ?>
                            </h2>
                            <p class="mb-2">
                                <strong>Placa:</strong> <?= htmlspecialchars($vehiculo['placa']) ?> | 
                                <strong>Propietario:</strong> <?= htmlspecialchars($vehiculo['propietario']) ?>
                            </p>
                            <span class="badge bg-<?= $vehiculo['estado_color'] ?> fs-6">
                                <?= ucfirst(str_replace('_', ' ', $vehiculo['estado'])) ?>
                            </span>
                        </div>
                        <div class="col-md-4 text-center">
                            <?php if ($vehiculo['codigo_qr']): ?>
                                <img src="../<?= htmlspecialchars($vehiculo['codigo_qr']) ?>" class="qr-code mb-3" alt="Código QR">
                                <br>
                            <?php endif; ?>
                            <button class="btn btn-light" onclick="generarQR(<?= $vehiculo['id'] ?>)" title="<?= $vehiculo['codigo_qr'] ? 'Regenerar código QR' : 'Generar código QR' ?>">
                                <i class="fas fa-qrcode fa-3x"></i>
                                <p class="mt-2 mb-0"><?= $vehiculo['codigo_qr'] ? 'Regenerar QR' : 'Generar QR' ?></p>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Solo información del vehículo -->

                <div class="row">
                    <!-- Información del vehículo -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información del Vehículo</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Número Interno:</strong></td>
                                        <td><?= htmlspecialchars($vehiculo['numero_interno']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Placa:</strong></td>
                                        <td><?= htmlspecialchars($vehiculo['placa']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Propietario:</strong></td>
                                        <td><?= htmlspecialchars($vehiculo['propietario']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Estado:</strong></td>
                                        <td>
                                            <span class="badge bg-<?= $vehiculo['estado_color'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $vehiculo['estado'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fecha de Registro:</strong></td>
                                        <td><?= date('d/m/Y H:i', strtotime($vehiculo['fecha_registro'])) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Documentos -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file-alt"></i> Documentos</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($documentos)): ?>
                                    <p class="text-muted text-center">No hay documentos registrados</p>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($documentos as $doc): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($doc['tipo_documento']) ?></h6>
                                                <?php 
                                                    $dias = (int)($doc['dias_vencimiento'] ?? 0);
                                                    $class = ($dias < 0) ? 'text-danger' : (($dias <= 30) ? 'text-warning' : 'text-success');
                                                ?>
                                                <small class="<?= $class ?>">
                                                    Vence: <?= date('d/m/Y', strtotime($doc['fecha_vencimiento'])) ?>
                                                    <?php if ($dias < 0): ?>
                                                        (Vencido hace <?= abs($dias) ?> días)
                                                    <?php elseif ($dias <= 30): ?>
                                                        (Vence en <?= $dias ?> días)
                                                    <?php else: ?>
                                                        (Faltan <?= $dias ?> días)
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-<?= $doc['estado_color'] ?>">
                                                <?php if ($doc['dias_vencimiento'] < 0): ?>
                                                    Vencido
                                                <?php elseif ($doc['dias_vencimiento'] <= 30): ?>
                                                    Por vencer
                                                <?php else: ?>
                                                    Vigente
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sección eliminada: solo información del vehículo -->
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarVehiculo() {
            window.location.href = 'vehiculos.php?editar=<?= $vehiculo['id'] ?>';
        }
        
        // Función eliminada: solo información del vehículo
        
        function generarQR(id) {
            // Detectar si ya existe un QR para este vehículo
            const hasQR = document.querySelector('img.qr-code') !== null;
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
    </script>

    <div class="text-center mt-4 py-3 border-top">
        <small class="text-muted">
© 2025 <a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-decoration-none">BLACKCROWSOFT.COM</a> - Todos los derechos reservados
        </small>
    </div>
</body>
</html>
