-- =====================================================
-- SIGAV - Sistema de Gestión de Alistamiento Vehicular
-- Script de Creación de Base de Datos
-- =====================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS sigavv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigavv;

-- =====================================================
-- TABLA: usuarios
-- =====================================================
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'inspector') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: vehiculos
-- =====================================================
CREATE TABLE vehiculos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_interno VARCHAR(20) UNIQUE NOT NULL,
    placa VARCHAR(10) UNIQUE NOT NULL,
    propietario VARCHAR(100) NOT NULL,
    estado ENUM('activo', 'inactivo', 'mantenimiento') DEFAULT 'activo',
    qr_code TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: conductores
-- =====================================================
CREATE TABLE conductores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    telefono VARCHAR(15),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: documentos
-- =====================================================
CREATE TABLE documentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehiculo_id INT NOT NULL,
    tipo_documento ENUM('soat', 'tecnomecanica', 'tarjeta_operacion', 'extintor') NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: categorias_checklist
-- =====================================================
CREATE TABLE categorias_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    orden INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: items_checklist
-- =====================================================
CREATE TABLE items_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    es_vital BOOLEAN DEFAULT FALSE,
    orden INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias_checklist(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: alistamientos
-- =====================================================
CREATE TABLE alistamientos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehiculo_id INT NOT NULL,
    inspector_id INT NOT NULL,
    estado_final ENUM('verde', 'amarillo', 'rojo') NOT NULL,
    es_alistamiento_parcial BOOLEAN DEFAULT FALSE,
    fecha_alistamiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones_generales TEXT,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: detalle_alistamiento
-- =====================================================
CREATE TABLE detalle_alistamiento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    alistamiento_id INT NOT NULL,
    item_id INT NOT NULL,
    estado ENUM('ok', 'malo') NOT NULL,
    observaciones TEXT,
    foto_url VARCHAR(255),
    fecha_revision TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alistamiento_id) REFERENCES alistamientos(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items_checklist(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: vehiculos_detenidos
-- =====================================================
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
);

-- =====================================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- =====================================================
CREATE INDEX idx_vehiculos_numero_interno ON vehiculos(numero_interno);
CREATE INDEX idx_vehiculos_placa ON vehiculos(placa);
CREATE INDEX idx_documentos_vehiculo ON documentos(vehiculo_id);
CREATE INDEX idx_documentos_vencimiento ON documentos(fecha_vencimiento);
CREATE INDEX idx_alistamientos_vehiculo ON alistamientos(vehiculo_id);
CREATE INDEX idx_alistamientos_fecha ON alistamientos(fecha_alistamiento);
CREATE INDEX idx_vehiculos_detenidos_estado ON vehiculos_detenidos(estado);

-- =====================================================
-- DATOS DE PRUEBA
-- =====================================================

-- Insertar usuarios de prueba
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador Principal', 'admin@sigavv.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Inspector Juan Pérez', 'inspector1@sigavv.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inspector'),
('Inspector María García', 'inspector2@sigavv.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inspector');

-- Insertar vehículos de prueba
INSERT INTO vehiculos (numero_interno, placa, propietario, estado) VALUES
('BUS-001', 'ABC123', 'Transportes El Rápido S.A.S', 'activo'),
('BUS-002', 'DEF456', 'Transportes El Rápido S.A.S', 'activo'),
('BUS-003', 'GHI789', 'Cooperativa de Transportadores', 'activo'),
('BUS-004', 'JKL012', 'Transportes El Rápido S.A.S', 'mantenimiento'),
('BUS-005', 'MNO345', 'Flota Empresarial Ltda', 'activo');

-- Insertar conductores de prueba
INSERT INTO conductores (nombre, cedula, telefono) VALUES
('Carlos Rodríguez', '12345678', '3001234567'),
('Ana Martínez', '87654321', '3009876543'),
('Luis González', '11223344', '3005566778'),
('Patricia López', '44332211', '3007788990'),
('Miguel Torres', '55667788', '3002233445');

-- Insertar categorías de checklist
INSERT INTO categorias_checklist (nombre, descripcion, orden) VALUES
('Sistema de Frenos', 'Revisión completa del sistema de frenado', 1),
('Sistema Eléctrico', 'Verificación de luces y componentes eléctricos', 2),
('Motor y Fluidos', 'Control de niveles y estado del motor', 3),
('Dirección y Suspensión', 'Revisión de dirección y sistema de suspensión', 4),
('Carrocería y Seguridad', 'Verificación de elementos de seguridad', 5),
('Documentación', 'Verificación de documentos obligatorios', 6);

-- Insertar ítems de checklist
INSERT INTO items_checklist (categoria_id, nombre, descripcion, es_vital, orden) VALUES
-- Sistema de Frenos (VITALES)
(1, 'Pedal de freno', 'Verificar funcionamiento y recorrido del pedal', TRUE, 1),
(1, 'Freno de parqueo', 'Comprobar efectividad del freno de mano', TRUE, 2),
(1, 'Nivel de líquido de frenos', 'Verificar nivel en depósito', TRUE, 3),
(1, 'Pastillas/bandas de freno', 'Revisar desgaste', TRUE, 4),

-- Sistema Eléctrico (VITALES)
(2, 'Luces de freno', 'Verificar funcionamiento', TRUE, 1),
(2, 'Direccionales', 'Comprobar todas las direccionales', TRUE, 2),
(2, 'Luces principales', 'Verificar faros delanteros', TRUE, 3),
(2, 'Luces de reversa', 'Comprobar funcionamiento', TRUE, 4),
(2, 'Batería', 'Verificar estado y conexiones', FALSE, 5),
(2, 'Tablero de instrumentos', 'Verificar funcionamiento', FALSE, 6),

-- Motor y Fluidos (VITALES)
(3, 'Nivel de aceite', 'Verificar nivel con varilla', TRUE, 1),
(3, 'Nivel de refrigerante', 'Comprobar nivel en radiador', TRUE, 2),
(3, 'Nivel de combustible', 'Verificar cantidad suficiente', TRUE, 3),
(3, 'Fugas de fluidos', 'Revisar bajo el vehículo', TRUE, 4),
(3, 'Correas del motor', 'Verificar tensión y estado', FALSE, 5),

-- Dirección y Suspensión (VITALES)
(4, 'Volante y dirección', 'Verificar juego y alineación', TRUE, 1),
(4, 'Presión de llantas', 'Comprobar presión adecuada', TRUE, 2),
(4, 'Estado de llantas', 'Revisar desgaste y daños', TRUE, 3),
(4, 'Amortiguadores', 'Verificar funcionamiento', TRUE, 4),

-- Carrocería y Seguridad (MIXTO)
(5, 'Puertas principales', 'Verificar apertura y cierre', TRUE, 1),
(5, 'Puertas de emergencia', 'Comprobar funcionamiento', TRUE, 2),
(5, 'Ventanas', 'Verificar estado', FALSE, 3),
(5, 'Asientos', 'Revisar fijación y estado', FALSE, 4),
(5, 'Extintor', 'Verificar presencia y vigencia', FALSE, 5),
(5, 'Botiquín', 'Comprobar presencia y contenido', FALSE, 6),
(5, 'Martillos rompe vidrios', 'Verificar presencia', FALSE, 7),
(5, 'Cámaras de seguridad', 'Comprobar funcionamiento', FALSE, 8),

-- Documentación (NO VITALES)
(6, 'SOAT vigente', 'Verificar vigencia del SOAT', FALSE, 1),
(6, 'Tarjeta de operación', 'Comprobar vigencia', FALSE, 2),
(6, 'RTM vigente', 'Verificar revisión técnico mecánica', FALSE, 3);

-- Insertar documentos de prueba con diferentes estados de vigencia
INSERT INTO documentos (vehiculo_id, tipo_documento, fecha_vencimiento, estado_vigencia) VALUES
-- Vehículo 1 - Estados mixtos
(1, 'soat', '2025-06-15', 'verde'),
(1, 'rtm', '2024-12-30', 'amarillo'),
(1, 'tarjeta_operacion', '2025-03-20', 'azul'),
(1, 'extintor', '2024-11-15', 'rojo'),

-- Vehículo 2 - Todo en verde
(2, 'soat', '2025-08-10', 'verde'),
(2, 'rtm', '2025-05-25', 'verde'),
(2, 'tarjeta_operacion', '2025-07-30', 'verde'),
(2, 'extintor', '2025-04-12', 'verde'),

-- Vehículo 3 - Algunos vencidos
(3, 'soat', '2024-10-05', 'rojo'),
(3, 'rtm', '2025-02-14', 'azul'),
(3, 'tarjeta_operacion', '2024-11-30', 'rojo'),
(3, 'extintor', '2025-01-20', 'amarillo');

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS ÚTILES
-- =====================================================

-- Procedimiento para actualizar estado de vigencia de documentos
DELIMITER //
CREATE PROCEDURE ActualizarEstadoDocumentos()
BEGIN
    UPDATE documentos SET 
        estado_vigencia = CASE 
            WHEN fecha_vencimiento < CURDATE() THEN 'rojo'
            WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) THEN 'amarillo'
            WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 4 MONTH) THEN 'azul'
            ELSE 'verde'
        END;
END //
DELIMITER ;

-- Procedimiento para obtener estadísticas del dashboard
DELIMITER //
CREATE PROCEDURE ObtenerEstadisticasDashboard()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM vehiculos WHERE estado = 'activo') as vehiculos_activos,
        (SELECT COUNT(*) FROM vehiculos_detenidos WHERE estado = 'detenido') as vehiculos_detenidos,
        (SELECT COUNT(*) FROM documentos WHERE estado_vigencia = 'rojo') as documentos_vencidos,
        (SELECT COUNT(*) FROM alistamientos WHERE DATE(fecha_alistamiento) = CURDATE()) as alistamientos_hoy,
        (SELECT COUNT(*) FROM alistamientos WHERE estado_final = 'verde' AND DATE(fecha_alistamiento) = CURDATE()) as alistamientos_verdes_hoy,
        (SELECT COUNT(*) FROM alistamientos WHERE estado_final = 'amarillo' AND DATE(fecha_alistamiento) = CURDATE()) as alistamientos_amarillos_hoy,
        (SELECT COUNT(*) FROM alistamientos WHERE estado_final = 'rojo' AND DATE(fecha_alistamiento) = CURDATE()) as alistamientos_rojos_hoy;
END //
DELIMITER ;

-- =====================================================
-- TRIGGERS PARA AUTOMATIZACIÓN
-- =====================================================

-- Trigger para generar QR automáticamente al insertar vehículo
DELIMITER //
CREATE TRIGGER GenerarQRVehiculo 
AFTER INSERT ON vehiculos
FOR EACH ROW
BEGIN
    UPDATE vehiculos 
    SET qr_code = CONCAT('SIGAVV-', NEW.numero_interno, '-', NEW.id)
    WHERE id = NEW.id;
END //
DELIMITER ;

-- Trigger para crear registro de detención automática
DELIMITER //
CREATE TRIGGER DetenerVehiculoRojo
AFTER INSERT ON alistamientos
FOR EACH ROW
BEGIN
    IF NEW.estado_final = 'rojo' THEN
        INSERT INTO vehiculos_detenidos (vehiculo_id, alistamiento_id, motivo_detencion)
        SELECT NEW.vehiculo_id, NEW.id, 
               GROUP_CONCAT(ic.nombre SEPARATOR ', ')
        FROM detalle_alistamiento da
        JOIN items_checklist ic ON da.item_id = ic.id
        WHERE da.alistamiento_id = NEW.id 
        AND da.estado = 'malo' 
        AND ic.es_vital = TRUE;
        
        UPDATE vehiculos 
        SET estado = 'mantenimiento' 
        WHERE id = NEW.vehiculo_id;
    END IF;
END //
DELIMITER ;

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista para dashboard de vehículos
CREATE VIEW vista_vehiculos_dashboard AS
SELECT 
    v.id,
    v.numero_interno,
    v.placa,
    v.propietario,
    v.estado,
    COALESCE(a.estado_final, 'sin_revision') as ultimo_alistamiento,
    a.fecha_alistamiento as fecha_ultimo_alistamiento,
    vd.estado as estado_detencion
FROM vehiculos v
LEFT JOIN alistamientos a ON v.id = a.vehiculo_id 
    AND a.fecha_alistamiento = (
        SELECT MAX(fecha_alistamiento) 
        FROM alistamientos 
        WHERE vehiculo_id = v.id
    )
LEFT JOIN vehiculos_detenidos vd ON v.id = vd.vehiculo_id AND vd.estado = 'detenido';

-- Vista para documentos próximos a vencer
CREATE VIEW vista_documentos_alertas AS
SELECT 
    v.numero_interno,
    v.placa,
    d.tipo_documento,
    d.fecha_vencimiento,
    d.estado_vigencia,
    DATEDIFF(d.fecha_vencimiento, CURDATE()) as dias_restantes
FROM documentos d
JOIN vehiculos v ON d.vehiculo_id = v.id
WHERE d.estado_vigencia IN ('amarillo', 'rojo')
ORDER BY d.fecha_vencimiento ASC;

-- =====================================================
-- CONFIGURACIÓN FINAL
-- =====================================================

-- Ejecutar procedimiento inicial para actualizar estados
CALL ActualizarEstadoDocumentos();

-- Mensaje de confirmación
SELECT 'Base de datos SIGAVV creada exitosamente' as mensaje;