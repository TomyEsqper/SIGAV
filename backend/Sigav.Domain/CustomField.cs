using System.ComponentModel.DataAnnotations;

namespace Sigav.Domain;

public class CustomField : BaseEntity
{
    [Required]
    [MaxLength(100)]
    public string Nombre { get; set; } = string.Empty;
    
    [MaxLength(500)]
    public string? Descripcion { get; set; }
    
    [Required]
    [MaxLength(50)]
    public string Tipo { get; set; } = string.Empty; // Text, Number, Date, Boolean, Select, etc.
    
    [MaxLength(1000)]
    public string? Opciones { get; set; } // Para campos de tipo Select, opciones separadas por |
    
    [Required]
    [MaxLength(50)]
    public string Entidad { get; set; } = string.Empty; // Buseta, Empleado, Checklist, etc.
    
    public bool Requerido { get; set; } = false;
    
    public int Orden { get; set; } = 0;
    
    public int EmpresaId { get; set; }
    
    // Relación con la empresa
    public virtual Empresa Empresa { get; set; } = null!;
}
