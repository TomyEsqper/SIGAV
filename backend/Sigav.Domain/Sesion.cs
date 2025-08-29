using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class Sesion : BaseEntity
{
    [Required]
    public int UsuarioId { get; set; }
    
    [Required]
    [MaxLength(100)]
    public string Jti { get; set; } = string.Empty; // JWT ID único
    
    [Required]
    [MaxLength(100)]
    public string RefreshToken { get; set; } = string.Empty; // Token de renovación
    
    [Required]
    public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
    
    [Required]
    public DateTime FechaExpiracion { get; set; }
    
    [Required]
    public DateTime UltimoAcceso { get; set; } = DateTime.UtcNow;
    
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
    public string Tipo { get; set; } = string.Empty; // "normal", "remember_me", "mobile"
    
    [Required]
    public bool Activa { get; set; } = true;
    
    [Required]
    public bool EsRecordarme { get; set; } = false; // Si es un token de "recordarme"
    
    public int? DispositivoId { get; set; } // Referencia al dispositivo
    
    // Relaciones
    public virtual Usuario Usuario { get; set; } = null!;
    public virtual Dispositivo? Dispositivo { get; set; }
}
