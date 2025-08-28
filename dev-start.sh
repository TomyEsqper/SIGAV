#!/bin/bash

# Script para iniciar el entorno de desarrollo con hot reload
echo "🚀 Iniciando SIGAV Development Environment..."

# Detener contenedores existentes si los hay
echo "🛑 Deteniendo contenedores existentes..."
docker-compose -f docker-compose.dev.yml down

# Limpiar volúmenes si es necesario (descomenta la línea siguiente si quieres resetear la DB)
# docker-compose -f docker-compose.dev.yml down -v

# Iniciar todos los servicios
echo "🔥 Iniciando servicios con hot reload..."
docker-compose -f docker-compose.dev.yml up --build

echo "✅ Entorno de desarrollo iniciado!"
echo "📱 Frontend: http://localhost:4200"
echo "🔧 Backend API: http://localhost:5000"
echo "📊 Swagger: http://localhost:5000/swagger"
echo "🗄️ pgAdmin: http://localhost:5050 (admin@sigav.com / admin123)"
echo "💾 PostgreSQL: localhost:5432"
echo "🔴 Redis: localhost:6379"
