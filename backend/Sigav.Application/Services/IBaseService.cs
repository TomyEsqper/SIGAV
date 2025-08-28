using Sigav.Domain;

namespace Sigav.Application.Services;

/// <summary>
/// Interfaz base para todos los servicios de la aplicación
/// </summary>
/// <typeparam name="TEntity">Tipo de entidad de dominio</typeparam>
/// <typeparam name="TDto">Tipo de DTO de respuesta</typeparam>
/// <typeparam name="TCreateDto">Tipo de DTO para creación</typeparam>
/// <typeparam name="TUpdateDto">Tipo de DTO para actualización</typeparam>
public interface IBaseService<TEntity, TDto, TCreateDto, TUpdateDto>
    where TEntity : BaseEntity
    where TDto : class
    where TCreateDto : class
    where TUpdateDto : class
{
    /// <summary>
    /// Obtiene todas las entidades activas
    /// </summary>
    /// <param name="empresaId">ID de la empresa para filtrar (opcional)</param>
    /// <returns>Colección de DTOs</returns>
    Task<IEnumerable<TDto>> GetAllAsync(int? empresaId = null);

    /// <summary>
    /// Obtiene una entidad por su ID
    /// </summary>
    /// <param name="id">ID de la entidad</param>
    /// <returns>DTO de la entidad o null si no existe</returns>
    Task<TDto?> GetByIdAsync(int id);

    /// <summary>
    /// Crea una nueva entidad
    /// </summary>
    /// <param name="createDto">DTO con los datos para crear</param>
    /// <returns>DTO de la entidad creada</returns>
    Task<TDto> CreateAsync(TCreateDto createDto);

    /// <summary>
    /// Actualiza una entidad existente
    /// </summary>
    /// <param name="id">ID de la entidad a actualizar</param>
    /// <param name="updateDto">DTO con los datos para actualizar</param>
    Task UpdateAsync(int id, TUpdateDto updateDto);

    /// <summary>
    /// Elimina una entidad (soft delete)
    /// </summary>
    /// <param name="id">ID de la entidad a eliminar</param>
    Task DeleteAsync(int id);

    /// <summary>
    /// Obtiene entidades por empresa
    /// </summary>
    /// <param name="empresaId">ID de la empresa</param>
    /// <returns>Colección de DTOs</returns>
    Task<IEnumerable<TDto>> GetByEmpresaAsync(int empresaId);

    /// <summary>
    /// Verifica si una entidad existe
    /// </summary>
    /// <param name="id">ID de la entidad</param>
    /// <returns>True si existe, false en caso contrario</returns>
    Task<bool> ExistsAsync(int id);
}
