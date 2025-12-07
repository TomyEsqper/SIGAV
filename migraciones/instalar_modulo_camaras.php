<?php
/**
 * Instalación del Módulo de Cámaras (rol + tablas + usuario inicial)
 * Ejecutar en navegador: /migraciones/instalar_modulo_camaras.php
 */
require_once __DIR__ . '/../config/database.php';

function columnaExiste($db, $tabla, $columna) {
    $row = $db->fetch(
        "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$tabla, $columna]
    );
    return ((int)($row['c'] ?? 0)) > 0;
}

function enumContieneValor($db, $tabla, $columna, $valor) {
    $row = $db->fetch(
        "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$tabla, $columna]
    );
    $type = $row['COLUMN_TYPE'] ?? '';
    return stripos($type, "'" . $valor . "'") !== false;
}

$mensaje = [];
try {
    $db = getDB();

    // 1) Ampliar ENUM de usuarios.rol para incluir inspector_camaras
    if (columnaExiste($db, 'usuarios', 'rol')) {
        $tiene = enumContieneValor($db, 'usuarios', 'rol', 'inspector_camaras');
        if (!$tiene) {
            // Detectar valores actuales del ENUM y agregar el nuevo
            $row = $db->fetch(
                "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'rol'"
            );
            $colType = $row['COLUMN_TYPE'] ?? "enum('admin','inspector')";
            // Reemplazar enum(...) por lista + nuevo
            preg_match("/enum\((.*)\)/i", $colType, $m);
            $vals = isset($m[1]) ? $m[1] : "'admin','inspector'";
            // Si no tiene, agregamos al final
            if (stripos($vals, "'inspector_camaras'") === false) {
                $vals = $vals . ",'inspector_camaras'";
            }
            $sql = "ALTER TABLE usuarios MODIFY rol ENUM($vals) NOT NULL";
            $db->execute($sql);
            $mensaje[] = "Actualizado ENUM usuarios.rol: +inspector_camaras";
        } else {
            $mensaje[] = "ENUM usuarios.rol ya contiene inspector_camaras";
        }
    } else {
        $mensaje[] = "La tabla usuarios no tiene columna rol";
    }

    // 2) Crear usuario inicial lucho/lamaquina con rol inspector_camaras si no existe
    $tieneUsuario = columnaExiste($db, 'usuarios', 'usuario');
    $tieneEmail = columnaExiste($db, 'usuarios', 'email');
    $tieneActivo = columnaExiste($db, 'usuarios', 'activo');
    $tieneNombre = columnaExiste($db, 'usuarios', 'nombre');
    $passwordPlano = 'lamaquina';
    $passwordHash = password_hash($passwordPlano, PASSWORD_BCRYPT);
    $usuarioValor = 'lucho';
    $emailValor = 'lucho@sigavv.com';

    $existe = false;
    if ($tieneUsuario) {
        $existe = (bool)$db->fetch("SELECT id FROM usuarios WHERE usuario = ?", [$usuarioValor]);
    } elseif ($tieneEmail) {
        $existe = (bool)$db->fetch("SELECT id FROM usuarios WHERE email = ?", [$emailValor]);
    }

    if (!$existe) {
        $cols = [];
        $vals = [];
        $params = [];
        if ($tieneUsuario) { $cols[] = 'usuario'; $vals[] = '?'; $params[] = $usuarioValor; }
        if ($tieneEmail)   { $cols[] = 'email';   $vals[] = '?'; $params[] = $emailValor; }
        if ($tieneNombre)  { $cols[] = 'nombre';  $vals[] = '?'; $params[] = 'Inspector Cámaras'; }
        $cols[] = 'password'; $vals[] = '?'; $params[] = $passwordHash;
        $cols[] = 'rol';      $vals[] = '?'; $params[] = 'inspector_camaras';
        if ($tieneActivo)  { $cols[] = 'activo';  $vals[] = '1'; }
        $sql = "INSERT INTO usuarios (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        $db->insert($sql, $params);
        $mensaje[] = "Usuario creado: lucho / " . $passwordPlano . " (rol inspector_camaras)";
    } else {
        // Asegurar rol correcto
        $db->execute("UPDATE usuarios SET rol = 'inspector_camaras' WHERE " . ($tieneUsuario ? "usuario = ?" : "email = ?"), [$tieneUsuario ? $usuarioValor : $emailValor]);
        $mensaje[] = "Usuario lucho existe. Rol ajustado a inspector_camaras";
    }

    // 3) Crear tablas del módulo cámaras
    $db->execute("CREATE TABLE IF NOT EXISTS camaras_inspecciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehiculo_id INT NOT NULL,
        inspector_id INT NOT NULL,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        estado_final ENUM('verde','amarillo','rojo') DEFAULT 'verde',
        observaciones TEXT NULL,
        foto_inicio_url VARCHAR(255) NULL,
        foto_fin_url VARCHAR(255) NULL,
        manipulado_conductor TINYINT(1) DEFAULT 0,
        tipo_novedad ENUM('cambio_memoria','cambio_fusible','mantenimiento','reparacion_cableado','otro') DEFAULT 'otro',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vehiculo(vehiculo_id),
        INDEX idx_inspector(inspector_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $mensaje[] = "Tabla camaras_inspecciones verificada/creada";

    $db->execute("CREATE TABLE IF NOT EXISTS camaras_inspeccion_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inspeccion_id INT NOT NULL,
        item_key VARCHAR(100) NOT NULL,
        estado ENUM('ok','malo') NOT NULL,
        observaciones TEXT NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_inspeccion(inspeccion_id),
        INDEX idx_item(item_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $mensaje[] = "Tabla camaras_inspeccion_detalle verificada/creada";

    $db->execute("CREATE TABLE IF NOT EXISTS camaras_evidencias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        detalle_id INT NOT NULL,
        archivo_url VARCHAR(255) NOT NULL,
        tipo ENUM('foto','video') DEFAULT 'foto',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_detalle(detalle_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $mensaje[] = "Tabla camaras_evidencias verificada/creada";

} catch (Exception $e) {
    $mensaje[] = 'Error en migración: ' . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Módulo Cámaras - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1 class="h4 mb-3">Instalación del Módulo de Cámaras</h1>
        <?php foreach ($mensaje as $m): ?>
            <div class="alert alert-info"><?= htmlspecialchars($m) ?></div>
        <?php endforeach; ?>
        <a class="btn btn-primary" href="../login.php">Ir al Login</a>
    </div>
</body>
</html>