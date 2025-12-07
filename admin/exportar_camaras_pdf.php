<?php
/**
 * Exportar PDF de Inspección de Cámaras - SIGAV
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
try {
    $header = $db->fetch("SELECT ci.*, v.numero_interno, v.placa, u.nombre AS inspector_nombre, u.usuario AS inspector_usuario
        FROM camaras_inspecciones ci
        JOIN vehiculos v ON v.id = ci.vehiculo_id
        JOIN usuarios u ON u.id = ci.inspector_id
        WHERE ci.id = ?", [$id]);
} catch (Exception $e) { $header = null; }
if (!$header) {
    http_response_code(404);
    echo 'Inspección no encontrada';
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
                $url = '/' . ltrim($e['archivo_url'], '/');
                $abs = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($e['archivo_url'], DIRECTORY_SEPARATOR));
                $evidenciasFotos[] = [ 'web' => $url, 'abs' => $abs ];
            }
        }
    }
}

// Crear PDF
$pdf = new SIGAVPDFGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('SIGAV');
$pdf->SetAuthor('BLACK CROWSOFT');
$pdf->SetTitle('SIGAV - Reporte Inspección Cámaras');
$pdf->SetSubject('Inspección Cámaras');

$pdf->SetMargins(20, 40, 20);
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();

// Título
$pdf->createReportTitle(
    'REPORTE DE INSPECCIÓN DE CÁMARAS',
    'Vehículo NI ' . $ni . ' • Placa ' . $placa . ' • Fecha ' . $fechaFmt
);

// Info
$pdf->createInfoBox('Inspector', $insp);
$pdf->createInfoBox('Estado final', $estadoTxt);
$pdf->createInfoBox('Manipulado por conductor', $manipulado);
$pdf->createInfoBox('Tipo de novedad', ucfirst($tipoNovedad));

// Caso / Observaciones generales
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(51, 102, 153);
$pdf->Cell(0, 8, 'DESCRIPCIÓN DEL CASO', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);
$pdf->MultiCell(0, 6, ($obsGeneral !== '' ? $obsGeneral : 'Sin observaciones generales registradas.'), 0, 'L');
$pdf->Ln(3);

// Ítems MALO
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(51, 102, 153);
$pdf->Cell(0, 8, 'ÍTEMS CON NOVEDAD (MALO)', 0, 1, 'L');
$pdf->SetTextColor(0);
$pdf->createTable(['Ítem', 'Observaciones', 'Evidencias'], $itemsMaloRows, [50, 90, 30]);

// Evidencias fotográficas
if (!empty($evidenciasFotos)) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(51, 102, 153);
    $pdf->Cell(0, 8, 'EVIDENCIAS FOTOGRÁFICAS', 0, 1, 'L');
    $pdf->Ln(2);

    $xStart = 20; // margen izquierdo
    $yCurr = $pdf->GetY();
    $col = 0;
    $imgW = 50; // ancho por imagen
    $gap = 10;  // separación entre columnas

    foreach ($evidenciasFotos as $img) {
        $path = $img['abs'];
        if (!$path || !file_exists($path)) { continue; }
        $x = $xStart + ($col * ($imgW + $gap));
        $pdf->Image($path, $x, $yCurr, $imgW, 0, '', '', '', false, 300, '', false, false, 1, false, false, false);
        $col++;
        if ($col >= 3) { // salto de fila
            $col = 0;
            $yCurr += 42;
            // Salto de página si se acerca al final
            if ($yCurr > 240) {
                $pdf->AddPage();
                $yCurr = $pdf->GetY();
            }
        }
    }
    $pdf->Ln(45);
}

// Resumen
$pdf->addReportSummary(count($itemsMaloRows), [
    'Vehículo NI ' . $ni,
    'Placa ' . $placa,
    'Inspector ' . $insp,
]);

// Salida
$filename = 'reporte_inspeccion_camaras_' . preg_replace('/[^A-Za-z0-9]/', '', $ni) . '_' . date('Ymd_His') . '.pdf';
$modo = strtoupper(trim($_GET['modo'] ?? 'D'));
if (!in_array($modo, ['I','D','F','S'], true)) { $modo = 'D'; }
$pdf->Output($filename, $modo);
exit;