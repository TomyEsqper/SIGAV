using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;

namespace Sigav.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class HealthController : ControllerBase
{
    private readonly SigavDbContext _context;

    public HealthController(SigavDbContext context)
    {
        _context = context;
    }

    [HttpGet]
    public IActionResult Get()
    {
        return Ok(new { status = "healthy", timestamp = DateTime.UtcNow });
    }

    [HttpGet("data")]
    public async Task<IActionResult> GetData()
    {
        try
        {
            var empresas = await _context.Empresas.ToListAsync();
            var usuarios = await _context.Usuarios.ToListAsync();
            
            return Ok(new 
            { 
                empresas = empresas.Select(e => new { e.Id, e.Nombre, e.Activo }),
                usuarios = usuarios.Select(u => new { u.Id, u.Nombre, u.Email, u.EmpresaId, u.Activo, u.PasswordHash })
            });
        }
        catch (Exception ex)
        {
            return Ok(new { error = ex.Message });
        }
    }
}
