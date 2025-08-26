# Sigav.Domain

Esta carpeta contendrá las entidades del dominio y la lógica de negocio central de la aplicación SIGAV.

## Estructura planificada:

- **Entities/**: Entidades del dominio (Buseta, Checklist, Usuario, etc.)
- **Enums/**: Enumeraciones del dominio (EstadoBuseta, RolUsuario, etc.)
- **ValueObjects/**: Objetos de valor (Placa, Capacidad, etc.)
- **Interfaces/**: Contratos del dominio
- **Exceptions/**: Excepciones específicas del dominio

## Propósito:

El dominio contiene las reglas de negocio y entidades que son independientes de la infraestructura y la presentación. Aquí se define "qué" hace la aplicación, no "cómo" lo hace.
