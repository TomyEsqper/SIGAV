<?php
/**
 * Script para ajustar la tabla 'alistamientos' a los campos del checklist
 * Agrega columnas por cada ítem del wizard y campos de pasos 1-3.
 */

$host = 'localhost';
$dbname = 'sigavv';
$username = 'root';
$password = '';

function addEnumColumn(PDO $pdo, $table, $column, array $values, $nullable = true) {
    // Verificar existencia
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    if ($stmt->fetchColumn()) {
        echo "- Columna '$column' ya existe\n";
        return;
    }
    $enum = "ENUM('" . implode("','", $values) . "')";
    $null = $nullable ? 'NULL' : 'NOT NULL';
    $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $enum $null AFTER observaciones_generales";
    $pdo->exec($sql);
    echo "✓ Columna '$column' agregada\n";
}

function addColumn(PDO $pdo, $table, $column, $type, $nullable = true) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    if ($stmt->fetchColumn()) {
        echo "- Columna '$column' ya existe\n";
        return;
    }
    $null = $nullable ? 'NULL' : 'NOT NULL';
    $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $type $null AFTER observaciones_generales";
    $pdo->exec($sql);
    echo "✓ Columna '$column' agregada\n";
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Actualizando estructura de 'alistamientos'...\n\n";

    // Pasos 1-3
    addEnumColumn($pdo, 'alistamientos', 'tipo_designacion', ['FIJO','SUPERNUMERARIO']);
    addColumn($pdo, 'alistamientos', 'conductor_id', 'INT');
    addEnumColumn($pdo, 'alistamientos', 'mantenimiento_tipo', ['ACEITE_MOTOR','LIQUIDO_FRENOS','LLANTAS','SINCRONIZACION','ALINEACION_BALANCEO','TENSION_FRENOS','NINGUNA','OTRO']);
    addColumn($pdo, 'alistamientos', 'mantenimiento_fecha', 'DATE');
    addColumn($pdo, 'alistamientos', 'mantenimiento_otro', 'VARCHAR(255)');

    // Paso 4 - Videograbación (BUENO/MALO/N/A)
    foreach (['vg_gps','vg_camara1','vg_camara2','vg_caja_mdvr','vg_mdvr','vg_memoria_mdvr'] as $c) {
        addEnumColumn($pdo, 'alistamientos', $c, ['BUENO','MALO','N/A']);
    }

    // Paso 5 - Puesta en marcha (BUENO/MALO)
    foreach (['pm_pedal_clutch','pm_sistema_encendido','pm_sistema_transmision','pm_sistema_direccion','pm_correas_mangueras','pm_sistema_arranque'] as $c) {
        addEnumColumn($pdo, 'alistamientos', $c, ['BUENO','MALO']);
    }

    // Paso 6 - Equipo de carretera
    foreach (['ec_senales_carretera','ec_caja_herramientas','ec_botiquin','ec_tacos_bloqueo','ec_cruceta','ec_chalecos','ec_gato','ec_extintor','ec_linterna'] as $c) {
        addEnumColumn($pdo, 'alistamientos', $c, ['BUENO','MALO']);
    }

    // Paso 7 - Revisión mecánica
    foreach (['rm_cadena_cardan','rm_freno_emergencia','rm_freno_principal','rm_suspension_gral'] as $c) {
        addEnumColumn($pdo, 'alistamientos', $c, ['BUENO','MALO']);
    }

    // Paso 8 - Cabina y carrocería
    foreach (['cc_tubos_apoyo','cc_estado_piso','cc_estado_claraboya','cc_estado_sillas','cc_cierre_puertas_ventanas','cc_aseo_vehiculo','cc_espejos_retrovisores','cc_vidrios_panoramicos','cc_salidas_emergencia','cc_disp_velocidad_aviso','cc_cinturon_conductor','cc_cinturon_auxiliar','cc_limpia_parabrisas','cc_eyector_agua','cc_estado_placas','cc_manijas_calapies'] as $c) {
        addEnumColumn($pdo, 'alistamientos', $c, ['BUENO','MALO']);
    }

    // Paso 9 - Revisión de instrumentos
    foreach (['ri_indicador_combustible','ri_velocimetro','ri_luces_direccional','ri_luces_altas','ri_tacometro_motor'] as $c) {
        addEnumColumn($pdo, 'alistamientos', $c, ['BUENO','MALO']);
    }

    // Paso 10 - Revisión de fugas (niveles/fluídos)
    foreach (['rf_aceite_motor','rf_aceite_hidraulico_direccion','rf_aceites_caja_transmision','rf_combustible_sistema','rf_liquido_freno','rf_refrigerante'] as $c) {
        addEnumColumn($pdo, 'alistamientos', $c, ['BUENO','MALO']);
    }

    // Paso 11 - Revisión eléctrica
    foreach (['re_luces_altas','re_luces_bajas','re_direccionales','re_stop_frenos','re_posicion_laterales','re_reversa','re_parqueo','re_luces_cabina','re_luces_tablero','re_pito'] as $c) {
        addEnumColumn($pdo, 'alistamientos', $c, ['BUENO','MALO']);
    }

    // Paso 12 - Revisión de llantas
    foreach (['rl_salpicaderas','rl_llanta_delantera_izquierda','rl_llanta_delantera_derecha','rl_llanta_trasera_interior_izquierda','rl_llanta_trasera_externa_izquierda','rl_llanta_trasera_interior_derecha','rl_llanta_trasera_externa_derecha','rl_llanta_repuesto','rl_pernos'] as $c) {
        addEnumColumn($pdo, 'alistamientos', $c, ['BUENO','MALO']);
    }

    echo "\nEstructura final de 'alistamientos':\n";
    $stmt = $pdo->query('DESCRIBE alistamientos');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo sprintf("%-30s %-25s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
    }
    echo "\n✓ Actualización completada\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>