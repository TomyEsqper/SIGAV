<?php
/**
 * Sistema de Autenticación SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2025 BLACK CROWSOFT - Todos los derechos reservados
 */

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar si el usuario está autenticado
 */
function verificarAutenticacion($redirigir = true) {
    if (!isset($_SESSION['user_id'])) {
        if ($redirigir) {
            $redirect_url = '/login.php';
            header('Location: ' . $redirect_url);
            exit();
        }
        return false;
    }
    return true;
}

/**
 * Verificar timeout de sesión (30 minutos)
 */
function verificarTimeout($redirigir = true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_destroy();
        if ($redirigir) {
            setcookie('flash_timeout', '1', time() + 60, '/');
            header('Location: /login.php');
            exit();
        }
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Verificar permisos de rol
 */
function verificarRol($roles_permitidos = ['admin']) {
    if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $roles_permitidos)) {
        header('HTTP/1.0 403 Forbidden');
        die('Acceso denegado. No tiene permisos para acceder a esta sección.');
    }
    return true;
}

/**
 * Obtener información del usuario actual
 */
function obtenerUsuarioActual() {
    if (!verificarAutenticacion(false)) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'usuario' => $_SESSION['usuario'],
        'nombre' => $_SESSION['nombre'],
        'rol' => $_SESSION['rol']
    ];
}

/**
 * Verificación completa de autenticación y timeout
 */
function verificarSesion($roles_permitidos = ['admin']) {
    verificarAutenticacion();
    verificarTimeout();
    verificarRol($roles_permitidos);
}

/**
 * Generar token CSRF
 */
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Limpiar sesión completamente
 */
function limpiarSesion() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Registrar actividad del usuario
 */
function registrarActividad($accion, $detalles = '') {
    if (!verificarAutenticacion(false)) {
        return false;
    }
    
    try {
        require_once __DIR__ . '/database.php';
        $db = getDB();
        
        $db->execute(
            "INSERT INTO log_actividades (usuario_id, accion, detalles, ip_address, user_agent, fecha) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [
                $_SESSION['user_id'],
                $accion,
                $detalles,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]
        );
        
        return true;
    } catch (Exception $e) {
        error_log("Error registrando actividad: " . $e->getMessage());
        return false;
    }
}

/**
 * Función helper para mostrar nombre del usuario
 */
function nombreUsuario() {
    return $_SESSION['nombre'] ?? 'Usuario';
}

/**
 * Función helper para mostrar rol del usuario
 */
function rolUsuario() {
    return $_SESSION['rol'] ?? 'usuario';
}

/**
 * Verificar si el usuario es administrador
 */
function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

/**
 * Verificar si el usuario es inspector
 */
function esInspector() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'inspector';
}

/**
 * Verificar si el usuario es inspector de cámaras
 */
function esInspectorCamaras() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'inspector_camaras';
}

/**
 * Verificar si el usuario es del rol revisión de memorias
 */
function esRevisionMemorias() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'revision_memorias';
}
?>
