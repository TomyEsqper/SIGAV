using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class Dispositivo : BaseEntity
{
    [Required]
    public int UsuarioId { get; set; }
    
    [Required]
    [MaxLength(100)]
    public string Nombre { get; set; } = string.Empty; // "Chrome en Windows", "iPhone 12", etc.
    
    [Required]
    [MaxLength(100)]
    public string Tipo { get; set; } = string.Empty; // "browser", "mobile", "desktop"
    
    [Required]
    [MaxLength(500)]
    public string UserAgent { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(45)]
    public string IpAddress { get; set; } = string.Empty;
    
    [MaxLength(100)]
    public string? Ubicacion { get; set; } // Ciudad, País
    
    [Required]
    public DateTime FechaRegistro { get; set; } = DateTime.UtcNow;
    
    [Required]
    public DateTime UltimoAcceso { get; set; } = DateTime.UtcNow;
    
    [Required]
    public bool EsConfiable { get; set; } = false;
    
    [Required]
    public bool Activo { get; set; } = true;
    
    // Relaciones
    public virtual Usuario Usuario { get; set; } = null!;
}
