# Script para generar el hash de la contraseña
$password = "admin123"
Write-Host "Generando hash para contraseña: $password" -ForegroundColor Green

# Usar SHA256 como en el código del backend
$sha256 = [System.Security.Cryptography.SHA256]::Create()
$passwordBytes = [System.Text.Encoding]::UTF8.GetBytes($password)
$hashBytes = $sha256.ComputeHash($passwordBytes)
$hash = [Convert]::ToBase64String($hashBytes)

Write-Host "Hash generado: $hash" -ForegroundColor Cyan
Write-Host "Hash en la BD: YUF6MiZwPLC7QlsWmO359dMgV91owyTjyrN5hnig5oI=" -ForegroundColor Yellow

# Verificar si coinciden
if ($hash -eq "YUF6MiZwPLC7QlsWmO359dMgV91owyTjyrN5hnig5oI=") {
    Write-Host "✅ Los hashes coinciden!" -ForegroundColor Green
} else {
    Write-Host "❌ Los hashes NO coinciden" -ForegroundColor Red
}
