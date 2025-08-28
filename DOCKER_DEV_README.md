# 🐳 Desarrollo con Docker - SIGAV

Este documento explica cómo configurar y usar el entorno de desarrollo con Docker para el proyecto SIGAV.

## 📋 Prerrequisitos

- Docker Desktop instalado y ejecutándose
- Docker Compose v2
- PowerShell (Windows) o Bash (Linux/Mac)

## 🚀 Inicio Rápido

### Opción 1: Script Automático (Recomendado)

```powershell
# En Windows PowerShell
.\start-dev.ps1
```

### Opción 2: Comandos Manuales

```bash
# Construir las imágenes
docker compose -f docker-compose.dev.yml build

# Arrancar todos los servicios con hot-reload
docker compose -f docker-compose.dev.yml up --watch
```

## 🏗️ Arquitectura del Entorno

### Servicios Disponibles

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| **Frontend** | 4200 | Angular con hot-reload |
| **Backend API** | 5000 | .NET 8 con hot-reload |
| **PostgreSQL** | 5432 | Base de datos principal |
| **Redis** | 6379 | Caché y sesiones |
| **Live Reload** | 49153 | Puerto para live-reload de Angular |

### URLs de Acceso

- 🌐 **Frontend**: http://localhost:4200
- 🔧 **Backend API**: http://localhost:5000
- 📚 **Swagger UI**: http://localhost:5000/swagger
- 🗄️ **PostgreSQL**: localhost:5432
- ⚡ **Redis**: localhost:6379

## 🔄 Hot Reload

### Frontend (Angular)
- Los cambios en archivos `.ts`, `.html`, `.scss` se reflejan automáticamente
- Configurado con `--poll 2000` para detectar cambios en sistemas de archivos que no soportan inotify
- Live-reload en puerto 49153

### Backend (.NET)
- Los cambios en archivos `.cs` se reflejan automáticamente
- Configurado con `DOTNET_USE_POLLING_FILE_WATCHER=1`
- Hot reload habilitado con `dotnet watch run`

## 📁 Estructura de Volúmenes

```
Proyecto/
├── frontend/          # Montado en /app del contenedor frontend
├── backend/           # Montado en /app del contenedor api
└── postgres_data/     # Datos persistentes de PostgreSQL
```

### Exclusiones de Volúmenes

- `node_modules` del frontend (usa los del contenedor)
- `bin/` y `obj/` del backend (carpetas de build)

## 🛠️ Comandos Útiles

### Gestión de Servicios

```bash
# Ver logs de todos los servicios
docker compose -f docker-compose.dev.yml logs -f

# Ver logs de un servicio específico
docker compose -f docker-compose.dev.yml logs -f frontend
docker compose -f docker-compose.dev.yml logs -f api
docker compose -f docker-compose.dev.yml logs -f db

# Detener todos los servicios
docker compose -f docker-compose.dev.yml down

# Reconstruir una imagen específica
docker compose -f docker-compose.dev.yml build frontend
docker compose -f docker-compose.dev.yml build api

# Ejecutar comandos dentro de un contenedor
docker compose -f docker-compose.dev.yml exec frontend npm install
docker compose -f docker-compose.dev.yml exec api dotnet ef migrations add InitialCreate
```

### Base de Datos

```bash
# Conectar a PostgreSQL
docker compose -f docker-compose.dev.yml exec db psql -U sigav_user -d sigav

# Ejecutar migraciones
docker compose -f docker-compose.dev.yml exec api dotnet ef database update

# Crear nueva migración
docker compose -f docker-compose.dev.yml exec api dotnet ef migrations add NombreMigracion
```

## 🔧 Configuración

### Variables de Entorno

Las variables de entorno están configuradas en `docker-compose.dev.yml`:

```yaml
# Frontend
NODE_ENV: development

# Backend
ASPNETCORE_ENVIRONMENT: Development
ASPNETCORE_URLS: http://+:5000
DOTNET_USE_POLLING_FILE_WATCHER: 1
DOTNET_WATCH_SUPPRESS_LAUNCH_BROWSER: 1
DOTNET_WATCH_SUPPRESS_HOT_RELOAD: 0

# Base de datos
POSTGRES_DB: sigav
POSTGRES_USER: sigav_user
POSTGRES_PASSWORD: sigav_password
```

### Conexión a la Base de Datos

La cadena de conexión está configurada para usar el servicio `db`:

```
Host=db;Database=sigav;Username=sigav_user;Password=sigav_password
```

## 🐛 Solución de Problemas

### Problemas Comunes

1. **Puerto ya en uso**
   ```bash
   # Verificar qué está usando el puerto
   netstat -ano | findstr :4200
   # Detener el proceso o cambiar el puerto en docker-compose.dev.yml
   ```

2. **Hot reload no funciona**
   - Verificar que los volúmenes estén montados correctamente
   - Revisar logs: `docker compose -f docker-compose.dev.yml logs -f frontend`

3. **Base de datos no conecta**
   - Verificar que el servicio `db` esté saludable
   - Revisar logs: `docker compose -f docker-compose.dev.yml logs -f db`

4. **Cambios no se reflejan**
   - Verificar que no haya archivos `.dockerignore` excluyendo archivos importantes
   - Reiniciar el servicio: `docker compose -f docker-compose.dev.yml restart frontend`

### Limpieza

```bash
# Limpiar contenedores, redes y volúmenes
docker compose -f docker-compose.dev.yml down -v

# Limpiar imágenes no utilizadas
docker image prune -f

# Limpiar todo (¡CUIDADO! Esto elimina todos los datos)
docker system prune -a --volumes
```

## 📝 Notas Importantes

- Los datos de PostgreSQL se mantienen en un volumen persistente
- Los `node_modules` del frontend se instalan dentro del contenedor
- El hot-reload funciona mejor en Linux/Mac que en Windows
- Para desarrollo en Windows, se usa polling en lugar de inotify

## 🔄 Flujo de Desarrollo

1. Ejecutar `.\start-dev.ps1`
2. Editar código en tu IDE
3. Los cambios se reflejan automáticamente
4. Para cambios en dependencias, reconstruir la imagen correspondiente
5. Para cambios en configuración de Docker, reiniciar los servicios
