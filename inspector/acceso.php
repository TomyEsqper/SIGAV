<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal = preg_match('/^(127\.0\.0\.1|::1|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $clientIp);

$token = $_GET['token'] ?? '';
$allow = false;
if (defined('ACCESS_PUBLIC_KEY') && constant('ACCESS_PUBLIC_KEY')) {
    $expected = hash_hmac('sha256', date('Y-m-d'), constant('ACCESS_PUBLIC_KEY'));
    $allow = hash_equals($expected, $token);
}
if (!$allow) {
    if ($isLocal && isset($_GET['public']) && $_GET['public'] === '1') { $allow = true; }
}
if (!$allow) { header('Location: /login.php'); exit; }

try {
    $db = getDB();
    $user = $db->fetch("SELECT * FROM usuarios WHERE usuario = ? AND activo = 1", ['inspector1']);
    if (!$user) {
        $user = $db->fetch("SELECT * FROM usuarios WHERE rol = 'inspector' AND activo = 1 LIMIT 1");
    }
    if (!$user) { header('Location: /login.php'); exit; }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['usuario'] = $user['usuario'] ?? ($user['email'] ?? '');
    $_SESSION['nombre'] = $user['nombre'] ?? 'Inspector';
    $_SESSION['rol'] = 'inspector';
    $_SESSION['last_activity'] = time();

    $colUltimoAcceso = $db->fetch("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'ultimo_acceso'");
    if (((int)($colUltimoAcceso['c'] ?? 0)) > 0) { $db->execute("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?", [$user['id']]); }

    header('Location: /inspector/');
    exit;
} catch (Exception $e) {
    header('Location: /login.php');
    exit;
}
?>
