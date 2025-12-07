# SIGAV - Sistema de Gestión de Alistamiento Vehicular
## Documentación Técnica Completa

---

## 📋 **ÍNDICE**
1. [Descripción General](#descripción-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Base de Datos](#base-de-datos)
4. [Módulos del Sistema](#módulos-del-sistema)
5. [Plan de Desarrollo Secuencial](#plan-de-desarrollo-secuencial)
6. [Especificaciones Técnicas](#especificaciones-técnicas)

---

## 🎯 **DESCRIPCIÓN GENERAL**

### **Objetivo:**
Sistema web para realizar revisiones preoperacionales digitales de una flota de buses, garantizando seguridad vial y cumplimiento normativo.

### **Usuarios del Sistema:**
- **👨‍💼 Administrador:** Gestión completa del sistema
- **👨‍🔧 Inspector:** Realización de alistamientos vehiculares

---

## 🏗️ **ARQUITECTURA DEL SISTEMA**

### **Stack Tecnológico:**
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
- **Backend:** PHP 8.x
- **Base de Datos:** MySQL (XAMPP)
- **Servidor:** Apache (XAMPP)
- **Librerías Adicionales:**
  - QR Code Generator (PHP)
  - Camera API (JavaScript)
  - PDF Generator (TCPDF/FPDF)

---

## 🗄️ **BASE DE DATOS - ESTRUCTURA**

### **Tablas Principales:**

#### **1. usuarios**
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- nombre (VARCHAR(100))
- email (VARCHAR(100), UNIQUE)
- password (VARCHAR(255))
- rol (ENUM: 'admin', 'inspector')
- activo (BOOLEAN, DEFAULT TRUE)
- fecha_creacion (TIMESTAMP)
```

#### **2. vehiculos**
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- numero_interno (VARCHAR(20), UNIQUE)
- placa (VARCHAR(10), UNIQUE)
- propietario (VARCHAR(100))
- estado (ENUM: 'activo', 'inactivo', 'mantenimiento')
- qr_code (TEXT)
- fecha_creacion (TIMESTAMP)
- fecha_actualizacion (TIMESTAMP)
```

#### **3. conductores**
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- nombre (VARCHAR(100))
- cedula (VARCHAR(20), UNIQUE)
- telefono (VARCHAR(15))
- activo (BOOLEAN, DEFAULT TRUE)
- fecha_creacion (TIMESTAMP)
```

#### **4. documentos**
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- vehiculo_id (INT, FOREIGN KEY)
- tipo_documento (ENUM: 'soat', 'rtm', 'tarjeta_operacion', 'extintor')
- fecha_vencimiento (DATE)
- archivo_url (VARCHAR(255))
- estado_vigencia (ENUM: 'verde', 'azul', 'amarillo', 'rojo')
- fecha_actualizacion (TIMESTAMP)
```

#### **5. categorias_checklist**
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- nombre (VARCHAR(100))
- descripcion (TEXT)
- orden (INT)
- activo (BOOLEAN, DEFAULT TRUE)
```

#### **6. items_checklist**
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- categoria_id (INT, FOREIGN KEY)
- nombre (VARCHAR(100))
- descripcion (TEXT)
- es_vital (BOOLEAN, DEFAULT FALSE)
- orden (INT)
- activo (BOOLEAN, DEFAULT TRUE)
```

#### **7. alistamientos**
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- vehiculo_id (INT, FOREIGN KEY)
- inspector_id (INT, FOREIGN KEY)
- estado_final (ENUM: 'verde', 'amarillo', 'rojo')
- es_alistamiento_parcial (BOOLEAN, DEFAULT FALSE)
- fecha_alistamiento (TIMESTAMP)
- observaciones_generales (TEXT)
```

#### **8. detalle_alistamiento**
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- alistamiento_id (INT, FOREIGN KEY)
- item_id (INT, FOREIGN KEY)
- estado (ENUM: 'ok', 'malo')
- observaciones (TEXT)
- foto_url (VARCHAR(255))
- fecha_revision (TIMESTAMP)
```

#### **9. vehiculos_detenidos**
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- vehiculo_id (INT, FOREIGN KEY)
- alistamiento_id (INT, FOREIGN KEY)
- motivo_detencion (TEXT)
- fecha_detencion (TIMESTAMP)
- fecha_liberacion (TIMESTAMP, NULL)
- estado (ENUM: 'detenido', 'liberado')
```

---

## 📱 **MÓDULOS DEL SISTEMA**

### **🔐 MÓDULO DE AUTENTICACIÓN**
- Login con email y contraseña
- Validación de roles (admin/inspector)
- Sesiones seguras
- Logout automático por inactividad

### **👨‍💼 MÓDULO ADMINISTRADOR**

#### **Dashboard Principal:**
- Estadísticas en tiempo real
- Gráficos de estado de flota
- Alertas de documentos por vencer
- Resumen de vehículos detenidos

#### **Gestión de Vehículos:**
- CRUD completo de vehículos
- Generación de códigos QR únicos
- Cambio de estados (activo/inactivo)
- Historial de alistamientos por vehículo

#### **Gestión de Conductores:**
- CRUD completo de conductores
- Información de contacto
- Estado activo/inactivo

#### **Gestión de Documentos:**
- Carga de documentos por vehículo
- Control de fechas de vencimiento
- Sistema de alertas por colores:
  - 🟢 Verde: +4 meses
  - 🔵 Azul: -4 meses
  - 🟡 Amarillo: -1 mes
  - 🔴 Rojo: Vencido

#### **Control de Alistamientos:**
- Vista por estados (Verde/Amarillo/Rojo)
- Detalle de fallas con fotografías
- Gestión de vehículos detenidos
- Seguimiento de reparaciones

#### **Sistema de Reportes:**
- Reportes por estado de vehículos
- Filtros por fechas (diario/mensual/personalizado)
- Exportación a PDF y Excel
- Estadísticas de inspectores

### **👨‍🔧 MÓDULO INSPECTOR**

#### **Selección de Vehículo:**
- Escaneo de código QR
- Ingreso manual de número interno
- Validación de vehículo en base de datos

#### **Alistamiento Completo:**
- Checklist organizado por categorías
- Evaluación ítem por ítem (OK/Malo)
- Fotografía obligatoria para ítems malos
- Campo de observaciones

#### **Alistamiento Parcial:**
- Solo ítems previamente marcados como malos
- Nueva fotografía para evidenciar reparación
- Comparación antes/después

#### **Finalización:**
- Cálculo automático de estado (Verde/Amarillo/Rojo)
- Detención automática si hay ítems vitales malos
- Registro en base de datos

---

## 📅 **PLAN DE DESARROLLO SECUENCIAL**

### **FASE 1: FUNDACIÓN (Semana 1)**
1. ✅ Crear base de datos 'sigavv' en XAMPP
2. ✅ Configurar estructura de tablas
3. ✅ Insertar datos de prueba
4. ✅ Configurar conexión PHP-MySQL

### **FASE 2: AUTENTICACIÓN (Semana 1)**
1. 🔄 Sistema de login
2. 🔄 Validación de roles
3. 🔄 Gestión de sesiones
4. 🔄 Páginas de redirección

### **FASE 3: MÓDULO ADMIN - BÁSICO (Semana 2)**
1. 🔄 Dashboard principal
2. 🔄 CRUD de usuarios
3. 🔄 CRUD de vehículos
4. 🔄 Generación de códigos QR

### **FASE 4: MÓDULO ADMIN - AVANZADO (Semana 3)**
1. 🔄 CRUD de conductores
2. 🔄 Gestión de documentos
3. 🔄 Sistema de alertas de vencimiento
4. 🔄 Configuración de checklist

### **FASE 5: MÓDULO INSPECTOR (Semana 4)**
1. 🔄 Interfaz de selección de vehículo
2. 🔄 Escaneo QR y cámara
3. 🔄 Checklist digital
4. 🔄 Sistema de fotografías

### **FASE 6: LÓGICA DE NEGOCIO (Semana 5)**
1. 🔄 Algoritmo de semáforo

---

## 🚀 **Despliegue y Acceso**

- Servidor: `Apache` en `XAMPP` con `PHP 8.2`.
- `DocumentRoot` y vhost SSL apuntan a `C:/Users/Cuervo/Desktop/sigavv`.
- URLs principales:
  - `https://localhost/login.php` (principal, HTTPS)
  - `http://localhost/login.php` redirige automáticamente a HTTPS
  - Módulos tras login según rol:
    - `admin/dashboard.php` (administrativo y talento humano)
    - `inspector/` (inspectores)
    - `inspector/camaras/` (inspector de cámaras)

### Servidor PHP embebido (opcional)
- Configurado sólo local: `127.0.0.1:8888` (no expuesto públicamente).
- No es necesario usarlo si se trabaja con Apache.

---

## 🔐 **Autenticación y Sesiones**

- Login en `login.php` acepta `usuario` o `email` según columnas disponibles.
- Verificación de contraseña con `bcrypt` (`password_verify`).
- Variables de sesión establecidas al iniciar sesión: `user_id`, `usuario`, `nombre`, `rol`, `last_activity`.
- Timeout de sesión: `SESSION_TIMEOUT = 3600` segundos.
- Helpers y verificación: `config/auth.php`:
  - `verificarAutenticacion`, `verificarSesion`, `verificarTimeout`, `verificarRol`.

---

## 👤 **Roles Disponibles**

- `admin`
- `inspector`
- `inspector_camaras`
- `revision_memorias` (si se instala módulo Evasión)

---

## 🧱 **Esquema de Usuarios (ajustes)**

- Tabla `usuarios` extendida para despliegue:
  - `usuario VARCHAR(100) UNIQUE` (añadida si faltaba)
  - `rol ENUM(...)` ampliado para incluir `inspector_camaras`
  - `activo TINYINT(1) DEFAULT 1` (añadida si faltaba)
- El login detecta y usa `usuario` o `email` dinámicamente.

---

## 🧩 **Módulos adicionales**

- **Cámaras** (`inspector_camaras`):
  - Script: `migraciones/instalar_modulo_camaras.php` amplía `usuarios.rol` y crea usuario inicial `lucho` si falta.
  - Tablas: `camaras_inspecciones`, `camaras_inspeccion_detalle`, evidencias.

- **Evasión / Revisión de Memorias** (`revision_memorias`):
  - Script: `migraciones/instalar_modulo_evasion.php` amplía `usuarios.rol` y crea usuario inicial `revision` si falta.
  - Tablas: `evasion_inspecciones`, `evasion_detalle`.

---

## 🛠️ **Scripts de Migración y Utilidades**

- `migraciones/configurar_usuarios.php`:
  - Asegura el esquema de `usuarios` (columnas y ENUM).
  - Configura exactamente los usuarios permitidos y elimina otros.
  - Idempotente; se puede ejecutar múltiples veces.
  - Ejecución: abrir `https://localhost/migraciones/configurar_usuarios.php`.

- `migraciones/instalar_modulo_camaras.php` y `migraciones/instalar_modulo_evasion.php`:
  - Amplían `usuarios.rol` y crean tablas de los módulos.

---

## 🔑 **Credenciales de Acceso (despliegue local)**

> Importante: estas credenciales son de entorno local. No exponer públicamente.

- Administrativo
  - `usuario=admin`
  - `password=cotrautol2025*`
  - `rol=admin`

- Talento Humano
  - `usuario=talento humano`
  - `password=talentohumano2025*`
  - `rol=admin`

- Inspectores
  - `usuario=inspector`
  - `password=cotrautol`
  - `rol=inspector`

- Inspector de Cámaras
  - `usuario=lucho`
  - `password=lamaquina`
  - `rol=inspector_camaras`

- Usuario Personal
  - `usuario=cuervo`
  - `password=crow`
  - `rol=admin`

---

## ✅ **Validación rápida de login (cURL)**

```bash
# Admin
curl -k -i -s -X POST https://localhost/login.php --data "usuario=admin&password=cotrautol2025*"
# Talento Humano
curl -k -i -s -X POST https://localhost/login.php --data-urlencode "usuario=talento humano" --data-urlencode "password=talentohumano2025*"
# Inspector
curl -k -i -s -X POST https://localhost/login.php --data "usuario=inspector&password=cotrautol"
# Inspector de Cámaras
curl -k -i -s -X POST https://localhost/login.php --data "usuario=lucho&password=lamaquina"
# Usuario Personal
curl -k -i -s -X POST https://localhost/login.php --data "usuario=cuervo&password=crow"
```

Cada uno debe responder `HTTP/1.1 302 Found` con `Location` hacia su módulo correspondiente.

---

## 🔒 **Seguridad y buenas prácticas**

- Mantener estas credenciales sólo en entorno local.
- Cambiar contraseñas al pasar a producción.
- Habilitar `DB_PASS` en `config/env.php` para MySQL en producción.
- Usar HTTPS siempre (`httpd-ssl.conf` configurado con `ServerName localhost:443`).

---

## ♻️ **Mantenimiento**

- Reiniciar Apache tras cambios en `httpd.conf` o `httpd-ssl.conf`.
- Respaldo de BD: exportar `sigavv` desde phpMyAdmin regularmente.
- Auditoría de sesiones y actividad: ver `config/auth.php` y tabla `log_actividades` si está habilitada.

2. 🔄 Detención automática de vehículos
3. 🔄 Alistamiento parcial
4. 🔄 Control de estados

### **FASE 7: REPORTES Y FINALIZACIÓN (Semana 6)**
1. 🔄 Sistema de reportes
2. 🔄 Exportación PDF
3. 🔄 Estadísticas avanzadas
4. 🔄 Pruebas finales

---

## ⚙️ **ESPECIFICACIONES TÉCNICAS**

### **Requisitos del Servidor:**
- PHP 8.0 o superior
- MySQL 5.7 o superior
- Apache 2.4 o superior
- Extensiones PHP: PDO, GD, mbstring

### **Requisitos del Cliente:**
- Navegador moderno (Chrome, Firefox, Safari, Edge)
- Cámara para escaneo QR y fotografías
- Conexión a internet estable

### **Seguridad:**
- Contraseñas hasheadas (password_hash)
- Validación de entrada de datos
- Protección contra SQL Injection
- Sesiones seguras con tokens CSRF

### **Performance:**
- Imágenes optimizadas y comprimidas
- Consultas SQL optimizadas
- Cache de datos frecuentes
- Paginación en listados largos

---

## 🚀 **PRÓXIMOS PASOS**

1. **Crear base de datos** con estructura definida
2. **Configurar entorno** de desarrollo
3. **Desarrollar autenticación** básica
4. **Implementar módulo admin** paso a paso
5. **Pruebas unitarias** de cada funcionalidad

---

## 🎥 **MÓDULO INSPECTOR DE CÁMARAS**

### **Objetivo**
- Realizar revisiones específicas del sistema de cámaras/MDVR por vehículo, usando el mismo QR del vehículo.

### **Rol y Acceso**
- Rol: `inspector_camaras` (credenciales iniciales: usuario `lucho`, contraseña `lamaquina`).
- Acceso: exclusivo al módulo de cámaras; redirección automática tras login.

### **Flujo de Inspección**
- **Inicio (foto obligatoria):** Captura en el momento (no desde galería) de la caja del MDVR abierta y/o pantalla conectada.
- **Checklist:** Ítems evaluables (OK/MALO) con evidencia obligatoria para MALO.
- **Cierre (foto obligatoria):** Captura en el momento de cómo quedó el sistema después de la revisión, más resumen y novedades.

### **Checklist Base (parametrizable por vehículo)**
- Cableado general y conexiones
- MDVR/DVR (estado físico y funcionamiento)
- Fusibles y protección
- Alimentación/voltaje
- Cámaras específicas (delantera, puerta, posterior, cabina u otras)

### **Novedades Registrables**
- Cambio de memoria
- Cambio de fusible
- Mantenimiento
- Reparación de cableado
- Observaciones adicionales (texto libre)
- Marcar si hubo manipulación por conductor (cámaras/DVR)

### **Evidencias y Reglas**
- Fotos obligatorias de Inicio y Cierre deben capturarse con la cámara (WebRTC), no seleccionar de galería.
- Evidencias por ítem MALO: foto o video (preferible captura directa; se permite `capture` para video/foto).
- Se almacenan en `uploads/evidencias/camaras/{inspeccion_id}/` con nombres únicos.

### **Datos y Tablas (nuevas)**
- `camaras_inspecciones`: id, vehiculo_id, inspector_id, fecha, estado_final, observaciones, foto_inicio_url, foto_fin_url, manipulado_conductor (BOOLEAN), tipo_novedad (ENUM), creado_en.
- `camaras_inspeccion_detalle`: id, inspeccion_id, item_key (VARCHAR), estado (ENUM 'ok','malo'), observaciones, creado_en.
- `camaras_evidencias`: id, detalle_id, archivo_url, tipo (ENUM 'foto','video'), creado_en.

### **Rutas y Vistas (módulo)**
- `inspector/camaras/index.php`: entrada por QR o número interno.
- `inspector/camaras/iniciar.php`: captura foto inicial (WebRTC) y creación de inspección.
- `inspector/camaras/checklist.php`: evaluación ítems y carga de evidencias.
- `inspector/camaras/cerrar.php`: captura foto final, novedades y resumen.
- `inspector/camaras/api/*`: endpoints para crear inspección y subir evidencias.

### **Permisos y Redirección**
- Login debe redirigir `inspector_camaras` a `inspector/camaras/`.
- Páginas de cámaras exigen rol `inspector_camaras` (o `admin`).

---

**Fecha de creación:** $(date)
**Versión:** 1.0
**Estado:** En desarrollo
