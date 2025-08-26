using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class Buseta : BaseEntity
{
    [Required]
    [MaxLength(20)]
    public string Placa { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(100)]
    public string Marca { get; set; } = string.Empty;
    
    [Required]
    [MaxLength(100)]
    public string Modelo { get; set; } = string.Empty;
    
    [Required]
    public int Ano { get; set; }
    
    [Required]
    public int Capacidad { get; set; }
    
    [MaxLength(100)]
    public string? Color { get; set; }
    
    [MaxLength(100)]
    public string? NumeroMotor { get; set; }
    
    [MaxLength(100)]
    public string? NumeroChasis { get; set; }
    
    [MaxLength(50)]
    public string Estado { get; set; } = "Disponible"; // Disponible, En Mantenimiento, En Ruta, Fuera de Servicio
    
    [MaxLength(100)]
    public string? Combustible { get; set; } // Gasolina, Diesel, Electrico, Hibrido
    
    public decimal? ConsumoPromedio { get; set; } // L/100km
    
    public DateTime? UltimaRevision { get; set; }
    
    public DateTime? ProximaRevision { get; set; }
    
    public int? Kilometraje { get; set; }
    
    [MaxLength(500)]
    public string? Observaciones { get; set; }
    
    public int EmpresaId { get; set; }
    
    // Relaciones
    public virtual Empresa Empresa { get; set; } = null!;
    public virtual ICollection<CustomFieldValue> CustomFieldValues { get; set; } = new List<CustomFieldValue>();
}
