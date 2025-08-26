using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public abstract class BaseEntity
{
    [Key]
    public int Id { get; set; }
    
    public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
    
    public DateTime? FechaActualizacion { get; set; }
    
    public bool Activo { get; set; } = true;
}
