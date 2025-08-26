using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class ChecklistPlantilla : BaseEntity
{
    [Required]
    [MaxLength(200)]
    public string Nombre { get; set; } = string.Empty;
    
    [MaxLength(500)]
    public string? Descripcion { get; set; }
    
    [Required]
    [MaxLength(50)]
    public string Tipo { get; set; } = string.Empty; // Diario, Semanal, Mensual, Pre-Viaje, etc.
    
    public bool Activa { get; set; } = true;
    
    public int? TiempoEstimado { get; set; } // En minutos
    
    public int EmpresaId { get; set; }
    
    // Relaciones
    public virtual Empresa Empresa { get; set; } = null!;
    public virtual ICollection<ChecklistItemPlantilla> Items { get; set; } = new List<ChecklistItemPlantilla>();
    public virtual ICollection<CustomFieldValue> CustomFieldValues { get; set; } = new List<CustomFieldValue>();
}
