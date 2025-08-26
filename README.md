# SIGAV - Sistema de Gestión de Vehículos

SIGAV es un sistema para gestionar flotas (busetas, checklists, empleados) con soporte de campos personalizables por empresa.

## Arquitectura
- Backend: .NET 8 (ASP.NET Core Web API) → `backend/Sigav.Api`
- Frontend: Angular → `frontend`
- Base de datos: PostgreSQL · Redis · Docker Compose

## Empezar rápido
- Guía paso a paso: ver `GETTING_STARTED.md`
- Campos personalizables: ver `backend/Sigav.Api/README_CustomFields.md`

## Estructura
```
SIGAV/
├── backend/
│   ├── SIGAV.sln
│   └── Sigav.Api/
│       ├── Controllers/
│       ├── Domain/
│       ├── Data/
│       └── Program.cs
├── frontend/
├── docker-compose.yml
└── README.md
```

## Comandos útiles (desde la raíz)
```bash
# Backend
dotnet build backend/Sigav.Api/Sigav.Api.csproj
dotnet run --project backend/Sigav.Api/Sigav.Api.csproj --urls http://localhost:5000

# Docker (todo el stack)
docker-compose up -d
```

## Estado actual
- Backend: compila y ejecuta
- API mínima lista (Health + CRUDs base + personalización)
- Estructura preparada para crecer
- Ver GETTING_STARTED.md para más
