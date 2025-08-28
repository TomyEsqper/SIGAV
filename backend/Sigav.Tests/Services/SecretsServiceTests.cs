using Moq;
using Microsoft.Extensions.Configuration;
using Sigav.Api.Services;
using Xunit;

namespace Sigav.Tests.Services;

public class SecretsServiceTests
{
    private readonly Mock<IConfiguration> _mockConfiguration;
    private readonly Mock<ILogger<SecretsService>> _mockLogger;
    private readonly SecretsService _service;

    public SecretsServiceTests()
    {
        _mockConfiguration = new Mock<IConfiguration>();
        _mockLogger = new Mock<ILogger<SecretsService>>();
        
        // Configurar para usar configuración local (sin Key Vault)
        _mockConfiguration.Setup(x => x["KeyVault:Url"]).Returns((string?)null);
        
        _service = new SecretsService(_mockConfiguration.Object, _mockLogger.Object);
    }

    [Fact]
    public async Task GetSecretWithFallbackAsync_WhenKeyVaultNotConfigured_ShouldReturnFallbackValue()
    {
        // Arrange
        var secretName = "test-secret";
        var fallbackValue = "fallback-value";

        // Act
        var result = await _service.GetSecretWithFallbackAsync(secretName, fallbackValue);

        // Assert
        Assert.Equal(fallbackValue, result);
    }

    [Fact]
    public async Task IsKeyVaultAvailableAsync_WhenKeyVaultNotConfigured_ShouldReturnFalse()
    {
        // Act
        var result = await _service.IsKeyVaultAvailableAsync();

        // Assert
        Assert.False(result);
    }

    [Fact]
    public async Task GetSecretAsync_WhenKeyVaultNotConfigured_ShouldThrowException()
    {
        // Arrange
        var secretName = "test-secret";

        // Act & Assert
        await Assert.ThrowsAsync<InvalidOperationException>(
            () => _service.GetSecretAsync(secretName));
    }
}
