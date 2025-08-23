using Microsoft.AspNetCore.Identity;
using Microsoft.IdentityModel.Tokens;
using Sigav.Api.DTOs;
using Sigav.Api.Domain;
using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Text;

namespace Sigav.Api.Services;

public interface IAuthService
{
    Task<LoginResponse?> LoginAsync(string email, string password);
    Task<ProfileResponse?> GetProfileAsync(Guid userId);
    Task<bool> CreateUserAsync(string email, string password, string nombre, string apellido, RolUsuario rol);
}

public class AuthService : IAuthService
{
    private readonly UserManager<Usuario> _userManager;
    private readonly IConfiguration _configuration;

    public AuthService(UserManager<Usuario> userManager, IConfiguration configuration)
    {
        _userManager = userManager;
        _configuration = configuration;
    }

    public async Task<LoginResponse> LoginAsync(string email, string password)
    {
        var user = await _userManager.FindByEmailAsync(email);
        if (user == null || !await _userManager.CheckPasswordAsync(user, password))
        {
            throw new UnauthorizedAccessException("Credenciales inválidas");
        }

        if (!user.Activo)
        {
            throw new UnauthorizedAccessException("Usuario inactivo");
        }

        var token = await GenerateJwtTokenAsync(user);
        var expiresAt = DateTime.UtcNow.AddHours(24);

        return new LoginResponse(
            token,
            "Bearer",
            expiresAt,
            new UsuarioInfo
            {
                Id = user.Id,
                Email = user.Email!,
                Nombre = user.Nombre,
                Apellido = user.Apellido,
                Rol = user.Rol.ToString()
            }
        );
    }

    public async Task<ProfileResponse?> GetProfileAsync(Guid userId)
    {
        var user = await _userManager.FindByIdAsync(userId.ToString());
        if (user == null) return null;

        return new ProfileResponse(user.Email, user.Nombre, user.Apellido, user.Rol.ToString());
    }

    public async Task<bool> CreateUserAsync(string email, string password, string nombre, string apellido, RolUsuario rol)
    {
        if (await _userManager.FindByEmailAsync(email) != null)
            return false;

        var user = new Usuario
        {
            UserName = email,
            Email = email,
            Nombre = nombre,
            Apellido = apellido,
            Rol = rol,
            EmailConfirmed = true
        };

        var result = await _userManager.CreateAsync(user, password);
        if (!result.Succeeded)
        {
            var errors = string.Join(", ", result.Errors.Select(e => e.Description));
            throw new InvalidOperationException($"Error al crear usuario: {errors}");
        }

        return true;
    }

    public async Task<Usuario?> GetUserByIdAsync(string id)
    {
        return await _userManager.FindByIdAsync(id);
    }

    private async Task<string> GenerateJwtTokenAsync(Usuario user)
    {
        var jwtSection = _configuration.GetSection("Jwt");
        var key = Encoding.UTF8.GetBytes(jwtSection["Key"]!);

        var claims = new List<Claim>
        {
            new(ClaimTypes.NameIdentifier, user.Id),
            new(ClaimTypes.Email, user.Email!),
            new(ClaimTypes.Name, $"{user.Nombre} {user.Apellido}"),
            new(ClaimTypes.Role, user.Rol.ToString())
        };

        var tokenDescriptor = new SecurityTokenDescriptor
        {
            Subject = new ClaimsIdentity(claims),
            Expires = DateTime.UtcNow.AddHours(24),
            Issuer = jwtSection["Issuer"],
            Audience = jwtSection["Audience"],
            SigningCredentials = new SigningCredentials(new SymmetricSecurityKey(key), SecurityAlgorithms.HmacSha256Signature)
        };

        var tokenHandler = new JwtSecurityTokenHandler();
        var token = tokenHandler.CreateToken(tokenDescriptor);
        return tokenHandler.WriteToken(token);
    }
}
