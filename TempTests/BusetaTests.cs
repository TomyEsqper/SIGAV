using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Api.Domain;
using Sigav.Api.DTOs;
using Sigav.Api.Validators;
using Xunit;

namespace Sigav.Api.Tests;

public class BusetaTests
{
    private readonly CreateBusetaRequestValidator _validator;

    public BusetaTests()
    {
        _validator = new CreateBusetaRequestValidator();
    }

    [Fact]
    public void CreateBusetaRequest_ValidData_ShouldPassValidation()
    {
        // Arrange
        var request = new CreateBusetaRequest
        {
            Placa = "ABC123",
            Modelo = "Mercedes-Benz O500",
            Capacidad = 45,
            Agencia = "Transportes Unidos"
        };

        // Act
        var result = _validator.Validate(request);

        // Assert
        Assert.True(result.IsValid);
    }

    [Fact]
    public void CreateBusetaRequest_InvalidPlaca_ShouldFailValidation()
    {
        // Arrange
        var request = new CreateBusetaRequest
        {
            Placa = "INVALID",
            Modelo = "Mercedes-Benz O500",
            Capacidad = 45,
            Agencia = "Transportes Unidos"
        };

        // Act
        var result = _validator.Validate(request);

        // Assert
        Assert.False(result.IsValid);
        Assert.Contains(result.Errors, e => e.PropertyName == "Placa");
    }

    [Fact]
    public void CreateBusetaRequest_EmptyModelo_ShouldFailValidation()
    {
        // Arrange
        var request = new CreateBusetaRequest
        {
            Placa = "ABC123",
            Modelo = "",
            Capacidad = 45,
            Agencia = "Transportes Unidos"
        };

        // Act
        var result = _validator.Validate(request);

        // Assert
        Assert.False(result.IsValid);
        Assert.Contains(result.Errors, e => e.PropertyName == "Modelo");
    }

    [Fact]
    public void CreateBusetaRequest_InvalidCapacidad_ShouldFailValidation()
    {
        // Arrange
        var request = new CreateBusetaRequest
        {
            Placa = "ABC123",
            Modelo = "Mercedes-Benz O500",
            Capacidad = 0,
            Agencia = "Transportes Unidos"
        };

        // Act
        var result = _validator.Validate(request);

        // Assert
        Assert.False(result.IsValid);
        Assert.Contains(result.Errors, e => e.PropertyName == "Capacidad");
    }
}
