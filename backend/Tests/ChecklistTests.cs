using FluentValidation.TestHelper;
using Sigav.Api.DTOs;
using Sigav.Api.Validators;

namespace Sigav.Api.Tests;

public class ChecklistTests
{
    private readonly CompletarChecklistRequestValidator _validator;

    public ChecklistTests()
    {
        _validator = new CompletarChecklistRequestValidator();
    }

    [Fact]
    public void CompletarChecklistRequest_ValidData_ShouldPassValidation()
    {
        // Arrange
        var request = new CompletarChecklistRequest
        {
            Resultados = new List<ChecklistItemResultadoRequest>
            {
                new() { ItemPlantillaId = 1, Aprobado = true },
                new() { ItemPlantillaId = 2, Aprobado = false, Observacion = "Falla en frenos" }
            },
            ObservacionesGenerales = "Checklist completado con observaciones"
        };

        // Act
        var result = _validator.Validate(request);

        // Assert
        Assert.True(result.IsValid);
    }

    [Fact]
    public void CompletarChecklistRequest_EmptyResultados_ShouldFailValidation()
    {
        // Arrange
        var request = new CompletarChecklistRequest
        {
            Resultados = new List<ChecklistItemResultadoRequest>(),
            ObservacionesGenerales = "Checklist completado"
        };

        // Act
        var result = _validator.Validate(request);

        // Assert
        Assert.False(result.IsValid);
        Assert.Contains(result.Errors, e => e.PropertyName == "Resultados");
    }

    [Fact]
    public void CompletarChecklistRequest_RejectedItemWithoutObservation_ShouldFailValidation()
    {
        // Arrange
        var request = new CompletarChecklistRequest
        {
            Resultados = new List<ChecklistItemResultadoRequest>
            {
                new() { ItemPlantillaId = 1, Aprobado = false } // Missing observation
            },
            ObservacionesGenerales = "Checklist completado"
        };

        // Act
        var result = _validator.Validate(request);

        // Assert
        Assert.False(result.IsValid);
        Assert.Contains(result.Errors, e => e.PropertyName == "Resultados");
    }

    [Fact]
    public void CompletarChecklistRequest_ValidRejectedItem_ShouldPassValidation()
    {
        // Arrange
        var request = new CompletarChecklistRequest
        {
            Resultados = new List<ChecklistItemResultadoRequest>
            {
                new() { ItemPlantillaId = 1, Aprobado = false, Observacion = "Falla en sistema" }
            },
            ObservacionesGenerales = "Checklist completado"
        };

        // Act
        var result = _validator.Validate(request);

        // Assert
        Assert.True(result.IsValid);
    }
}
