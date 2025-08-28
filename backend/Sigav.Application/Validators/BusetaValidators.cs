using FluentValidation;
using Sigav.Shared.DTOs;

namespace Sigav.Application.Validators;

/// <summary>
/// Validador para CreateBusetaDto
/// </summary>
public class CreateBusetaDtoValidator : BaseValidator<CreateBusetaDto>
{
    public CreateBusetaDtoValidator()
    {
        ValidateRequiredString(x => x.Placa, 20, "Placa");
        ValidateRequiredString(x => x.Marca, 100, "Marca");
        ValidateRequiredString(x => x.Modelo, 100, "Modelo");
        ValidateRange(x => x.Ano, 1900, 2100, "Año");
        ValidatePositive(x => x.Capacidad, "Capacidad");
        ValidatePositive(x => x.EmpresaId, "Empresa");

        RuleFor(x => x.Color)
            .MaximumLength(100)
            .When(x => !string.IsNullOrEmpty(x.Color))
            .WithMessage("El color no puede exceder 100 caracteres");

        RuleFor(x => x.NumeroMotor)
            .MaximumLength(100)
            .When(x => !string.IsNullOrEmpty(x.NumeroMotor))
            .WithMessage("El número de motor no puede exceder 100 caracteres");

        RuleFor(x => x.NumeroChasis)
            .MaximumLength(100)
            .When(x => !string.IsNullOrEmpty(x.NumeroChasis))
            .WithMessage("El número de chasis no puede exceder 100 caracteres");

        RuleFor(x => x.Estado)
            .Must(estado => new[] { "Disponible", "En Mantenimiento", "En Ruta", "Fuera de Servicio" }.Contains(estado))
            .When(x => !string.IsNullOrEmpty(x.Estado))
            .WithMessage("El estado debe ser: Disponible, En Mantenimiento, En Ruta, o Fuera de Servicio");

        RuleFor(x => x.Combustible)
            .Must(combustible => new[] { "Gasolina", "Diesel", "Electrico", "Hibrido" }.Contains(combustible))
            .When(x => !string.IsNullOrEmpty(x.Combustible))
            .WithMessage("El combustible debe ser: Gasolina, Diesel, Electrico, o Hibrido");

        RuleFor(x => x.ConsumoPromedio)
            .GreaterThan(0)
            .When(x => x.ConsumoPromedio.HasValue)
            .WithMessage("El consumo promedio debe ser mayor que 0");

        RuleFor(x => x.Kilometraje)
            .GreaterThanOrEqualTo(0)
            .When(x => x.Kilometraje.HasValue)
            .WithMessage("El kilometraje no puede ser negativo");

        RuleFor(x => x.Observaciones)
            .MaximumLength(500)
            .When(x => !string.IsNullOrEmpty(x.Observaciones))
            .WithMessage("Las observaciones no pueden exceder 500 caracteres");
    }
}

/// <summary>
/// Validador para UpdateBusetaDto
/// </summary>
public class UpdateBusetaDtoValidator : BaseValidator<UpdateBusetaDto>
{
    public UpdateBusetaDtoValidator()
    {
        ValidateRequiredString(x => x.Placa, 20, "Placa");
        ValidateRequiredString(x => x.Marca, 100, "Marca");
        ValidateRequiredString(x => x.Modelo, 100, "Modelo");
        ValidateRange(x => x.Ano, 1900, 2100, "Año");
        ValidatePositive(x => x.Capacidad, "Capacidad");
        ValidatePositive(x => x.EmpresaId, "Empresa");

        RuleFor(x => x.Color)
            .MaximumLength(100)
            .When(x => !string.IsNullOrEmpty(x.Color))
            .WithMessage("El color no puede exceder 100 caracteres");

        RuleFor(x => x.NumeroMotor)
            .MaximumLength(100)
            .When(x => !string.IsNullOrEmpty(x.NumeroMotor))
            .WithMessage("El número de motor no puede exceder 100 caracteres");

        RuleFor(x => x.NumeroChasis)
            .MaximumLength(100)
            .When(x => !string.IsNullOrEmpty(x.NumeroChasis))
            .WithMessage("El número de chasis no puede exceder 100 caracteres");

        RuleFor(x => x.Estado)
            .Must(estado => new[] { "Disponible", "En Mantenimiento", "En Ruta", "Fuera de Servicio" }.Contains(estado))
            .When(x => !string.IsNullOrEmpty(x.Estado))
            .WithMessage("El estado debe ser: Disponible, En Mantenimiento, En Ruta, o Fuera de Servicio");

        RuleFor(x => x.Combustible)
            .Must(combustible => new[] { "Gasolina", "Diesel", "Electrico", "Hibrido" }.Contains(combustible))
            .When(x => !string.IsNullOrEmpty(x.Combustible))
            .WithMessage("El combustible debe ser: Gasolina, Diesel, Electrico, o Hibrido");

        RuleFor(x => x.ConsumoPromedio)
            .GreaterThan(0)
            .When(x => x.ConsumoPromedio.HasValue)
            .WithMessage("El consumo promedio debe ser mayor que 0");

        RuleFor(x => x.Kilometraje)
            .GreaterThanOrEqualTo(0)
            .When(x => x.Kilometraje.HasValue)
            .WithMessage("El kilometraje no puede ser negativo");

        RuleFor(x => x.Observaciones)
            .MaximumLength(500)
            .When(x => !string.IsNullOrEmpty(x.Observaciones))
            .WithMessage("Las observaciones no pueden exceder 500 caracteres");
    }
}
