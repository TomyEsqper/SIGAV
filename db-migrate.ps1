#!/usr/bin/env pwsh

# Script para gestionar migraciones de la base de datos SIGAV
# Uso: .\db-migrate.ps1 [comando]

param(
    [Parameter(Position=0)]
    [ValidateSet("update", "add", "remove", "list", "reset")]
    [string]$Command = "update",
    
    [Parameter(Position=1)]
    [string]$MigrationName = ""
)

Write-Host "🗄️  Gestión de migraciones de base de datos SIGAV" -ForegroundColor Green

# Verificar que los servicios estén ejecutándose
try {
    docker compose -f docker-compose.dev.yml ps | Select-String "api" | Out-Null
} catch {
    Write-Host "❌ Los servicios no están ejecutándose. Ejecuta primero: .\start-dev.ps1" -ForegroundColor Red
    exit 1
}

switch ($Command) {
    "update" {
        Write-Host "🔄 Aplicando migraciones..." -ForegroundColor Yellow
        docker compose -f docker-compose.dev.yml exec api dotnet ef database update
        Write-Host "✅ Migraciones aplicadas correctamente" -ForegroundColor Green
    }
    
    "add" {
        if ([string]::IsNullOrEmpty($MigrationName)) {
            Write-Host "❌ Debes especificar un nombre para la migración" -ForegroundColor Red
            Write-Host "Uso: .\db-migrate.ps1 add NombreMigracion" -ForegroundColor Yellow
            exit 1
        }
        Write-Host "➕ Creando nueva migración: $MigrationName" -ForegroundColor Yellow
        docker compose -f docker-compose.dev.yml exec api dotnet ef migrations add $MigrationName
        Write-Host "✅ Migración creada correctamente" -ForegroundColor Green
    }
    
    "remove" {
        Write-Host "🗑️  Eliminando última migración..." -ForegroundColor Yellow
        docker compose -f docker-compose.dev.yml exec api dotnet ef migrations remove
        Write-Host "✅ Última migración eliminada" -ForegroundColor Green
    }
    
    "list" {
        Write-Host "📋 Listando migraciones..." -ForegroundColor Yellow
        docker compose -f docker-compose.dev.yml exec api dotnet ef migrations list
    }
    
    "reset" {
        Write-Host "⚠️  ADVERTENCIA: Esto eliminará todos los datos de la base de datos" -ForegroundColor Red
        $confirmation = Read-Host "¿Estás seguro? (y/N)"
        if ($confirmation -eq "y" -or $confirmation -eq "Y") {
            Write-Host "🔄 Reseteando base de datos..." -ForegroundColor Yellow
            docker compose -f docker-compose.dev.yml exec api dotnet ef database drop --force
            docker compose -f docker-compose.dev.yml exec api dotnet ef database update
            Write-Host "✅ Base de datos reseteada correctamente" -ForegroundColor Green
        } else {
            Write-Host "❌ Operación cancelada" -ForegroundColor Yellow
        }
    }
    
    default {
        Write-Host "❌ Comando no válido" -ForegroundColor Red
        Write-Host "Comandos disponibles:" -ForegroundColor Yellow
        Write-Host "  update   - Aplicar migraciones pendientes" -ForegroundColor White
        Write-Host "  add      - Crear nueva migración" -ForegroundColor White
        Write-Host "  remove   - Eliminar última migración" -ForegroundColor White
        Write-Host "  list     - Listar migraciones" -ForegroundColor White
        Write-Host "  reset    - Resetear base de datos (¡CUIDADO!)" -ForegroundColor White
    }
}
