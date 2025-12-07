<?php
session_start();
require_once 'config/database.php';

// Si ya está logueado, redirigir según rol
if (isset($_SESSION['user_id'])) {
    $rol = $_SESSION['rol'] ?? 'admin';
    if ($rol === 'inspector') {
        header('Location: inspector/');
    } elseif ($rol === 'inspector_camaras') {
        header('Location: inspector/camaras/');
    } else {
        header('Location: admin/dashboard.php');
    }
    exit();
}

$error = '';
$message = '';

// Mensajes informativos
if (isset($_GET['logout'])) {
    $message = 'Sesión cerrada correctamente';
}
if (isset($_GET['timeout'])) {
    $error = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente';
}
if (isset($_COOKIE['flash_logout'])) {
    $message = 'Sesión cerrada correctamente';
    setcookie('flash_logout', '', time() - 3600, '/');
}
if (isset($_COOKIE['flash_timeout'])) {
    $error = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente';
    setcookie('flash_timeout', '', time() - 3600, '/');
}

if ($_POST) {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, complete todos los campos';
    } else {
        try {
            $db = getDB();
            // Detectar columna de autenticación disponible (usuario o email)
            $colUsuario = $db->fetch("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'usuario'");
            $colEmail = $db->fetch("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'email'");
            $usaUsuario = ((int)($colUsuario['c'] ?? 0)) > 0;
            $usaEmail = ((int)($colEmail['c'] ?? 0)) > 0;

            // Buscar por usuario o email (acepta ambos si existen)
            if ($usaUsuario && $usaEmail) {
                $user = $db->fetch(
                    "SELECT * FROM usuarios WHERE (usuario = ? OR email = ?) AND activo = 1",
                    [$usuario, $usuario]
                );
            } elseif ($usaUsuario) {
                $user = $db->fetch(
                    "SELECT * FROM usuarios WHERE usuario = ? AND activo = 1",
                    [$usuario]
                );
            } elseif ($usaEmail) {
                $user = $db->fetch(
                    "SELECT * FROM usuarios WHERE email = ? AND activo = 1",
                    [$usuario]
                );
            } else {
                throw new Exception('La tabla usuarios no tiene columnas de autenticación válidas');
            }
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['usuario'] = $user['usuario'] ?? ($user['email'] ?? '');
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['last_activity'] = time();
                
                // Actualizar último acceso
                $colUltimoAcceso = $db->fetch("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'ultimo_acceso'");
                if (((int)($colUltimoAcceso['c'] ?? 0)) > 0) {
                    $db->execute(
                        "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?",
                        [$user['id']]
                    );
                }
                
                // Redirigir según rol
                if ($user['rol'] === 'inspector') {
                    $redirect = 'inspector/';
                } elseif ($user['rol'] === 'inspector_camaras') {
                    $redirect = 'inspector/camaras/';
                } else {
                    $redirect = 'admin/dashboard.php';
                }
                header('Location: ' . $redirect);
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (Exception $e) {
            $error = 'Error de conexión. Verifique que la base de datos esté configurada.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAV - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; overflow: hidden; }
        body {
            background: url('imagendefondo.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        :root {
            --primary-900: #0b1e3f;
            --primary-700: #163a7a;
            --primary-600: #1d4ed8;
            --accent-600: #764ba2;
            --text-muted: #6c757d;
        }
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 1100px;
            gap: 24px;
            padding: 24px;
            height: 100vh;
            box-sizing: border-box;
            position: relative;
        }
        .login-wrapper::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(11, 30, 63, 0.55);
            pointer-events: none;
        }
        /* Botón primario acorde a paleta azul */
        .btn-primary {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #163a7a;
            border-color: #163a7a;
        }
        /* Splash al iniciar sesión */
        .splash-overlay {
            position: fixed;
            inset: 0;
            background: #ffffff; /* fondo blanco que rellena toda la pantalla */
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 0;
        }
        .splash-card {
            background: transparent;
            border-radius: 0;
            box-shadow: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            /* Ocupa toda la pantalla y se ajusta al viewport */
            width: 100vw;
            height: 100vh;
            height: 100dvh; /* soporte para móviles con barra dinámica */
            position: relative;
        }
        .splash-img {
            /* Mostrar el logo y la animación completa sin recorte */
            width: 100% !important;
            height: 100% !important;
            max-width: 100vw !important;
            max-height: 100dvh !important;
            object-fit: contain !important;
            border-radius: 0 !important;
            display: block;
        }
        .splash-title {
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 0;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0 2px 6px rgba(0,0,0,0.5);
            background: rgba(0,0,0,0.25);
            padding: 6px 12px;
            border-radius: 8px;
        }
        @media (max-width: 480px), (max-height: 580px) {
            .splash-title { bottom: 16px; font-size: 14px; padding: 5px 10px; }
        }
        .login-container {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border: 1px solid rgba(255,255,255,0.28);
            overflow: hidden;
            width: 100%;
            max-width: 380px;
        }
        .login-header {
            background: transparent;
            color: #0b1e3f;
            padding: 18px 18px;
            text-align: center;
        }
        .login-body {
            padding: 18px 18px;
        }
        .brand-panel {
            flex: 0 0 360px;
            max-width: 360px;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            display: block;
            height: 100%;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.28);
        }
        .brand-panel .bg-media {
            display: none;
        }
        .brand-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            background: transparent;
        }
        .brand-content {
            position: relative;
            z-index: 2;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 20px;
        }
        .brand-content h1 {
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-size: 22px;
        }
        .brand-content p {
            opacity: 0.95;
            margin-bottom: 14px;
            font-size: 16px;
        }
        .brand-highlights {
            display: flex;
            gap: 16px;
            justify-content: center;
        }
        .brand-chip {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            white-space: nowrap;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .form-title {
            font-weight: 700;
            color: #243b53;
            margin-bottom: 1rem;
        }
        .divider {
            height: 1px;
            background: #e9ecef;
            margin: 1rem 0 1.25rem;
        }
        .toggle-pass-btn {
            border-radius: 0 10px 10px 0;
            border: 2px solid #e9ecef;
            border-left: none;
            background: #f8f9fa;
            color: #495057;
        }
        .form-options {
            color: var(--text-muted);
            font-size: 14px;
        }
        .form-options a {
            color: var(--primary-600);
            text-decoration: none;
        }
        .form-options a:hover { text-decoration: underline; }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .footer-credits {
            background: #f8f9fa;
            padding: 16px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6c757d;
        }
        .footer-credits strong {
            color: #495057;
        }
        .logo-icon { margin-bottom: 10px; }
        .logo-img { width: 80px; height: auto; display: inline-block; }
        @media (max-height: 700px) {
            .login-header, .login-body { padding: 18px; }
            .login-container { max-width: 380px; }
            .logo-img { width: 72px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="brand-panel">
            <div class="bg-media"></div>
            <div class="brand-content">
                <div>
                    <img src="logo.png" alt="SIGAV" class="logo-img" style="width:120px; height:auto;">
                    <h1>Bienvenido a SIGAV</h1>
                    <p>Gestión ágil de vehículos, documentos y alistamientos.</p>
                    <div class="brand-highlights">
                        <span class="brand-chip"><i class="fas fa-shield-alt"></i> Documentos</span>
                        <span class="brand-chip"><i class="fas fa-truck"></i> Vehículos</span>
                        <span class="brand-chip"><i class="fas fa-clipboard-check"></i> Alistamientos</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="login-container">
        <div class="login-header">
            <div class="logo-icon">
                <img src="logo.png" alt="SIGAV" class="logo-img">
            </div>
            <h2 class="mb-0">SIGAV</h2>
            <p class="mb-0">Sistema de Gestión de Alistamiento Vehicular</p>
        </div>
        
        <div class="login-body">
            <h5 class="form-title"><i class="fas fa-lock"></i> Acceso al Sistema</h5>
            <div class="divider"></div>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" autocomplete="off">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="usuario" name="usuario" 
                               value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" 
                               placeholder="Ingrese su usuario" required aria-describedby="usuarioHelp">
                    </div>
                    <div id="usuarioHelp" class="form-text">Su usuario corporativo asignado.</div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Ingrese su contraseña" required aria-describedby="passwordHelp">
                        <button type="button" class="btn toggle-pass-btn" id="togglePass" aria-label="Mostrar u ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="passwordHelp" class="form-text">Mantenga sus credenciales seguras.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                        <select class="form-control" id="rol_visual" name="rol_visual" aria-describedby="rolHelp">
                            <option value="admin">Administrador</option>
                            <option value="inspector">Inspector</option>
                            <option value="inspector_camaras">Inspector de Cámaras</option>
                        </select>
                    </div>
                    <div id="rolHelp" class="form-text">Seleccione su rol. Tras autenticar, será dirigido al módulo correspondiente.</div>
                </div>
                <div class="d-flex justify-content-start align-items-center form-options mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="recordarme">
                        <label class="form-check-label" for="recordarme">Recordarme</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
                <div class="mt-3 form-options">
                    <i class="fas fa-info-circle"></i> ¿Inspector? También puede acceder con su cuenta y será redirigido al módulo de inspección.
                </div>
            </form>
            

        </div>
        
        <div class="footer-credits">
            <i class="fas fa-code"></i> Desarrollado por <a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-decoration-none">BLACKCROWSOFT.COM</a><br>
            <small>© 2025 <a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-decoration-none">BLACKCROWSOFT.COM</a> - Todos los derechos reservados</small>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <div id="login-splash" class="splash-overlay">
        <div class="splash-card">
            <img id="splashImg" class="splash-img" src="animacion.gif" alt="SIGAV">
        </div>
    </div>
    <script>
        // Mostrar animación al enviar el formulario de login
        (function(){
            const form = document.querySelector('form');
            const splash = document.getElementById('login-splash');
            const splashImg = document.getElementById('splashImg');
            let submitted = false;
            if (!form || !splash) return;
            // Restablecer UI para que la animación funcione en cada visita/envío
            function resetUI(){
                try {
                    splash.style.display = 'none';
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) { btn.disabled = false; btn.textContent = 'Iniciar Sesión'; }
                    const fields = form.querySelectorAll('input');
                    fields.forEach(f => f.readOnly = false);
                } catch(e) {}
                submitted = false;
            }
            // Ejecuta al cargar y cuando el navegador restaura la página desde caché (bfcache)
            document.addEventListener('DOMContentLoaded', resetUI);
            window.addEventListener('pageshow', resetUI);
            resetUI();
            form.addEventListener('submit', function(e){
                if (submitted) return; // evita doble envío
                e.preventDefault();
                // Previene múltiples envíos y muestra splash
                try {
                    // Reinicia el GIF cada vez para asegurar animación
                    if (splashImg) {
                        const base = 'animacion.gif';
                        splashImg.src = base + '?t=' + Date.now();
                    }
                    splash.style.display = 'flex';
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) { btn.disabled = true; btn.textContent = 'Ingresando...'; }
                    const fields = form.querySelectorAll('input');
                    fields.forEach(f => f.readOnly = true);
                } catch(e) {}
                submitted = true;
                // Espera 4 segundos para ver la animación completa y luego envía
                setTimeout(function(){ form.submit(); }, 4000);
            });
        })();
        (function(){
            const toggle = document.getElementById('togglePass');
            const input = document.getElementById('password');
            if (!toggle || !input) return;
            toggle.addEventListener('click', function(){
                const isPwd = input.type === 'password';
                input.type = isPwd ? 'text' : 'password';
                this.innerHTML = isPwd ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
        })();
    </script>
<style>
  html, body { overflow: hidden !important; }
  .login-wrapper { min-height: 100vh !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 24px !important; position: relative !important; }
  /* Fondo estático como antes; splash solo tras login */
  body { background: url('imagendefondo.jpg') center/cover no-repeat fixed !important; }
  .login-wrapper { background: url('imagendefondo.jpg') center/cover no-repeat fixed !important; }
  .login-wrapper::before { content: ""; position: absolute; inset: 0; background: rgba(11,30,63,0.55); }
  /* Igualar transparencia y hacer el panel izquierdo más pequeño */
  .brand-panel { flex: 0 0 360px !important; max-width: 360px !important; display: block !important; background: rgba(255,255,255,0.08) !important; backdrop-filter: blur(10px) !important; border: 1px solid rgba(255,255,255,0.28) !important; }
  .brand-panel .bg-media { display: none !important; }
  /* Mejora de contraste y tipografía para textos poco visibles */
  body h2,
  body h5,
  body p,
  body label,
  body small,
  body div { color: #111827 !important; }
  body h2 { font-weight: 700 !important; }
  body h5 { font-weight: 600 !important; }
  body label { font-weight: 600 !important; }
  .brand-panel h2,
  .brand-panel h5,
  .brand-panel p,
  .brand-panel small,
  .brand-panel label,
  .brand-panel div { color: #ffffff !important; text-shadow: 0 1px 2px rgba(0,0,0,.25); }
  .form-control { color: #111827 !important; background-color: #ffffff !important; }
  .form-control::placeholder { color: #6b7280 !important; }
  .brand-panel::after { background: transparent !important; }
  .login-container { background: rgba(255,255,255,0.08) !important; backdrop-filter: blur(10px) !important; border: 1px solid rgba(255,255,255,0.28) !important; max-width: 380px !important; }
  .login-header { background: transparent !important; color: #0b1e3f !important; }
  .brand-content { padding: 20px !important; }
  .brand-content h1 { font-size: 22px !important; }
  .brand-content p { font-size: 14px !important; margin-bottom: 14px !important; }
  .brand-chip { font-size: 12px !important; padding: 6px 10px !important; }
  /* Botón con la misma paleta azul-morado del panel */
  .btn-login { background: linear-gradient(135deg, #1d4ed8 0%, #764ba2 100%) !important; box-shadow: 0 10px 20px rgba(29,78,216,0.25) !important; }
  .btn-login:hover { box-shadow: 0 14px 28px rgba(29,78,216,0.35) !important; transform: translateY(-2px) !important; }
  /* Compactar verticalmente para que se vea más corto */
  .login-header, .login-body { padding: 14px !important; }
  .login-body .mb-4 { margin-bottom: 0.75rem !important; }
  .form-control { padding: 10px 12px !important; font-size: 15px !important; }
  .btn-login { padding: 10px !important; font-size: 15px !important; }
  .divider { margin: 0.75rem 0 1rem !important; }
  .footer-credits { padding: 12px !important; font-size: 12px !important; }
  .form-text { font-size: 12px !important; margin-top: 4px !important; }
  .login-container { max-height: none !important; overflow-y: visible !important; }
  .logo-img { width: 72px !important; }
  /* Aún más compacto en pantallas bajas */
  @media (max-height: 800px) {
    .login-header, .login-body { padding: 12px !important; }
    .login-body .mb-4 { margin-bottom: 0.5rem !important; }
    .btn-login { padding: 9px !important; font-size: 14px !important; }
    .logo-img { width: 64px !important; }
  }
  /* Responsivo: ajustes por ancho */
  @media (max-width: 992px) {
    .brand-panel { flex: 0 0 300px !important; max-width: 300px !important; }
  }
  @media (max-width: 768px) {
    html, body { overflow: auto !important; }
    .login-wrapper { flex-direction: column !important; gap: 16px !important; padding: 16px !important; height: auto !important; min-height: auto !important; }
    .login-container { max-width: 100% !important; width: 100% !important; }
    .brand-panel { width: 100% !important; max-width: 100% !important; flex: 0 0 auto !important; min-height: 160px !important; }
    .brand-content { padding: 16px !important; }
    .logo-img { width: 72px !important; }
    .brand-chip { font-size: 11px !important; padding: 4px 8px !important; }
    .login-header, .login-body { padding: 16px !important; }
  }
  @media (max-width: 480px) {
    .brand-panel { display: none !important; }
    .login-header, .login-body { padding: 14px !important; }
    .form-control { font-size: 15px !important; padding: 10px 12px !important; }
    .btn-login { padding: 10px !important; font-size: 15px !important; }
    .footer-credits { padding: 12px !important; font-size: 12px !important; }
  }
  /* Ajuste fino: compactar contenido para que encaje en el nuevo ancho */
  .login-container .form-label { font-size: 17px !important; margin-bottom: 4px !important; }
  .login-container .form-text { font-size: 12px !important; margin-top: 4px !important; }
  .login-container p { font-size: 17px !important; }
  .login-container .input-group-text, .login-container .toggle-pass-btn { padding: 8px 10px !important; font-size: 14px !important; }
  .login-container .form-control { padding: 8px 10px !important; font-size: 14px !important; line-height: 1.25 !important; }
  .login-container .btn-login { padding: 9px !important; font-size: 14px !important; }
  .login-container .mb-4 { margin-bottom: 0.5rem !important; }
  .login-container .mt-4 { margin-top: 8px !important; }
  .login-container .alert { padding: 8px 10px !important; font-size: 13px !important; }
  .login-container .footer-credits { padding: 10px !important; font-size: 11px !important; }
</style>
  </body>
  </html>
