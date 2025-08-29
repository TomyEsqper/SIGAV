using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using Sigav.Api.Data;
using Sigav.Api.Services;
using Sigav.Domain;
using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Security.Cryptography;
using System.Text;
using System.ComponentModel.DataAnnotations;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class AuthController : ControllerBase
{
    private readonly SigavDbContext _context;
    private readonly IConfiguration _configuration;
    private readonly ILogger<AuthController> _logger;
    private readonly ISecurityService _securityService;
    private readonly ISessionService _sessionService;
    private readonly IPasswordRecoveryService _passwordRecoveryService;

    public AuthController(SigavDbContext context, IConfiguration configuration, ILogger<AuthController> logger, ISecurityService securityService, ISessionService sessionService, IPasswordRecoveryService passwordRecoveryService)
    {
        _context = context;
        _configuration = configuration;
        _logger = logger;
        _securityService = securityService;
        _sessionService = sessionService;
        _passwordRecoveryService = passwordRecoveryService;
    }

    [HttpPost("login")]
    public async Task<IActionResult> Login([FromBody] LoginRequest request)
    {
        try
        {
            // Obtener información del cliente
            var ipAddress = GetClientIpAddress();
            var userAgent = Request.Headers["User-Agent"].ToString();
            var location = await GetLocationFromIp(ipAddress);

            // Validar request
            if (!ModelState.IsValid)
            {
                await _securityService.LogSecurityEventAsync("unknown", request.UsernameOrEmail ?? "unknown", ipAddress, userAgent, "login_fallido", "fail", detalles: "Modelo inválido");
                return BadRequest(new { message = "Credenciales inválidas" });
            }

            // Sanitizar inputs
            var tenant = request.Tenant?.Trim().ToLowerInvariant();
            var usernameOrEmail = request.UsernameOrEmail?.Trim();

            if (string.IsNullOrEmpty(tenant) || string.IsNullOrEmpty(usernameOrEmail) || string.IsNullOrEmpty(request.Password))
            {
                await _securityService.LogSecurityEventAsync("unknown", usernameOrEmail ?? "unknown", ipAddress, userAgent, "login_fallido", "fail", detalles: "Campos vacíos");
                return BadRequest(new { message = "Credenciales inválidas" });
            }

            // Verificar si la IP está bloqueada
            if (await _securityService.IsIpBlockedAsync(ipAddress))
            {
                await _securityService.LogSecurityEventAsync(tenant, usernameOrEmail, ipAddress, userAgent, "login_fallido", "blocked", detalles: "IP bloqueada");
                return StatusCode(423, new { message = "Acceso bloqueado temporalmente" });
            }

            // Buscar tenant
            var empresas = await _context.Empresas
                .Where(e => e.Activo)
                .ToListAsync();
            
            var empresa = empresas.FirstOrDefault(e => e.Nombre.ToLowerInvariant() == tenant);

            if (empresa == null)
            {
                await _securityService.LogSecurityEventAsync(tenant, usernameOrEmail, ipAddress, userAgent, "login_fallido", "fail", detalles: "Tenant inválido");
                _logger.LogWarning("Login attempt with invalid tenant: {Tenant}", tenant);
                return BadRequest(new { message = "Credenciales inválidas" });
            }

            // Buscar usuario
            var usuarios = await _context.Usuarios
                .Where(u => u.EmpresaId == empresa.Id && u.Activo)
                .ToListAsync();
            
            var usuario = usuarios.FirstOrDefault(u => 
                u.Email.ToLowerInvariant() == usernameOrEmail.ToLowerInvariant() || 
                u.Nombre.ToLowerInvariant() == usernameOrEmail.ToLowerInvariant());

            if (usuario == null)
            {
                await _securityService.LogSecurityEventAsync(tenant, usernameOrEmail, ipAddress, userAgent, "login_fallido", "fail", detalles: "Usuario no encontrado");
                _logger.LogWarning("Login attempt with invalid credentials for tenant: {Tenant}, username: {Username}", tenant, usernameOrEmail);
                return BadRequest(new { message = "Credenciales inválidas" });
            }

            // Verificar si la cuenta está bloqueada
            if (usuario.FailedAttempts >= 5 && usuario.LockedUntil.HasValue && usuario.LockedUntil > DateTime.UtcNow)
            {
                await _securityService.LogSecurityEventAsync(tenant, usernameOrEmail, ipAddress, userAgent, "login_fallido", "blocked", usuario.Id, detalles: "Cuenta bloqueada");
                _logger.LogWarning("Login attempt for locked account: {UserId}", usuario.Id);
                return StatusCode(423, new { message = "Cuenta bloqueada temporalmente" });
            }

            // Verificar contraseña
            if (!VerifyPassword(request.Password, usuario.PasswordHash))
            {
                // Incrementar intentos fallidos
                usuario.FailedAttempts++;
                if (usuario.FailedAttempts >= 5)
                {
                    usuario.LockedUntil = DateTime.UtcNow.AddMinutes(15);
                    
                    // Bloquear IP si hay muchos intentos fallidos
                    if (usuario.FailedAttempts >= 10)
                    {
                        await _securityService.BlockIpAsync(ipAddress, "Múltiples intentos fallidos de login", 30);
                    }
                }
                await _context.SaveChangesAsync();

                await _securityService.LogSecurityEventAsync(tenant, usernameOrEmail, ipAddress, userAgent, "login_fallido", "fail", usuario.Id, detalles: $"Intento {usuario.FailedAttempts}");
                _logger.LogWarning("Failed login attempt for user: {UserId}", usuario.Id);
                return BadRequest(new { message = "Credenciales inválidas" });
            }

            // Login exitoso - resetear contadores
            usuario.FailedAttempts = 0;
            usuario.LockedUntil = null;
            usuario.LastLoginAt = DateTime.UtcNow;
            await _context.SaveChangesAsync();

            // Manejar dispositivos
            var existingDevice = await _securityService.GetDeviceAsync(usuario.Id, userAgent, ipAddress);
            var isNewDevice = existingDevice == null;
            var isTrustedDevice = false;

            if (existingDevice != null)
            {
                await _securityService.UpdateDeviceLastAccessAsync(existingDevice.Id);
                isTrustedDevice = existingDevice.EsConfiable;
            }
            else
            {
                existingDevice = await _securityService.RegisterDeviceAsync(usuario.Id, userAgent, ipAddress, location);
                
                // Enviar notificación de nuevo dispositivo
                await _securityService.SendNewDeviceNotificationAsync(usuario, existingDevice);
            }

            // Generar JWT
            var token = GenerateJwtToken(usuario, empresa);

            // Generar refresh token
            var refreshToken = GenerateRefreshToken();

            // Crear sesión
            var session = await _sessionService.CreateSessionAsync(
                usuario.Id, 
                token.Id, 
                refreshToken, 
                ipAddress, 
                userAgent, 
                location ?? "Desconocida", 
                request.RememberMe,
                existingDevice?.Id.ToString()
            );

            // Log de auditoría
            await _securityService.LogSecurityEventAsync(tenant, usernameOrEmail, ipAddress, userAgent, "login_exitoso", "ok", usuario.Id, jti: token.Id, ubicacion: location);

            // Enviar notificación de login si es dispositivo no confiable
            if (!isTrustedDevice)
            {
                await _securityService.SendLoginNotificationAsync(usuario, ipAddress, userAgent, location);
            }

            _logger.LogInformation("Successful login for user: {UserId} in tenant: {Tenant} from {IpAddress}", usuario.Id, tenant, ipAddress);

            return Ok(new LoginResponse
            {
                AccessToken = token.Token,
                ExpiresIn = 900, // 15 minutos
                TokenType = "Bearer",
                RefreshToken = refreshToken,
                User = new UserInfo
                {
                    Id = usuario.Id.ToString(),
                    Name = $"{usuario.Nombre} {usuario.Apellido}",
                    Email = usuario.Email
                },
                Tenant = tenant,
                IsNewDevice = isNewDevice,
                DeviceName = existingDevice?.Nombre,
                RememberMe = request.RememberMe
            });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error during login process");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    private bool VerifyPassword(string password, string passwordHash)
    {
        // Implementación simple de verificación de hash
        // En producción, usar Argon2id o PBKDF2
        try
        {
            using var sha256 = SHA256.Create();
            var hashBytes = sha256.ComputeHash(Encoding.UTF8.GetBytes(password));
            var hash = Convert.ToBase64String(hashBytes);
            return hash == passwordHash;
        }
        catch
        {
            return false;
        }
    }

    private (string Token, string Id) GenerateJwtToken(Usuario usuario, Empresa empresa)
    {
        var jwtKey = _configuration["Jwt:Key"];
        var jwtIssuer = _configuration["Jwt:Issuer"];
        var jwtAudience = _configuration["Jwt:Audience"];

        if (string.IsNullOrEmpty(jwtKey))
        {
            throw new InvalidOperationException("JWT key not configured");
        }

        var key = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwtKey));
        var credentials = new SigningCredentials(key, SecurityAlgorithms.HmacSha256);

        var claims = new[]
        {
            new Claim(JwtRegisteredClaimNames.Sub, usuario.Id.ToString()),
            new Claim("tenant", empresa.Nombre.ToLowerInvariant()),
            new Claim(JwtRegisteredClaimNames.Jti, Guid.NewGuid().ToString()),
            new Claim(JwtRegisteredClaimNames.Iat, DateTimeOffset.UtcNow.ToUnixTimeSeconds().ToString(), ClaimValueTypes.Integer64)
        };

        var token = new JwtSecurityToken(
            issuer: jwtIssuer,
            audience: jwtAudience,
            claims: claims,
            expires: DateTime.UtcNow.AddMinutes(15),
            signingCredentials: credentials
        );

        var tokenString = new JwtSecurityTokenHandler().WriteToken(token);
        var jti = claims.First(c => c.Type == JwtRegisteredClaimNames.Jti).Value;

        return (tokenString, jti);
    }

    private string GenerateRefreshToken()
    {
        var randomBytes = new byte[32];
        using var rng = RandomNumberGenerator.Create();
        rng.GetBytes(randomBytes);
        return Convert.ToBase64String(randomBytes);
    }

    private async Task LogAuthAttempt(string tenant, int? userId, string usernameAttempted, bool success, string? jti = null)
    {
        try
        {
            var authLog = new AuthLog
            {
                Tenant = tenant,
                UserId = userId,
                UsernameAttempted = usernameAttempted,
                IpAddress = HttpContext.Connection.RemoteIpAddress?.ToString() ?? "unknown",
                UserAgent = HttpContext.Request.Headers.UserAgent.ToString(),
                Result = success ? "ok" : "fail",
                Timestamp = DateTime.UtcNow,
                Jti = jti
            };

            _context.AuthLogs.Add(authLog);
            await _context.SaveChangesAsync();
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error logging auth attempt");
        }
    }

    private string GetClientIpAddress()
    {
        // Obtener IP real considerando proxies
        var forwardedHeader = Request.Headers["X-Forwarded-For"].FirstOrDefault();
        if (!string.IsNullOrEmpty(forwardedHeader))
        {
            return forwardedHeader.Split(',')[0].Trim();
        }

        var realIpHeader = Request.Headers["X-Real-IP"].FirstOrDefault();
        if (!string.IsNullOrEmpty(realIpHeader))
        {
            return realIpHeader;
        }

        return HttpContext.Connection.RemoteIpAddress?.ToString() ?? "unknown";
    }

    private async Task<string?> GetLocationFromIp(string ipAddress)
    {
        try
        {
            // En una implementación real, aquí harías una llamada a un servicio de geolocalización
            // Por ahora, simulamos la ubicación basada en IPs locales
            if (ipAddress == "127.0.0.1" || ipAddress == "::1" || ipAddress.StartsWith("192.168.") || ipAddress.StartsWith("10."))
            {
                return "Local";
            }

            // TODO: Implementar llamada real a servicio de geolocalización
            // Ejemplo: https://ipapi.co/{ip}/json/
            
            return null;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting location for IP: {IpAddress}", ipAddress);
            return null;
        }
    }

    [HttpPost("refresh")]
    public async Task<IActionResult> RefreshToken([FromBody] RefreshTokenRequest request)
    {
        try
        {
            if (string.IsNullOrEmpty(request.RefreshToken))
            {
                return BadRequest(new { message = "Refresh token requerido" });
            }

            var session = await _sessionService.GetSessionByRefreshTokenAsync(request.RefreshToken);
            if (session == null || !session.Activa)
            {
                return BadRequest(new { message = "Refresh token inválido o expirado" });
            }

            // Verificar que la sesión no haya expirado
            if (session.FechaExpiracion < DateTime.UtcNow)
            {
                return BadRequest(new { message = "Sesión expirada" });
            }

            // Generar nuevo JWT
            var empresa = await _context.Empresas.FindAsync(session.Usuario.EmpresaId);
            if (empresa == null)
            {
                return BadRequest(new { message = "Empresa no encontrada" });
            }

            var newToken = GenerateJwtToken(session.Usuario, empresa);
            
            // Actualizar la sesión con el nuevo JTI
            session.Jti = newToken.Id;
            session.UltimoAcceso = DateTime.UtcNow;
            await _context.SaveChangesAsync();

            return Ok(new RefreshTokenResponse
            {
                AccessToken = newToken.Token,
                ExpiresIn = 900,
                TokenType = "Bearer"
            });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error refreshing token");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("logout")]
    public async Task<IActionResult> Logout([FromBody] LogoutRequest request)
    {
        try
        {
            if (!string.IsNullOrEmpty(request.Jti))
            {
                await _sessionService.RevokeSessionAsync(request.Jti);
            }

            return Ok(new { message = "Sesión cerrada correctamente" });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error during logout");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpGet("sessions")]
    [Authorize]
    public async Task<IActionResult> GetActiveSessions()
    {
        try
        {
            // Debug: Log all claims
            _logger.LogInformation("User claims: {Claims}", string.Join(", ", User.Claims.Select(c => $"{c.Type}={c.Value}")));
            
            // Obtener el usuario del token JWT
            var userIdClaim = User.FindFirst("sub")?.Value;
            if (string.IsNullOrEmpty(userIdClaim) || !int.TryParse(userIdClaim, out int userId))
            {
                _logger.LogWarning("Invalid token - userIdClaim: {UserIdClaim}", userIdClaim);
                return Unauthorized(new { message = "Token inválido" });
            }

            var sessions = await _sessionService.GetActiveSessionsAsync(userId);
            
            var sessionResponses = sessions.Select(s => new SessionInfo
            {
                Id = s.Id,
                Jti = s.Jti,
                IpAddress = s.IpAddress,
                UserAgent = s.UserAgent,
                Ubicacion = s.Ubicacion,
                Tipo = s.Tipo,
                EsRecordarme = s.EsRecordarme,
                FechaCreacion = s.FechaCreacion,
                UltimoAcceso = s.UltimoAcceso,
                FechaExpiracion = s.FechaExpiracion,
                DispositivoNombre = s.Dispositivo?.Nombre
            }).ToList();

            return Ok(new { sessions = sessionResponses });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting active sessions");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("sessions/{jti}/revoke")]
    public async Task<IActionResult> RevokeSession(string jti)
    {
        try
        {
            var userIdClaim = User.FindFirst("sub")?.Value;
            if (string.IsNullOrEmpty(userIdClaim) || !int.TryParse(userIdClaim, out int userId))
            {
                return Unauthorized(new { message = "Token inválido" });
            }

            var session = await _sessionService.GetSessionByJtiAsync(jti);
            if (session == null || session.UsuarioId != userId)
            {
                return NotFound(new { message = "Sesión no encontrada" });
            }

            var revoked = await _sessionService.RevokeSessionAsync(jti);
            if (revoked)
            {
                return Ok(new { message = "Sesión revocada correctamente" });
            }

            return BadRequest(new { message = "No se pudo revocar la sesión" });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error revoking session");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpGet("test-auth")]
    public IActionResult TestAuth()
    {
        return Ok(new { message = "API funcionando correctamente" });
    }

    [HttpPost("sessions/revoke-all-except-current")]
    public async Task<IActionResult> RevokeAllSessionsExceptCurrent()
    {
        try
        {
            var userIdClaim = User.FindFirst("sub")?.Value;
            var currentJti = User.FindFirst("jti")?.Value;
            
            if (string.IsNullOrEmpty(userIdClaim) || !int.TryParse(userIdClaim, out int userId))
            {
                return Unauthorized(new { message = "Token inválido" });
            }

            if (string.IsNullOrEmpty(currentJti))
            {
                return BadRequest(new { message = "JTI actual no encontrado" });
            }

            var revoked = await _sessionService.RevokeAllSessionsExceptAsync(userId, currentJti);
            
            return Ok(new { 
                message = "Sesiones revocadas correctamente",
                sessionsRevoked = revoked
            });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error revoking all sessions except current");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("forgot-password")]
    public async Task<IActionResult> ForgotPassword([FromBody] ForgotPasswordRequest request)
    {
        try
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(new { message = "Datos inválidos" });
            }

            var ipAddress = GetClientIpAddress();
            var userAgent = Request.Headers["User-Agent"].ToString();
            var location = await GetLocationFromIp(ipAddress);

            var success = await _passwordRecoveryService.RequestPasswordResetAsync(
                request.Tenant, 
                request.Email, 
                ipAddress, 
                userAgent, 
                location
            );

            if (success)
            {
                return Ok(new { message = "Si el email existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña" });
            }
            else
            {
                // Por seguridad, siempre devolver el mismo mensaje
                return Ok(new { message = "Si el email existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña" });
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error in forgot password request");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("validate-reset-token")]
    public async Task<IActionResult> ValidateResetToken([FromBody] ValidateResetTokenRequest request)
    {
        try
        {
            if (string.IsNullOrEmpty(request.Token))
            {
                return BadRequest(new { message = "Token requerido" });
            }

            var isValid = await _passwordRecoveryService.ValidateResetTokenAsync(request.Token);

            if (isValid)
            {
                return Ok(new { message = "Token válido" });
            }
            else
            {
                return BadRequest(new { message = "Token inválido o expirado" });
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error validating reset token");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("validate-recovery-code")]
    public async Task<IActionResult> ValidateRecoveryCode([FromBody] ValidateRecoveryCodeRequest request)
    {
        try
        {
            if (string.IsNullOrEmpty(request.Code))
            {
                return BadRequest(new { message = "Código requerido" });
            }

            var isValid = await _passwordRecoveryService.ValidateRecoveryCodeAsync(request.Code);

            if (isValid)
            {
                return Ok(new { message = "Código válido" });
            }
            else
            {
                return BadRequest(new { message = "Código inválido o expirado" });
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error validating recovery code");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("reset-password-token")]
    public async Task<IActionResult> ResetPasswordWithToken([FromBody] ResetPasswordTokenRequest request)
    {
        try
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(new { message = "Datos inválidos" });
            }

            var ipAddress = GetClientIpAddress();
            var userAgent = Request.Headers["User-Agent"].ToString();

            var success = await _passwordRecoveryService.ResetPasswordWithTokenAsync(
                request.Token, 
                request.NewPassword, 
                ipAddress, 
                userAgent
            );

            if (success)
            {
                return Ok(new { message = "Contraseña restablecida correctamente" });
            }
            else
            {
                return BadRequest(new { message = "Token inválido o expirado" });
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error resetting password with token");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("reset-password-code")]
    public async Task<IActionResult> ResetPasswordWithCode([FromBody] ResetPasswordCodeRequest request)
    {
        try
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(new { message = "Datos inválidos" });
            }

            var ipAddress = GetClientIpAddress();
            var userAgent = Request.Headers["User-Agent"].ToString();

            var success = await _passwordRecoveryService.ResetPasswordWithCodeAsync(
                request.Code, 
                request.NewPassword, 
                ipAddress, 
                userAgent
            );

            if (success)
            {
                return Ok(new { message = "Contraseña restablecida correctamente" });
            }
            else
            {
                return BadRequest(new { message = "Código inválido o expirado" });
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error resetting password with code");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("generate-emergency-codes")]
    [Authorize]
    public async Task<IActionResult> GenerateEmergencyCodes()
    {
        try
        {
            var userIdClaim = User.FindFirst("sub")?.Value;
            if (string.IsNullOrEmpty(userIdClaim) || !int.TryParse(userIdClaim, out int userId))
            {
                return Unauthorized(new { message = "Token inválido" });
            }

            var ipAddress = GetClientIpAddress();
            var userAgent = Request.Headers["User-Agent"].ToString();

            var success = await _passwordRecoveryService.GenerateEmergencyCodesAsync(userId, ipAddress, userAgent);

            if (success)
            {
                var codes = await _passwordRecoveryService.GetEmergencyCodesAsync(userId);
                return Ok(new { 
                    message = "Códigos de emergencia generados correctamente",
                    codes = codes
                });
            }
            else
            {
                return BadRequest(new { message = "Error generando códigos de emergencia" });
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error generating emergency codes");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpGet("emergency-codes")]
    [Authorize]
    public async Task<IActionResult> GetEmergencyCodes()
    {
        try
        {
            var userIdClaim = User.FindFirst("sub")?.Value;
            if (string.IsNullOrEmpty(userIdClaim) || !int.TryParse(userIdClaim, out int userId))
            {
                return Unauthorized(new { message = "Token inválido" });
            }

            var codes = await _passwordRecoveryService.GetEmergencyCodesAsync(userId);

            return Ok(new { 
                codes = codes,
                count = codes.Count
            });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting emergency codes");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }
}

public class LoginRequest
{
    [Required]
    public string Tenant { get; set; } = string.Empty;
    
    [Required]
    public string UsernameOrEmail { get; set; } = string.Empty;
    
    [Required]
    public string Password { get; set; } = string.Empty;
    
    public bool RememberMe { get; set; } = false;
}

public class LoginResponse
{
    public string AccessToken { get; set; } = string.Empty;
    public int ExpiresIn { get; set; }
    public string TokenType { get; set; } = string.Empty;
    public string RefreshToken { get; set; } = string.Empty;
    public UserInfo User { get; set; } = new();
    public string Tenant { get; set; } = string.Empty;
    public bool IsNewDevice { get; set; } = false;
    public string? DeviceName { get; set; }
    public bool RememberMe { get; set; } = false;
}

public class UserInfo
{
    public string Id { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string Email { get; set; } = string.Empty;
}

public class RefreshTokenRequest
{
    [Required]
    public string RefreshToken { get; set; } = string.Empty;
}

public class RefreshTokenResponse
{
    public string AccessToken { get; set; } = string.Empty;
    public int ExpiresIn { get; set; }
    public string TokenType { get; set; } = string.Empty;
}

public class LogoutRequest
{
    [Required]
    public string Jti { get; set; } = string.Empty;
}

public class SessionInfo
{
    public int Id { get; set; }
    public string Jti { get; set; } = string.Empty;
    public string IpAddress { get; set; } = string.Empty;
    public string UserAgent { get; set; } = string.Empty;
    public string Ubicacion { get; set; } = string.Empty;
    public string Tipo { get; set; } = string.Empty;
    public bool EsRecordarme { get; set; }
    public DateTime FechaCreacion { get; set; }
    public DateTime UltimoAcceso { get; set; }
    public DateTime FechaExpiracion { get; set; }
    public string? DispositivoNombre { get; set; }
}

public class ForgotPasswordRequest
{
    [Required]
    public string Tenant { get; set; } = string.Empty;
    
    [Required]
    [EmailAddress]
    public string Email { get; set; } = string.Empty;
}

public class ValidateResetTokenRequest
{
    [Required]
    public string Token { get; set; } = string.Empty;
}

public class ValidateRecoveryCodeRequest
{
    [Required]
    public string Code { get; set; } = string.Empty;
}

public class ResetPasswordTokenRequest
{
    [Required]
    public string Token { get; set; } = string.Empty;
    
    [Required]
    [MinLength(8)]
    public string NewPassword { get; set; } = string.Empty;
}

public class ResetPasswordCodeRequest
{
    [Required]
    public string Code { get; set; } = string.Empty;
    
    [Required]
    [MinLength(8)]
    public string NewPassword { get; set; } = string.Empty;
}


