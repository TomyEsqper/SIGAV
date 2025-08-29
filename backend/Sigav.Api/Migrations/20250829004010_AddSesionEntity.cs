using System;
using Microsoft.EntityFrameworkCore.Migrations;
using Npgsql.EntityFrameworkCore.PostgreSQL.Metadata;

#nullable disable

namespace Sigav.Api.Migrations
{
    /// <inheritdoc />
    public partial class AddSesionEntity : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.CreateTable(
                name: "Sesiones",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    UsuarioId = table.Column<int>(type: "integer", nullable: false),
                    Jti = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    RefreshToken = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaExpiracion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    UltimoAcceso = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    IpAddress = table.Column<string>(type: "character varying(45)", maxLength: 45, nullable: false),
                    UserAgent = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: false),
                    Ubicacion = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    Tipo = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Activa = table.Column<bool>(type: "boolean", nullable: false),
                    EsRecordarme = table.Column<bool>(type: "boolean", nullable: false),
                    DispositivoId = table.Column<int>(type: "integer", maxLength: 100, nullable: true),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true),
                    Activo = table.Column<bool>(type: "boolean", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_Sesiones", x => x.Id);
                    table.ForeignKey(
                        name: "FK_Sesiones_Dispositivos_DispositivoId",
                        column: x => x.DispositivoId,
                        principalTable: "Dispositivos",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.SetNull);
                    table.ForeignKey(
                        name: "FK_Sesiones_Usuarios_UsuarioId",
                        column: x => x.UsuarioId,
                        principalTable: "Usuarios",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Cascade);
                });

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_Activa",
                table: "Sesiones",
                column: "Activa");

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_Activa_FechaExpiracion",
                table: "Sesiones",
                columns: new[] { "Activa", "FechaExpiracion" });

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_DispositivoId",
                table: "Sesiones",
                column: "DispositivoId");

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_EsRecordarme",
                table: "Sesiones",
                column: "EsRecordarme");

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_FechaExpiracion",
                table: "Sesiones",
                column: "FechaExpiracion");

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_Jti",
                table: "Sesiones",
                column: "Jti",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_RefreshToken",
                table: "Sesiones",
                column: "RefreshToken",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_UsuarioId",
                table: "Sesiones",
                column: "UsuarioId");

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_UsuarioId_Activa",
                table: "Sesiones",
                columns: new[] { "UsuarioId", "Activa" });

            migrationBuilder.CreateIndex(
                name: "IX_Sesiones_UsuarioId_EsRecordarme",
                table: "Sesiones",
                columns: new[] { "UsuarioId", "EsRecordarme" });
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropTable(
                name: "Sesiones");
        }
    }
}
