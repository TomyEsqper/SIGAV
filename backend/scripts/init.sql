-- Script de inicialización para la base de datos SIGAV
-- Este archivo se ejecuta automáticamente cuando se crea el contenedor de PostgreSQL por primera vez

-- Crear extensiones útiles para desarrollo
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Crear índices adicionales si es necesario
-- CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(email);
-- CREATE INDEX IF NOT EXISTS idx_busetas_placa ON busetas(placa);

-- Insertar datos de prueba (opcional)
-- INSERT INTO empresas (nombre, direccion, telefono) VALUES 
-- ('Empresa Demo', 'Dirección Demo', '123456789') 
-- ON CONFLICT DO NOTHING;

-- Comentario: Los datos reales se crearán a través de las migraciones de Entity Framework
