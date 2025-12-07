<?php
require_once __DIR__ . '/../config/database.php';

function tablaExiste($db, $tabla) {
    $row = $db->fetch(
        "SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
        [$tabla]
    );
    return ((int)($row['c'] ?? 0)) > 0;
}

function borrarTabla($db, $tabla) {
    if (!tablaExiste($db, $tabla)) { echo "SKIP tabla $tabla (no existe)\n"; return; }
    $db->execute("DELETE FROM `$tabla`", []);
    echo "OK vaciada $tabla\n";
}

function borrarSiExiste($db, $sql, $params = [], $desc = '') {
    try { $af = $db->execute($sql, $params); echo "OK $desc ($af filas)\n"; }
    catch (Exception $e) { echo "FALLA $desc: " . $e->getMessage() . "\n"; }
}

try {
    $db = getDB();
    echo "== Limpieza de BD para despliegue ==\n";
    $db->execute("SET FOREIGN_KEY_CHECKS=0");

    // Módulo Alistamientos
    borrarTabla($db, 'detalle_alistamiento');
    borrarTabla($db, 'alistamientos');

    // Módulo Documentos
    borrarTabla($db, 'documentos');

    // Módulo Cámaras
    borrarTabla($db, 'camaras_evidencias');
    borrarTabla($db, 'camaras_inspeccion_detalle');
    borrarTabla($db, 'camaras_inspecciones');

    // Módulo Evasión
    borrarTabla($db, 'evasion_detalle');
    borrarTabla($db, 'evasion_inspecciones');

    // Usuarios: mantener roles admin, inspector, inspector_camaras; desactivar/borrar el resto
    if (tablaExiste($db, 'usuarios')) {
        borrarSiExiste($db, "DELETE FROM usuarios WHERE rol NOT IN ('admin','inspector','inspector_camaras')", [], 'usuarios no permitidos');
    }

    // Conductores: se mantienen (no borrar)
    echo "SKIP conductores (se mantienen)\n";

    // Vehículos: se mantienen
    echo "SKIP vehiculos (se mantienen)\n";

    $db->execute("SET FOREIGN_KEY_CHECKS=1");
    echo "== Limpieza finalizada ==\n";
} catch (Exception $e) {
    echo "Error en limpieza: " . $e->getMessage() . "\n";
}

?>

