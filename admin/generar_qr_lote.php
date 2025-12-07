<?php
// Generación masiva de códigos QR para vehículos sin QR
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

verificarSesion(['admin']);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF inválido']);
    exit;
}

$soloActivos = isset($_POST['solo_activos']) ? (int)$_POST['solo_activos'] : 0;
$tam = isset($_POST['tam']) ? (int)$_POST['tam'] : 200; // tamaño del QR remoto
// NUEVO: permitir regenerar todos los QR (no solo los faltantes)
$regenerarTodos = isset($_POST['regenerar_todos']) ? (int)$_POST['regenerar_todos'] : 0;

// Aumentar límites por procesamiento masivo
@set_time_limit(600);
@ignore_user_abort(true);

function descargarQR(string $numero, int $size): string|false {
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($numero);
    // Preferir cURL para robustez
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $data = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($data !== false && $data !== '') { return $data; }
        // Fallback si cURL falla
    }
    return @file_get_contents($url);
}

try {
    $db = getDB();

    $condEstado = $soloActivos ? "estado = 'activo'" : "estado != 'eliminado'";
    if ($regenerarTodos) {
        $query = "SELECT id, numero_interno FROM vehiculos WHERE $condEstado ORDER BY numero_interno ASC";
    } else {
        $query = "SELECT id, numero_interno FROM vehiculos WHERE $condEstado AND (codigo_qr IS NULL OR codigo_qr = '') ORDER BY numero_interno ASC";
    }
    $faltantes = $db->fetchAll($query, []);

    $total = count($faltantes);
    if ($total === 0) {
        echo json_encode(['success' => true, 'total' => 0, 'generados' => 0, 'message' => 'No hay vehículos pendientes por generar QR']);
        exit;
    }

    // Preparar directorio
    $qr_dir = __DIR__ . '/../assets/qr/';
    if (!is_dir($qr_dir)) {
        @mkdir($qr_dir, 0755, true);
    }

    $generados = 0;
    $errores = [];

    foreach ($faltantes as $idx => $v) {
        $numero = trim((string)$v['numero_interno']);
        if ($numero === '') { continue; }

        $size = max(120, $tam);
        $contenido = descargarQR($numero, $size);
        if ($contenido === false) {
            $errores[] = $numero;
            continue;
        }

        // Nombre único por cada vehículo
        $nombre = 'vehiculo_' . preg_replace('/[^A-Za-z0-9\-_.]/', '', $numero) . '_' . (time()) . '_' . sprintf('%03d', $idx + 1) . '_' . substr(uniqid('', true), -6) . '.png';
        $ruta_abs = $qr_dir . $nombre;
        $ruta_rel = 'assets/qr/' . $nombre;

        if (@file_put_contents($ruta_abs, $contenido) === false) {
            $errores[] = $numero;
            continue;
        }

        // Actualizar base de datos
        $stmt = $db->prepare('UPDATE vehiculos SET codigo_qr = ? WHERE id = ?');
        $stmt->execute([$ruta_rel, (int)$v['id']]);
        $generados++;
    }

    // Registrar actividad simple
    if (function_exists('registrarActividad')) {
        registrarActividad('Generación masiva de QR: ' . $generados . ' nuevos');
    }

    echo json_encode([
        'success' => true,
        'total' => $total,
        'generados' => $generados,
        'regenerados' => $regenerarTodos ? $generados : 0,
        'errores' => $errores,
        'message' => $regenerarTodos ? 'QR regenerados correctamente' : 'QR generados correctamente'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
?>