using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class RecuperacionContrasena : BaseEntity
{
    [Required]
    public int UsuarioId { get; set; }
    
    [Required]
    [MaxLength(100)]
    public string Token { get; set; } = string.Empty; // Token único para reset
    
    [Required]
    [MaxLength(100)]
    public string CodigoRecuperacion { get; set; } = string.Empty; // Código de 6 dígitos
    
    [Required]
    public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
    
    [Required]
    public DateTime FechaExpiracion { get; set; } // 1 hora para tokens, 24 horas para códigos
    
    [Required]
    public DateTime? FechaUso { get; set; } = null; // Cuándo se usó
    
    [Required]
    [MaxLength(45)]
    public string IpAddress { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(500)]
    public string UserAgent { get; set; } = string.Empty;
    
    [MaxLength(100)]
    public string? Ubicacion { get; set; }
    
    [Required]
    [MaxLength(50)]
    public string Tipo { get; set; } = string.Empty; // "email", "codigo_emergencia"
    
    [Required]
    public bool Usado { get; set; } = false;
    
    [Required]
    public bool Activo { get; set; } = true;
    
    // Relaciones
    public virtual Usuario Usuario { get; set; } = null!;
}
