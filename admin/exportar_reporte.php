<?php
/**
 * Exportar Reporte (CSV/PDF) - SIGAV
 * Desarrollado por BLACK CROWSOFT
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pdf_generator.php';

// Restringir a admin
verificarSesion(['admin']);

// Parámetros
$tipo = strtolower(trim($_GET['tipo'] ?? ''));
$formato = strtolower(trim($_GET['formato'] ?? 'pdf'));
// Filtros
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$tipo_doc = trim($_GET['tipo_doc'] ?? '');
$estado_doc = trim($_GET['estado_doc'] ?? '');
$estado_veh = trim($_GET['estado_veh'] ?? '');
$buscar = trim($_GET['buscar'] ?? '');

// Tipos permitidos
$permitidos = ['alistamientos7', 'documentos_estado', 'vehiculos_estado', 'conductores_estado', 'documentos_vencer_top', 'extintor_vencido'];
if (!in_array($tipo, $permitidos, true)) {
    http_response_code(400);
    echo 'Tipo de reporte inválido';
    exit;
}

// Formatos permitidos
if (!in_array($formato, ['csv', 'pdf'], true)) {
    http_response_code(400);
    echo 'Formato inválido';
    exit;
}

$db = getDB();

// Obtener datos según tipo
$titulo = '';
$headers = [];
$rows = [];

try {
    switch ($tipo) {
        case 'alistamientos7':
            $titulo = 'Alistamientos últimos 7 días';
            $headers = ['Día', 'Cantidad'];
            $params = [];
            $where = [];
            if ($desde !== '' && $hasta !== '') {
                $where[] = "DATE(fecha_alistamiento) BETWEEN ? AND ?";
                $params[] = $desde; $params[] = $hasta;
            } else {
                $where[] = "fecha_alistamiento >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
            }
            if ($buscar !== '') {
                $where[] = "vehiculo_id IN (SELECT id FROM vehiculos WHERE placa LIKE ? OR numero_interno LIKE ?)";
                $params[] = "%$buscar%"; $params[] = "%$buscar%";
            }
            if ($estado_veh !== '') {
                $where[] = "vehiculo_id IN (SELECT id FROM vehiculos WHERE estado = ?)";
                $params[] = $estado_veh;
            }
            $sql = "SELECT DATE(fecha_alistamiento) AS dia, COUNT(*) AS cantidad FROM alistamientos";
            if ($where) { $sql .= " WHERE ".implode(" AND ", $where); }
            $sql .= " GROUP BY DATE(fecha_alistamiento) ORDER BY dia ASC";
            $data = $db->fetchAll($sql, $params);
            foreach ($data as $r) { $rows[] = ['dia' => $r['dia'] ?? '', 'cantidad' => (int)($r['cantidad'] ?? 0)]; }
            break;

        case 'documentos_estado':
            $titulo = 'Documentos por estado';
            $headers = ['Estado', 'Cantidad'];
            // Aplicar filtros a documentos mediante JOIN a vehiculos
            $params = [];
            $where = [];
            if ($tipo_doc !== '') { $where[] = "d.tipo = ?"; $params[] = $tipo_doc; }
            if ($desde !== '') { $where[] = "DATE(d.fecha_vencimiento) >= ?"; $params[] = $desde; }
            if ($hasta !== '') { $where[] = "DATE(d.fecha_vencimiento) <= ?"; $params[] = $hasta; }
            if ($buscar !== '') { $where[] = "(v.placa LIKE ? OR v.numero_interno LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
            if ($estado_veh !== '') { $where[] = "v.estado = ?"; $params[] = $estado_veh; }
            $sql = "SELECT d.fecha_vencimiento FROM documentos d JOIN vehiculos v ON d.vehiculo_id = v.id";
            if ($where) { $sql .= " WHERE ".implode(" AND ", $where); }
            $docs = $db->fetchAll($sql, $params);
            $counts = ['Vigentes'=>0,'Próximos a vencer (≤2 meses)'=>0,'Por vencer (≤30 días)'=>0,'Vencidos'=>0];
            foreach ($docs as $d) {
                $fv = $d['fecha_vencimiento'];
                $estadoCalc = 'Vigentes';
                if ($fv < date('Y-m-d')) { $estadoCalc = 'Vencidos'; }
                elseif ($fv <= date('Y-m-d', strtotime('+1 month'))) { $estadoCalc = 'Por vencer (≤30 días)'; }
                elseif ($fv <= date('Y-m-d', strtotime('+2 months'))) { $estadoCalc = 'Próximos a vencer (≤2 meses)'; }
                // Si se filtró por estado específico, respetarlo
                if ($estado_doc !== '') {
                    $map = ['verde'=>'Vigentes','azul'=>'Próximos a vencer (≤2 meses)','amarillo'=>'Por vencer (≤30 días)','rojo'=>'Vencidos'];
                    if (($map[$estado_doc] ?? '') !== $estadoCalc) { continue; }
                }
                $counts[$estadoCalc]++;
            }
            foreach ($counts as $k=>$v) { $rows[] = ['estado'=>$k, 'cantidad'=>(int)$v]; }
            break;

        case 'vehiculos_estado':
            $titulo = 'Vehículos por estado';
            $headers = ['Estado', 'Cantidad'];
            $params = [];
            $where = [];
            if ($buscar !== '') { $where[] = "(placa LIKE ? OR numero_interno LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
            if ($estado_veh !== '') { $where[] = "estado = ?"; $params[] = $estado_veh; }
            $sql = "SELECT estado AS estado, COUNT(*) AS cantidad FROM vehiculos";
            if ($where) { $sql .= " WHERE ".implode(" AND ", $where); }
            $sql .= " GROUP BY estado ORDER BY estado";
            $data = $db->fetchAll($sql, $params);
            foreach ($data as $r) { $rows[] = ['estado' => ucfirst($r['estado'] ?? 'N/A'), 'cantidad' => (int)($r['cantidad'] ?? 0)]; }
            break;

        case 'conductores_estado':
            $titulo = 'Conductores por estado';
            $headers = ['Estado', 'Cantidad'];
            $data = $db->fetchAll("SELECT activo, COUNT(*) AS cantidad FROM conductores GROUP BY activo ORDER BY activo");
            foreach ($data as $r) {
                $estado = ((int)($r['activo'] ?? 0) === 1) ? 'Activos' : 'Inactivos';
                $rows[] = ['estado' => $estado, 'cantidad' => (int)($r['cantidad'] ?? 0)];
            }
            break;

        case 'documentos_vencer_top':
            $titulo = 'Documentos por vencer (Top 10)';
            $headers = ['Tipo','Placa','Interno','Fecha vencimiento','Días restantes'];
            $params = [];
            $where = ["d.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"];
            if ($tipo_doc !== '') { $where[] = "d.tipo = ?"; $params[] = $tipo_doc; }
            if ($buscar !== '') { $where[] = "(v.placa LIKE ? OR v.numero_interno LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
            if ($estado_veh !== '') { $where[] = "v.estado = ?"; $params[] = $estado_veh; }
            if ($desde !== '') { $where[] = "DATE(d.fecha_vencimiento) >= ?"; $params[] = $desde; }
            if ($hasta !== '') { $where[] = "DATE(d.fecha_vencimiento) <= ?"; $params[] = $hasta; }
            $sql = "SELECT d.tipo, v.placa, v.numero_interno, d.fecha_vencimiento, DATEDIFF(d.fecha_vencimiento, CURDATE()) as dias_restantes FROM documentos d JOIN vehiculos v ON d.vehiculo_id = v.id";
            $sql .= " WHERE ".implode(" AND ", $where)." ORDER BY d.fecha_vencimiento ASC LIMIT 10";
            $data = $db->fetchAll($sql, $params);
            foreach ($data as $r) {
                $rows[] = [
                    'tipo' => $r['tipo'] ?? '',
                    'placa' => $r['placa'] ?? '',
                    'interno' => $r['numero_interno'] ?? '',
                    'fecha' => $r['fecha_vencimiento'] ?? '',
                    'dias' => (int)($r['dias_restantes'] ?? 0)
                ];
            }
            break;

        case 'extintor_vencido':
            $titulo = 'Vehículos con extintor vencido';
            $headers = ['Placa','Interno','Fecha vencimiento'];
            $params = [];
            $where = ["d.tipo = 'extintor'", "d.fecha_vencimiento < CURDATE()"];
            if ($buscar !== '') { $where[] = "(v.placa LIKE ? OR v.numero_interno LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
            if ($estado_veh !== '') { $where[] = "v.estado = ?"; $params[] = $estado_veh; }
            $sql = "SELECT v.placa, v.numero_interno, d.fecha_vencimiento FROM documentos d JOIN vehiculos v ON d.vehiculo_id = v.id";
            $sql .= " WHERE ".implode(" AND ", $where)." ORDER BY d.fecha_vencimiento ASC";
            $data = $db->fetchAll($sql, $params);
            foreach ($data as $r) {
                $rows[] = [
                    'placa' => $r['placa'] ?? '',
                    'interno' => $r['numero_interno'] ?? '',
                    'fecha' => $r['fecha_vencimiento'] ?? ''
                ];
            }
            break;
    }
} catch (Exception $e) {
    $rows = [];
}

// Exportar según formato
if ($formato === 'csv') {
    $filename = 'reporte_' . $tipo . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');
    // Encabezados
    fputcsv($out, $headers);
    // Filas
    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
    }
    fclose($out);
    exit;
}

// PDF
$pdf = new SIGAVPDFGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('SIGAV');
$pdf->SetAuthor('BLACK CROWSOFT');
$pdf->SetTitle('SIGAV - ' . $titulo);
$pdf->SetSubject('Reporte ' . $titulo);

// Márgenes y auto break
$pdf->SetMargins(20, 40, 20);
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();

// Título del reporte
$pdf->createReportTitle(strtoupper($titulo), 'Generado el ' . date('d/m/Y H:i'));

// Tarjetas resumen (si aplica)
if ($tipo !== 'alistamientos7') {
    $total = 0;
    foreach ($rows as $r) { $total += (int)$r['cantidad']; }
    $stats = [
        'total_registros' => $total,
        'bloques' => count($rows)
    ];
    $pdf->createStatisticsCards($stats);
}

// Tabla
$pdf->createTable($headers, $rows);

// Resumen
$pdf->addReportSummary(count($rows));

// Salida
$filename = 'reporte_' . $tipo . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');
exit;
?>