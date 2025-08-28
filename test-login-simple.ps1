# Script simple para probar el login
Write-Host "🚀 Probando login..." -ForegroundColor Green

$body = @{
    tenant = "empresa demo"
    usernameOrEmail = "admin@demo.local"
    password = "admin123"
} | ConvertTo-Json

Write-Host "Body: $body" -ForegroundColor Yellow

try {
    $response = Invoke-RestMethod -Uri "http://localhost:5000/api/auth/login" -Method POST -Body $body -ContentType "application/json"
    Write-Host "✅ Login exitoso!" -ForegroundColor Green
    Write-Host "Token: $($response.accessToken)" -ForegroundColor Cyan
    Write-Host "Usuario: $($response.user.name)" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Error: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $statusCode = $_.Exception.Response.StatusCode
        Write-Host "Status Code: $statusCode" -ForegroundColor Red
        
        $responseBody = $_.ErrorDetails.Message
        Write-Host "Response Body: $responseBody" -ForegroundColor Red
    }
}
