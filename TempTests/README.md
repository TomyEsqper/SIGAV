# Sigav.Tests

Esta carpeta contendrá el proyecto de pruebas de SIGAV.

## Propósito

El proyecto de pruebas contendrá:
- Pruebas unitarias
- Pruebas de integración
- Pruebas de comportamiento (BDD)
- Mocks y fixtures de prueba
- Configuración de cobertura de código

## Estructura Futura

```
Sigav.Tests/
├── Unit/                # Pruebas unitarias
├── Integration/         # Pruebas de integración
├── Behavior/            # Pruebas de comportamiento
├── Fixtures/            # Datos de prueba y mocks
├── Helpers/             # Clases auxiliares para pruebas
└── Coverage/            # Configuración de cobertura
```

## Cuándo se implementará

Este proyecto se creará cuando se implemente la arquitectura limpia (Clean Architecture) 
y se quiera tener una cobertura de pruebas adecuada.

## Dependencias Futuras

- .NET 8
- xUnit o NUnit
- Moq (para mocks)
- FluentAssertions
- Microsoft.NET.Test.Sdk
- coverlet.collector
