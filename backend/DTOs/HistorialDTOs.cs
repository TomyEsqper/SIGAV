namespace Sigav.Api.DTOs;

public record HistorialFiltrosRequest
{
    public int? BusetaId { get; init; }
    public DateTime? From { get; init; }
    public DateTime? To { get; init; }
    public int Page { get; init; } = 1;
    public int PageSize { get; init; } = 20;
}

public record HistorialResponse
{
    public List<ChecklistEjecucionResponse> Items { get; init; } = new();
    public int Total { get; init; }
    public int Page { get; init; }
    public int PageSize { get; init; }
    public int TotalPages { get; init; }
}

public record ExportRequest
{
    public int? BusetaId { get; init; }
    public DateTime? From { get; init; }
    public DateTime? To { get; init; }
}
