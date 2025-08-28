# SIGAV - Sistema de Alistamiento de Busetas
# Script de inicio para Windows PowerShell

Write-Host "🚀 SIGAV - Sistema de Alistamiento de Busetas" -ForegroundColor Green
Write-Host "===============================================" -ForegroundColor Green

# Verificar si Docker está ejecutándose
Write-Host "🔍 Verificando Docker..." -ForegroundColor Yellow
try {
    docker version | Out-Null
    Write-Host "✅ Docker está ejecutándose" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker no está ejecutándose. Por favor, inicia Docker Desktop." -ForegroundColor Red
    exit 1
}

# Verificar si Docker Compose está disponible
Write-Host "🔍 Verificando Docker Compose..." -ForegroundColor Yellow
try {
    docker compose version | Out-Null
    Write-Host "✅ Docker Compose está disponible" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker Compose no está disponible." -ForegroundColor Red
    exit 1
}

# Detener servicios existentes si los hay
Write-Host "🛑 Deteniendo servicios existentes..." -ForegroundColor Yellow
docker compose down 2>$null

# Construir y ejecutar servicios
Write-Host "🔨 Construyendo y ejecutando servicios..." -ForegroundColor Yellow
docker compose up -d --build

# Esperar a que los servicios estén listos
Write-Host "⏳ Esperando a que los servicios estén listos..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

# Verificar estado de los servicios
Write-Host "🔍 Verificando estado de los servicios..." -ForegroundColor Yellow
docker compose ps

Write-Host ""
Write-Host "🎉 ¡SIGAV está ejecutándose!" -ForegroundColor Green
Write-Host ""
Write-Host "📱 Frontend: http://localhost:4200" -ForegroundColor Cyan
Write-Host "🔌 API Swagger: http://localhost:5000/swagger" -ForegroundColor Cyan
Write-Host "🗄️ PostgreSQL: localhost:5432" -ForegroundColor Cyan
Write-Host "🔴 Redis: localhost:6379" -ForegroundColor Cyan
Write-Host "📊 pgAdmin: http://localhost:5050" -ForegroundColor Cyan
Write-Host ""
Write-Host "🔑 Credenciales de Demo:" -ForegroundColor Yellow
Write-Host "   Admin: admin@sigav.local / Admin_123!" -ForegroundColor White
Write-Host "   Inspector: inspector@sigav.local / Inspector_123!" -ForegroundColor White
Write-Host "   Mecánico: mecanico@sigav.local / Mecanico_123!" -ForegroundColor White
Write-Host ""
Write-Host "📋 Comandos útiles:" -ForegroundColor Yellow
Write-Host "   Ver logs: docker compose logs -f" -ForegroundColor White
Write-Host "   Detener: docker compose down" -ForegroundColor White
Write-Host "   Reiniciar: docker compose restart" -ForegroundColor White
Write-Host ""
Write-Host "Presiona cualquier tecla para abrir el frontend en tu navegador..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

# Abrir el frontend en el navegador
Start-Process "http://localhost:4200"











