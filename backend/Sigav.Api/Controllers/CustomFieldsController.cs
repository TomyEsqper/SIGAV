using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class CustomFieldsController : ControllerBase
{
    private readonly SigavDbContext _context;

    public CustomFieldsController(SigavDbContext context)
    {
        _context = context;
    }

    // GET: api/CustomFields
    [HttpGet]
    public async Task<ActionResult<IEnumerable<CustomField>>> GetCustomFields([FromQuery] int? empresaId, [FromQuery] string? entidad)
    {
        var query = _context.CustomFields.AsQueryable();
        
        if (empresaId.HasValue)
            query = query.Where(cf => cf.EmpresaId == empresaId.Value);
            
        if (!string.IsNullOrEmpty(entidad))
            query = query.Where(cf => cf.Entidad == entidad);
            
        return await query.OrderBy(cf => cf.Orden).ToListAsync();
    }

    // GET: api/CustomFields/5
    [HttpGet("{id}")]
    public async Task<ActionResult<CustomField>> GetCustomField(int id)
    {
        var customField = await _context.CustomFields.FindAsync(id);

        if (customField == null)
        {
            return NotFound();
        }

        return customField;
    }

    // POST: api/CustomFields
    [HttpPost]
    public async Task<ActionResult<CustomField>> CreateCustomField(CustomField customField)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        _context.CustomFields.Add(customField);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetCustomField), new { id = customField.Id }, customField);
    }

    // PUT: api/CustomFields/5
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateCustomField(int id, CustomField customField)
    {
        if (id != customField.Id)
        {
            return BadRequest();
        }

        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        _context.Entry(customField).State = EntityState.Modified;

        try
        {
            await _context.SaveChangesAsync();
        }
        catch (DbUpdateConcurrencyException)
        {
            if (!CustomFieldExists(id))
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

    // DELETE: api/CustomFields/5
    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteCustomField(int id)
    {
        var customField = await _context.CustomFields.FindAsync(id);
        if (customField == null)
        {
            return NotFound();
        }

        _context.CustomFields.Remove(customField);
        await _context.SaveChangesAsync();

        return NoContent();
    }

    // GET: api/CustomFields/empresa/{empresaId}/entidad/{entidad}
    [HttpGet("empresa/{empresaId}/entidad/{entidad}")]
    public async Task<ActionResult<IEnumerable<CustomField>>> GetCustomFieldsByEmpresaAndEntidad(int empresaId, string entidad)
    {
        var customFields = await _context.CustomFields
            .Where(cf => cf.EmpresaId == empresaId && cf.Entidad == entidad)
            .OrderBy(cf => cf.Orden)
            .ToListAsync();

        return customFields;
    }

    private bool CustomFieldExists(int id)
    {
        return _context.CustomFields.Any(e => e.Id == id);
    }
}
