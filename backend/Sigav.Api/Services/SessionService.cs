using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;
using System.Security.Cryptography;

namespace Sigav.Api.Services;

public class SessionService : ISessionService
{
    private readonly SigavDbContext _context;
    private readonly ILogger<SessionService> _logger;
    private readonly IConfiguration _configuration;

    public SessionService(SigavDbContext context, ILogger<SessionService> logger, IConfiguration configuration)
    {
        _context = context;
        _logger = logger;
        _configuration = configuration;
    }

    public async Task<Sesion> CreateSessionAsync(int userId, string jti, string refreshToken, string ipAddress, 
        string userAgent, string? location = null, bool rememberMe = false, string? deviceId = null)
    {
        var sessionDuration = rememberMe ? 30 : 1; // 30 días para "recordarme", 1 día para sesión normal
        
        var sesion = new Sesion
        {
            UsuarioId = userId,
            Jti = jti,
            RefreshToken = refreshToken,
            FechaCreacion = DateTime.UtcNow,
            FechaExpiracion = DateTime.UtcNow.AddDays(sessionDuration),
            UltimoAcceso = DateTime.UtcNow,
            IpAddress = ipAddress,
            UserAgent = userAgent,
            Ubicacion = location,
            Tipo = rememberMe ? "remember_me" : "normal",
            Activa = true,
            EsRecordarme = rememberMe,
            DispositivoId = !string.IsNullOrEmpty(deviceId) ? int.Parse(deviceId) : null
        };

        _context.Sesiones.Add(sesion);
        await _context.SaveChangesAsync();

        _logger.LogInformation("Nueva sesión creada para usuario {UserId} con JTI {Jti}, tipo: {Tipo}", 
            userId, jti, sesion.Tipo);

        return sesion;
    }

    public async Task<Sesion?> GetSessionByJtiAsync(string jti)
    {
        return await _context.Sesiones
            .Include(s => s.Usuario)
            .Include(s => s.Dispositivo)
            .FirstOrDefaultAsync(s => s.Jti == jti && s.Activa && s.FechaExpiracion > DateTime.UtcNow);
    }

    public async Task<Sesion?> GetSessionByRefreshTokenAsync(string refreshToken)
    {
        return await _context.Sesiones
            .Include(s => s.Usuario)
            .Include(s => s.Dispositivo)
            .FirstOrDefaultAsync(s => s.RefreshToken == refreshToken && s.Activa && s.FechaExpiracion > DateTime.UtcNow);
    }

    public async Task UpdateSessionLastAccessAsync(string jti)
    {
        var session = await _context.Sesiones.FirstOrDefaultAsync(s => s.Jti == jti && s.Activa);
        if (session != null)
        {
            session.UltimoAcceso = DateTime.UtcNow;
            await _context.SaveChangesAsync();
        }
    }

    public async Task<List<Sesion>> GetActiveSessionsAsync(int userId)
    {
        return await _context.Sesiones
            .Include(s => s.Dispositivo)
            .Where(s => s.UsuarioId == userId && s.Activa && s.FechaExpiracion > DateTime.UtcNow)
            .OrderByDescending(s => s.UltimoAcceso)
            .ToListAsync();
    }

    public async Task<bool> RevokeSessionAsync(string jti)
    {
        var session = await _context.Sesiones.FirstOrDefaultAsync(s => s.Jti == jti && s.Activa);
        if (session != null)
        {
            session.Activa = false;
            await _context.SaveChangesAsync();
            
            _logger.LogInformation("Sesión {Jti} revocada para usuario {UserId}", jti, session.UsuarioId);
            return true;
        }
        return false;
    }

    public async Task<bool> RevokeAllSessionsExceptAsync(int userId, string currentJti)
    {
        var sessionsToRevoke = await _context.Sesiones
            .Where(s => s.UsuarioId == userId && s.Activa && s.Jti != currentJti)
            .ToListAsync();

        foreach (var session in sessionsToRevoke)
        {
            session.Activa = false;
        }

        await _context.SaveChangesAsync();
        
        _logger.LogInformation("Todas las sesiones excepto {CurrentJti} revocadas para usuario {UserId}", 
            currentJti, userId);
        
        return sessionsToRevoke.Count > 0;
    }

    public async Task<bool> RevokeAllSessionsAsync(int userId)
    {
        var sessionsToRevoke = await _context.Sesiones
            .Where(s => s.UsuarioId == userId && s.Activa)
            .ToListAsync();

        foreach (var session in sessionsToRevoke)
        {
            session.Activa = false;
        }

        await _context.SaveChangesAsync();
        
        _logger.LogInformation("Todas las sesiones revocadas para usuario {UserId}", userId);
        
        return sessionsToRevoke.Count > 0;
    }

    public async Task CleanupExpiredSessionsAsync()
    {
        var expiredSessions = await _context.Sesiones
            .Where(s => s.FechaExpiracion < DateTime.UtcNow && s.Activa)
            .ToListAsync();

        foreach (var session in expiredSessions)
        {
            session.Activa = false;
        }

        if (expiredSessions.Count > 0)
        {
            await _context.SaveChangesAsync();
            _logger.LogInformation("Limpieza automática: {Count} sesiones expiradas desactivadas", 
                expiredSessions.Count);
        }
    }

    public async Task<bool> IsSessionValidAsync(string jti)
    {
        var session = await _context.Sesiones
            .FirstOrDefaultAsync(s => s.Jti == jti && s.Activa && s.FechaExpiracion > DateTime.UtcNow);
        
        return session != null;
    }

    public async Task<bool> IsRefreshTokenValidAsync(string refreshToken)
    {
        var session = await _context.Sesiones
            .FirstOrDefaultAsync(s => s.RefreshToken == refreshToken && s.Activa && s.FechaExpiracion > DateTime.UtcNow);
        
        return session != null;
    }
}
