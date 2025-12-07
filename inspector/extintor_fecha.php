<?php
/**
 * Módulo Inspector - Fecha de Extintor - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2025 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación
verificarSesion(['inspector', 'admin']);

// Obtener ID del vehículo
$vehiculo_id = $_GET['vehiculo'] ?? null;
if (!$vehiculo_id) {
    header('Location: index.php');
    exit();
}

// Obtener información del vehículo
$db = getDB();
$stmt = $db->prepare("SELECT * FROM vehiculos WHERE id = ? AND estado != 'eliminado'");
$stmt->execute([$vehiculo_id]);
$vehiculo = $stmt->fetch();

if (!$vehiculo) {
    header('Location: index.php?error=vehiculo_no_encontrado');
    exit();
}

// Procesar formulario
if ($_POST) {
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
    
    if ($fecha_vencimiento) {
        try {
            // Buscar o crear documento de extintor
            $stmt = $db->prepare("
                SELECT id FROM documentos 
                WHERE vehiculo_id = ? AND tipo_documento = 'extintor'
            ");
            $stmt->execute([$vehiculo_id]);
            $documento = $stmt->fetch();
            
            if ($documento) {
                // Actualizar fecha existente
                $stmt = $db->prepare("
                    UPDATE documentos 
                    SET fecha_vencimiento = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$fecha_vencimiento, $documento['id']]);
            } else {
                // Crear nuevo documento
                $stmt = $db->prepare("
                    INSERT INTO documentos (vehiculo_id, tipo_documento, fecha_vencimiento) 
                    VALUES (?, 'extintor', ?)
                ");
                $stmt->execute([$vehiculo_id, $fecha_vencimiento]);
            }
            
            // Redirigir al alistamiento
            header('Location: /inspector/alistamiento.php?vehiculo=' . $vehiculo_id);
            exit();
            
        } catch (Exception $e) {
            $error = "Error al guardar la fecha: " . $e->getMessage();
        }
    } else {
        $error = "Por favor ingresa una fecha válida";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2c3e50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SIGAV Inspector">
    
    <title>Fecha Extintor - SIGAV Inspector</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #ecf0f1;
            --dark-text: #2c3e50;
            --border-radius: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --vh: 1vh;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light-bg) 0%, #bdc3c7 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            min-height: calc(var(--vh, 1vh) * 100);
            overflow-x: hidden;
        }

        .mobile-container {
            max-width: 100%;
            margin: 0 auto;
            min-height: 100vh;
            min-height: calc(var(--vh, 1vh) * 100);
            display: flex;
            flex-direction: column;
            background: white;
        }

        .mobile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            padding: 1rem;
            text-align: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .mobile-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .mobile-header .subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }

        .content-area {
            flex: 1;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .vehicle-info {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--secondary-color);
        }

        .vehicle-info h3 {
            color: var(--primary-color);
            margin: 0 0 1rem 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .vehicle-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .detail-item {
            text-align: center;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-text);
        }

        .form-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .form-card h4 {
            color: var(--primary-color);
            margin: 0 0 1.5rem 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-text);
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            background: white;
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            border: none;
            font-weight: 500;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert-info {
            background: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
            border-left: 4px solid var(--secondary-color);
        }

        .btn-group-mobile {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 56px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2980b9 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Optimizaciones móviles */
        @media (max-width: 768px) {
            .content-area {
                padding: 1rem;
            }
            
            .vehicle-details {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .btn-group-mobile {
                flex-direction: column;
            }
        }

        /* PWA específico */
        @media (display-mode: standalone) {
            .mobile-header {
                padding-top: env(safe-area-inset-top, 1rem);
            }
        }

        /* Animaciones */
        .form-card, .vehicle-info {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <div class="mobile-header">
            <h1><i class="fas fa-fire-extinguisher me-2"></i>Fecha del Extintor</h1>
            <div class="subtitle">COTRAUTOL - Información requerida para continuar</div>
        </div>

        <div class="content-area">
            <!-- Información del Vehículo -->
            <div class="vehicle-info">
                <h3><i class="fas fa-truck"></i>Información del Vehículo</h3>
                <div class="vehicle-details">
                    <div class="detail-item">
                        <div class="detail-label">Número Interno</div>
                        <div class="detail-value">COTRAUTOL - <?= htmlspecialchars($vehiculo['numero_interno']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Placa</div>
                        <div class="detail-value"><?= htmlspecialchars($vehiculo['placa']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Marca</div>
                        <div class="detail-value"><?= htmlspecialchars($vehiculo['marca']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Modelo</div>
                        <div class="detail-value"><?= htmlspecialchars($vehiculo['modelo']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Formulario -->
            <div class="form-card">
                <h4><i class="fas fa-calendar-alt"></i>Fecha de Vencimiento del Extintor</h4>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    El sistema requiere la fecha de vencimiento del extintor para continuar con el alistamiento.
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="fecha_vencimiento" class="form-label">
                            <i class="fas fa-calendar me-2"></i>Fecha de Vencimiento
                        </label>
                        <input type="date" 
                               class="form-control" 
                               id="fecha_vencimiento" 
                               name="fecha_vencimiento" 
                               required 
                               min="<?= date('Y-m-d') ?>"
                               value="<?= $_POST['fecha_vencimiento'] ?? '' ?>">
                    </div>

                    <div class="btn-group-mobile">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar y Continuar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar altura de viewport para móviles
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
            
            // Actualizar en resize
            window.addEventListener('resize', () => {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            });

            // Auto-focus en el input de fecha
            const fechaInput = document.getElementById('fecha_vencimiento');
            if (fechaInput) {
                setTimeout(() => {
                    fechaInput.focus();
                }, 300);
            }

            // Feedback táctil para botones
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('touchstart', function() {
                    if (navigator.vibrate) {
                        navigator.vibrate(25);
                    }
                });
            });

            // Validación en tiempo real
            if (fechaInput) {
                fechaInput.addEventListener('change', function() {
                    const selectedDate = new Date(this.value);
                    const today = new Date();
                    
                    if (selectedDate <= today) {
                        this.setCustomValidity('La fecha debe ser posterior a hoy');
                        this.style.borderColor = 'var(--danger-color)';
                    } else {
                        this.setCustomValidity('');
                        this.style.borderColor = 'var(--success-color)';
                    }
                });
            }

            // Prevenir zoom en iOS
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    if (window.innerWidth < 768) {
                        document.querySelector('meta[name=viewport]').setAttribute(
                            'content', 
                            'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'
                        );
                    }
                });
                
                input.addEventListener('blur', function() {
                    document.querySelector('meta[name=viewport]').setAttribute(
                        'content', 
                        'width=device-width, initial-scale=1.0, user-scalable=no'
                    );
                });
            });
        });
    </script>
</body>
</html>