namespace Sigav.Api.Domain;

public class ChecklistPlantilla
{
    public int Id { get; set; }
    public string Nombre { get; set; } = string.Empty;
    public string? Descripcion { get; set; }
    public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
    public DateTime? FechaActualizacion { get; set; }
    public bool Activa { get; set; } = true;
    
    // Navigation properties
    public virtual ICollection<ChecklistItemPlantilla> Items { get; set; } = new List<ChecklistItemPlantilla>();
    public virtual ICollection<ChecklistEjecucion> Ejecuciones { get; set; } = new List<ChecklistEjecucion>();
}

public class ChecklistItemPlantilla
{
    public int Id { get; set; }
    public int ChecklistPlantillaId { get; set; }
    public string Nombre { get; set; } = string.Empty;
    public string? Descripcion { get; set; }
    public int Orden { get; set; }
    public bool RequiereObservacion { get; set; } = true;
    
    // Navigation properties
    public virtual ChecklistPlantilla Plantilla { get; set; } = null!;
    public virtual ICollection<ChecklistItemResultado> Resultados { get; set; } = new List<ChecklistItemResultado>();
}
