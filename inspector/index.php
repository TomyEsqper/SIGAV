<?php
/**
 * Módulo Inspector - Selección de Vehículo - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2024 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación
verificarSesion(['inspector', 'admin']);

$mensaje = '';
$tipo_mensaje = '';

// Procesar selección de vehículo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_seleccionado = null;
    
    if (isset($_POST['numero_interno'])) {
        // Búsqueda por número interno
        $numero_interno = strtoupper(trim($_POST['numero_interno']));
        
        // Validar formato COTRAUTOL (CO con o sin guión + números)
        if (!preg_match('/^CO-?[0-9]{3,6}$/', $numero_interno)) {
            $mensaje = 'Formato inválido. Use el formato COTRAUTOL: CO con 3-6 dígitos (Ej: CO-001 o CO001)';
            $tipo_mensaje = 'warning';
        } else {
            // Normalizar a formato con guión (CO-XXXX)
            $numero_interno = preg_replace('/^CO-?([0-9]{3,6})$/', 'CO-$1', $numero_interno);
            try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM vehiculos WHERE numero_interno = ? AND estado != 'eliminado'");
            $stmt->execute([$numero_interno]);
            $vehiculo_seleccionado = $stmt->fetch();
            
            if ($vehiculo_seleccionado) {
                // Verificar si el vehículo está activo
                if ($vehiculo_seleccionado['estado'] === 'activo') {
                    // Verificar si el extintor necesita fecha de vencimiento
                    $stmt_extintor = $db->prepare("SELECT * FROM documentos WHERE vehiculo_id = ? AND tipo_documento = 'extintor'");
                    $stmt_extintor->execute([$vehiculo_seleccionado['id']]);
                    $extintor = $stmt_extintor->fetch();
                    
                    if (!$extintor || !$extintor['fecha_vencimiento']) {
                        // Redirigir a página de extintor si no existe o no tiene fecha
                        header('Location: /inspector/extintor_fecha.php?vehiculo=' . $vehiculo_seleccionado['id']);
                        exit;
                    } else {
                        header('Location: /inspector/alistamiento.php?vehiculo=' . $vehiculo_seleccionado['id']);
                        exit;
                    }
                } else {
                    $mensaje = "El vehículo está en estado: " . ucfirst($vehiculo_seleccionado['estado']);
                    $tipo_mensaje = "warning";
                }
            } else {
                $mensaje = "Vehículo no encontrado";
                $tipo_mensaje = "danger";
            }
            
            } catch (Exception $e) {
                $mensaje = "Error al buscar vehículo: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Obtener últimos alistamientos del inspector
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT a.*, v.numero_interno, v.placa, v.propietario
        FROM alistamientos a
        JOIN vehiculos v ON a.vehiculo_id = v.id
        WHERE a.inspector_id = ?
        ORDER BY a.fecha_alistamiento DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $ultimos_alistamientos = $stmt->fetchAll();
    
} catch (Exception $e) {
    $ultimos_alistamientos = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Inspector - SIGAV</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SIGAV Inspector">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="../manifest.json">
    
    <!-- Favicons y iconos para diferentes dispositivos -->
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/icons/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/icons/apple-touch-icon.png">
    <link rel="mask-icon" href="../assets/icons/safari-pinned-tab.svg" color="#2c3e50">
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <!-- QR Scanner Library -->
    <script src="qr-scanner.umd.min.js"></script>
    
    <style>
        :root {
            /* Paleta inspirada en login.php */
            --primary-gradient: linear-gradient(135deg, #edf2ff 0%, #e9efff 100%);
            --success-gradient: linear-gradient(45deg, #22c55e, #20c997);
            --info-gradient: linear-gradient(135deg, #1d4ed8 0%, #764ba2 100%);
            --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            --border-radius: 20px;
            --card-bg: rgba(255, 255, 255, 0.08);
            --card-bg-hover: rgba(255, 255, 255, 0.12);
            --text-primary: #0b1e3f;
            --text-secondary: #6c757d;
            --border-color: rgba(255, 255, 255, 0.28);
            --ring-color: rgba(102, 126, 234, 0.25);
        }
        
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        input, textarea {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }
        
        body {
            /* Fondo con imagen más visible y overlay de color (azul → morado) */
            background:
                linear-gradient(135deg, rgba(29, 78, 216, 0.35), rgba(118, 75, 162, 0.35)),
                url('../imagendefondo.jpg') center/cover no-repeat fixed;
            background-blend-mode: overlay;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }
        
        .mobile-container {
            width: 100%;
            min-height: 100vh;
            padding: 1rem;
            box-sizing: border-box;
        }
        
        .header-mobile {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            text-align: center;
            backdrop-filter: blur(10px);
            position: relative;
        }
        
        .header-mobile h1 {
            font-size: 1.6rem;
            margin: 0;
            color: var(--text-primary);
            font-weight: 700;
        }
        
        .header-mobile .inspector-name {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        .subtitle { color: var(--text-secondary); }

        /* Mejorar visibilidad de iconos sobre fondos con imagen */
        .header-mobile h1 i,
        .action-card h3 i {
            background: rgba(255, 255, 255, 0.85);
            color: #1d4ed8;
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 6px;
            box-shadow: 0 6px 14px rgba(29, 78, 216, 0.18);
        }

        .inspector-name i {
            background: rgba(255, 255, 255, 0.8);
            color: #0b1e3f;
            border-radius: 999px;
            padding: 4px;
            margin-right: 4px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }

        /* Evitar deformación de iconos dentro de botones */
        .btn .fa,
        .btn .fas,
        .btn .far,
        .btn .fab {
            background: transparent;
            padding: 0;
            box-shadow: none;
            color: inherit;
        }

        /* Mejorar contraste de títulos y subtítulos */
        .subtitle { text-shadow: 0 1px 3px  rgb(253, 254, 255); }

        /* Se elimina toggle de tema: no se requiere en este diseño */
        
        .action-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
        }
        
        .scan-button {
            background: var(--success-gradient);
            border: none;
            color: white;
            padding: 1.2rem;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .scan-button:hover, .scan-button:active {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .manual-button {
            background: var(--info-gradient);
            border: none;
            color: white;
            padding: 1rem;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(29, 78, 216, 0.25);
        }
        
        .manual-button:hover, .manual-button:active {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(29, 78, 216, 0.35);
            color: white;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            background: #ffffff;
            color: #111827;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem var(--ring-color);
        }
        
        .history-mobile {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-top: 1rem;
        }
        
        .history-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: background-color 0.3s ease;
        }
        
        .history-item:hover {
            background-color: var(--card-bg-hover);
        }
        
        .history-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .camera-container {
            text-align: center;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 15px;
            margin: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .camera-preview {
            width: 100%;
            max-width: 300px;
            height: 300px;
            border-radius: 15px;
            margin: 1rem auto;
            display: block;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }
        
        .qr-result {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }
        
        /* Mobile optimizations */
        @media (max-width: 576px) {
            .mobile-container {
                padding: 0.5rem;
            }
            
            .header-mobile {
                padding: 1rem;
                margin-bottom: 0.5rem;
            }
            
            .action-card {
                padding: 1rem;
                margin-bottom: 0.5rem;
            }
            
            .scan-button {
                padding: 1rem;
                font-size: 1rem;
            }
            
            .manual-button {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
        }
        
        /* PWA specific styles */
        @media (display-mode: standalone) {
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
            }
        }
    </style>
        
    </style>
    <style>
        /* Overrides: permitir scroll y reservar espacio de footer */
        :root { --footer-height: 56px; }
        html, body { min-height: 100vh; overflow-x: hidden; overflow-y: auto; }
        #app-root { min-height: calc(100vh - var(--footer-height)); display: flex; flex-direction: column; gap: 0.4rem; }
        .page-footer { height: var(--footer-height); margin: 0; padding: 0.5rem 0; }

        /* Ajustes de cabecera */
        .header-mobile { padding: 0.5rem 0.7rem; }
        .header-mobile h1 { font-size: 1.2rem; }
        .header-mobile .inspector-name { margin-top: 0.2rem; font-size: 0.9rem; }
        .subtitle { font-size: 0.9rem; }

        /* Contenedor principal */
        .mobile-container { min-height: auto; max-height: none; height: auto; }

        /* Tarjeta de acción y botones */
        .action-card { padding: 0.7rem; display: flex; flex-direction: column; gap: 0.5rem; flex: 1 1 auto; overflow: visible; }
        .action-card .text-center { flex: 0 0 auto; }
        .scan-button { padding: 0.7rem; font-size: 0.95rem; margin-bottom: 0.4rem; }
        .manual-button { padding: 0.65rem; font-size: 0.9rem; }

        /* Inputs */
        .form-control { padding: 0.6rem 0.85rem; font-size: 0.9rem; }

        /* Cámara */
        .camera-container { flex: 1 1 auto; max-height: 40vh; padding: 0.45rem; }
        .camera-preview { height: 20vh; max-width: 100%; }

        /* Resultados y alertas */
        .qr-result { padding: 0.5rem 0.7rem; margin-top: 0.4rem; }
        .alert { margin: 0.2rem 0; padding: 0.55rem 0.75rem; }

        /* Historial oculto en móvil */
        .history-mobile { display: none !important; }

        @media (max-width: 576px) {
            #app-root { gap: 0.35rem; }
            .header-mobile h1 { font-size: 1.1rem; }
            .subtitle { font-size: 0.85rem; }
            .camera-container { max-height: 36vh; }
            .camera-preview { height: 18vh; }
        }
    </style>
</head>
<body>
    <div class="mobile-container" id="app-root">
        <!-- Header Mobile -->
        <div class="header-mobile text-center">
            <img src="../logo.png" alt="SIGAV" class="navbar-logo mb-2">
            <h1>
                <i class="fas fa-clipboard-check me-2"></i>
                SIGAV Inspector
            </h1>
            <div class="inspector-name">
                <i class="fas fa-user me-1"></i>
                <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?> me-2"></i>
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Acción Principal: Escanear QR -->
        <div class="action-card">
            <div class="text-center">
                <h3 class="mb-3">
                    <i class="fas fa-qrcode me-2"></i>
                    Escanear Vehículo
                </h3>
                <button type="button" id="btn-activar-camara" class="btn scan-button" onclick="iniciarEscaneoQR()">
                    <i class="fas fa-camera me-2"></i>
                    Activar Cámara QR
                </button>
            </div>
            
            <!-- Cámara para QR -->
            <div class="camera-container" id="camera-container" style="display: none;">
                <video id="camera-preview" class="camera-preview" autoplay playsinline muted></video>
                <canvas id="qr-canvas" style="display: none;"></canvas>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-danger me-2" onclick="detenerEscanerQR()">
                        <i class="fas fa-times me-2"></i>
                        Cancelar
                    </button>
                </div>
            </div>
            
            <!-- Resultado QR -->
            <div class="qr-result" id="qr-result">
                <h5><i class="fas fa-check-circle text-success me-2"></i>Vehículo Detectado</h5>
                <p id="qr-text" class="mb-3"></p>
                <button type="button" class="btn btn-success w-100" onclick="procesarQR()">
                    <i class="fas fa-arrow-right me-2"></i>
                    Continuar con este vehículo
                </button>
            </div>
        </div>

        <!-- Opción Manual -->
        <div class="action-card">
            <div class="text-center">
                <h4 class="mb-3">
                    <i class="fas fa-keyboard me-2"></i>
                    Ingreso Manual
                </h4>
                <button type="button" class="btn manual-button" onclick="toggleManualInput()">
                    <i class="fas fa-edit me-2"></i>
                    Escribir Número Interno
                </button>
            </div>
            
            <!-- Formulario Manual -->
            <div class="manual-input-section" id="manual-input-section" style="display: none;">
                <form method="POST" action="" class="mt-3">
                    <div class="mb-3">
                        <label for="numero_interno" class="form-label fw-bold">
                            <i class="fas fa-hashtag me-2"></i>
                            Número Interno del Vehículo
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="numero_interno" 
                               name="numero_interno" 
                               placeholder="Ej: CO-001, CO-0123" 
                               required
                               autocomplete="off"
                               pattern="CO-?[0-9]{3,6}"
                               title="Formato: CO con 3-6 dígitos, con o sin guión (Ej: CO-001 o CO001)"
                               maxlength="9"
                               style="text-align: center; font-size: 1.2rem; font-weight: bold; text-transform: uppercase;">
                        <div class="input-help mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Formato COTRAUTOL: CO- + números (Ej: CO-001, CO-123, CO-1234). También se acepta sin guión.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-search me-2"></i>
                        Buscar Vehículo
                    </button>
                </form>
            </div>
        </div>

        <!-- Historial de Alistamientos -->
        <?php if (!empty($ultimos_alistamientos)): ?>
        <div class="history-mobile">
            <h4 class="mb-3">
                <i class="fas fa-history me-2"></i>
                Últimos Alistamientos
            </h4>
            
            <?php foreach ($ultimos_alistamientos as $alistamiento): ?>
            <div class="history-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-bold">
                            <i class="fas fa-bus me-2"></i>
                            <?= htmlspecialchars($alistamiento['numero_interno']) ?>
                        </h6>
                        <div class="small text-muted mb-1">
                            <i class="fas fa-id-card me-1"></i>
                            <?= htmlspecialchars($alistamiento['placa']) ?>
                        </div>
                        <div class="small text-muted mb-1">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($alistamiento['propietario']) ?>
                        </div>
                        <div class="small text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?= date('d/m/Y H:i', strtotime($alistamiento['fecha_alistamiento'])) ?>
                        </div>
                    </div>
                    <div class="ms-2">
                        <span class="status-badge 
                            <?= $alistamiento['estado_final'] === 'verde' ? 'bg-success' : 
                                ($alistamiento['estado_final'] === 'amarillo' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                            <?= ucfirst($alistamiento['estado_final']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Botón de Logout -->
        <div class="text-center mt-3 mb-4">
            <a href="../admin/logout.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-sign-out-alt me-2"></i>
                Cerrar Sesión
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tema fijo sin alternancia: se eliminó la lógica de toggle
        let qrScanner = null;
        let scanning = false;
        let qrDetected = '';

        function detectarCompatibilidadNavegador() {
            console.log('=== DETECCIÓN DE COMPATIBILIDAD ===');
            
            const userAgent = navigator.userAgent;
            console.log('User Agent:', userAgent);
            
            // Detectar navegador y versión
            let navegador = 'Desconocido';
            let version = 'Desconocida';
            
            if (userAgent.includes('Chrome/')) {
                navegador = 'Chrome';
                version = userAgent.match(/Chrome\/(\d+)/)?.[1] || 'Desconocida';
            } else if (userAgent.includes('Firefox/')) {
                navegador = 'Firefox';
                version = userAgent.match(/Firefox\/(\d+)/)?.[1] || 'Desconocida';
            } else if (userAgent.includes('Safari/') && !userAgent.includes('Chrome')) {
                navegador = 'Safari';
                version = userAgent.match(/Version\/(\d+)/)?.[1] || 'Desconocida';
            } else if (userAgent.includes('Edge/')) {
                navegador = 'Edge Legacy';
                version = userAgent.match(/Edge\/(\d+)/)?.[1] || 'Desconocida';
            } else if (userAgent.includes('Edg/')) {
                navegador = 'Edge Chromium';
                version = userAgent.match(/Edg\/(\d+)/)?.[1] || 'Desconocida';
            }
            
            console.log(`Navegador: ${navegador} ${version}`);
            
            // Verificar compatibilidad mínima
            const esCompatible = verificarVersionMinima(navegador, parseInt(version));
            console.log('¿Es compatible?', esCompatible);
            
            return { navegador, version, esCompatible };
        }

        function verificarVersionMinima(navegador, version) {
            const versionesMinimas = {
                'Chrome': 53,
                'Firefox': 36,
                'Safari': 11,
                'Edge Chromium': 79,
                'Edge Legacy': 12
            };
            
            return versionesMinimas[navegador] ? version >= versionesMinimas[navegador] : false;
        }

        function implementarPolyfillGetUserMedia() {
            console.log('=== IMPLEMENTANDO POLYFILLS ===');
            
            // Polyfill para navigator.mediaDevices
            if (!navigator.mediaDevices) {
                console.log('Creando polyfill para navigator.mediaDevices');
                navigator.mediaDevices = {};
            }
            
            // Polyfill para getUserMedia
            if (!navigator.mediaDevices.getUserMedia) {
                console.log('Implementando polyfill para getUserMedia');
                
                navigator.mediaDevices.getUserMedia = function(constraints) {
                    // Buscar getUserMedia en diferentes ubicaciones (navegadores antiguos)
                    const getUserMedia = navigator.getUserMedia ||
                                       navigator.webkitGetUserMedia ||
                                       navigator.mozGetUserMedia ||
                                       navigator.msGetUserMedia;
                    
                    if (!getUserMedia) {
                        return Promise.reject(new Error('getUserMedia no está soportado en este navegador'));
                    }
                    
                    // Convertir callback a Promise
                    return new Promise((resolve, reject) => {
                        getUserMedia.call(navigator, constraints, resolve, reject);
                    });
                };
            }
            
            // Polyfill para enumerateDevices
            if (!navigator.mediaDevices.enumerateDevices) {
                console.log('Implementando polyfill básico para enumerateDevices');
                
                navigator.mediaDevices.enumerateDevices = function() {
                    return Promise.resolve([
                        {
                            deviceId: 'default',
                            kind: 'videoinput',
                            label: 'Cámara predeterminada',
                            groupId: 'default'
                        }
                    ]);
                };
            }
        }

        async function verificarDispositivosMedia() {
            console.log('=== DIAGNÓSTICO DE CÁMARAS ===');
            
            // PASO 1: Detectar compatibilidad del navegador
            const { navegador, version, esCompatible } = detectarCompatibilidadNavegador();
            
            if (!esCompatible) {
                throw new Error(`Navegador no compatible: ${navegador} ${version}. Se requiere una versión más reciente.`);
            }
            
            // PASO 2: Implementar polyfills si es necesario
            implementarPolyfillGetUserMedia();
            
            // PASO 3: Verificar soporte después de polyfills
            console.log('navigator.mediaDevices:', !!navigator.mediaDevices);
            console.log('getUserMedia:', !!navigator.mediaDevices?.getUserMedia);
            console.log('enumerateDevices:', !!navigator.mediaDevices?.enumerateDevices);
            
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('MediaDevices API no soportada incluso con polyfills');
            }
            
            try {
                // Enumerar dispositivos
                const devices = await navigator.mediaDevices.enumerateDevices();
                console.log('Todos los dispositivos:', devices);
                
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                console.log('Dispositivos de video:', videoDevices);
                
                if (videoDevices.length === 0) {
                    throw new Error('No se encontraron dispositivos de video');
                }
                
                return videoDevices;
            } catch (error) {
                console.error('Error al enumerar dispositivos:', error);
                throw error;
            }
        }

        async function probarAccesoCamara() {
            console.log('=== PROBANDO ACCESO DIRECTO A CÁMARA ===');
            
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment' 
                    } 
                });
                
                console.log('✅ Acceso a cámara exitoso:', stream);
                
                // Detener el stream inmediatamente
                stream.getTracks().forEach(track => track.stop());
                
                return true;
            } catch (error) {
                console.error('❌ Error de acceso a cámara:', error);
                
                // Intentar con configuración básica
                try {
                    const basicStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    console.log('✅ Acceso básico a cámara exitoso:', basicStream);
                    basicStream.getTracks().forEach(track => track.stop());
                    return true;
                } catch (basicError) {
                    console.error('❌ Error de acceso básico:', basicError);
                    throw basicError;
                }
            }
        }

        async function iniciarEscaneoQR() {
            console.log('Iniciando escáner QR...');
            
            // Esperar a que la librería QrScanner esté completamente cargada
            if (typeof QrScanner === 'undefined') {
                console.log('Esperando a que se cargue QrScanner...');
                await new Promise((resolve) => {
                    const checkQrScanner = () => {
                        if (typeof QrScanner !== 'undefined') {
                            resolve();
                        } else {
                            setTimeout(checkQrScanner, 100);
                        }
                    };
                    checkQrScanner();
                });
            }
            
            const cameraContainer = document.getElementById('camera-container');
            const video = document.getElementById('camera-preview');
            
            console.log('Elementos encontrados:', {
                cameraContainer: !!cameraContainer,
                video: !!video
            });
            
            // Forzar visibilidad del contenedor y video
            cameraContainer.style.display = 'block';
            cameraContainer.style.visibility = 'visible';
            cameraContainer.style.opacity = '1';
            
            video.style.display = 'block';
            video.style.visibility = 'visible';
            video.style.opacity = '1';
            
            // Verificar que los elementos se muestren
            console.log('Estado después de mostrar:', {
                containerDisplay: cameraContainer.style.display,
                videoDisplay: video.style.display,
                containerVisible: cameraContainer.offsetHeight > 0,
                videoVisible: video.offsetHeight > 0
            });
            
            try {
                // Verificar si QrScanner está disponible
                console.log('Verificando QrScanner:', typeof QrScanner);
                if (typeof QrScanner === 'undefined') {
                    throw new Error('QR Scanner library not loaded');
                }
                
                // PASO 1: Verificar dispositivos manualmente
                console.log('PASO 1: Verificando dispositivos...');
                await verificarDispositivosMedia();
                
                // PASO 2: Probar acceso directo a cámara
                console.log('PASO 2: Probando acceso a cámara...');
                await probarAccesoCamara();
                
                // PASO 3: Verificar con QrScanner
                console.log('PASO 3: Verificando con QrScanner...');
                const hasCamera = await QrScanner.hasCamera();
                console.log('QrScanner.hasCamera():', hasCamera);
                
                if (!hasCamera) {
                    console.warn('QrScanner no detecta cámaras, pero continuamos...');
                }
                
                // PASO 4: Listar cámaras disponibles
                try {
                    const cameras = await QrScanner.listCameras(true);
                    console.log('Cámaras disponibles:', cameras);
                } catch (e) {
                    console.warn('No se pudieron listar las cámaras:', e);
                }
                
                // PASO 5: Crear instancia del escáner QR
                console.log('PASO 5: Creando instancia QrScanner...');
                qrScanner = new QrScanner(
                    video,
                    result => {
                        // ===== VALIDADOR QR SIGAV v4.0 - SIMPLIFICADO =====
                         console.log('🚀 [SIGAV QR VALIDATOR v4.0] QR detectado:', result.data);
                         
                         const qrData = result.data.trim().toUpperCase();
                         let vehiculoId = null;
                         
                         // Validación simplificada: solo verificar formato CO######
                         if (/^CO[-]?[0-9]{3,6}$/.test(qrData)) {
                             vehiculoId = qrData;
                             console.log('✅ QR VÁLIDO - Número interno:', vehiculoId);
                         } else {
                             console.error('❌ QR INVÁLIDO - Formato esperado: CO-#### o CO####');
                             console.error('❌ Recibido:', qrData);
                             
                             // Vibración de error
                             if (navigator.vibrate) {
                                 navigator.vibrate([200, 100, 200]);
                             }
                         }
                        
                        if (vehiculoId) {
                            qrDetected = vehiculoId;
                            
                            // Vibración de éxito
                            if (navigator.vibrate) {
                                navigator.vibrate([100, 50, 100]);
                            }
                            
                            // Detener cámara inmediatamente
                            detenerCamara();
                            
                            // Procesar automáticamente el QR detectado
                            procesarQRAutomatico(vehiculoId);
                        } else {
                            // Vibración de error
                            if (navigator.vibrate) {
                                navigator.vibrate([100, 100, 100]);
                            }
                        }
                    },
                    {
                        preferredCamera: 'environment', // Cámara trasera
                        highlightScanRegion: true,
                        highlightCodeOutline: true,
                        maxScansPerSecond: 5,
                    }
                );
                
                console.log('PASO 6: Iniciando QrScanner...');
                
                // Iniciar el escáner
                await qrScanner.start();
                console.log('✅ QrScanner iniciado exitosamente');
                scanning = true;
                
                // Verificar que el video esté activo
                setTimeout(() => {
                    console.log('Verificación post-inicio:', {
                        videoSrcObject: !!video.srcObject,
                        videoReadyState: video.readyState,
                        videoVideoWidth: video.videoWidth,
                        videoVideoHeight: video.videoHeight,
                        containerVisible: cameraContainer.offsetHeight > 0,
                        videoVisible: video.offsetHeight > 0
                    });
                }, 1000);
                
                // Vibración para feedback táctil
                if (navigator.vibrate) {
                    navigator.vibrate(100);
                }
                
            } catch (error) {
                console.error('❌ Error al iniciar escáner QR:', error);
                
                // Mensaje de error más específico
                let mensaje = 'No se pudo acceder a la cámara.';
                
                if (error.name === 'NotAllowedError') {
                    mensaje = 'Permisos de cámara denegados. Por favor:\n\n1. Haz clic en el ícono de cámara en la barra de direcciones\n2. Selecciona "Permitir"\n3. Recarga la página';
                } else if (error.name === 'NotFoundError' || error.message.includes('No cameras found') || error.message.includes('No se encontraron dispositivos de video')) {
                    mensaje = 'No se encontró ninguna cámara.\n\nVerifica:\n• Tu dispositivo tiene una cámara\n• La cámara no está siendo usada por otra aplicación\n• Los drivers de la cámara están instalados';
                } else if (error.name === 'NotSupportedError' || error.message.includes('MediaDevices API no soportada') || error.message.includes('getUserMedia no está soportado')) {
                    mensaje = 'Tu navegador no soporta el acceso a la cámara.\n\n🔄 SOLUCIONES:\n\n📱 MÓVIL:\n• Usa Chrome 53+ o Firefox 36+\n• Actualiza tu navegador\n• Prueba con otro navegador\n\n💻 ESCRITORIO:\n• Chrome 53+, Firefox 36+, Safari 11+\n• Edge 79+ (Chromium)\n• Actualiza tu navegador\n\n🌐 ACCESO:\n• Usa HTTPS o localhost\n• Evita navegadores muy antiguos';
                } else if (error.message.includes('Navegador no compatible')) {
                    mensaje = `${error.message}\n\n🔄 ACTUALIZA TU NAVEGADOR:\n\n• Chrome: Versión 53 o superior\n• Firefox: Versión 36 o superior\n• Safari: Versión 11 o superior\n• Edge: Versión 79 o superior\n\n📱 En móvil, usa la versión más reciente disponible.`;
                } else if (error.name === 'NotReadableError') {
                    mensaje = 'La cámara está siendo usada por otra aplicación.\n\nCierra otras aplicaciones que puedan estar usando la cámara.';
                } else if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1' && !location.hostname.includes('192.168')) {
                    mensaje = 'Se requiere HTTPS para usar la cámara.\n\nAccede desde:\n• https://\n• localhost\n• IP local (192.168.x.x)';
                } else if (error.message.includes('QR Scanner library not loaded')) {
                    mensaje = 'Error al cargar la librería del escáner QR.\n\nVerifica tu conexión a internet y recarga la página.';
                }
                
                alert(mensaje);
                cameraContainer.style.display = 'none';
            }
        }

        function procesarQRAutomatico(vehiculoId) {
            console.log('🔄 [DEBUG] Procesando QR automáticamente:', vehiculoId);
            console.log('🔄 [DEBUG] Tipo de vehiculoId:', typeof vehiculoId);
            console.log('🔄 [DEBUG] Longitud:', vehiculoId.length);
            
            // Mostrar mensaje de procesamiento (usar contenedor existente)
            const container = document.querySelector('.mobile-container') 
                                || document.querySelector('.container') 
                                || document.body;
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'loading-qr';
            loadingDiv.className = 'alert alert-info text-center';
            loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando vehículo ' + vehiculoId + '...';
            
            if (container.firstChild) {
                container.insertBefore(loadingDiv, container.firstChild);
            } else {
                container.appendChild(loadingDiv);
            }
            
            // Preparar datos para enviar
            const postData = 'numero_interno=' + encodeURIComponent(vehiculoId);
            console.log('🔄 [DEBUG] Datos a enviar:', postData);
            
            // Buscar el ID del vehículo mediante AJAX
            fetch('buscar_vehiculo_id.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: postData
            })
            .then(response => response.json())
            .then(data => {
                // Remover mensaje de loading
                const loadingElement = document.getElementById('loading-qr');
                if (loadingElement) {
                    loadingElement.remove();
                }
                
                if (data.success && data.vehiculo_id) {
                    console.log('Vehículo encontrado, redirigiendo...', data);
                    
                    // Verificar si necesita fecha de extintor
                    if (data.necesita_extintor) {
                        const url = '/inspector/extintor_fecha.php?vehiculo=' + data.vehiculo_id;
                        console.log('Redirigiendo a extintor:', url);
                        window.location.href = url;
                    } else {
                        // Construir URL robusta para despliegues en subcarpetas
                        const basePath = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>';
                        const url = `${basePath}/alistamiento.php?vehiculo=${data.vehiculo_id}`;
                        console.log('Redirigiendo a alistamiento:', url);
                        window.location.href = url;
                    }
                } else {
                    console.error('Error al buscar vehículo:', data);
                    alert(data.message || 'Vehículo no encontrado');
                    
                    // Permitir escanear nuevamente
                    document.getElementById('btn-activar-camara').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error en la búsqueda:', error);
                
                // Remover mensaje de loading
                const loadingElement = document.getElementById('loading-qr');
                if (loadingElement) {
                    loadingElement.remove();
                }
                
                alert('Error al procesar el QR. Inténtalo nuevamente.');
                
                // Permitir escanear nuevamente
                document.getElementById('btn-activar-camara').style.display = 'block';
            });
        }

        function mostrarResultadoQR(vehiculo) {
            const resultDiv = document.getElementById('qr-result');
            const textDiv = document.getElementById('qr-text');
            
            textDiv.textContent = `Vehículo: ${vehiculo}`;
            resultDiv.style.display = 'block';
            
            // Vibración de éxito
            if (navigator.vibrate) {
                navigator.vibrate([100, 50, 100]);
            }
            
            // Detener cámara
            detenerCamara();
        }

        function detenerEscanerQR() {
            scanning = false;
            qrDetected = null;
            
            if (qrScanner) {
                qrScanner.stop();
                qrScanner.destroy();
                qrScanner = null;
            }
            document.getElementById('camera-container').style.display = 'none';
            document.getElementById('camera-preview').style.display = 'none';
        }

        function detenerCamara() {
            detenerEscanerQR();
        }

        function procesarQR() {
            if (qrDetected) {
                // Mostrar loading
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
                btn.disabled = true;
                
                // Buscar el ID del vehículo mediante AJAX
                fetch('buscar_vehiculo_id.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'numero_interno=' + encodeURIComponent(qrDetected)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.vehiculo_id) {
                        // Verificar si necesita fecha de extintor
                        if (data.necesita_extintor) {
                            const url = '/inspector/extintor_fecha.php?vehiculo=' + data.vehiculo_id;
                            console.log('Redirigiendo a extintor:', url);
                            window.location.href = url;
                        } else {
                            const url = '/inspector/alistamiento.php?vehiculo=' + data.vehiculo_id;
                            console.log('Redirigiendo a alistamiento:', url);
                            window.location.href = url;
                        }
                    } else {
                        alert(data.message || 'Vehículo no encontrado');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar el vehículo');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }
        }

        function toggleManualInput() {
            const section = document.getElementById('manual-input-section');
            const button = event.target;
            
            if (section.style.display === 'none' || section.style.display === '') {
                section.style.display = 'block';
                button.innerHTML = '<i class="fas fa-times me-2"></i>Cancelar';
                
                // Focus en el input con un pequeño delay para animación
                setTimeout(() => {
                    const input = document.getElementById('numero_interno');
                    input.focus();
                    
                    // Auto-formateo para COTRAUTOL (inserta guión automáticamente)
                    input.addEventListener('input', function(e) {
                        let value = e.target.value.toUpperCase();
                        
                        // Si no empieza con CO, agregarlo automáticamente
                        if (value.length > 0 && !value.startsWith('CO')) {
                            // Si empieza con números, agregar CO al inicio
                            if (/^[0-9]/.test(value)) {
                                value = 'CO' + value;
                            }
                            // Si empieza con C pero no CO, completar
                            else if (value.startsWith('C') && !value.startsWith('CO')) {
                                value = 'CO' + value.substring(1);
                            }
                        }
                        
                        // Limitar a CO- + máximo 6 números
                        if (value.startsWith('CO')) {
                            const numbers = value.replace(/^CO-?/, '').replace(/[^0-9]/g, '');
                            value = 'CO-' + numbers.substring(0, 6);
                        }
                        
                        e.target.value = value;
                        
                        // Validación visual
                        const isValid = /^CO-?[0-9]{3,6}$/.test(value);
                        if (value.length >= 5) {
                            e.target.style.borderColor = isValid ? 'var(--success-color)' : 'var(--danger-color)';
                            e.target.style.backgroundColor = isValid ? 'rgba(39, 174, 96, 0.1)' : 'rgba(231, 76, 60, 0.1)';
                        } else {
                            e.target.style.borderColor = '';
                            e.target.style.backgroundColor = '';
                        }
                    });
                }, 100);
                
                // Vibración
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
            } else {
                section.style.display = 'none';
                button.innerHTML = '<i class="fas fa-edit me-2"></i>Escribir Número Interno';
            }
        }

        // Funciones PWA y optimizaciones móviles
        document.addEventListener('DOMContentLoaded', function() {
            // Registrar service worker para PWA
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('../sw.js')
                    .then(function(registration) {
                        console.log('Service Worker registrado:', registration);
                    })
                    .catch(function(error) {
                        console.log('Error al registrar Service Worker:', error);
                    });
            }
            
            // Prevenir zoom en inputs en iOS
            const inputs = document.querySelectorAll('input[type="text"]');
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
            
            // Optimización para teclado virtual
            window.addEventListener('resize', function() {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            });
            
            // Feedback táctil para botones
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('touchstart', function() {
                    if (navigator.vibrate) {
                        navigator.vibrate(25);
                    }
                });
            });
        });

        // Prevenir comportamientos no deseados en móviles
        document.addEventListener('touchmove', function(e) {
            if (e.target.closest('.camera-preview')) {
                e.preventDefault();
            }
        }, { passive: false });

        // Manejo de orientación
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                if (scanning) {
                    detenerCamara();
                    setTimeout(iniciarEscaneoQR, 500);
                }
            }, 500);
        });
        
        // ===== CACHE INVALIDATION TIMESTAMP: <?php echo date('Y-m-d H:i:s') . '_' . microtime(true); ?> =====
        // ===== SIGAV QR VALIDATOR v3.0 - FORCE RELOAD =====
        console.log('🚀 SIGAV Inspector v3.0 - Nueva validación QR activa!');
        console.log('⏰ Timestamp:', '<?php echo date('Y-m-d H:i:s'); ?>');
        console.log('🔄 Cache invalidado - Nueva versión cargada');
    </script>
    
    <div class="page-footer text-center py-3">
        <small class="text-white-50">
            © 2025 <a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-decoration-none">BLACKCROWSOFT.COM</a> - Todos los derechos reservados
        </small>
    </div>
</body>
</html>