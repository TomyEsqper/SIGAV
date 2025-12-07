<?php
/**
 * Búsqueda de Conductores - SIGAV
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

$busqueda = trim($_POST['busqueda'] ?? '');
$sugerencias = isset($_POST['sugerencias']) && ($_POST['sugerencias'] === '1' || $_POST['sugerencias'] === 1 || $_POST['sugerencias'] === true);
$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
$limit = max(1, min(10, $limit));

if ($busqueda === '') {
    echo json_encode(['success' => false, 'message' => 'Parámetro de búsqueda requerido']);
    exit;
}

try {
    $db = getDB();

    if ($sugerencias) {
        $sql = "SELECT id, nombre, cedula, telefono, activo
                FROM conductores
                WHERE (nombre LIKE ? OR cedula LIKE ?)
                ORDER BY activo DESC, nombre ASC
                LIMIT $limit";
        $stmt = $db->prepare($sql);
        $param = '%' . $busqueda . '%';
        $stmt->execute([$param, $param]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    }

    // Un solo resultado (compatibilidad)
    $stmt = $db->prepare(
        "SELECT id, nombre, cedula, telefono, activo 
         FROM conductores 
         WHERE (nombre LIKE ? OR cedula LIKE ?) 
         ORDER BY activo DESC, nombre ASC 
         LIMIT 1"
    );
    $param = '%' . $busqueda . '%';
    $stmt->execute([$param, $param]);
    $conductor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($conductor) {
        echo json_encode(['success' => true, 'data' => $conductor]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Conductor no encontrado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>