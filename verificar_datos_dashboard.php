<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'sigavv';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICACIÓN DE DATOS PARA DASHBOARD ===\n\n";
    
    // Verificar datos totales en documentos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM documentos");
    $total = $stmt->fetch()['total'];
    echo "Total documentos en la tabla: $total\n\n";
    
    // Verificar datos SOAT
    echo "=== DATOS SOAT ===\n";
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN fecha_vencimiento < CURDATE() THEN 'rojo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'amarillo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'azul'
                ELSE 'verde'
            END as estado,
            COUNT(*) as cantidad
        FROM documentos 
        WHERE tipo_documento = 'soat'
        GROUP BY 
            CASE 
                WHEN fecha_vencimiento < CURDATE() THEN 'rojo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'amarillo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'azul'
                ELSE 'verde'
            END
        ORDER BY estado
    ");
    
    $soat_data = $stmt->fetchAll();
    foreach ($soat_data as $row) {
        echo "Estado {$row['estado']}: {$row['cantidad']} documentos\n";
    }
    
    echo "\n=== DATOS TECNOMECÁNICA ===\n";
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN fecha_vencimiento < CURDATE() THEN 'rojo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'amarillo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'azul'
                ELSE 'verde'
            END as estado,
            COUNT(*) as cantidad
        FROM documentos 
        WHERE tipo_documento IN ('tecnomecanica', 'tecnicomecanica', 'rtm')
        GROUP BY 
            CASE 
                WHEN fecha_vencimiento < CURDATE() THEN 'rojo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'amarillo'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'azul'
                ELSE 'verde'
            END
        ORDER BY estado
    ");
    
    $tecno_data = $stmt->fetchAll();
    foreach ($tecno_data as $row) {
        echo "Estado {$row['estado']}: {$row['cantidad']} documentos\n";
    }
    
    echo "\n=== MUESTRA DE DOCUMENTOS ===\n";
    $stmt = $pdo->query("
        SELECT 
            id, 
            vehiculo_id, 
            tipo_documento, 
            fecha_vencimiento,
            CASE 
                WHEN fecha_vencimiento < CURDATE() THEN 'ROJO (vencido)'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'AMARILLO (< 1 mes)'
                WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'AZUL (< 4 meses)'
                ELSE 'VERDE (> 4 meses)'
            END as estado_calculado,
            DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes
        FROM documentos 
        ORDER BY tipo_documento, fecha_vencimiento
        LIMIT 10
    ");
    
    $documentos = $stmt->fetchAll();
    foreach ($documentos as $doc) {
        echo "ID: {$doc['id']} | Vehículo: {$doc['vehiculo_id']} | Tipo: {$doc['tipo_documento']} | ";
        echo "Vencimiento: {$doc['fecha_vencimiento']} | Estado: {$doc['estado_calculado']} | ";
        echo "Días: {$doc['dias_restantes']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>