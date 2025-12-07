<?php
// Script de creación de credenciales para Inspector
// Uso: abrir en navegador: /setup/crear_usuario_inspector.php

require_once __DIR__ . '/../config/database.php';

function columnaExiste($db, $tabla, $columna) {
    $row = $db->fetch(
        "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$tabla, $columna]
    );
    return ((int)($row['c'] ?? 0)) > 0;
}

try {
    $db = getDB();

    // Determinar columnas disponibles
    $tieneUsuario = columnaExiste($db, 'usuarios', 'usuario');
    $tieneEmail = columnaExiste($db, 'usuarios', 'email');
    $tieneActivo = columnaExiste($db, 'usuarios', 'activo');
    $tieneRol = columnaExiste($db, 'usuarios', 'rol');
    $tieneNombre = columnaExiste($db, 'usuarios', 'nombre');
    $tienePassword = columnaExiste($db, 'usuarios', 'password');

    if (!$tieneRol || !$tienePassword) {
        throw new Exception('La tabla usuarios no tiene columnas requeridas (rol/password).');
    }

    $usuarioValor = 'inspector';
    $emailValor = 'inspector@sigavv.com';
    $nombreValor = 'Inspector SIGAV';
    $passwordPlano = '123456';
    $passwordHash = password_hash($passwordPlano, PASSWORD_BCRYPT);

    // Verificar si ya existe
    $existe = false;
    if ($tieneUsuario) {
        $existe = (bool)$db->fetch("SELECT id FROM usuarios WHERE usuario = ?", [$usuarioValor]);
    } elseif ($tieneEmail) {
        $existe = (bool)$db->fetch("SELECT id FROM usuarios WHERE email = ?", [$emailValor]);
    }

    if ($existe) {
        $mensaje = 'Ya existe un usuario inspector. Credenciales: ' . ($tieneUsuario ? $usuarioValor : $emailValor) . ' / ' . $passwordPlano;
    } else {
        // Construir INSERT dinámico
        $cols = [];
        $vals = [];
        $params = [];

        if ($tieneUsuario) { $cols[] = 'usuario'; $vals[] = '?'; $params[] = $usuarioValor; }
        if ($tieneEmail)   { $cols[] = 'email';   $vals[] = '?'; $params[] = $emailValor; }
        if ($tieneNombre)  { $cols[] = 'nombre';  $vals[] = '?'; $params[] = $nombreValor; }
        $cols[] = 'password'; $vals[] = '?'; $params[] = $passwordHash;
        $cols[] = 'rol';      $vals[] = '?'; $params[] = 'inspector';
        if ($tieneActivo)  { $cols[] = 'activo';  $vals[] = '1'; }

        $sql = "INSERT INTO usuarios (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
        $db->insert($sql, $params);
        $mensaje = 'Usuario inspector creado. Credenciales: ' . ($tieneUsuario ? $usuarioValor : $emailValor) . ' / ' . $passwordPlano;
    }

} catch (Exception $e) {
    $mensaje = 'Error creando usuario inspector: ' . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Crear Usuario Inspector - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { padding: 24px; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; } </style>
</head>
<body>
    <div class="container">
        <h1 class="h4 mb-3">Crear Usuario Inspector</h1>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
        <p class="text-muted">Use estas credenciales en el login. Si elige "Inspector" o su rol es inspector, será dirigido al módulo correspondiente.</p>
        <a class="btn btn-primary" href="../login.php">Ir al Login</a>
    </div>
</body>
</html>