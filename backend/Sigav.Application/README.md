# Sigav.Application

Esta carpeta contendrá la capa de aplicación que orquesta los casos de uso y coordina entre el dominio y la infraestructura.

## Estructura planificada:

- **UseCases/**: Casos de uso de la aplicación (CrearBuseta, EjecutarChecklist, etc.)
- **Commands/**: Comandos para operaciones de escritura
- **Queries/**: Consultas para operaciones de lectura
- **DTOs/**: Objetos de transferencia de datos
- **Interfaces/**: Contratos para servicios externos
- **Validators/**: Validaciones de entrada
- **Mappers/**: Mapeo entre entidades y DTOs

## Propósito:

La capa de aplicación implementa los casos de uso específicos del negocio, coordinando las entidades del dominio y los servicios de infraestructura. Aquí se define "cómo" se implementan las funcionalidades del negocio.
