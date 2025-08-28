using System.Net;
using System.Text.Json;
using Microsoft.AspNetCore.Http;
using Microsoft.Extensions.Logging;

namespace Sigav.Api.Middleware;

public class ExceptionHandlingMiddleware
{
    private readonly RequestDelegate _next;
    private readonly ILogger<ExceptionHandlingMiddleware> _logger;

    public ExceptionHandlingMiddleware(RequestDelegate next, ILogger<ExceptionHandlingMiddleware> logger)
    {
        _next = next;
        _logger = logger;
    }

    public async Task InvokeAsync(HttpContext context)
    {
        try
        {
            await _next(context);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "An unhandled exception occurred");
            await HandleExceptionAsync(context, ex);
        }
    }

    private static async Task HandleExceptionAsync(HttpContext context, Exception exception)
    {
        context.Response.ContentType = "application/json";

        var response = new
        {
            Success = false,
            Message = GetUserFriendlyMessage(exception),
            Details = GetExceptionDetails(exception)
        };

        context.Response.StatusCode = GetStatusCode(exception);

        var jsonResponse = JsonSerializer.Serialize(response, new JsonSerializerOptions
        {
            PropertyNamingPolicy = JsonNamingPolicy.CamelCase
        });

        await context.Response.WriteAsync(jsonResponse);
    }

    private static string GetUserFriendlyMessage(Exception exception)
    {
        return exception switch
        {
            InvalidOperationException => "Operación no válida",
            ArgumentException => "Parámetros inválidos",
            UnauthorizedAccessException => "Acceso no autorizado",
            _ => "Ha ocurrido un error interno del servidor"
        };
    }

    private static object GetExceptionDetails(Exception exception)
    {
        return new
        {
            Type = exception.GetType().Name,
            Message = exception.Message,
            StackTrace = exception.StackTrace
        };
    }

    private static int GetStatusCode(Exception exception)
    {
        return exception switch
        {
            InvalidOperationException => (int)HttpStatusCode.BadRequest,
            ArgumentException => (int)HttpStatusCode.BadRequest,
            UnauthorizedAccessException => (int)HttpStatusCode.Unauthorized,
            _ => (int)HttpStatusCode.InternalServerError
        };
    }
}
