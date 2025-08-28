# ADR-001: Gestión de Secrets con Azure Key Vault

## Estado
Aceptado

## Fecha
2024-12-19

## Contexto
El proyecto SIGAV requiere gestionar secrets de forma segura, incluyendo claves JWT, connection strings de base de datos, y otros datos sensibles. Actualmente estos valores están hardcodeados en archivos de configuración, lo cual representa un riesgo de seguridad significativo.

## Decisión
Implementar Azure Key Vault como solución principal para gestión de secrets, con fallback a configuración local para desarrollo.

### Razones para la decisión:
- **Seguridad**: Azure Key Vault proporciona encriptación en reposo y en tránsito
- **Rotación automática**: Permite rotación automática de secrets
- **Auditoría**: Proporciona logs detallados de acceso a secrets
- **Integración**: Se integra bien con Azure Managed Identity
- **Fallback**: Permite desarrollo local sin Key Vault

## Consecuencias

### Positivas:
- Mayor seguridad en producción
- Cumplimiento de estándares de seguridad
- Auditoría completa de acceso a secrets
- Rotación automática de claves

### Negativas:
- Complejidad adicional en configuración
- Dependencia de Azure Key Vault
- Costo adicional para el servicio
- Curva de aprendizaje para el equipo

## Implementación

### Configuración:
```json
{
  "KeyVault": {
    "Url": "https://sigav-keyvault.vault.azure.net/"
  }
}
```

### Uso:
```csharp
// Obtener secret con fallback
var jwtKey = await _secretsService.GetSecretWithFallbackAsync(
    "JwtKey", 
    "dev-secret-key-please-change"
);
```

### Migración:
1. Crear Azure Key Vault
2. Migrar secrets existentes
3. Actualizar configuración de aplicación
4. Probar fallback en desarrollo

## Alternativas Consideradas

### AWS Secrets Manager
- Pros: Similar funcionalidad, buena integración con AWS
- Contras: No estamos usando AWS, costo adicional

### HashiCorp Vault
- Pros: Multi-cloud, open source
- Contras: Requiere infraestructura adicional, complejidad de mantenimiento

### Variables de entorno
- Pros: Simplicidad
- Contras: No proporciona encriptación, difícil de auditar

## Referencias
- [Azure Key Vault Documentation](https://docs.microsoft.com/en-us/azure/key-vault/)
- [ASP.NET Core Configuration](https://docs.microsoft.com/en-us/aspnet/core/fundamentals/configuration/)
