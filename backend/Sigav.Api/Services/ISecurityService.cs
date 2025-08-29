using Sigav.Domain;

namespace Sigav.Api.Services;

public interface ISecurityService
{
    // Verificación de IP
    Task<bool> IsIpBlockedAsync(string ipAddress);
    Task BlockIpAsync(string ipAddress, string reason, int minutes = 30);
    Task UnblockIpAsync(string ipAddress);
    
    // Detección de dispositivos
    Task<Dispositivo?> GetDeviceAsync(int userId, string userAgent, string ipAddress);
    Task<Dispositivo> RegisterDeviceAsync(int userId, string userAgent, string ipAddress, string? location = null);
    Task UpdateDeviceLastAccessAsync(int deviceId);
    Task<bool> IsDeviceTrustedAsync(int userId, string userAgent, string ipAddress);
    
    // Logs de seguridad
    Task LogSecurityEventAsync(string tenant, string usernameAttempted, string ipAddress, 
        string userAgent, string tipoEvento, string resultado, int? userId = null, 
        string? detalles = null, string? ubicacion = null, string? jti = null);
    
    // Notificaciones
    Task SendLoginNotificationAsync(Usuario usuario, string ipAddress, string userAgent, string? location = null);
    Task SendNewDeviceNotificationAsync(Usuario usuario, Dispositivo dispositivo);
    Task SendSuspiciousActivityNotificationAsync(Usuario usuario, string ipAddress, string reason);
}
