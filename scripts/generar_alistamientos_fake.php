<?php
/**
 * Generador de alistamientos fake para demostración
 * Rango: 2025-11-01 al 2025-12-02 (inclusive)
 * Todos en VERDE, cantidad diaria variable (máx ~200), domingos más baja.
 * Ejecutar sólo en local: /scripts/generar_alistamientos_fake.php?confirm=1
 */
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('America/Bogota');
header('Content-Type: text/plain; charset=utf-8');

$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$isCli = (PHP_SAPI === 'cli');
$confirm = $isCli ? true : (isset($_GET['confirm']) && $_GET['confirm'] === '1');
if (!$confirm) {
    if (!$isCli) { http_response_code(400); }
    echo "Error: falta confirmación. Ejecute con ?confirm=1\n";
    exit;
}
if (!$isCli && $clientIp !== '127.0.0.1' && $clientIp !== '::1') {
    if (!$isCli) { http_response_code(403); }
    echo "Acceso denegado: ejecute este script solo desde localhost. IP: {$clientIp}\n";
    exit;
}

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int)($row['c'] ?? 0)) > 0;
    } catch (Throwable $e) { return false; }
}

try {
    $db = getDB();
    $pdo = $db->getConnection();

    // Recopilar IDs disponibles
    $vehiculos = [];
    try {
        $vehiculos = $db->fetchAll("SELECT id FROM vehiculos WHERE estado != 'eliminado' AND (estado = 'activo' OR estado IS NULL)");
    } catch (Exception $e) {}
    if (!$vehiculos) { echo "Sin vehículos disponibles.\n"; exit; }
    $vehiculoIds = array_map(fn($r) => (int)$r['id'], $vehiculos);

    $inspectores = [];
    try {
        $inspectores = $db->fetchAll("SELECT id FROM usuarios WHERE rol = 'inspector' AND activo = 1");
    } catch (Exception $e) {}
    if (!$inspectores) {
        $inspectores = $db->fetchAll("SELECT id FROM usuarios WHERE rol = 'admin' AND activo = 1");
    }
    if (!$inspectores) { echo "Sin usuarios inspectores/admin activos.\n"; exit; }
    $inspectorIds = array_map(fn($r) => (int)$r['id'], $inspectores);

    $conductorIds = [];
    $hasConductorId = columnExists($pdo, 'alistamientos', 'conductor_id');
    if ($hasConductorId) {
        try {
            $rows = $db->fetchAll("SELECT id FROM conductores WHERE activo = 1");
            $conductorIds = array_map(fn($r) => (int)$r['id'], $rows);
        } catch (Exception $e) { $conductorIds = []; }
    }

    // Preparar sentencia de inserción
    $baseSql = "INSERT INTO alistamientos (vehiculo_id, inspector_id, estado_final, es_alistamiento_parcial, fecha_alistamiento, observaciones_generales";
    $baseVals = ") VALUES (?, ?, 'verde', 0, ?, ?)";
    $sql = $baseSql;
    $useConductor = false;
    if ($hasConductorId && !empty($conductorIds)) {
        $sql = "INSERT INTO alistamientos (vehiculo_id, inspector_id, conductor_id, estado_final, es_alistamiento_parcial, fecha_alistamiento, observaciones_generales) VALUES (?, ?, ?, 'verde', 0, ?, ?)";
        $useConductor = true;
    } else {
        $sql = $baseSql . $baseVals;
    }
    $stmt = $pdo->prepare($sql);

    $start = new DateTime('2025-11-01');
    $end = new DateTime('2025-12-02');
    $end->setTime(23,59,59);

    $totalInserted = 0;
    echo "Generando alistamientos fake (todos en VERDE) ...\n";

    for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
        $dow = (int)$d->format('N'); // 1..7
        // Cantidades diarias:
        // L-V: 120–180, Sáb: 80–120, Dom: 40–90 (máx ~200)
        if ($dow >= 1 && $dow <= 5) {
            $qty = random_int(120, 180);
        } elseif ($dow === 6) {
            $qty = random_int(80, 120);
        } else { // domingo
            $qty = random_int(40, 90);
        }

        // Seleccionar vehículos únicos del día
        $selectedVehiculos = [];
        $vehCount = count($vehiculoIds);
        for ($i = 0; $i < $qty; $i++) {
            $vid = $vehiculoIds[random_int(0, $vehCount - 1)];
            // Evitar duplicado por día
            if (isset($selectedVehiculos[$vid])) { continue; }
            $selectedVehiculos[$vid] = true;

            $insId = $inspectorIds[random_int(0, count($inspectorIds) - 1)];
            $obs = 'Alistamiento simulado (demo)';

            // Hora aleatoria dentro del día
            $hour = random_int(6, 20);
            $min = random_int(0, 59);
            $sec = random_int(0, 59);
            $dateStr = $d->format('Y-m-d') . sprintf(' %02d:%02d:%02d', $hour, $min, $sec);

            try {
                if ($useConductor) {
                    $cid = $conductorIds ? $conductorIds[random_int(0, count($conductorIds) - 1)] : null;
                    $stmt->execute([$vid, $insId, $cid, $dateStr, $obs]);
                } else {
                    $stmt->execute([$vid, $insId, $dateStr, $obs]);
                }
                $totalInserted++;
            } catch (Throwable $e) {
                // Ignorar errores puntuales para continuidad
            }
        }
        echo $d->format('Y-m-d') . " -> " . count($selectedVehiculos) . " registros\n";
    }

    echo "\nTotal insertado: {$totalInserted}\n";
    echo "Listo.\n";

} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error generando alistamientos fake: ' . $e->getMessage() . "\n";
}

?>
