using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class ChecklistItemResultado : BaseEntity
{
    [Required]
    public int ChecklistEjecucionId { get; set; }
    
    [Required]
    public int ChecklistItemPlantillaId { get; set; }
    
    [Required]
    [MaxLength(50)]
    public string Resultado { get; set; } = string.Empty; // Aprobado, Rechazado, N/A, etc.
    
    [MaxLength(500)]
    public string? Observacion { get; set; }
    
    [Required]
    public DateTime FechaVerificacion { get; set; } = DateTime.UtcNow;
    
    [MaxLength(100)]
    public string? Valor { get; set; } // Para campos de tipo Text, Number, Select
    
    public bool? Aprobado { get; set; } // Para campos de tipo Checkbox
    
    [MaxLength(100)]
    public string? Evidencia { get; set; } // URL de foto o documento
    
    // Relaciones
    public virtual ChecklistEjecucion ChecklistEjecucion { get; set; } = null!;
    public virtual ChecklistItemPlantilla ChecklistItemPlantilla { get; set; } = null!;
}
