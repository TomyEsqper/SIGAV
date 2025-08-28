using AutoMapper;
using Sigav.Shared.DTOs;
using Sigav.Domain;
using Sigav.Domain.Repositories;
using FluentValidation;

namespace Sigav.Application.Services;

/// <summary>
/// Servicio para gestión de busetas
/// </summary>
public class BusetaService : BaseService<Buseta, BusetaDto, CreateBusetaDto, UpdateBusetaDto>, IBusetaService
{
    private readonly IBusetaRepository _busetaRepository;
    private readonly IRepository<CustomFieldValue> _customFieldValueRepository;
    private readonly IValidator<CreateBusetaDto> _createValidator;
    private readonly IValidator<UpdateBusetaDto> _updateValidator;

    public BusetaService(
        IBusetaRepository busetaRepository,
        IRepository<CustomFieldValue> customFieldValueRepository,
        IMapper mapper,
        ILogger<BusetaService> logger,
        IValidator<CreateBusetaDto> createValidator,
        IValidator<UpdateBusetaDto> updateValidator) 
        : base(busetaRepository, mapper, logger)
    {
        _busetaRepository = busetaRepository;
        _customFieldValueRepository = customFieldValueRepository;
        _createValidator = createValidator;
        _updateValidator = updateValidator;
    }

    public async Task<IEnumerable<CustomFieldValueDto>> GetCustomFieldsAsync(int id)
    {
        try
        {
            var customFieldValues = await _customFieldValueRepository.FindAsync(
                cfv => cfv.EntidadId == id);
            
            return _mapper.Map<IEnumerable<CustomFieldValueDto>>(customFieldValues);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting custom fields for buseta {Id}", id);
            throw;
        }
    }

    protected override int GetEmpresaId(Buseta entity)
    {
        return entity.EmpresaId;
    }

    protected override async Task ValidateCreateAsync(CreateBusetaDto createDto)
    {
        var validationResult = await _createValidator.ValidateAsync(createDto);
        if (!validationResult.IsValid)
        {
            var errors = string.Join(", ", validationResult.Errors.Select(e => e.ErrorMessage));
            throw new ValidationException($"Datos de buseta inválidos: {errors}");
        }

        // Validación adicional de negocio
        var existingBuseta = await _busetaRepository.FindAsync(b => b.Placa == createDto.Placa);
        if (existingBuseta.Any())
        {
            throw new ValidationException($"Ya existe una buseta con la placa {createDto.Placa}");
        }
    }

    protected override async Task ValidateUpdateAsync(int id, UpdateBusetaDto updateDto)
    {
        var validationResult = await _updateValidator.ValidateAsync(updateDto);
        if (!validationResult.IsValid)
        {
            var errors = string.Join(", ", validationResult.Errors.Select(e => e.ErrorMessage));
            throw new ValidationException($"Datos de buseta inválidos: {errors}");
        }

        // Validación adicional de negocio
        var existingBuseta = await _busetaRepository.FindAsync(b => b.Placa == updateDto.Placa && b.Id != id);
        if (existingBuseta.Any())
        {
            throw new ValidationException($"Ya existe otra buseta con la placa {updateDto.Placa}");
        }
    }
}
