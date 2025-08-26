# Sigav.Application

## Propósito
Esta carpeta contendrá los casos de uso, servicios de aplicación y lógica de coordinación entre el dominio y la infraestructura.

## Estructura Futura
```
Application/
├── Services/           # Servicios de aplicación
├── Commands/          # Comandos CQRS
├── Queries/           # Consultas CQRS
├── DTOs/             # Objetos de transferencia de datos
├── Interfaces/        # Interfaces de servicios
└── Validators/        # Validaciones de entrada
```

## Servicios Principales
- `AuthService` - Autenticación y autorización
- `BusetaService` - Gestión de busetas
- `ChecklistService` - Gestión de checklists
- `ExportService` - Exportación de datos
- `HistorialService` - Gestión del historial

## Estado Actual
- ✅ Servicios básicos ya implementados en `Sigav.Api/Services/`
- 🔄 Pendiente: Refactorización para separar en proyecto independiente
- 🔄 Pendiente: Implementación de CQRS y validaciones
