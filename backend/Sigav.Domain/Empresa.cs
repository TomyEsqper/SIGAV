using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class Empresa : BaseEntity
{
    [Required]
    [MaxLength(200)]
    public string Nombre { get; set; } = string.Empty;
    
    [MaxLength(500)]
    public string? Descripcion { get; set; }
    
    [MaxLength(100)]
    public string? Nit { get; set; }
    
    [MaxLength(200)]
    public string? Direccion { get; set; }
    
    [MaxLength(100)]
    public string? Telefono { get; set; }
    
    [MaxLength(100)]
    public string? Email { get; set; }
    
    [MaxLength(100)]
    public string? SitioWeb { get; set; }
    
    public string? LogoUrl { get; set; }
    
    // Relaciones
    public virtual ICollection<CustomField> CustomFields { get; set; } = new List<CustomField>();
    public virtual ICollection<Usuario> Usuarios { get; set; } = new List<Usuario>();
    public virtual ICollection<Buseta> Busetas { get; set; } = new List<Buseta>();
}
