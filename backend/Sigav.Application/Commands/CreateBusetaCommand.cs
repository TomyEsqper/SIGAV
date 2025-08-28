using MediatR;
using Sigav.Shared.DTOs;

namespace Sigav.Application.Commands;

/// <summary>
/// Command para crear una nueva buseta
/// </summary>
public class CreateBusetaCommand : IRequest<BusetaDto>
{
    public CreateBusetaDto CreateDto { get; set; }

    public CreateBusetaCommand(CreateBusetaDto createDto)
    {
        CreateDto = createDto;
    }
}

/// <summary>
/// Command para actualizar una buseta existente
/// </summary>
public class UpdateBusetaCommand : IRequest<BusetaDto>
{
    public int Id { get; set; }
    public UpdateBusetaDto UpdateDto { get; set; }

    public UpdateBusetaCommand(int id, UpdateBusetaDto updateDto)
    {
        Id = id;
        UpdateDto = updateDto;
    }
}

/// <summary>
/// Command para eliminar una buseta
/// </summary>
public class DeleteBusetaCommand : IRequest<bool>
{
    public int Id { get; set; }

    public DeleteBusetaCommand(int id)
    {
        Id = id;
    }
}
