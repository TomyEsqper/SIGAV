using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class Usuario : BaseEntity
{
    [Required]
    [MaxLength(100)]
    public string Nombre { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(100)]
    public string Apellido { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(100)]
    [EmailAddress]
    public string Email { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(100)]
    public string PasswordHash { get; set; } = string.Empty;
    
    [MaxLength(100)]
    public string? Telefono { get; set; }
    
    [MaxLength(100)]
    public string? Documento { get; set; }
    
    [MaxLength(50)]
    public string? TipoDocumento { get; set; } // CC, CE, NIT, etc.
    
    [MaxLength(100)]
    public string? Cargo { get; set; }
    
    [MaxLength(100)]
    public string? Departamento { get; set; }
    
    public DateTime? FechaNacimiento { get; set; }
    
    public DateTime? FechaContratacion { get; set; }
    
    public decimal? Salario { get; set; }
    
    [Required]
    [MaxLength(50)]
    public string Rol { get; set; } = string.Empty; // Admin, Inspector, Mecanico, etc.
    
    public int EmpresaId { get; set; }
    
    // Relaciones
    public virtual Empresa Empresa { get; set; } = null!;
    public virtual ICollection<CustomFieldValue> CustomFieldValues { get; set; } = new List<CustomFieldValue>();
}
