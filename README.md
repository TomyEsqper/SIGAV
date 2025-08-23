# 🚌 SIGAV - Sistema de Alistamiento de Busetas
## Guía Completa de Entendimiento y Ejecución

---

## 📖 **¿QUÉ ES SIGAV?**

**SIGAV** es un sistema completo y profesional diseñado para gestionar el proceso de **alistamiento de busetas** (preparación y verificación de vehículos antes de salir a ruta). 

### 🎯 **¿PARA QUÉ SIRVE?**

Imagina que tienes una empresa de transporte con 50 busetas que salen diariamente. Antes de cada viaje, necesitas:
- ✅ Verificar que la buseta esté en buen estado
- ✅ Revisar frenos, luces, neumáticos, etc.
- ✅ Documentar todo el proceso
- ✅ Generar reportes para auditorías
- ✅ Controlar quién hizo qué revisión

**SIGAV automatiza todo este proceso** con checklists digitales, historial completo y reportes exportables.

---

## 🏗️ **ARQUITECTURA DEL SISTEMA**

### **¿POR QUÉ ESTA ARQUITECTURA?**

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   FRONTEND      │    │   BACKEND       │    │   BASE DE       │
│   (Angular)     │◄──►│   (.NET 8)      │◄──►│   DATOS         │
│                 │    │                 │    │   (PostgreSQL)  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Usuario       │    │   API REST      │    │   Cache         │
│   (Inspector)   │    │   (Swagger)     │    │   (Redis)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

**¿Por qué Angular + .NET?**
- **Angular**: Interfaz moderna, responsive, fácil de mantener
- **.NET 8**: Backend robusto, rápido, con Entity Framework
- **PostgreSQL**: Base de datos empresarial, confiable
- **Redis**: Cache para mejorar velocidad de respuestas

---

## 🚀 **PASO A PASO: CÓMO EJECUTAR EL PROYECTO**

### **PASO 1: PREPARACIÓN DEL ENTORNO**

#### **¿Qué necesitas instalar?**

1. **Docker Desktop** 
   - **¿Por qué?** Docker te permite ejecutar todo el sistema sin instalar nada más
   - **¿Dónde?** [docker.com](https://docker.com)
   - **¿Qué hace?** Crea "contenedores" virtuales con todo lo necesario

2. **Git** (opcional)
   - **¿Por qué?** Para descargar el código del proyecto
   - **¿Dónde?** [git-scm.com](https://git-scm.com)

#### **¿Por qué Docker y no instalación manual?**
- ✅ **Sin conflictos**: No interfiere con tu sistema
- ✅ **Reproducible**: Funciona igual en cualquier computadora
- ✅ **Fácil**: Un comando y todo funciona
- ✅ **Limpio**: Puedes eliminar todo fácilmente

### **PASO 2: DESCARGAR EL PROYECTO**

```bash
# Opción 1: Si tienes Git
git clone <URL-DEL-REPOSITORIO>
cd SIGAV

# Opción 2: Descargar ZIP y extraer
# Luego abrir terminal en la carpeta SIGAV
```

### **PASO 3: EJECUTAR CON DOCKER**

```bash
# En la carpeta SIGAV, ejecutar:
docker compose up -d
```

#### **¿Qué hace este comando?**

1. **Lee** `docker-compose.yml` (archivo de configuración)
2. **Descarga** las imágenes necesarias (PostgreSQL, Redis, etc.)
3. **Construye** tu aplicación (.NET + Angular)
4. **Inicia** todos los servicios
5. **Conecta** todo entre sí

#### **¿Qué servicios se inician?**

| Servicio | Puerto | ¿Para qué sirve? | ¿Por qué ese puerto? |
|----------|--------|------------------|----------------------|
| **API** | 5000 | Backend de la aplicación | Puerto estándar para APIs |
| **Frontend** | 4200 | Interfaz web | Puerto estándar de Angular |
| **PostgreSQL** | 5432 | Base de datos | Puerto estándar de PostgreSQL |
| **Redis** | 6379 | Cache | Puerto estándar de Redis |
| **pgAdmin** | 5050 | Administrar base de datos | Puerto personalizado |

### **PASO 4: VERIFICAR QUE TODO FUNCIONE**

#### **¿Cómo saber si está funcionando?**

1. **Abrir navegador** y ir a: `http://localhost:4200`
   - Deberías ver la pantalla de login de SIGAV

2. **Verificar API**: `http://localhost:5000/swagger`
   - Deberías ver la documentación de la API

3. **Verificar base de datos**: `http://localhost:5050`
   - Login: `admin@sigav.com` / `admin123`

#### **¿Qué hacer si algo no funciona?**

```bash
# Ver logs de todos los servicios
docker compose logs -f

# Ver logs de un servicio específico
docker compose logs -f api
docker compose logs -f frontend

# Reiniciar todo
docker compose down
docker compose up -d
```

---

## 🔑 **PRIMERA VEZ: ACCEDER AL SISTEMA**

### **Credenciales de Prueba**

| Rol | Email | Contraseña | ¿Qué puede hacer? |
|-----|-------|------------|-------------------|
| **Admin** | admin@sigav.local | Admin_123! | Todo (crear usuarios, gestionar busetas) |
| **Inspector** | inspector@sigav.local | Inspector_123! | Ejecutar checklists, ver busetas |
| **Mecánico** | mecanico@sigav.local | Mecanico_123! | Ver historial, generar reportes |

### **¿Por qué estos roles?**

- **Admin**: Gestión completa del sistema
- **Inspector**: Persona que revisa las busetas
- **Mecánico**: Persona que ve el historial para mantenimiento

---

## 🛠️ **DESARROLLO LOCAL (OPCIONAL)**

### **¿Cuándo usar desarrollo local?**

- ✅ Quieres modificar el código
- ✅ Quieres debuggear paso a paso
- ✅ Quieres usar tu editor favorito
- ✅ Quieres ejecutar tests

### **¿Qué necesitas instalar?**

1. **.NET 8 SDK**
   - **¿Por qué?** Para ejecutar el backend
   - **¿Dónde?** [dotnet.microsoft.com](https://dotnet.microsoft.com)

2. **Node.js 22**
   - **¿Por qué?** Para ejecutar Angular
   - **¿Dónde?** [nodejs.org](https://nodejs.org)

### **Ejecutar Backend Localmente**

```bash
cd backend
dotnet restore          # Descarga dependencias
dotnet run             # Ejecuta la aplicación
```

**¿Qué hace cada comando?**
- `dotnet restore`: Descarga paquetes NuGet (.NET)
- `dotnet run`: Compila y ejecuta la aplicación

### **Ejecutar Frontend Localmente**

```bash
cd frontend
npm install            # Descarga dependencias de Node.js
npm start             # Ejecuta Angular en modo desarrollo
```

**¿Qué hace cada comando?**
- `npm install`: Descarga paquetes de Node.js
- `npm start`: Ejecuta Angular con recarga automática

---

## 📊 **ENTENDIENDO LAS FUNCIONALIDADES**

### **1. GESTIÓN DE BUSETAS**

#### **¿Qué es una "Buseta"?**
Una buseta es un vehículo de transporte que necesita ser revisado antes de salir a ruta.

#### **¿Qué información se guarda?**
- **Placa**: Identificación única del vehículo
- **Modelo**: Marca y modelo del vehículo
- **Capacidad**: Número de pasajeros
- **Agencia**: A qué empresa pertenece
- **Estado**: Disponible, En Mantenimiento, En Ruta

#### **¿Por qué estos campos?**
- **Placa única**: Evita duplicados
- **Estado**: Control del ciclo de vida del vehículo
- **Agencia**: Organización por empresas

### **2. CHECKLISTS DE ALISTAMIENTO**

#### **¿Qué es un "Checklist"?**
Una lista de verificación que debe completar el inspector antes de que la buseta salga.

#### **¿Cómo funciona?**
1. **Admin crea plantilla**: Define qué revisar
2. **Inspector ejecuta**: Marca cada item como ✅ o ❌
3. **Sistema valida**: Si algo está ❌, pide explicación
4. **Se guarda resultado**: Para auditorías futuras

#### **Ejemplo de Checklist:**
```
□ Frenos funcionando correctamente
□ Luces delanteras y traseras OK
□ Neumáticos con presión correcta
□ Documentación al día
□ Combustible suficiente
```

### **3. HISTORIAL Y REPORTES**

#### **¿Por qué es importante?**
- **Auditorías**: Cumplimiento de normativas
- **Mantenimiento**: Seguimiento de problemas
- **Estadísticas**: Rendimiento de la flota

#### **¿Qué reportes se generan?**
- **CSV**: Para análisis en Excel
- **PDF**: Para impresión y archivo

---

## 🗄️ **BASE DE DATOS: ¿POR QUÉ POSTGRESQL?**

### **¿Qué es PostgreSQL?**
Una base de datos empresarial, robusta y confiable.

### **¿Por qué no SQL Server o MySQL?**
- **SQL Server**: Solo Windows, licencia costosa
- **MySQL**: Menos robusto para aplicaciones empresariales
- **PostgreSQL**: Gratuito, multiplataforma, muy robusto

### **¿Qué tablas tiene el sistema?**

| Tabla | ¿Para qué? | Ejemplo de datos |
|-------|------------|------------------|
| **Usuarios** | Gestionar quién accede | admin@sigav.local |
| **Busetas** | Información de vehículos | Placa ABC123 |
| **ChecklistPlantillas** | Plantillas de verificación | "Checklist Diario" |
| **ChecklistEjecuciones** | Resultados de verificaciones | Fecha, inspector, buseta |
| **ChecklistItemResultados** | Detalle de cada item | "Frenos OK", "Luces mal" |

---

## 🔒 **SEGURIDAD: ¿POR QUÉ JWT?**

### **¿Qué es JWT?**
JSON Web Token - Una forma segura de identificar usuarios.

### **¿Cómo funciona?**
1. **Usuario hace login** con email/contraseña
2. **Sistema valida** credenciales
3. **Sistema genera token** (como un "pase" temporal)
4. **Usuario usa token** para acceder a funciones
5. **Token expira** en 24 horas por seguridad

### **¿Por qué 24 horas?**
- **Seguridad**: Si se roba el token, solo funciona 24 horas
- **Conveniencia**: No pedir login cada día
- **Balance**: Entre seguridad y usabilidad

---

## 🐳 **DOCKER: ¿QUÉ ES Y POR QUÉ USARLO?**

### **¿Qué es Docker?**
Una tecnología que "empaqueta" aplicaciones con todo lo necesario.

### **Analogía simple:**
- **Sin Docker**: Como cocinar desde cero - necesitas ingredientes, utensilios, etc.
- **Con Docker**: Como pedir comida a domicilio - todo viene listo

### **¿Por qué usar Docker en SIGAV?**
- ✅ **Consistencia**: Funciona igual en cualquier computadora
- ✅ **Simplicidad**: Un comando y todo funciona
- ✅ **Aislamiento**: No interfiere con tu sistema
- ✅ **Portabilidad**: Fácil de mover entre servidores

### **¿Qué contiene cada "contenedor"?**

| Contenedor | Contiene | ¿Por qué? |
|------------|----------|-----------|
| **api** | Aplicación .NET | Backend de la API |
| **frontend** | Angular + Nginx | Interfaz web + servidor web |
| **postgres** | PostgreSQL | Base de datos |
| **redis** | Redis | Cache para mejorar velocidad |
| **pgadmin** | pgAdmin | Herramienta para administrar BD |

---

## 🚨 **SOLUCIÓN DE PROBLEMAS COMUNES**

### **Problema 1: "Puerto ya está en uso"**

#### **¿Qué significa?**
Otro programa está usando el puerto que necesita SIGAV.

#### **¿Cómo solucionarlo?**
```bash
# Ver qué está usando el puerto 5000
netstat -an | findstr :5000

# Ver qué está usando el puerto 4200
netstat -an | findstr :4200

# Si es otro Docker, pararlo
docker stop <nombre-del-contenedor>
```

### **Problema 2: "No puedo acceder a la aplicación"**

#### **Verificar pasos:**
1. **¿Está Docker ejecutándose?**
2. **¿Se ejecutó `docker compose up -d`?**
3. **¿Hay errores en los logs?**

```bash
# Ver estado de contenedores
docker compose ps

# Ver logs
docker compose logs -f
```

### **Problema 3: "Base de datos no conecta"**

#### **Posibles causas:**
- PostgreSQL no se inició correctamente
- Contraseña incorrecta
- Puerto bloqueado

#### **Solución:**
```bash
# Reiniciar solo PostgreSQL
docker compose restart postgres

# Ver logs específicos
docker compose logs postgres
```

---

## 📈 **MONITOREO Y MANTENIMIENTO**

### **¿Cómo ver si todo funciona bien?**

```bash
# Estado de todos los servicios
docker compose ps

# Uso de recursos
docker stats

# Logs en tiempo real
docker compose logs -f
```

### **¿Cuándo reiniciar?**

- **Cambios en configuración**
- **Problemas de memoria**
- **Actualizaciones de seguridad**

### **¿Cómo reiniciar?**

```bash
# Reiniciar todo
docker compose restart

# Reiniciar servicio específico
docker compose restart api
docker compose restart frontend
```

---

## 🧪 **TESTING Y CALIDAD**

### **¿Por qué hacer tests?**

- ✅ **Confianza**: Saber que el código funciona
- ✅ **Mantenimiento**: Cambios no rompen funcionalidad
- ✅ **Documentación**: Tests explican cómo usar el código

### **Ejecutar Tests del Backend**

```bash
cd backend
dotnet test
```

### **Ejecutar Tests del Frontend**

```bash
cd frontend
npm test
npm run lint
```

---

## 🚀 **DESPLIEGUE EN PRODUCCIÓN**

### **¿Cuándo usar producción?**

- ✅ Sistema en uso real por usuarios
- ✅ Necesitas máximo rendimiento
- ✅ Necesitas seguridad adicional

### **¿Qué cambia en producción?**

```bash
# Usar configuración de producción
docker compose -f docker-compose.prod.yml up -d

# Variables de entorno diferentes
ASPNETCORE_ENVIRONMENT=Production
JWT__Key=<clave-secreta-fuerte>
```

---

## 📚 **RECURSOS ADICIONALES**

### **Documentación Oficial**
- **.NET**: [docs.microsoft.com](https://docs.microsoft.com)
- **Angular**: [angular.io](https://angular.io)
- **PostgreSQL**: [postgresql.org](https://postgresql.org)
- **Docker**: [docker.com](https://docker.com)

### **Comunidad y Soporte**
- **GitHub Issues**: Para reportar problemas
- **Stack Overflow**: Para preguntas técnicas
- **Discord/Slack**: Para discusiones en tiempo real

---

## 🎯 **RESUMEN DE COMANDOS IMPORTANTES**

### **Docker (Ejecución Rápida)**
```bash
docker compose up -d          # Iniciar todo
docker compose down           # Parar todo
docker compose logs -f        # Ver logs
docker compose ps             # Estado de servicios
docker compose restart        # Reiniciar todo
```

### **Desarrollo Local**
```bash
# Backend
cd backend
dotnet restore
dotnet run

# Frontend
cd frontend
npm install
npm start
```

### **Testing**
```bash
# Backend
dotnet test

# Frontend
npm test
npm run lint
```

---

## 🏁 **¡LISTO PARA EMPEZAR!**

Ahora tienes toda la información necesaria para:

1. ✅ **Entender** qué es SIGAV y para qué sirve
2. ✅ **Instalar** Docker y ejecutar el proyecto
3. ✅ **Acceder** al sistema con las credenciales
4. ✅ **Desarrollar** localmente si quieres modificar código
5. ✅ **Solucionar** problemas comunes
6. ✅ **Mantener** el sistema funcionando

**¿Tienes alguna pregunta?** Revisa la sección de troubleshooting o consulta la documentación de la API en Swagger.

---

**🚌 SIGAV - Sistema de Alistamiento de Busetas v1.0.0**

*Desarrollado con ❤️ para hacer el transporte más seguro y eficiente*
