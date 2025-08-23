using Sigav.Api.Domain;

namespace Sigav.Api.DTOs;

public record CreateBusetaRequest
{
    public string Placa { get; init; } = string.Empty;
    public string Modelo { get; init; } = string.Empty;
    public int Capacidad { get; init; }
    public string Agencia { get; init; } = string.Empty;
}

public record UpdateBusetaRequest
{
    public string Modelo { get; init; } = string.Empty;
    public int Capacidad { get; init; }
    public string Agencia { get; init; } = string.Empty;
}

public record UpdateEstadoRequest
{
    public string Estado { get; init; } = string.Empty;
}

public record BusetaResponse
{
    public int Id { get; init; }
    public string Placa { get; init; } = string.Empty;
    public string Modelo { get; init; } = string.Empty;
    public int Capacidad { get; init; }
    public string Agencia { get; init; } = string.Empty;
    public string Estado { get; init; } = string.Empty;
    public DateTime FechaCreacion { get; init; }
    public DateTime? FechaActualizacion { get; init; }
}

public record BusetaListResponse
{
    public List<BusetaResponse> Items { get; init; } = new();
    public int Total { get; init; }
    public int Page { get; init; }
    public int PageSize { get; init; }
    public int TotalPages { get; init; }
}
