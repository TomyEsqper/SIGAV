<?php
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

// Intento robusto de parseo de fecha desde inputs variados
function parseFechaProgramada($input) {
    $input = trim((string)$input);
    if ($input === '') return null;
    $formatos = [
        'Y-m-d\TH:i', // datetime-local
        'Y-m-d H:i',
        'm/d/Y h:i A', // 10/26/2025 08:20 PM
        'd/m/Y H:i',
        'd-m-Y H:i',
        'Y/m/d H:i'
    ];
    foreach ($formatos as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $input);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d H:i:s');
        }
    }
    $ts = strtotime($input);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

// Asegura que la tabla de citaciones exista para evitar errores 1146
function ensureCitacionesTable($db) {
    try {
        $db->execute("CREATE TABLE IF NOT EXISTS camaras_citaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehiculo_id INT NOT NULL,
            inspeccion_id INT NOT NULL,
            estado_citacion ENUM('pendiente','programada','resuelta','cancelada') DEFAULT 'pendiente',
            motivo VARCHAR(255) DEFAULT NULL,
            lugar VARCHAR(255) DEFAULT NULL,
            fecha_programada DATETIME DEFAULT NULL,
            nota TEXT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_vehiculo(vehiculo_id),
            INDEX idx_inspeccion(inspeccion_id),
            INDEX idx_estado(estado_citacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
        throw new Exception('Error SQL al verificar/crear tabla camaras_citaciones: ' . $e->getMessage());
    }
}

try {
    verificarSesion(['inspector_camaras', 'admin']);
    $input = json_decode(file_get_contents('php://input'), true);
    $inspeccion_id = intval($input['inspeccion_id'] ?? 0);
    $foto_base64 = $input['foto_base64'] ?? '';
    $estado_final = strtolower(trim($input['estado_final'] ?? 'verde')) === 'rojo' ? 'rojo' : 'verde';
    $citacion = $input['citacion'] ?? null;
    if (!$inspeccion_id || !$foto_base64) { throw new Exception('Datos incompletos'); }

    $db = getDB();

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
        $fileRel = $dirRel . '/final_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.' . $ext;
        $fileAbs = dirname(__DIR__, 3) . '/' . $fileRel;
        if (file_put_contents($fileAbs, $bin) === false) { throw new Exception('No se pudo guardar la imagen'); }
        // Actualizar ruta en inspección y estado final (con errores detallados)
        $stmt = $db->prepare("UPDATE camaras_inspecciones SET foto_fin_url = ?, estado_final = ? WHERE id = ?");
        try {
            $stmt->execute([$fileRel, $estado_final, $inspeccion_id]);
        } catch (PDOException $e) {
            throw new Exception('Error SQL al actualizar inspección: ' . $e->getMessage());
        }
    } else {
        throw new Exception('Formato de imagen no soportado'); }

    // Crear citación si corresponde
    if ($estado_final === 'rojo') {
        // Obtener vehiculo_id de la inspección
        $row = $db->fetch("SELECT vehiculo_id FROM camaras_inspecciones WHERE id = ?", [$inspeccion_id]);
        $vehiculo_id = intval($row['vehiculo_id'] ?? 0);
        // Asegurar tabla de citaciones disponible
        ensureCitacionesTable($db);
        if ($vehiculo_id > 0) {
            $motivo = isset($citacion['motivo']) ? trim($citacion['motivo']) : null;
            $lugar = isset($citacion['lugar']) ? trim($citacion['lugar']) : null;
            $fecha_prog = isset($citacion['fecha_programada']) ? trim($citacion['fecha_programada']) : '';
            $nota = isset($citacion['nota']) ? trim($citacion['nota']) : null;
            $fecha_programada = parseFechaProgramada($fecha_prog);
            $estado_citacion = $fecha_programada ? 'programada' : 'pendiente';

            $stmt = $db->prepare(
                "INSERT INTO camaras_citaciones (vehiculo_id, inspeccion_id, estado_citacion, motivo, lugar, fecha_programada, nota)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            try {
                $stmt->execute([$vehiculo_id, $inspeccion_id, $estado_citacion, $motivo, $lugar, $fecha_programada, $nota]);
            } catch (PDOException $e) {
                throw new Exception('Error SQL al crear citación: ' . $e->getMessage());
            }
        }
    }

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}