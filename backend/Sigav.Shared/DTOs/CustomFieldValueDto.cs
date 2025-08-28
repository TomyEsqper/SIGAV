using System.ComponentModel.DataAnnotations;

namespace Sigav.Shared.DTOs;

public class CustomFieldValueDto
{
    public int Id { get; set; }
    public int CustomFieldId { get; set; }
    public string? CustomFieldNombre { get; set; }
    public string? CustomFieldTipo { get; set; }
    public string? Valor { get; set; }
    public int? EntidadId { get; set; }
    public string? EntidadTipo { get; set; }
    public DateTime FechaCreacion { get; set; }
    public DateTime? FechaActualizacion { get; set; }
    public bool Activo { get; set; }
}

public class CreateCustomFieldValueDto
{
    [Required(ErrorMessage = "El campo personalizado es requerido")]
    public int CustomFieldId { get; set; }

    [Required(ErrorMessage = "El valor es requerido")]
    [StringLength(500, ErrorMessage = "El valor no puede exceder 500 caracteres")]
    public string Valor { get; set; } = string.Empty;

    [Required(ErrorMessage = "El ID de la entidad es requerido")]
    public int EntidadId { get; set; }

    [Required(ErrorMessage = "El tipo de entidad es requerido")]
    [StringLength(50, ErrorMessage = "El tipo de entidad no puede exceder 50 caracteres")]
    public string EntidadTipo { get; set; } = string.Empty;
}

public class UpdateCustomFieldValueDto
{
    [Required(ErrorMessage = "El valor es requerido")]
    [StringLength(500, ErrorMessage = "El valor no puede exceder 500 caracteres")]
    public string Valor { get; set; } = string.Empty;
}
