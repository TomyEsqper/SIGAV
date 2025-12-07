<?php
require_once '../config/auth.php';
require_once '../config/database.php';

verificarSesion(['admin']);

$db = getDB();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { die('ID inválido'); }

function mes_es($m) {
    $map = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
    return $map[intval($m)] ?? '';
}

// Cargar inspección
$insp = $db->fetch('SELECT * FROM evasion_inspecciones WHERE id = ?', [$id]);
if (!$insp) { die('Inspección no encontrada'); }

// Vehículo
$vehiculo = $db->fetch('SELECT placa, numero_interno FROM vehiculos WHERE id = ?', [$insp['vehiculo_id']]);
$placa = $vehiculo ? $vehiculo['placa'] : 'SINPLACA';
$vehiculoCodigo = $vehiculo ? $vehiculo['numero_interno'] : 'N/D';
// Ajuste solicitado: para placa TGV109 usar CO-0001
if ($vehiculo && strtoupper(trim($vehiculo['placa'])) === 'TGV109') { $vehiculoCodigo = 'CO-0001'; }
$vehiculoStr = $vehiculo ? ($placa . '-' . $vehiculoCodigo) : 'N/D';

// Conductor
$conductorStr = 'N/D';
if (!empty($insp['conductor_id'])) {
    $cond = $db->fetch('SELECT nombre, cedula FROM conductores WHERE id = ?', [$insp['conductor_id']]);
    if ($cond) { $conductorStr = $cond['nombre']; }
}

// Detalle clips
$det = $db->fetchAll('SELECT grabacion, hora, pasajeros FROM evasion_detalle WHERE inspeccion_id = ? ORDER BY id ASC', [$id]);
$registros = [];
foreach ($det as $d) {
    $horaFmt = $d['hora'] ? date('H:i:s', strtotime($d['hora'])) : '';
    $registros[] = [
        'grabacion' => $d['grabacion'],
        'hora' => $horaFmt,
        'pasajeros' => intval($d['pasajeros'])
    ];
}

// Fechas
$fr = $insp['fecha_revision']; // YYYY-MM-DD
$frParts = explode('-', $fr);
$fechaRevisionStr = count($frParts) === 3 ? (intval($frParts[2]) . ' de ' . mes_es(intval($frParts[1])) . ' ' . $frParts[0]) : $fr;
$diasRevisadosStr = $insp['dias_revisados'] ?: $fechaRevisionStr;
if (!$diasRevisadosStr || strlen(trim($diasRevisadosStr)) < 4) { $diasRevisadosStr = $fechaRevisionStr; }

// Datos para el PY
$nowIso = date('Y-m-d');
$numeroRuta = $insp['ruta'] ?: '';
$totalPasajeros = intval($insp['total_pasajeros']);

// Salida: corrección de ruta a storage/envios dentro del proyecto
$storageDir = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'envios');
if (!$storageDir || !is_dir($storageDir)) {
    $storageDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'envios';
    if (!is_dir($storageDir)) { @mkdir($storageDir, 0777, true); }
}
$nombreBase = 'reporte_revision_camaras_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $placa) . '_' . date('Ymd_His') . '.docx';
$nombreArchivo = $storageDir . DIRECTORY_SEPARATOR . $nombreBase;

$datos = [
    'fecha_actual' => $nowIso,
    'dias_revisados' => $diasRevisadosStr,
    'vehiculo' => $vehiculoStr,
    'vehiculo_codigo' => $vehiculoCodigo,
    'vehiculo_placa' => $placa,
    'conductor' => $conductorStr,
    'numero_informe' => $insp['numero_informe'],
    'fecha_revision' => $fechaRevisionStr,
    'numero_ruta' => $numeroRuta,
    'registros' => $registros,
    'total_pasajeros' => $totalPasajeros,
    'nombre_archivo' => $nombreArchivo
];

$tmpJson = $storageDir . DIRECTORY_SEPARATOR . 'tmp_evasion_' . $id . '_' . time() . '.json';
file_put_contents($tmpJson, json_encode($datos, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

// Ejecutar Python: corrección de ruta a formatoreporte.py en el raíz del proyecto
$pyScript = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'formatoreporte.py';
if (!is_file($pyScript)) { @unlink($tmpJson); die('Script Python no encontrado'); }

$cmds = [
    "python \"$pyScript\" \"$tmpJson\"",
    "py -3 \"$pyScript\" \"$tmpJson\"",
    "py \"$pyScript\" \"$tmpJson\""
];
$ok = false; $out = '';
foreach ($cmds as $cmd) {
    $out = @shell_exec($cmd . ' 2>&1');
    if (is_file($nombreArchivo)) { $ok = true; break; }
}
@unlink($tmpJson);

if (!$ok) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "No se pudo generar el DOCX. Salida:\n\n" . $out;
    exit;
}

// Descargar/mostrar
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $nombreBase . '"');
header('Content-Length: ' . filesize($nombreArchivo));
readfile($nombreArchivo);
exit;