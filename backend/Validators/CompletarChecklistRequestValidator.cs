using FluentValidation;
using Sigav.Api.DTOs;

namespace Sigav.Api.Validators;

public class CompletarChecklistRequestValidator : AbstractValidator<CompletarChecklistRequest>
{
    public CompletarChecklistRequestValidator()
    {
        RuleFor(x => x.Resultados)
            .NotEmpty().WithMessage("Debe incluir al menos un resultado");

        RuleForEach(x => x.Resultados)
            .SetValidator(new ChecklistItemResultadoRequestValidator());

        RuleFor(x => x.ObservacionesGenerales)
            .MaximumLength(1000).WithMessage("Las observaciones generales no pueden exceder 1000 caracteres");
    }
}

public class ChecklistItemResultadoRequestValidator : AbstractValidator<ChecklistItemResultadoRequest>
{
    public ChecklistItemResultadoRequestValidator()
    {
        RuleFor(x => x.ItemPlantillaId)
            .GreaterThan(0).WithMessage("El ID del item es obligatorio");

        RuleFor(x => x.Observacion)
            .NotEmpty().When(x => !x.Aprobado)
            .WithMessage("La observación es obligatoria cuando el item no es aprobado")
            .MaximumLength(500).WithMessage("La observación no puede exceder 500 caracteres");
    }
}
