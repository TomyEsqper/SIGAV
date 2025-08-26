using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class ChecklistEjecucion : BaseEntity
{
    [Required]
    public int BusetaId { get; set; }
    
    [Required]
    public int ChecklistPlantillaId { get; set; }
    
    [Required]
    public int InspectorId { get; set; }
    
    [Required]
    public DateTime FechaInicio { get; set; } = DateTime.UtcNow;
    
    public DateTime? FechaFin { get; set; }
    
    [MaxLength(1000)]
    public string? ObservacionesGenerales { get; set; }
    
    [Required]
    [MaxLength(50)]
    public string Estado { get; set; } = "En Progreso"; // En Progreso, Completado, Cancelado
    
    public bool Completado { get; set; } = false;
    
    public int? TiempoTotal { get; set; } // En minutos
    
    [MaxLength(100)]
    public string? Ubicacion { get; set; }
    
    [MaxLength(100)]
    public string? CondicionesClimaticas { get; set; }
    
    // Relaciones
    public virtual Buseta Buseta { get; set; } = null!;
    public virtual ChecklistPlantilla ChecklistPlantilla { get; set; } = null!;
    public virtual Usuario Inspector { get; set; } = null!;
    public virtual ICollection<ChecklistItemResultado> ItemsResultado { get; set; } = new List<ChecklistItemResultado>();
    public virtual ICollection<CustomFieldValue> CustomFieldValues { get; set; } = new List<CustomFieldValue>();
}
