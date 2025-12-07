<?php
/**
 * Página de Impresión de QR - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2025 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación
verificarSesion(['admin']);

$vehiculo_id = $_GET['vehiculo'] ?? 0;

if (!$vehiculo_id) {
    header('Location: vehiculos.php');
    exit;
}

try {
    $db = getDB();
    
    // Obtener datos del vehículo
    $stmt = $db->prepare("SELECT * FROM vehiculos WHERE id = ? AND estado != 'eliminado'");
    $stmt->execute([$vehiculo_id]);
    $vehiculo = $stmt->fetch();
    
    if (!$vehiculo || !$vehiculo['codigo_qr']) {
        header('Location: vehiculos.php');
        exit;
    }
    
} catch (Exception $e) {
    header('Location: vehiculos.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir QR - <?= htmlspecialchars($vehiculo['numero_interno']) ?> - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos para pantalla */
        @media screen {
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                padding: 2rem;
            }
            
            .print-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                overflow: hidden;
            }
            
            .no-print {
                display: block;
            }
        }
        
        /* Estilos para impresión */
        @media print {
            body {
                background: white !important;
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
            }
            
            .print-container {
                max-width: none;
                margin: 0;
                background: white;
                border-radius: 0;
                box-shadow: none;
                page-break-inside: avoid;
            }
            
            .no-print {
                display: none !important;
            }
            
            .qr-section {
                text-align: center;
                padding: 20mm;
            }
            
            .qr-code {
                width: 80mm !important;
                height: 80mm !important;
                margin: 10mm auto;
                border: 2px solid #000;
                padding: 5mm;
            }
            
            .vehicle-info {
                font-size: 14pt;
                font-weight: bold;
                margin: 5mm 0;
            }
            
            .instructions {
                font-size: 10pt;
                margin-top: 10mm;
                border-top: 1px solid #ccc;
                padding-top: 5mm;
            }
        }
        
        .header-section {
            background: linear-gradient(45deg, #2c3e50, #3498db);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .qr-section {
            padding: 3rem;
            text-align: center;
        }
        
        .qr-code {
            width: 300px;
            height: 300px;
            margin: 2rem auto;
            border: 3px solid #2c3e50;
            border-radius: 15px;
            padding: 20px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .vehicle-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
            border-left: 5px solid #3498db;
        }
        
        .vehicle-info h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .instructions {
            background: #e8f4fd;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid #bee5eb;
        }
        
        .btn-print {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            margin: 0 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(45deg, #6c757d, #495057);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            margin: 0 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Header -->
        <div class="header-section no-print">
            <h1><i class="fas fa-qrcode me-3"></i>Código QR para Impresión</h1>
            <p class="mb-0">Vehículo <?= htmlspecialchars($vehiculo['numero_interno']) ?> - <?= htmlspecialchars($vehiculo['placa']) ?></p>
        </div>
        
        <!-- Sección del QR -->
        <div class="qr-section">
            <!-- Logo/Header para impresión -->
            <div class="text-center mb-4">
                <h2 style="color: #2c3e50; font-weight: bold;">SISTEMA SIGAV</h2>
                <p style="color: #666; margin: 0;">Sistema Integral de Gestión y Alistamiento Vehicular</p>
            </div>
            
            <!-- Código QR -->
            <div class="qr-code">
                <img src="../<?= htmlspecialchars($vehiculo['codigo_qr']) ?>" 
                     alt="Código QR Vehículo <?= htmlspecialchars($vehiculo['numero_interno']) ?>"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkVycm9yIFFSPC90ZXh0Pjwvc3ZnPg=='">
            </div>
            
            <!-- Información del Vehículo -->
            <div class="vehicle-info">
                <h4><i class="fas fa-bus me-2"></i>Información del Vehículo</h4>
                <div class="info-row">
                    <strong>Número Interno:</strong>
                    <span><?= htmlspecialchars($vehiculo['numero_interno']) ?></span>
                </div>
                <div class="info-row">
                    <strong>Placa:</strong>
                    <span><?= htmlspecialchars($vehiculo['placa']) ?></span>
                </div>
                <div class="info-row">
                    <strong>Propietario:</strong>
                    <span><?= htmlspecialchars($vehiculo['propietario']) ?></span>
                </div>
                <div class="info-row">
                    <strong>Estado:</strong>
                    <span class="badge bg-<?= $vehiculo['estado'] == 'activo' ? 'success' : ($vehiculo['estado'] == 'inactivo' ? 'warning' : 'info') ?>">
                        <?= ucfirst($vehiculo['estado']) ?>
                    </span>
                </div>
                <div class="info-row">
                    <strong>Fecha de Generación:</strong>
                    <span><?= date('d/m/Y H:i') ?></span>
                </div>
            </div>
            
            <!-- Instrucciones -->
            <div class="instructions">
                <h5><i class="fas fa-info-circle me-2"></i>Instrucciones de Uso</h5>
                <ul class="text-start">
                    <li><strong>Pegue este QR</strong> en un lugar visible del vehículo</li>
                    <li><strong>Proteja el código</strong> con plástico transparente si es necesario</li>
                    <li><strong>Para alistamiento:</strong> Escanee el QR con la app del inspector</li>
                    <li><strong>Acceso directo:</strong> El QR lleva al formulario de alistamiento</li>
                </ul>
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Código generado por SIGAV - BLACK CROWSOFT © 2025
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Botones de acción (solo en pantalla) -->
        <div class="text-center pb-4 no-print">
            <button type="button" class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Imprimir QR
            </button>
            <a href="vehiculos.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i>Volver a Vehículos
            </a>
        </div>
    </div>
    
    <script>
        // Auto-abrir diálogo de impresión si se especifica en la URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auto_print') === '1') {
            setTimeout(() => {
                window.print();
            }, 1000);
        }
        
        // Función para imprimir
        function imprimirQR() {
            window.print();
        }
        
        // Detectar cuando se cierra el diálogo de impresión
        window.addEventListener('afterprint', function() {
            // Opcional: redirigir de vuelta después de imprimir
            // window.location.href = 'vehiculos.php';
        });
    </script>
</body>
</html>