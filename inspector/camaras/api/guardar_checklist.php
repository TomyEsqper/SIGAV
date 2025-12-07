<?php
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

verificarSesion(['inspector_camaras', 'admin']);

try {
    if (!isset($_POST['inspeccion_id'])) { throw new Exception('Falta inspección'); }
    $inspeccion_id = intval($_POST['inspeccion_id']);
    if ($inspeccion_id <= 0) { throw new Exception('Inspección inválida'); }

    $db = getDB();

    // Directorio de evidencias
    $dirRel = 'uploads/evidencias/camaras/' . $inspeccion_id;
    $dirAbs = dirname(__DIR__, 3) . '/' . $dirRel;
    if (!is_dir($dirAbs)) { @mkdir($dirAbs, 0777, true); }

    $estados = $_POST['estado'] ?? [];
    $obs = $_POST['obs'] ?? [];

    foreach ($estados as $item_key => $estado) {
        $estado = strtolower($estado) === 'malo' ? 'malo' : 'ok';
        $observaciones = trim($obs[$item_key] ?? '');

        // Insertar/actualizar detalle
        $detalle_id = (int)$db->insert(
            'INSERT INTO camaras_inspeccion_detalle (inspeccion_id, item_key, estado, observaciones)
             VALUES (?, ?, ?, ?)',
            [$inspeccion_id, $item_key, $estado, $observaciones]
        );

        // Manejo de evidencia si está MALO
        if ($estado === 'malo') {
            if (!isset($_FILES['evidencia']) || !isset($_FILES['evidencia']['name'][$item_key])) {
                throw new Exception('Debe adjuntar evidencia para "' . $item_key . '"');
            }
            $names = $_FILES['evidencia']['name'][$item_key];
            $tmp_names = $_FILES['evidencia']['tmp_name'][$item_key];
            $types = $_FILES['evidencia']['type'][$item_key];
            $errors = $_FILES['evidencia']['error'][$item_key];

            $count = is_array($names) ? count($names) : 0;
            if ($count === 0) { throw new Exception('Debe adjuntar evidencia para "' . $item_key . '"'); }

            for ($i = 0; $i < $count; $i++) {
                if ($errors[$i] !== UPLOAD_ERR_OK) { throw new Exception('Error subiendo evidencia'); }
                $mime = $types[$i] ?? '';
                $orig = $names[$i] ?? 'archivo';
                $ext = pathinfo($orig, PATHINFO_EXTENSION);
                $tipo = (strpos($mime, 'video/') === 0) ? 'video' : 'foto';
                $rel = $dirRel . '/' . $item_key . '_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.' . ($ext ?: ($tipo==='video'?'mp4':'jpg'));
                $abs = dirname(__DIR__, 3) . '/' . $rel;
                if (!move_uploaded_file($tmp_names[$i], $abs)) { throw new Exception('No se pudo guardar evidencia'); }
                $db->insert(
                    'INSERT INTO camaras_evidencias (detalle_id, archivo_url, tipo) VALUES (?, ?, ?)',
                    [$detalle_id, $rel, $tipo]
                );
            }
        }
    }

    // Redirigir a cierre
    header('Location: ../cerrar.php?inspeccion=' . $inspeccion_id);
    exit;
} catch (Exception $e) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="es"><body><p>Error: ' . htmlspecialchars($e->getMessage()) . '</p><a href="../checklist.php?inspeccion=' . htmlspecialchars($_POST['inspeccion_id'] ?? '') . '">Volver</a></body></html>';
}