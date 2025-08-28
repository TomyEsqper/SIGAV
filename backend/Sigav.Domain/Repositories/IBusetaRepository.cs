using Sigav.Domain;

namespace Sigav.Domain.Repositories;

public interface IBusetaRepository : IRepository<Buseta>
{
    Task<IEnumerable<Buseta>> GetByEmpresaAsync(int empresaId);
    Task<IEnumerable<Buseta>> GetByEmpresaWithCustomFieldsAsync(int empresaId);
}
