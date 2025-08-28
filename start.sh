#!/bin/bash

# SIGAV - Sistema de Alistamiento de Busetas
# Script de inicio para Unix/Linux/macOS

echo "🚀 SIGAV - Sistema de Alistamiento de Busetas"
echo "==============================================="

# Verificar si Docker está ejecutándose
echo "🔍 Verificando Docker..."
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker no está ejecutándose. Por favor, inicia Docker."
    exit 1
fi
echo "✅ Docker está ejecutándose"

# Verificar si Docker Compose está disponible
echo "🔍 Verificando Docker Compose..."
if ! docker compose version > /dev/null 2>&1; then
    echo "❌ Docker Compose no está disponible."
    exit 1
fi
echo "✅ Docker Compose está disponible"

# Detener servicios existentes si los hay
echo "🛑 Deteniendo servicios existentes..."
docker compose down 2>/dev/null

# Construir y ejecutar servicios
echo "🔨 Construyendo y ejecutando servicios..."
docker compose up -d --build

# Esperar a que los servicios estén listos
echo "⏳ Esperando a que los servicios estén listos..."
sleep 30

# Verificar estado de los servicios
echo "🔍 Verificando estado de los servicios..."
docker compose ps

echo ""
echo "🎉 ¡SIGAV está ejecutándose!"
echo ""
echo "📱 Frontend: http://localhost:4200"
echo "🔌 API Swagger: http://localhost:5000/swagger"
echo "🗄️ PostgreSQL: localhost:5432"
echo "🔴 Redis: localhost:6379"
echo "📊 pgAdmin: http://localhost:5050"
echo ""
echo "🔑 Credenciales de Demo:"
echo "   Admin: admin@sigav.local / Admin_123!"
echo "   Inspector: inspector@sigav.local / Inspector_123!"
echo "   Mecánico: mecanico@sigav.local / Mecanico_123!"
echo ""
echo "📋 Comandos útiles:"
echo "   Ver logs: docker compose logs -f"
echo "   Detener: docker compose down"
echo "   Reiniciar: docker compose restart"
echo ""

# Intentar abrir el navegador (solo en sistemas con GUI)
if command -v xdg-open > /dev/null; then
    echo "🌐 Abriendo frontend en el navegador..."
    xdg-open "http://localhost:4200" 2>/dev/null &
elif command -v open > /dev/null; then
    echo "🌐 Abriendo frontend en el navegador..."
    open "http://localhost:4200" 2>/dev/null &
fi











