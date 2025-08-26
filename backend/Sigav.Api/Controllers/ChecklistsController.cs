using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class ChecklistsController : ControllerBase
{
    private readonly SigavDbContext _context;

    public ChecklistsController(SigavDbContext context)
    {
        _context = context;
    }

    // GET: api/Checklists/plantillas
    [HttpGet("plantillas")]
    public async Task<ActionResult<IEnumerable<ChecklistPlantilla>>> GetChecklistPlantillas([FromQuery] int? empresaId)
    {
        var query = _context.ChecklistPlantillas
            .Include(cp => cp.Empresa)
            .Include(cp => cp.Items.OrderBy(i => i.Orden))
            .Where(cp => cp.Activa);

        if (empresaId.HasValue)
            query = query.Where(cp => cp.EmpresaId == empresaId.Value);

        return await query.ToListAsync();
    }

    // GET: api/Checklists/plantillas/5
    [HttpGet("plantillas/{id}")]
    public async Task<ActionResult<ChecklistPlantilla>> GetChecklistPlantilla(int id)
    {
        var plantilla = await _context.ChecklistPlantillas
            .Include(cp => cp.Empresa)
            .Include(cp => cp.Items.OrderBy(i => i.Orden))
            .FirstOrDefaultAsync(cp => cp.Id == id && cp.Activa);

        if (plantilla == null)
        {
            return NotFound();
        }

        return plantilla;
    }

    // POST: api/Checklists/plantillas
    [HttpPost("plantillas")]
    public async Task<ActionResult<ChecklistPlantilla>> CreateChecklistPlantilla(ChecklistPlantilla plantilla)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        plantilla.FechaCreacion = DateTime.UtcNow;
        plantilla.Activa = true;

        _context.ChecklistPlantillas.Add(plantilla);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetChecklistPlantilla), new { id = plantilla.Id }, plantilla);
    }

    // PUT: api/Checklists/plantillas/5
    [HttpPut("plantillas/{id}")]
    public async Task<IActionResult> UpdateChecklistPlantilla(int id, ChecklistPlantilla plantilla)
    {
        if (id != plantilla.Id)
        {
            return BadRequest();
        }

        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        plantilla.FechaActualizacion = DateTime.UtcNow;

        _context.Entry(plantilla).State = EntityState.Modified;

        try
        {
            await _context.SaveChangesAsync();
        }
        catch (DbUpdateConcurrencyException)
        {
            if (!ChecklistPlantillaExists(id))
            {
                return NotFound();
            }
            else
            {
                throw;
            }
        }

        return NoContent();
    }

    // DELETE: api/Checklists/plantillas/5
    [HttpDelete("plantillas/{id}")]
    public async Task<IActionResult> DeleteChecklistPlantilla(int id)
    {
        var plantilla = await _context.ChecklistPlantillas.FindAsync(id);
        if (plantilla == null)
        {
            return NotFound();
        }

        // Soft delete
        plantilla.Activa = false;
        plantilla.FechaActualizacion = DateTime.UtcNow;
        
        await _context.SaveChangesAsync();

        return NoContent();
    }

    // GET: api/Checklists/ejecuciones
    [HttpGet("ejecuciones")]
    public async Task<ActionResult<IEnumerable<ChecklistEjecucion>>> GetChecklistEjecuciones([FromQuery] int? empresaId, [FromQuery] int? busetaId)
    {
        var query = _context.ChecklistEjecuciones
            .Include(ce => ce.Buseta)
            .Include(ce => ce.ChecklistPlantilla)
            .Include(ce => ce.Inspector)
            .Include(ce => ce.ItemsResultado)
                .ThenInclude(ir => ir.ChecklistItemPlantilla)
            .Where(ce => ce.Activo);

        if (empresaId.HasValue)
            query = query.Where(ce => ce.Buseta.EmpresaId == empresaId.Value);

        if (busetaId.HasValue)
            query = query.Where(ce => ce.BusetaId == busetaId.Value);

        return await query.OrderByDescending(ce => ce.FechaInicio).ToListAsync();
    }

    // GET: api/Checklists/ejecuciones/5
    [HttpGet("ejecuciones/{id}")]
    public async Task<ActionResult<ChecklistEjecucion>> GetChecklistEjecucion(int id)
    {
        var ejecucion = await _context.ChecklistEjecuciones
            .Include(ce => ce.Buseta)
            .Include(ce => ce.ChecklistPlantilla)
            .Include(ce => ce.Inspector)
            .Include(ce => ce.ItemsResultado)
                .ThenInclude(ir => ir.ChecklistItemPlantilla)
            .FirstOrDefaultAsync(ce => ce.Id == id && ce.Activo);

        if (ejecucion == null)
        {
            return NotFound();
        }

        return ejecucion;
    }

    // POST: api/Checklists/ejecuciones
    [HttpPost("ejecuciones")]
    public async Task<ActionResult<ChecklistEjecucion>> CreateChecklistEjecucion(ChecklistEjecucion ejecucion)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        ejecucion.FechaCreacion = DateTime.UtcNow;
        ejecucion.Activo = true;
        ejecucion.Estado = "En Progreso";

        _context.ChecklistEjecuciones.Add(ejecucion);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetChecklistEjecucion), new { id = ejecucion.Id }, ejecucion);
    }

    // PUT: api/Checklists/ejecuciones/5
    [HttpPut("ejecuciones/{id}")]
    public async Task<IActionResult> UpdateChecklistEjecucion(int id, ChecklistEjecucion ejecucion)
    {
        if (id != ejecucion.Id)
        {
            return BadRequest();
        }

        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        ejecucion.FechaActualizacion = DateTime.UtcNow;

        _context.Entry(ejecucion).State = EntityState.Modified;

        try
        {
            await _context.SaveChangesAsync();
        }
        catch (DbUpdateConcurrencyException)
        {
            if (!ChecklistEjecucionExists(id))
            {
                return NotFound();
            }
            else
            {
                throw;
            }
        }

        return NoContent();
    }

    // POST: api/Checklists/ejecuciones/5/completar
    [HttpPost("ejecuciones/{id}/completar")]
    public async Task<IActionResult> CompletarChecklist(int id)
    {
        var ejecucion = await _context.ChecklistEjecuciones.FindAsync(id);
        if (ejecucion == null)
        {
            return NotFound();
        }

        ejecucion.Estado = "Completado";
        ejecucion.Completado = true;
        ejecucion.FechaFin = DateTime.UtcNow;
        ejecucion.FechaActualizacion = DateTime.UtcNow;

        // Calcular tiempo total
        var tiempoTotal = DateTime.UtcNow - ejecucion.FechaInicio;
        ejecucion.TiempoTotal = (int)tiempoTotal.TotalMinutes;

        await _context.SaveChangesAsync();

        return NoContent();
    }

    private bool ChecklistPlantillaExists(int id)
    {
        return _context.ChecklistPlantillas.Any(e => e.Id == id && e.Activa);
    }

    private bool ChecklistEjecucionExists(int id)
    {
        return _context.ChecklistEjecuciones.Any(e => e.Id == id && e.Activo);
    }
}
