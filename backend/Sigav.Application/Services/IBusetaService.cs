using Sigav.Shared.DTOs;

namespace Sigav.Application.Services;

public interface IBusetaService
{
    Task<IEnumerable<BusetaDto>> GetAllAsync(int? empresaId = null);
    Task<BusetaDto?> GetByIdAsync(int id);
    Task<BusetaDto> CreateAsync(CreateBusetaDto createDto);
    Task<BusetaDto> UpdateAsync(int id, UpdateBusetaDto updateDto);
    Task DeleteAsync(int id);
    Task<IEnumerable<BusetaDto>> GetByEmpresaAsync(int empresaId);
    Task<IEnumerable<CustomFieldValueDto>> GetCustomFieldsAsync(int id);
}
