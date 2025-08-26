using Microsoft.EntityFrameworkCore;
using Serilog;
using Sigav.Api.Data;
using Sigav.Domain;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.IdentityModel.Tokens;
using System.Text;

var builder = WebApplication.CreateBuilder(args);

// Serilog
Log.Logger = new LoggerConfiguration()
    .ReadFrom.Configuration(builder.Configuration)
    .Enrich.FromLogContext()
    .CreateLogger();

builder.Host.UseSerilog();

// Auth (JWT)
var jwtSection = builder.Configuration.GetSection("Jwt");
var jwtKey = jwtSection.GetValue<string>("Key") ?? "dev-secret-key-please-change";
var jwtIssuer = jwtSection.GetValue<string>("Issuer") ?? "sigav";
var jwtAudience = jwtSection.GetValue<string>("Audience") ?? "sigav-clients";
var signingKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwtKey));

builder.Services
    .AddAuthentication(options =>
    {
        options.DefaultAuthenticateScheme = JwtBearerDefaults.AuthenticationScheme;
        options.DefaultChallengeScheme = JwtBearerDefaults.AuthenticationScheme;
    })
    .AddJwtBearer(options =>
    {
        options.RequireHttpsMetadata = false;
        options.SaveToken = true;
        options.TokenValidationParameters = new TokenValidationParameters
        {
            ValidateIssuer = true,
            ValidateAudience = true,
            ValidateIssuerSigningKey = true,
            ValidIssuer = jwtIssuer,
            ValidAudience = jwtAudience,
            IssuerSigningKey = signingKey,
            ClockSkew = TimeSpan.FromMinutes(2)
        };
    });

builder.Services.AddAuthorization(options =>
{
    options.AddPolicy("Admin", p => p.RequireRole("Admin"));
    options.AddPolicy("Inspector", p => p.RequireRole("Inspector"));
    options.AddPolicy("Mecanico", p => p.RequireRole("Mecanico"));
});

// Services
builder.Services.AddControllers();
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();

builder.Services.AddDbContext<SigavDbContext>(options =>
    options.UseNpgsql(builder.Configuration.GetConnectionString("DefaultConnection")));

// HealthChecks
builder.Services.AddHealthChecks()
    .AddNpgSql(builder.Configuration.GetConnectionString("DefaultConnection"), name: "postgres")
    .AddRedis(builder.Configuration.GetSection("Redis").GetValue<string>("ConnectionString"), name: "redis");

// CORS (ajuste simple por entorno)
var allowAll = builder.Configuration.GetValue<string>("ASPNETCORE_ENVIRONMENT") == "Development";
builder.Services.AddCors(options =>
{
    options.AddPolicy("Default", policy =>
    {
        if (allowAll)
        {
            policy.AllowAnyOrigin().AllowAnyMethod().AllowAnyHeader();
        }
        else
        {
            policy.WithOrigins("http://localhost:4200").AllowAnyMethod().AllowAnyHeader();
        }
    });
});

var app = builder.Build();

if (app.Environment.IsDevelopment())
{
    app.UseSwagger();
    app.UseSwaggerUI();
}

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

        var usuario = new Usuario
        {
            Nombre = "Admin",
            Apellido = "Demo",
            Email = "admin@demo.local",
            PasswordHash = "",
            Rol = "Admin",
            EmpresaId = empresa.Id
        };
        ctx.Usuarios.Add(usuario);

        var campo = new CustomField
        {
            Nombre = "Zona de Operación",
            Tipo = "Select",
            Opciones = "Norte|Sur|Este|Oeste",
            Entidad = "Buseta",
            EmpresaId = empresa.Id,
            Requerido = true,
            Orden = 1
        };
        ctx.CustomFields.Add(campo);

        await ctx.SaveChangesAsync();
    }
}

app.UseHttpsRedirection();
app.UseCors("Default");
app.UseAuthentication();
app.UseAuthorization();

// Health endpoints
app.MapHealthChecks("/health");
app.MapHealthChecks("/health/ready");

app.MapControllers();

app.Run();
