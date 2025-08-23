namespace Sigav.Api.Domain;

public class Buseta
{
    public int Id { get; set; }
    public string Placa { get; set; } = string.Empty;
    public string Modelo { get; set; } = string.Empty;
    public int Capacidad { get; set; }
    public string Agencia { get; set; } = string.Empty;
    public EstadoBuseta Estado { get; set; } = EstadoBuseta.Disponible;
    public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
    public DateTime? FechaActualizacion { get; set; }
    
    // Navigation properties
    public virtual ICollection<ChecklistEjecucion> ChecklistEjecuciones { get; set; } = new List<ChecklistEjecucion>();
}

public enum EstadoBuseta
{
    Disponible,
    EnMantenimiento,
    EnRuta
}
