# Sigav.Tests

Esta carpeta contendrá el proyecto de pruebas unitarias e integración para la aplicación SIGAV.

## Estructura planificada:

- **Unit/**: Pruebas unitarias por capa
  - **Domain/**: Pruebas de entidades y lógica de negocio
  - **Application/**: Pruebas de casos de uso
  - **Infrastructure/**: Pruebas de repositorios y servicios
- **Integration/**: Pruebas de integración
  - **API/**: Pruebas de endpoints
  - **Database/**: Pruebas con base de datos real
- **Fixtures/**: Datos de prueba y configuraciones
- **Helpers/**: Utilidades para las pruebas

## Propósito:

El proyecto de pruebas asegura la calidad del código y la funcionalidad correcta de la aplicación. Incluye pruebas unitarias para validar la lógica individual y pruebas de integración para validar la interacción entre componentes.

## Tecnologías:

- xUnit como framework de pruebas
- Moq para mocking
- FluentAssertions para aserciones legibles
- TestContainers para pruebas de integración (opcional)
