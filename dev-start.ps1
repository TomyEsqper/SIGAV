# Script para iniciar el entorno de desarrollo con hot reload
Write-Host "🚀 Iniciando SIGAV Development Environment..." -ForegroundColor Green

# Detener contenedores existentes si los hay
Write-Host "🛑 Deteniendo contenedores existentes..." -ForegroundColor Yellow
docker-compose -f docker-compose.dev.yml down

# Limpiar volúmenes si es necesario (descomenta la línea siguiente si quieres resetear la DB)
# docker-compose -f docker-compose.dev.yml down -v

# Iniciar todos los servicios
Write-Host "🔥 Iniciando servicios con hot reload..." -ForegroundColor Cyan
docker-compose -f docker-compose.dev.yml up --build

Write-Host "✅ Entorno de desarrollo iniciado!" -ForegroundColor Green
Write-Host "📱 Frontend: http://localhost:4200" -ForegroundColor White
Write-Host "🔧 Backend API: http://localhost:5000" -ForegroundColor White
Write-Host "📊 Swagger: http://localhost:5000/swagger" -ForegroundColor White
Write-Host "🗄️ pgAdmin: http://localhost:5050 (admin@sigav.com / admin123)" -ForegroundColor White
Write-Host "💾 PostgreSQL: localhost:5432" -ForegroundColor White
Write-Host "🔴 Redis: localhost:6379" -ForegroundColor White
