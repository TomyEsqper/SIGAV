using Microsoft.EntityFrameworkCore;
using Sigav.Api.Data;
using Sigav.Domain;

var builder = WebApplication.CreateBuilder(args);

// Add services to the container.
builder.Services.AddControllers();
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();

// Database
builder.Services.AddDbContext<SigavDbContext>(options =>
    options.UseNpgsql(builder.Configuration.GetConnectionString("DefaultConnection")));

// CORS
builder.Services.AddCors(options =>
{
    options.AddPolicy("AllowAll", policy =>
    {
        policy.AllowAnyOrigin()
              .AllowAnyMethod()
              .AllowAnyHeader();
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
app.UseCors("AllowAll");
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
            EmpresaId = empresa.Id
        };
        ctx.CustomFields.Add(campo);

        await ctx.SaveChangesAsync();
    }
}

app.Run();
