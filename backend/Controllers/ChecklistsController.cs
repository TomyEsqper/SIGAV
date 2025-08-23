using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Api.DTOs;
using Sigav.Api.Domain;
using System.Security.Claims;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/v1/[controller]")]
[Tags("Checklists")]
[Authorize]
public class ChecklistsController : ControllerBase
{
    private readonly SigavDbContext _context;
    private readonly ILogger<ChecklistsController> _logger;

    public ChecklistsController(SigavDbContext context, ILogger<ChecklistsController> logger)
    {
        _context = context;
        _logger = logger;
    }

    #region Plantillas

    [HttpGet("plantillas")]
    public async Task<ActionResult<List<ChecklistPlantillaResponse>>> GetPlantillas()
    {
        try
        {
            var plantillas = await _context.ChecklistPlantillas
                .Where(p => p.Activa)
                .Include(p => p.Items.OrderBy(i => i.Orden))
                .Select(p => new ChecklistPlantillaResponse
                {
                    Id = p.Id,
                    Nombre = p.Nombre,
                    Descripcion = p.Descripcion,
                    FechaCreacion = p.FechaCreacion,
                    FechaActualizacion = p.FechaActualizacion,
                    Activa = p.Activa,
                    Items = p.Items.Select(i => new ChecklistItemResponse
                    {
                        Id = i.Id,
                        Nombre = i.Nombre,
                        Descripcion = i.Descripcion,
                        Orden = i.Orden,
                        RequiereObservacion = i.RequiereObservacion
                    }).ToList()
                })
                .ToListAsync();

            return Ok(plantillas);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al obtener plantillas");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpGet("plantillas/{id}")]
    public async Task<ActionResult<ChecklistPlantillaResponse>> GetPlantilla(int id)
    {
        try
        {
            var plantilla = await _context.ChecklistPlantillas
                .Where(p => p.Id == id && p.Activa)
                .Include(p => p.Items.OrderBy(i => i.Orden))
                .Select(p => new ChecklistPlantillaResponse
                {
                    Id = p.Id,
                    Nombre = p.Nombre,
                    Descripcion = p.Descripcion,
                    FechaCreacion = p.FechaCreacion,
                    FechaActualizacion = p.FechaActualizacion,
                    Activa = p.Activa,
                    Items = p.Items.Select(i => new ChecklistItemResponse
                    {
                        Id = i.Id,
                        Nombre = i.Nombre,
                        Descripcion = i.Descripcion,
                        Orden = i.Orden,
                        RequiereObservacion = i.RequiereObservacion
                    }).ToList()
                })
                .FirstOrDefaultAsync();

            if (plantilla == null)
                return NotFound(new { message = "Plantilla no encontrada" });

            return Ok(plantilla);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al obtener plantilla {Id}", id);
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("plantillas")]
    [Authorize(Policy = "AdminOnly")]
    public async Task<ActionResult<ChecklistPlantillaResponse>> CreatePlantilla([FromBody] CreateChecklistPlantillaRequest request)
    {
        try
        {
            var plantilla = new ChecklistPlantilla
            {
                Nombre = request.Nombre,
                Descripcion = request.Descripcion,
                Items = request.Items.Select((item, index) => new ChecklistItemPlantilla
                {
                    Nombre = item.Nombre,
                    Descripcion = item.Descripcion,
                    Orden = item.Orden > 0 ? item.Orden : index + 1,
                    RequiereObservacion = item.RequiereObservacion
                }).ToList()
            };

            _context.ChecklistPlantillas.Add(plantilla);
            await _context.SaveChangesAsync();

            var response = new ChecklistPlantillaResponse
            {
                Id = plantilla.Id,
                Nombre = plantilla.Nombre,
                Descripcion = plantilla.Descripcion,
                FechaCreacion = plantilla.FechaCreacion,
                FechaActualizacion = plantilla.FechaActualizacion,
                Activa = plantilla.Activa,
                Items = plantilla.Items.Select(i => new ChecklistItemResponse
                {
                    Id = i.Id,
                    Nombre = i.Nombre,
                    Descripcion = i.Descripcion,
                    Orden = i.Orden,
                    RequiereObservacion = i.RequiereObservacion
                }).ToList()
            };

            return CreatedAtAction(nameof(GetPlantilla), new { id = plantilla.Id }, response);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al crear plantilla");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPut("plantillas/{id}")]
    [Authorize(Policy = "AdminOnly")]
    public async Task<ActionResult<ChecklistPlantillaResponse>> UpdatePlantilla(int id, [FromBody] UpdateChecklistPlantillaRequest request)
    {
        try
        {
            var plantilla = await _context.ChecklistPlantillas
                .Include(p => p.Items)
                .FirstOrDefaultAsync(p => p.Id == id && p.Activa);

            if (plantilla == null)
                return NotFound(new { message = "Plantilla no encontrada" });

            plantilla.Nombre = request.Nombre;
            plantilla.Descripcion = request.Descripcion;
            plantilla.FechaActualizacion = DateTime.UtcNow;

            // Remove existing items
            _context.ChecklistItemPlantillas.RemoveRange(plantilla.Items);

            // Add new items
            plantilla.Items = request.Items.Select((item, index) => new ChecklistItemPlantilla
            {
                Nombre = item.Nombre,
                Descripcion = item.Descripcion,
                Orden = item.Orden > 0 ? item.Orden : index + 1,
                RequiereObservacion = item.RequiereObservacion
            }).ToList();

            await _context.SaveChangesAsync();

            var response = new ChecklistPlantillaResponse
            {
                Id = plantilla.Id,
                Nombre = plantilla.Nombre,
                Descripcion = plantilla.Descripcion,
                FechaCreacion = plantilla.FechaCreacion,
                FechaActualizacion = plantilla.FechaActualizacion,
                Activa = plantilla.Activa,
                Items = plantilla.Items.Select(i => new ChecklistItemResponse
                {
                    Id = i.Id,
                    Nombre = i.Nombre,
                    Descripcion = i.Descripcion,
                    Orden = i.Orden,
                    RequiereObservacion = i.RequiereObservacion
                }).ToList()
            };

            return Ok(response);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al actualizar plantilla {Id}", id);
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    #endregion

    #region Ejecuciones

    [HttpPost("ejecuciones")]
    [Authorize(Policy = "InspectorOrAdmin")]
    public async Task<ActionResult<ChecklistEjecucionResponse>> IniciarChecklist([FromBody] IniciarChecklistRequest request)
    {
        try
        {
            var buseta = await _context.Busetas.FindAsync(request.BusetaId);
            if (buseta == null)
                return NotFound(new { message = "Buseta no encontrada" });

            var plantilla = await _context.ChecklistPlantillas
                .Include(p => p.Items)
                .FirstOrDefaultAsync(p => p.Id == request.PlantillaId && p.Activa);
            if (plantilla == null)
                return NotFound(new { message = "Plantilla no encontrada" });

            var inspectorId = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            if (string.IsNullOrEmpty(inspectorId))
                return Unauthorized(new { message = "Usuario no identificado" });

            var ejecucion = new ChecklistEjecucion
            {
                BusetaId = request.BusetaId,
                ChecklistPlantillaId = request.PlantillaId,
                InspectorId = inspectorId,
                Estado = EstadoEjecucion.EnProceso
            };

            _context.ChecklistEjecuciones.Add(ejecucion);
            await _context.SaveChangesAsync();

            var response = new ChecklistEjecucionResponse
            {
                Id = ejecucion.Id,
                BusetaId = ejecucion.BusetaId,
                PlacaBuseta = buseta.Placa,
                PlantillaId = ejecucion.ChecklistPlantillaId,
                NombrePlantilla = plantilla.Nombre,
                InspectorId = ejecucion.InspectorId,
                NombreInspector = "", // Will be populated when viewing
                FechaInicio = ejecucion.FechaInicio,
                FechaCompletado = ejecucion.FechaCompletado,
                ObservacionesGenerales = ejecucion.ObservacionesGenerales,
                Estado = ejecucion.Estado.ToString(),
                Resultados = new List<ChecklistItemResultadoResponse>()
            };

            return CreatedAtAction(nameof(GetEjecucion), new { id = ejecucion.Id }, response);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al iniciar checklist");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost("ejecuciones/{id}/completar")]
    [Authorize(Policy = "InspectorOrAdmin")]
    public async Task<ActionResult<ChecklistEjecucionResponse>> CompletarChecklist(int id, [FromBody] CompletarChecklistRequest request)
    {
        try
        {
            var ejecucion = await _context.ChecklistEjecuciones
                .Include(e => e.Resultados)
                .FirstOrDefaultAsync(e => e.Id == id);

            if (ejecucion == null)
                return NotFound(new { message = "Ejecución no encontrada" });

            if (ejecucion.Estado != EstadoEjecucion.EnProceso)
                return BadRequest(new { message = "La ejecución ya no está en proceso" });

            // Remove existing results
            _context.ChecklistItemResultados.RemoveRange(ejecucion.Resultados);

            // Add new results
            ejecucion.Resultados = request.Resultados.Select(r => new ChecklistItemResultado
            {
                ChecklistEjecucionId = id,
                ChecklistItemPlantillaId = r.ItemPlantillaId,
                Aprobado = r.Aprobado,
                Observacion = r.Observacion
            }).ToList();

            ejecucion.ObservacionesGenerales = request.ObservacionesGenerales;
            ejecucion.Estado = EstadoEjecucion.Completado;
            ejecucion.FechaCompletado = DateTime.UtcNow;

            await _context.SaveChangesAsync();

            return await GetEjecucion(id);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al completar checklist {Id}", id);
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpGet("ejecuciones")]
    public async Task<ActionResult<List<ChecklistEjecucionResponse>>> GetEjecuciones(
        [FromQuery] int? busetaId,
        [FromQuery] DateTime? from,
        [FromQuery] DateTime? to)
    {
        try
        {
            var query = _context.ChecklistEjecuciones
                .Include(e => e.Buseta)
                .Include(e => e.Plantilla)
                .Include(e => e.Inspector)
                .Include(e => e.Resultados)
                .ThenInclude(r => r.ItemPlantilla)
                .AsQueryable();

            if (busetaId.HasValue)
                query = query.Where(e => e.BusetaId == busetaId.Value);

            if (from.HasValue)
                query = query.Where(e => e.FechaInicio >= from.Value);

            if (to.HasValue)
                query = query.Where(e => e.FechaInicio <= to.Value);

            var ejecuciones = await query
                .OrderByDescending(e => e.FechaInicio)
                .Select(e => new ChecklistEjecucionResponse
                {
                    Id = e.Id,
                    BusetaId = e.BusetaId,
                    PlacaBuseta = e.Buseta.Placa,
                    PlantillaId = e.PlantillaId,
                    NombrePlantilla = e.Plantilla.Nombre,
                    InspectorId = e.InspectorId,
                    NombreInspector = $"{e.Inspector.Nombre} {e.Inspector.Apellido}",
                    FechaInicio = e.FechaInicio,
                    FechaCompletado = e.FechaCompletado,
                    ObservacionesGenerales = e.ObservacionesGenerales,
                    Estado = e.Estado.ToString(),
                    Resultados = e.Resultados.Select(r => new ChecklistItemResultadoResponse
                    {
                        Id = r.Id,
                        ItemPlantillaId = r.ChecklistItemPlantillaId,
                        NombreItem = r.ItemPlantilla.Nombre,
                        DescripcionItem = r.ItemPlantilla.Descripcion,
                        Aprobado = r.Aprobado,
                        Observacion = r.Observacion,
                        FechaVerificacion = r.FechaVerificacion
                    }).ToList()
                })
                .ToListAsync();

            return Ok(ejecuciones);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al obtener ejecuciones");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpGet("ejecuciones/{id}")]
    public async Task<ActionResult<ChecklistEjecucionResponse>> GetEjecucion(int id)
    {
        try
        {
            var ejecucion = await _context.ChecklistEjecuciones
                .Include(e => e.Buseta)
                .Include(e => e.Plantilla)
                .Include(e => e.Inspector)
                .Include(e => e.Resultados)
                .ThenInclude(r => r.ItemPlantilla)
                .Where(e => e.Id == id)
                .Select(e => new ChecklistEjecucionResponse
                {
                    Id = e.Id,
                    BusetaId = e.BusetaId,
                    PlacaBuseta = e.Buseta.Placa,
                    PlantillaId = e.PlantillaId,
                    NombrePlantilla = e.Plantilla.Nombre,
                    InspectorId = e.InspectorId,
                    NombreInspector = $"{e.Inspector.Nombre} {e.Inspector.Apellido}",
                    FechaInicio = e.FechaInicio,
                    FechaCompletado = e.FechaCompletado,
                    ObservacionesGenerales = e.ObservacionesGenerales,
                    Estado = e.Estado.ToString(),
                    Resultados = e.Resultados.Select(r => new ChecklistItemResultadoResponse
                    {
                        Id = r.Id,
                        ItemPlantillaId = r.ChecklistItemPlantillaId,
                        NombreItem = r.ItemPlantilla.Nombre,
                        DescripcionItem = r.ItemPlantilla.Descripcion,
                        Aprobado = r.Aprobado,
                        Observacion = r.Observacion,
                        FechaVerificacion = r.FechaVerificacion
                    }).ToList()
                })
                .FirstOrDefaultAsync();

            if (ejecucion == null)
                return NotFound(new { message = "Ejecución no encontrada" });

            return Ok(ejecucion);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al obtener ejecución {Id}", id);
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    #endregion
}
