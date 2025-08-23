namespace Sigav.Api.DTOs;

// Auth
public record LoginRequest(string Email, string Password);

// Busetas
public record CreateBusetaRequest(string Placa, string Modelo, int Capacidad, string Agencia);
public record UpdateBusetaRequest(string Modelo, int Capacidad, string Agencia);
public record ChangeEstadoRequest(EstadoBuseta Estado);

// Checklists - Plantillas
public record CreateChecklistPlantillaRequest(string Nombre, string Descripcion, List<CreateChecklistItemRequest> Items);
public record CreateChecklistItemRequest(string Nombre, string Descripcion, int Orden, bool RequiereObservacion = true);
public record UpdateChecklistPlantillaRequest(string Nombre, string Descripcion, List<UpdateChecklistItemRequest> Items);
public record UpdateChecklistItemRequest(Guid Id, string Nombre, string Descripcion, int Orden, bool RequiereObservacion = true);

// Checklists - Ejecuciones
public record CreateChecklistEjecucionRequest(Guid BusetaId, Guid PlantillaId);
public record CompletarChecklistRequest(List<CompletarItemRequest> Items, string? ObservacionesGenerales);
public record CompletarItemRequest(Guid ItemPlantillaId, bool Aprobado, string? Observacion);

// Historial
public record HistorialFilterRequest(Guid? BusetaId = null, DateTime? From = null, DateTime? To = null, int Page = 1, int PageSize = 20);


