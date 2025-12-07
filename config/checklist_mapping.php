<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Definición de categorías del wizard y si son vitales
$CHECKLIST_CATEGORIES = [
    'videograbacion' => ['nombre' => 'Videograbación y Satelital', 'es_vital' => false],
    'equipo_carretera' => ['nombre' => 'Equipo de Carretera', 'es_vital' => false],
    'puesta_marcha' => ['nombre' => 'Puesta en Marcha', 'es_vital' => true],
    'revision_mecanica' => ['nombre' => 'Revisión Mecánica', 'es_vital' => true],
    'cabina_carroceria' => ['nombre' => 'Cabina y Carrocería', 'es_vital' => true],
    'revision_instrumentos' => ['nombre' => 'Revisión de Instrumentos', 'es_vital' => true],
    'revision_fugas' => ['nombre' => 'Revisión de Fugas', 'es_vital' => true],
    'revision_electrica' => ['nombre' => 'Revisión Eléctrica', 'es_vital' => true],
    'revision_llantas' => ['nombre' => 'Revisión de Llantas', 'es_vital' => true],
];

// Helper para convertir claves a etiquetas legibles
function formatItemLabel($key) {
    $label = str_replace('_', ' ', (string)$key);
    $label = strtolower($label);
    $label = mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    return $label;
}

// Buscar o crear categoría por nombre y devolver su ID
function findOrCreateCategoryId($nombre, $descripcion = '') {
    $db = getDB();
    $row = $db->fetch('SELECT id FROM categorias_checklist WHERE nombre = ?', [$nombre]);
    if ($row && isset($row['id'])) {
        return (int)$row['id'];
    }
    // calcular orden siguiente
    $max = $db->fetch('SELECT COALESCE(MAX(orden), 0) as max_orden FROM categorias_checklist');
    $orden = (int)($max['max_orden'] ?? 0) + 1;
    return (int)$db->insert('INSERT INTO categorias_checklist (nombre, descripcion, orden) VALUES (?, ?, ?)', [$nombre, $descripcion, $orden]);
}

// Persistir estado por categoría a partir de los datos del wizard
// $photos: mapa opcional de fotos capturadas en el wizard [catKey][itemKey] => ruta temporal
function persistWizardDetails($alistamientoId, $wiz, $photos = []) {
    if (!$alistamientoId || !is_array($wiz)) { return; }
    $db = getDB();

    // En esquema simplificado no usamos tabla items_checklist; se mantiene helper sin uso
    $findOrCreateItemId = function($categoriaId, $label, $esVital) { return null; };

    // Recorrer categorías definidas
    global $CHECKLIST_CATEGORIES;
    $categoriasWizard = array_keys($CHECKLIST_CATEGORIES);
    // Incluir categorías no vitales especiales
    foreach (['videograbacion','equipo_carretera'] as $extra) {
        if (!in_array($extra, $categoriasWizard, true)) { $categoriasWizard[] = $extra; }
    }

    foreach ($categoriasWizard as $catKey) {
        if (!isset($wiz[$catKey]) || !is_array($wiz[$catKey])) { continue; }
        $info = $CHECKLIST_CATEGORIES[$catKey] ?? ['nombre' => formatItemLabel($catKey), 'es_vital' => false];
        $categoriaId = findOrCreateCategoryId($info['nombre']);

        foreach ($wiz[$catKey] as $itemKey => $valor) {
            // Etiqueta legible del ítem
            $label = formatItemLabel($itemKey);
            // Esquema simplificado: no hay item_id por ítem

            // Mapear valor del wizard a estado del detalle
            $estado = 'ok';
            $obs = null;
            if ($valor === 'MALO') { $estado = 'malo'; }
            elseif ($valor === 'N/A') { $estado = 'ok'; $obs = 'No aplica'; }

            // Insertar detalle
            try {
                // Insertar detalle con columnas existentes en el esquema simplificado
                $detalleId = (int)$db->insert(
                    'INSERT INTO detalle_alistamiento (alistamiento_id, categoria_id, estado, fecha_revision) VALUES (?, ?, ?, ?)',
                    [$alistamientoId, $categoriaId, $estado, date('Y-m-d H:i:s')]
                );

                // Si hay foto capturada para este ítem y está en MALO, mover a destino y asociar
                if ($estado === 'malo' && isset($photos[$catKey]) && isset($photos[$catKey][$itemKey])) {
                    $tmpRel = $photos[$catKey][$itemKey];
                    $tmpAbs = dirname(__DIR__) . '/' . $tmpRel;
                    if (is_file($tmpAbs)) {
                        // Preparar carpeta definitiva
                        $destDirRel = 'uploads/evidencias/alistamientos/' . $alistamientoId;
                        $destDirAbs = dirname(__DIR__) . '/' . $destDirRel;
                        if (!is_dir($destDirAbs)) { @mkdir($destDirAbs, 0777, true); }
                        $ext = pathinfo($tmpAbs, PATHINFO_EXTENSION) ?: 'jpg';
                        // Nombrar por categoría en ausencia de item_id
                        $destRel = $destDirRel . '/cat_' . $categoriaId . '_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                        $destAbs = dirname(__DIR__) . '/' . $destRel;
                        // Intentar mover; si falla, copiar y eliminar origen
                        $moved = @rename($tmpAbs, $destAbs);
                        if (!$moved) {
                            $moved = (@copy($tmpAbs, $destAbs) && @unlink($tmpAbs));
                        }
                        if ($moved) {
                            // Guardar ruta en detalle
                            try {
                                $db->execute(
                                    'UPDATE detalle_alistamiento SET foto_url = ? WHERE id = ? LIMIT 1',
                                    [$destRel, $detalleId]
                                );
                            } catch (Exception $e) { /* ignorar errores menores */ }
                        }
                    }
                }
            } catch (Exception $e) {
                // Silenciar duplicados o errores menores para no romper el flujo
            }
        }
    }
}

// Nota: se elimina snapshot en alistamientos y evidencias; solo se guarda categoría/estado

// Guardar evidencia fotográfica subida en la vista de evidencias
// Mueve la imagen al directorio definitivo del alistamiento y asocia la ruta en detalle_alistamiento
function saveEvidenceImage($detalleId, $fileInfo) {
    $db = getDB();
    $detalleId = intval($detalleId);
    if ($detalleId <= 0) { throw new Exception('Detalle inválido'); }

    // Validar archivo
    if (!is_array($fileInfo)) { throw new Exception('Archivo no recibido'); }
    if (($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { throw new Exception('Error al subir archivo'); }
    $tmpName = $fileInfo['tmp_name'] ?? '';
    if (!$tmpName || !is_uploaded_file($tmpName)) { throw new Exception('Archivo temporal no encontrado'); }

    // Consultar datos del detalle para construir destino
    $detalle = $db->fetch('SELECT id, alistamiento_id, categoria_id FROM detalle_alistamiento WHERE id = ?', [$detalleId]);
    if (!$detalle || !isset($detalle['alistamiento_id'])) { throw new Exception('Detalle no encontrado'); }
    $alistamientoId = intval($detalle['alistamiento_id']);
    $categoriaId = intval($detalle['categoria_id'] ?? 0);

    // Determinar extensión por MIME
    $mime = mime_content_type($tmpName) ?: 'image/jpeg';
    $ext = 'jpg';
    if (preg_match('/png$/i', $mime)) { $ext = 'png'; }
    elseif (preg_match('/gif$/i', $mime)) { $ext = 'gif'; }
    elseif (preg_match('/jpeg|jpg$/i', $mime)) { $ext = 'jpg'; }

    // Directorio destino unificado
    $destDirRel = 'uploads/evidencias/alistamientos/' . $alistamientoId;
    $destDirAbs = dirname(__DIR__) . '/' . $destDirRel;
    if (!is_dir($destDirAbs)) { @mkdir($destDirAbs, 0777, true); }

    // Nombre de archivo
    $fileName = 'detalle_' . $detalleId . '_cat' . $categoriaId . '_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.' . $ext;
    $destRel = $destDirRel . '/' . $fileName;
    $destAbs = dirname(__DIR__) . '/' . $destRel;

    // Intentar mover; si falla, copiar desde tmp y eliminar origen
    $moved = @move_uploaded_file($tmpName, $destAbs);
    if (!$moved) {
        $moved = (@copy($tmpName, $destAbs) && @unlink($tmpName));
    }
    if (!$moved) { throw new Exception('No se pudo mover la imagen'); }

    // Persistir ruta en detalle
    $db->execute('UPDATE detalle_alistamiento SET foto_url = ? WHERE id = ? LIMIT 1', [$destRel, $detalleId]);
    return $destRel;
}

?>