using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class AuthLog : BaseEntity
{
    [Required]
    [MaxLength(100)]
    public string Tenant { get; set; } = string.Empty;
    
    public int? UserId { get; set; }
    
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
    [MaxLength(20)]
    public string Result { get; set; } = string.Empty; // ok, fail, locked
    
    [Required]
    public DateTime Timestamp { get; set; }
    
    [MaxLength(100)]
    public string? Jti { get; set; }
}
