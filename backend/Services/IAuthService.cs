using Sigav.Api.DTOs;
using Sigav.Api.Domain;

namespace Sigav.Api.Services;

public interface IAuthService
{
    Task<LoginResponse> LoginAsync(LoginRequest request);
    Task<Usuario> CreateUserAsync(string email, string password, string nombre, string apellido, RolUsuario rol);
    Task<string> GenerateJwtTokenAsync(Usuario usuario);
}
