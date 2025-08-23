using Microsoft.AspNetCore.Identity;

namespace Sigav.Api.Domain;

public class Usuario : IdentityUser
{
    public string Nombre { get; set; } = string.Empty;
    public string Apellido { get; set; } = string.Empty;
    public RolUsuario Rol { get; set; }
    public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
    public bool Activo { get; set; } = true;
}

public enum RolUsuario
{
    Admin,
    Inspector,
    Mecanico
}
