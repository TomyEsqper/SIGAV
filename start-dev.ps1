#!/usr/bin/env pwsh

# Script para arrancar el entorno de desarrollo SIGAV
# Uso: .\start-dev.ps1

Write-Host "🚀 Iniciando entorno de desarrollo SIGAV..." -ForegroundColor Green

# Verificar que Docker esté ejecutándose
try {
    docker version | Out-Null
} catch {
    Write-Host "❌ Docker no está ejecutándose. Por favor, inicia Docker Desktop." -ForegroundColor Red
    exit 1
}

# Verificar que docker-compose esté disponible
try {
    docker compose version | Out-Null
} catch {
    Write-Host "❌ Docker Compose no está disponible." -ForegroundColor Red
    exit 1
}

# Parar contenedores existentes si los hay
Write-Host "🛑 Deteniendo contenedores existentes..." -ForegroundColor Yellow
docker compose -f docker-compose.dev.yml down

# Construir las imágenes si no existen o han cambiado
Write-Host "🔨 Construyendo imágenes..." -ForegroundColor Yellow
docker compose -f docker-compose.dev.yml build

# Arrancar los servicios con watch mode
Write-Host "▶️  Arrancando servicios con hot-reload..." -ForegroundColor Green
Write-Host "📝 Los cambios en el código se reflejarán automáticamente" -ForegroundColor Cyan
Write-Host "" -ForegroundColor White

# Mostrar información de los puertos
Write-Host "🌐 Servicios disponibles:" -ForegroundColor Cyan
Write-Host "   • Frontend (Angular): http://localhost:4200" -ForegroundColor White
Write-Host "   • Backend API (.NET): http://localhost:5000" -ForegroundColor White
Write-Host "   • Swagger UI: http://localhost:5000/swagger" -ForegroundColor White
Write-Host "   • PostgreSQL: localhost:5432" -ForegroundColor White
Write-Host "   • Redis: localhost:6379" -ForegroundColor White
Write-Host "" -ForegroundColor White

Write-Host "💡 Para detener los servicios, presiona Ctrl+C" -ForegroundColor Yellow
Write-Host "💡 Para ver logs específicos: docker compose -f docker-compose.dev.yml logs -f [servicio]" -ForegroundColor Yellow

# Arrancar con watch mode
docker compose -f docker-compose.dev.yml up --watch
