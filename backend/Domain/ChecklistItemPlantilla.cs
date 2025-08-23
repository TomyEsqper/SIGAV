namespace Sigav.Api.Domain;

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
