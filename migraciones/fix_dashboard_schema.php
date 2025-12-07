<?php
// Migración para ajustar el esquema de BD a lo que requiere el dashboard
require_once __DIR__ . '/../config/database.php';

function out($msg) {
    echo $msg . (php_sapi_name() === 'cli' ? "\n" : '<br>');
}

try {
    $db = getDB();

    // Helpers
    $tableExists = function($name) use ($db) {
        $row = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
            [$name]
        );
        return (int)($row['cnt'] ?? 0) > 0;
    };

    $columnExists = function($table, $column) use ($db) {
        $row = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            [$table, $column]
        );
        return (int)($row['cnt'] ?? 0) > 0;
    };

    $indexExists = function($table, $index) use ($db) {
        $row = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
            [$table, $index]
        );
        return (int)($row['cnt'] ?? 0) > 0;
    };

    out('== Migración esquema dashboard ==');

    // conductores
    if (!$tableExists('conductores')) {
        out('Creando tabla conductores...');
        $db->execute(<<<SQL
            CREATE TABLE conductores (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nombre VARCHAR(100) NOT NULL,
                cedula VARCHAR(20) UNIQUE NOT NULL,
                telefono VARCHAR(15),
                activo BOOLEAN DEFAULT TRUE,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        SQL);
    } else {
        out('Tabla conductores ya existe.');
    }

    // vehiculos_detenidos
    if (!$tableExists('vehiculos_detenidos')) {
        out('Creando tabla vehiculos_detenidos...');
        $db->execute(<<<SQL
            CREATE TABLE vehiculos_detenidos (
                id INT PRIMARY KEY AUTO_INCREMENT,
                vehiculo_id INT NOT NULL,
                alistamiento_id INT NOT NULL,
                motivo_detencion TEXT NOT NULL,
                fecha_detencion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_liberacion TIMESTAMP NULL,
                estado ENUM('detenido', 'liberado') DEFAULT 'detenido',
                FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
                FOREIGN KEY (alistamiento_id) REFERENCES alistamientos(id) ON DELETE CASCADE
            )
        SQL);
    } else {
        out('Tabla vehiculos_detenidos ya existe.');
        // asegurar columnas clave
        if (!$columnExists('vehiculos_detenidos', 'fecha_liberacion')) {
            out('Agregando columna fecha_liberacion a vehiculos_detenidos...');
            $db->execute("ALTER TABLE vehiculos_detenidos ADD COLUMN fecha_liberacion TIMESTAMP NULL");
        }
        if (!$columnExists('vehiculos_detenidos', 'estado')) {
            out('Agregando columna estado a vehiculos_detenidos...');
            $db->execute("ALTER TABLE vehiculos_detenidos ADD COLUMN estado ENUM('detenido','liberado') DEFAULT 'detenido'");
        }
    }

    if (!$indexExists('vehiculos_detenidos', 'idx_vehiculos_detenidos_estado')) {
        out('Creando índice idx_vehiculos_detenidos_estado...');
        $db->execute("CREATE INDEX idx_vehiculos_detenidos_estado ON vehiculos_detenidos(estado)");
    }

    // alistamientos: asegurar columnas usadas
    if ($tableExists('alistamientos')) {
        if (!$columnExists('alistamientos', 'estado_final')) {
            out('Agregando columna estado_final a alistamientos...');
            $db->execute("ALTER TABLE alistamientos ADD COLUMN estado_final ENUM('verde','amarillo','rojo') NOT NULL DEFAULT 'verde'");
        }
        if (!$columnExists('alistamientos', 'fecha_alistamiento')) {
            out('Agregando columna fecha_alistamiento a alistamientos...');
            $db->execute("ALTER TABLE alistamientos ADD COLUMN fecha_alistamiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
        if (!$columnExists('alistamientos', 'es_alistamiento_parcial')) {
            out('Agregando columna es_alistamiento_parcial a alistamientos...');
            $db->execute("ALTER TABLE alistamientos ADD COLUMN es_alistamiento_parcial BOOLEAN NOT NULL DEFAULT 0");
        }
        if (!$indexExists('alistamientos', 'idx_alistamientos_fecha')) {
            out('Creando índice idx_alistamientos_fecha...');
            $db->execute("CREATE INDEX idx_alistamientos_fecha ON alistamientos(fecha_alistamiento)");
        }
    } else {
        out('ADVERTENCIA: La tabla alistamientos no existe. El dashboard mostrará 0 en métricas de alistamientos.');
    }

    // documentos: normalizar enum de tipo_documento
    if ($tableExists('documentos')) {
        out('Normalizando enum tipo_documento en documentos...');
        // Incluir ambas variantes para compatibilidad y los tipos usados en la app
        $db->execute("ALTER TABLE documentos MODIFY COLUMN tipo_documento ENUM('soat','tecnomecanica','tecnicomecanica','rtm','tarjeta_operacion','extintor') NOT NULL");

        if (!$indexExists('documentos', 'idx_documentos_vencimiento')) {
            out('Creando índice idx_documentos_vencimiento...');
            $db->execute("CREATE INDEX idx_documentos_vencimiento ON documentos(fecha_vencimiento)");
        }
    } else {
        out('ADVERTENCIA: La tabla documentos no existe. El dashboard mostrará 0 en métricas de documentos.');
    }

    // =============================
    // Checklist: categorías e ítems
    // =============================
    if (!$tableExists('categorias_checklist')) {
        out('Creando tabla categorias_checklist...');
        $db->execute(<<<SQL
            CREATE TABLE categorias_checklist (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nombre VARCHAR(100) NOT NULL,
                descripcion TEXT,
                orden INT NOT NULL,
                activo BOOLEAN DEFAULT TRUE,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
    } else {
        out('Tabla categorias_checklist ya existe.');
    }

    if (!$tableExists('items_checklist')) {
        out('Creando tabla items_checklist...');
        $db->execute(<<<SQL
            CREATE TABLE items_checklist (
                id INT PRIMARY KEY AUTO_INCREMENT,
                categoria_id INT NOT NULL,
                descripcion TEXT,
                es_vital BOOLEAN DEFAULT FALSE,
                orden INT NOT NULL,
                activo BOOLEAN DEFAULT TRUE,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (categoria_id) REFERENCES categorias_checklist(id) ON DELETE CASCADE
            )
        SQL);
    } else {
        out('Tabla items_checklist ya existe.');
    }

    // =============================
    // Detalle de alistamiento
    // =============================
    if (!$tableExists('detalle_alistamiento')) {
        out('Creando tabla detalle_alistamiento...');
        $db->execute(<<<SQL
            CREATE TABLE detalle_alistamiento (
                id INT PRIMARY KEY AUTO_INCREMENT,
                alistamiento_id INT NOT NULL,
                item_id INT NOT NULL,
                estado ENUM('ok','malo') NOT NULL,
                observaciones TEXT,
                foto_url VARCHAR(255),
                fecha_revision TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (alistamiento_id) REFERENCES alistamientos(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES items_checklist(id) ON DELETE CASCADE
            )
        SQL);
    } else {
        out('Tabla detalle_alistamiento ya existe.');
        if (!$columnExists('detalle_alistamiento', 'estado')) {
            out('Agregando columna estado a detalle_alistamiento...');
            $db->execute("ALTER TABLE detalle_alistamiento ADD COLUMN estado ENUM('ok','malo') NOT NULL DEFAULT 'ok'");
        }
        if (!$columnExists('detalle_alistamiento', 'foto_url')) {
            out('Agregando columna foto_url a detalle_alistamiento...');
            $db->execute("ALTER TABLE detalle_alistamiento ADD COLUMN foto_url VARCHAR(255) NULL");
        }
    }

    out('Migración completada.');
} catch (Exception $e) {
    http_response_code(500);
    out('ERROR en migración: ' . $e->getMessage());
}

?>