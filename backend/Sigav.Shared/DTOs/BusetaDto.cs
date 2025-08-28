using System.ComponentModel.DataAnnotations;

namespace Sigav.Shared.DTOs;

public class BusetaDto
{
    public int Id { get; set; }
    public string Placa { get; set; } = string.Empty;
    public string Marca { get; set; } = string.Empty;
    public string Modelo { get; set; } = string.Empty;
    public int Ano { get; set; }
    public string Color { get; set; } = string.Empty;
    public int EmpresaId { get; set; }
    public string? EmpresaNombre { get; set; }
    public DateTime FechaCreacion { get; set; }
    public DateTime? FechaActualizacion { get; set; }
    public bool Activo { get; set; }
    public List<CustomFieldValueDto>? CustomFieldValues { get; set; }
}

public class CreateBusetaDto
{
    public string Placa { get; set; } = string.Empty;
    public string Marca { get; set; } = string.Empty;
    public string Modelo { get; set; } = string.Empty;
    public int Ano { get; set; }
    public int Capacidad { get; set; }
    public string? Color { get; set; }
    public string? NumeroMotor { get; set; }
    public string? NumeroChasis { get; set; }
    public string Estado { get; set; } = "Disponible";
    public string? Combustible { get; set; }
    public decimal? ConsumoPromedio { get; set; }
    public DateTime? UltimaRevision { get; set; }
    public DateTime? ProximaRevision { get; set; }
    public int? Kilometraje { get; set; }
    public string? Observaciones { get; set; }
    public int EmpresaId { get; set; }
}

public class UpdateBusetaDto
{
    public string Placa { get; set; } = string.Empty;
    public string Marca { get; set; } = string.Empty;
    public string Modelo { get; set; } = string.Empty;
    public int Ano { get; set; }
    public int Capacidad { get; set; }
    public string? Color { get; set; }
    public string? NumeroMotor { get; set; }
    public string? NumeroChasis { get; set; }
    public string Estado { get; set; } = "Disponible";
    public string? Combustible { get; set; }
    public decimal? ConsumoPromedio { get; set; }
    public DateTime? UltimaRevision { get; set; }
    public DateTime? ProximaRevision { get; set; }
    public int? Kilometraje { get; set; }
    public string? Observaciones { get; set; }
    public int EmpresaId { get; set; }
}
