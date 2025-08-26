using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class ChecklistItemPlantilla : BaseEntity
{
    [Required]
    [MaxLength(200)]
    public string Nombre { get; set; } = string.Empty;
    
    [MaxLength(500)]
    public string? Descripcion { get; set; }
    
    [Required]
    public int Orden { get; set; }
    
    [Required]
    [MaxLength(50)]
    public string Tipo { get; set; } = string.Empty; // Checkbox, Radio, Text, Number, Select
    
    [MaxLength(1000)]
    public string? Opciones { get; set; } // Para campos de tipo Select, opciones separadas por |
    
    public bool Obligatorio { get; set; } = true;
    
    public bool PermiteObservacion { get; set; } = false;
    
    [MaxLength(100)]
    public string? Categoria { get; set; } // Seguridad, Motor, Electrico, etc.
    
    public int ChecklistPlantillaId { get; set; }
    
    // Relaciones
    public virtual ChecklistPlantilla ChecklistPlantilla { get; set; } = null!;
}
