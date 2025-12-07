<?php
/**
 * Instalación del Procedimiento GetDashboardStats
 * Ejecutar en navegador: /migraciones/instalar_procedimiento_dashboard.php
 */
require_once __DIR__ . '/../config/database.php';

$mensajes = [];
try {
    $db = getDB();
    $pdo = $db->getConnection();

    // Eliminar si existe para asegurar actualización
    try {
        $pdo->exec("DROP PROCEDURE IF EXISTS GetDashboardStats");
        $mensajes[] = 'Procedimiento previo eliminado (si existía).';
    } catch (Throwable $e) {
        $mensajes[] = 'Advertencia al eliminar SP: ' . $e->getMessage();
    }

    // Crear procedimiento sin DEFINER (el servidor asigna el usuario actual)
    $sql = "
    CREATE PROCEDURE GetDashboardStats()
    BEGIN
        SELECT
            (SELECT COUNT(*) FROM vehiculos WHERE estado != 'eliminado') AS total_vehiculos,
            (SELECT COUNT(*) FROM vehiculos WHERE estado = 'activo') AS vehiculos_activos,
            (SELECT COUNT(*) FROM vehiculos WHERE estado = 'mantenimiento') AS vehiculos_mantenimiento,
            (SELECT COUNT(*) FROM alistamientos WHERE DATE(fecha_alistamiento) = CURDATE()) AS alistamientos_hoy,
            (SELECT COUNT(*) FROM documentos WHERE fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS documentos_por_vencer;
    END";

    $pdo->exec($sql);
    $mensajes[] = 'Procedimiento GetDashboardStats creado/actualizado correctamente.';
} catch (Throwable $e) {
    http_response_code(500);
    $mensajes[] = 'ERROR al crear SP: ' . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar SP Dashboard - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h1 class="h4 mb-3">Instalación del Procedimiento GetDashboardStats</h1>
    <?php foreach ($mensajes as $m): ?>
        <div class="alert alert-info"><?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>
    <a class="btn btn-primary" href="../admin/dashboard.php">Ir al Dashboard</a>
</div>
</body>
</html>