using Sigav.Domain;

namespace Sigav.Api.Services;

public interface IPasswordRecoveryService
{
    Task<bool> RequestPasswordResetAsync(string tenant, string email, string ipAddress, string userAgent, string? location = null);
    Task<bool> ValidateResetTokenAsync(string token);
    Task<bool> ValidateRecoveryCodeAsync(string code);
    Task<bool> ResetPasswordWithTokenAsync(string token, string newPassword, string ipAddress, string userAgent);
    Task<bool> ResetPasswordWithCodeAsync(string code, string newPassword, string ipAddress, string userAgent);
    Task<bool> GenerateEmergencyCodesAsync(int userId, string ipAddress, string userAgent);
    Task<List<string>> GetEmergencyCodesAsync(int userId);
    Task<bool> RevokeAllRecoveryTokensAsync(int userId);
    Task<bool> CleanupExpiredTokensAsync();
}
