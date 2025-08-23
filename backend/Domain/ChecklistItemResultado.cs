namespace Sigav.Api.Domain;

public class ChecklistItemResultado
{
    public int Id { get; set; }
    public int ChecklistEjecucionId { get; set; }
    public int ChecklistItemPlantillaId { get; set; }
    public bool Aprobado { get; set; }
    public string? Observacion { get; set; }
    public DateTime FechaRegistro { get; set; } = DateTime.UtcNow;
    
    // Navigation properties
    public virtual ChecklistEjecucion Ejecucion { get; set; } = null!;
    public virtual ChecklistItemPlantilla ItemPlantilla { get; set; } = null!;
}
