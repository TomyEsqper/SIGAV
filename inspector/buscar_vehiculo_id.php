<?php
/**
 * Búsqueda de ID de Vehículo por Número Interno - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2025 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación
verificarSesion(['inspector', 'inspector_camaras', 'admin']);

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener número interno
$numero_interno = strtoupper(trim($_POST['numero_interno'] ?? ''));

// DEBUG: Log de lo que se recibe
error_log("[DEBUG SIGAV] Datos POST recibidos: " . print_r($_POST, true));
error_log("[DEBUG SIGAV] numero_interno procesado: '$numero_interno'");
error_log("[DEBUG SIGAV] Longitud: " . strlen($numero_interno));

// Validar formato COTRAUTOL (CO con o sin guión)
if (!preg_match('/^CO-?[0-9]{3,6}$/', $numero_interno)) {
    error_log("[DEBUG SIGAV] Validación FALLIDA para: '$numero_interno'");
    echo json_encode([
        'success' => false, 
        'message' => 'Formato inválido para número interno: ' . $numero_interno
    ]);
    exit;
}

try {
    $db = getDB();
    
    // Normalizar a formato con guión (CO-XXXX)
    $numero_interno = preg_replace('/^CO-?([0-9]{3,6})$/', 'CO-$1', $numero_interno);

    // Buscar vehículo
    $stmt = $db->prepare("SELECT * FROM vehiculos WHERE numero_interno = ? AND estado != 'eliminado'");
    $stmt->execute([$numero_interno]);
    $vehiculo = $stmt->fetch();
    
    if (!$vehiculo) {
        echo json_encode([
            'success' => false, 
            'message' => 'Vehículo no encontrado'
        ]);
        exit;
    }
    
    // Verificar estado del vehículo
    if ($vehiculo['estado'] !== 'activo') {
        echo json_encode([
            'success' => false, 
            'message' => 'El vehículo está en estado: ' . ucfirst($vehiculo['estado'])
        ]);
        exit;
    }
    
    // Verificar si el extintor necesita fecha de vencimiento
    $stmt_extintor = $db->prepare("SELECT * FROM documentos WHERE vehiculo_id = ? AND tipo_documento = 'extintor'");
    $stmt_extintor->execute([$vehiculo['id']]);
    $extintor = $stmt_extintor->fetch();
    
    $necesita_extintor = !$extintor || !$extintor['fecha_vencimiento'];
    
    echo json_encode([
        'success' => true,
        'vehiculo_id' => $vehiculo['id'],
        'numero_interno' => $vehiculo['numero_interno'],
        'placa' => $vehiculo['placa'],
        'necesita_extintor' => $necesita_extintor
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al buscar vehículo: ' . $e->getMessage()
    ]);
}
?>