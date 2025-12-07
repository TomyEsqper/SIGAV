<?php
// Inspección de la tabla detalle_alistamiento: columnas y muestra de filas
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = getDB();
    echo "=== Columnas de detalle_alistamiento ===\n";
    $cols = $db->fetchAll("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'detalle_alistamiento' ORDER BY ORDINAL_POSITION");
    foreach ($cols as $c) { echo ($c['COLUMN_NAME'] ?? '') . "\n"; }

    echo "\n=== Muestra de filas ===\n";
    $rows = $db->fetchAll("SELECT * FROM detalle_alistamiento ORDER BY id DESC LIMIT 3");
    if (empty($rows)) { echo "(sin filas)\n"; }
    foreach ($rows as $r) { echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n"; }
    echo "\nListo.\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

?>