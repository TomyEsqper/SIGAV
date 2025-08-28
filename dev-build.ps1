# Script para desarrollo automático de SIGAV Frontend
Write-Host "🚀 Iniciando build automático..." -ForegroundColor Green

# Navegar al directorio frontend
Set-Location frontend

# Limpiar cache de npm
Write-Host "🧹 Limpiando cache..." -ForegroundColor Yellow
npm cache clean --force

# Instalar dependencias si es necesario
if (!(Test-Path "node_modules")) {
    Write-Host "📦 Instalando dependencias..." -ForegroundColor Yellow
    npm install
}

# Build del proyecto
Write-Host "🔨 Compilando Angular..." -ForegroundColor Yellow
npm run build

# Volver al directorio raíz
Set-Location ..

# Build de Docker
Write-Host "🐳 Construyendo imagen Docker..." -ForegroundColor Yellow
docker build -f Dockerfile.frontend -t sigav-frontend .

# Reiniciar contenedor
Write-Host "🔄 Reiniciando contenedor..." -ForegroundColor Yellow
docker-compose restart frontend

Write-Host "✅ ¡Listo! Accede a http://localhost:4200/login" -ForegroundColor Green
Write-Host "💡 Para desarrollo rápido, usa: cd frontend && npm start" -ForegroundColor Cyan
