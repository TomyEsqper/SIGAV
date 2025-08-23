using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Api.DTOs;
using Sigav.Api.Services;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/v1/[controller]")]
[Tags("Historial")]
[Authorize]
public class HistorialController : ControllerBase
{
    private readonly SigavDbContext _context;
    private readonly IExportService _exportService;
    private readonly ILogger<HistorialController> _logger;

    public HistorialController(SigavDbContext context, IExportService exportService, ILogger<HistorialController> logger)
    {
        _context = context;
        _exportService = exportService;
        _logger = logger;
    }

    [HttpGet]
    public async Task<ActionResult<HistorialResponse>> GetHistorial(
        [FromQuery] int? busetaId,
        [FromQuery] DateTime? from,
        [FromQuery] DateTime? to,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20)
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

            var total = await query.CountAsync();
            var totalPages = (int)Math.Ceiling((double)total / pageSize);

            var ejecuciones = await query
                .OrderByDescending(e => e.FechaInicio)
                .Skip((page - 1) * pageSize)
                .Take(pageSize)
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

            return Ok(new HistorialResponse
            {
                Items = ejecuciones,
                Total = total,
                Page = page,
                PageSize = pageSize,
                TotalPages = totalPages
            });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al obtener historial");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpGet("export/csv")]
    public async Task<IActionResult> ExportCsv(
        [FromQuery] int? busetaId,
        [FromQuery] DateTime? from,
        [FromQuery] DateTime? to)
    {
        try
        {
            var request = new ExportRequest
            {
                BusetaId = busetaId,
                From = from,
                To = to
            };

            var csvData = await _exportService.ExportToCsvAsync(request);
            var fileName = $"historial_checklists_{DateTime.Now:yyyyMMdd_HHmmss}.csv";

            return File(csvData, "text/csv", fileName);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al exportar CSV");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpGet("export/pdf")]
    public async Task<IActionResult> ExportPdf(
        [FromQuery] int? busetaId,
        [FromQuery] DateTime? from,
        [FromQuery] DateTime? to)
    {
        try
        {
            var request = new ExportRequest
            {
                BusetaId = busetaId,
                From = from,
                To = to
            };

            var pdfData = await _exportService.ExportToPdfAsync(request);
            var fileName = $"historial_checklists_{DateTime.Now:yyyyMMdd_HHmmss}.pdf";

            return File(pdfData, "application/pdf", fileName);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al exportar PDF");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }
}
