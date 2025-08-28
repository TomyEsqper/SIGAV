# Getting Started - SIGAV

Esta guía te ayuda a levantar SIGAV en minutos, en Windows, macOS o Linux.

## 🚀 Requisitos Previos

- **Docker Desktop** (obligatorio)
- **Docker Compose** (incluido con Docker Desktop)
- **Git** (para clonar el repositorio)

> **Nota**: No necesitas instalar .NET, Node.js ni PostgreSQL por separado. Todo se ejecuta en contenedores Docker.

---

## 🐳 Opción A) Solo con Docker (RECOMENDADO)

### 1. Preparación Inicial

1. **Clonar el repositorio** (si no lo has hecho):
```bash
git clone <url-del-repositorio>
cd SIGAV
```

2. **Verificar que Docker Desktop esté ejecutándose**

3. **Opcional**: Crear archivo `.env` personalizado:
```bash
cp env.example .env
# Editar .env si necesitas cambiar puertos o credenciales
```

### 2. Ejecutar el Proyecto

```bash
# Construir e iniciar todos los servicios
docker-compose up -d --build

# Verificar que todos los servicios estén corriendo
docker-compose ps
```

### 3. Verificar que Todo Funcione

```bash
# Verificar API
curl http://localhost:5000/health
# Debe responder: "Healthy"

# Verificar API endpoints
curl http://localhost:5000/api/Busetas
# Debe responder: []

# Verificar Frontend
curl -I http://localhost:4200
# Debe responder: HTTP/1.1 200 OK
```

### 4. URLs de Acceso

| Servicio   | Puerto | URL                                    | Credenciales                    |
|------------|--------|----------------------------------------|--------------------------------|
| **Frontend** | 4200   | http://localhost:4200                 | -                              |
| **API**      | 5000   | http://localhost:5000                 | -                              |
| **PgAdmin**  | 5050   | http://localhost:5050                 | Email: `admin@sigav.local`<br>Pass: `admin123` |
| **PostgreSQL**| 5432   | Host: `localhost`                     | User: `sigav_user`<br>Pass: `sigav_password` |
| **Redis**    | 6379   | redis://localhost:6379                | -                              |

### 5. Comandos Útiles

```bash
# Ver logs en tiempo real
docker-compose logs -f

# Ver logs de un servicio específico
docker-compose logs -f api
docker-compose logs -f frontend

# Reiniciar un servicio
docker-compose restart api
docker-compose restart frontend

# Reconstruir un servicio después de cambios
docker-compose up -d --build api
docker-compose up -d --build frontend

# Detener todos los servicios
docker-compose down

# Detener y eliminar volúmenes (RESETEA LA BASE DE DATOS)
docker-compose down -v

# Ver estado de todos los servicios
docker-compose ps
```

### 6. Solución de Problemas Comunes

#### ❌ Error: "Connection refused" al conectar a PostgreSQL
**Solución**: Verificar que el archivo `backend/Sigav.Api/appsettings.Development.json` tenga:
```json
{
  "ConnectionStrings": {
    "DefaultConnection": "Host=postgres;Database=sigav;Username=sigav_user;Password=sigav_password"
  }
}
```

#### ❌ Error: "relation does not exist"
**Solución**: Ejecutar migraciones manualmente:
```bash
cd backend/Sigav.Api
dotnet ef database update --connection "Host=localhost;Database=sigav;Username=sigav_user;Password=sigav_password"
```

#### ❌ Error: Puertos ocupados
**Solución**: Cambiar puertos en `.env` o liberar los puertos:
```bash
# Ver qué está usando el puerto
netstat -ano | findstr :5000
# Terminar el proceso si es necesario
```

#### ❌ Error: Docker no puede construir las imágenes
**Solución**: 
```bash
# Limpiar cache de Docker
docker system prune -a
# Reconstruir
docker-compose up -d --build
```

### 7. Desarrollo con Docker

- **Hot Reload**: Los cambios en el código se reflejan automáticamente
- **Base de Datos**: Los datos persisten entre reinicios
- **Logs**: Usa `docker-compose logs -f` para ver logs en tiempo real

---

## 🔧 Opción B) Desarrollo Manual (Solo para desarrollo avanzado)

### Backend (.NET 8)

```bash
# Restaurar dependencias
dotnet restore backend/Sigav.Api/Sigav.Api.csproj

# Compilar
dotnet build backend/Sigav.Api/Sigav.Api.csproj

# Ejecutar
dotnet run --project backend/Sigav.Api/Sigav.Api.csproj --urls http://localhost:5000
```

### Frontend (Angular 20)

```bash
cd frontend
npm install
npm start
```

### Base de Datos (PostgreSQL)

Necesitas PostgreSQL instalado localmente o usar Docker solo para la BD:
```bash
docker-compose up -d postgres redis
```

---

## 📋 Endpoints de la API

### Health Check
- `GET /health` - Estado de salud del sistema

### Entidades Principales
- `GET /api/Busetas` - Listar busetas
- `POST /api/Busetas` - Crear buseta
- `GET /api/Busetas/{id}` - Obtener buseta específica
- `PUT /api/Busetas/{id}` - Actualizar buseta
- `DELETE /api/Busetas/{id}` - Eliminar buseta

- `GET /api/Empresas` - Listar empresas
- `GET /api/Usuarios` - Listar usuarios
- `GET /api/CustomFields` - Listar campos personalizados

### Campos Personalizados
- `GET /api/Busetas/{id}/custom-fields` - Campos personalizados de una buseta
- `GET /api/Busetas/empresa/{empresaId}` - Busetas por empresa

---

## 🎯 Próximos Pasos

1. **Abrir el navegador** y visitar http://localhost:4200
2. **Explorar la API** en http://localhost:5000/api/Busetas
3. **Administrar la BD** con PgAdmin en http://localhost:5050
4. **Crear datos de prueba** para ver la aplicación funcionando

---

## 📚 Documentación Adicional

- **Campos Personalizados**: Ver `backend/Sigav.Api/README_CustomFields.md`
- **Arquitectura**: Ver `backend/Application/README.md`
- **Infraestructura**: Ver `backend/Infrastructure/README.md`

¿Problemas? Revisa los logs con `docker-compose logs` o consulta la sección de solución de problemas arriba.
