namespace Sigav.Api.Domain;

public class ChecklistEjecucion
{
    public int Id { get; set; }
    public int BusetaId { get; set; }
    public int ChecklistPlantillaId { get; set; }
    public string InspectorId { get; set; } = string.Empty;
    public DateTime FechaInicio { get; set; } = DateTime.UtcNow;
    public DateTime? FechaCompletado { get; set; }
    public string? ObservacionesGenerales { get; set; }
    public EstadoEjecucion Estado { get; set; } = EstadoEjecucion.EnProceso;
    
    // Navigation properties
    public virtual Buseta Buseta { get; set; } = null!;
    public virtual ChecklistPlantilla Plantilla { get; set; } = null!;
    public virtual Usuario Inspector { get; set; } = null!;
    public virtual ICollection<ChecklistItemResultado> Resultados { get; set; } = new List<ChecklistItemResultado>();
}

public class ChecklistItemResultado
{
    public int Id { get; set; }
    public int ChecklistEjecucionId { get; set; }
    public int ChecklistItemPlantillaId { get; set; }
    public bool Aprobado { get; set; }
    public string? Observacion { get; set; }
    public DateTime FechaVerificacion { get; set; } = DateTime.UtcNow;
    
    // Navigation properties
    public virtual ChecklistEjecucion Ejecucion { get; set; } = null!;
    public virtual ChecklistItemPlantilla ItemPlantilla { get; set; } = null!;
}

public enum EstadoEjecucion
{
    EnProceso,
    Completado,
    Cancelado
}
