using Sigav.Domain;

namespace Sigav.Api.Services;

public interface ISessionService
{
    // Crear y gestionar sesiones
    Task<Sesion> CreateSessionAsync(int userId, string jti, string refreshToken, string ipAddress, 
        string userAgent, string? location = null, bool rememberMe = false, string? deviceId = null);
    
    Task<Sesion?> GetSessionByJtiAsync(string jti);
    Task<Sesion?> GetSessionByRefreshTokenAsync(string refreshToken);
    Task UpdateSessionLastAccessAsync(string jti);
    
    // Gestión de sesiones
    Task<List<Sesion>> GetActiveSessionsAsync(int userId);
    Task<bool> RevokeSessionAsync(string jti);
    Task<bool> RevokeAllSessionsExceptAsync(int userId, string currentJti);
    Task<bool> RevokeAllSessionsAsync(int userId);
    
    // Limpieza automática
    Task CleanupExpiredSessionsAsync();
    
    // Validación
    Task<bool> IsSessionValidAsync(string jti);
    Task<bool> IsRefreshTokenValidAsync(string refreshToken);
}
