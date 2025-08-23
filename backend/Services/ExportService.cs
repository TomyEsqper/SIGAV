using CsvHelper;
using Microsoft.EntityFrameworkCore;
using QuestPDF.Fluent;
using QuestPDF.Helpers;
using QuestPDF.Infrastructure;
using Sigav.Api.Data;
using Sigav.Api.DTOs;
using System.Globalization;
using System.Text;

namespace Sigav.Api.Services;

public class ExportService : IExportService
{
    private readonly SigavDbContext _context;

    public ExportService(SigavDbContext context)
    {
        _context = context;
    }

    public async Task<byte[]> ExportToCsvAsync(ExportRequest request)
    {
        var ejecuciones = await GetEjecucionesForExportAsync(request);

        using var memoryStream = new MemoryStream();
        using var writer = new StreamWriter(memoryStream, Encoding.UTF8);
        using var csv = new CsvWriter(writer, CultureInfo.InvariantCulture);

        // Headers
        csv.WriteField("ID");
        csv.WriteField("Fecha");
        csv.WriteField("Buseta");
        csv.WriteField("Plantilla");
        csv.WriteField("Inspector");
        csv.WriteField("Estado");
        csv.WriteField("Items Aprobados");
        csv.WriteField("Items Rechazados");
        csv.WriteField("Total Items");
        csv.WriteField("Observaciones Generales");
        csv.NextRecord();

        foreach (var ejecucion in ejecuciones)
        {
            var itemsAprobados = ejecucion.Resultados.Count(r => r.Aprobado);
            var itemsRechazados = ejecucion.Resultados.Count(r => !r.Aprobado);

            csv.WriteField(ejecucion.Id);
            csv.WriteField(ejecucion.FechaInicio.ToString("yyyy-MM-dd HH:mm"));
            csv.WriteField(ejecucion.PlacaBuseta);
            csv.WriteField(ejecucion.NombrePlantilla);
            csv.WriteField(ejecucion.NombreInspector);
            csv.WriteField(ejecucion.Estado);
            csv.WriteField(itemsAprobados);
            csv.WriteField(itemsRechazados);
            csv.WriteField(ejecucion.Resultados.Count);
            csv.WriteField(ejecucion.ObservacionesGenerales ?? "");
            csv.NextRecord();
        }

        await csv.FlushAsync();
        return memoryStream.ToArray();
    }

    public async Task<byte[]> ExportToPdfAsync(ExportRequest request)
    {
        var ejecuciones = await GetEjecucionesForExportAsync(request);

        var document = Document.Create(container =>
        {
            container.Page(page =>
            {
                page.Size(PageSizes.A4);
                page.Margin(2, Unit.Centimetre);
                page.DefaultTextStyle(x => x.FontSize(10));

                page.Header().Element(ComposeHeader);
                page.Content().Element(container => ComposeContent(container, ejecuciones));
                page.Footer().AlignCenter().Text(x =>
                {
                    x.CurrentPageNumber();
                    x.Span(" / ");
                    x.TotalPages();
                });
            });
        });

        return document.GeneratePdf();
    }

    private void ComposeHeader(IContainer container)
    {
        container.Row(row =>
        {
            row.RelativeItem().Column(column =>
            {
                column.Item().Text("SIGAV - Sistema de Alistamiento de Busetas").Bold().FontSize(16);
                column.Item().Text("Reporte de Historial de Checklists").FontSize(14);
                column.Item().Text($"Generado: {DateTime.Now:yyyy-MM-dd HH:mm}").FontSize(10);
            });
        });
    }

    private void ComposeContent(IContainer container, List<ChecklistEjecucionResponse> ejecuciones)
    {
        container.Column(column =>
        {
            column.Item().Text("Resumen de Ejecuciones").Bold().FontSize(14);
            column.Item().Height(0.5f, Unit.Centimetre);

            foreach (var ejecucion in ejecuciones)
            {
                column.Item().Background(Colors.Grey.Lighten3).Padding(5).Column(col =>
                {
                    col.Item().Row(row =>
                    {
                        row.RelativeItem().Text($"Buseta: {ejecucion.PlacaBuseta}").Bold();
                        row.RelativeItem().Text($"Fecha: {ejecucion.FechaInicio:yyyy-MM-dd HH:mm}").FontSize(9);
                    });
                    col.Item().Text($"Plantilla: {ejecucion.NombrePlantilla}");
                    col.Item().Text($"Inspector: {ejecucion.NombreInspector}");
                    col.Item().Text($"Estado: {ejecucion.Estado}");
                    
                    var itemsAprobados = ejecucion.Resultados.Count(r => r.Aprobado);
                    var itemsRechazados = ejecucion.Resultados.Count(r => !r.Aprobado);
                    col.Item().Text($"Resultado: {itemsAprobados} aprobados, {itemsRechazados} rechazados de {ejecucion.Resultados.Count} total");
                    
                    if (!string.IsNullOrEmpty(ejecucion.ObservacionesGenerales))
                    {
                        col.Item().Text($"Observaciones: {ejecucion.ObservacionesGenerales}").FontSize(9);
                    }
                });
                column.Item().Height(0.3f, Unit.Centimetre);
            }
        });
    }

    private async Task<List<ChecklistEjecucionResponse>> GetEjecucionesForExportAsync(ExportRequest request)
    {
        var query = _context.ChecklistEjecuciones
            .Include(e => e.Buseta)
            .Include(e => e.Plantilla)
            .Include(e => e.Inspector)
            .Include(e => e.Resultados)
            .ThenInclude(r => r.ItemPlantilla)
            .AsQueryable();

        if (request.BusetaId.HasValue)
            query = query.Where(e => e.BusetaId == request.BusetaId.Value);

        if (request.From.HasValue)
            query = query.Where(e => e.FechaInicio >= request.From.Value);

        if (request.To.HasValue)
            query = query.Where(e => e.FechaInicio <= request.To.Value);

        var ejecuciones = await query
            .OrderByDescending(e => e.FechaInicio)
            .ToListAsync();

        return ejecuciones.Select(e => new ChecklistEjecucionResponse
        {
            Id = e.Id,
            BusetaId = e.BusetaId,
            PlacaBuseta = e.Buseta.Placa,
            PlantillaId = e.PlantillaId,
            NombrePlantilla = e.Plantilla.Nombre,
            InspectorId = e.InspectorId,
            NombreInspector = $"{e.Inspector.Nombre} {e.Inspector.Apellido}",
            FechaInicio = e.FechaInicio,
            FechaCompletado = e.FechaCompletado,
            ObservacionesGenerales = e.ObservacionesGenerales,
            Estado = e.Estado.ToString(),
            Resultados = e.Resultados.Select(r => new ChecklistItemResultadoResponse
            {
                Id = r.Id,
                ItemPlantillaId = r.ChecklistItemPlantillaId,
                NombreItem = r.ItemPlantilla.Nombre,
                DescripcionItem = r.ItemPlantilla.Descripcion,
                Aprobado = r.Aprobado,
                Observacion = r.Observacion,
                FechaVerificacion = r.FechaVerificacion
            }).ToList()
        }).ToList();
    }
}
