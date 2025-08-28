using AutoMapper;
using Sigav.Shared.DTOs;
using Sigav.Domain;

namespace Sigav.Application.Mapping;

public class MappingProfile : Profile
{
    public MappingProfile()
    {
        // Buseta mappings
        CreateMap<Buseta, BusetaDto>()
            .ForMember(dest => dest.EmpresaNombre, opt => opt.MapFrom(src => src.Empresa != null ? src.Empresa.Nombre : null))
            .ForMember(dest => dest.CustomFieldValues, opt => opt.MapFrom(src => src.CustomFieldValues));

        CreateMap<CreateBusetaDto, Buseta>();
        CreateMap<UpdateBusetaDto, Buseta>();

        // CustomFieldValue mappings
        CreateMap<CustomFieldValue, CustomFieldValueDto>()
            .ForMember(dest => dest.CustomFieldNombre, opt => opt.MapFrom(src => src.CustomField != null ? src.CustomField.Nombre : null))
            .ForMember(dest => dest.CustomFieldTipo, opt => opt.MapFrom(src => src.CustomField != null ? src.CustomField.Tipo : null));

        CreateMap<CreateCustomFieldValueDto, CustomFieldValue>();
        CreateMap<UpdateCustomFieldValueDto, CustomFieldValue>();
    }
}
