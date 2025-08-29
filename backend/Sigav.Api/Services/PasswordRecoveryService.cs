using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;
using System.Security.Cryptography;
using System.Text;

namespace Sigav.Api.Services;

public class PasswordRecoveryService : IPasswordRecoveryService
{
    private readonly SigavDbContext _context;
    private readonly ILogger<PasswordRecoveryService> _logger;
    private readonly ISecurityService _securityService;

    public PasswordRecoveryService(SigavDbContext context, ILogger<PasswordRecoveryService> logger, ISecurityService securityService)
    {
        _context = context;
        _logger = logger;
        _securityService = securityService;
    }

    public async Task<bool> RequestPasswordResetAsync(string tenant, string email, string ipAddress, string userAgent, string? location = null)
    {
        try
        {
            // Buscar usuario por email y tenant
            var empresa = await _context.Empresas
                .FirstOrDefaultAsync(e => e.Nombre.ToLowerInvariant() == tenant.ToLowerInvariant() && e.Activo);

            if (empresa == null)
            {
                _logger.LogWarning("Password reset request for invalid tenant: {Tenant}", tenant);
                return false;
            }

            var usuario = await _context.Usuarios
                .FirstOrDefaultAsync(u => u.Email.ToLowerInvariant() == email.ToLowerInvariant() && 
                                        u.EmpresaId == empresa.Id && u.Activo);

            if (usuario == null)
            {
                _logger.LogWarning("Password reset request for non-existent user: {Email} in tenant: {Tenant}", email, tenant);
                return false;
            }

            // Revocar tokens anteriores
            await RevokeAllRecoveryTokensAsync(usuario.Id);

            // Generar nuevo token y código
            var token = GenerateSecureToken();
            var codigo = GenerateRecoveryCode();

            var recuperacion = new RecuperacionContrasena
            {
                UsuarioId = usuario.Id,
                Token = token,
                CodigoRecuperacion = codigo,
                FechaCreacion = DateTime.UtcNow,
                FechaExpiracion = DateTime.UtcNow.AddHours(1), // 1 hora para tokens
                IpAddress = ipAddress,
                UserAgent = userAgent,
                Ubicacion = location,
                Tipo = "email",
                Usado = false,
                Activo = true
            };

            _context.RecuperacionesContrasena.Add(recuperacion);
            await _context.SaveChangesAsync();

            // Log de seguridad
            await _securityService.LogSecurityEventAsync(tenant, email, ipAddress, userAgent, "password_reset_requested", "ok", usuario.Id, ubicacion: location);

            // TODO: Enviar email con token y código
            _logger.LogInformation("Password reset requested for user {UserId} ({Email}) in tenant {Tenant}. Token: {Token}, Code: {Code}", 
                usuario.Id, email, tenant, token, codigo);

            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error requesting password reset for {Email} in tenant {Tenant}", email, tenant);
            return false;
        }
    }

    public async Task<bool> ValidateResetTokenAsync(string token)
    {
        try
        {
            var recuperacion = await _context.RecuperacionesContrasena
                .Include(r => r.Usuario)
                .FirstOrDefaultAsync(r => r.Token == token && r.Activo && !r.Usado && r.FechaExpiracion > DateTime.UtcNow);

            return recuperacion != null;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error validating reset token");
            return false;
        }
    }

    public async Task<bool> ValidateRecoveryCodeAsync(string code)
    {
        try
        {
            var recuperacion = await _context.RecuperacionesContrasena
                .Include(r => r.Usuario)
                .FirstOrDefaultAsync(r => r.CodigoRecuperacion == code && r.Activo && !r.Usado && r.FechaExpiracion > DateTime.UtcNow);

            return recuperacion != null;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error validating recovery code");
            return false;
        }
    }

    public async Task<bool> ResetPasswordWithTokenAsync(string token, string newPassword, string ipAddress, string userAgent)
    {
        try
        {
            var recuperacion = await _context.RecuperacionesContrasena
                .Include(r => r.Usuario)
                .ThenInclude(u => u.Empresa)
                .FirstOrDefaultAsync(r => r.Token == token && r.Activo && !r.Usado && r.FechaExpiracion > DateTime.UtcNow);

            if (recuperacion == null)
            {
                _logger.LogWarning("Invalid or expired reset token used: {Token}", token);
                return false;
            }

            // Hash de la nueva contraseña
            using var sha256 = SHA256.Create();
            var hashBytes = sha256.ComputeHash(Encoding.UTF8.GetBytes(newPassword));
            var passwordHash = Convert.ToBase64String(hashBytes);

            // Actualizar contraseña
            recuperacion.Usuario.PasswordHash = passwordHash;
            recuperacion.Usuario.FailedAttempts = 0; // Reset failed attempts
            recuperacion.Usuario.LockedUntil = null; // Unlock account

            // Marcar token como usado
            recuperacion.Usado = true;
            recuperacion.FechaUso = DateTime.UtcNow;
            recuperacion.Activo = false;

            await _context.SaveChangesAsync();

            // Log de seguridad
            await _securityService.LogSecurityEventAsync(
                recuperacion.Usuario.Empresa.Nombre, 
                recuperacion.Usuario.Email, 
                ipAddress, 
                userAgent, 
                "password_reset_completed", 
                "ok", 
                recuperacion.Usuario.Id
            );

            _logger.LogInformation("Password reset completed for user {UserId} using token {Token}", 
                recuperacion.Usuario.Id, token);

            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error resetting password with token {Token}", token);
            return false;
        }
    }

    public async Task<bool> ResetPasswordWithCodeAsync(string code, string newPassword, string ipAddress, string userAgent)
    {
        try
        {
            var recuperacion = await _context.RecuperacionesContrasena
                .Include(r => r.Usuario)
                .ThenInclude(u => u.Empresa)
                .FirstOrDefaultAsync(r => r.CodigoRecuperacion == code && r.Activo && !r.Usado && r.FechaExpiracion > DateTime.UtcNow);

            if (recuperacion == null)
            {
                _logger.LogWarning("Invalid or expired recovery code used: {Code}", code);
                return false;
            }

            // Hash de la nueva contraseña
            using var sha256 = SHA256.Create();
            var hashBytes = sha256.ComputeHash(Encoding.UTF8.GetBytes(newPassword));
            var passwordHash = Convert.ToBase64String(hashBytes);

            // Actualizar contraseña
            recuperacion.Usuario.PasswordHash = passwordHash;
            recuperacion.Usuario.FailedAttempts = 0; // Reset failed attempts
            recuperacion.Usuario.LockedUntil = null; // Unlock account

            // Marcar código como usado
            recuperacion.Usado = true;
            recuperacion.FechaUso = DateTime.UtcNow;
            recuperacion.Activo = false;

            await _context.SaveChangesAsync();

            // Log de seguridad
            await _securityService.LogSecurityEventAsync(
                recuperacion.Usuario.Empresa.Nombre, 
                recuperacion.Usuario.Email, 
                ipAddress, 
                userAgent, 
                "password_reset_completed", 
                "ok", 
                recuperacion.Usuario.Id
            );

            _logger.LogInformation("Password reset completed for user {UserId} using code {Code}", 
                recuperacion.Usuario.Id, code);

            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error resetting password with code {Code}", code);
            return false;
        }
    }

    public async Task<bool> GenerateEmergencyCodesAsync(int userId, string ipAddress, string userAgent)
    {
        try
        {
            // Revocar códigos anteriores
            var existingCodes = await _context.RecuperacionesContrasena
                .Where(r => r.UsuarioId == userId && r.Tipo == "codigo_emergencia" && r.Activo)
                .ToListAsync();

            foreach (var code in existingCodes)
            {
                code.Activo = false;
            }

            // Generar 5 códigos de emergencia
            for (int i = 0; i < 5; i++)
            {
                var codigo = GenerateRecoveryCode();
                var recuperacion = new RecuperacionContrasena
                {
                    UsuarioId = userId,
                    Token = string.Empty, // No se usa para códigos de emergencia
                    CodigoRecuperacion = codigo,
                    FechaCreacion = DateTime.UtcNow,
                    FechaExpiracion = DateTime.UtcNow.AddDays(30), // 30 días para códigos de emergencia
                    IpAddress = ipAddress,
                    UserAgent = userAgent,
                    Tipo = "codigo_emergencia",
                    Usado = false,
                    Activo = true
                };

                _context.RecuperacionesContrasena.Add(recuperacion);
            }

            await _context.SaveChangesAsync();

            _logger.LogInformation("Emergency codes generated for user {UserId}", userId);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error generating emergency codes for user {UserId}", userId);
            return false;
        }
    }

    public async Task<List<string>> GetEmergencyCodesAsync(int userId)
    {
        try
        {
            var codigos = await _context.RecuperacionesContrasena
                .Where(r => r.UsuarioId == userId && r.Tipo == "codigo_emergencia" && r.Activo && !r.Usado && r.FechaExpiracion > DateTime.UtcNow)
                .Select(r => r.CodigoRecuperacion)
                .ToListAsync();

            return codigos;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting emergency codes for user {UserId}", userId);
            return new List<string>();
        }
    }

    public async Task<bool> RevokeAllRecoveryTokensAsync(int userId)
    {
        try
        {
            var tokens = await _context.RecuperacionesContrasena
                .Where(r => r.UsuarioId == userId && r.Activo)
                .ToListAsync();

            foreach (var token in tokens)
            {
                token.Activo = false;
            }

            await _context.SaveChangesAsync();
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error revoking recovery tokens for user {UserId}", userId);
            return false;
        }
    }

    public async Task<bool> CleanupExpiredTokensAsync()
    {
        try
        {
            var expiredTokens = await _context.RecuperacionesContrasena
                .Where(r => r.FechaExpiracion < DateTime.UtcNow && r.Activo)
                .ToListAsync();

            foreach (var token in expiredTokens)
            {
                token.Activo = false;
            }

            await _context.SaveChangesAsync();

            if (expiredTokens.Count > 0)
            {
                _logger.LogInformation("Cleaned up {Count} expired recovery tokens", expiredTokens.Count);
            }

            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error cleaning up expired tokens");
            return false;
        }
    }

    private string GenerateSecureToken()
    {
        var randomBytes = new byte[32];
        using var rng = RandomNumberGenerator.Create();
        rng.GetBytes(randomBytes);
        return Convert.ToBase64String(randomBytes).Replace("+", "-").Replace("/", "_").Replace("=", "");
    }

    private string GenerateRecoveryCode()
    {
        var random = new Random();
        return random.Next(100000, 999999).ToString(); // Código de 6 dígitos
    }
}
