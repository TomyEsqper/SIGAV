using AutoMapper;
using Sigav.Domain;
using Sigav.Domain.Repositories;

namespace Sigav.Application.Services;

/// <summary>
/// Implementación base para todos los servicios de la aplicación
/// </summary>
public abstract class BaseService<TEntity, TDto, TCreateDto, TUpdateDto> : IBaseService<TEntity, TDto, TCreateDto, TUpdateDto>
    where TEntity : BaseEntity
    where TDto : class
    where TCreateDto : class
    where TUpdateDto : class
{
    protected readonly IRepository<TEntity> _repository;
    protected readonly IMapper _mapper;
    protected readonly ILogger<BaseService<TEntity, TDto, TCreateDto, TUpdateDto>> _logger;

    protected BaseService(
        IRepository<TEntity> repository,
        IMapper mapper,
        ILogger<BaseService<TEntity, TDto, TCreateDto, TUpdateDto>> logger)
    {
        _repository = repository;
        _mapper = mapper;
        _logger = logger;
    }

    public virtual async Task<IEnumerable<TDto>> GetAllAsync(int? empresaId = null)
    {
        try
        {
            var entities = empresaId.HasValue
                ? await GetByEmpresaAsync(empresaId.Value)
                : await _repository.GetAllAsync();

            return _mapper.Map<IEnumerable<TDto>>(entities);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting all {EntityName}", typeof(TEntity).Name);
            throw;
        }
    }

    public virtual async Task<TDto?> GetByIdAsync(int id)
    {
        try
        {
            var entity = await _repository.GetByIdAsync(id);
            return _mapper.Map<TDto>(entity);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting {EntityName} with id {Id}", typeof(TEntity).Name, id);
            throw;
        }
    }

    public virtual async Task<TDto> CreateAsync(TCreateDto createDto)
    {
        try
        {
            await ValidateCreateAsync(createDto);

            var entity = _mapper.Map<TEntity>(createDto);
            var createdEntity = await _repository.AddAsync(entity);
            
            _logger.LogInformation("Created {EntityName} with id {Id}", typeof(TEntity).Name, createdEntity.Id);
            
            return _mapper.Map<TDto>(createdEntity);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error creating {EntityName}", typeof(TEntity).Name);
            throw;
        }
    }

    public virtual async Task UpdateAsync(int id, TUpdateDto updateDto)
    {
        try
        {
            await ValidateUpdateAsync(id, updateDto);

            var existingEntity = await _repository.GetByIdAsync(id);
            if (existingEntity == null)
                throw new NotFoundException($"{typeof(TEntity).Name} con ID {id} no encontrado");

            _mapper.Map(updateDto, existingEntity);
            await _repository.UpdateAsync(existingEntity);
            
            _logger.LogInformation("Updated {EntityName} with id {Id}", typeof(TEntity).Name, id);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error updating {EntityName} with id {Id}", typeof(TEntity).Name, id);
            throw;
        }
    }

    public virtual async Task DeleteAsync(int id)
    {
        try
        {
            var entity = await _repository.GetByIdAsync(id);
            if (entity == null)
                throw new NotFoundException($"{typeof(TEntity).Name} con ID {id} no encontrado");

            await _repository.DeleteAsync(id);
            
            _logger.LogInformation("Deleted {EntityName} with id {Id}", typeof(TEntity).Name, id);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error deleting {EntityName} with id {Id}", typeof(TEntity).Name, id);
            throw;
        }
    }

    public virtual async Task<IEnumerable<TDto>> GetByEmpresaAsync(int empresaId)
    {
        try
        {
            var entities = await _repository.FindAsync(e => GetEmpresaId(e) == empresaId);
            return _mapper.Map<IEnumerable<TDto>>(entities);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting {EntityName} by empresa {EmpresaId}", typeof(TEntity).Name, empresaId);
            throw;
        }
    }

    public virtual async Task<bool> ExistsAsync(int id)
    {
        try
        {
            return await _repository.ExistsAsync(id);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error checking existence of {EntityName} with id {Id}", typeof(TEntity).Name, id);
            throw;
        }
    }

    /// <summary>
    /// Obtiene el ID de empresa de una entidad
    /// </summary>
    protected abstract int GetEmpresaId(TEntity entity);

    /// <summary>
    /// Valida los datos antes de crear una entidad
    /// </summary>
    protected virtual Task ValidateCreateAsync(TCreateDto createDto)
    {
        return Task.CompletedTask;
    }

    /// <summary>
    /// Valida los datos antes de actualizar una entidad
    /// </summary>
    protected virtual Task ValidateUpdateAsync(int id, TUpdateDto updateDto)
    {
        return Task.CompletedTask;
    }
}
