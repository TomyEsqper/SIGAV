# Sigav.Infrastructure

Esta carpeta contendrá la capa de infraestructura que implementa el acceso a datos, servicios externos y configuraciones técnicas.

## Estructura planificada:

- **Data/**: Implementación de Entity Framework y repositorios
- **Services/**: Servicios externos (email, SMS, APIs, etc.)
- **Configuration/**: Configuraciones de la aplicación
- **Logging/**: Configuración de logging
- **Caching/**: Implementación de caché (Redis)
- **Security/**: Configuración de autenticación y autorización
- **Migrations/**: Migraciones de base de datos

## Propósito:

La capa de infraestructura implementa los detalles técnicos de "cómo" se accede a los datos y servicios externos. Aquí se implementan las interfaces definidas en el dominio y la aplicación.
