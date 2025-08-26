using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class CustomFieldValue : BaseEntity
{
    [Required]
    public int CustomFieldId { get; set; }
    
    [Required]
    [MaxLength(50)]
    public string Entidad { get; set; } = string.Empty; // Buseta, Usuario, etc.
    
    [Required]
    public int EntidadId { get; set; } // ID de la entidad específica
    
    [Required]
    [MaxLength(1000)]
    public string Valor { get; set; } = string.Empty;
    
    // Relaciones
    public virtual CustomField CustomField { get; set; } = null!;
}
