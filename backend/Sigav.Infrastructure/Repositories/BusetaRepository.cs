using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;
using Sigav.Domain.Repositories;

namespace Sigav.Infrastructure.Repositories;

public class BusetaRepository : EfRepository<Buseta>, IBusetaRepository
{
    public BusetaRepository(SigavDbContext context) : base(context)
    {
    }

    public async Task<IEnumerable<Buseta>> GetByEmpresaAsync(int empresaId)
    {
        return await _dbSet
            .Where(b => b.EmpresaId == empresaId && b.Activo)
            .ToListAsync();
    }

    public async Task<IEnumerable<Buseta>> GetByEmpresaWithCustomFieldsAsync(int empresaId)
    {
        return await _dbSet
            .Where(b => b.EmpresaId == empresaId && b.Activo)
            .Include(b => b.CustomFieldValues)
            .ThenInclude(cfv => cfv.CustomField)
            .ToListAsync();
    }
}
