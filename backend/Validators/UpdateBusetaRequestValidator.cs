using FluentValidation;
using Sigav.Api.DTOs;

namespace Sigav.Api.Validators;

public class UpdateBusetaRequestValidator : AbstractValidator<UpdateBusetaRequest>
{
    public UpdateBusetaRequestValidator()
    {
        RuleFor(x => x.Modelo)
            .NotEmpty().WithMessage("El modelo es obligatorio")
            .MaximumLength(100).WithMessage("El modelo no puede exceder 100 caracteres");

        RuleFor(x => x.Capacidad)
            .GreaterThan(0).WithMessage("La capacidad debe ser mayor a 0")
            .LessThanOrEqualTo(100).WithMessage("La capacidad no puede exceder 100");

        RuleFor(x => x.Agencia)
            .NotEmpty().WithMessage("La agencia es obligatoria")
            .MaximumLength(100).WithMessage("La agencia no puede exceder 100 caracteres");
    }
}
