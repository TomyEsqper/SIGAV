<?php
/**
 * Módulo Inspector de Cámaras - Selección de Vehículo con QR
 */
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación (rol cámaras o admin)
verificarSesion(['inspector_camaras', 'admin']);

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_interno = strtoupper(trim($_POST['numero_interno'] ?? ''));
    if (!$numero_interno) {
        $mensaje = 'Ingrese el número interno del vehículo.';
        $tipo_mensaje = 'warning';
    } else {
        // Normalizar formato CO-XXXX
        if (preg_match('/^CO-?([0-9]{3,6})$/', $numero_interno, $m)) {
            $numero_interno = 'CO-' . $m[1];
        }
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, numero_interno, estado FROM vehiculos WHERE numero_interno = ?");
            $stmt->execute([$numero_interno]);
            $vehiculo = $stmt->fetch();
            if ($vehiculo) {
                if (($vehiculo['estado'] ?? 'activo') === 'inactivo') {
                    $mensaje = 'El vehículo está inactivo. Confirme si procede la inspección.';
                    $tipo_mensaje = 'warning';
                }
                header('Location: iniciar.php?vehiculo=' . intval($vehiculo['id']));
                exit;
            } else {
                $mensaje = 'Vehículo no encontrado.';
                $tipo_mensaje = 'danger';
            }
        } catch (Exception $e) {
            $mensaje = 'Error consultando vehículo.';
            $tipo_mensaje = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
    <meta name="theme-color" content="#2c3e50" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="mobile-web-app-capable" content="yes" />
    <link rel="manifest" href="../../manifest.json" />
    <title>Inspector de Cámaras - Selección de Vehículo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link href="../../assets/css/admin.css" rel="stylesheet" />
    <style>
        /* Ajustes visuales alineados con estilos del sistema (admin.css) */
        body { background: var(--surface); color: var(--text-strong); }
        .container { max-width: 820px; }
        .page-banner {
            background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-600) 100%);
            color: #fff; border-radius: 12px; padding: 16px 18px; margin-bottom: 18px;
            box-shadow: var(--shadow);
        }
        .form-label { color: var(--text-strong); font-weight: 700; }
        .form-label i { color: var(--brand-600); }
        input.form-control { text-transform: uppercase; letter-spacing: .4px; font-weight: 600; }
        .btn-success { background-color: var(--ok); border-color: var(--ok); }
        .btn-success:hover { background-color: #15803d; border-color: #15803d; transform: translateY(-1px); }
        #qr-result.alert { background-color: var(--brand-50); border-color: var(--brand-200); color: var(--text-strong); }
        #camera-container { display: none; }
        video { width: 100%; max-width: 560px; border: 1px solid var(--border); border-radius: 12px; background-color: #000; }
         #manual-emergencia summary { cursor: pointer; color: var(--text-strong); }
         #manual-emergencia summary i { color: var(--warning-600, #f59e0b); }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <div class="page-banner d-flex align-items-center justify-content-between shadow-soft">
            <h1 class="h4 mb-0"><i class="fas fa-video me-2"></i>Módulo de Cámaras - Selección de Vehículo</h1>
            <span class="small opacity-75"><i class="fas fa-qrcode me-1"></i>Escaneo QR recomendado</span>
        </div>
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensaje) ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <!-- Escaneo por QR (Recomendado) -->
        <div class="card stat-card p-3 mb-3">
            <h2 class="section-title h5"><i class="fas fa-qrcode me-2"></i>Escanear QR del Vehículo</h2>
            <p class="text-muted-sm">Recomendado: escanee el QR para continuar. El ingreso manual es solo de emergencia.</p>
            <div class="d-flex flex-column gap-2 mb-3">
                <button id="btn-activar-camara" class="btn btn-primary btn-lg w-100" type="button"><i class="fas fa-qrcode me-2"></i>Escanear QR (Recomendado)</button>
                <button id="btn-detener-camara" class="btn btn-outline-secondary" type="button" style="display:none;"><i class="fas fa-stop me-2"></i>Detener cámara</button>
            </div>
            <div id="camera-container" class="mb-3">
                <video id="camera-preview"></video>
            </div>
            <div id="qr-result" class="alert alert-info" style="display:none;">
                <div id="qr-text" class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>QR detectado</div>
                <button id="btn-procesar-qr" class="btn btn-success" type="button"><i class="fas fa-arrow-right me-2"></i>Continuar con este vehículo</button>
            </div>
        </div>

        <!-- Ingreso manual (emergencia) -->
        <details class="card stat-card p-3 mb-4" id="manual-emergencia">
            <summary class="h6 mb-3 d-flex align-items-center">
                <i class="fas fa-triangle-exclamation text-warning me-2"></i>
                Ingreso manual (solo en caso de emergencia)
            </summary>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-hashtag me-2"></i>Número Interno (Formato CO-XXXX)</label>
                    <input class="form-control" type="text" name="numero_interno" placeholder="CO-0033" value="CO-" required autocomplete="off" pattern="CO-[0-9]{3,6}" title="Escriba solo el número, el prefijo CO- ya está fijo" maxlength="9" />
                </div>
                <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-arrow-right me-2"></i>Continuar</button>
            </form>
        </details>

        <a class="btn btn-secondary" href="../../login.php"><i class="fas fa-sign-out-alt me-2"></i>Volver al Login</a>
    </div>

    <!-- Librería de escaneo QR -->
    <script src="../qr-scanner.umd.min.js"></script>
    <script>
        // Registrar Service Worker para PWA en móviles/tablets
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../../sw.js')
                .then(reg => console.log('Service Worker registrado (Cámaras):', reg.scope))
                .catch(err => console.warn('SW registro falló:', err));
        }

        // Configuración del worker
        QrScanner.WORKER_PATH = '../qr-scanner-worker.min.js';

        let qrScanner = null;
        let scanning = false;
        let qrDetected = null;

        const btnActivar = document.getElementById('btn-activar-camara');
         const btnDetener = document.getElementById('btn-detener-camara');
         const container = document.getElementById('camera-container');
         const video = document.getElementById('camera-preview');
         const resultDiv = document.getElementById('qr-result');
         const textDiv = document.getElementById('qr-text');
         const btnProcesar = document.getElementById('btn-procesar-qr');

         // Priorizar QR si ?action=qr en la URL
         const params = new URLSearchParams(location.search);
         if (params.get('action') === 'qr' && btnActivar) {
             btnActivar.focus();
             document.querySelector('.section-title')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
         }

        // Prefijo CO- asegurado en ingreso manual
        const inputManual = document.querySelector('input[name="numero_interno"]');
        if (inputManual) {
            const ensureValue = () => {
                let val = inputManual.value.toUpperCase();
                val = val.replace(/^CO-?/, '');
                val = val.replace(/[^0-9]/g, '');
                inputManual.value = 'CO-' + val;
            };
            if (!inputManual.value) inputManual.value = 'CO-';
            inputManual.addEventListener('focus', () => {
                if (!inputManual.value.startsWith('CO-')) inputManual.value = 'CO-';
                setTimeout(() => {
                    const len = inputManual.value.length;
                    inputManual.setSelectionRange(len, len);
                }, 0);
            });
            inputManual.addEventListener('input', ensureValue);
            inputManual.addEventListener('keydown', (e) => {
                const pos = inputManual.selectionStart || 0;
                if (pos <= 3 && (e.key === 'Backspace' || e.key === 'Delete')) {
                    e.preventDefault();
                }
            });
        }

        btnActivar.addEventListener('click', async () => {
            try {
                if (!qrScanner) {
                    qrScanner = new QrScanner(
                        video,
                        result => {
                            const raw = (result && result.data ? result.data : String(result)).trim().toUpperCase();
                            // Aceptar CO-#### o CO####
                            const m = raw.match(/^CO-?([0-9]{3,6})$/);
                            if (!m) {
                                console.warn('QR inválido:', raw);
                                return;
                            }
                            const numero = 'CO-' + m[1];
                            qrDetected = numero;
                            textDiv.textContent = 'Vehículo: ' + numero;
                            resultDiv.style.display = 'block';
                            detener();
                            // Procesar automáticamente
                            procesarQR(numero);
                        },
                        { preferredCamera: 'environment', maxScansPerSecond: 5 }
                    );
                }
                await qrScanner.start();
                container.style.display = 'block';
                btnActivar.style.display = 'none';
                btnDetener.style.display = 'inline-block';
                scanning = true;
            } catch (e) {
                alert('No se pudo acceder a la cámara. Si estás en móvil, usa HTTPS, localhost o 192.168.x.x y permite el permiso de cámara.');
                console.error(e);
            }
        });

        btnDetener.addEventListener('click', () => detener());

        function detener() {
            scanning = false;
            if (qrScanner) { qrScanner.stop(); }
            container.style.display = 'none';
            btnActivar.style.display = 'inline-block';
            btnDetener.style.display = 'none';
        }

        btnProcesar.addEventListener('click', () => {
            if (qrDetected) { procesarQR(qrDetected); }
        });

        function procesarQR(numeroInterno) {
            // Mostrar loading
            const loading = document.createElement('div');
            loading.className = 'alert alert-info';
            loading.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando vehículo ' + numeroInterno + '...';
            document.querySelector('.container').prepend(loading);

            fetch('../buscar_vehiculo_id.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'numero_interno=' + encodeURIComponent(numeroInterno)
            })
            .then(r => r.json())
            .then(data => {
                loading.remove();
                if (data.success && data.vehiculo_id) {
                    // Redirigir a inicio de inspección de cámaras
                    window.location.href = 'iniciar.php?vehiculo=' + data.vehiculo_id;
                } else {
                    alert(data.message || 'Vehículo no encontrado');
                    resultDiv.style.display = 'none';
                    qrDetected = null;
                }
            })
            .catch(err => {
                loading.remove();
                console.error('Error procesando QR:', err);
                alert('Error al procesar el QR. Intenta nuevamente.');
                resultDiv.style.display = 'none';
                qrDetected = null;
            });
        }
    </script>
</body>
</html>