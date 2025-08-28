# SIGAV - Sistema de Gestión de Vehículos

SIGAV es un sistema empresarial robusto para gestionar flotas (busetas, checklists, empleados) con soporte de campos personalizables por empresa, implementado con las mejores prácticas de arquitectura y seguridad.

## 🏗️ Arquitectura

- **Backend**: .NET 8 (ASP.NET Core Web API) con Clean Architecture
- **Frontend**: Angular 20 con Material Design
- **Base de datos**: PostgreSQL 17 con Entity Framework Core
- **Cache**: Redis 8 con estrategias avanzadas de invalidación
- **Observabilidad**: OpenTelemetry, Jaeger, Prometheus, Grafana
- **Seguridad**: Azure Key Vault, JWT, Rate Limiting
- **CI/CD**: GitHub Actions con quality gates
- **Containerización**: Docker Compose con servicios completos

## 🚀 Características Principales

### ✅ Seguridad Empresarial
- **Azure Key Vault** para gestión segura de secrets
- **JWT Authentication** con rotación automática de claves
- **Rate Limiting** por IP y endpoint
- **Validación robusta** con FluentValidation
- **CORS configurado** por entorno

### ✅ Observabilidad Completa
- **Tracing distribuido** con Jaeger
- **Métricas de performance** con Prometheus
- **Dashboards** con Grafana
- **Logs estructurados** con Serilog
- **Health checks** avanzados

### ✅ Performance Optimizada
- **Cache distribuido** con Redis
- **Índices optimizados** en base de datos
- **CQRS** con MediatR
- **Paginación** en endpoints de listado
- **Compresión** de respuestas

### ✅ API Versionada
- **Versionado semántico** de APIs
- **Compatibilidad hacia atrás**
- **Documentación automática** con Swagger
- **Deprecation warnings**

### ✅ Testing Completo
- **Tests unitarios** con xUnit y Moq
- **Tests de integración** con WebApplicationFactory
- **Tests de performance** con métricas
- **Cobertura de código** ≥ 80%

## 🛠️ Tecnologías

### Backend
- **.NET 8** - Framework principal
- **Entity Framework Core** - ORM
- **AutoMapper** - Mapeo de objetos
- **FluentValidation** - Validación
- **MediatR** - CQRS y Mediator Pattern
- **Serilog** - Logging estructurado
- **OpenTelemetry** - Observabilidad

### Frontend
- **Angular 20** - Framework SPA
- **Angular Material** - Componentes UI
- **RxJS** - Programación reactiva
- **TypeScript** - Tipado estático

### Infraestructura
- **PostgreSQL** - Base de datos principal
- **Redis** - Cache distribuido
- **Docker** - Containerización
- **Docker Compose** - Orquestación local

### Observabilidad
- **Jaeger** - Distributed tracing
- **Prometheus** - Métricas y alertas
- **Grafana** - Dashboards
- **Elasticsearch** - Logs centralizados
- **Kibana** - Visualización de logs

## 📦 Instalación y Configuración

### Requisitos Previos
- Docker Desktop
- Git
- 8GB RAM mínimo (para stack completo)

### Instalación Rápida

```bash
# Clonar repositorio
git clone <repository-url>
cd SIGAV

# Configurar variables de entorno
cp env.example .env
# Editar .env según necesidades

# Levantar stack completo
docker-compose up -d

# Verificar servicios
docker-compose ps
```

### URLs de Acceso

| Servicio | URL | Credenciales |
|----------|-----|--------------|
| **Frontend** | http://localhost:4200 | - |
| **API Swagger** | http://localhost:5000/swagger | - |
| **Grafana** | http://localhost:3000 | admin/admin123 |
| **Jaeger** | http://localhost:16686 | - |
| **Prometheus** | http://localhost:9090 | - |
| **Kibana** | http://localhost:5601 | - |
| **PgAdmin** | http://localhost:5050 | admin@sigav.com/admin123 |

## 🔧 Desarrollo

### Estructura del Proyecto

```
SIGAV/
├── backend/
│   ├── Sigav.Api/           # API principal
│   ├── Sigav.Application/   # Casos de uso
│   ├── Sigav.Domain/        # Entidades y lógica de negocio
│   ├── Sigav.Infrastructure/# Implementaciones técnicas
│   └── Sigav.Tests/         # Tests unitarios e integración
├── frontend/                # Aplicación Angular
├── docs/                    # Documentación
│   └── adr/                # Architecture Decision Records
├── monitoring/              # Configuración de observabilidad
└── docker-compose.yml       # Stack completo
```

### Comandos de Desarrollo

```bash
# Backend
dotnet restore backend/Sigav.Api/Sigav.Api.csproj
dotnet build backend/Sigav.Api/Sigav.Api.csproj
dotnet test backend/Sigav.Tests/Sigav.Tests.csproj

# Frontend
cd frontend
npm install
npm start

# Tests
npm run test:ci
npm run e2e:ci
```

## 🔒 Seguridad

### Gestión de Secrets
- **Azure Key Vault** en producción
- **Variables de entorno** en desarrollo
- **Rotación automática** de claves
- **Auditoría completa** de acceso

### Autenticación y Autorización
- **JWT tokens** con expiración configurable
- **Roles basados en claims** (Admin, Inspector, Mecánico)
- **Rate limiting** por IP y endpoint
- **Validación de entrada** robusta

## 📊 Observabilidad

### Métricas Clave
- **Performance**: Latencia p95 < 300ms, Throughput > 1000 req/sec
- **Disponibilidad**: Uptime > 99.9%
- **Errores**: Error rate < 1%
- **Negocio**: Busetas por empresa, Checklists completados

### Dashboards Disponibles
- **Performance Overview** - Métricas generales de la aplicación
- **Database Performance** - Consultas y conexiones
- **Cache Performance** - Hit ratio y latencia
- **Business Metrics** - Métricas de negocio

## 🚀 CI/CD

### Pipeline de Calidad
1. **Análisis de código** - SonarQube
2. **Tests unitarios** - Cobertura ≥ 80%
3. **Tests de integración** - Validación de endpoints
4. **Security scan** - Snyk vulnerability scan
5. **Build y package** - Docker images
6. **Deploy automático** - Staging y producción

### Quality Gates
- **Cobertura de tests**: ≥ 80%
- **Vulnerabilidades críticas**: 0
- **Duplicación de código**: < 3%
- **Performance**: p95 < 300ms

## 📚 Documentación

### ADRs (Architecture Decision Records)
- [ADR-001: Gestión de Secrets](docs/adr/ADR-001-secrets-management.md)
- [ADR-002: Observabilidad con OpenTelemetry](docs/adr/ADR-002-observability-opentelemetry.md)

### Guías
- [Getting Started](GETTING_STARTED.md) - Guía de inicio rápido
- [API Documentation](backend/Sigav.Api/README_CustomFields.md) - Documentación de APIs
- [Development Guide](docs/development-guide.md) - Guía de desarrollo

## 🤝 Contribución

### Proceso de Desarrollo
1. **Fork** del repositorio
2. **Feature branch** (`git checkout -b feature/amazing-feature`)
3. **Commit** de cambios (`git commit -m 'Add amazing feature'`)
4. **Push** al branch (`git push origin feature/amazing-feature`)
5. **Pull Request** con descripción detallada

### Estándares de Código
- **C#**: .NET 8, C# 12, nullable reference types
- **TypeScript**: Angular 20, strict mode
- **Tests**: xUnit, Moq, FluentAssertions
- **Documentación**: XML comments, READMEs

## 📈 Roadmap

### Fase 1 (Completado) ✅
- Arquitectura limpia implementada
- Seguridad básica con JWT
- Tests unitarios básicos
- Docker Compose básico

### Fase 2 (Completado) ✅
- Observabilidad completa
- Cache avanzado con Redis
- API versioning
- CI/CD pipeline

### Fase 3 (En Progreso) 🔄
- Microservicios
- Event sourcing
- Machine learning para predicciones
- Mobile app

### Fase 4 (Planificado) 📋
- Multi-tenancy avanzado
- Real-time notifications
- Advanced analytics
- Integration APIs

## 📞 Soporte

### Canales de Soporte
- **Issues**: GitHub Issues para bugs y features
- **Documentación**: Wiki del proyecto
- **Comunidad**: Discord/Slack (enlaces en Wiki)

### SLA
- **Critical bugs**: 4 horas
- **Major features**: 1 semana
- **Minor improvements**: 2 semanas

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

---

**SIGAV** - Transformando la gestión de flotas con tecnología de vanguardia 🚀
