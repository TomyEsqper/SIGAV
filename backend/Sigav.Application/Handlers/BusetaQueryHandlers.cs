using MediatR;
using Microsoft.Extensions.Caching.Distributed;
using System.Text.Json;
using Sigav.Application.Queries;
using Sigav.Application.Services;
using Sigav.Shared.DTOs;

namespace Sigav.Application.Handlers;

/// <summary>
/// Handler para obtener todas las busetas
/// </summary>
public class GetBusetasQueryHandler : IRequestHandler<GetBusetasQuery, IEnumerable<BusetaDto>>
{
    private readonly IBusetaService _busetaService;
    private readonly IDistributedCache _cache;
    private readonly ILogger<GetBusetasQueryHandler> _logger;

    public GetBusetasQueryHandler(
        IBusetaService busetaService,
        IDistributedCache cache,
        ILogger<GetBusetasQueryHandler> logger)
    {
        _busetaService = busetaService;
        _cache = cache;
        _logger = logger;
    }

    public async Task<IEnumerable<BusetaDto>> Handle(GetBusetasQuery request, CancellationToken cancellationToken)
    {
        var cacheKey = $"busetas_all_{request.EmpresaId ?? 0}";
        
        // Intentar obtener del cache
        var cachedData = await _cache.GetStringAsync(cacheKey, cancellationToken);
        if (!string.IsNullOrEmpty(cachedData))
        {
            _logger.LogInformation("Retrieved busetas from cache for key: {CacheKey}", cacheKey);
            return JsonSerializer.Deserialize<IEnumerable<BusetaDto>>(cachedData)!;
        }

        // Obtener de la base de datos
        var busetas = await _busetaService.GetAllAsync(request.EmpresaId);
        
        // Guardar en cache por 5 minutos
        var cacheOptions = new DistributedCacheEntryOptions
        {
            AbsoluteExpirationRelativeToNow = TimeSpan.FromMinutes(5)
        };
        
        await _cache.SetStringAsync(cacheKey, JsonSerializer.Serialize(busetas), cacheOptions, cancellationToken);
        
        _logger.LogInformation("Stored busetas in cache for key: {CacheKey}", cacheKey);
        
        return busetas;
    }
}

/// <summary>
/// Handler para obtener una buseta por ID
/// </summary>
public class GetBusetaByIdQueryHandler : IRequestHandler<GetBusetaByIdQuery, BusetaDto?>
{
    private readonly IBusetaService _busetaService;
    private readonly IDistributedCache _cache;
    private readonly ILogger<GetBusetaByIdQueryHandler> _logger;

    public GetBusetaByIdQueryHandler(
        IBusetaService busetaService,
        IDistributedCache cache,
        ILogger<GetBusetaByIdQueryHandler> logger)
    {
        _busetaService = busetaService;
        _cache = cache;
        _logger = logger;
    }

    public async Task<BusetaDto?> Handle(GetBusetaByIdQuery request, CancellationToken cancellationToken)
    {
        var cacheKey = $"buseta_{request.Id}";
        
        // Intentar obtener del cache
        var cachedData = await _cache.GetStringAsync(cacheKey, cancellationToken);
        if (!string.IsNullOrEmpty(cachedData))
        {
            _logger.LogInformation("Retrieved buseta from cache for key: {CacheKey}", cacheKey);
            return JsonSerializer.Deserialize<BusetaDto>(cachedData);
        }

        // Obtener de la base de datos
        var buseta = await _busetaService.GetByIdAsync(request.Id);
        
        if (buseta != null)
        {
            // Guardar en cache por 10 minutos
            var cacheOptions = new DistributedCacheEntryOptions
            {
                AbsoluteExpirationRelativeToNow = TimeSpan.FromMinutes(10)
            };
            
            await _cache.SetStringAsync(cacheKey, JsonSerializer.Serialize(buseta), cacheOptions, cancellationToken);
            
            _logger.LogInformation("Stored buseta in cache for key: {CacheKey}", cacheKey);
        }
        
        return buseta;
    }
}

/// <summary>
/// Handler para obtener busetas por empresa
/// </summary>
public class GetBusetasByEmpresaQueryHandler : IRequestHandler<GetBusetasByEmpresaQuery, IEnumerable<BusetaDto>>
{
    private readonly IBusetaService _busetaService;
    private readonly IDistributedCache _cache;
    private readonly ILogger<GetBusetasByEmpresaQueryHandler> _logger;

    public GetBusetasByEmpresaQueryHandler(
        IBusetaService busetaService,
        IDistributedCache cache,
        ILogger<GetBusetasByEmpresaQueryHandler> logger)
    {
        _busetaService = busetaService;
        _cache = cache;
        _logger = logger;
    }

    public async Task<IEnumerable<BusetaDto>> Handle(GetBusetasByEmpresaQuery request, CancellationToken cancellationToken)
    {
        var cacheKey = $"busetas_empresa_{request.EmpresaId}";
        
        // Intentar obtener del cache
        var cachedData = await _cache.GetStringAsync(cacheKey, cancellationToken);
        if (!string.IsNullOrEmpty(cachedData))
        {
            _logger.LogInformation("Retrieved busetas by empresa from cache for key: {CacheKey}", cacheKey);
            return JsonSerializer.Deserialize<IEnumerable<BusetaDto>>(cachedData)!;
        }

        // Obtener de la base de datos
        var busetas = await _busetaService.GetByEmpresaAsync(request.EmpresaId);
        
        // Guardar en cache por 5 minutos
        var cacheOptions = new DistributedCacheEntryOptions
        {
            AbsoluteExpirationRelativeToNow = TimeSpan.FromMinutes(5)
        };
        
        await _cache.SetStringAsync(cacheKey, JsonSerializer.Serialize(busetas), cacheOptions, cancellationToken);
        
        _logger.LogInformation("Stored busetas by empresa in cache for key: {CacheKey}", cacheKey);
        
        return busetas;
    }
}

/// <summary>
/// Handler para obtener campos personalizados de una buseta
/// </summary>
public class GetBusetaCustomFieldsQueryHandler : IRequestHandler<GetBusetaCustomFieldsQuery, IEnumerable<CustomFieldValueDto>>
{
    private readonly IBusetaService _busetaService;
    private readonly IDistributedCache _cache;
    private readonly ILogger<GetBusetaCustomFieldsQueryHandler> _logger;

    public GetBusetaCustomFieldsQueryHandler(
        IBusetaService busetaService,
        IDistributedCache cache,
        ILogger<GetBusetaCustomFieldsQueryHandler> logger)
    {
        _busetaService = busetaService;
        _cache = cache;
        _logger = logger;
    }

    public async Task<IEnumerable<CustomFieldValueDto>> Handle(GetBusetaCustomFieldsQuery request, CancellationToken cancellationToken)
    {
        var cacheKey = $"buseta_customfields_{request.BusetaId}";
        
        // Intentar obtener del cache
        var cachedData = await _cache.GetStringAsync(cacheKey, cancellationToken);
        if (!string.IsNullOrEmpty(cachedData))
        {
            _logger.LogInformation("Retrieved buseta custom fields from cache for key: {CacheKey}", cacheKey);
            return JsonSerializer.Deserialize<IEnumerable<CustomFieldValueDto>>(cachedData)!;
        }

        // Obtener de la base de datos
        var customFields = await _busetaService.GetCustomFieldsAsync(request.BusetaId);
        
        // Guardar en cache por 15 minutos
        var cacheOptions = new DistributedCacheEntryOptions
        {
            AbsoluteExpirationRelativeToNow = TimeSpan.FromMinutes(15)
        };
        
        await _cache.SetStringAsync(cacheKey, JsonSerializer.Serialize(customFields), cacheOptions, cancellationToken);
        
        _logger.LogInformation("Stored buseta custom fields in cache for key: {CacheKey}", cacheKey);
        
        return customFields;
    }
}
