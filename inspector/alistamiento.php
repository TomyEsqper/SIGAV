<?php
/**
 * Módulo Inspector - Alistamiento de Vehículo - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2024 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación
verificarSesion(['inspector', 'admin']);

$vehiculo_id = $_GET['vehiculo'] ?? 0;

if (!$vehiculo_id) {
    header('Location: index.php');
    exit;
}

// Estado del Wizard por Categorías
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$wizard = isset($_GET['wizard']);
$current_step = isset($_GET['step']) ? max(1, intval($_GET['step'])) : 1;

// Procesar envío del formulario de categorías
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wizard_step'])) {
    $step = intval($_POST['wizard_step']);
    // Guardar respuesta de la categoría en sesión (scoped por vehículo)
    if (!isset($_SESSION['alistamiento_wizard'])) {
        $_SESSION['alistamiento_wizard'] = [];
    }
    if (!isset($_SESSION['alistamiento_wizard'][$vehiculo_id])) {
        $_SESSION['alistamiento_wizard'][$vehiculo_id] = [];
    }

    // Paso 1: FIJO O SUPERNUMERARIO
    if ($step === 1) {
        $_SESSION['alistamiento_wizard'][$vehiculo_id]['tipo_designacion'] = $_POST['tipo_designacion'] ?? null;
    }

    // Ir al siguiente paso (placeholder hasta recibir más categorías)
    $nextStep = $step + 1;
    header('Location: alistamiento.php?vehiculo=' . $vehiculo_id . '&wizard=1&step=' . $nextStep);
    exit;
}

try {
    $db = getDB();
    
    // Obtener datos del vehículo
    $stmt = $db->prepare("SELECT * FROM vehiculos WHERE id = ? AND estado != 'eliminado'");
    $stmt->execute([$vehiculo_id]);
    $vehiculo = $stmt->fetch();
    
    if (!$vehiculo) {
        header('Location: index.php');
        exit;
    }
    
    // Verificar si ya existe un alistamiento en progreso
    $stmt = $db->prepare("
        SELECT * FROM alistamientos 
        WHERE vehiculo_id = ? AND DATE(fecha_alistamiento) = CURDATE()
        ORDER BY fecha_alistamiento DESC 
        LIMIT 1
    ");
    $stmt->execute([$vehiculo_id]);
    $alistamiento_existente = $stmt->fetch();
    
} catch (Exception $e) {
    $error = "Error al cargar el vehículo: " . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alistamiento - <?= htmlspecialchars($vehiculo['numero_interno']) ?> - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .alistamiento-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .vehiculo-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }
        
        .alistamiento-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .estado-vehiculo {
            font-size: 1.2rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
        }
        
        .btn-iniciar {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .btn-iniciar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-continuar {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .btn-continuar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.4);
            color: white;
        }
        
        .info-card {
            background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
        }
        
        .warning-card {
            background: linear-gradient(45deg, #fff3e0, #fce4ec);
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="alistamiento-container">
        <!-- Header del Vehículo -->
        <div class="vehiculo-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3">
                        <i class="fas fa-bus text-primary me-2"></i>
                        Vehículo <?= htmlspecialchars($vehiculo['numero_interno']) ?>
                    </h2>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Placa:</strong> <?= htmlspecialchars($vehiculo['placa']) ?></p>
                            <p class="mb-2"><strong>Propietario:</strong> <?= htmlspecialchars($vehiculo['propietario']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Estado:</strong> 
                                <span class="badge estado-vehiculo bg-<?= $vehiculo['estado'] == 'activo' ? 'success' : ($vehiculo['estado'] == 'inactivo' ? 'warning' : 'info') ?>">
                                    <?= ucfirst($vehiculo['estado']) ?>
                                </span>
                            </p>
                            <p class="mb-0"><strong>Inspector:</strong> <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-outline-secondary mb-2">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <br>
                    <small class="text-muted">
                        <?= date('d/m/Y H:i') ?>
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Estado del Alistamiento -->
        <div class="alistamiento-card">
            <?php if ($alistamiento_existente): ?>
                <!-- Alistamiento Existente -->
                <div class="warning-card mb-4">
                    <h4 class="text-warning mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Alistamiento Existente
                    </h4>
                    <p class="mb-3">
                        Ya existe un alistamiento para este vehículo realizado hoy a las 
                        <strong><?= date('H:i', strtotime($alistamiento_existente['fecha_alistamiento'])) ?></strong>
                    </p>
                    <p class="mb-3">
                        <strong>Estado Final:</strong> 
                        <span class="badge bg-<?= $alistamiento_existente['estado_final'] == 'verde' ? 'success' : ($alistamiento_existente['estado_final'] == 'amarillo' ? 'warning' : 'danger') ?>">
                            <?= strtoupper($alistamiento_existente['estado_final']) ?>
                        </span>
                    </p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <a href="../admin/alistamiento_detalle.php?id=<?= $alistamiento_existente['id'] ?>" 
                               class="btn btn-outline-primary w-100">
                                <i class="fas fa-eye me-2"></i>Ver Detalle
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <?php if ($alistamiento_existente['estado_final'] != 'verde'): ?>
                                <button type="button" class="btn btn-continuar w-100" onclick="iniciarAlistamientoParcial()">
                                    <i class="fas fa-tools me-2"></i>Alistamiento Parcial
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-success w-100" disabled>
                                    <i class="fas fa-check me-2"></i>Completado
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Nuevo Alistamiento -->
                <div class="info-card mb-4">
                    <h4 class="text-primary mb-3">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Nuevo Alistamiento
                    </h4>
                    <p class="mb-3">
                        Vas a realizar un alistamiento completo del vehículo 
                        <strong><?= htmlspecialchars($vehiculo['numero_interno']) ?></strong>
                    </p>
                    <p class="mb-4">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        El sistema evaluará todos los ítems del checklist y determinará el estado final del vehículo.
                    </p>
                    
                    <div class="text-center">
                        <form method="GET" action="alistamiento_wizard.php" style="display:inline-block;">
                            <input type="hidden" name="vehiculo" value="<?= $vehiculo_id ?>">
                            <input type="hidden" name="step" value="1">
                            <button type="submit" class="btn btn-iniciar">
                                <i class="fas fa-play me-2"></i>Iniciar Alistamiento Completo
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Información del Proceso -->
            <div class="row">
                <div class="col-md-4 text-center">
                    <div class="p-3">
                        <i class="fas fa-list-check fa-2x text-primary mb-2"></i>
                        <h6>Checklist Digital</h6>
                        <small class="text-muted">Evaluación ítem por ítem</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="p-3">
                        <i class="fas fa-camera fa-2x text-success mb-2"></i>
                        <h6>Evidencias Fotográficas</h6>
                        <small class="text-muted">Fotos obligatorias para fallas</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="p-3">
                        <i class="fas fa-traffic-light fa-2x text-warning mb-2"></i>
                        <h6>Sistema Semáforo</h6>
                        <small class="text-muted">Verde, Amarillo o Rojo</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($wizard): ?>
    <!-- Wizard por Categorías -->
    <div class="alistamiento-card mt-4">
        <div class="info-card mb-3">
            <h4 class="text-primary mb-2">
                <i class="fas fa-list-check me-2"></i>
                Checklist por Categorías
            </h4>
            <small class="text-muted">Vehículo: <strong><?= htmlspecialchars($vehiculo['numero_interno']) ?></strong></small>
        </div>

        <?php if ($current_step === 1): ?>
        <form method="POST" action="alistamiento.php?vehiculo=<?= $vehiculo_id ?>&wizard=1&step=<?= $current_step ?>">
            <input type="hidden" name="wizard_step" value="<?= $current_step ?>">

            <div class="card mb-3">
                <div class="card-header">
                    <strong>FIJO O SUPERNUMERARIO</strong>
                    <span class="badge bg-secondary ms-2">Obligatorio</span>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tipo_designacion" id="tipo_fijo" value="FIJO" required>
                        <label class="form-check-label" for="tipo_fijo">FIJO</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tipo_designacion" id="tipo_supernumerario" value="SUPERNUMERARIO" required>
                        <label class="form-check-label" for="tipo_supernumerario">SUPERNUMERARIO</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" disabled>
                    <i class="fas fa-arrow-left me-2"></i>Anterior
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-2"></i>Siguiente
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="card">
            <div class="card-body">
                <p class="mb-2"><strong>Próxima categoría:</strong> en construcción</p>
                <p class="text-muted mb-0">Envía la siguiente categoría cuando me la compartas.</p>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-3">
            <a href="alistamiento.php?vehiculo=<?= $vehiculo_id ?>&wizard=1&step=<?= $current_step - 1 ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Anterior
            </a>
            <button type="button" class="btn btn-secondary" disabled>
                <i class="fas fa-arrow-right me-2"></i>Siguiente
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function iniciarAlistamientoCompleto() {
            if (confirm('¿Iniciar alistamiento completo del vehículo <?= htmlspecialchars($vehiculo['numero_interno']) ?>?')) {
                // Aquí se implementaría la lógica para iniciar el alistamiento
                alert('Funcionalidad en desarrollo - Módulo de Checklist Digital');
            }
        }
        
        function iniciarAlistamientoParcial() {
            if (confirm('¿Iniciar alistamiento parcial para revisar ítems previamente marcados como malos?')) {
                // Redirigir al flujo de alistamiento parcial
                window.location.href = '/inspector/alistamiento_parcial.php?vehiculo=<?= $vehiculo_id ?>';
            }
        }
    </script>
    
    <div class="text-center mt-4 py-3">
        <small class="text-white-50">
            © 2025 <strong>BLACK CROWSOFT</strong> - Todos los derechos reservados
        </small>
    </div>
</body>
</html>