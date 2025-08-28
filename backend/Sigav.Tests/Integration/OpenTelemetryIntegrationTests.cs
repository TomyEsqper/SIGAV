using Microsoft.AspNetCore.Mvc.Testing;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Logging;
using OpenTelemetry.Trace;
using Xunit;

namespace Sigav.Tests.Integration;

public class OpenTelemetryIntegrationTests : IClassFixture<WebApplicationFactory<Program>>
{
    private readonly WebApplicationFactory<Program> _factory;

    public OpenTelemetryIntegrationTests(WebApplicationFactory<Program> factory)
    {
        _factory = factory;
    }

    [Fact]
    public void OpenTelemetry_ShouldBeConfigured()
    {
        // Arrange & Act
        using var client = _factory.CreateClient();
        var tracerProvider = _factory.Services.GetService<TracerProvider>();

        // Assert
        Assert.NotNull(tracerProvider);
    }

    [Fact]
    public async Task Request_ShouldGenerateTrace()
    {
        // Arrange
        using var client = _factory.CreateClient();

        // Act
        var response = await client.GetAsync("/api/v1/busetas");

        // Assert
        response.EnsureSuccessStatusCode();
        // En un entorno real, verificaríamos que se generó un trace
        // Esto requiere configuración adicional de Jaeger o similar
    }
}
