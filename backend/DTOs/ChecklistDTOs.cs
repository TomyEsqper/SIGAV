namespace Sigav.Api.DTOs;

public record CreateChecklistPlantillaRequest
{
    public string Nombre { get; init; } = string.Empty;
    public string? Descripcion { get; init; }
    public List<CreateChecklistItemRequest> Items { get; init; } = new();
}

public record CreateChecklistItemRequest
{
    public string Nombre { get; init; } = string.Empty;
    public string? Descripcion { get; init; }
    public int Orden { get; init; }
    public bool RequiereObservacion { get; init; } = true;
}

public record UpdateChecklistPlantillaRequest
{
    public string Nombre { get; init; } = string.Empty;
    public string? Descripcion { get; init; }
    public List<UpdateChecklistItemRequest> Items { get; init; } = new();
}

public record UpdateChecklistItemRequest
{
    public int Id { get; init; }
    public string Nombre { get; init; } = string.Empty;
    public string? Descripcion { get; init; }
    public int Orden { get; init; }
    public bool RequiereObservacion { get; init; } = true;
}

public record ChecklistPlantillaResponse
{
    public int Id { get; init; }
    public string Nombre { get; init; } = string.Empty;
    public string? Descripcion { get; init; }
    public DateTime FechaCreacion { get; init; }
    public DateTime? FechaActualizacion { get; init; }
    public bool Activa { get; init; }
    public List<ChecklistItemResponse> Items { get; init; } = new();
}

public record ChecklistItemResponse
{
    public int Id { get; init; }
    public string Nombre { get; init; } = string.Empty;
    public string? Descripcion { get; init; }
    public int Orden { get; init; }
    public bool RequiereObservacion { get; init; }
}

public record IniciarChecklistRequest
{
    public int BusetaId { get; init; }
    public int PlantillaId { get; init; }
}

public record CompletarChecklistRequest
{
    public List<ChecklistItemResultadoRequest> Resultados { get; init; } = new();
    public string? ObservacionesGenerales { get; init; }
}

public record ChecklistItemResultadoRequest
{
    public int ItemPlantillaId { get; init; }
    public bool Aprobado { get; init; }
    public string? Observacion { get; init; }
}

public record ChecklistEjecucionResponse
{
    public int Id { get; init; }
    public int BusetaId { get; init; }
    public string PlacaBuseta { get; init; } = string.Empty;
    public int PlantillaId { get; init; }
    public string NombrePlantilla { get; init; } = string.Empty;
    public string InspectorId { get; init; } = string.Empty;
    public string NombreInspector { get; init; } = string.Empty;
    public DateTime FechaInicio { get; init; }
    public DateTime? FechaCompletado { get; init; }
    public string? ObservacionesGenerales { get; init; }
    public string Estado { get; init; } = string.Empty;
    public List<ChecklistItemResultadoResponse> Resultados { get; init; } = new();
}

public record ChecklistItemResultadoResponse
{
    public int Id { get; init; }
    public int ItemPlantillaId { get; init; }
    public string NombreItem { get; init; } = string.Empty;
    public string? DescripcionItem { get; init; }
    public bool Aprobado { get; init; }
    public string? Observacion { get; init; }
    public DateTime FechaVerificacion { get; init; }
}
