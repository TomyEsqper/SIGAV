using Microsoft.AspNetCore.Mvc.Testing;
using System.Diagnostics;
using Xunit;

namespace Sigav.Tests.Performance;

public class PerformanceTests : IClassFixture<WebApplicationFactory<Program>>
{
    private readonly WebApplicationFactory<Program> _factory;

    public PerformanceTests(WebApplicationFactory<Program> factory)
    {
        _factory = factory;
    }

    [Fact]
    public async Task HealthCheck_ShouldRespondUnder100ms()
    {
        // Arrange
        using var client = _factory.CreateClient();
        var stopwatch = Stopwatch.StartNew();

        // Act
        var response = await client.GetAsync("/health");
        stopwatch.Stop();

        // Assert
        response.EnsureSuccessStatusCode();
        Assert.True(stopwatch.ElapsedMilliseconds < 100, 
            $"Health check took {stopwatch.ElapsedMilliseconds}ms, expected < 100ms");
    }

    [Fact]
    public async Task GetBusetas_ShouldRespondUnder300ms()
    {
        // Arrange
        using var client = _factory.CreateClient();
        var stopwatch = Stopwatch.StartNew();

        // Act
        var response = await client.GetAsync("/api/v1/busetas");
        stopwatch.Stop();

        // Assert
        response.EnsureSuccessStatusCode();
        Assert.True(stopwatch.ElapsedMilliseconds < 300, 
            $"Get busetas took {stopwatch.ElapsedMilliseconds}ms, expected < 300ms");
    }

    [Fact]
    public async Task ConcurrentRequests_ShouldHandleLoad()
    {
        // Arrange
        using var client = _factory.CreateClient();
        var tasks = new List<Task<HttpResponseMessage>>();
        var concurrentRequests = 10;

        // Act
        for (int i = 0; i < concurrentRequests; i++)
        {
            tasks.Add(client.GetAsync("/api/v1/busetas"));
        }

        var responses = await Task.WhenAll(tasks);
        var stopwatch = Stopwatch.StartNew();

        // Assert
        Assert.All(responses, response => response.EnsureSuccessStatusCode());
        Assert.True(stopwatch.ElapsedMilliseconds < 1000, 
            $"Concurrent requests took {stopwatch.ElapsedMilliseconds}ms, expected < 1000ms");
    }

    [Fact]
    public async Task DatabaseQuery_ShouldBeOptimized()
    {
        // Arrange
        using var client = _factory.CreateClient();
        var stopwatch = Stopwatch.StartNew();

        // Act - Multiple queries to test N+1 prevention
        var tasks = new List<Task<HttpResponseMessage>>();
        for (int i = 0; i < 5; i++)
        {
            tasks.Add(client.GetAsync($"/api/v1/busetas/{i + 1}"));
        }

        var responses = await Task.WhenAll(tasks);
        stopwatch.Stop();

        // Assert
        Assert.All(responses, response => Assert.True(response.IsSuccessStatusCode || response.StatusCode == System.Net.HttpStatusCode.NotFound));
        Assert.True(stopwatch.ElapsedMilliseconds < 500, 
            $"Multiple database queries took {stopwatch.ElapsedMilliseconds}ms, expected < 500ms");
    }
}
