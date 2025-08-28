# 🚀 Desarrollo con Hot Reload

## Configuración Completa de Desarrollo

Este proyecto está configurado para desarrollo con **hot reload automático** en todos los componentes.

## 🎯 Inicio Rápido

### Windows (PowerShell)
```powershell
.\dev-start.ps1
```

### Linux/Mac (Bash)
```bash
./dev-start.sh
```

### Manual
```bash
docker-compose -f docker-compose.dev.yml up --build
```

## 🔥 Hot Reload - Cambios Automáticos

### ✅ Frontend (Angular)
- **Puerto:** `http://localhost:4200`
- **Hot Reload:** ✅ Automático
- **Cambios:** Se reflejan instantáneamente al guardar archivos

### ✅ Backend (.NET)
- **Puerto:** `http://localhost:5000`
- **Swagger:** `http://localhost:5000/swagger`
- **Hot Reload:** ✅ Automático con `dotnet run --watch`
- **Cambios:** Se reflejan automáticamente al guardar archivos `.cs`

### ✅ Base de Datos (PostgreSQL)
- **Puerto:** `localhost:5432`
- **Persistencia:** ✅ Los datos se mantienen entre reinicios
- **Migraciones:** Se aplican automáticamente

### ✅ Cache (Redis)
- **Puerto:** `localhost:6379`
- **Persistencia:** ✅ El cache se mantiene entre reinicios

## 🛠️ Servicios Disponibles

| Servicio | URL | Descripción |
|----------|-----|-------------|
| Frontend | http://localhost:4200 | Angular con hot reload |
| Backend API | http://localhost:5000 | .NET API con hot reload |
| Swagger | http://localhost:5000/swagger | Documentación de APIs |
| pgAdmin | http://localhost:5050 | Administrador de PostgreSQL |
| PostgreSQL | localhost:5432 | Base de datos |
| Redis | localhost:6379 | Cache |

## 🔧 Comandos Útiles

### Ver logs en tiempo real
```bash
docker-compose -f docker-compose.dev.yml up --follow
```

### Solo frontend y backend
```bash
docker-compose -f docker-compose.dev.yml up frontend-dev api-dev
```

### Solo backend y base de datos
```bash
docker-compose -f docker-compose.dev.yml up api-dev postgres redis
```

### Detener todos los servicios
```bash
docker-compose -f docker-compose.dev.yml down
```

### Resetear base de datos (cuidado, borra todos los datos)
```bash
docker-compose -f docker-compose.dev.yml down -v
docker-compose -f docker-compose.dev.yml up
```

## 🗄️ Acceso a Base de Datos

### pgAdmin
- **URL:** http://localhost:5050
- **Email:** admin@sigav.com
- **Password:** admin123

### Conexión Directa
- **Host:** localhost
- **Puerto:** 5432
- **Base de datos:** sigav
- **Usuario:** sigav_user
- **Password:** sigav_password

## 🔄 Flujo de Desarrollo

1. **Ejecuta:** `.\dev-start.ps1` (Windows) o `./dev-start.sh` (Linux/Mac)
2. **Desarrolla Frontend:**
   - Edita archivos en `frontend/src/`
   - Los cambios se reflejan automáticamente en `http://localhost:4200`
3. **Desarrolla Backend:**
   - Edita archivos en `backend/Sigav.Api/`
   - Los cambios se reflejan automáticamente en `http://localhost:5000`
4. **Prueba APIs:**
   - Usa Swagger en `http://localhost:5000/swagger`
5. **Administra DB:**
   - Usa pgAdmin en `http://localhost:5050`

## ⚡ Ventajas de esta Configuración

- ✅ **Hot reload** en frontend y backend
- ✅ **Persistencia** de datos y cache
- ✅ **Entorno consistente** entre desarrolladores
- ✅ **No reinicios** necesarios para cambios de código
- ✅ **Fácil acceso** a todos los servicios
- ✅ **Debugging** simplificado

## 🚨 Solución de Problemas

### Si el backend no inicia
```bash
# Ver logs del backend
docker-compose -f docker-compose.dev.yml logs api-dev

# Rebuildear solo el backend
docker-compose -f docker-compose.dev.yml up --build api-dev
```

### Si el frontend no inicia
```bash
# Ver logs del frontend
docker-compose -f docker-compose.dev.yml logs frontend-dev

# Rebuildear solo el frontend
docker-compose -f docker-compose.dev.yml up --build frontend-dev
```

### Si la base de datos no conecta
```bash
# Verificar que PostgreSQL esté corriendo
docker-compose -f docker-compose.dev.yml ps postgres

# Resetear solo la base de datos
docker-compose -f docker-compose.dev.yml down postgres
docker-compose -f docker-compose.dev.yml up postgres
```

¡Ahora puedes desarrollar sin reiniciar nada! 🎉
