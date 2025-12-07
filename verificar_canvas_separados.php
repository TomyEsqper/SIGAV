<?php
// Verificar que los canvas estén recibiendo datos separados correctamente
try {
    $pdo = new PDO("mysql:host=localhost;dbname=sigavv;charset=utf8", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICACIÓN DE CANVAS SEPARADOS EN DASHBOARD ===\n\n";
    
    // Consulta exacta del dashboard para SOAT
    echo "=== CANVAS SOAT ===\n";
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
    $soat_vigencia = $stmt->fetchAll();
    
    if (empty($soat_vigencia)) {
        echo "❌ No hay datos SOAT\n";
    } else {
        foreach ($soat_vigencia as $soat) {
            echo "✅ Estado {$soat['estado']}: {$soat['cantidad']} documentos SOAT\n";
        }
    }
    
    // Consulta exacta del dashboard para Tecnomecánica
    echo "\n=== CANVAS TECNOMECÁNICA ===\n";
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
    $tecnomecanica_vigencia = $stmt->fetchAll();
    
    if (empty($tecnomecanica_vigencia)) {
        echo "❌ No hay datos Tecnomecánica\n";
    } else {
        foreach ($tecnomecanica_vigencia as $tecno) {
            echo "✅ Estado {$tecno['estado']}: {$tecno['cantidad']} documentos Tecnomecánica\n";
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    $total_soat = array_sum(array_column($soat_vigencia, 'cantidad'));
    $total_tecno = array_sum(array_column($tecnomecanica_vigencia, 'cantidad'));
    
    echo "📊 Total documentos SOAT: $total_soat\n";
    echo "📊 Total documentos Tecnomecánica: $total_tecno\n";
    echo "📊 Total general: " . ($total_soat + $total_tecno) . "\n";
    
    if ($total_soat > 0 && $total_tecno > 0) {
        echo "\n✅ AMBOS CANVAS TIENEN DATOS - Dashboard funcionando correctamente\n";
        echo "🎯 Canvas SOAT alimentado con documentos tipo 'soat'\n";
        echo "🎯 Canvas Tecnomecánica alimentado con documentos tipo 'tecnomecanica'\n";
    } else {
        echo "\n❌ Faltan datos en uno o ambos canvas\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>