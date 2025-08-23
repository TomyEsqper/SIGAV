using FluentValidation;
using Sigav.Api.DTOs;

namespace Sigav.Api.Validators;

public class CreateBusetaRequestValidator : AbstractValidator<CreateBusetaRequest>
{
    public CreateBusetaRequestValidator()
    {
        RuleFor(x => x.Placa)
            .NotEmpty().WithMessage("La placa es obligatoria")
            .Matches(@"^[A-Z]{3}\d{3}$").WithMessage("La placa debe tener el formato AAA123");

        RuleFor(x => x.Modelo)
            .NotEmpty().WithMessage("El modelo es obligatorio")
            .MaximumLength(100).WithMessage("El modelo no puede exceder 100 caracteres");

        RuleFor(x => x.Capacidad)
            .GreaterThan(0).WithMessage("La capacidad debe ser mayor a 0")
            .LessThanOrEqualTo(100).WithMessage("La capacidad no puede exceder 100 pasajeros");

        RuleFor(x => x.Agencia)
            .NotEmpty().WithMessage("La agencia es obligatoria")
            .MaximumLength(100).WithMessage("La agencia no puede exceder 100 caracteres");
    }
}

public class UpdateBusetaRequestValidator : AbstractValidator<UpdateBusetaRequest>
{
    public UpdateBusetaRequestValidator()
    {
        RuleFor(x => x.Modelo)
            .NotEmpty().WithMessage("El modelo es obligatorio")
            .MaximumLength(100).WithMessage("El modelo no puede exceder 100 caracteres");

        RuleFor(x => x.Capacidad)
            .GreaterThan(0).WithMessage("La capacidad debe ser mayor a 0")
            .LessThanOrEqualTo(100).WithMessage("La capacidad no puede exceder 100 pasajeros");

        RuleFor(x => x.Agencia)
            .NotEmpty().WithMessage("La agencia es obligatoria")
            .MaximumLength(100).WithMessage("La agencia no puede exceder 100 caracteres");
    }
}

public class CreateChecklistPlantillaRequestValidator : AbstractValidator<CreateChecklistPlantillaRequest>
{
    public CreateChecklistPlantillaRequestValidator()
    {
        RuleFor(x => x.Nombre)
            .NotEmpty().WithMessage("El nombre es obligatorio")
            .MaximumLength(200).WithMessage("El nombre no puede exceder 200 caracteres");

        RuleFor(x => x.Descripcion)
            .MaximumLength(500).WithMessage("La descripción no puede exceder 500 caracteres");

        RuleFor(x => x.Items)
            .NotEmpty().WithMessage("Debe incluir al menos un ítem")
            .Must(items => items.Count <= 50).WithMessage("No puede exceder 50 ítems");

        RuleForEach(x => x.Items).SetValidator(new CreateChecklistItemRequestValidator());
    }
}

public class CreateChecklistItemRequestValidator : AbstractValidator<CreateChecklistItemRequest>
{
    public CreateChecklistItemRequestValidator()
    {
        RuleFor(x => x.Nombre)
            .NotEmpty().WithMessage("El nombre del ítem es obligatorio")
            .MaximumLength(200).WithMessage("El nombre no puede exceder 200 caracteres");

        RuleFor(x => x.Descripcion)
            .MaximumLength(500).WithMessage("La descripción no puede exceder 500 caracteres");

        RuleFor(x => x.Orden)
            .GreaterThan(0).WithMessage("El orden debe ser mayor a 0");
    }
}

public class CompletarChecklistRequestValidator : AbstractValidator<CompletarChecklistRequest>
{
    public CompletarChecklistRequestValidator()
    {
        RuleFor(x => x.Items)
            .NotEmpty().WithMessage("Debe incluir al menos un ítem");

        RuleForEach(x => x.Items).SetValidator(new CompletarItemRequestValidator());
    }
}

public class CompletarItemRequestValidator : AbstractValidator<CompletarItemRequest>
{
    public CompletarItemRequestValidator()
    {
        RuleFor(x => x.ItemPlantillaId)
            .NotEmpty().WithMessage("El ID del ítem es obligatorio");

        When(x => !x.Aprobado, () =>
        {
            RuleFor(x => x.Observacion)
                .NotEmpty().WithMessage("La observación es obligatoria cuando el ítem no es aprobado")
                .MaximumLength(500).WithMessage("La observación no puede exceder 500 caracteres");
        });
    }
}

public class LoginRequestValidator : AbstractValidator<LoginRequest>
{
    public LoginRequestValidator()
    {
        RuleFor(x => x.Email)
            .NotEmpty().WithMessage("El email es obligatorio")
            .EmailAddress().WithMessage("El formato del email no es válido");

        RuleFor(x => x.Password)
            .NotEmpty().WithMessage("La contraseña es obligatoria")
            .MinimumLength(6).WithMessage("La contraseña debe tener al menos 6 caracteres");
    }
}


