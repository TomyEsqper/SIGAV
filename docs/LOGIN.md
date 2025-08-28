# Sistema de Login - SIGAV

## Descripción

Sistema de autenticación para SIGAV que permite a los usuarios iniciar sesión con credenciales de empresa, usuario/email y contraseña.

## Endpoints

### POST /api/auth/login

**Descripción**: Endpoint para autenticación de usuarios.

**Request Body**:
```json
{
  "tenant": "string",           // Nombre de la empresa (requerido, min 3 chars)
  "usernameOrEmail": "string",  // Usuario o email (requerido, min 3 chars)
  "password": "string"          // Contraseña (requerido, min 8 chars)
}
```

**Respuestas**:

#### 200 OK - Login exitoso
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresIn": 900,
  "tokenType": "Bearer",
  "user": {
    "id": "1",
    "name": "Admin Demo",
    "email": "admin@demo.local"
  },
  "tenant": "empresa demo"
}
```

#### 400 Bad Request - Credenciales inválidas
```json
{
  "message": "Credenciales inválidas"
}
```

#### 423 Locked - Cuenta bloqueada
```json
{
  "message": "Cuenta bloqueada temporalmente"
}
```

#### 500 Internal Server Error
```json
{
  "message": "Error interno del servidor"
}
```

## Seguridad

### JWT Token
- **Algoritmo**: HS256
- **Tiempo de vida**: 15 minutos
- **Claims incluidos**:
  - `sub`: ID del usuario
  - `tenant`: Nombre de la empresa (normalizado a minúsculas)
  - `jti`: ID único del token
  - `iat`: Timestamp de emisión
  - `exp`: Timestamp de expiración

### Protección contra ataques
- **Anti-enumeración**: Mensajes genéricos para todos los errores de credenciales
- **Bloqueo temporal**: 5 intentos fallidos bloquean la cuenta por 15 minutos
- **Rate limiting**: Configurado a nivel de aplicación
- **Hash seguro**: SHA256 para contraseñas (en producción usar Argon2id)

### Validaciones
- **Tenant**: Debe existir y estar activo
- **Usuario**: Debe pertenecer al tenant y estar activo
- **Contraseña**: Verificación contra hash almacenado
- **Sanitización**: Inputs normalizados (trim, lowercase)

## Auditoría

Cada intento de login genera un registro en la tabla `AuthLogs` con:
- Tenant
- UserId (si existe)
- Username intentado
- IP address
- User agent
- Resultado (ok/fail/locked)
- Timestamp
- JTI (si login exitoso)

## Variables de Entorno

```bash
# JWT Configuration
AUTH_JWT_SECRET=your-super-secret-key-minimum-256-bits
AUTH_ACCESS_TTL_MIN=15
AUTH_RATE_LIMIT_WINDOW=300
AUTH_RATE_LIMIT_MAX=5

# Database
DB_CONNECTION_STRING=Host=postgres;Database=sigav;Username=sigav_user;Password=sigav_password

# Environment
APP_ENV=Development|Staging|Production

# CORS
CORS_ALLOWED_ORIGINS=https://app.sigav.com,https://staging.sigav.com
```

## Datos de Prueba

### Usuario Demo
- **Empresa**: "Empresa Demo"
- **Usuario**: "admin@demo.local" o "Admin"
- **Contraseña**: "admin123"

### Cómo probar

1. **Login exitoso**:
```bash
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "tenant": "empresa demo",
    "usernameOrEmail": "admin@demo.local",
    "password": "admin123"
  }'
```

2. **Credenciales inválidas**:
```bash
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "tenant": "empresa demo",
    "usernameOrEmail": "admin@demo.local",
    "password": "wrongpassword"
  }'
```

3. **Tenant inválido**:
```bash
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "tenant": "empresa inexistente",
    "usernameOrEmail": "admin@demo.local",
    "password": "admin123"
  }'
```

## Frontend Integration

### Formulario de Login
- Campo **Empresa** (tenant)
- Campo **Usuario o Email**
- Campo **Contraseña** con toggle de visibilidad
- Validaciones en cliente
- Manejo de estados de carga y error
- Mensajes de error específicos para bloqueo

### Almacenamiento
- `accessToken`: Token JWT
- `currentUser`: Información del usuario
- `tenant`: Nombre de la empresa

## Consideraciones de Producción

1. **Cambiar clave JWT**: Usar clave de al menos 256 bits
2. **Hash de contraseñas**: Migrar a Argon2id o PBKDF2
3. **HTTPS**: Obligatorio en producción
4. **Rate limiting**: Configurar a nivel de infraestructura
5. **Logs**: Configurar logging estructurado
6. **Monitoreo**: Alertas para intentos fallidos masivos
