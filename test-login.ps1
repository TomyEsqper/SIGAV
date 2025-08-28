# Script para probar el endpoint de login
Write-Host "🚀 Probando el sistema de login de SIGAV..." -ForegroundColor Green

# Datos de prueba
$loginData = @{
    tenant = "empresa demo"
    usernameOrEmail = "admin@demo.local"
    password = "admin123"
} | ConvertTo-Json

Write-Host "📤 Enviando request de login..." -ForegroundColor Yellow
Write-Host "Datos: $loginData" -ForegroundColor Gray

# Intentar hacer la petición
try {
    $response = Invoke-RestMethod -Uri "http://localhost:5000/api/auth/login" -Method POST -Body $loginData -ContentType "application/json"
    Write-Host "✅ Login exitoso!" -ForegroundColor Green
    Write-Host "Token: $($response.accessToken)" -ForegroundColor Cyan
    Write-Host "Usuario: $($response.user.name)" -ForegroundColor Cyan
    Write-Host "Tenant: $($response.tenant)" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Error en el login:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $statusCode = $_.Exception.Response.StatusCode
        Write-Host "Status Code: $statusCode" -ForegroundColor Red
    }
}

Write-Host "`n🎯 Para probar el frontend, ve a: http://localhost:4200" -ForegroundColor Magenta
