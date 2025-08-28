using Azure.Identity;
using Azure.Security.KeyVault.Secrets;
using Microsoft.Extensions.Configuration;

namespace Sigav.Api.Services;

/// <summary>
/// Implementación del servicio de secrets con Azure Key Vault
/// </summary>
public class SecretsService : ISecretsService
{
    private readonly SecretClient? _secretClient;
    private readonly IConfiguration _configuration;
    private readonly ILogger<SecretsService> _logger;
    private readonly bool _useKeyVault;

    public SecretsService(IConfiguration configuration, ILogger<SecretsService> logger)
    {
        _configuration = configuration;
        _logger = logger;
        _useKeyVault = !string.IsNullOrEmpty(_configuration["KeyVault:Url"]);

        if (_useKeyVault)
        {
            try
            {
                var keyVaultUrl = _configuration["KeyVault:Url"];
                var credential = new DefaultAzureCredential();
                _secretClient = new SecretClient(new Uri(keyVaultUrl!), credential);
                _logger.LogInformation("Azure Key Vault client initialized successfully");
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Failed to initialize Azure Key Vault client, falling back to local configuration");
                _useKeyVault = false;
            }
        }
    }

    public async Task<string> GetSecretAsync(string secretName)
    {
        if (_useKeyVault && _secretClient != null)
        {
            try
            {
                var secret = await _secretClient.GetSecretAsync(secretName);
                return secret.Value.Value;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to retrieve secret {SecretName} from Key Vault", secretName);
                throw;
            }
        }

        throw new InvalidOperationException($"Secret {secretName} not found and no fallback available");
    }

    public async Task<string> GetSecretWithFallbackAsync(string secretName, string fallbackValue)
    {
        if (_useKeyVault && _secretClient != null)
        {
            try
            {
                var secret = await _secretClient.GetSecretAsync(secretName);
                _logger.LogDebug("Retrieved secret {SecretName} from Key Vault", secretName);
                return secret.Value.Value;
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Failed to retrieve secret {SecretName} from Key Vault, using fallback", secretName);
            }
        }

        _logger.LogDebug("Using fallback value for secret {SecretName}", secretName);
        return fallbackValue;
    }

    public async Task<bool> IsKeyVaultAvailableAsync()
    {
        if (!_useKeyVault || _secretClient == null)
            return false;

        try
        {
            // Intentar obtener un secret de prueba
            await _secretClient.GetSecretAsync("test-availability");
            return true;
        }
        catch
        {
            return false;
        }
    }
}
