using System;
using Microsoft.EntityFrameworkCore.Migrations;
using Npgsql.EntityFrameworkCore.PostgreSQL.Metadata;

#nullable disable

namespace Sigav.Api.Migrations
{
    /// <inheritdoc />
    public partial class AddPasswordRecovery : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.CreateTable(
                name: "RecuperacionesContrasena",
                columns: table => new
                {
                    Id = table.Column<int>(type: "integer", nullable: false)
                        .Annotation("Npgsql:ValueGenerationStrategy", NpgsqlValueGenerationStrategy.IdentityByDefaultColumn),
                    UsuarioId = table.Column<int>(type: "integer", nullable: false),
                    Token = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    CodigoRecuperacion = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: false),
                    FechaCreacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaExpiracion = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    FechaUso = table.Column<DateTime>(type: "timestamp with time zone", nullable: false),
                    IpAddress = table.Column<string>(type: "character varying(45)", maxLength: 45, nullable: false),
                    UserAgent = table.Column<string>(type: "character varying(500)", maxLength: 500, nullable: false),
                    Ubicacion = table.Column<string>(type: "character varying(100)", maxLength: 100, nullable: true),
                    Tipo = table.Column<string>(type: "character varying(50)", maxLength: 50, nullable: false),
                    Usado = table.Column<bool>(type: "boolean", nullable: false),
                    Activo = table.Column<bool>(type: "boolean", nullable: false),
                    FechaActualizacion = table.Column<DateTime>(type: "timestamp with time zone", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_RecuperacionesContrasena", x => x.Id);
                    table.ForeignKey(
                        name: "FK_RecuperacionesContrasena_Usuarios_UsuarioId",
                        column: x => x.UsuarioId,
                        principalTable: "Usuarios",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Cascade);
                });

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_Activo",
                table: "RecuperacionesContrasena",
                column: "Activo");

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_Activo_FechaExpiracion",
                table: "RecuperacionesContrasena",
                columns: new[] { "Activo", "FechaExpiracion" });

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_CodigoRecuperacion",
                table: "RecuperacionesContrasena",
                column: "CodigoRecuperacion");

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_CodigoRecuperacion_Activo",
                table: "RecuperacionesContrasena",
                columns: new[] { "CodigoRecuperacion", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_FechaExpiracion",
                table: "RecuperacionesContrasena",
                column: "FechaExpiracion");

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_Tipo",
                table: "RecuperacionesContrasena",
                column: "Tipo");

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_Tipo_Activo",
                table: "RecuperacionesContrasena",
                columns: new[] { "Tipo", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_Token",
                table: "RecuperacionesContrasena",
                column: "Token",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_Token_Activo",
                table: "RecuperacionesContrasena",
                columns: new[] { "Token", "Activo" });

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_Usado",
                table: "RecuperacionesContrasena",
                column: "Usado");

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_UsuarioId",
                table: "RecuperacionesContrasena",
                column: "UsuarioId");

            migrationBuilder.CreateIndex(
                name: "IX_RecuperacionesContrasena_UsuarioId_Activo",
                table: "RecuperacionesContrasena",
                columns: new[] { "UsuarioId", "Activo" });
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropTable(
                name: "RecuperacionesContrasena");
        }
    }
}
