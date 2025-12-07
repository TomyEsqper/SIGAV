<?php
// Lista los ítems en estado MALO del último alistamiento de un vehículo
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$vehiculoId = isset($argv[1]) ? intval($argv[1]) : 0;
if ($vehiculoId <= 0) {
    echo "Uso: php scripts/list_malos_por_vehiculo.php <vehiculo_id>\n";
    exit(1);
}

try {
    $db = getDB();
    $alist = $db->fetch('SELECT id FROM alistamientos WHERE vehiculo_id = ? ORDER BY id DESC LIMIT 1', [$vehiculoId]);
    if (!$alist) {
        echo "Sin alistamientos para el vehículo $vehiculoId\n";
        exit(0);
    }
    $alistId = (int)$alist['id'];
    echo "Vehículo: $vehiculoId\nÚltimo alistamiento: #$alistId\n\n";

    $rows = [];
    try {
        $rows = $db->fetchAll(
            'SELECT da.id AS detalle_id, da.estado, da.categoria_id, cc.nombre AS categoria_nombre, ic.nombre AS item_nombre
             FROM detalle_alistamiento da
             LEFT JOIN categorias_checklist cc ON cc.id = da.categoria_id
             LEFT JOIN items_checklist ic ON ic.id = da.item_id
             WHERE da.alistamiento_id = ? AND da.estado = "malo"
             ORDER BY cc.nombre ASC, da.id ASC',
            [$alistId]
        );
    } catch (Exception $e) {
        // Fallback para esquema simplificado sin items_checklist
        $rows = $db->fetchAll(
            'SELECT da.id AS detalle_id, da.estado, da.categoria_id, cc.nombre AS categoria_nombre
             FROM detalle_alistamiento da
             LEFT JOIN categorias_checklist cc ON cc.id = da.categoria_id
             WHERE da.alistamiento_id = ? AND da.estado = "malo"
             ORDER BY cc.nombre ASC, da.id ASC',
            [$alistId]
        );
        foreach ($rows as &$r) { $r['item_nombre'] = null; }
        unset($r);
    }

    if (empty($rows)) {
        echo "No hay ítems en MALO en el último alistamiento.\n";
        exit(0);
    }

    foreach ($rows as $r) {
        $cat = $r['categoria_nombre'] ?? 'Sin categoría';
        $item = $r['item_nombre'] ?? ('Detalle #' . $r['detalle_id']);
        echo "- Categoria: $cat | Ítem: $item | DetalleID: {$r['detalle_id']}\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

?>