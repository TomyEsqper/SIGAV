using Microsoft.AspNetCore.Mvc.Testing;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.DependencyInjection;
using System.Net;
using System.Text;
using System.Text.Json;
using Sigav.Api.Data;
using Sigav.Shared.DTOs;

namespace Sigav.Tests.Integration;

public class BusetasControllerIntegrationTests : IClassFixture<WebApplicationFactory<Program>>
{
    private readonly WebApplicationFactory<Program> _factory;
    private readonly HttpClient _client;

    public BusetasControllerIntegrationTests(WebApplicationFactory<Program> factory)
    {
        _factory = factory.WithWebHostBuilder(builder =>
        {
            builder.ConfigureServices(services =>
            {
                // Reemplazar DbContext con in-memory database
                var descriptor = services.SingleOrDefault(
                    d => d.ServiceType == typeof(DbContextOptions<SigavDbContext>));
                if (descriptor != null)
                {
                    services.Remove(descriptor);
                }

                services.AddDbContext<SigavDbContext>(options =>
                {
                    options.UseInMemoryDatabase("TestDatabase");
                });
            });
        });

        _client = _factory.CreateClient();
    }

    [Fact]
    public async Task GetAll_ShouldReturnEmptyList_WhenNoBusetasExist()
    {
        // Act
        var response = await _client.GetAsync("/api/busetas");
        var content = await response.Content.ReadAsStringAsync();
        var busetas = JsonSerializer.Deserialize<List<BusetaDto>>(content, new JsonSerializerOptions
        {
            PropertyNameCaseInsensitive = true
        });

        // Assert
        response.EnsureSuccessStatusCode();
        Assert.NotNull(busetas);
        Assert.Empty(busetas);
    }

    [Fact]
    public async Task Create_ShouldReturnCreatedBuseta_WhenValidDataProvided()
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

        var json = JsonSerializer.Serialize(createDto);
        var content = new StringContent(json, Encoding.UTF8, "application/json");

        // Act
        var response = await _client.PostAsync("/api/busetas", content);
        var responseContent = await response.Content.ReadAsStringAsync();
        var createdBuseta = JsonSerializer.Deserialize<BusetaDto>(responseContent, new JsonSerializerOptions
        {
            PropertyNameCaseInsensitive = true
        });

        // Assert
        Assert.Equal(HttpStatusCode.Created, response.StatusCode);
        Assert.NotNull(createdBuseta);
        Assert.Equal("ABC123", createdBuseta.Placa);
        Assert.Equal("Toyota", createdBuseta.Marca);
        Assert.True(createdBuseta.Activo);
    }

    [Fact]
    public async Task Create_ShouldReturnBadRequest_WhenInvalidDataProvided()
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

        var json = JsonSerializer.Serialize(createDto);
        var content = new StringContent(json, Encoding.UTF8, "application/json");

        // Act
        var response = await _client.PostAsync("/api/busetas", content);

        // Assert
        Assert.Equal(HttpStatusCode.BadRequest, response.StatusCode);
    }

    [Fact]
    public async Task GetById_ShouldReturnNotFound_WhenBusetaDoesNotExist()
    {
        // Act
        var response = await _client.GetAsync("/api/busetas/999");

        // Assert
        Assert.Equal(HttpStatusCode.NotFound, response.StatusCode);
    }

    [Fact]
    public async Task Update_ShouldReturnNotFound_WhenBusetaDoesNotExist()
    {
        // Arrange
        var updateDto = new UpdateBusetaDto
        {
            Placa = "XYZ789",
            Marca = "Nissan",
            Modelo = "Urvan",
            Ano = 2021,
            Capacidad = 20,
            EmpresaId = 1
        };

        var json = JsonSerializer.Serialize(updateDto);
        var content = new StringContent(json, Encoding.UTF8, "application/json");

        // Act
        var response = await _client.PutAsync("/api/busetas/999", content);

        // Assert
        Assert.Equal(HttpStatusCode.NotFound, response.StatusCode);
    }

    [Fact]
    public async Task Delete_ShouldReturnNotFound_WhenBusetaDoesNotExist()
    {
        // Act
        var response = await _client.DeleteAsync("/api/busetas/999");

        // Assert
        Assert.Equal(HttpStatusCode.NotFound, response.StatusCode);
    }

    [Fact]
    public async Task GetCustomFields_ShouldReturnNotFound_WhenBusetaDoesNotExist()
    {
        // Act
        var response = await _client.GetAsync("/api/busetas/999/custom-fields");

        // Assert
        Assert.Equal(HttpStatusCode.NotFound, response.StatusCode);
    }
}
