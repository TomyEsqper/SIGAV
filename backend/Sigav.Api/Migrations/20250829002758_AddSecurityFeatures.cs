using System;
using Microsoft.EntityFrameworkCore.Migrations;
using Npgsql.EntityFrameworkCore.PostgreSQL.Metadata;

#nullable disable

namespace Sigav.Api.Migrations
{
    /// <inheritdoc />
    public partial class AddSecurityFeatures : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.CreateTable(
                name: "Dispositivos",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    UsuarioId = table.Column<int>(type: "integer", nullable: false),
                    Nombre = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    Tipo = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    UserAgent = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    IpAddress = table.Column<string>(type: "character varying(45)", maxLength: 45, nullable: false),
                    Ubicacion = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    FechaRegistro = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    UltimoAcceso = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    EsConfiable = table.Column<bool>(type: "boolean", nullable: false),
                    Activo = table.Column<bool>(type: "boolean", nullable: false),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_Dispositivos", x => x.Id);
                    table.ForeignKey(
                        name: "FK_Dispositivos_Usuarios_UsuarioId",
                        column: x => x.UsuarioId,
                        principalTable: "Usuarios",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Cascade);
                });

            migrationBuilder.CreateTable(
                name: "IpsBloqueadas",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    IpAddress = table.Column<string>(type: "character varying(45)", maxLength: 45, nullable: false),
                    Razon = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    FechaBloqueo = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaExpiracion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    Detalles = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false),
                    IntentosFallidos = table.Column<int>(type: "integer", nullable: false),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_IpsBloqueadas", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "LogsSeguridad",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    UsuarioId = table.Column<int>(type: "integer", nullable: false),
                    Tenant = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    UsernameAttempted = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    IpAddress = table.Column<string>(type: "character varying(45)", maxLength: 45, nullable: false),
                    UserAgent = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: false),
                    TipoEvento = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Resultado = table.Column<string>(type: "character varying(20)", maxLength: 20, nullable: false),
                    Detalles = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: true),
                    Ubicacion = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    Timestamp = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    Jti = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_LogsSeguridad", x => x.Id);
                    table.ForeignKey(
                        name: "FK_LogsSeguridad_Usuarios_UsuarioId",
                        column: x => x.UsuarioId,
                        principalTable: "Usuarios",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.SetNull);
                });

            migrationBuilder.CreateIndex(
                name: "IX_Dispositivos_Activo",
                table: "Dispositivos",
                column: "Activo");

            migrationBuilder.CreateIndex(
                name: "IX_Dispositivos_EsConfiable",
                table: "Dispositivos",
                column: "EsConfiable");

            migrationBuilder.CreateIndex(
                name: "IX_Dispositivos_IpAddress",
                table: "Dispositivos",
                column: "IpAddress");

            migrationBuilder.CreateIndex(
                name: "IX_Dispositivos_IpAddress_Activo",
                table: "Dispositivos",
                columns: new[] { "IpAddress", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_Dispositivos_UltimoAcceso",
                table: "Dispositivos",
                column: "UltimoAcceso");

            migrationBuilder.CreateIndex(
                name: "IX_Dispositivos_UsuarioId",
                table: "Dispositivos",
                column: "UsuarioId");

            migrationBuilder.CreateIndex(
                name: "IX_Dispositivos_UsuarioId_Activo",
                table: "Dispositivos",
                columns: new[] { "UsuarioId", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_IpsBloqueadas_Activo",
                table: "IpsBloqueadas",
                column: "Activo");

            migrationBuilder.CreateIndex(
                name: "IX_IpsBloqueadas_Activo_FechaExpiracion",
                table: "IpsBloqueadas",
                columns: new[] { "Activo", "FechaExpiracion" });

            migrationBuilder.CreateIndex(
                name: "IX_IpsBloqueadas_FechaExpiracion",
                table: "IpsBloqueadas",
                column: "FechaExpiracion");

            migrationBuilder.CreateIndex(
                name: "IX_IpsBloqueadas_IpAddress",
                table: "IpsBloqueadas",
                column: "IpAddress");

            migrationBuilder.CreateIndex(
                name: "IX_IpsBloqueadas_IpAddress_Activo",
                table: "IpsBloqueadas",
                columns: new[] { "IpAddress", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_LogsSeguridad_IpAddress",
                table: "LogsSeguridad",
                column: "IpAddress");

            migrationBuilder.CreateIndex(
                name: "IX_LogsSeguridad_IpAddress_Timestamp",
                table: "LogsSeguridad",
                columns: new[] { "IpAddress", "Timestamp" });

            migrationBuilder.CreateIndex(
                name: "IX_LogsSeguridad_Resultado",
                table: "LogsSeguridad",
                column: "Resultado");

            migrationBuilder.CreateIndex(
                name: "IX_LogsSeguridad_Tenant",
                table: "LogsSeguridad",
                column: "Tenant");

            migrationBuilder.CreateIndex(
                name: "IX_LogsSeguridad_Tenant_Timestamp",
                table: "LogsSeguridad",
                columns: new[] { "Tenant", "Timestamp" });

            migrationBuilder.CreateIndex(
                name: "IX_LogsSeguridad_Timestamp",
                table: "LogsSeguridad",
                column: "Timestamp");

            migrationBuilder.CreateIndex(
                name: "IX_LogsSeguridad_TipoEvento",
                table: "LogsSeguridad",
                column: "TipoEvento");

            migrationBuilder.CreateIndex(
                name: "IX_LogsSeguridad_TipoEvento_Resultado",
                table: "LogsSeguridad",
                columns: new[] { "TipoEvento", "Resultado" });

            migrationBuilder.CreateIndex(
                name: "IX_LogsSeguridad_UsuarioId",
                table: "LogsSeguridad",
                column: "UsuarioId");
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropTable(
                name: "Dispositivos");

            migrationBuilder.DropTable(
                name: "IpsBloqueadas");

            migrationBuilder.DropTable(
                name: "LogsSeguridad");
        }
    }
}
