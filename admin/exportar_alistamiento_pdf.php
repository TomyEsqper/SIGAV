<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pdf_generator.php';

verificarSesion(['admin']);

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo 'ID inválido'; exit; }

$header = null;
try {
    $header = $db->fetch("SELECT a.*, v.numero_interno, v.placa, v.propietario, u.nombre AS inspector_nombre, u.usuario AS inspector_usuario, c.nombre AS conductor_nombre, c.cedula AS conductor_cedula, c.telefono AS conductor_telefono FROM alistamientos a LEFT JOIN vehiculos v ON v.id = a.vehiculo_id LEFT JOIN usuarios u ON u.id = a.inspector_id LEFT JOIN conductores c ON c.id = a.conductor_id WHERE a.id = ?", [$id]);
} catch (Exception $e) {
    try {
        // Fallback para esquemas sin columna conductor_id
        $header = $db->fetch("SELECT a.*, v.numero_interno, v.placa, v.propietario, u.nombre AS inspector_nombre, u.usuario AS inspector_usuario FROM alistamientos a LEFT JOIN vehiculos v ON v.id = a.vehiculo_id LEFT JOIN usuarios u ON u.id = a.inspector_id WHERE a.id = ?", [$id]);
    } catch (Exception $e2) { $header = null; }
}
if (!$header) { http_response_code(404); echo 'Alistamiento no encontrado'; exit; }

$detalles = [];
try {
    $detalles = $db->fetchAll("SELECT da.id as detalle_id, da.estado, da.foto_url, ic.nombre as item_nombre, cc.nombre as categoria_nombre, da.observaciones FROM detalle_alistamiento da LEFT JOIN items_checklist ic ON da.item_id = ic.id LEFT JOIN categorias_checklist cc ON ic.categoria_id = cc.id WHERE da.alistamiento_id = ? ORDER BY cc.orden ASC, ic.orden ASC, da.id ASC", [$id]);
} catch (Exception $e) { $detalles = []; }
if (empty($detalles)) {
    try {
        $detalles = $db->fetchAll("SELECT da.id as detalle_id, da.estado, da.foto_url, da.observaciones, cc.nombre as categoria_nombre FROM detalle_alistamiento da LEFT JOIN categorias_checklist cc ON da.categoria_id = cc.id WHERE da.alistamiento_id = ? ORDER BY da.id ASC", [$id]);
    } catch (Exception $e) { $detalles = []; }
}

$fechaFmt = !empty($header['fecha_alistamiento']) ? date('d/m/Y H:i', strtotime($header['fecha_alistamiento'])) : date('d/m/Y H:i');
$ni = (string)($header['numero_interno'] ?? '');
$placa = (string)($header['placa'] ?? '');
$prop = (string)($header['propietario'] ?? '');
$insp = (string)(($header['inspector_nombre'] ?: $header['inspector_usuario']) ?? '');
$estado = strtoupper((string)($header['estado_final'] ?? ''));
$conductorNom = trim((string)($header['conductor_nombre'] ?? ''));
$conductorCed = trim((string)($header['conductor_cedula'] ?? ''));
$conductorTel = trim((string)($header['conductor_telefono'] ?? ''));

if (!in_array($estado, ['AMARILLO','ROJO'], true)) { http_response_code(400); echo 'El reporte aplica solo para alistamientos en estado AMARILLO o ROJO'; exit; }

// Fallback 1: conductor específico del alistamiento
if ($conductorNom === '') {
    try {
        $fc = $db->fetch('SELECT c.nombre, c.cedula, c.telefono FROM conductores c INNER JOIN alistamientos a ON a.id = ? AND c.id = a.conductor_id', [$id]);
        if ($fc) {
            $conductorNom = trim((string)($fc['nombre'] ?? ''));
            $conductorCed = trim((string)($fc['cedula'] ?? ''));
            $conductorTel = trim((string)($fc['telefono'] ?? ''));
        }
    } catch (Exception $e) { }
}

// Fallback 1b: usar inspector_id como conductor si conductor_id no existe
if ($conductorNom === '' && !empty($header['inspector_id'])) {
    try {
        $ci = $db->fetch('SELECT nombre, cedula, telefono FROM conductores WHERE id = ?', [intval($header['inspector_id'])]);
        if ($ci) {
            $conductorNom = trim((string)($ci['nombre'] ?? ''));
            $conductorCed = trim((string)($ci['cedula'] ?? ''));
            $conductorTel = trim((string)($ci['telefono'] ?? ''));
        }
    } catch (Exception $e) { }
}

// Fallback 2: conductor del último alistamiento del mismo vehículo
if ($conductorNom === '' && !empty($header['vehiculo_id'])) {
    try {
        $f = $db->fetch('SELECT c.nombre, c.cedula, c.telefono FROM alistamientos a LEFT JOIN conductores c ON c.id = a.conductor_id WHERE a.vehiculo_id = ? AND a.id <= ? AND a.conductor_id IS NOT NULL ORDER BY a.id DESC LIMIT 1', [$header['vehiculo_id'], $id]);
        if ($f) {
            $conductorNom = trim((string)($f['nombre'] ?? ''));
            $conductorCed = trim((string)($f['cedula'] ?? ''));
            $conductorTel = trim((string)($f['telefono'] ?? ''));
        }
    } catch (Exception $e) { }
}

$rows = [];
$malosRows = [];
$okRows = [];
$evidenciasFotos = [];
foreach ($detalles as $d) {
    $cat = $d['categoria_nombre'] ?? '';
    $item = $d['item_nombre'] ?? ('Detalle #' . (int)($d['detalle_id'] ?? 0));
    $estadoDet = strtolower((string)($d['estado'] ?? ''));
    $obs = ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : '';
    $absImg = '';
    if (!empty($d['foto_url'])) {
        $abs = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($d['foto_url'], DIRECTORY_SEPARATOR));
        if ($abs && file_exists($abs)) { $absImg = $abs; }
    }
    $rows[] = [ 'categoria' => $cat, 'item' => strtoupper($item), 'estado' => strtoupper($estadoDet) ];
    $isMalo = ($estadoDet === 'malo') || (strpos($estadoDet, 'mal') === 0);
    if ($isMalo) { $malosRows[] = [ 'categoria' => $cat, 'item' => strtoupper($item), 'evidencia' => $absImg ]; }
    if ($estadoDet === 'ok' || $estadoDet === 'bueno')   { $okRows[]   = [ 'categoria' => $cat, 'item' => strtoupper($item) ]; }
    if (!empty($d['foto_url'])) {
        $url = '/' . ltrim($d['foto_url'], '/');
        $abs = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($d['foto_url'], DIRECTORY_SEPARATOR));
        if ($abs && file_exists($abs)) { $evidenciasFotos[] = [ 'web' => $url, 'abs' => $abs ]; }
    }
}

$pdf = new SIGAVPDFGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('SIGAV');
$pdf->SetAuthor('BLACK CROWSOFT');
$pdf->SetTitle('SIGAV - Reporte Alistamiento');
$pdf->SetSubject('Alistamiento Vehicular');
$pdf->SetMargins(20, 40, 20);
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();

$pdf->createReportTitle('REPORTE DE ALISTAMIENTO VEHICULAR', 'Vehículo NI ' . $ni . ' • Placa ' . $placa . ' • Fecha ' . $fechaFmt);

$pdf->createInfoBox('Propietario', ($prop !== '' ? $prop : 'N/A'));
$pdf->createInfoBox('Conductor', ($conductorNom !== '' ? $conductorNom : 'No asignado'));
if ($conductorTel !== '') { $pdf->createInfoBox('Teléfono', $conductorTel); }
$pdf->createInfoBox('Estado final', ($estado !== '' ? $estado : 'N/A'));

// Cuadro de ítems en estado MALO
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(51, 102, 153);
$pdf->Cell(0, 8, 'ÍTEMS EN ESTADO MALO', 0, 1, 'L');
$pdf->SetTextColor(0);
if (!empty($malosRows)) {
    $pdf->createMalosTable($malosRows, [60, 70, 40]);
} else {
    $pdf->SetFont('helvetica', '', 10);
    // Fallback: reconstruir tabla de MALO directo desde BD
    try {
        $malosDirect = $db->fetchAll("SELECT da.estado, da.foto_url, ic.nombre AS item_nombre, cc.nombre AS categoria_nombre FROM detalle_alistamiento da LEFT JOIN items_checklist ic ON ic.id = da.item_id LEFT JOIN categorias_checklist cc ON cc.id = ic.categoria_id WHERE da.alistamiento_id = ? AND LOWER(da.estado) LIKE 'mal%' ORDER BY cc.orden, ic.orden, da.id", [$id]);
    } catch (Exception $e) { $malosDirect = []; }
    $recon = [];
    foreach ($malosDirect as $md) {
        $catN = $md['categoria_nombre'] ?? '';
        $itemN = strtoupper((string)($md['item_nombre'] ?? 'ITEM'));
        $absI = '';
        if (!empty($md['foto_url'])) {
            $absI2 = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($md['foto_url'], DIRECTORY_SEPARATOR));
            if ($absI2 && file_exists($absI2)) { $absI = $absI2; }
        }
        $recon[] = [ 'categoria' => $catN, 'item' => $itemN, 'evidencia' => $absI ];
    }
    if (!empty($recon)) {
        $pdf->createMalosTable($recon, [60, 70, 40]);
    } else {
        $pdf->Cell(0, 6, 'No hay ítems reportados en estado MALO para este alistamiento.', 0, 1, 'L');
        $pdf->Ln(4);
    }
}

// Mensaje de notificación

$prevId = 0;
try {
    $prevRow = $db->fetch('SELECT id FROM alistamientos WHERE vehiculo_id = ? AND id < ? ORDER BY id DESC LIMIT 1', [$header['vehiculo_id'], $id]);
    $prevId = intval($prevRow['id'] ?? 0);
} catch (Exception $e) { $prevId = 0; }

if (!empty($header['es_alistamiento_parcial']) && $prevId > 0) {
    $prevPorCat = [];
    $despuesPorCat = [];
    $pendientePorCat = [];
    try {
        $qA = 'SELECT da.categoria_id, da.foto_url, cc.nombre AS categoria_nombre FROM detalle_alistamiento da LEFT JOIN categorias_checklist cc ON cc.id = da.categoria_id WHERE da.alistamiento_id = ? AND da.estado = "malo" AND da.foto_url IS NOT NULL AND da.foto_url <> "" ORDER BY da.categoria_id, da.id';
        $qD = 'SELECT da.categoria_id, da.foto_url, cc.nombre AS categoria_nombre FROM detalle_alistamiento da LEFT JOIN categorias_checklist cc ON cc.id = da.categoria_id WHERE da.alistamiento_id = ? AND da.estado = "ok" AND da.foto_url IS NOT NULL AND da.foto_url <> "" ORDER BY da.categoria_id, da.id';
        $qM = 'SELECT da.categoria_id, da.foto_url, cc.nombre AS categoria_nombre FROM detalle_alistamiento da LEFT JOIN categorias_checklist cc ON cc.id = da.categoria_id WHERE da.alistamiento_id = ? AND da.estado = "malo" AND da.foto_url IS NOT NULL AND da.foto_url <> "" ORDER BY da.categoria_id, da.id';
        foreach ($db->fetchAll($qA, [$prevId]) as $r) { $cid = (int)($r['categoria_id'] ?? 0); $prevPorCat[$cid]['nombre'] = ($r['categoria_nombre'] ?? 'Categoría'); $prevPorCat[$cid]['items'][] = $r['foto_url']; }
        foreach ($db->fetchAll($qD, [$id]) as $r)   { $cid = (int)($r['categoria_id'] ?? 0); $despuesPorCat[$cid]['nombre'] = ($r['categoria_nombre'] ?? 'Categoría'); $despuesPorCat[$cid]['items'][] = $r['foto_url']; }
        foreach ($db->fetchAll($qM, [$id]) as $r)   { $cid = (int)($r['categoria_id'] ?? 0); $pendientePorCat[$cid]['nombre'] = ($r['categoria_nombre'] ?? 'Categoría'); $pendientePorCat[$cid]['items'][] = $r['foto_url']; }
    } catch (Exception $e) { }

    $catIds = array_unique(array_merge(array_keys($prevPorCat), array_keys($despuesPorCat), array_keys($pendientePorCat)));
    if (!empty($catIds)) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(51, 102, 153);
        $pdf->Cell(0, 8, 'COMPARATIVA ANTES / DESPUES (PARCIAL)', 0, 1, 'L');
        $pdf->SetTextColor(0);
        foreach ($catIds as $cid) {
            $nombreCat = ($prevPorCat[$cid]['nombre'] ?? $despuesPorCat[$cid]['nombre'] ?? $pendientePorCat[$cid]['nombre'] ?? 'Categoría');
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 6, $nombreCat, 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $antesList = $prevPorCat[$cid]['items'] ?? [];
            $despList   = $despuesPorCat[$cid]['items'] ?? [];
            $pendList   = $pendientePorCat[$cid]['items'] ?? [];
            $max = max(count($antesList), count($despList), count($pendList));
            $xStart = 20; $imgW = 70; $gap = 10; $yCurr = $pdf->GetY();
            for ($i = 0; $i < $max; $i++) {
                $pdf->SetXY($xStart, $yCurr);
                $pdf->Cell($imgW, 6, 'ANTES', 0, 0, 'L');
                $label = isset($despList[$i]) ? 'DESPUES' : (isset($pendList[$i]) ? 'SIGUE MALO' : 'DESPUES');
                $pdf->SetXY($xStart + $imgW + $gap, $yCurr);
                $pdf->Cell($imgW, 6, $label, 0, 1, 'L');
                $yCurr += 7;
                if (isset($antesList[$i])) {
                    $absA = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($antesList[$i], DIRECTORY_SEPARATOR));
                    if ($absA && file_exists($absA)) { $pdf->Image($absA, $xStart, $yCurr, $imgW, 0, '', '', '', false, 300); }
                }
                if (isset($despList[$i])) {
                    $absD = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($despList[$i], DIRECTORY_SEPARATOR));
                    if ($absD && file_exists($absD)) { $pdf->Image($absD, $xStart + $imgW + $gap, $yCurr, $imgW, 0, '', '', '', false, 300); }
                } elseif (isset($pendList[$i])) {
                    $absP = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($pendList[$i], DIRECTORY_SEPARATOR));
                    if ($absP && file_exists($absP)) { $pdf->Image($absP, $xStart + $imgW + $gap, $yCurr, $imgW, 0, '', '', '', false, 300); }
                } else {
                    $pdf->SetXY($xStart + $imgW + $gap, $yCurr + 20);
                    $pdf->Cell($imgW, 6, 'Sin foto', 0, 1, 'C');
                }
                $yCurr += 60;
                if ($yCurr > 240) { $pdf->AddPage(); $yCurr = $pdf->GetY(); }
            }
            $pdf->Ln(4);
        }
    }
}

$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(51, 102, 153);
$pdf->Cell(0, 8, 'NOTIFICACIÓN AL PROPIETARIO', 0, 1, 'L');
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 10);
$mensaje = 'Por medio del presente se informa que en el alistamiento del vehículo NI ' . $ni . ', placa ' . $placa . ', realizado el ' . $fechaFmt . ', se identificaron ítems en estado MALO, detallados en el cuadro de Categoría, Ítem y Evidencia. ';
$mensaje .= 'En consecuencia, el alistamiento queda en estado PARCIAL. Una vez se realicen las correcciones, debe repetirse el proceso mediante Alistamiento Parcial, revisando únicamente los ítems reportados en mal estado y adjuntando las fotos de las correcciones efectuadas. ';
if (!empty($conductorNom)) { $mensaje .= 'Conductor responsable el día de la revisión: ' . $conductorNom . '. '; }
$pdf->MultiCell(0, 6, $mensaje, 0, 'L');

// Bloque de firmas
$pdf->Ln(6);
$pdf->SetFont('helvetica', '', 10);
$y = $pdf->GetY();
$pdf->Line(35, $y + 18, 95, $y + 18);
$pdf->SetXY(35, $y + 20); $pdf->Cell(60, 6, 'Propietario: ' . ($prop !== '' ? $prop : '____________________'), 0, 0, 'C');
$pdf->Ln(22);

$filename = 'reporte_alistamiento_' . preg_replace('/[^A-Za-z0-9]/', '', $ni) . '_' . date('Ymd_His') . '.pdf';
$modo = strtoupper(trim($_GET['modo'] ?? 'D'));
if (!in_array($modo, ['I','D','F','S'], true)) { $modo = 'D'; }
$pdf->Output($filename, $modo);
exit;
