using MediatR;
using Microsoft.Extensions.Caching.Distributed;
using Sigav.Application.Commands;
using Sigav.Application.Services;
using Sigav.Shared.DTOs;

namespace Sigav.Application.Handlers;

/// <summary>
/// Handler para crear una nueva buseta
/// </summary>
public class CreateBusetaCommandHandler : IRequestHandler<CreateBusetaCommand, BusetaDto>
{
    private readonly IBusetaService _busetaService;
    private readonly IDistributedCache _cache;
    private readonly ILogger<CreateBusetaCommandHandler> _logger;

    public CreateBusetaCommandHandler(
        IBusetaService busetaService,
        IDistributedCache cache,
        ILogger<CreateBusetaCommandHandler> logger)
    {
        _busetaService = busetaService;
        _cache = cache;
        _logger = logger;
    }

    public async Task<BusetaDto> Handle(CreateBusetaCommand request, CancellationToken cancellationToken)
    {
        // Crear la buseta
        var createdBuseta = await _busetaService.CreateAsync(request.CreateDto);
        
        // Invalidar cache relacionado
        await InvalidateRelatedCache(createdBuseta.EmpresaId, cancellationToken);
        
        _logger.LogInformation("Created buseta with ID {Id} and invalidated related cache", createdBuseta.Id);
        
        return createdBuseta;
    }

    private async Task InvalidateRelatedCache(int empresaId, CancellationToken cancellationToken)
    {
        var cacheKeysToRemove = new[]
        {
            $"busetas_all_0",
            $"busetas_all_{empresaId}",
            $"busetas_empresa_{empresaId}"
        };

        foreach (var key in cacheKeysToRemove)
        {
            await _cache.RemoveAsync(key, cancellationToken);
            _logger.LogDebug("Invalidated cache key: {CacheKey}", key);
        }
    }
}

/// <summary>
/// Handler para actualizar una buseta existente
/// </summary>
public class UpdateBusetaCommandHandler : IRequestHandler<UpdateBusetaCommand, BusetaDto>
{
    private readonly IBusetaService _busetaService;
    private readonly IDistributedCache _cache;
    private readonly ILogger<UpdateBusetaCommandHandler> _logger;

    public UpdateBusetaCommandHandler(
        IBusetaService busetaService,
        IDistributedCache cache,
        ILogger<UpdateBusetaCommandHandler> logger)
    {
        _busetaService = busetaService;
        _cache = cache;
        _logger = logger;
    }

    public async Task<BusetaDto> Handle(UpdateBusetaCommand request, CancellationToken cancellationToken)
    {
        // Obtener la buseta actual para conocer la empresa
        var existingBuseta = await _busetaService.GetByIdAsync(request.Id);
        if (existingBuseta == null)
        {
            throw new InvalidOperationException($"Buseta con ID {request.Id} no encontrada");
        }

        // Actualizar la buseta
        await _busetaService.UpdateAsync(request.Id, request.UpdateDto);
        
        // Obtener la buseta actualizada
        var updatedBuseta = await _busetaService.GetByIdAsync(request.Id);
        
        // Invalidar cache relacionado
        await InvalidateRelatedCache(existingBuseta.EmpresaId, request.Id, cancellationToken);
        
        _logger.LogInformation("Updated buseta with ID {Id} and invalidated related cache", request.Id);
        
        return updatedBuseta!;
    }

    private async Task InvalidateRelatedCache(int empresaId, int busetaId, CancellationToken cancellationToken)
    {
        var cacheKeysToRemove = new[]
        {
            $"busetas_all_0",
            $"busetas_all_{empresaId}",
            $"busetas_empresa_{empresaId}",
            $"buseta_{busetaId}",
            $"buseta_customfields_{busetaId}"
        };

        foreach (var key in cacheKeysToRemove)
        {
            await _cache.RemoveAsync(key, cancellationToken);
            _logger.LogDebug("Invalidated cache key: {CacheKey}", key);
        }
    }
}

/// <summary>
/// Handler para eliminar una buseta
/// </summary>
public class DeleteBusetaCommandHandler : IRequestHandler<DeleteBusetaCommand, bool>
{
    private readonly IBusetaService _busetaService;
    private readonly IDistributedCache _cache;
    private readonly ILogger<DeleteBusetaCommandHandler> _logger;

    public DeleteBusetaCommandHandler(
        IBusetaService busetaService,
        IDistributedCache cache,
        ILogger<DeleteBusetaCommandHandler> logger)
    {
        _busetaService = busetaService;
        _cache = cache;
        _logger = logger;
    }

    public async Task<bool> Handle(DeleteBusetaCommand request, CancellationToken cancellationToken)
    {
        // Obtener la buseta para conocer la empresa
        var existingBuseta = await _busetaService.GetByIdAsync(request.Id);
        if (existingBuseta == null)
        {
            return false;
        }

        // Eliminar la buseta
        await _busetaService.DeleteAsync(request.Id);
        
        // Invalidar cache relacionado
        await InvalidateRelatedCache(existingBuseta.EmpresaId, request.Id, cancellationToken);
        
        _logger.LogInformation("Deleted buseta with ID {Id} and invalidated related cache", request.Id);
        
        return true;
    }

    private async Task InvalidateRelatedCache(int empresaId, int busetaId, CancellationToken cancellationToken)
    {
        var cacheKeysToRemove = new[]
        {
            $"busetas_all_0",
            $"busetas_all_{empresaId}",
            $"busetas_empresa_{empresaId}",
            $"buseta_{busetaId}",
            $"buseta_customfields_{busetaId}"
        };

        foreach (var key in cacheKeysToRemove)
        {
            await _cache.RemoveAsync(key, cancellationToken);
            _logger.LogDebug("Invalidated cache key: {CacheKey}", key);
        }
    }
}
