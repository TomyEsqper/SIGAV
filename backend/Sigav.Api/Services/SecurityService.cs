using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;
using System.Text.RegularExpressions;

namespace Sigav.Api.Services;

public class SecurityService : ISecurityService
{
    private readonly SigavDbContext _context;
    private readonly ILogger<SecurityService> _logger;
    private readonly IConfiguration _configuration;

    public SecurityService(SigavDbContext context, ILogger<SecurityService> logger, IConfiguration configuration)
    {
        _context = context;
        _logger = logger;
        _configuration = configuration;
    }

    // Verificación de IP
    public async Task<bool> IsIpBlockedAsync(string ipAddress)
    {
        var blockedIp = await _context.IpsBloqueadas
            .Where(ip => ip.IpAddress == ipAddress && ip.Activo && ip.FechaExpiracion > DateTime.UtcNow)
            .FirstOrDefaultAsync();

        return blockedIp != null;
    }

    public async Task BlockIpAsync(string ipAddress, string reason, int minutes = 30)
    {
        var existingBlock = await _context.IpsBloqueadas
            .Where(ip => ip.IpAddress == ipAddress && ip.Activo)
            .FirstOrDefaultAsync();

        if (existingBlock != null)
        {
            // Actualizar bloqueo existente
            existingBlock.IntentosFallidos++;
            existingBlock.FechaExpiracion = DateTime.UtcNow.AddMinutes(minutes);
            existingBlock.Detalles = $"{existingBlock.Detalles}; {reason}";
        }
        else
        {
            // Crear nuevo bloqueo
            var blockedIp = new IpBloqueada
            {
                IpAddress = ipAddress,
                Razon = reason,
                FechaBloqueo = DateTime.UtcNow,
                FechaExpiracion = DateTime.UtcNow.AddMinutes(minutes),
                Detalles = reason,
                Activo = true,
                IntentosFallidos = 1
            };

            _context.IpsBloqueadas.Add(blockedIp);
        }

        await _context.SaveChangesAsync();
        _logger.LogWarning("IP {IpAddress} bloqueada por {Reason} por {Minutes} minutos", ipAddress, reason, minutes);
    }

    public async Task UnblockIpAsync(string ipAddress)
    {
        var blockedIps = await _context.IpsBloqueadas
            .Where(ip => ip.IpAddress == ipAddress && ip.Activo)
            .ToListAsync();

        foreach (var blockedIp in blockedIps)
        {
            blockedIp.Activo = false;
        }

        await _context.SaveChangesAsync();
        _logger.LogInformation("IP {IpAddress} desbloqueada", ipAddress);
    }

    // Detección de dispositivos
    public async Task<Dispositivo?> GetDeviceAsync(int userId, string userAgent, string ipAddress)
    {
        return await _context.Dispositivos
            .Where(d => d.UsuarioId == userId && 
                       d.UserAgent == userAgent && 
                       d.IpAddress == ipAddress && 
                       d.Activo)
            .FirstOrDefaultAsync();
    }

    public async Task<Dispositivo> RegisterDeviceAsync(int userId, string userAgent, string ipAddress, string? location = null)
    {
        var deviceName = ParseDeviceName(userAgent);
        var deviceType = ParseDeviceType(userAgent);

        var dispositivo = new Dispositivo
        {
            UsuarioId = userId,
            Nombre = deviceName,
            Tipo = deviceType,
            UserAgent = userAgent,
            IpAddress = ipAddress,
            Ubicacion = location,
            FechaRegistro = DateTime.UtcNow,
            UltimoAcceso = DateTime.UtcNow,
            EsConfiable = false, // Por defecto no es confiable hasta que el usuario lo confirme
            Activo = true
        };

        _context.Dispositivos.Add(dispositivo);
        await _context.SaveChangesAsync();

        _logger.LogInformation("Nuevo dispositivo registrado para usuario {UserId}: {DeviceName}", userId, deviceName);
        return dispositivo;
    }

    public async Task UpdateDeviceLastAccessAsync(int deviceId)
    {
        var device = await _context.Dispositivos.FindAsync(deviceId);
        if (device != null)
        {
            device.UltimoAcceso = DateTime.UtcNow;
            await _context.SaveChangesAsync();
        }
    }

    public async Task<bool> IsDeviceTrustedAsync(int userId, string userAgent, string ipAddress)
    {
        var device = await GetDeviceAsync(userId, userAgent, ipAddress);
        return device?.EsConfiable == true;
    }

    // Logs de seguridad
    public async Task LogSecurityEventAsync(string tenant, string usernameAttempted, string ipAddress, 
        string userAgent, string tipoEvento, string resultado, int? userId = null, 
        string? detalles = null, string? ubicacion = null, string? jti = null)
    {
        var log = new LogSeguridad
        {
            Tenant = tenant,
            UsernameAttempted = usernameAttempted,
            IpAddress = ipAddress,
            UserAgent = userAgent,
            TipoEvento = tipoEvento,
            Resultado = resultado,
            Detalles = detalles,
            Ubicacion = ubicacion,
            Timestamp = DateTime.UtcNow,
            Jti = jti,
            UsuarioId = userId
        };

        _context.LogsSeguridad.Add(log);
        await _context.SaveChangesAsync();

        _logger.LogInformation("Evento de seguridad registrado: {TipoEvento} - {Resultado} para {Username} desde {IpAddress}", 
            tipoEvento, resultado, usernameAttempted, ipAddress);
    }

    // Notificaciones
    public async Task SendLoginNotificationAsync(Usuario usuario, string ipAddress, string userAgent, string? location = null)
    {
        // En una implementación real, aquí enviarías email/SMS
        _logger.LogInformation("Notificación de login enviada a {Email} desde {IpAddress} en {Location}", 
            usuario.Email, ipAddress, location ?? "ubicación desconocida");
        
        // TODO: Implementar envío real de notificaciones
        await Task.CompletedTask;
    }

    public async Task SendNewDeviceNotificationAsync(Usuario usuario, Dispositivo dispositivo)
    {
        // En una implementación real, aquí enviarías email/SMS
        _logger.LogInformation("Notificación de nuevo dispositivo enviada a {Email} para dispositivo {DeviceName}", 
            usuario.Email, dispositivo.Nombre);
        
        // TODO: Implementar envío real de notificaciones
        await Task.CompletedTask;
    }

    public async Task SendSuspiciousActivityNotificationAsync(Usuario usuario, string ipAddress, string reason)
    {
        // En una implementación real, aquí enviarías email/SMS
        _logger.LogWarning("Notificación de actividad sospechosa enviada a {Email} desde {IpAddress}: {Reason}", 
            usuario.Email, ipAddress, reason);
        
        // TODO: Implementar envío real de notificaciones
        await Task.CompletedTask;
    }

    // Métodos auxiliares para parsear User Agent
    private string ParseDeviceName(string userAgent)
    {
        if (userAgent.Contains("Chrome"))
            return "Chrome";
        else if (userAgent.Contains("Firefox"))
            return "Firefox";
        else if (userAgent.Contains("Safari"))
            return "Safari";
        else if (userAgent.Contains("Edge"))
            return "Edge";
        else if (userAgent.Contains("Mobile"))
            return "Dispositivo móvil";
        else
            return "Navegador desconocido";
    }

    private string ParseDeviceType(string userAgent)
    {
        if (userAgent.Contains("Mobile") || userAgent.Contains("Android") || userAgent.Contains("iPhone"))
            return "mobile";
        else if (userAgent.Contains("Tablet") || userAgent.Contains("iPad"))
            return "tablet";
        else
            return "desktop";
    }
}
