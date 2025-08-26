using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class BusetasController : ControllerBase
{
    private readonly SigavDbContext _context;

    public BusetasController(SigavDbContext context)
    {
        _context = context;
    }

    // GET: api/Busetas
    [HttpGet]
    public async Task<ActionResult<IEnumerable<Buseta>>> GetBusetas([FromQuery] int? empresaId)
    {
        var query = _context.Busetas
            .Include(b => b.Empresa)
            .Include(b => b.CustomFieldValues)
                .ThenInclude(cfv => cfv.CustomField)
            .Where(b => b.Activo);

        if (empresaId.HasValue)
            query = query.Where(b => b.EmpresaId == empresaId.Value);

        return await query.ToListAsync();
    }

    // GET: api/Busetas/5
    [HttpGet("{id}")]
    public async Task<ActionResult<Buseta>> GetBuseta(int id)
    {
        var buseta = await _context.Busetas
            .Include(b => b.Empresa)
            .Include(b => b.CustomFieldValues)
                .ThenInclude(cfv => cfv.CustomField)
            .FirstOrDefaultAsync(b => b.Id == id && b.Activo);

        if (buseta == null)
        {
            return NotFound();
        }

        return buseta;
    }

    // POST: api/Busetas
    [HttpPost]
    public async Task<ActionResult<Buseta>> CreateBuseta(Buseta buseta)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        buseta.FechaCreacion = DateTime.UtcNow;
        buseta.Activo = true;

        _context.Busetas.Add(buseta);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetBuseta), new { id = buseta.Id }, buseta);
    }

    // PUT: api/Busetas/5
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateBuseta(int id, Buseta buseta)
    {
        if (id != buseta.Id)
        {
            return BadRequest();
        }

        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        buseta.FechaActualizacion = DateTime.UtcNow;

        _context.Entry(buseta).State = EntityState.Modified;

        try
        {
            await _context.SaveChangesAsync();
        }
        catch (DbUpdateConcurrencyException)
        {
            if (!BusetaExists(id))
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

    // DELETE: api/Busetas/5
    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteBuseta(int id)
    {
        var buseta = await _context.Busetas.FindAsync(id);
        if (buseta == null)
        {
            return NotFound();
        }

        // Soft delete
        buseta.Activo = false;
        buseta.FechaActualizacion = DateTime.UtcNow;
        
        await _context.SaveChangesAsync();

        return NoContent();
    }

    // GET: api/Busetas/empresa/{empresaId}
    [HttpGet("empresa/{empresaId}")]
    public async Task<ActionResult<IEnumerable<Buseta>>> GetBusetasByEmpresa(int empresaId)
    {
        var busetas = await _context.Busetas
            .Include(b => b.Empresa)
            .Include(b => b.CustomFieldValues)
                .ThenInclude(cfv => cfv.CustomField)
            .Where(b => b.EmpresaId == empresaId && b.Activo)
            .ToListAsync();

        return busetas;
    }

    // GET: api/Busetas/5/custom-fields
    [HttpGet("{id}/custom-fields")]
    public async Task<ActionResult<IEnumerable<CustomFieldValue>>> GetBusetaCustomFields(int id)
    {
        var buseta = await _context.Busetas
            .Include(b => b.CustomFieldValues)
                .ThenInclude(cfv => cfv.CustomField)
            .FirstOrDefaultAsync(b => b.Id == id && b.Activo);

        if (buseta == null)
        {
            return NotFound();
        }

        return buseta.CustomFieldValues.ToList();
    }

    private bool BusetaExists(int id)
    {
        return _context.Busetas.Any(e => e.Id == id && e.Activo);
    }
}
