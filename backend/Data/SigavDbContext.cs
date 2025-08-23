using Microsoft.AspNetCore.Identity.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore;
using Sigav.Api.Domain;

namespace Sigav.Api.Data;

public class SigavDbContext : IdentityDbContext<Usuario>
{
    public SigavDbContext(DbContextOptions<SigavDbContext> options) : base(options)
    {
    }

    public DbSet<Buseta> Busetas { get; set; }
    public DbSet<ChecklistPlantilla> ChecklistPlantillas { get; set; }
    public DbSet<ChecklistItemPlantilla> ChecklistItemPlantillas { get; set; }
    public DbSet<ChecklistEjecucion> ChecklistEjecuciones { get; set; }
    public DbSet<ChecklistItemResultado> ChecklistItemResultados { get; set; }

    protected override void OnModelCreating(ModelBuilder builder)
    {
        base.OnModelCreating(builder);

        // Usuario configuration
        builder.Entity<Usuario>(entity =>
        {
            entity.Property(e => e.Rol)
                .HasConversion<string>()
                .HasMaxLength(20);
        });

        // Buseta configuration
        builder.Entity<Buseta>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Placa)
                .IsRequired()
                .HasMaxLength(20);
            entity.Property(e => e.Modelo)
                .IsRequired()
                .HasMaxLength(100);
            entity.Property(e => e.Agencia)
                .IsRequired()
                .HasMaxLength(100);
            entity.Property(e => e.Estado)
                .HasConversion<string>()
                .HasMaxLength(20);

            entity.HasIndex(e => e.Placa)
                .IsUnique();
        });

        // ChecklistPlantilla configuration
        builder.Entity<ChecklistPlantilla>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Nombre)
                .IsRequired()
                .HasMaxLength(100);
            entity.Property(e => e.Descripcion)
                .HasMaxLength(500);
        });

        // ChecklistItemPlantilla configuration
        builder.Entity<ChecklistItemPlantilla>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Nombre)
                .IsRequired()
                .HasMaxLength(100);
            entity.Property(e => e.Descripcion)
                .HasMaxLength(500);

            entity.HasOne(e => e.Plantilla)
                .WithMany(p => p.Items)
                .HasForeignKey(e => e.ChecklistPlantillaId)
                .OnDelete(DeleteBehavior.Cascade);
        });

        // ChecklistEjecucion configuration
        builder.Entity<ChecklistEjecucion>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.ObservacionesGenerales)
                .HasMaxLength(1000);
            entity.Property(e => e.Estado)
                .HasConversion<string>()
                .HasMaxLength(20);

            entity.HasOne(e => e.Buseta)
                .WithMany(b => b.ChecklistEjecuciones)
                .HasForeignKey(e => e.BusetaId)
                .OnDelete(DeleteBehavior.Restrict);

            entity.HasOne(e => e.Plantilla)
                .WithMany(p => p.Ejecuciones)
                .HasForeignKey(e => e.ChecklistPlantillaId)
                .OnDelete(DeleteBehavior.Restrict);

            entity.HasOne(e => e.Inspector)
                .WithMany()
                .HasForeignKey(e => e.InspectorId)
                .OnDelete(DeleteBehavior.Restrict);
        });

        // ChecklistItemResultado configuration
        builder.Entity<ChecklistItemResultado>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Observacion)
                .HasMaxLength(500);

            entity.HasOne(e => e.Ejecucion)
                .WithMany(ec => ec.Resultados)
                .HasForeignKey(e => e.ChecklistEjecucionId)
                .OnDelete(DeleteBehavior.Cascade);

            entity.HasOne(e => e.ItemPlantilla)
                .WithMany(ip => ip.Resultados)
                .HasForeignKey(e => e.ChecklistItemPlantillaId)
                .OnDelete(DeleteBehavior.Restrict);
        });
    }
}
