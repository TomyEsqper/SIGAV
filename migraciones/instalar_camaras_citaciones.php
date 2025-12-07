<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

verificarSesion(['admin']);

$mensaje = [];
try {
    $db = getDB();

    // Crear tabla de citaciones de cámaras
    $db->execute("CREATE TABLE IF NOT EXISTS camaras_citaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehiculo_id INT NOT NULL,
        inspeccion_id INT NOT NULL,
        estado_citacion ENUM('pendiente','programada','resuelta','cancelada') DEFAULT 'pendiente',
        motivo VARCHAR(255) DEFAULT NULL,
        lugar VARCHAR(255) DEFAULT NULL,
        fecha_programada DATETIME DEFAULT NULL,
        nota TEXT DEFAULT NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vehiculo(vehiculo_id),
        INDEX idx_inspeccion(inspeccion_id),
        INDEX idx_estado(estado_citacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $mensaje[] = "Tabla camaras_citaciones verificada/creada";

} catch (Exception $e) {
    $mensaje[] = 'Error en migración: ' . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Citaciones Cámaras - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1 class="h4 mb-3">Instalación de Citaciones de Cámaras</h1>
        <?php foreach ($mensaje as $m): ?>
            <div class="alert alert-info"><?= htmlspecialchars($m) ?></div>
        <?php endforeach; ?>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="../admin/camaras.php">Ir a Cámaras</a>
            <a class="btn btn-secondary" href="../admin/dashboard.php">Ir al Dashboard</a>
        </div>
    </div>
</body>
</html>