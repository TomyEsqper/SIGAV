<?php
require_once __DIR__ . '/../vendor/autoload.php';

class SIGAVPDFGenerator extends TCPDF {
    
    public function Header() {
        $this->SetY(10);
        $candidates = [
            realpath(__DIR__ . '/../assets/img/cotrautol_logo.png'),
            realpath(__DIR__ . '/../assets/img/cotrautol_logo.jpg'),
            realpath(__DIR__ . '/../assets/img/cotrautol_logo.jpeg'),
            realpath(__DIR__ . '/../assets/img/cotrautol_logo.svg'),
            realpath(__DIR__ . '/../cotrautol_logo.png'),
            realpath(__DIR__ . '/../cotrautol_logo.jpg'),
            realpath(__DIR__ . '/../cotrautol_logo.jpeg'),
            realpath(__DIR__ . '/../cotrautol_logo.svg')
        ];
        $logo = null;
        foreach ($candidates as $p) { if ($p && file_exists($p)) { $logo = $p; break; } }
        if ($logo) {
            $ext = strtolower(pathinfo($logo, PATHINFO_EXTENSION));
            if ($ext === 'svg') { $this->ImageSVG($logo, 20, 6, 75, '', '', '', '', 300); }
            else { $this->Image($logo, 20, 8, 75, 0, '', '', '', false, 300); }
        }
        $this->SetDrawColor(29, 78, 216);
        $this->SetLineWidth(1.0);
        $this->Line(20, 42, 190, 42);
    }
    
    public function Footer() {
        $this->SetY(-20);
        $this->SetDrawColor(29, 78, 216);
        $this->SetLineWidth(0.5);
        $this->Line(20, $this->GetY(), 190, $this->GetY());
        $this->Ln(3);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'SIGAV — Black CrowSoft', 0, 1, 'C');
    }
    
    public function createReportTitle($title, $subtitle = '') {
        // Título principal alineado a la izquierda con línea inferior
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(11, 30, 63);
        $this->Cell(0, 10, $title, 0, 1, 'L');
        
        if ($subtitle) {
            $this->SetFont('helvetica', '', 11);
            $this->SetTextColor(75, 85, 99);
            $this->Cell(0, 8, $subtitle, 0, 1, 'L');
        }
        
        $y = $this->GetY() + 2;
        $this->SetDrawColor(29, 78, 216);
        $this->SetLineWidth(0.6);
        $this->Line(20, $y, 190, $y);
        $this->Ln(6);
    }
    
    public function createInfoBox($title, $content) {
        // Meta en texto simple (sin caja) al estilo mostrado
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(11, 30, 63);
        $this->Cell(40, 6, $title . ':', 0, 0, 'L');
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(17, 17, 17);
        $this->Cell(130, 6, $content, 0, 1, 'L');
        $this->Ln(2);
    }
    
    public function createTable($headers, $data, $widths = null) {
        if (!$widths) {
            $widths = array_fill(0, count($headers), 170 / count($headers));
        }
        
        // Encabezados de tabla (azul corporativo)
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(29, 78, 216);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(29, 78, 216);
        
        for ($i = 0; $i < count($headers); $i++) {
            $this->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Datos de la tabla
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(60, 60, 60);
        $fill = false;
        
        foreach ($data as $row) {
            $this->SetFillColor($fill ? 248 : 255, $fill ? 248 : 255, $fill ? 248 : 255);
            
            for ($i = 0; $i < count($headers); $i++) {
                $value = isset($row[array_keys($row)[$i]]) ? $row[array_keys($row)[$i]] : '';
                
                // Formatear valores especiales
                if (strpos(array_keys($row)[$i], 'fecha') !== false && $value) {
                    $value = date('d/m/Y', strtotime($value));
                }
                
                $this->Cell($widths[$i], 6, $this->formatCellValue($value), 1, 0, 'C', $fill);
            }
            $this->Ln();
            $fill = !$fill;
        }
        
        $this->Ln(5);
    }

    public function createMalosTable($data, $widths = [60, 70, 40]) {
        $headers = ['Categoría', 'Ítem', 'Evidencia'];
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(29, 78, 216);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(29, 78, 216);
        for ($i = 0; $i < count($headers); $i++) {
            $this->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(60, 60, 60);
        $fill = false;
        foreach ($data as $row) {
            $this->SetFillColor($fill ? 248 : 255, $fill ? 248 : 255, $fill ? 248 : 255);
            $x = 20;
            $y = $this->GetY();
            $h = 24;
            $this->SetDrawColor(200, 200, 200);
            $this->Rect($x, $y, $widths[0], $h, $fill ? 'DF' : 'D');
            $this->Rect($x + $widths[0], $y, $widths[1], $h, $fill ? 'DF' : 'D');
            $this->Rect($x + $widths[0] + $widths[1], $y, $widths[2], $h, $fill ? 'DF' : 'D');
            $this->SetXY($x + 2, $y + 3);
            $this->MultiCell($widths[0] - 4, 6, isset($row['categoria']) ? $row['categoria'] : '', 0, 'L');
            $this->SetXY($x + $widths[0] + 2, $y + 3);
            $this->MultiCell($widths[1] - 4, 6, isset($row['item']) ? $row['item'] : '', 0, 'L');
            $img = isset($row['evidencia']) ? $row['evidencia'] : '';
            if ($img && file_exists($img)) {
                $this->Image($img, $x + $widths[0] + $widths[1] + 2, $y + 2, $widths[2] - 4, 0, '', '', '', false, 300);
            } else {
                $this->SetXY($x + $widths[0] + $widths[1], $y + 9);
                $this->Cell($widths[2], 6, 'Sin foto', 0, 0, 'C');
            }
            $this->SetY($y + $h);
            $fill = !$fill;
        }
        $this->Ln(5);
    }
    
    public function createStatisticsCards($stats) {
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(29, 78, 216);
        $this->Cell(0, 8, 'RESUMEN ESTADÍSTICO', 0, 1, 'C');
        $this->Ln(3);
        
        $cardWidth = 80;
        $cardHeight = 25;
        $x = 25;
        $y = $this->GetY();
        
        $colors = [
            ['r' => 52, 'g' => 152, 'b' => 219],  // Azul
            ['r' => 46, 'g' => 204, 'b' => 113],  // Verde
            ['r' => 231, 'g' => 76, 'b' => 60],   // Rojo
            ['r' => 241, 'g' => 196, 'b' => 15]   // Amarillo
        ];
        
        $i = 0;
        foreach ($stats as $key => $value) {
            if ($i % 2 == 0 && $i > 0) {
                $y += $cardHeight + 5;
                $x = 25;
            }
            
            $color = $colors[$i % count($colors)];
            
            // Fondo de la tarjeta
            $this->SetFillColor($color['r'], $color['g'], $color['b']);
            $this->Rect($x, $y, $cardWidth, $cardHeight, 'F');
            
            // Texto de la tarjeta
            $this->SetXY($x + 5, $y + 5);
            $this->SetFont('helvetica', 'B', 14);
            $this->SetTextColor(255, 255, 255);
            $this->Cell($cardWidth - 10, 8, $value, 0, 1, 'C');
            
            $this->SetXY($x + 5, $y + 13);
            $this->SetFont('helvetica', '', 9);
            $this->Cell($cardWidth - 10, 6, ucfirst(str_replace('_', ' ', $key)), 0, 1, 'C');
            
            $x += $cardWidth + 10;
            $i++;
        }
        
        $this->SetY($y + $cardHeight + 10);
    }
    
    private function formatCellValue($value) {
        if (is_null($value) || $value === '') {
            return 'N/A';
        }
        
        // Truncar texto largo
        if (strlen($value) > 25) {
            return substr($value, 0, 22) . '...';
        }
        
        return $value;
    }
    
    public function addReportSummary($totalRecords, $filters = []) {
        $this->Ln(5);
        
        // Box de resumen
        $this->SetFillColor(245, 245, 245);
        $this->SetDrawColor(200, 200, 200);
        $this->Rect(20, $this->GetY(), 170, 20, 'DF');
        
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 8, 'RESUMEN DEL REPORTE', 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 9);
        $this->Cell(85, 6, 'Total de registros: ' . $totalRecords, 0, 0, 'L');
        
        if (!empty($filters)) {
            $filterText = 'Filtros aplicados: ' . implode(', ', $filters);
            $this->Cell(85, 6, $filterText, 0, 1, 'R');
        } else {
            $this->Cell(85, 6, 'Sin filtros aplicados', 0, 1, 'R');
        }
        
        $this->Ln(5);
    }
}
?>
