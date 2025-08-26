using Microsoft.EntityFrameworkCore;
using Sigav.Domain;

namespace Sigav.Api.Data;

public class SigavDbContext : DbContext
{
    public SigavDbContext(DbContextOptions<SigavDbContext> options) : base(options)
    {
    }

    // DbSets para las entidades
    public DbSet<Empresa> Empresas { get; set; }
    public DbSet<Usuario> Usuarios { get; set; }
    public DbSet<Buseta> Busetas { get; set; }
    public DbSet<CustomField> CustomFields { get; set; }
    public DbSet<CustomFieldValue> CustomFieldValues { get; set; }
    public DbSet<ChecklistPlantilla> ChecklistPlantillas { get; set; }
    public DbSet<ChecklistItemPlantilla> ChecklistItemPlantillas { get; set; }
    public DbSet<ChecklistEjecucion> ChecklistEjecuciones { get; set; }
    public DbSet<ChecklistItemResultado> ChecklistItemResultados { get; set; }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        base.OnModelCreating(modelBuilder);

        // Configuración de Empresa
        modelBuilder.Entity<Empresa>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Nombre).IsRequired().HasMaxLength(200);
            entity.Property(e => e.Nit).HasMaxLength(100);
            entity.HasIndex(e => e.Nit).IsUnique();
        });

        // Configuración de Usuario
        modelBuilder.Entity<Usuario>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Email).IsRequired().HasMaxLength(100);
            entity.HasIndex(e => e.Email).IsUnique();
            entity.Property(e => e.PasswordHash).IsRequired().HasMaxLength(100);
            
            entity.HasOne(e => e.Empresa)
                  .WithMany(e => e.Usuarios)
                  .HasForeignKey(e => e.EmpresaId)
                  .OnDelete(DeleteBehavior.Restrict);
        });

        // Configuración de Buseta
        modelBuilder.Entity<Buseta>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Placa).IsRequired().HasMaxLength(20);
            entity.HasIndex(e => e.Placa).IsUnique();
            entity.Property(e => e.Marca).IsRequired().HasMaxLength(100);
            entity.Property(e => e.Modelo).IsRequired().HasMaxLength(100);
            
            entity.HasOne(e => e.Empresa)
                  .WithMany(e => e.Busetas)
                  .HasForeignKey(e => e.EmpresaId)
                  .OnDelete(DeleteBehavior.Restrict);
        });

        // Configuración de CustomField
        modelBuilder.Entity<CustomField>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Nombre).IsRequired().HasMaxLength(100);
            entity.Property(e => e.Tipo).IsRequired().HasMaxLength(50);
            entity.Property(e => e.Entidad).IsRequired().HasMaxLength(50);
            
            entity.HasOne(e => e.Empresa)
                  .WithMany(e => e.CustomFields)
                  .HasForeignKey(e => e.EmpresaId)
                  .OnDelete(DeleteBehavior.Cascade);
        });

        // Configuración de CustomFieldValue
        modelBuilder.Entity<CustomFieldValue>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Entidad).IsRequired().HasMaxLength(50);
            entity.Property(e => e.Valor).IsRequired().HasMaxLength(1000);
            
            entity.HasOne(e => e.CustomField)
                  .WithMany()
                  .HasForeignKey(e => e.CustomFieldId)
                  .OnDelete(DeleteBehavior.Cascade);
        });

        // Configuración de ChecklistPlantilla
        modelBuilder.Entity<ChecklistPlantilla>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Nombre).IsRequired().HasMaxLength(200);
            entity.Property(e => e.Tipo).IsRequired().HasMaxLength(50);
            
            entity.HasOne(e => e.Empresa)
                  .WithMany()
                  .HasForeignKey(e => e.EmpresaId)
                  .OnDelete(DeleteBehavior.Restrict);
        });

        // Configuración de ChecklistItemPlantilla
        modelBuilder.Entity<ChecklistItemPlantilla>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Nombre).IsRequired().HasMaxLength(200);
            entity.Property(e => e.Tipo).IsRequired().HasMaxLength(50);
            entity.Property(e => e.Orden).IsRequired();
            
            entity.HasOne(e => e.ChecklistPlantilla)
                  .WithMany(e => e.Items)
                  .HasForeignKey(e => e.ChecklistPlantillaId)
                  .OnDelete(DeleteBehavior.Cascade);
        });

        // Configuración de ChecklistEjecucion
        modelBuilder.Entity<ChecklistEjecucion>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.FechaInicio).IsRequired();
            entity.Property(e => e.Estado).IsRequired().HasMaxLength(50);
            
            entity.HasOne(e => e.Buseta)
                  .WithMany()
                  .HasForeignKey(e => e.BusetaId)
                  .OnDelete(DeleteBehavior.Restrict);
                  
            entity.HasOne(e => e.ChecklistPlantilla)
                  .WithMany()
                  .HasForeignKey(e => e.ChecklistPlantillaId)
                  .OnDelete(DeleteBehavior.Restrict);
                  
            entity.HasOne(e => e.Inspector)
                  .WithMany()
                  .HasForeignKey(e => e.InspectorId)
                  .OnDelete(DeleteBehavior.Restrict);
        });

        // Configuración de ChecklistItemResultado
        modelBuilder.Entity<ChecklistItemResultado>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Resultado).IsRequired().HasMaxLength(50);
            entity.Property(e => e.FechaVerificacion).IsRequired();
            
            entity.HasOne(e => e.ChecklistEjecucion)
                  .WithMany(e => e.ItemsResultado)
                  .HasForeignKey(e => e.ChecklistEjecucionId)
                  .OnDelete(DeleteBehavior.Cascade);
                  
            entity.HasOne(e => e.ChecklistItemPlantilla)
                  .WithMany()
                  .HasForeignKey(e => e.ChecklistItemPlantillaId)
                  .OnDelete(DeleteBehavior.Restrict);
        });
    }
}
