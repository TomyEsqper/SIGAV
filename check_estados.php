<?php
require_once 'config/database.php';

try {
    $db = getDB();
    $stmt = $db->query('SELECT DISTINCT estado, COUNT(*) as cantidad FROM vehiculos GROUP BY estado ORDER BY estado');
    $estados = $stmt->fetchAll();
    
    echo "Estados encontrados en la base de datos:\n";
    foreach($estados as $estado) {
        echo "- " . $estado['estado'] . ": " . $estado['cantidad'] . " vehículos\n";
    }
    
    echo "\nTodos los vehículos:\n";
    $stmt2 = $db->query('SELECT numero_interno, placa, estado FROM vehiculos ORDER BY numero_interno');
    $vehiculos = $stmt2->fetchAll();
    
    foreach($vehiculos as $vehiculo) {
        echo "- " . $vehiculo['numero_interno'] . " (" . $vehiculo['placa'] . "): " . $vehiculo['estado'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>