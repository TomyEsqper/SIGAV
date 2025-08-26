using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class EmpresasController : ControllerBase
{
    private readonly SigavDbContext _context;

    public EmpresasController(SigavDbContext context)
    {
        _context = context;
    }

    // GET: api/Empresas
    [HttpGet]
    public async Task<ActionResult<IEnumerable<Empresa>>> GetEmpresas()
    {
        return await _context.Empresas
            .Include(e => e.CustomFields)
            .Where(e => e.Activo)
            .ToListAsync();
    }

    // GET: api/Empresas/5
    [HttpGet("{id}")]
    public async Task<ActionResult<Empresa>> GetEmpresa(int id)
    {
        var empresa = await _context.Empresas
            .Include(e => e.CustomFields)
            .FirstOrDefaultAsync(e => e.Id == id && e.Activo);

        if (empresa == null)
        {
            return NotFound();
        }

        return empresa;
    }

    // POST: api/Empresas
    [HttpPost]
    public async Task<ActionResult<Empresa>> CreateEmpresa(Empresa empresa)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        empresa.FechaCreacion = DateTime.UtcNow;
        empresa.Activo = true;

        _context.Empresas.Add(empresa);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetEmpresa), new { id = empresa.Id }, empresa);
    }

    // PUT: api/Empresas/5
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateEmpresa(int id, Empresa empresa)
    {
        if (id != empresa.Id)
        {
            return BadRequest();
        }

        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        empresa.FechaActualizacion = DateTime.UtcNow;

        _context.Entry(empresa).State = EntityState.Modified;

        try
        {
            await _context.SaveChangesAsync();
        }
        catch (DbUpdateConcurrencyException)
        {
            if (!EmpresaExists(id))
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

    // DELETE: api/Empresas/5
    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteEmpresa(int id)
    {
        var empresa = await _context.Empresas.FindAsync(id);
        if (empresa == null)
        {
            return NotFound();
        }

        // Soft delete
        empresa.Activo = false;
        empresa.FechaActualizacion = DateTime.UtcNow;
        
        await _context.SaveChangesAsync();

        return NoContent();
    }

    // GET: api/Empresas/5/custom-fields
    [HttpGet("{id}/custom-fields")]
    public async Task<ActionResult<IEnumerable<CustomField>>> GetEmpresaCustomFields(int id)
    {
        var empresa = await _context.Empresas
            .Include(e => e.CustomFields)
            .FirstOrDefaultAsync(e => e.Id == id && e.Activo);

        if (empresa == null)
        {
            return NotFound();
        }

        return empresa.CustomFields.OrderBy(cf => cf.Orden).ToList();
    }

    private bool EmpresaExists(int id)
    {
        return _context.Empresas.Any(e => e.Id == id && e.Activo);
    }
}
