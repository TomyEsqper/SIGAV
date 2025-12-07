<?php
/**
 * Instalación del Módulo de Evasión (rol + tablas + usuario inicial)
 * Ejecutar en navegador: /migraciones/instalar_modulo_evasion.php
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

    // 1) Ampliar ENUM de usuarios.rol para incluir revision_memorias
    if (columnaExiste($db, 'usuarios', 'rol')) {
        $tiene = enumContieneValor($db, 'usuarios', 'rol', 'revision_memorias');
        if (!$tiene) {
            // Detectar valores actuales del ENUM y agregar el nuevo
            $row = $db->fetch(
                "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'rol'"
            );
            $colType = $row['COLUMN_TYPE'] ?? "enum('admin','inspector')";
            preg_match("/enum\((.*)\)/i", $colType, $m);
            $vals = isset($m[1]) ? $m[1] : "'admin','inspector'";
            if (stripos($vals, "'revision_memorias'") === false) {
                $vals = $vals . ",'revision_memorias'";
            }
            $sql = "ALTER TABLE usuarios MODIFY rol ENUM($vals) NOT NULL";
            $db->execute($sql);
            $mensaje[] = "Actualizado ENUM usuarios.rol: +revision_memorias";
        } else {
            $mensaje[] = "ENUM usuarios.rol ya contiene revision_memorias";
        }
    } else {
        $mensaje[] = "La tabla usuarios no tiene columna rol";
    }

    // 2) Crear usuario inicial revision/123456 con rol revision_memorias si no existe
    $tieneUsuario = columnaExiste($db, 'usuarios', 'usuario');
    $tieneEmail = columnaExiste($db, 'usuarios', 'email');
    $tieneActivo = columnaExiste($db, 'usuarios', 'activo');
    $tieneNombre = columnaExiste($db, 'usuarios', 'nombre');
    $passwordPlano = '123456';
    $passwordHash = password_hash($passwordPlano, PASSWORD_BCRYPT);
    $usuarioValor = 'revision';
    $emailValor = 'revision@sigavv.com';

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
        if ($tieneNombre)  { $cols[] = 'nombre';  $vals[] = '?'; $params[] = 'Revisión de Memorias'; }
        $cols[] = 'password'; $vals[] = '?'; $params[] = $passwordHash;
        $cols[] = 'rol';      $vals[] = '?'; $params[] = 'revision_memorias';
        if ($tieneActivo)  { $cols[] = 'activo';  $vals[] = '1'; }
        $sql = "INSERT INTO usuarios (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        $db->insert($sql, $params);
        $mensaje[] = "Usuario creado: " . ($tieneUsuario ? $usuarioValor : $emailValor) . " / " . $passwordPlano . " (rol revision_memorias)";
    } else {
        // Asegurar rol correcto
        if ($tieneUsuario) {
            $db->execute("UPDATE usuarios SET rol = 'revision_memorias' WHERE usuario = ?", [$usuarioValor]);
        } elseif ($tieneEmail) {
            $db->execute("UPDATE usuarios SET rol = 'revision_memorias' WHERE email = ?", [$emailValor]);
        }
        $mensaje[] = "Usuario de revisión existe. Rol ajustado a revision_memorias";
    }

    // 3) Crear tablas del módulo Evasión (si no existen)
    $db->execute("CREATE TABLE IF NOT EXISTS evasion_inspecciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero_informe VARCHAR(32) NOT NULL,
        vehiculo_id INT NOT NULL,
        conductor_id INT NULL,
        ruta VARCHAR(120) NULL,
        dias_revisados VARCHAR(120) NULL,
        fecha_revision DATE NOT NULL,
        fecha_reporte DATETIME NOT NULL,
        usuario_id INT NOT NULL,
        total_pasajeros INT DEFAULT 0,
        observaciones TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $mensaje[] = "Tabla evasion_inspecciones verificada/creada";

    $db->execute("CREATE TABLE IF NOT EXISTS evasion_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inspeccion_id INT NOT NULL,
        grabacion VARCHAR(64) NOT NULL,
        hora TIME NULL,
        pasajeros INT NOT NULL,
        archivo_url VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (inspeccion_id) REFERENCES evasion_inspecciones(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $mensaje[] = "Tabla evasion_detalle verificada/creada";

} catch (Exception $e) {
    $mensaje[] = 'Error en migración: ' . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Módulo Evasión - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1 class="h4 mb-3">Instalación del Módulo de Evasión</h1>
        <?php foreach ($mensaje as $m): ?>
            <div class="alert alert-info"><?= htmlspecialchars($m) ?></div>
        <?php endforeach; ?>
        <a class="btn btn-primary" href="../login.php">Ir al Login</a>
    </div>
</body>
</html>