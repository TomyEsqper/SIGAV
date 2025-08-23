using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.AspNetCore.Authorization;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using Microsoft.OpenApi.Models;
using Sigav.Api.Data;
using Sigav.Api.Domain;
using Sigav.Api.Services;
using Sigav.Api.Validators;
using System.Text;

var builder = WebApplication.CreateBuilder(args);

// Add services to the container
builder.Services.AddControllers();
builder.Services.AddEndpointsApiExplorer();

// Swagger
builder.Services.AddSwaggerGen(c =>
{
    c.SwaggerDoc("v1", new OpenApiInfo 
    { 
        Title = "SIGAV API", 
        Version = "v1",
        Description = "API para el Sistema de Alistamiento de Busetas"
    });

    c.AddSecurityDefinition("Bearer", new OpenApiSecurityScheme
    {
        Description = "JWT Authorization header using the Bearer scheme",
        Name = "Authorization",
        In = ParameterLocation.Header,
        Type = SecuritySchemeType.ApiKey,
        Scheme = "Bearer"
    });

    c.AddSecurityRequirement(new OpenApiSecurityRequirement
    {
        {
            new OpenApiSecurityScheme
            {
                Reference = new OpenApiReference
                {
                    Type = ReferenceType.SecurityScheme,
                    Id = "Bearer"
                }
            },
            Array.Empty<string>()
        }
    });

    c.TagActionsBy(api => new[] { api.GroupName ?? api.ActionDescriptor.RouteValues["controller"] ?? "default" });
});

// CORS
builder.Services.AddCors(options =>
{
    options.AddPolicy("AllowFrontend", policy =>
        policy.WithOrigins("http://localhost:4200")
              .AllowAnyHeader()
              .AllowAnyMethod());
});

// Authentication & Authorization
builder.Services.AddAuthentication(options =>
{
    options.DefaultAuthenticateScheme = JwtBearerDefaults.AuthenticationScheme;
    options.DefaultChallengeScheme = JwtBearerDefaults.AuthenticationScheme;
})
.AddJwtBearer(options =>
{
    var jwtSection = builder.Configuration.GetSection("Jwt");
    var key = Encoding.UTF8.GetBytes(jwtSection["Key"]!);
    options.TokenValidationParameters = new TokenValidationParameters
    {
        ValidateIssuer = true,
        ValidateAudience = true,
        ValidateIssuerSigningKey = true,
        ValidIssuer = jwtSection["Issuer"],
        ValidAudience = jwtSection["Audience"],
        IssuerSigningKey = new SymmetricSecurityKey(key),
        ClockSkew = TimeSpan.Zero
    };
});

builder.Services.AddAuthorization(options =>
{
    options.AddPolicy("AdminOnly", policy => policy.RequireRole("Admin"));
    options.AddPolicy("InspectorOrAdmin", policy => policy.RequireRole("Admin", "Inspector"));
    options.AddPolicy("MecanicoOrAdmin", policy => policy.RequireRole("Admin", "Mecanico"));
});

// Database
builder.Services.AddDbContext<SigavDbContext>(options =>
{
    var conn = builder.Configuration.GetConnectionString("Default");
    options.UseNpgsql(conn);
});

// Redis
builder.Services.AddStackExchangeRedisCache(options =>
{
    options.Configuration = builder.Configuration.GetConnectionString("Redis");
});

// Identity
builder.Services.AddIdentity<Usuario, IdentityRole>()
    .AddEntityFrameworkStores<SigavDbContext>()
    .AddDefaultTokenProviders();

// Services
builder.Services.AddScoped<IAuthService, AuthService>();
builder.Services.AddScoped<IExportService, ExportService>();

// Validators
builder.Services.AddFluentValidationAutoValidation();
builder.Services.AddValidatorsFromAssemblyContaining<CreateBusetaRequestValidator>();

var app = builder.Build();

// Configure the HTTP request pipeline
if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI(c => c.SwaggerEndpoint("/swagger/v1/swagger.json", "SIGAV API v1"));
}

app.UseHttpsRedirection();
app.UseCors("AllowFrontend");
app.UseAuthentication();
app.UseAuthorization();

// API Routes
app.MapControllers();

// Ensure database is created and seeded
using (var scope = app.Services.CreateScope())
{
    var db = scope.ServiceProvider.GetRequiredService<SigavDbContext>();
    await db.Database.EnsureCreatedAsync();
    
    // Seed data
    await SeedDataAsync(db, scope.ServiceProvider.GetRequiredService<IAuthService>());
}

app.Run();

// Seed method
async Task SeedDataAsync(SigavDbContext db, IAuthService authService)
{
    // Check if already seeded
    if (await db.Users.AnyAsync())
        return;

    // Create admin user
    await authService.CreateUserAsync(
        "admin@sigav.local", 
        "Admin_123!", 
        "Administrador", 
        "Sistema", 
        RolUsuario.Admin
    );

    // Create demo users
    await authService.CreateUserAsync(
        "inspector@sigav.local", 
        "Inspector_123!", 
        "Juan", 
        "Pérez", 
        RolUsuario.Inspector
    );

    await authService.CreateUserAsync(
        "mecanico@sigav.local", 
        "Mecanico_123!", 
        "Carlos", 
        "García", 
        RolUsuario.Mecanico
    );

    // Create demo busetas
    var buseta1 = new Buseta
    {
        Placa = "ABC123",
        Modelo = "Mercedes-Benz O500",
        Capacidad = 45,
        Agencia = "Transportes Unidos"
    };

    var buseta2 = new Buseta
    {
        Placa = "XYZ789",
        Modelo = "Volvo B7R",
        Capacidad = 50,
        Agencia = "Express del Norte"
    };

    db.Busetas.AddRange(buseta1, buseta2);

    // Create demo checklist template
    var plantilla = new ChecklistPlantilla
    {
        Nombre = "Checklist Básico",
        Descripcion = "Verificación estándar de seguridad y funcionamiento",
        Items = new List<ChecklistItemPlantilla>
        {
            new() { Nombre = "Luces", Descripcion = "Verificar funcionamiento de todas las luces", Orden = 1 },
            new() { Nombre = "Frenos", Descripcion = "Probar sistema de frenos", Orden = 2 },
            new() { Nombre = "Botiquín", Descripcion = "Verificar botiquín de primeros auxilios", Orden = 3 },
            new() { Nombre = "Extintor", Descripcion = "Verificar extintor de incendios", Orden = 4 },
            new() { Nombre = "Llantas", Descripcion = "Revisar estado de las llantas", Orden = 5 }
        }
    };

    db.ChecklistPlantillas.Add(plantilla);

    await db.SaveChangesAsync();
}
