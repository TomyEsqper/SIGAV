<?php
/**
 * Búsqueda de Vehículos - SIGAV
 * Desarrollado por BLACK CROWSOFT
 * © 2025 BLACK CROWSOFT - Todos los derechos reservados
 */

require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación
verificarSesion(['admin', 'revision_memorias']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$busqueda = $_POST['busqueda'] ?? '';
$sugerencias = isset($_POST['sugerencias']) && ($_POST['sugerencias'] === '1' || $_POST['sugerencias'] === 1 || $_POST['sugerencias'] === true);
$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
$limit = max(1, min(10, $limit));

if (empty($busqueda)) {
    echo json_encode(['success' => false, 'message' => 'Parámetro de búsqueda requerido']);
    exit;
}

try {
    $db = getDB();
    
    if ($sugerencias) {
        // Lista de sugerencias (top N)
        $sql = "SELECT id, placa, numero_interno, propietario 
                FROM vehiculos 
                WHERE estado = 'activo' 
                  AND (placa LIKE ? OR numero_interno LIKE ?) 
                ORDER BY numero_interno ASC 
                LIMIT $limit";
        $stmt = $db->prepare($sql);
        $param = '%' . $busqueda . '%';
        $stmt->execute([$param, $param]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    }

    // Buscar vehículo por placa o número interno (un solo resultado)
    $stmt = $db->prepare(
        "SELECT id, placa, numero_interno, propietario 
         FROM vehiculos 
         WHERE estado = 'activo' 
           AND (placa LIKE ? OR numero_interno LIKE ?) 
         LIMIT 1"
    );
    $busqueda_param = '%' . $busqueda . '%';
    $stmt->execute([$busqueda_param, $busqueda_param]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vehiculo) {
        echo json_encode([
            'success' => true,
            'data' => $vehiculo
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Vehículo no encontrado'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>