# Getting Started

Esta guía te ayuda a levantar SIGAV en minutos, en Windows, macOS o Linux.

## Requisitos
- .NET 8 SDK
- Node.js 18+
- Docker Desktop (opcional, para stack completo)
- Rider o Visual Studio (opcional)

---

## Opción A) Solo con Docker (recomendado para probar rápido)
No necesitas instalar .NET, Node.js ni PostgreSQL. Solo Docker Desktop + Docker Compose.

1) Opcional: crea un archivo `.env` en la raíz copiando `env.example` y ajusta puertos/credenciales.
2) Desde la raíz del repo:
```bash
docker-compose up -d
docker-compose ps
```
3) Servicios y puertos (por defecto):

| Servicio   | Puerto host | URL/Detalle                     |
|-----------|-------------|----------------------------------|
| API       | 5000        | http://localhost:5000           |
| Frontend  | 4200        | http://localhost:4200           |
| PostgreSQL| 5432        | Host: localhost                  |
| pgAdmin   | 5050        | http://localhost:5050           |
| Redis     | 6379        | redis://localhost:6379          |

4) Persistencia de base de datos
- Los datos de PostgreSQL se guardan en el volumen `postgres_data`.
- `docker compose down` no borra datos.
- `docker compose down -v` apaga y elimina volúmenes (reset total de BD).

5) Desarrollo dentro de Docker
- El código se monta como volumen. Cambios en archivos se reflejan (hot reload según configuración de cada servicio).
- Si cambias dependencias (NuGet/NPM), reconstruye:
```bash
docker compose build
docker compose up -d
```

6) Comandos útiles
```bash
docker compose logs -f                 # ver logs de todos
docker compose logs -f api             # ver logs de un servicio
docker compose restart api             # reiniciar un contenedor
docker compose down                    # apagar servicios
docker compose down -v                 # apagar y borrar volúmenes (resetea BD)
```

7) Problemas comunes
- Puertos ocupados: cambia los puertos en `.env` o libera el puerto.
- Windows: habilitar WSL2 y virtualización (Docker Desktop > Settings > General).
- pgAdmin: para conectar a PostgreSQL desde pgAdmin, usa host `postgres` (nombre del servicio), puerto `5432`, user/pass del `.env`.

---

## Opción B) Manual (sin Docker)

### Backend (API) – ejecutar desde la raíz del repo
```bash
# Restaurar y compilar
dotnet restore backend/Sigav.Api/Sigav.Api.csproj
dotnet build backend/Sigav.Api/Sigav.Api.csproj

# Ejecutar en http://localhost:5000
dotnet run --project backend/Sigav.Api/Sigav.Api.csproj --urls http://localhost:5000
```
Notas:
- Si ejecutas desde la raíz, usa siempre la ruta con `backend/`.
- Verificación: GET http://localhost:5000/api/health
- Ver 404 en `http://localhost:5000/` es normal (no hay ruta raíz).
- El warning de HTTPS redirection en desarrollo es benigno.

### Frontend (Angular)
```bash
cd frontend
npm install
npm start
# http://localhost:4200
```

### Abrir en Rider/Visual Studio
1) Abrir `backend/SIGAV.sln`
2) Seleccionar proyecto de inicio: `Sigav.Api`
3) Compilar y ejecutar

---

## Endpoints útiles
- Salud API: `GET /api/health`
- Empresas: CRUD en `POST/GET/PUT/DELETE /api/Empresas`
- Campos personalizados: `POST/GET/PUT/DELETE /api/CustomFields`
- Valores personalizados: `POST/GET/PUT/DELETE /api/CustomFieldValues`
- Busetas y usuarios: CRUD con soporte de campos personalizados

¿Listo para más detalles sobre personalización? Ver `backend/Sigav.Api/README_CustomFields.md`.
