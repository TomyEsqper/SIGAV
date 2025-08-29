# 🛡️ Funcionalidades de Seguridad Avanzada

## 📋 Resumen

Este documento describe las funcionalidades de seguridad avanzada implementadas en SIGAV para proteger las cuentas de usuario y detectar actividades sospechosas.

## 🔐 Funcionalidades Implementadas

### 1. **Verificación de IP**
- **Bloqueo automático**: Las IPs que realizan múltiples intentos fallidos de login son bloqueadas automáticamente
- **Tiempo de bloqueo**: 30 minutos por defecto (configurable)
- **Escalado**: El tiempo de bloqueo aumenta con cada intento fallido
- **Desbloqueo automático**: Las IPs se desbloquean automáticamente después del tiempo configurado

### 2. **Detección de Dispositivos**
- **Registro automático**: Cada dispositivo nuevo se registra automáticamente
- **Identificación**: Se detecta el tipo de dispositivo (desktop, mobile, tablet) y navegador
- **Ubicación**: Se registra la ubicación geográfica del dispositivo (cuando está disponible)
- **Dispositivos confiables**: Los usuarios pueden marcar dispositivos como confiables
- **Notificaciones**: Se envían notificaciones cuando se detecta un dispositivo nuevo

### 3. **Notificaciones de Login**
- **Login exitoso**: Notificación cuando se inicia sesión desde un dispositivo no confiable
- **Nuevo dispositivo**: Notificación cuando se detecta un dispositivo nuevo
- **Actividad sospechosa**: Notificación cuando se detecta actividad sospechosa
- **Canales**: Email y SMS (configurable)

## 🗄️ Base de Datos

### Nuevas Tablas

#### `Dispositivos`
```sql
CREATE TABLE Dispositivos (
    Id SERIAL PRIMARY KEY,
    UsuarioId INTEGER NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Tipo VARCHAR(100) NOT NULL,
    UserAgent VARCHAR(100) NOT NULL,
    IpAddress VARCHAR(45) NOT NULL,
    Ubicacion VARCHAR(100),
    FechaRegistro TIMESTAMP NOT NULL,
    UltimoAcceso TIMESTAMP NOT NULL,
    EsConfiable BOOLEAN NOT NULL DEFAULT FALSE,
    Activo BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (UsuarioId) REFERENCES Usuarios(Id) ON DELETE CASCADE
);
```

#### `LogsSeguridad`
```sql
CREATE TABLE LogsSeguridad (
    Id SERIAL PRIMARY KEY,
    UsuarioId INTEGER,
    Tenant VARCHAR(100) NOT NULL,
    UsernameAttempted VARCHAR(100) NOT NULL,
    IpAddress VARCHAR(45) NOT NULL,
    UserAgent VARCHAR(500) NOT NULL,
    TipoEvento VARCHAR(50) NOT NULL,
    Resultado VARCHAR(20) NOT NULL,
    Detalles VARCHAR(500),
    Ubicacion VARCHAR(100),
    Timestamp TIMESTAMP NOT NULL,
    Jti VARCHAR(100),
    FOREIGN KEY (UsuarioId) REFERENCES Usuarios(Id) ON DELETE SET NULL
);
```

#### `IpsBloqueadas`
```sql
CREATE TABLE IpsBloqueadas (
    Id SERIAL PRIMARY KEY,
    IpAddress VARCHAR(45) NOT NULL,
    Razon VARCHAR(100) NOT NULL,
    FechaBloqueo TIMESTAMP NOT NULL,
    FechaExpiracion TIMESTAMP NOT NULL,
    Detalles VARCHAR(500),
    Activo BOOLEAN NOT NULL DEFAULT TRUE,
    IntentosFallidos INTEGER NOT NULL DEFAULT 0
);
```

## 🔧 Configuración

### Variables de Entorno

```bash
# Configuración de seguridad
AUTH_MAX_FAILED_ATTEMPTS=5
AUTH_ACCOUNT_LOCKOUT_MINUTES=15
AUTH_IP_BLOCK_MINUTES=30
AUTH_IP_BLOCK_THRESHOLD=10

# Notificaciones
NOTIFICATIONS_EMAIL_ENABLED=true
NOTIFICATIONS_SMS_ENABLED=false
NOTIFICATIONS_EMAIL_FROM=noreply@sigav.com
```

### Configuración en appsettings.json

```json
{
  "Security": {
    "MaxFailedAttempts": 5,
    "AccountLockoutMinutes": 15,
    "IpBlockMinutes": 30,
    "IpBlockThreshold": 10,
    "DeviceTrustRequired": false,
    "LocationTracking": true
  },
  "Notifications": {
    "EmailEnabled": true,
    "SmsEnabled": false,
    "EmailFrom": "noreply@sigav.com"
  }
}
```

## 🚀 API Endpoints

### Login Mejorado

**POST** `/api/auth/login`

**Request:**
```json
{
  "tenant": "empresa_demo",
  "usernameOrEmail": "admin@demo.local",
  "password": "admin123"
}
```

**Response:**
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
  "tenant": "empresa_demo",
  "isNewDevice": true,
  "deviceName": "Chrome en Windows"
}
```

## 📊 Logs y Auditoría

### Tipos de Eventos Registrados

- `login_exitoso`: Login exitoso
- `login_fallido`: Login fallido
- `dispositivo_nuevo`: Nuevo dispositivo detectado
- `ip_bloqueada`: IP bloqueada
- `actividad_sospechosa`: Actividad sospechosa detectada

### Resultados

- `ok`: Operación exitosa
- `fail`: Operación fallida
- `blocked`: Acceso bloqueado
- `suspicious`: Actividad sospechosa

## 🔍 Monitoreo y Alertas

### Métricas Clave

- **Intentos fallidos por IP**: Monitoreo de IPs sospechosas
- **Dispositivos nuevos**: Detección de dispositivos no reconocidos
- **Logins desde ubicaciones inusuales**: Alertas geográficas
- **Patrones de uso anómalos**: Detección de comportamiento sospechoso

### Alertas Automáticas

1. **Múltiples intentos fallidos**: Bloqueo automático de IP
2. **Nuevo dispositivo**: Notificación al usuario
3. **Login desde ubicación inusual**: Notificación de seguridad
4. **Actividad sospechosa**: Bloqueo temporal y notificación

## 🛠️ Implementación Técnica

### Servicios

#### `ISecurityService`
```csharp
public interface ISecurityService
{
    // Verificación de IP
    Task<bool> IsIpBlockedAsync(string ipAddress);
    Task BlockIpAsync(string ipAddress, string reason, int minutes = 30);
    Task UnblockIpAsync(string ipAddress);
    
    // Detección de dispositivos
    Task<Dispositivo?> GetDeviceAsync(int userId, string userAgent, string ipAddress);
    Task<Dispositivo> RegisterDeviceAsync(int userId, string userAgent, string ipAddress, string? location = null);
    Task UpdateDeviceLastAccessAsync(int deviceId);
    Task<bool> IsDeviceTrustedAsync(int userId, string userAgent, string ipAddress);
    
    // Logs de seguridad
    Task LogSecurityEventAsync(string tenant, string usernameAttempted, string ipAddress, 
        string userAgent, string tipoEvento, string resultado, int? userId = null, 
        string? detalles = null, string? ubicacion = null, string? jti = null);
    
    // Notificaciones
    Task SendLoginNotificationAsync(Usuario usuario, string ipAddress, string userAgent, string? location = null);
    Task SendNewDeviceNotificationAsync(Usuario usuario, Dispositivo dispositivo);
    Task SendSuspiciousActivityNotificationAsync(Usuario usuario, string ipAddress, string reason);
}
```

### Flujo de Login Mejorado

1. **Validación de IP**: Verificar si la IP está bloqueada
2. **Autenticación**: Validar credenciales
3. **Detección de dispositivo**: Identificar si es un dispositivo conocido
4. **Registro de dispositivo**: Registrar dispositivo nuevo si es necesario
5. **Notificaciones**: Enviar notificaciones según el caso
6. **Logs**: Registrar evento de seguridad
7. **Respuesta**: Devolver token con información adicional

## 🔮 Próximas Mejoras

### Funcionalidades Planificadas

1. **Autenticación de dos factores (2FA)**
   - SMS/Email
   - TOTP (Google Authenticator)
   - Backup codes

2. **Gestión de sesiones**
   - Sesiones múltiples
   - Cerrar sesión remota
   - Historial de sesiones

3. **Análisis de comportamiento**
   - Machine Learning para detectar patrones anómalos
   - Score de riesgo por sesión
   - Alertas inteligentes

4. **Integración con servicios externos**
   - Geolocalización avanzada
   - Verificación de IP (VPN/Tor detection)
   - Threat intelligence feeds

## 📝 Notas de Implementación

### Consideraciones de Seguridad

- **Privacidad**: La ubicación se almacena de forma opcional
- **Consentimiento**: Los usuarios deben consentir el tracking de dispositivos
- **Retención**: Los logs se mantienen por un período limitado
- **Acceso**: Solo administradores pueden acceder a logs detallados

### Performance

- **Índices**: Se han creado índices optimizados para consultas frecuentes
- **Caching**: Los dispositivos confiables se cachean para mejorar performance
- **Async**: Todas las operaciones son asíncronas para no bloquear el login

### Escalabilidad

- **Rate limiting**: Implementado a nivel de IP
- **Distributed caching**: Preparado para Redis
- **Microservices**: Arquitectura preparada para separación de servicios
