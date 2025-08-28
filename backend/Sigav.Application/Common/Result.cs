namespace Sigav.Application.Common;

/// <summary>
/// Patrón Result para manejo consistente de operaciones
/// </summary>
/// <typeparam name="T">Tipo de dato de retorno</typeparam>
public class Result<T>
{
    public bool IsSuccess { get; }
    public T? Value { get; }
    public string? Error { get; }
    public List<string> ValidationErrors { get; }

    private Result(bool isSuccess, T? value, string? error = null, List<string>? validationErrors = null)
    {
        IsSuccess = isSuccess;
        Value = value;
        Error = error;
        ValidationErrors = validationErrors ?? new List<string>();
    }

    public static Result<T> Success(T value) => new(true, value);
    public static Result<T> Failure(string error) => new(false, default, error);
    public static Result<T> ValidationFailure(List<string> errors) => new(false, default, "Validation failed", errors);

    public static implicit operator Result<T>(T value) => Success(value);
}

/// <summary>
/// Result sin valor de retorno
/// </summary>
public class Result
{
    public bool IsSuccess { get; }
    public string? Error { get; }
    public List<string> ValidationErrors { get; }

    private Result(bool isSuccess, string? error = null, List<string>? validationErrors = null)
    {
        IsSuccess = isSuccess;
        Error = error;
        ValidationErrors = validationErrors ?? new List<string>();
    }

    public static Result Success() => new(true);
    public static Result Failure(string error) => new(false, error);
    public static Result ValidationFailure(List<string> errors) => new(false, "Validation failed", errors);
}

/// <summary>
/// Extensiones para Result
/// </summary>
public static class ResultExtensions
{
    public static Result<T> ToResult<T>(this T value) => Result<T>.Success(value);
    
    public static async Task<Result<T>> ToResultAsync<T>(this Task<T> task)
    {
        try
        {
            var result = await task;
            return Result<T>.Success(result);
        }
        catch (Exception ex)
        {
            return Result<T>.Failure(ex.Message);
        }
    }

    public static async Task<Result> ToResultAsync(this Task task)
    {
        try
        {
            await task;
            return Result.Success();
        }
        catch (Exception ex)
        {
            return Result.Failure(ex.Message);
        }
    }
}
