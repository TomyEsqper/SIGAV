# Sistema de Campos Personalizables - SIGAV

## 🎯 **¿Qué es el Sistema de Campos Personalizables?**

Es un sistema flexible que permite a **cada empresa** agregar sus propios campos personalizados a cualquier entidad del sistema (Busetas, Empleados, Checklists, etc.) sin necesidad de modificar el código base.

## 🏗️ **Arquitectura del Sistema**

### **Entidades Principales:**

1. **`CustomField`** - Define un campo personalizable
2. **`CustomFieldValue`** - Almacena el valor de un campo personalizable para una entidad específica
3. **`Empresa`** - Cada empresa tiene sus propios campos personalizados

### **Relaciones:**
```
Empresa (1) ←→ (N) CustomField
CustomField (1) ←→ (N) CustomFieldValue
Entidad (Buseta/Usuario/etc.) (1) ←→ (N) CustomFieldValue
```

## 📝 **Tipos de Campos Soportados**

| Tipo | Descripción | Ejemplo |
|------|-------------|---------|
| **Text** | Campo de texto libre | "Color preferido del conductor" |
| **Number** | Campo numérico | "Años de experiencia" |
| **Date** | Campo de fecha | "Fecha de última revisión médica" |
| **Boolean** | Campo verdadero/falso | "¿Tiene licencia especial?" |
| **Select** | Campo de selección múltiple | "Tipo de combustible preferido" |

## 🚀 **Cómo Usar el Sistema**

### **1. Crear una Empresa**
```json
POST /api/Empresas
{
  "nombre": "Transportes ABC",
  "nit": "12345678-9",
  "direccion": "Calle 123 #45-67",
  "telefono": "300-123-4567",
  "email": "info@transportesabc.com"
}
```

### **2. Agregar Campos Personalizados a Busetas**
```json
POST /api/CustomFields
{
  "nombre": "Tipo de Ruta",
  "descripcion": "Tipo de ruta que puede manejar la buseta",
  "tipo": "Select",
  "opciones": "Urbana|Interurbana|Rural|Mixta",
  "entidad": "Buseta",
  "empresaId": 1,
  "requerido": true,
  "orden": 1
}
```

### **3. Agregar Campos Personalizados a Empleados**
```json
POST /api/CustomFields
{
  "nombre": "Licencias Especiales",
  "descripcion": "Licencias adicionales que posee el conductor",
  "tipo": "Text",
  "entidad": "Usuario",
  "empresaId": 1,
  "requerido": false,
  "orden": 2
}
```

### **4. Crear una Buseta con Campos Personalizados**
```json
POST /api/Busetas
{
  "placa": "ABC123",
  "marca": "Mercedes",
  "modelo": "Sprinter",
  "ano": 2020,
  "capacidad": 15,
  "empresaId": 1
}
```

### **5. Asignar Valores a los Campos Personalizados**
```json
POST /api/CustomFieldValues
{
  "customFieldId": 1,
  "entidad": "Buseta",
  "entidadId": 1,
  "valor": "Interurbana"
}
```

## 🔍 **Consultas Disponibles**

### **Obtener Campos Personalizados de una Empresa:**
```
GET /api/CustomFields?empresaId=1&entidad=Buseta
```

### **Obtener Valores de Campos Personalizados de una Buseta:**
```
GET /api/Busetas/1/custom-fields
```

### **Obtener Busetas con Campos Personalizados:**
```
GET /api/Busetas?empresaId=1
```

## 💡 **Casos de Uso Reales**

### **Empresa de Transporte Escolar:**
- **Campo**: "Capacidad de Sillas Infantiles"
- **Tipo**: Number
- **Valor**: 5

### **Empresa de Transporte de Carga:**
- **Campo**: "Tipo de Carga Permitida"
- **Tipo**: Select
- **Opciones**: "Secos|Refrigerados|Peligrosos|Fragiles"

### **Empresa de Transporte Ejecutivo:**
- **Campo**: "Nivel de Confort"
- **Tipo**: Select
- **Opciones**: "Básico|Premium|Lujo"

## 🛠️ **Implementación Técnica**

### **Base de Datos:**
- **Tabla `CustomFields`**: Almacena la definición de campos
- **Tabla `CustomFieldValues`**: Almacena los valores específicos
- **Relaciones**: Claves foráneas a `Empresa` y entidades

### **API Endpoints:**
- **CRUD completo** para campos personalizados
- **CRUD completo** para valores de campos
- **Consultas filtradas** por empresa y entidad
- **Operaciones en lote** para múltiples campos

### **Validaciones:**
- Campos requeridos según configuración
- Tipos de datos según el tipo de campo
- Opciones válidas para campos de selección

## 🔮 **Ventajas del Sistema**

### **Para Empresas:**
✅ **Flexibilidad total** - Agregan los campos que necesitan
✅ **Sin dependencia** - No esperan actualizaciones del software
✅ **Personalización** - Adaptan el sistema a sus procesos

### **Para Desarrolladores:**
✅ **Código limpio** - No hay campos hardcodeados
✅ **Escalabilidad** - Fácil agregar nuevos tipos de campos
✅ **Mantenimiento** - Cambios sin tocar el código base

## 📋 **Ejemplo Completo de Implementación**

### **Paso 1: Configurar Empresa**
```bash
# Crear empresa
curl -X POST "http://localhost:5000/api/Empresas" \
  -H "Content-Type: application/json" \
  -d '{"nombre": "Mi Empresa", "nit": "123-456"}'
```

### **Paso 2: Agregar Campo Personalizado**
```bash
# Agregar campo para busetas
curl -X POST "http://localhost:5000/api/CustomFields" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Zona de Operación",
    "tipo": "Select",
    "opciones": "Norte|Sur|Este|Oeste",
    "entidad": "Buseta",
    "empresaId": 1,
    "requerido": true
  }'
```

### **Paso 3: Crear Buseta**
```bash
# Crear buseta
curl -X POST "http://localhost:5000/api/Busetas" \
  -H "Content-Type: application/json" \
  -d '{
    "placa": "XYZ789",
    "marca": "Toyota",
    "modelo": "Hiace",
    "ano": 2021,
    "capacidad": 12,
    "empresaId": 1
  }'
```

### **Paso 4: Asignar Valor Personalizado**
```bash
# Asignar zona de operación
curl -X POST "http://localhost:5000/api/CustomFieldValues" \
  -H "Content-Type: application/json" \
  -d '{
    "customFieldId": 1,
    "entidad": "Buseta",
    "entidadId": 1,
    "valor": "Norte"
  }'
```

## 🎉 **¡Resultado Final!**

Ahora tienes una buseta con un campo personalizado "Zona de Operación" con valor "Norte" que solo existe para tu empresa y que puedes usar en reportes, filtros y consultas.

---

**¿Necesitas ayuda para implementar algún caso específico?** ¡El sistema está diseñado para ser flexible y fácil de usar!
