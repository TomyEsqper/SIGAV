<?php
/**
 * Generador de Códigos QR para Vehículos - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2025 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación
verificarSesion(['admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!verificarTokenCSRF($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF inválido']);
    exit;
}

$vehiculo_id = $_POST['vehiculo_id'] ?? 0;

if (!$vehiculo_id) {
    echo json_encode(['error' => 'ID de vehículo requerido']);
    exit;
}

try {
    $db = getDB();
    
    // Obtener datos del vehículo
    $stmt = $db->prepare("SELECT * FROM vehiculos WHERE id = ? AND estado != 'eliminado'");
    $stmt->execute([$vehiculo_id]);
    $vehiculo = $stmt->fetch();
    
    if (!$vehiculo) {
        echo json_encode(['error' => 'Vehículo no encontrado']);
        exit;
    }
    
    // NUEVO: QR simplificado - solo número interno del vehículo
    // Esto permite acceso directo al alistamiento sin validaciones complejas
    $numero_interno = strip_tags(trim($vehiculo['numero_interno']));
    
    // Validar que el número interno existe
    if (empty($numero_interno)) {
        echo json_encode(['error' => 'Número interno del vehículo no válido']);
        exit;
    }
    
    // El QR contendrá únicamente el número interno (ej: CO-0001)
    $qr_data = $numero_interno;
    
    // Codificar datos para URL
    $qr_data_encoded = urlencode($qr_data);
    
    // URL del QR
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . $qr_data_encoded;
    
    // Crear directorio si no existe
    $qr_dir = '../assets/qr/';
    if (!file_exists($qr_dir)) {
        mkdir($qr_dir, 0755, true);
    }
    
    // Nombre del archivo QR
    $qr_filename = 'vehiculo_' . $vehiculo['numero_interno'] . '_' . time() . '.png';
    $qr_path = $qr_dir . $qr_filename;
    $qr_url_local = 'assets/qr/' . $qr_filename;
    
    // Descargar y guardar el QR
    $qr_content = file_get_contents($qr_url);
    
    if ($qr_content === false) {
        echo json_encode(['error' => 'Error al generar código QR']);
        exit;
    }
    
    file_put_contents($qr_path, $qr_content);
    
    // Actualizar base de datos
    $stmt = $db->prepare("UPDATE vehiculos SET codigo_qr = ? WHERE id = ?");
    $stmt->execute([$qr_url_local, $vehiculo_id]);
    
    // Registrar actividad
    registrarActividad("Generó código QR para vehículo: " . $vehiculo['placa']);
    
    echo json_encode([
        'success' => true,
        'qr_url' => $qr_url_local,
        'print_url' => 'imprimir_qr.php?vehiculo=' . $vehiculo_id,
        'message' => 'Código QR generado exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
?>