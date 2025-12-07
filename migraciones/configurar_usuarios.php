<?php
/**
 * Configurar usuarios permitidos para despliegue
 * Ejecutar en navegador: /migraciones/configurar_usuarios.php
 */
require_once __DIR__ . '/../config/database.php';

function out($msg) {
    echo $msg . (php_sapi_name() === 'cli' ? "\n" : '<br>');
}

function columnaExiste($db, $tabla, $columna) {
    $row = $db->fetch(
        "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$tabla, $columna]
    );
    return ((int)($row['c'] ?? 0)) > 0;
}

function obtenerEnumValores($db, $tabla, $columna) {
    $row = $db->fetch(
        "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$tabla, $columna]
    );
    $colType = $row['COLUMN_TYPE'] ?? '';
    if (preg_match("/enum\((.*)\)/i", $colType, $m)) {
        return $m[1];
    }
    return "'admin','inspector'"; // por defecto
}

try {
    $db = getDB();
    out('== Configuración de usuarios para despliegue ==');

    // Asegurar columnas requeridas
    $tieneUsuario = columnaExiste($db, 'usuarios', 'usuario');
    $tieneEmail   = columnaExiste($db, 'usuarios', 'email');
    $tieneActivo  = columnaExiste($db, 'usuarios', 'activo');
    $tieneRol     = columnaExiste($db, 'usuarios', 'rol');
    $tieneNombre  = columnaExiste($db, 'usuarios', 'nombre');
    $tienePassword= columnaExiste($db, 'usuarios', 'password');

    if (!$tienePassword || !$tieneRol || !$tieneNombre) {
        throw new Exception('La tabla usuarios no tiene columnas requeridas (nombre/password/rol).');
    }

    if (!$tieneUsuario) {
        out('Agregando columna usuarios.usuario...');
        // Permitir NULL temporalmente por seguridad; los nuevos registros se llenan
        $db->execute("ALTER TABLE usuarios ADD COLUMN usuario VARCHAR(100) UNIQUE NULL AFTER nombre");
        $tieneUsuario = true;
    } else {
        out('Columna usuarios.usuario ya existe.');
    }

    if (!$tieneActivo) {
        out('Agregando columna usuarios.activo...');
        $db->execute("ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER rol");
        $tieneActivo = true;
    }

    // Asegurar valores de rol requeridos
    if ($tieneRol) {
        $vals = obtenerEnumValores($db, 'usuarios', 'rol');
        $necesarios = ["'admin'", "'inspector'", "'inspector_camaras'"];
        foreach ($necesarios as $v) {
            if (stripos($vals, $v) === false) {
                $vals .= ",$v";
            }
        }
        $sql = "ALTER TABLE usuarios MODIFY rol ENUM($vals) NOT NULL";
        $db->execute($sql);
        out('Actualizado ENUM usuarios.rol con valores requeridos.');
    }

    // Usuarios deseados
    $usuariosDeseados = [
        [
            'usuario'  => 'admin',
            'email'    => 'admin@sigavv.com',
            'nombre'   => 'Administrativo',
            'password' => 'cotrautol2025',
            'rol'      => 'admin',
        ],
        [
            'usuario'  => 'talentohumano',
            'email'    => 'talentohumano@sigavv.com',
            'nombre'   => 'Talento Humano',
            'password' => 'cotrautol2025',
            'rol'      => 'admin',
        ],
        [
            'usuario'  => 'lucho',
            'email'    => 'lucho@sigavv.com',
            'nombre'   => 'Inspector Cámaras',
            'password' => 'lamaquina',
            'rol'      => 'inspector_camaras',
        ],
        [
            'usuario'  => 'camaras',
            'email'    => 'camaras@sigavv.com',
            'nombre'   => 'Inspector Cámaras',
            'password' => 'inspectorcamaras',
            'rol'      => 'inspector_camaras',
        ],
        [
            'usuario'  => 'inspector1',
            'email'    => 'inspector1@sigavv.com',
            'nombre'   => 'Inspector 1',
            'password' => 'inspector2025',
            'rol'      => 'inspector',
        ],
        [
            'usuario'  => 'inspector2',
            'email'    => 'inspector2@sigavv.com',
            'nombre'   => 'Inspector 2',
            'password' => 'inspector2025',
            'rol'      => 'inspector',
        ],
        [
            'usuario'  => 'inspector3',
            'email'    => 'inspector3@sigavv.com',
            'nombre'   => 'Inspector 3',
            'password' => 'inspector2025',
            'rol'      => 'inspector',
        ],
        [
            'usuario'  => 'inspector4',
            'email'    => 'inspector4@sigavv.com',
            'nombre'   => 'Inspector 4',
            'password' => 'inspector2025',
            'rol'      => 'inspector',
        ],
        [
            'usuario'  => 'inspector5',
            'email'    => 'inspector5@sigavv.com',
            'nombre'   => 'Inspector 5',
            'password' => 'inspector2025',
            'rol'      => 'inspector',
        ],
    ];

    $db->beginTransaction();

    $db->execute("SET FOREIGN_KEY_CHECKS=0");
    $db->execute("DELETE FROM usuarios");
    $db->execute("SET FOREIGN_KEY_CHECKS=1");
    out('Usuarios antiguos eliminados.');

    // Insertar/actualizar usuarios deseados
    foreach ($usuariosDeseados as $u) {
        $hash = password_hash($u['password'], PASSWORD_BCRYPT);
        $existe = false;
        if ($tieneUsuario) {
            $existe = (bool)$db->fetch("SELECT id FROM usuarios WHERE usuario = ?", [$u['usuario']]);
        } elseif ($tieneEmail) {
            $existe = (bool)$db->fetch("SELECT id FROM usuarios WHERE email = ?", [$u['email']]);
        }

        if ($existe) {
            $sql = "UPDATE usuarios SET nombre = ?, password = ?, rol = ?, activo = 1" .
                   ($tieneUsuario ? ", usuario = ?" : "") .
                   ($tieneEmail ? ", email = ?" : "") .
                   " WHERE " . ($tieneUsuario ? "usuario = ?" : "email = ?");
            $params = [$u['nombre'], $hash, $u['rol']];
            if ($tieneUsuario) { $params[] = $u['usuario']; }
            if ($tieneEmail)   { $params[] = $u['email']; }
            $params[] = $tieneUsuario ? $u['usuario'] : $u['email'];
            $db->execute($sql, $params);
            out('Actualizado usuario: ' . htmlspecialchars($u['usuario']));
        } else {
            $cols = ['nombre', 'password', 'rol'];
            $vals = ['?', '?', '?'];
            $params = [$u['nombre'], $hash, $u['rol']];
            if ($tieneUsuario) { $cols[] = 'usuario'; $vals[] = '?'; $params[] = $u['usuario']; }
            if ($tieneEmail)   { $cols[] = 'email';   $vals[] = '?'; $params[] = $u['email']; }
            if ($tieneActivo)  { $cols[] = 'activo';  $vals[] = '1'; }
            $sql = "INSERT INTO usuarios (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
            $db->insert($sql, $params);
            out('Creado usuario: ' . htmlspecialchars($u['usuario']));
        }
    }

    $db->commit();
    out('✓ Configuración completada.');
    out('Puede iniciar sesión en /login.php con las credenciales especificadas.');
} catch (Exception $e) {
    if (isset($db)) { $db->rollback(); }
    http_response_code(500);
    out('ERROR: ' . $e->getMessage());
}

?>
