namespace Sigav.Api.DTOs;

// Auth
public record LoginResponse(string AccessToken, string Email, string Nombre, string Apellido, string Rol);
public record ProfileResponse(string Email, string Nombre, string Apellido, string Rol);

// Busetas
public record BusetaResponse(Guid Id, string Placa, string Modelo, int Capacidad, string Agencia, EstadoBuseta Estado, DateTime CreatedAtUtc);
public record BusetaListResponse(List<BusetaResponse> Items, int Total, int Page, int PageSize);

// Checklists - Plantillas
public record ChecklistPlantillaResponse(Guid Id, string Nombre, string Descripcion, bool Activa, List<ChecklistItemPlantillaResponse> Items);
public record ChecklistItemPlantillaResponse(Guid Id, string Nombre, string Descripcion, int Orden, bool RequiereObservacion);
public record ChecklistPlantillaListResponse(List<ChecklistPlantillaResponse> Items, int Total);

// Checklists - Ejecuciones
public record ChecklistEjecucionResponse(Guid Id, Guid BusetaId, string BusetaPlaca, Guid PlantillaId, string PlantillaNombre, Guid InspectorId, string InspectorNombre, DateTime FechaInicio, DateTime? FechaCompletado, string? ObservacionesGenerales, bool Completado, List<ChecklistItemResultadoResponse> Resultados);
public record ChecklistItemResultadoResponse(Guid Id, Guid ItemPlantillaId, string ItemNombre, string ItemDescripcion, bool Aprobado, string? Observacion);
public record ChecklistEjecucionListResponse(List<ChecklistEjecucionResponse> Items, int Total, int Page, int PageSize);

// Historial
public record HistorialResponse(List<ChecklistEjecucionResponse> Items, int Total, int Page, int PageSize);

// Common
public record PaginatedResponse<T>(List<T> Items, int Total, int Page, int PageSize) where T : class;
public record ErrorResponse(string Message, string? Details = null);


