using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class LogSeguridad : BaseEntity
{
    [Required]
    public int? UsuarioId { get; set; } // Nullable para intentos fallidos
    
    [Required]
    [MaxLength(100)]
    public string Tenant { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(100)]
    public string UsernameAttempted { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(45)]
    public string IpAddress { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(500)]
    public string UserAgent { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(50)]
    public string TipoEvento { get; set; } = string.Empty; // "login_exitoso", "login_fallido", "dispositivo_nuevo", "ip_sospechosa"
    
    [Required]
    [MaxLength(20)]
    public string Resultado { get; set; } = string.Empty; // "ok", "fail", "blocked", "suspicious"
    
    [MaxLength(500)]
    public string? Detalles { get; set; } // Información adicional del evento
    
    [MaxLength(100)]
    public string? Ubicacion { get; set; } // Ciudad, País
    
    [Required]
    public DateTime Timestamp { get; set; } = DateTime.UtcNow;
    
    [MaxLength(100)]
    public string? Jti { get; set; } // JWT ID para login exitoso
    
    // Relaciones
    public virtual Usuario? Usuario { get; set; }
}
