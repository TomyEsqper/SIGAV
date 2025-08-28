using Microsoft.EntityFrameworkCore;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.IdentityModel.Tokens;
using Sigav.Api.Data;
using Sigav.Domain;
using System.Text;

var builder = WebApplication.CreateBuilder(args);

// Add services to the container.
builder.Services.AddControllers();
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();

// Database
builder.Services.AddDbContext<SigavDbContext>(options =>
    options.UseNpgsql(builder.Configuration.GetConnectionString("DefaultConnection")));

// JWT Authentication
var jwtKey = builder.Configuration["Jwt:Key"];
var jwtIssuer = builder.Configuration["Jwt:Issuer"];
var jwtAudience = builder.Configuration["Jwt:Audience"];

if (!string.IsNullOrEmpty(jwtKey))
{
    builder.Services.AddAuthentication(JwtBearerDefaults.AuthenticationScheme)
        .AddJwtBearer(options =>
        {
            options.TokenValidationParameters = new TokenValidationParameters
            {
                ValidateIssuer = true,
                ValidateAudience = true,
                ValidateLifetime = true,
                ValidateIssuerSigningKey = true,
                ValidIssuer = jwtIssuer,
                ValidAudience = jwtAudience,
                IssuerSigningKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwtKey))
            };
        });
}

// CORS
builder.Services.AddCors(options =>
{
    options.AddPolicy("AllowSigav", policy =>
    {
        policy.WithOrigins("http://localhost:4200", "https://app.sigav.com", "https://staging.sigav.com")
              .AllowAnyMethod()
              .AllowAnyHeader()
              .AllowCredentials();
    });
});

// Health Checks
builder.Services.AddHealthChecks();

var app = builder.Build();

// Configure the HTTP request pipeline.
if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI();
}

app.UseHttpsRedirection();
app.UseCors("AllowSigav");
app.UseAuthentication();
app.UseAuthorization();

app.MapControllers();
app.MapHealthChecks("/health");

// DB migrate + seed mínimo
using (var scope = app.Services.CreateScope())
{
    var ctx = scope.ServiceProvider.GetRequiredService<SigavDbContext>();
    await ctx.Database.MigrateAsync();

    if (!await ctx.Empresas.AnyAsync())
    {
        var empresa = new Empresa { Nombre = "Empresa Demo", Nit = "123-456" };
        ctx.Empresas.Add(empresa);
        await ctx.SaveChangesAsync();

        // Crear hash de contraseña para el usuario demo
        using var sha256 = System.Security.Cryptography.SHA256.Create();
        var passwordBytes = System.Text.Encoding.UTF8.GetBytes("admin123");
        var hashBytes = sha256.ComputeHash(passwordBytes);
        var passwordHash = Convert.ToBase64String(hashBytes);

        var usuario = new Usuario
        {
            Nombre = "Admin",
            Apellido = "Demo",
            Email = "admin@demo.local",
            PasswordHash = passwordHash,
            Rol = "Admin",
            EmpresaId = empresa.Id,
            FailedAttempts = 0,
            LastLoginAt = null
        };
        ctx.Usuarios.Add(usuario);

        var campo = new CustomField
        {
            Nombre = "Zona de Operación",
            Tipo = "Select",
            Opciones = "Norte|Sur|Este|Oeste",
            Entidad = "Buseta",
            EmpresaId = empresa.Id
        };
        ctx.CustomFields.Add(campo);

        await ctx.SaveChangesAsync();
    }
}

app.Run();
