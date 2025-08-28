using FluentValidation;
using Sigav.Shared.DTOs;

namespace Sigav.Application.Validators;

/// <summary>
/// Validador base para DTOs
/// </summary>
public abstract class BaseValidator<T> : AbstractValidator<T>
{
    protected BaseValidator()
    {
        // Reglas comunes que se aplican a todos los DTOs
        RuleFor(x => x)
            .NotNull()
            .WithMessage("Los datos no pueden ser nulos");
    }

    /// <summary>
    /// Valida que una cadena no esté vacía y tenga la longitud máxima especificada
    /// </summary>
    protected IRuleBuilderOptions<T, string> ValidateRequiredString(
        System.Linq.Expressions.Expression<Func<T, string>> expression,
        int maxLength,
        string fieldName)
    {
        return RuleFor(expression)
            .NotEmpty()
            .WithMessage($"{fieldName} es requerido")
            .MaximumLength(maxLength)
            .WithMessage($"{fieldName} no puede exceder {maxLength} caracteres");
    }

    /// <summary>
    /// Valida que un entero esté en el rango especificado
    /// </summary>
    protected IRuleBuilderOptions<T, int> ValidateRange(
        System.Linq.Expressions.Expression<Func<T, int>> expression,
        int min,
        int max,
        string fieldName)
    {
        return RuleFor(expression)
            .InclusiveBetween(min, max)
            .WithMessage($"{fieldName} debe estar entre {min} y {max}");
    }

    /// <summary>
    /// Valida que un entero sea mayor que cero
    /// </summary>
    protected IRuleBuilderOptions<T, int> ValidatePositive(
        System.Linq.Expressions.Expression<Func<T, int>> expression,
        string fieldName)
    {
        return RuleFor(expression)
            .GreaterThan(0)
            .WithMessage($"{fieldName} debe ser mayor que 0");
    }
}
