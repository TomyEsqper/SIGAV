<?php
/**
 * Exportar PDF de Evasión de Pasajeros - SIGAV
 * Desarrollado por BLACK CROWSOFT
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pdf_generator.php';

verificarSesion(['admin']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'ID inválido';
    exit;
}

// Encabezado de inspección
$h = null;
try {
    $h = $db->fetch("SELECT e.*, v.numero_interno, v.placa, c.nombre AS conductor_nombre, u.nombre AS usuario_nombre, u.usuario AS usuario_login
        FROM evasion_inspecciones e
        JOIN vehiculos v ON v.id = e.vehiculo_id
        LEFT JOIN conductores c ON c.id = e.conductor_id
        LEFT JOIN usuarios u ON u.id = e.usuario_id
        WHERE e.id = ?", [$id]);
} catch (Exception $e) { $h = null; }
if (!$h) {
    http_response_code(404);
    echo 'Revisión no encontrada';
    exit;
}

// Detalles
$det = [];
try {
    $det = $db->fetchAll("SELECT * FROM evasion_detalle WHERE inspeccion_id = ? ORDER BY id ASC", [$id]);
} catch (Exception $e) { $det = []; }

$fechaRev = $h['fecha_revision'] ?? date('Y-m-d');
$fechaFmt = date('d/m/Y', strtotime($fechaRev));
$ni = $h['numero_interno'] ?? '';
$placa = $h['placa'] ?? '';
$conductor = $h['conductor_nombre'] ?? '—';
$ruta = $h['ruta'] ?? '—';
$informe = $h['numero_informe'] ?? '';
$dias = $h['dias_revisados'] ?? '';
$totalPas = (int)($h['total_pasajeros'] ?? 0);
$obs = trim($h['observaciones'] ?? '');
$responsable = ($h['usuario_nombre'] ?: $h['usuario_login']) ?? 'Usuario SIGAV';

// Preparar filas para tabla
$rows = [];
foreach ($det as $d) {
    $horaFmt = $d['hora'] ? date('H:i:s', strtotime($d['hora'])) : '—';
    $rows[] = [
        'grabacion' => $d['grabacion'],
        'hora' => $horaFmt,
        'pasajeros' => (string)(int)$d['pasajeros'],
    ];
}

// Crear PDF
$pdf = new SIGAVPDFGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('SIGAV');
$pdf->SetAuthor('BLACK CROWSOFT');
$pdf->SetTitle('SIGAV - Reporte Evasión');
$pdf->SetSubject('Reporte de Evasión de Pasajeros');

$pdf->SetMargins(20, 40, 20);
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();

// Título y subtítulo
$pdf->createReportTitle(
    'REPORTE REVISIÓN CÁMARA',
    'Vehículo NI ' . $ni . ' • Placa ' . $placa . ' • Fecha ' . $fechaFmt
);

// Encabezado informativo estilo corporativo
$pdf->createInfoBox('DÍAS RELACIONADOS', $dias !== '' ? $dias : '—');
$pdf->createInfoBox('VEHÍCULO', ($ni ?: '—') . ' • ' . ($placa ?: '—'));
$pdf->createInfoBox('CONDUCTOR', $conductor);
$pdf->createInfoBox('NÚMERO DE INFORME', $informe);
$pdf->createInfoBox('RUTA', $ruta);

// Cuerpo descriptivo del caso
$intro = "El día $fechaFmt, el vehículo que cubría la ruta $ruta presentó el siguiente registro de pasajeros que ingresaron por la puerta trasera.";
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);
$pdf->MultiCell(0, 6, $intro, 0, 'L');
$pdf->Ln(3);

// Tabla de clips
$headers = ['GRABACIÓN', 'HORA', 'NO. PASAJEROS'];
$widths = [70, 40, 60];
$pdf->createTable($headers, $rows, $widths);

// Total
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(29, 78, 216);
$pdf->Cell(0, 8, 'TOTAL: ' . $totalPas . ' pasajeros', 0, 1, 'R');
$pdf->Ln(2);

// Observaciones y cierre
$parrafos = [];
if ($obs !== '') { $parrafos[] = $obs; }
$parrafos[] = 'Se adjuntan las evidencias en video, correspondientes a los tiempos de las novedades registradas anteriormente.';
$parrafos[] = 'Se comunica a gerencia que los días revisados, correspondientes a días aleatorios y por ello se informa a la fecha para su conocimiento.';
$parrafos[] = 'Cordialmente,';

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);
foreach ($parrafos as $p) {
    $pdf->MultiCell(0, 6, $p, 0, 'L');
    $pdf->Ln(1);
}

// Firma del responsable
$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(11, 30, 63);
$pdf->Cell(0, 6, strtoupper($responsable), 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'RESPONSABLE REVISIÓN DE MEMORIAS', 0, 1, 'L');
$pdf->Cell(0, 5, 'SIGAV • COTRAUTOL', 0, 1, 'L');

// Resumen del reporte
$pdf->addReportSummary(count($rows), [
    'Informe ' . $informe,
    'Vehículo ' . $ni,
    'Ruta ' . $ruta,
]);

// Salida
$filename = 'reporte_evasion_' . preg_replace('/[^A-Za-z0-9]/', '', $informe) . '_' . date('Ymd_His') . '.pdf';
$modo = strtoupper(trim($_GET['modo'] ?? 'D'));
if (!in_array($modo, ['I','D','F','S'], true)) { $modo = 'D'; }
$pdf->Output($filename, $modo);
exit;