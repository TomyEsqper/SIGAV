namespace Sigav.Application.Services;

/// <summary>
/// Servicio de cache avanzado con estrategias de invalidación
/// </summary>
public interface ICacheService
{
    /// <summary>
    /// Obtiene un valor del cache
    /// </summary>
    /// <typeparam name="T">Tipo del valor</typeparam>
    /// <param name="key">Clave del cache</param>
    /// <returns>Valor cacheado o null si no existe</returns>
    Task<T?> GetAsync<T>(string key);
    
    /// <summary>
    /// Establece un valor en el cache
    /// </summary>
    /// <typeparam name="T">Tipo del valor</typeparam>
    /// <param name="key">Clave del cache</param>
    /// <param name="value">Valor a cachear</param>
    /// <param name="expiration">Tiempo de expiración</param>
    /// <param name="tags">Tags para invalidación</param>
    Task SetAsync<T>(string key, T value, TimeSpan? expiration = null, string[]? tags = null);
    
    /// <summary>
    /// Elimina un valor del cache
    /// </summary>
    /// <param name="key">Clave del cache</param>
    Task RemoveAsync(string key);
    
    /// <summary>
    /// Invalida todos los valores con un tag específico
    /// </summary>
    /// <param name="tag">Tag a invalidar</param>
    Task InvalidateByTagAsync(string tag);
    
    /// <summary>
    /// Obtiene o establece un valor con función de fallback
    /// </summary>
    /// <typeparam name="T">Tipo del valor</typeparam>
    /// <param name="key">Clave del cache</param>
    /// <param name="factory">Función para generar el valor</param>
    /// <param name="expiration">Tiempo de expiración</param>
    /// <param name="tags">Tags para invalidación</param>
    Task<T> GetOrSetAsync<T>(string key, Func<Task<T>> factory, TimeSpan? expiration = null, string[]? tags = null);
}
