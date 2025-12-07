<?php
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

try {
    verificarSesion(['inspector_camaras', 'admin']);
    $input = json_decode(file_get_contents('php://input'), true);
    $vehiculo_id = intval($input['vehiculo_id'] ?? 0);
    $foto_base64 = $input['foto_base64'] ?? '';
    if (!$vehiculo_id || !$foto_base64) { throw new Exception('Datos incompletos'); }

    $db = getDB();
    $inspector_id = intval($_SESSION['user_id']);

    // Crear inspección
    $inspeccion_id = (int)$db->insert(
        "INSERT INTO camaras_inspecciones (vehiculo_id, inspector_id, estado_final) VALUES (?, ?, 'verde')",
        [$vehiculo_id, $inspector_id]
    );

    // Preparar carpeta de evidencias
    $dirRel = 'uploads/evidencias/camaras/' . $inspeccion_id;
    $dirAbs = dirname(__DIR__, 3) . '/' . $dirRel;
    if (!is_dir($dirAbs)) { @mkdir($dirAbs, 0777, true); }

    // Guardar imagen base64
    if (preg_match('/^data:image\/(png|jpg|jpeg);base64,/', $foto_base64, $m)) {
        $ext = strtolower($m[1]) === 'png' ? 'png' : 'jpg';
        $data = substr($foto_base64, strpos($foto_base64, ',') + 1);
        $bin = base64_decode($data);
        if ($bin === false) { throw new Exception('Imagen inválida'); }
        $fileRel = $dirRel . '/inicio_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.' . $ext;
        $fileAbs = dirname(__DIR__, 3) . '/' . $fileRel;
        if (file_put_contents($fileAbs, $bin) === false) { throw new Exception('No se pudo guardar la imagen'); }
        // Actualizar ruta en inspección
        $db->execute("UPDATE camaras_inspecciones SET foto_inicio_url = ? WHERE id = ?", [$fileRel, $inspeccion_id]);
    } else {
        throw new Exception('Formato de imagen no soportado');
    }

    echo json_encode(['ok' => true, 'inspeccion_id' => $inspeccion_id]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}