<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAV - Ingreso</title>
    <link rel="icon" href="assets/icons/icon-192x192.svg">
    <link rel="prefetch" href="login.php">
    <style>
        html, body { height: 100%; margin: 0; }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%);
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }
        .splash { text-align: center; }
        .splash img {
            width: min(60vw, 360px);
            height: auto;
            display: block;
            margin: 0 auto;
            image-rendering: -webkit-optimize-contrast;
        }
        .brand { margin-top: 12px; letter-spacing: 0.5px; opacity: 0.9; }
    </style>
    <script>
      // Redirección exacta a los 4 segundos desde el DOM cargado
      document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
          window.location.replace('login.php');
        }, 4000);
      });
    </script>
</head>
<body>
    <div class="splash">
        <img id="splash" src="animacion.gif" alt="SIGAV">
        <div class="brand">SIGAV • <a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" style="color: inherit; text-decoration: none;">BLACKCROWSOFT.COM</a></div>
    </div>
</body>
</html>