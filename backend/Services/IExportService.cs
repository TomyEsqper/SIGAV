using Sigav.Api.DTOs;

namespace Sigav.Api.Services;

public interface IExportService
{
    Task<byte[]> ExportToCsvAsync(ExportRequest request);
    Task<byte[]> ExportToPdfAsync(ExportRequest request);
}
