using AutoMapper;
using FluentValidation;
using Moq;
using Sigav.Application.Mapping;
using Sigav.Application.Services;
using Sigav.Application.Validators;
using Sigav.Shared.DTOs;
using Sigav.Domain;
using Sigav.Domain.Repositories;
using Xunit;

namespace Sigav.Tests.Services;

public class BusetaServiceTests
{
    private readonly Mock<IBusetaRepository> _mockBusetaRepository;
    private readonly Mock<IRepository<CustomFieldValue>> _mockCustomFieldValueRepository;
    private readonly Mock<ILogger<BusetaService>> _mockLogger;
    private readonly IMapper _mapper;
    private readonly IValidator<CreateBusetaDto> _createValidator;
    private readonly IValidator<UpdateBusetaDto> _updateValidator;

    public BusetaServiceTests()
    {
        _mockBusetaRepository = new Mock<IBusetaRepository>();
        _mockCustomFieldValueRepository = new Mock<IRepository<CustomFieldValue>>();
        _mockLogger = new Mock<ILogger<BusetaService>>();
        
        var mappingConfig = new MapperConfiguration(mc =>
        {
            mc.AddProfile(new MappingProfile());
        });
        _mapper = mappingConfig.CreateMapper();
        
        _createValidator = new CreateBusetaDtoValidator();
        _updateValidator = new UpdateBusetaDtoValidator();
    }

    [Fact]
    public async Task GetAllAsync_ShouldReturnMappedDtos()
    {
        // Arrange
        var busetas = new List<Buseta>
        {
            new Buseta { Id = 1, Placa = "ABC123", Marca = "Toyota", Modelo = "Hiace", Ano = 2020, Color = "Blanco", EmpresaId = 1 },
            new Buseta { Id = 2, Placa = "XYZ789", Marca = "Nissan", Modelo = "Urvan", Ano = 2021, Color = "Azul", EmpresaId = 1 }
        };

        _mockBusetaRepository.Setup(r => r.GetAllAsync()).ReturnsAsync(busetas);

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act
        var result = await service.GetAllAsync();

        // Assert
        Assert.NotNull(result);
        Assert.Equal(2, result.Count());
        Assert.Equal("ABC123", result.First().Placa);
        Assert.Equal("Toyota", result.First().Marca);
    }

    [Fact]
    public async Task GetByIdAsync_WithValidId_ShouldReturnBuseta()
    {
        // Arrange
        var buseta = new Buseta { Id = 1, Placa = "ABC123", Marca = "Toyota", Modelo = "Hiace", Ano = 2020, Color = "Blanco", EmpresaId = 1 };
        _mockBusetaRepository.Setup(r => r.GetByIdAsync(1)).ReturnsAsync(buseta);

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act
        var result = await service.GetByIdAsync(1);

        // Assert
        Assert.NotNull(result);
        Assert.Equal(1, result.Id);
        Assert.Equal("ABC123", result.Placa);
    }

    [Fact]
    public async Task GetByIdAsync_WithInvalidId_ShouldReturnNull()
    {
        // Arrange
        _mockBusetaRepository.Setup(r => r.GetByIdAsync(999)).ReturnsAsync((Buseta?)null);

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act
        var result = await service.GetByIdAsync(999);

        // Assert
        Assert.Null(result);
    }

    [Fact]
    public async Task CreateAsync_WithValidDto_ShouldReturnCreatedBuseta()
    {
        // Arrange
        var createDto = new CreateBusetaDto
        {
            Placa = "ABC123",
            Marca = "Toyota",
            Modelo = "Hiace",
            Ano = 2020,
            Capacidad = 15,
            Color = "Blanco",
            EmpresaId = 1
        };

        var createdBuseta = new Buseta
        {
            Id = 1,
            Placa = "ABC123",
            Marca = "Toyota",
            Modelo = "Hiace",
            Ano = 2020,
            Capacidad = 15,
            Color = "Blanco",
            EmpresaId = 1,
            FechaCreacion = DateTime.UtcNow,
            Activo = true
        };

        _mockBusetaRepository.Setup(r => r.AddAsync(It.IsAny<Buseta>())).ReturnsAsync(createdBuseta);
        _mockBusetaRepository.Setup(r => r.FindAsync(It.IsAny<Expression<Func<Buseta, bool>>>())).ReturnsAsync(new List<Buseta>());

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act
        var result = await service.CreateAsync(createDto);

        // Assert
        Assert.NotNull(result);
        Assert.Equal(1, result.Id);
        Assert.Equal("ABC123", result.Placa);
        Assert.True(result.Activo);
    }

    [Fact]
    public async Task CreateAsync_WithInvalidDto_ShouldThrowValidationException()
    {
        // Arrange
        var createDto = new CreateBusetaDto
        {
            Placa = "", // Inválido
            Marca = "Toyota",
            Modelo = "Hiace",
            Ano = 2020,
            Capacidad = 15,
            EmpresaId = 1
        };

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act & Assert
        var exception = await Assert.ThrowsAsync<ValidationException>(() => service.CreateAsync(createDto));
        Assert.Contains("Placa es requerido", exception.Message);
    }

    [Fact]
    public async Task CreateAsync_WithDuplicatePlaca_ShouldThrowValidationException()
    {
        // Arrange
        var createDto = new CreateBusetaDto
        {
            Placa = "ABC123",
            Marca = "Toyota",
            Modelo = "Hiace",
            Ano = 2020,
            Capacidad = 15,
            EmpresaId = 1
        };

        var existingBusetas = new List<Buseta>
        {
            new Buseta { Id = 2, Placa = "ABC123", EmpresaId = 1 }
        };

        _mockBusetaRepository.Setup(r => r.FindAsync(It.IsAny<Expression<Func<Buseta, bool>>>())).ReturnsAsync(existingBusetas);

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act & Assert
        var exception = await Assert.ThrowsAsync<ValidationException>(() => service.CreateAsync(createDto));
        Assert.Contains("Ya existe una buseta con la placa ABC123", exception.Message);
    }

    [Fact]
    public async Task UpdateAsync_WithValidDto_ShouldUpdateBuseta()
    {
        // Arrange
        var updateDto = new UpdateBusetaDto
        {
            Placa = "ABC123",
            Marca = "Toyota",
            Modelo = "Hiace",
            Ano = 2020,
            Capacidad = 15,
            Color = "Blanco",
            EmpresaId = 1
        };

        var existingBuseta = new Buseta
        {
            Id = 1,
            Placa = "ABC123",
            Marca = "Toyota",
            Modelo = "Hiace",
            Ano = 2020,
            Capacidad = 15,
            Color = "Blanco",
            EmpresaId = 1
        };

        _mockBusetaRepository.Setup(r => r.GetByIdAsync(1)).ReturnsAsync(existingBuseta);
        _mockBusetaRepository.Setup(r => r.FindAsync(It.IsAny<Expression<Func<Buseta, bool>>>())).ReturnsAsync(new List<Buseta>());
        _mockBusetaRepository.Setup(r => r.UpdateAsync(It.IsAny<Buseta>())).Returns(Task.CompletedTask);

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act
        await service.UpdateAsync(1, updateDto);

        // Assert
        _mockBusetaRepository.Verify(r => r.UpdateAsync(It.IsAny<Buseta>()), Times.Once);
    }

    [Fact]
    public async Task UpdateAsync_WithNonExistentId_ShouldThrowNotFoundException()
    {
        // Arrange
        var updateDto = new UpdateBusetaDto
        {
            Placa = "ABC123",
            Marca = "Toyota",
            Modelo = "Hiace",
            Ano = 2020,
            Capacidad = 15,
            EmpresaId = 1
        };

        _mockBusetaRepository.Setup(r => r.GetByIdAsync(999)).ReturnsAsync((Buseta?)null);

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act & Assert
        var exception = await Assert.ThrowsAsync<NotFoundException>(() => service.UpdateAsync(999, updateDto));
        Assert.Contains("Buseta con ID 999 no encontrado", exception.Message);
    }

    [Fact]
    public async Task DeleteAsync_WithValidId_ShouldDeleteBuseta()
    {
        // Arrange
        var buseta = new Buseta { Id = 1, Placa = "ABC123", EmpresaId = 1 };
        _mockBusetaRepository.Setup(r => r.GetByIdAsync(1)).ReturnsAsync(buseta);
        _mockBusetaRepository.Setup(r => r.DeleteAsync(1)).Returns(Task.CompletedTask);

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act
        await service.DeleteAsync(1);

        // Assert
        _mockBusetaRepository.Verify(r => r.DeleteAsync(1), Times.Once);
    }

    [Fact]
    public async Task DeleteAsync_WithNonExistentId_ShouldThrowNotFoundException()
    {
        // Arrange
        _mockBusetaRepository.Setup(r => r.GetByIdAsync(999)).ReturnsAsync((Buseta?)null);

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act & Assert
        var exception = await Assert.ThrowsAsync<NotFoundException>(() => service.DeleteAsync(999));
        Assert.Contains("Buseta con ID 999 no encontrado", exception.Message);
    }

    [Fact]
    public async Task GetCustomFieldsAsync_WithValidId_ShouldReturnCustomFields()
    {
        // Arrange
        var customFieldValues = new List<CustomFieldValue>
        {
            new CustomFieldValue { Id = 1, EntidadId = 1, Valor = "Norte", CustomFieldId = 1 },
            new CustomFieldValue { Id = 2, EntidadId = 1, Valor = "Urbano", CustomFieldId = 2 }
        };

        _mockCustomFieldValueRepository.Setup(r => r.FindAsync(It.IsAny<Expression<Func<CustomFieldValue, bool>>>())).ReturnsAsync(customFieldValues);

        var service = new BusetaService(_mockBusetaRepository.Object, _mockCustomFieldValueRepository.Object, _mapper, _mockLogger.Object, _createValidator, _updateValidator);

        // Act
        var result = await service.GetCustomFieldsAsync(1);

        // Assert
        Assert.NotNull(result);
        Assert.Equal(2, result.Count());
    }
}
