# Script para probar diferentes contraseñas
$targetHash = "YUF6MiZwPLC7QlsWmO359dMgV91owyTjyrN5hnig5oI="
$passwords = @("admin", "admin123", "password", "123456", "sigav", "demo", "test")

Write-Host "Probando contraseñas para encontrar el hash: $targetHash" -ForegroundColor Green

foreach ($password in $passwords) {
    $sha256 = [System.Security.Cryptography.SHA256]::Create()
    $passwordBytes = [System.Text.Encoding]::UTF8.GetBytes($password)
    $hashBytes = $sha256.ComputeHash($passwordBytes)
    $hash = [Convert]::ToBase64String($hashBytes)
    
    if ($hash -eq $targetHash) {
        Write-Host "✅ ¡Encontrada! Contraseña: '$password'" -ForegroundColor Green
        break
    } else {
        Write-Host "❌ '$password' -> $hash" -ForegroundColor Red
    }
}
