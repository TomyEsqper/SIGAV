# Sigav.Infrastructure

## Propósito
Esta carpeta contendrá la implementación de acceso a datos, configuración de base de datos, y servicios externos.

## Estructura Futura
```
Infrastructure/
├── Data/              # Contexto de base de datos y repositorios
├── Services/          # Servicios de infraestructura
├── Configuration/     # Configuración de servicios
├── Migrations/        # Migraciones de Entity Framework
└── External/          # Servicios externos (Redis, etc.)
```

## Componentes Principales
- `SigavDbContext` - Contexto de Entity Framework
- `Repositories` - Implementaciones de repositorios
- `RedisService` - Servicio de cache
- `Configuration` - Configuración de servicios

## Estado Actual
- ✅ Contexto de base de datos ya implementado en `Sigav.Api/Data/`
- 🔄 Pendiente: Refactorización para separar en proyecto independiente
- 🔄 Pendiente: Implementación de repositorios genéricos
- 🔄 Pendiente: Configuración de migraciones
