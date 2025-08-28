using System;
using Microsoft.EntityFrameworkCore.Migrations;
using Npgsql.EntityFrameworkCore.PostgreSQL.Metadata;

#nullable disable

namespace Sigav.Api.Migrations
{
    /// <inheritdoc />
    public partial class InitialCreate : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.CreateTable(
                name: "AuthLogs",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    Tenant = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    UserId = table.Column<int>(type: "integer", nullable: true),
                    UsernameAttempted = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    IpAddress = table.Column<string>(type: "character varying(45)", maxLength: 45, nullable: false),
                    UserAgent = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: false),
                    Result = table.Column<string>(type: "character varying(20)", maxLength: 20, nullable: false),
                    Timestamp = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    Jti = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_AuthLogs", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "Empresas",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    Nombre = table.Column<string>(type: "character varying(200)", maxLength: 200, nullable: false),
                    Descripcion = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: true),
                    Nit = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    Direccion = table.Column<string>(type: "character varying(200)", maxLength: 200, nullable: true),
                    Telefono = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    Email = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    SitioWeb = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    LogoUrl = table.Column<string>(type: "text", nullable: true),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_Empresas", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "Busetas",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    Placa = table.Column<string>(type: "character varying(20)", maxLength: 20, nullable: false),
                    Marca = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    Modelo = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    Ano = table.Column<int>(type: "integer", nullable: false),
                    Capacidad = table.Column<int>(type: "integer", nullable: false),
                    Color = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    NumeroMotor = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    NumeroChasis = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    Estado = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Combustible = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    ConsumoPromedio = table.Column<decimal>(type: "numeric", nullable: true),
                    UltimaRevision = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    ProximaRevision = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Kilometraje = table.Column<int>(type: "integer", nullable: true),
                    Observaciones = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: true),
                    EmpresaId = table.Column<int>(type: "integer", nullable: false),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_Busetas", x => x.Id);
                    table.ForeignKey(
                        name: "FK_Busetas_Empresas_EmpresaId",
                        column: x => x.EmpresaId,
                        principalTable: "Empresas",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Restrict);
                });

            migrationBuilder.CreateTable(
                name: "ChecklistPlantillas",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    Nombre = table.Column<string>(type: "character varying(200)", maxLength: 200, nullable: false),
                    Descripcion = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: true),
                    Tipo = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Activa = table.Column<bool>(type: "boolean", nullable: false),
                    TiempoEstimado = table.Column<int>(type: "integer", nullable: true),
                    EmpresaId = table.Column<int>(type: "integer", nullable: false),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_ChecklistPlantillas", x => x.Id);
                    table.ForeignKey(
                        name: "FK_ChecklistPlantillas_Empresas_EmpresaId",
                        column: x => x.EmpresaId,
                        principalTable: "Empresas",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Restrict);
                });

            migrationBuilder.CreateTable(
                name: "CustomFields",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    Nombre = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    Descripcion = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: true),
                    Tipo = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Opciones = table.Column<string>(type: "character varying(1000)", maxLength: 1000, nullable: true),
                    Entidad = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Requerido = table.Column<bool>(type: "boolean", nullable: false),
                    Orden = table.Column<int>(type: "integer", nullable: false),
                    EmpresaId = table.Column<int>(type: "integer", nullable: false),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_CustomFields", x => x.Id);
                    table.ForeignKey(
                        name: "FK_CustomFields_Empresas_EmpresaId",
                        column: x => x.EmpresaId,
                        principalTable: "Empresas",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Cascade);
                });

            migrationBuilder.CreateTable(
                name: "Usuarios",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    Nombre = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    Apellido = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    Email = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    PasswordHash = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    Telefono = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    Documento = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    TipoDocumento = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: true),
                    Cargo = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    Departamento = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    FechaNacimiento = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    FechaContratacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Salario = table.Column<decimal>(type: "numeric", nullable: true),
                    Rol = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    EmpresaId = table.Column<int>(type: "integer", nullable: false),
                    FailedAttempts = table.Column<int>(type: "integer", nullable: false),
                    LockedUntil = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    LastLoginAt = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_Usuarios", x => x.Id);
                    table.ForeignKey(
                        name: "FK_Usuarios_Empresas_EmpresaId",
                        column: x => x.EmpresaId,
                        principalTable: "Empresas",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Restrict);
                });

            migrationBuilder.CreateTable(
                name: "ChecklistItemPlantillas",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    Nombre = table.Column<string>(type: "character varying(200)", maxLength: 200, nullable: false),
                    Descripcion = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: true),
                    Orden = table.Column<int>(type: "integer", nullable: false),
                    Tipo = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Opciones = table.Column<string>(type: "character varying(1000)", maxLength: 1000, nullable: true),
                    Obligatorio = table.Column<bool>(type: "boolean", nullable: false),
                    PermiteObservacion = table.Column<bool>(type: "boolean", nullable: false),
                    Categoria = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    ChecklistPlantillaId = table.Column<int>(type: "integer", nullable: false),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_ChecklistItemPlantillas", x => x.Id);
                    table.ForeignKey(
                        name: "FK_ChecklistItemPlantillas_ChecklistPlantillas_ChecklistPlanti~",
                        column: x => x.ChecklistPlantillaId,
                        principalTable: "ChecklistPlantillas",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Cascade);
                });

            migrationBuilder.CreateTable(
                name: "ChecklistEjecuciones",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    BusetaId = table.Column<int>(type: "integer", nullable: false),
                    ChecklistPlantillaId = table.Column<int>(type: "integer", nullable: false),
                    InspectorId = table.Column<int>(type: "integer", nullable: false),
                    FechaInicio = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaFin = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    ObservacionesGenerales = table.Column<string>(type: "character varying(1000)", maxLength: 1000, nullable: true),
                    Estado = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Completado = table.Column<bool>(type: "boolean", nullable: false),
                    TiempoTotal = table.Column<int>(type: "integer", nullable: true),
                    Ubicacion = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    CondicionesClimaticas = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_ChecklistEjecuciones", x => x.Id);
                    table.ForeignKey(
                        name: "FK_ChecklistEjecuciones_Busetas_BusetaId",
                        column: x => x.BusetaId,
                        principalTable: "Busetas",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Restrict);
                    table.ForeignKey(
                        name: "FK_ChecklistEjecuciones_ChecklistPlantillas_ChecklistPlantilla~",
                        column: x => x.ChecklistPlantillaId,
                        principalTable: "ChecklistPlantillas",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Restrict);
                    table.ForeignKey(
                        name: "FK_ChecklistEjecuciones_Usuarios_InspectorId",
                        column: x => x.InspectorId,
                        principalTable: "Usuarios",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Restrict);
                });

            migrationBuilder.CreateTable(
                name: "ChecklistItemResultados",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    ChecklistEjecucionId = table.Column<int>(type: "integer", nullable: false),
                    ChecklistItemPlantillaId = table.Column<int>(type: "integer", nullable: false),
                    Resultado = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Observacion = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: true),
                    FechaVerificacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    Valor = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    Aprobado = table.Column<bool>(type: "boolean", nullable: true),
                    Evidencia = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_ChecklistItemResultados", x => x.Id);
                    table.ForeignKey(
                        name: "FK_ChecklistItemResultados_ChecklistEjecuciones_ChecklistEjecu~",
                        column: x => x.ChecklistEjecucionId,
                        principalTable: "ChecklistEjecuciones",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Cascade);
                    table.ForeignKey(
                        name: "FK_ChecklistItemResultados_ChecklistItemPlantillas_ChecklistIt~",
                        column: x => x.ChecklistItemPlantillaId,
                        principalTable: "ChecklistItemPlantillas",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Restrict);
                });

            migrationBuilder.CreateTable(
                name: "CustomFieldValues",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    CustomFieldId = table.Column<int>(type: "integer", nullable: false),
                    Entidad = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    EntidadId = table.Column<int>(type: "integer", nullable: false),
                    Valor = table.Column<string>(type: "character varying(1000)", maxLength: 1000, nullable: false),
                    BusetaId = table.Column<int>(type: "integer", nullable: true),
                    ChecklistEjecucionId = table.Column<int>(type: "integer", nullable: true),
                    ChecklistPlantillaId = table.Column<int>(type: "integer", nullable: true),
                    UsuarioId = table.Column<int>(type: "integer", nullable: true),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_CustomFieldValues", x => x.Id);
                    table.ForeignKey(
                        name: "FK_CustomFieldValues_Busetas_BusetaId",
                        column: x => x.BusetaId,
                        principalTable: "Busetas",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_CustomFieldValues_ChecklistEjecuciones_ChecklistEjecucionId",
                        column: x => x.ChecklistEjecucionId,
                        principalTable: "ChecklistEjecuciones",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_CustomFieldValues_ChecklistPlantillas_ChecklistPlantillaId",
                        column: x => x.ChecklistPlantillaId,
                        principalTable: "ChecklistPlantillas",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_CustomFieldValues_CustomFields_CustomFieldId",
                        column: x => x.CustomFieldId,
                        principalTable: "CustomFields",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Cascade);
                    table.ForeignKey(
                        name: "FK_CustomFieldValues_Usuarios_UsuarioId",
                        column: x => x.UsuarioId,
                        principalTable: "Usuarios",
                        principalColumn: "Id");
                });

            migrationBuilder.CreateIndex(
                name: "IX_AuthLogs_IpAddress_Timestamp",
                table: "AuthLogs",
                columns: new[] { "IpAddress", "Timestamp" });

            migrationBuilder.CreateIndex(
                name: "IX_AuthLogs_Result",
                table: "AuthLogs",
                column: "Result");

            migrationBuilder.CreateIndex(
                name: "IX_AuthLogs_Tenant",
                table: "AuthLogs",
                column: "Tenant");

            migrationBuilder.CreateIndex(
                name: "IX_AuthLogs_Tenant_Result",
                table: "AuthLogs",
                columns: new[] { "Tenant", "Result" });

            migrationBuilder.CreateIndex(
                name: "IX_AuthLogs_Tenant_Timestamp",
                table: "AuthLogs",
                columns: new[] { "Tenant", "Timestamp" });

            migrationBuilder.CreateIndex(
                name: "IX_AuthLogs_Timestamp",
                table: "AuthLogs",
                column: "Timestamp");

            migrationBuilder.CreateIndex(
                name: "IX_AuthLogs_UserId",
                table: "AuthLogs",
                column: "UserId");

            migrationBuilder.CreateIndex(
                name: "IX_Busetas_EmpresaId",
                table: "Busetas",
                column: "EmpresaId");

            migrationBuilder.CreateIndex(
                name: "IX_Busetas_EmpresaId_Activo",
                table: "Busetas",
                columns: new[] { "EmpresaId", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_Busetas_EmpresaId_Estado_Activo",
                table: "Busetas",
                columns: new[] { "EmpresaId", "Estado", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_Busetas_Estado",
                table: "Busetas",
                column: "Estado");

            migrationBuilder.CreateIndex(
                name: "IX_Busetas_Estado_Activo",
                table: "Busetas",
                columns: new[] { "Estado", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_Busetas_Marca",
                table: "Busetas",
                column: "Marca");

            migrationBuilder.CreateIndex(
                name: "IX_Busetas_Placa",
                table: "Busetas",
                column: "Placa",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_Busetas_ProximaRevision",
                table: "Busetas",
                column: "ProximaRevision");

            migrationBuilder.CreateIndex(
                name: "IX_Busetas_UltimaRevision",
                table: "Busetas",
                column: "UltimaRevision");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistEjecuciones_BusetaId",
                table: "ChecklistEjecuciones",
                column: "BusetaId");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistEjecuciones_BusetaId_FechaInicio",
                table: "ChecklistEjecuciones",
                columns: new[] { "BusetaId", "FechaInicio" });

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistEjecuciones_ChecklistPlantillaId",
                table: "ChecklistEjecuciones",
                column: "ChecklistPlantillaId");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistEjecuciones_Estado",
                table: "ChecklistEjecuciones",
                column: "Estado");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistEjecuciones_Estado_FechaInicio",
                table: "ChecklistEjecuciones",
                columns: new[] { "Estado", "FechaInicio" });

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistEjecuciones_FechaInicio",
                table: "ChecklistEjecuciones",
                column: "FechaInicio");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistEjecuciones_InspectorId",
                table: "ChecklistEjecuciones",
                column: "InspectorId");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistEjecuciones_InspectorId_FechaInicio",
                table: "ChecklistEjecuciones",
                columns: new[] { "InspectorId", "FechaInicio" });

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistItemPlantillas_ChecklistPlantillaId",
                table: "ChecklistItemPlantillas",
                column: "ChecklistPlantillaId");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistItemPlantillas_ChecklistPlantillaId_Orden",
                table: "ChecklistItemPlantillas",
                columns: new[] { "ChecklistPlantillaId", "Orden" });

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistItemResultados_ChecklistEjecucionId",
                table: "ChecklistItemResultados",
                column: "ChecklistEjecucionId");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistItemResultados_ChecklistEjecucionId_Resultado",
                table: "ChecklistItemResultados",
                columns: new[] { "ChecklistEjecucionId", "Resultado" });

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistItemResultados_ChecklistItemPlantillaId",
                table: "ChecklistItemResultados",
                column: "ChecklistItemPlantillaId");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistItemResultados_FechaVerificacion",
                table: "ChecklistItemResultados",
                column: "FechaVerificacion");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistItemResultados_Resultado",
                table: "ChecklistItemResultados",
                column: "Resultado");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistPlantillas_EmpresaId",
                table: "ChecklistPlantillas",
                column: "EmpresaId");

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistPlantillas_EmpresaId_Activa",
                table: "ChecklistPlantillas",
                columns: new[] { "EmpresaId", "Activa" });

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistPlantillas_EmpresaId_Tipo",
                table: "ChecklistPlantillas",
                columns: new[] { "EmpresaId", "Tipo" });

            migrationBuilder.CreateIndex(
                name: "IX_ChecklistPlantillas_Tipo",
                table: "ChecklistPlantillas",
                column: "Tipo");

            migrationBuilder.CreateIndex(
                name: "IX_CustomFields_EmpresaId",
                table: "CustomFields",
                column: "EmpresaId");

            migrationBuilder.CreateIndex(
                name: "IX_CustomFields_EmpresaId_Entidad",
                table: "CustomFields",
                columns: new[] { "EmpresaId", "Entidad" });

            migrationBuilder.CreateIndex(
                name: "IX_CustomFields_EmpresaId_Entidad_Activo",
                table: "CustomFields",
                columns: new[] { "EmpresaId", "Entidad", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_CustomFields_Entidad",
                table: "CustomFields",
                column: "Entidad");

            migrationBuilder.CreateIndex(
                name: "IX_CustomFieldValues_BusetaId",
                table: "CustomFieldValues",
                column: "BusetaId");

            migrationBuilder.CreateIndex(
                name: "IX_CustomFieldValues_ChecklistEjecucionId",
                table: "CustomFieldValues",
                column: "ChecklistEjecucionId");

            migrationBuilder.CreateIndex(
                name: "IX_CustomFieldValues_ChecklistPlantillaId",
                table: "CustomFieldValues",
                column: "ChecklistPlantillaId");

            migrationBuilder.CreateIndex(
                name: "IX_CustomFieldValues_CustomFieldId",
                table: "CustomFieldValues",
                column: "CustomFieldId");

            migrationBuilder.CreateIndex(
                name: "IX_CustomFieldValues_CustomFieldId_Entidad_EntidadId",
                table: "CustomFieldValues",
                columns: new[] { "CustomFieldId", "Entidad", "EntidadId" });

            migrationBuilder.CreateIndex(
                name: "IX_CustomFieldValues_Entidad",
                table: "CustomFieldValues",
                column: "Entidad");

            migrationBuilder.CreateIndex(
                name: "IX_CustomFieldValues_Entidad_EntidadId",
                table: "CustomFieldValues",
                columns: new[] { "Entidad", "EntidadId" });

            migrationBuilder.CreateIndex(
                name: "IX_CustomFieldValues_EntidadId",
                table: "CustomFieldValues",
                column: "EntidadId");

            migrationBuilder.CreateIndex(
                name: "IX_CustomFieldValues_UsuarioId",
                table: "CustomFieldValues",
                column: "UsuarioId");

            migrationBuilder.CreateIndex(
                name: "IX_Empresas_Activo",
                table: "Empresas",
                column: "Activo");

            migrationBuilder.CreateIndex(
                name: "IX_Empresas_Activo_FechaCreacion",
                table: "Empresas",
                columns: new[] { "Activo", "FechaCreacion" });

            migrationBuilder.CreateIndex(
                name: "IX_Empresas_Nit",
                table: "Empresas",
                column: "Nit",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_Usuarios_Email",
                table: "Usuarios",
                column: "Email",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_Usuarios_EmpresaId",
                table: "Usuarios",
                column: "EmpresaId");

            migrationBuilder.CreateIndex(
                name: "IX_Usuarios_EmpresaId_Activo",
                table: "Usuarios",
                columns: new[] { "EmpresaId", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_Usuarios_Rol",
                table: "Usuarios",
                column: "Rol");

            migrationBuilder.CreateIndex(
                name: "IX_Usuarios_Rol_Activo",
                table: "Usuarios",
                columns: new[] { "Rol", "Activo" });
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropTable(
                name: "AuthLogs");

            migrationBuilder.DropTable(
                name: "ChecklistItemResultados");

            migrationBuilder.DropTable(
                name: "CustomFieldValues");

            migrationBuilder.DropTable(
                name: "ChecklistItemPlantillas");

            migrationBuilder.DropTable(
                name: "ChecklistEjecuciones");

            migrationBuilder.DropTable(
                name: "CustomFields");

            migrationBuilder.DropTable(
                name: "Busetas");

            migrationBuilder.DropTable(
                name: "ChecklistPlantillas");

            migrationBuilder.DropTable(
                name: "Usuarios");

            migrationBuilder.DropTable(
                name: "Empresas");
        }
    }
}
