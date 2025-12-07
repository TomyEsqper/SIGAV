<?php
/**
 * SIGAV - Router personalizado para el servidor PHP integrado
 * Maneja rutas especiales y evita errores 404 de extensiones del navegador
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Manejar rutas de Vite/React Refresh (extensiones del navegador o inyecciones de dev server)
if (strpos($uri, '/@vite') === 0 || strpos($uri, '/@react-refresh') === 0) {
    // Responder con un stub válido para evitar errores de carga
    header('Content-Type: application/javascript');
    header('Cache-Control: no-store');
    http_response_code(200);
    echo "// Dev client disabled in this environment: {$uri}";
    return true;
}

// Manejar archivos estáticos
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/', $uri)) {
    $file = __DIR__ . $uri;
    if (file_exists($file)) {
        // El servidor integrado servirá el archivo
        return false;
    }

    // Fallback: intentar servir desde /inspector cuando la ruta es solicitada desde root
    $altFile = __DIR__ . '/inspector' . $uri; // p.ej. /qr-scanner.umd.min.js -> /inspector/qr-scanner.umd.min.js
    if (file_exists($altFile)) {
        // Determinar Content-Type básico por extensión
        $ext = pathinfo($altFile, PATHINFO_EXTENSION);
        $mimeMap = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
        ];
        if (isset($mimeMap[$ext])) {
            header('Content-Type: ' . $mimeMap[$ext]);
        }
        readfile($altFile);
        return true; // Ya respondimos manualmente
    }

    http_response_code(404);
    echo 'File not found';
    return true;
}

// Para todas las demás rutas, usar el comportamiento por defecto
return false;
?>