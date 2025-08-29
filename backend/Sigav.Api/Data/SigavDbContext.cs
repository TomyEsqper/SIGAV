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
    public DbSet<AuthLog> AuthLogs { get; set; }
    
    // Nuevos DbSets para seguridad
    public DbSet<Dispositivo> Dispositivos { get; set; }
    public DbSet<LogSeguridad> LogsSeguridad { get; set; }
    public DbSet<IpBloqueada> IpsBloqueadas { get; set; }
    public DbSet<Sesion> Sesiones { get; set; }
    public DbSet<RecuperacionContrasena> RecuperacionesContrasena { get; set; }

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
            
            // Índices adicionales para performance
            entity.HasIndex(e => e.Activo);
            entity.HasIndex(e => new { e.Activo, e.FechaCreacion });
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
                  
            // Índices adicionales para performance
            entity.HasIndex(e => e.EmpresaId);
            entity.HasIndex(e => e.Rol);
            entity.HasIndex(e => new { e.EmpresaId, e.Activo });
            entity.HasIndex(e => new { e.Rol, e.Activo });
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
                  
            // Índices adicionales para performance
            entity.HasIndex(e => e.EmpresaId);
            entity.HasIndex(e => e.Estado);
            entity.HasIndex(e => e.Marca);
            entity.HasIndex(e => new { e.EmpresaId, e.Activo });
            entity.HasIndex(e => new { e.Estado, e.Activo });
            entity.HasIndex(e => new { e.EmpresaId, e.Estado, e.Activo });
            entity.HasIndex(e => e.UltimaRevision);
            entity.HasIndex(e => e.ProximaRevision);
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
                  
            // Índices adicionales para performance
            entity.HasIndex(e => e.EmpresaId);
            entity.HasIndex(e => e.Entidad);
            entity.HasIndex(e => new { e.EmpresaId, e.Entidad });
            entity.HasIndex(e => new { e.EmpresaId, e.Entidad, e.Activo });
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
                  
            // Índices adicionales para performance
            entity.HasIndex(e => e.CustomFieldId);
            entity.HasIndex(e => e.Entidad);
            entity.HasIndex(e => e.EntidadId);
            entity.HasIndex(e => new { e.Entidad, e.EntidadId });
            entity.HasIndex(e => new { e.CustomFieldId, e.Entidad, e.EntidadId });
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
                  
            // Índices adicionales para performance
            entity.HasIndex(e => e.EmpresaId);
            entity.HasIndex(e => e.Tipo);
            entity.HasIndex(e => new { e.EmpresaId, e.Tipo });
            entity.HasIndex(e => new { e.EmpresaId, e.Activa });
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
                  
            // Índices adicionales para performance
            entity.HasIndex(e => e.ChecklistPlantillaId);
            entity.HasIndex(e => new { e.ChecklistPlantillaId, e.Orden });
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
                  
            // Índices adicionales para performance
            entity.HasIndex(e => e.BusetaId);
            entity.HasIndex(e => e.InspectorId);
            entity.HasIndex(e => e.FechaInicio);
            entity.HasIndex(e => e.Estado);
            entity.HasIndex(e => new { e.BusetaId, e.FechaInicio });
            entity.HasIndex(e => new { e.InspectorId, e.FechaInicio });
            entity.HasIndex(e => new { e.Estado, e.FechaInicio });
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
                  
            // Índices adicionales para performance
            entity.HasIndex(e => e.ChecklistEjecucionId);
            entity.HasIndex(e => e.ChecklistItemPlantillaId);
            entity.HasIndex(e => e.Resultado);
            entity.HasIndex(e => e.FechaVerificacion);
            entity.HasIndex(e => new { e.ChecklistEjecucionId, e.Resultado });
        });

        // Configuración de AuthLog
        modelBuilder.Entity<AuthLog>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Tenant).IsRequired().HasMaxLength(100);
            entity.Property(e => e.UsernameAttempted).IsRequired().HasMaxLength(100);
            entity.Property(e => e.IpAddress).IsRequired().HasMaxLength(45);
            entity.Property(e => e.UserAgent).IsRequired().HasMaxLength(500);
            entity.Property(e => e.Result).IsRequired().HasMaxLength(20);
            entity.Property(e => e.Timestamp).IsRequired();
            entity.Property(e => e.Jti).HasMaxLength(100);
            
            // Índices para auditoría y análisis
            entity.HasIndex(e => e.Tenant);
            entity.HasIndex(e => e.UserId);
            entity.HasIndex(e => e.Timestamp);
            entity.HasIndex(e => e.Result);
            entity.HasIndex(e => new { e.Tenant, e.Timestamp });
            entity.HasIndex(e => new { e.Tenant, e.Result });
            entity.HasIndex(e => new { e.IpAddress, e.Timestamp });
        });

        // Configuración de Dispositivo
        modelBuilder.Entity<Dispositivo>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Nombre).IsRequired().HasMaxLength(100);
            entity.Property(e => e.Tipo).IsRequired().HasMaxLength(100);
            entity.Property(e => e.UserAgent).IsRequired().HasMaxLength(500);
            entity.Property(e => e.IpAddress).IsRequired().HasMaxLength(45);
            entity.Property(e => e.Ubicacion).HasMaxLength(100);
            entity.Property(e => e.FechaRegistro).IsRequired();
            entity.Property(e => e.UltimoAcceso).IsRequired();
            entity.Property(e => e.EsConfiable).IsRequired();
            entity.Property(e => e.Activo).IsRequired();
            
            entity.HasOne(e => e.Usuario)
                  .WithMany()
                  .HasForeignKey(e => e.UsuarioId)
                  .OnDelete(DeleteBehavior.Cascade);
                  
            // Índices para performance y consultas
            entity.HasIndex(e => e.UsuarioId);
            entity.HasIndex(e => e.IpAddress);
            entity.HasIndex(e => e.EsConfiable);
            entity.HasIndex(e => e.Activo);
            entity.HasIndex(e => e.UltimoAcceso);
            entity.HasIndex(e => new { e.UsuarioId, e.Activo });
            entity.HasIndex(e => new { e.IpAddress, e.Activo });
        });

        // Configuración de LogSeguridad
        modelBuilder.Entity<LogSeguridad>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Tenant).IsRequired().HasMaxLength(100);
            entity.Property(e => e.UsernameAttempted).IsRequired().HasMaxLength(100);
            entity.Property(e => e.IpAddress).IsRequired().HasMaxLength(45);
            entity.Property(e => e.UserAgent).IsRequired().HasMaxLength(500);
            entity.Property(e => e.TipoEvento).IsRequired().HasMaxLength(50);
            entity.Property(e => e.Resultado).IsRequired().HasMaxLength(20);
            entity.Property(e => e.Detalles).HasMaxLength(500);
            entity.Property(e => e.Ubicacion).HasMaxLength(100);
            entity.Property(e => e.Timestamp).IsRequired();
            entity.Property(e => e.Jti).HasMaxLength(100);
            
            entity.HasOne(e => e.Usuario)
                  .WithMany()
                  .HasForeignKey(e => e.UsuarioId)
                  .OnDelete(DeleteBehavior.SetNull);
                  
            // Índices para auditoría y análisis
            entity.HasIndex(e => e.Tenant);
            entity.HasIndex(e => e.UsuarioId);
            entity.HasIndex(e => e.IpAddress);
            entity.HasIndex(e => e.TipoEvento);
            entity.HasIndex(e => e.Resultado);
            entity.HasIndex(e => e.Timestamp);
            entity.HasIndex(e => new { e.Tenant, e.Timestamp });
            entity.HasIndex(e => new { e.IpAddress, e.Timestamp });
            entity.HasIndex(e => new { e.TipoEvento, e.Resultado });
        });

        // Configuración de IpBloqueada
        modelBuilder.Entity<IpBloqueada>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.IpAddress).IsRequired().HasMaxLength(45);
            entity.Property(e => e.Razon).IsRequired().HasMaxLength(100);
            entity.Property(e => e.FechaBloqueo).IsRequired();
            entity.Property(e => e.FechaExpiracion).IsRequired();
            entity.Property(e => e.Detalles).HasMaxLength(500);
            entity.Property(e => e.Activo).IsRequired();
            entity.Property(e => e.IntentosFallidos).IsRequired();
            
            // Índices para performance y consultas
            entity.HasIndex(e => e.IpAddress);
            entity.HasIndex(e => e.Activo);
            entity.HasIndex(e => e.FechaExpiracion);
            entity.HasIndex(e => new { e.IpAddress, e.Activo });
            entity.HasIndex(e => new { e.Activo, e.FechaExpiracion });
        });

        // Configuración de Sesion
        modelBuilder.Entity<Sesion>(entity =>
        {
            entity.HasKey(e => e.Id);
            entity.Property(e => e.Jti).IsRequired().HasMaxLength(100);
            entity.Property(e => e.RefreshToken).IsRequired().HasMaxLength(100);
            entity.Property(e => e.FechaCreacion).IsRequired();
            entity.Property(e => e.FechaExpiracion).IsRequired();
            entity.Property(e => e.UltimoAcceso).IsRequired();
            entity.Property(e => e.IpAddress).IsRequired().HasMaxLength(45);
            entity.Property(e => e.UserAgent).IsRequired().HasMaxLength(500);
            entity.Property(e => e.Ubicacion).HasMaxLength(100);
            entity.Property(e => e.Tipo).IsRequired().HasMaxLength(50);
            entity.Property(e => e.Activa).IsRequired();
            entity.Property(e => e.EsRecordarme).IsRequired();
            entity.Property(e => e.DispositivoId).HasMaxLength(100);
            
            entity.HasOne(e => e.Usuario)
                  .WithMany()
                  .HasForeignKey(e => e.UsuarioId)
                  .OnDelete(DeleteBehavior.Cascade);
                  
            entity.HasOne(e => e.Dispositivo)
                  .WithMany()
                  .HasForeignKey(e => e.DispositivoId)
                  .OnDelete(DeleteBehavior.SetNull);
                  
            // Índices para performance y consultas
            entity.HasIndex(e => e.UsuarioId);
            entity.HasIndex(e => e.Jti).IsUnique();
            entity.HasIndex(e => e.RefreshToken).IsUnique();
            entity.HasIndex(e => e.Activa);
            entity.HasIndex(e => e.FechaExpiracion);
            entity.HasIndex(e => e.EsRecordarme);
            entity.HasIndex(e => new { e.UsuarioId, e.Activa });
                         entity.HasIndex(e => new { e.UsuarioId, e.EsRecordarme });
             entity.HasIndex(e => new { e.Activa, e.FechaExpiracion });
         });

         // Configuración de RecuperacionContrasena
         modelBuilder.Entity<RecuperacionContrasena>(entity =>
         {
             entity.HasKey(e => e.Id);
             entity.Property(e => e.Token).IsRequired().HasMaxLength(100);
             entity.Property(e => e.CodigoRecuperacion).IsRequired().HasMaxLength(100);
             entity.Property(e => e.FechaCreacion).IsRequired();
             entity.Property(e => e.FechaExpiracion).IsRequired();
             entity.Property(e => e.FechaUso);
             entity.Property(e => e.IpAddress).IsRequired().HasMaxLength(45);
             entity.Property(e => e.UserAgent).IsRequired().HasMaxLength(500);
             entity.Property(e => e.Ubicacion).HasMaxLength(100);
             entity.Property(e => e.Tipo).IsRequired().HasMaxLength(50);
             entity.Property(e => e.Usado).IsRequired();
             entity.Property(e => e.Activo).IsRequired();
             
             entity.HasOne(e => e.Usuario)
                   .WithMany()
                   .HasForeignKey(e => e.UsuarioId)
                   .OnDelete(DeleteBehavior.Cascade);
                   
             // Índices para performance y consultas
             entity.HasIndex(e => e.UsuarioId);
             entity.HasIndex(e => e.Token).IsUnique();
             entity.HasIndex(e => e.CodigoRecuperacion);
             entity.HasIndex(e => e.Tipo);
             entity.HasIndex(e => e.Usado);
             entity.HasIndex(e => e.Activo);
             entity.HasIndex(e => e.FechaExpiracion);
             entity.HasIndex(e => new { e.UsuarioId, e.Activo });
             entity.HasIndex(e => new { e.Token, e.Activo });
             entity.HasIndex(e => new { e.CodigoRecuperacion, e.Activo });
             entity.HasIndex(e => new { e.Tipo, e.Activo });
             entity.HasIndex(e => new { e.Activo, e.FechaExpiracion });
         });
    }

    protected override void OnConfiguring(DbContextOptionsBuilder optionsBuilder)
    {
        base.OnConfiguring(optionsBuilder);
        
        // Configuraciones de performance
        optionsBuilder.UseQueryTrackingBehavior(QueryTrackingBehavior.NoTracking);
        
        // Solo habilitar logging sensible en desarrollo
        if (!optionsBuilder.IsConfigured)
        {
            optionsBuilder.EnableSensitiveDataLogging(false);
        }
    }
}
