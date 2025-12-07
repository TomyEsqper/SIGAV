<?php
/**
 * Enviar PDF de Inspección de Cámaras por correo - SIGAV
 * Desarrollado por BLACK CROWSOFT
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pdf_generator.php';

// Solo admin
verificarSesion(['admin']);

// Validar CSRF
$token = $_POST['csrf_token'] ?? '';
if (!verificarTokenCSRF($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$correos = trim($_POST['correos'] ?? '');
$asunto = trim($_POST['asunto'] ?? 'SIGAV - Reporte Cámaras');
$mensaje = trim($_POST['mensaje'] ?? 'Adjunto reporte de inspección de cámaras.');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de inspección inválido']);
    exit;
}
if ($correos === '') {
    echo json_encode(['success' => false, 'message' => 'Debe indicar al menos un destinatario']);
    exit;
}

// Normalizar lista de correos
$destinatarios = array_filter(array_map(function($c){ return strtolower(trim($c)); }, explode(',', $correos)));
$destinatarios = array_values(array_filter($destinatarios, function($c){ return filter_var($c, FILTER_VALIDATE_EMAIL); }));
if (empty($destinatarios)) {
    echo json_encode(['success' => false, 'message' => 'No hay correos válidos']);
    exit;
}

$db = getDB();

// Encabezado de inspección
try {
    $header = $db->fetch("SELECT ci.*, v.numero_interno, v.placa, u.nombre AS inspector_nombre, u.usuario AS inspector_usuario
        FROM camaras_inspecciones ci
        JOIN vehiculos v ON v.id = ci.vehiculo_id
        JOIN usuarios u ON u.id = ci.inspector_id
        WHERE ci.id = ?", [$id]);
} catch (Exception $e) { $header = null; }
if (!$header) {
    echo json_encode(['success' => false, 'message' => 'Inspección no encontrada']);
    exit;
}

// Detalles y evidencias
$detalles = [];
try {
    $detalles = $db->fetchAll("SELECT * FROM camaras_inspeccion_detalle WHERE inspeccion_id = ? ORDER BY id ASC", [$id]);
} catch (Exception $e) { $detalles = []; }

$evidenciasPorDetalle = [];
foreach ($detalles as $d) {
    try {
        $evs = $db->fetchAll("SELECT * FROM camaras_evidencias WHERE detalle_id = ? ORDER BY id ASC", [$d['id']]);
        $evidenciasPorDetalle[$d['id']] = $evs;
    } catch (Exception $e) { $evidenciasPorDetalle[$d['id']] = []; }
}

// Preparar datos
$fechaFmt = date('d/m/Y H:i', strtotime($header['fecha'] ?? 'now'));
$ni = $header['numero_interno'] ?? '';
$placa = $header['placa'] ?? '';
$insp = ($header['inspector_nombre'] ?: $header['inspector_usuario']) ?? '';
$estado = strtolower($header['estado_final'] ?? 'verde');
$estadoTxt = strtoupper($estado);
$manipulado = ((int)($header['manipulado_conductor'] ?? 0)) ? 'Sí' : 'No';
$tipoNovedad = $header['tipo_novedad'] ?? 'otro';
$obsGeneral = trim($header['observaciones'] ?? '');

$itemsMaloRows = [];
$evidenciasFotos = [];
foreach ($detalles as $d) {
    if (strtolower($d['estado']) === 'malo') {
        $evs = $evidenciasPorDetalle[$d['id']] ?? [];
        $itemsMaloRows[] = [
            'item' => strtoupper($d['item_key']),
            'observaciones' => $d['observaciones'] ?: '—',
            'evidencias' => (string)count($evs),
        ];
        foreach ($evs as $e) {
            if (($e['tipo'] ?? 'foto') === 'foto') {
                $abs = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($e['archivo_url'], DIRECTORY_SEPARATOR));
                if ($abs && file_exists($abs)) {
                    $evidenciasFotos[] = [ 'abs' => $abs ];
                }
            }
        }
    }
}

// Crear PDF (en memoria)
$pdf = new SIGAVPDFGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('SIGAV');
$pdf->SetAuthor('BLACK CROWSOFT');
$pdf->SetTitle('SIGAV - Reporte Inspección Cámaras');
$pdf->SetSubject('Inspección Cámaras');
$pdf->SetMargins(20, 40, 20);
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();
$pdf->createReportTitle(
    'REPORTE DE INSPECCIÓN DE CÁMARAS',
    'Vehículo NI ' . $ni . ' • Placa ' . $placa . ' • Fecha ' . $fechaFmt
);
$pdf->createInfoBox('Inspector', $insp);
$pdf->createInfoBox('Estado final', $estadoTxt);
$pdf->createInfoBox('Manipulado por conductor', $manipulado);
$pdf->createInfoBox('Tipo de novedad', ucfirst($tipoNovedad));
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(51, 102, 153);
$pdf->Cell(0, 8, 'DESCRIPCIÓN DEL CASO', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);
$pdf->MultiCell(0, 6, ($obsGeneral !== '' ? $obsGeneral : 'Sin observaciones generales registradas.'), 0, 'L');
$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(51, 102, 153);
$pdf->Cell(0, 8, 'ÍTEMS CON NOVEDAD (MALO)', 0, 1, 'L');
$pdf->SetTextColor(0);
$pdf->createTable(['Ítem', 'Observaciones', 'Evidencias'], $itemsMaloRows, [50, 90, 30]);
if (!empty($evidenciasFotos)) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(51, 102, 153);
    $pdf->Cell(0, 8, 'EVIDENCIAS FOTOGRÁFICAS', 0, 1, 'L');
    $pdf->Ln(2);
    $xStart = 20; $yCurr = $pdf->GetY(); $col = 0; $imgW = 50; $gap = 10;
    foreach ($evidenciasFotos as $img) {
        $path = $img['abs'];
        $x = $xStart + ($col * ($imgW + $gap));
        $pdf->Image($path, $x, $yCurr, $imgW, 0, '', '', '', false, 300, '', false, false, 1, false, false, false);
        $col++;
        if ($col >= 3) { $col = 0; $yCurr += 42; if ($yCurr > 240) { $pdf->AddPage(); $yCurr = $pdf->GetY(); } }
    }
    $pdf->Ln(45);
}
$pdf->addReportSummary(count($itemsMaloRows), [ 'Vehículo NI ' . $ni, 'Placa ' . $placa, 'Inspector ' . $insp ]);
$filename = 'reporte_inspeccion_camaras_' . preg_replace('/[^A-Za-z0-9]/', '', $ni) . '_' . date('Ymd_His') . '.pdf';
$pdfData = $pdf->Output($filename, 'S');

// Enviar correos con adjunto usando mail()
$from = 'SIGAV <no-reply@localhost>';
$boundary = md5(uniqid(time()));
$headers = [];
$headers[] = 'From: ' . $from;
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
$headersStr = implode("\r\n", $headers);

$body = [];
$body[] = '--' . $boundary;
$body[] = 'Content-Type: text/html; charset="UTF-8"';
$body[] = 'Content-Transfer-Encoding: 8bit';
$body[] = '';
$body[] = nl2br(htmlspecialchars($mensaje)) . '<br><br><small>Enviado desde SIGAV</small>';
$body[] = '';
$body[] = '--' . $boundary;
$body[] = 'Content-Type: application/pdf; name="' . $filename . '"';
$body[] = 'Content-Transfer-Encoding: base64';
$body[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
$body[] = '';
$body[] = chunk_split(base64_encode($pdfData));
$body[] = '--' . $boundary . '--';
$bodyStr = implode("\r\n", $body);

$enviados = 0; $fallidos = 0;
foreach ($destinatarios as $to) {
    $ok = @mail($to, $asunto, $bodyStr, $headersStr);
    if ($ok) { $enviados++; } else { $fallidos++; }
}

// Fallback: guardar archivo para descarga si no se pudo enviar
$download_url = '';
if ($enviados === 0) {
    $saveDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'envios';
    if (!is_dir($saveDir)) { @mkdir($saveDir, 0777, true); }
    $savePath = $saveDir . DIRECTORY_SEPARATOR . $filename;
    @file_put_contents($savePath, $pdfData);
    $download_url = '/storage/envios/' . $filename;
}

if ($fallidos === 0 && $enviados > 0) {
    echo json_encode(['success' => true, 'message' => 'Reporte enviado a ' . $enviados . ' destinatario(s).']);
} else if ($enviados === 0) {
    echo json_encode(['success' => false, 'message' => 'No se pudo enviar por correo. Archivo disponible para descarga.', 'download_url' => $download_url]);
} else {
    echo json_encode(['success' => true, 'message' => 'Enviado a ' . $enviados . '. Fallidos: ' . $fallidos, 'download_url' => $download_url]);
}
exit;