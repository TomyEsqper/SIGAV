using MediatR;
using Sigav.Shared.DTOs;

namespace Sigav.Application.Queries;

/// <summary>
/// Query para obtener todas las busetas
/// </summary>
public class GetBusetasQuery : IRequest<IEnumerable<BusetaDto>>
{
    public int? EmpresaId { get; set; }

    public GetBusetasQuery(int? empresaId = null)
    {
        EmpresaId = empresaId;
    }
}

/// <summary>
/// Query para obtener una buseta por ID
/// </summary>
public class GetBusetaByIdQuery : IRequest<BusetaDto?>
{
    public int Id { get; set; }

    public GetBusetaByIdQuery(int id)
    {
        Id = id;
    }
}

/// <summary>
/// Query para obtener busetas por empresa
/// </summary>
public class GetBusetasByEmpresaQuery : IRequest<IEnumerable<BusetaDto>>
{
    public int EmpresaId { get; set; }

    public GetBusetasByEmpresaQuery(int empresaId)
    {
        EmpresaId = empresaId;
    }
}

/// <summary>
/// Query para obtener campos personalizados de una buseta
/// </summary>
public class GetBusetaCustomFieldsQuery : IRequest<IEnumerable<CustomFieldValueDto>>
{
    public int BusetaId { get; set; }

    public GetBusetaCustomFieldsQuery(int busetaId)
    {
        BusetaId = busetaId;
    }
}
