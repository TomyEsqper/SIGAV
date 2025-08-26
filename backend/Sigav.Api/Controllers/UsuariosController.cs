using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class UsuariosController : ControllerBase
{
    private readonly SigavDbContext _context;

    public UsuariosController(SigavDbContext context)
    {
        _context = context;
    }

    // GET: api/Usuarios
    [HttpGet]
    public async Task<ActionResult<IEnumerable<Usuario>>> GetUsuarios([FromQuery] int? empresaId)
    {
        var query = _context.Usuarios
            .Include(u => u.Empresa)
            .Include(u => u.CustomFieldValues)
                .ThenInclude(cfv => cfv.CustomField)
            .Where(u => u.Activo);

        if (empresaId.HasValue)
            query = query.Where(u => u.EmpresaId == empresaId.Value);

        return await query.ToListAsync();
    }

    // GET: api/Usuarios/5
    [HttpGet("{id}")]
    public async Task<ActionResult<Usuario>> GetUsuario(int id)
    {
        var usuario = await _context.Usuarios
            .Include(u => u.Empresa)
            .Include(u => u.CustomFieldValues)
                .ThenInclude(cfv => cfv.CustomField)
            .FirstOrDefaultAsync(u => u.Id == id && u.Activo);

        if (usuario == null)
        {
            return NotFound();
        }

        return usuario;
    }

    // POST: api/Usuarios
    [HttpPost]
    public async Task<ActionResult<Usuario>> CreateUsuario(Usuario usuario)
    {
        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        usuario.FechaCreacion = DateTime.UtcNow;
        usuario.Activo = true;

        _context.Usuarios.Add(usuario);
        await _context.SaveChangesAsync();

        return CreatedAtAction(nameof(GetUsuario), new { id = usuario.Id }, usuario);
    }

    // PUT: api/Usuarios/5
    [HttpPut("{id}")]
    public async Task<IActionResult> UpdateUsuario(int id, Usuario usuario)
    {
        if (id != usuario.Id)
        {
            return BadRequest();
        }

        if (!ModelState.IsValid)
        {
            return BadRequest(ModelState);
        }

        usuario.FechaActualizacion = DateTime.UtcNow;

        _context.Entry(usuario).State = EntityState.Modified;

        try
        {
            await _context.SaveChangesAsync();
        }
        catch (DbUpdateConcurrencyException)
        {
            if (!UsuarioExists(id))
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

    // DELETE: api/Usuarios/5
    [HttpDelete("{id}")]
    public async Task<IActionResult> DeleteUsuario(int id)
    {
        var usuario = await _context.Usuarios.FindAsync(id);
        if (usuario == null)
        {
            return NotFound();
        }

        // Soft delete
        usuario.Activo = false;
        usuario.FechaActualizacion = DateTime.UtcNow;
        
        await _context.SaveChangesAsync();

        return NoContent();
    }

    // GET: api/Usuarios/empresa/{empresaId}
    [HttpGet("empresa/{empresaId}")]
    public async Task<ActionResult<IEnumerable<Usuario>>> GetUsuariosByEmpresa(int empresaId)
    {
        var usuarios = await _context.Usuarios
            .Include(u => u.Empresa)
            .Include(u => u.CustomFieldValues)
                .ThenInclude(cfv => cfv.CustomField)
            .Where(u => u.EmpresaId == empresaId && u.Activo)
            .ToListAsync();

        return usuarios;
    }

    // GET: api/Usuarios/5/custom-fields
    [HttpGet("{id}/custom-fields")]
    public async Task<ActionResult<IEnumerable<CustomFieldValue>>> GetUsuarioCustomFields(int id)
    {
        var usuario = await _context.Usuarios
            .Include(u => u.CustomFieldValues)
                .ThenInclude(cfv => cfv.CustomField)
            .FirstOrDefaultAsync(u => u.Id == id && u.Activo);

        if (usuario == null)
        {
            return NotFound();
        }

        return usuario.CustomFieldValues.ToList();
    }

    private bool UsuarioExists(int id)
    {
        return _context.Usuarios.Any(e => e.Id == id && e.Activo);
    }
}
