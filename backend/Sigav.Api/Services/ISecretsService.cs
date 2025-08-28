namespace Sigav.Api.Services;

/// <summary>
/// Servicio para gestión segura de secrets
/// </summary>
public interface ISecretsService
{
    /// <summary>
    /// Obtiene un secret del Key Vault
    /// </summary>
    /// <param name="secretName">Nombre del secret</param>
    /// <returns>Valor del secret</returns>
    Task<string> GetSecretAsync(string secretName);
    
    /// <summary>
    /// Obtiene un secret con fallback a configuración local
    /// </summary>
    /// <param name="secretName">Nombre del secret</param>
    /// <param name="fallbackValue">Valor de fallback</param>
    /// <returns>Valor del secret o fallback</returns>
    Task<string> GetSecretWithFallbackAsync(string secretName, string fallbackValue);
    
    /// <summary>
    /// Verifica si el Key Vault está disponible
    /// </summary>
    /// <returns>True si está disponible</returns>
    Task<bool> IsKeyVaultAvailableAsync();
}
