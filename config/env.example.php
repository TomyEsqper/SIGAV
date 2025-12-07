<?php
/**
 * Archivo de ejemplo de variables de entorno para producción.
 * Copie este archivo como env.php y complete sus credenciales reales en el servidor (Hostinger).
 * No suba env.php al repositorio público.
 */

// Credenciales MySQL de producción (consulte hPanel > Bases de datos MySQL)
define('DB_HOST', 'mysql_hostinger_servidor');    // Ej: 'localhost' o 'mysql-123456.hostinger.com'
define('DB_NAME', 'usuario_base');                // Ej: 'u123456789_sigav'
define('DB_USER', 'usuario_mysql');               // Ej: 'u123456789_root'
define('DB_PASS', 'su_contraseña_segura');        // Ej: '********'
define('DB_CHARSET', 'utf8mb4');

// (Opcional) URL de la app en producción
// define('APP_URL', 'https://su-dominio.com');

// Zona horaria (la app ya usa America/Bogota por defecto)
// date_default_timezone_set('America/Bogota');
?>