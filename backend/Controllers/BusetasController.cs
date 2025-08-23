using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Api.DTOs;
using Sigav.Api.Domain;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/v1/[controller]")]
[Tags("Busetas")]
[Authorize]
public class BusetasController : ControllerBase
{
    private readonly SigavDbContext _context;
    private readonly ILogger<BusetasController> _logger;

    public BusetasController(SigavDbContext context, ILogger<BusetasController> logger)
    {
        _context = context;
        _logger = logger;
    }

    [HttpGet]
    public async Task<ActionResult<BusetaListResponse>> GetBusetas(
        [FromQuery] string? estado,
        [FromQuery] string? q,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20)
    {
        try
        {
            var query = _context.Busetas.AsQueryable();

            if (!string.IsNullOrEmpty(estado) && Enum.TryParse<EstadoBuseta>(estado, true, out var estadoEnum))
            {
                query = query.Where(b => b.Estado == estadoEnum);
            }

            if (!string.IsNullOrEmpty(q))
            {
                query = query.Where(b => b.Placa.Contains(q) || b.Modelo.Contains(q) || b.Agencia.Contains(q));
            }

            var total = await query.CountAsync();
            var totalPages = (int)Math.Ceiling((double)total / pageSize);

            var busetas = await query
                .OrderBy(b => b.Placa)
                .Skip((page - 1) * pageSize)
                .Take(pageSize)
                .Select(b => new BusetaResponse
                {
                    Id = b.Id,
                    Placa = b.Placa,
                    Modelo = b.Modelo,
                    Capacidad = b.Capacidad,
                    Agencia = b.Agencia,
                    Estado = b.Estado.ToString(),
                    FechaCreacion = b.FechaCreacion,
                    FechaActualizacion = b.FechaActualizacion
                })
                .ToListAsync();

            return Ok(new BusetaListResponse
            {
                Items = busetas,
                Total = total,
                Page = page,
                PageSize = pageSize,
                TotalPages = totalPages
            });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al obtener busetas");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpGet("{id}")]
    public async Task<ActionResult<BusetaResponse>> GetBuseta(int id)
    {
        try
        {
            var buseta = await _context.Busetas
                .Where(b => b.Id == id)
                .Select(b => new BusetaResponse
                {
                    Id = b.Id,
                    Placa = b.Placa,
                    Modelo = b.Modelo,
                    Capacidad = b.Capacidad,
                    Agencia = b.Agencia,
                    Estado = b.Estado.ToString(),
                    FechaCreacion = b.FechaCreacion,
                    FechaActualizacion = b.FechaActualizacion
                })
                .FirstOrDefaultAsync();

            if (buseta == null)
                return NotFound(new { message = "Buseta no encontrada" });

            return Ok(buseta);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al obtener buseta {Id}", id);
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPost]
    [Authorize(Policy = "AdminOnly")]
    public async Task<ActionResult<BusetaResponse>> CreateBuseta([FromBody] CreateBusetaRequest request)
    {
        try
        {
            var buseta = new Buseta
            {
                Placa = request.Placa,
                Modelo = request.Modelo,
                Capacidad = request.Capacidad,
                Agencia = request.Agencia,
                Estado = EstadoBuseta.Disponible
            };

            _context.Busetas.Add(buseta);
            await _context.SaveChangesAsync();

            var response = new BusetaResponse
            {
                Id = buseta.Id,
                Placa = buseta.Placa,
                Modelo = buseta.Modelo,
                Capacidad = buseta.Capacidad,
                Agencia = buseta.Agencia,
                Estado = buseta.Estado.ToString(),
                FechaCreacion = buseta.FechaCreacion,
                FechaActualizacion = buseta.FechaActualizacion
            };

            return CreatedAtAction(nameof(GetBuseta), new { id = buseta.Id }, response);
        }
        catch (DbUpdateException ex) when (ex.InnerException?.Message.Contains("unique") == true)
        {
            return BadRequest(new { message = "Ya existe una buseta con esa placa" });
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al crear buseta");
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPut("{id}")]
    [Authorize(Policy = "AdminOnly")]
    public async Task<ActionResult<BusetaResponse>> UpdateBuseta(int id, [FromBody] UpdateBusetaRequest request)
    {
        try
        {
            var buseta = await _context.Busetas.FindAsync(id);
            if (buseta == null)
                return NotFound(new { message = "Buseta no encontrada" });

            buseta.Modelo = request.Modelo;
            buseta.Capacidad = request.Capacidad;
            buseta.Agencia = request.Agencia;
            buseta.FechaActualizacion = DateTime.UtcNow;

            await _context.SaveChangesAsync();

            var response = new BusetaResponse
            {
                Id = buseta.Id,
                Placa = buseta.Placa,
                Modelo = buseta.Modelo,
                Capacidad = buseta.Capacidad,
                Agencia = buseta.Agencia,
                Estado = buseta.Estado.ToString(),
                FechaCreacion = buseta.FechaCreacion,
                FechaActualizacion = buseta.FechaActualizacion
            };

            return Ok(response);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al actualizar buseta {Id}", id);
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }

    [HttpPatch("{id}/estado")]
    [Authorize(Policy = "AdminOnly")]
    public async Task<ActionResult<BusetaResponse>> UpdateEstado(int id, [FromBody] UpdateEstadoRequest request)
    {
        try
        {
            if (!Enum.TryParse<EstadoBuseta>(request.Estado, true, out var estado))
            {
                return BadRequest(new { message = "Estado inválido" });
            }

            var buseta = await _context.Busetas.FindAsync(id);
            if (buseta == null)
                return NotFound(new { message = "Buseta no encontrada" });

            buseta.Estado = estado;
            buseta.FechaActualizacion = DateTime.UtcNow;

            await _context.SaveChangesAsync();

            var response = new BusetaResponse
            {
                Id = buseta.Id,
                Placa = buseta.Placa,
                Modelo = buseta.Modelo,
                Capacidad = buseta.Capacidad,
                Agencia = buseta.Agencia,
                Estado = buseta.Estado.ToString(),
                FechaCreacion = buseta.FechaCreacion,
                FechaActualizacion = buseta.FechaActualizacion
            };

            return Ok(response);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error al actualizar estado de buseta {Id}", id);
            return StatusCode(500, new { message = "Error interno del servidor" });
        }
    }
}
