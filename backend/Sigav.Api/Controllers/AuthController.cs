using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using Sigav.Api.Data;
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

    public AuthController(SigavDbContext context, IConfiguration configuration, ILogger<AuthController> logger)
    {
        _context = context;
        _configuration = configuration;
        _logger = logger;
    }

    [HttpPost("login")]
    public async Task<IActionResult> Login([FromBody] LoginRequest request)
    {
        try
        {
            // Validar request
            if (!ModelState.IsValid)
            {
                return BadRequest(new { message = "Credenciales inválidas" });
            }

            // Sanitizar inputs
            var tenant = request.Tenant?.Trim().ToLowerInvariant();
            var usernameOrEmail = request.UsernameOrEmail?.Trim();

            if (string.IsNullOrEmpty(tenant) || string.IsNullOrEmpty(usernameOrEmail) || string.IsNullOrEmpty(request.Password))
            {
                return BadRequest(new { message = "Credenciales inválidas" });
            }

            // Buscar tenant
            var empresas = await _context.Empresas
                .Where(e => e.Activo)
                .ToListAsync();
            
            var empresa = empresas.FirstOrDefault(e => e.Nombre.ToLowerInvariant() == tenant);

            if (empresa == null)
            {
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
                _logger.LogWarning("Login attempt with invalid credentials for tenant: {Tenant}, username: {Username}", tenant, usernameOrEmail);
                return BadRequest(new { message = "Credenciales inválidas" });
            }

            // Verificar si la cuenta está bloqueada
            if (usuario.FailedAttempts >= 5 && usuario.LockedUntil.HasValue && usuario.LockedUntil > DateTime.UtcNow)
            {
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
                }
                await _context.SaveChangesAsync();

                _logger.LogWarning("Failed login attempt for user: {UserId}", usuario.Id);
                return BadRequest(new { message = "Credenciales inválidas" });
            }

            // Login exitoso - resetear contadores
            usuario.FailedAttempts = 0;
            usuario.LockedUntil = null;
            usuario.LastLoginAt = DateTime.UtcNow;
            await _context.SaveChangesAsync();

            // Generar JWT
            var token = GenerateJwtToken(usuario, empresa);

            // Log de auditoría
            await LogAuthAttempt(tenant, usuario.Id, usernameOrEmail, true, token.Id);

            _logger.LogInformation("Successful login for user: {UserId} in tenant: {Tenant}", usuario.Id, tenant);

            return Ok(new LoginResponse
            {
                AccessToken = token.Token,
                ExpiresIn = 900, // 15 minutos
                TokenType = "Bearer",
                User = new UserInfo
                {
                    Id = usuario.Id.ToString(),
                    Name = $"{usuario.Nombre} {usuario.Apellido}",
                    Email = usuario.Email
                },
                Tenant = tenant
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
}

public class LoginRequest
{
    [Required]
    public string Tenant { get; set; } = string.Empty;
    
    [Required]
    public string UsernameOrEmail { get; set; } = string.Empty;
    
    [Required]
    public string Password { get; set; } = string.Empty;
}

public class LoginResponse
{
    public string AccessToken { get; set; } = string.Empty;
    public int ExpiresIn { get; set; }
    public string TokenType { get; set; } = string.Empty;
    public UserInfo User { get; set; } = new();
    public string Tenant { get; set; } = string.Empty;
}

public class UserInfo
{
    public string Id { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string Email { get; set; } = string.Empty;
}


