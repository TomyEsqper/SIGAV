using Microsoft.Extensions.Caching.Distributed;
using Microsoft.Extensions.DependencyInjection;
using Sigav.Application.Services;
using Xunit;

namespace Sigav.Tests.Integration;

public class CacheServiceIntegrationTests : IClassFixture<WebApplicationFactory<Program>>
{
    private readonly WebApplicationFactory<Program> _factory;
    private readonly ICacheService _cacheService;

    public CacheServiceIntegrationTests(WebApplicationFactory<Program> factory)
    {
        _factory = factory;
        var scope = factory.Services.CreateScope();
        _cacheService = scope.ServiceProvider.GetRequiredService<ICacheService>();
    }

    [Fact]
    public async Task SetAndGet_ShouldWorkCorrectly()
    {
        // Arrange
        var key = "test-key";
        var value = new { id = 1, name = "test" };

        // Act
        await _cacheService.SetAsync(key, value, TimeSpan.FromMinutes(5));
        var retrieved = await _cacheService.GetAsync<object>(key);

        // Assert
        Assert.NotNull(retrieved);
    }

    [Fact]
    public async Task GetOrSet_ShouldUseFactoryWhenNotCached()
    {
        // Arrange
        var key = "test-getorset";
        var expectedValue = "factory-value";
        var factoryCalled = false;

        // Act
        var result = await _cacheService.GetOrSetAsync(key, async () =>
        {
            factoryCalled = true;
            return expectedValue;
        });

        // Assert
        Assert.Equal(expectedValue, result);
        Assert.True(factoryCalled);
    }

    [Fact]
    public async Task GetOrSet_ShouldUseCachedValueWhenAvailable()
    {
        // Arrange
        var key = "test-getorset-cached";
        var cachedValue = "cached-value";
        await _cacheService.SetAsync(key, cachedValue);

        var factoryCalled = false;

        // Act
        var result = await _cacheService.GetOrSetAsync(key, async () =>
        {
            factoryCalled = true;
            return "new-value";
        });

        // Assert
        Assert.Equal(cachedValue, result);
        Assert.False(factoryCalled);
    }

    [Fact]
    public async Task Remove_ShouldDeleteCachedValue()
    {
        // Arrange
        var key = "test-remove";
        var value = "test-value";
        await _cacheService.SetAsync(key, value);

        // Act
        await _cacheService.RemoveAsync(key);
        var retrieved = await _cacheService.GetAsync<string>(key);

        // Assert
        Assert.Null(retrieved);
    }
}
