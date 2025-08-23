using System.ComponentModel.DataAnnotations;

namespace Sigav.Api.Domain;

// Enums
public enum EstadoBuseta
{
    Disponible = 1,
    EnMantenimiento = 2,
    EnRuta = 3
}

public enum RolUsuario
{
    Admin = 1,
    Inspector = 2,
    Mecanico = 3
}

// Entidades principales
public class Usuario
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string Email { get; set; } = string.Empty;
    public string PasswordHash { get; set; } = string.Empty;
    public string Nombre { get; set; } = string.Empty;
    public string Apellido { get; set; } = string.Empty;
    public RolUsuario Rol { get; set; }
    public bool Activo { get; set; } = true;
    public DateTime CreatedAtUtc { get; set; } = DateTime.UtcNow;
    public DateTime? LastLoginAtUtc { get; set; }

    // Navigation properties
    public virtual ICollection<ChecklistEjecucion> Ejecuciones { get; set; } = new List<ChecklistEjecucion>();
}

public class Buseta
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string Placa { get; set; } = string.Empty;
    public string Modelo { get; set; } = string.Empty;
    public int Capacidad { get; set; }
    public string Agencia { get; set; } = string.Empty;
    public EstadoBuseta Estado { get; set; } = EstadoBuseta.Disponible;
    public DateTime CreatedAtUtc { get; set; } = DateTime.UtcNow;
    public DateTime? UpdatedAtUtc { get; set; }

    // Navigation properties
    public virtual ICollection<ChecklistEjecucion> Ejecuciones { get; set; } = new List<ChecklistEjecucion>();
}

public class ChecklistPlantilla
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string Nombre { get; set; } = string.Empty;
    public string Descripcion { get; set; } = string.Empty;
    public bool Activa { get; set; } = true;
    public DateTime CreatedAtUtc { get; set; } = DateTime.UtcNow;
    public DateTime? UpdatedAtUtc { get; set; }

    // Navigation properties
    public virtual ICollection<ChecklistItemPlantilla> Items { get; set; } = new List<ChecklistItemPlantilla>();
    public virtual ICollection<ChecklistEjecucion> Ejecuciones { get; set; } = new List<ChecklistEjecucion>();
}

public class ChecklistItemPlantilla
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid ChecklistPlantillaId { get; set; }
    public string Nombre { get; set; } = string.Empty;
    public string Descripcion { get; set; } = string.Empty;
    public int Orden { get; set; }
    public bool RequiereObservacion { get; set; } = true;
    public DateTime CreatedAtUtc { get; set; } = DateTime.UtcNow;

    // Navigation properties
    public virtual ChecklistPlantilla Plantilla { get; set; } = null!;
    public virtual ICollection<ChecklistItemResultado> Resultados { get; set; } = new List<ChecklistItemResultado>();
}

public class ChecklistEjecucion
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid BusetaId { get; set; }
    public Guid PlantillaId { get; set; }
    public Guid InspectorId { get; set; }
    public DateTime FechaInicio { get; set; } = DateTime.UtcNow;
    public DateTime? FechaCompletado { get; set; }
    public string? ObservacionesGenerales { get; set; }
    public bool Completado { get; set; } = false;
    public DateTime CreatedAtUtc { get; set; } = DateTime.UtcNow;

    // Navigation properties
    public virtual Buseta Buseta { get; set; } = null!;
    public virtual ChecklistPlantilla Plantilla { get; set; } = null!;
    public virtual Usuario Inspector { get; set; } = null!;
    public virtual ICollection<ChecklistItemResultado> Resultados { get; set; } = new List<ChecklistItemResultado>();
}

public class ChecklistItemResultado
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid EjecucionId { get; set; }
    public Guid ItemPlantillaId { get; set; }
    public bool Aprobado { get; set; }
    public string? Observacion { get; set; }
    public DateTime CreatedAtUtc { get; set; } = DateTime.UtcNow;

    // Navigation properties
    public virtual ChecklistEjecucion Ejecucion { get; set; } = null!;
    public virtual ChecklistItemPlantilla ItemPlantilla { get; set; } = null!;
}


