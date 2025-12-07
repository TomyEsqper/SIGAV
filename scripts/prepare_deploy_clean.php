<?php
/**
 * Script de preparación para despliegue: limpia datos de reportes (evasión) y alistamientos,
 * y elimina archivos de evidencias relacionados. Úselo localmente antes de subir a Hostinger.
 *
 * Seguridad:
 * - Requiere confirmación explícita: agregar ?confirm=1 a la URL.
 * - Solo permite ejecución desde localhost (127.0.0.1 o ::1) para evitar usos accidentales en producción.
 *
 * Qué hace:
 * - Borra filas de: evasion_detalle, evasion_inspecciones, detalle_alistamiento, alistamientos
 * - (Opcional si existen) Borra filas de: camaras_evidencias, camaras_inspeccion_detalle, camaras_inspecciones
 * - Limpia archivos en: uploads/evasion, uploads/evidencias/alistamientos, uploads/evidencias/camaras, storage/envios
 */

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('America/Bogota');
header('Content-Type: text/plain; charset=utf-8');

// Validación de origen (localhost) y confirmación
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$isCli = (PHP_SAPI === 'cli');
$confirm = $isCli ? true : (isset($_GET['confirm']) && $_GET['confirm'] === '1');
if (!$confirm) {
    if (!$isCli) { http_response_code(400); }
    echo "Error: falta confirmación. Ejecute con ?confirm=1\n";
    exit;
}
if (!$isCli && $clientIp !== '127.0.0.1' && $clientIp !== '::1') {
    if (!$isCli) { http_response_code(403); }
    echo "Acceso denegado: ejecute este script solo desde localhost. IP: {$clientIp}\n";
    exit;
}

function tableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '".$table."'");
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function purgeTable(PDO $pdo, string $table): array {
    if (!tableExists($pdo, $table)) {
        return ['table' => $table, 'status' => 'skipped', 'rows' => 0, 'message' => 'Tabla no existe'];
    }
    try {
        $rows = $pdo->exec("DELETE FROM `{$table}`");
        return ['table' => $table, 'status' => 'purged', 'rows' => (int)$rows];
    } catch (Throwable $e) {
        return ['table' => $table, 'status' => 'error', 'rows' => 0, 'message' => $e->getMessage()];
    }
}

function cleanDirFiles(string $dir): array {
    $result = ['dir' => $dir, 'status' => 'skipped', 'files' => 0];
    if (!is_dir($dir)) {
        $result['message'] = 'Directorio no existe';
        return $result;
    }
    $count = 0;
    $patterns = ['*.*'];
    foreach ($patterns as $pattern) {
        foreach (glob(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $pattern) as $file) {
            if (is_file($file)) {
                @unlink($file);
                $count++;
            }
        }
    }
    $result['status'] = 'cleaned';
    $result['files'] = $count;
    return $result;
}

try {
    $pdo = getDB()->getConnection();
    // Desactivar FKs
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}

    $report = [];

    // Evasión (detalle primero)
    $report[] = purgeTable($pdo, 'evasion_detalle');
    $report[] = purgeTable($pdo, 'evasion_inspecciones');

    // Alistamientos (detalle primero)
    $report[] = purgeTable($pdo, 'detalle_alistamiento');
    $report[] = purgeTable($pdo, 'alistamientos');

    // Cámaras (si existen)
    $report[] = purgeTable($pdo, 'camaras_evidencias');
    $report[] = purgeTable($pdo, 'camaras_inspeccion_detalle');
    $report[] = purgeTable($pdo, 'camaras_inspecciones');

    // Reactivar FKs
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}

    echo "=== Resultado limpieza BD ===\n";
    foreach ($report as $r) {
        $msg = $r['table'] . ' -> ' . $r['status'] . ' (rows: ' . $r['rows'] . ')';
        if (!empty($r['message'])) { $msg .= ' | ' . $r['message']; }
        echo $msg . "\n";
    }

    // Limpieza de archivos
    $base = realpath(__DIR__ . '/..');
    $paths = [
        $base . '/uploads/evasion',
        $base . '/uploads/evidencias/alistamientos',
        $base . '/uploads/evidencias/camaras',
        $base . '/storage/envios',
    ];

    echo "\n=== Resultado limpieza archivos ===\n";
    foreach ($paths as $p) {
        $res = cleanDirFiles($p);
        $msg = ($res['dir'] ?? $p) . ' -> ' . ($res['status'] ?? 'unknown') . ' (files: ' . ($res['files'] ?? 0) . ')';
        if (!empty($res['message'])) { $msg .= ' | ' . $res['message']; }
        echo $msg . "\n";
    }

    echo "\nListo. BD y archivos de reportes/alistamientos han sido limpiados de forma segura.\n";
    echo "Recuerde configurar config/env.php con las credenciales de Hostinger antes de subir.\n";

} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error en limpieza: ' . $e->getMessage() . "\n";
}