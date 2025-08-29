using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class IpBloqueada : BaseEntity
{
    [Required]
    [MaxLength(45)]
    public string IpAddress { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(100)]
    public string Razon { get; set; } = string.Empty; // "multiple_failed_attempts", "suspicious_activity", "manual_block"
    
    [Required]
    public DateTime FechaBloqueo { get; set; } = DateTime.UtcNow;
    
    [Required]
    public DateTime FechaExpiracion { get; set; } // Cuándo se desbloquea automáticamente
    
    [MaxLength(500)]
    public string? Detalles { get; set; } // Información adicional del bloqueo
    
    [Required]
    public bool Activo { get; set; } = true;
    
    [Required]
    public int IntentosFallidos { get; set; } = 0; // Contador de intentos fallidos
}
