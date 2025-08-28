# ADR-002: Observabilidad con OpenTelemetry

## Estado
Aceptado

## Fecha
2024-12-19

## Contexto
SIGAV necesita un sistema de observabilidad completo para monitorear el rendimiento, debugging y métricas de negocio en producción. Actualmente solo tiene logging básico con Serilog, lo cual es insuficiente para operaciones en producción.

## Decisión
Implementar OpenTelemetry como estándar de observabilidad, incluyendo tracing distribuido, métricas y logs estructurados.

### Razones para la decisión:
- **Estándar abierto**: OpenTelemetry es el estándar de facto para observabilidad
- **Vendor agnostic**: Permite cambiar de proveedor sin cambiar código
- **Integración completa**: Tracing, métricas y logs en una sola solución
- **Ecosistema rico**: Muchos proveedores y herramientas compatibles
- **Performance**: Bajo overhead en producción

## Consecuencias

### Positivas:
- Observabilidad completa en producción
- Debugging distribuido eficiente
- Métricas de performance detalladas
- Flexibilidad para cambiar proveedores
- Cumplimiento de estándares de observabilidad

### Negativas:
- Complejidad inicial de configuración
- Overhead adicional (mínimo)
- Curva de aprendizaje para el equipo
- Costo de herramientas de visualización

## Implementación

### Configuración:
```csharp
services.AddOpenTelemetry()
    .WithTracing(tracerProviderBuilder =>
    {
        tracerProviderBuilder
            .AddAspNetCoreInstrumentation()
            .AddHttpClientInstrumentation()
            .AddEntityFrameworkCoreInstrumentation()
            .AddJaegerExporter();
    })
    .WithMetrics(meterProviderBuilder =>
    {
        meterProviderBuilder
            .AddAspNetCoreInstrumentation()
            .AddPrometheusExporter();
    });
```

### Instrumentación:
- **ASP.NET Core**: Requests, responses, exceptions
- **HTTP Client**: Outgoing requests
- **Entity Framework**: Database queries
- **Redis**: Cache operations
- **Custom**: Business metrics

### Exporters:
- **Jaeger**: Para tracing distribuido
- **Prometheus**: Para métricas
- **Console**: Para desarrollo local

## Alternativas Consideradas

### Application Insights
- Pros: Integración nativa con Azure, fácil configuración
- Contras: Vendor lock-in, costo alto, menos flexible

### Jaeger + Prometheus + Grafana
- Pros: Stack completo, open source
- Contras: Requiere más infraestructura, complejidad de mantenimiento

### Logging estructurado solo
- Pros: Simplicidad
- Contras: No proporciona tracing ni métricas

## Métricas Clave

### Performance:
- Request duration (p50, p95, p99)
- Throughput (requests/sec)
- Error rate
- Database query performance

### Business:
- Busetas por empresa
- Checklists completados
- Usuarios activos
- Custom fields utilizados

## Referencias
- [OpenTelemetry Documentation](https://opentelemetry.io/docs/)
- [ASP.NET Core Instrumentation](https://github.com/open-telemetry/opentelemetry-dotnet)
- [Jaeger Documentation](https://www.jaegertracing.io/docs/)
