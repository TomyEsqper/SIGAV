namespace Sigav.Api.DTOs;

public record LoginRequest(string Email, string Password);
public record LoginResponse(string AccessToken, string TokenType, DateTime ExpiresAt, UsuarioInfo Usuario);

public record UsuarioInfo
{
    public string Id { get; init; } = string.Empty;
    public string Email { get; init; } = string.Empty;
    public string Nombre { get; init; } = string.Empty;
    public string Apellido { get; init; } = string.Empty;
    public string Rol { get; init; } = string.Empty;
}

public record CreateUserRequest
{
    public string Email { get; init; } = string.Empty;
    public string Password { get; init; } = string.Empty;
    public string Nombre { get; init; } = string.Empty;
    public string Apellido { get; init; } = string.Empty;
    public string Rol { get; init; } = string.Empty;
}
