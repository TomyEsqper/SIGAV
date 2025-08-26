using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class CustomFieldValuesController : ControllerBase
{
    private readonly SigavDbContext _context;

    public CustomFieldValuesController(SigavDbContext context)
    {
        _context = context;
    }

    // GET: api/CustomFieldValues
    [HttpGet]
    public async Task<ActionResult<IEnumerable<CustomFieldValue>>> GetCustomFieldValues(
        [FromQuery] int? customFieldId, 
        [FromQuery] string? entidad, 
        [FromQuery] int? entidadId)
    {
        var query = _context.CustomFieldValues
            .Include(cfv => cfv.CustomField)
            .AsQueryable();

        if (customFieldId.HasValue)
            query = query.Where(cfv => cfv.CustomFieldId == customFieldId.Value);

        if (!string.IsNullOrEmpty(entidad))
            query = query.Where(cfv => cfv.Entidad == entidad);

        if (entidadId.HasValue)
            query = query.Where(cfv => cfv.EntidadId == entidadId.Value);

        return await query.ToListAsync();
    }

    // GET: api/CustomFieldValues/5
    [HttpGet("{id}")]
    public async Task<ActionResult<CustomFieldValue>> GetCustomFieldValue(int id)
    {
        var customFieldValue = await _context.CustomFieldValues
            .Include(cfv => cfv.CustomField)
            .FirstOrDefaultAsync(cfv => cfv.Id == id);

        if (customFieldValue == null)
        {
            return NotFound();
        }

        return customFieldValue;
    }

    // POST: api/CustomFieldValues
    [HttpPost]
    public async Task<ActionResult<CustomFieldValue>> CreateCustomFieldValue(CustomFieldValue customFieldValue)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        customFieldValue.FechaCreacion = DateTime.UtcNow;

        _context.CustomFieldValues.Add(customFieldValue);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetCustomFieldValue), new { id = customFieldValue.Id }, customFieldValue);
    }

    // PUT: api/CustomFieldValues/5
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateCustomFieldValue(int id, CustomFieldValue customFieldValue)
    {
        if (id != customFieldValue.Id)
        {
            return BadRequest();
        }

        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        customFieldValue.FechaActualizacion = DateTime.UtcNow;

        _context.Entry(customFieldValue).State = EntityState.Modified;

        try
        {
            await _context.SaveChangesAsync();
        }
        catch (DbUpdateConcurrencyException)
        {
            if (!CustomFieldValueExists(id))
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

    // DELETE: api/CustomFieldValues/5
    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteCustomFieldValue(int id)
    {
        var customFieldValue = await _context.CustomFieldValues.FindAsync(id);
        if (customFieldValue == null)
        {
            return NotFound();
        }

        _context.CustomFieldValues.Remove(customFieldValue);
        await _context.SaveChangesAsync();

        return NoContent();
    }

    // POST: api/CustomFieldValues/batch
    [HttpPost("batch")]
    public async Task<IActionResult> CreateCustomFieldValuesBatch(List<CustomFieldValue> customFieldValues)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        foreach (var cfv in customFieldValues)
        {
            cfv.FechaCreacion = DateTime.UtcNow;
        }

        _context.CustomFieldValues.AddRange(customFieldValues);
        await _context.SaveChangesAsync();

        return Ok(new { message = $"{customFieldValues.Count} campos personalizados creados exitosamente" });
    }

    // PUT: api/CustomFieldValues/batch
    [HttpPut("batch")]
    public async Task<IActionResult> UpdateCustomFieldValuesBatch(List<CustomFieldValue> customFieldValues)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        foreach (var cfv in customFieldValues)
        {
            cfv.FechaActualizacion = DateTime.UtcNow;
            _context.Entry(cfv).State = EntityState.Modified;
        }

        await _context.SaveChangesAsync();

        return Ok(new { message = $"{customFieldValues.Count} campos personalizados actualizados exitosamente" });
    }

    private bool CustomFieldValueExists(int id)
    {
        return _context.CustomFieldValues.Any(e => e.Id == id);
    }
}
