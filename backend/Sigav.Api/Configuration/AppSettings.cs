namespace Sigav.Api.Configuration;

public class AppSettings
{
    public DatabaseSettings Database { get; set; } = new();
    public RedisSettings Redis { get; set; } = new();
    public JwtSettings Jwt { get; set; } = new();
    public LoggingSettings Logging { get; set; } = new();
}

public class DatabaseSettings
{
    public string ConnectionString { get; set; } = string.Empty;
    public int CommandTimeout { get; set; } = 30;
    public bool EnableSensitiveDataLogging { get; set; } = false;
}

public class RedisSettings
{
    public string ConnectionString { get; set; } = string.Empty;
    public int Database { get; set; } = 0;
    public int ConnectTimeout { get; set; } = 5000;
}

public class JwtSettings
{
    public string Key { get; set; } = string.Empty;
    public string Issuer { get; set; } = string.Empty;
    public string Audience { get; set; } = string.Empty;
    public int ExpirationMinutes { get; set; } = 60;
}

public class LoggingSettings
{
    public string MinimumLevel { get; set; } = "Information";
    public bool EnableConsole { get; set; } = true;
    public bool EnableFile { get; set; } = true;
    public string FilePath { get; set; } = "Logs/sigav-.log";
}
