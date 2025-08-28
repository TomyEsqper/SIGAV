using Microsoft.Extensions.Caching.Distributed;
using System.Text.Json;

namespace Sigav.Application.Services;

/// <summary>
/// Implementación del servicio de cache con Redis
/// </summary>
public class CacheService : ICacheService
{
    private readonly IDistributedCache _cache;
    private readonly ILogger<CacheService> _logger;
    private readonly JsonSerializerOptions _jsonOptions;

    public CacheService(IDistributedCache cache, ILogger<CacheService> logger)
    {
        _cache = cache;
        _logger = logger;
        _jsonOptions = new JsonSerializerOptions
        {
            PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
            WriteIndented = false
        };
    }

    public async Task<T?> GetAsync<T>(string key)
    {
        try
        {
            var jsonValue = await _cache.GetStringAsync(key);
            if (string.IsNullOrEmpty(jsonValue))
            {
                _logger.LogDebug("Cache miss for key: {Key}", key);
                return default;
            }

            var value = JsonSerializer.Deserialize<T>(jsonValue, _jsonOptions);
            _logger.LogDebug("Cache hit for key: {Key}", key);
            return value;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error retrieving value from cache for key: {Key}", key);
            return default;
        }
    }

    public async Task SetAsync<T>(string key, T value, TimeSpan? expiration = null, string[]? tags = null)
    {
        try
        {
            var jsonValue = JsonSerializer.Serialize(value, _jsonOptions);
            var options = new DistributedCacheEntryOptions();

            if (expiration.HasValue)
            {
                options.AbsoluteExpirationRelativeToNow = expiration;
            }

            await _cache.SetStringAsync(key, jsonValue, options);

            // Almacenar tags para invalidación
            if (tags != null && tags.Length > 0)
            {
                await StoreTagsAsync(key, tags);
            }

            _logger.LogDebug("Cached value for key: {Key} with expiration: {Expiration}", key, expiration);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error setting value in cache for key: {Key}", key);
        }
    }

    public async Task RemoveAsync(string key)
    {
        try
        {
            await _cache.RemoveAsync(key);
            await RemoveTagsAsync(key);
            _logger.LogDebug("Removed cache entry for key: {Key}", key);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error removing cache entry for key: {Key}", key);
        }
    }

    public async Task InvalidateByTagAsync(string tag)
    {
        try
        {
            var tagKey = $"tag:{tag}";
            var keysJson = await _cache.GetStringAsync(tagKey);
            
            if (!string.IsNullOrEmpty(keysJson))
            {
                var keys = JsonSerializer.Deserialize<string[]>(keysJson, _jsonOptions);
                if (keys != null)
                {
                    foreach (var key in keys)
                    {
                        await _cache.RemoveAsync(key);
                    }
                    await _cache.RemoveAsync(tagKey);
                    _logger.LogInformation("Invalidated {Count} cache entries for tag: {Tag}", keys.Length, tag);
                }
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error invalidating cache by tag: {Tag}", tag);
        }
    }

    public async Task<T> GetOrSetAsync<T>(string key, Func<Task<T>> factory, TimeSpan? expiration = null, string[]? tags = null)
    {
        var cachedValue = await GetAsync<T>(key);
        if (cachedValue != null)
        {
            return cachedValue;
        }

        var value = await factory();
        await SetAsync(key, value, expiration, tags);
        return value;
    }

    private async Task StoreTagsAsync(string key, string[] tags)
    {
        foreach (var tag in tags)
        {
            var tagKey = $"tag:{tag}";
            var existingKeysJson = await _cache.GetStringAsync(tagKey);
            var existingKeys = string.IsNullOrEmpty(existingKeysJson) 
                ? new string[0] 
                : JsonSerializer.Deserialize<string[]>(existingKeysJson, _jsonOptions) ?? new string[0];

            var updatedKeys = existingKeys.Append(key).Distinct().ToArray();
            var updatedKeysJson = JsonSerializer.Serialize(updatedKeys, _jsonOptions);

            await _cache.SetStringAsync(tagKey, updatedKeysJson, new DistributedCacheEntryOptions
            {
                AbsoluteExpirationRelativeToNow = TimeSpan.FromDays(1)
            });
        }
    }

    private async Task RemoveTagsAsync(string key)
    {
        // Implementación simplificada - en producción se podría optimizar
        // buscando en todos los tags que contengan esta key
    }
}
