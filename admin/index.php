<?php
require_once '../config/auth.php';
verificarSesion(['admin']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio Administrativo - SIGAV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link href="../assets/css/chart.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; position: relative; }
        body::before { content: ""; position: fixed; inset: 0; background: rgba(11, 30, 63, 0.55); pointer-events: none; z-index: 0; }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; }
        .sidebar { min-height: 100vh; position: sticky; top: 0; overflow: hidden; background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%); color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 10px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .stat-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border: none; transition: transform 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); }
        .footer-credits { background: white; padding: 15px; text-align: center; border-top: 1px solid #e9ecef; margin-top: 30px; border-radius: 10px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <div class="p-3">
                        <div class="text-center mb-3">
                            <img src="../logo.png" alt="SIGAV" class="sidebar-logo mb-2">
                            <h4 class="text-white">SIGAV</h4>
                            <small class="text-light"><a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-light text-decoration-none">BLACKCROWSOFT.COM</a></small>
                        </div>
                        <?php $current = basename($_SERVER['PHP_SELF']); ?>
                        <nav class="nav flex-column">
                            <a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a class="nav-link <?= $current === 'vehiculos.php' ? 'active' : '' ?>" href="vehiculos.php">
                                <i class="fas fa-truck"></i> Vehículos
                            </a>
                            <a class="nav-link <?= $current === 'conductores.php' ? 'active' : '' ?>" href="conductores.php">
                                <i class="fas fa-users"></i> Conductores
                            </a>
                            <a class="nav-link <?= $current === 'documentos.php' ? 'active' : '' ?>" href="documentos.php">
                                <i class="fas fa-file-alt"></i> Documentos
                            </a>
                            <a class="nav-link <?= $current === 'alistamientos.php' ? 'active' : '' ?>" href="alistamientos.php">
                                <i class="fas fa-clipboard-check"></i> Alistamientos
                            </a>
                            <a class="nav-link <?= $current === 'reportes.php' ? 'active' : '' ?>" href="reportes.php">
                                <i class="fas fa-chart-bar"></i> Reportes
                            </a>
                            <a class="nav-link <?= $current === 'camaras.php' ? 'active' : '' ?>" href="camaras.php">
                                <i class="fas fa-video"></i> Cámaras
                            </a>
                            <a class="nav-link <?= $current === 'evasion.php' ? 'active' : '' ?>" href="evasion.php">
                                <i class="fas fa-user-secret"></i> Evasión
                            </a>
                            <hr class="text-light">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-10 px-md-4">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-home"></i> Inicio Administrativo</h1>
                    <p class="text-muted mb-0">Seleccione una opción del menú para continuar.</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <a class="text-decoration-none" href="dashboard.php">
                            <div class="stat-card">
                                <h5 class="mb-2"><i class="fas fa-tachometer-alt me-2"></i> Ir al Dashboard</h5>
                                <p class="text-muted mb-0">Resumen de métricas y actividad.</p>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="footer-credits mt-4">
                    © 2025 <a href="http://blackcrowsoft.com/" target="_blank" rel="noopener" class="text-decoration-none">BLACKCROWSOFT.COM</a> - SIGAV
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="../assets/js/chart.min.js"></script>
</body>
</html>