<?php
require_once '../config/auth.php';
require_once '../config/database.php';

// Verificar autenticación y permisos
verificarSesion(['admin']);

$db = getDB();
$current = 'camaras.php';

// Filtros
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$inspector_id = intval($_GET['inspector_id'] ?? 0);
$estado_final = trim($_GET['estado_final'] ?? ''); // verde, amarillo, rojo
$buscar = trim($_GET['buscar'] ?? ''); // placa o número interno

// Inspecciones
$params = [];
$where = [];

if ($desde !== '' && $hasta !== '') {
    $where[] = "DATE(ci.fecha) BETWEEN ? AND ?";
    $params[] = $desde; $params[] = $hasta;
}
if ($inspector_id > 0) {
    $where[] = "ci.inspector_id = ?";
    $params[] = $inspector_id;
}
if ($estado_final !== '') {
    $where[] = "ci.estado_final = ?";
    $params[] = $estado_final;
}
if ($buscar !== '') {
    $where[] = "ci.vehiculo_id IN (SELECT id FROM vehiculos WHERE placa LIKE ? OR numero_interno LIKE ?)";
    $params[] = "%$buscar%"; $params[] = "%$buscar%";
}

$sql = "SELECT ci.*, v.numero_interno, v.placa, u.nombre AS inspector_nombre, u.usuario AS inspector_usuario,
        (SELECT COUNT(*) FROM camaras_evidencias e JOIN camaras_inspeccion_detalle d ON e.detalle_id = d.id WHERE d.inspeccion_id = ci.id) AS evidencias_count
        FROM camaras_inspecciones ci
        JOIN vehiculos v ON v.id = ci.vehiculo_id
        JOIN usuarios u ON u.id = ci.inspector_id";
if ($where) { $sql .= " WHERE " . implode(" AND ", $where); }

try {
    $stmt = $db->prepare($sql . " ORDER BY ci.fecha DESC");
    $stmt->execute($params);
    $inspecciones = $stmt->fetchAll();
} catch (Exception $e) {
    $inspecciones = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Administrador - Cámaras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        /* Fondo con imagen y overlay igual al Dashboard */
        html, body { height: 100%; }
        body {
            background: url('../imagendefondo.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(11, 30, 63, 0.55);
            pointer-events: none;
            z-index: 0;
        }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; }
        /* Sidebar exactamente como Dashboard */
        .sidebar {
            min-height: 100vh;
            position: sticky;
            top: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #0b1e3f 0%, #1d4ed8 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        /* Tarjeta de tabla como Dashboard */
        .table-card { background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border: none; }
        .table-card .card-header {
            background: linear-gradient(135deg, var(--brand-900) 0%, var(--brand-600) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        /* Estilos propios del módulo */
        .badge-verde { background-color: #16a34a; }
        .badge-amarillo { background-color: #f59e0b; }
        .badge-rojo { background-color: #dc2626; }
        .thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 6px; }
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
                            <a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            <a class="nav-link <?= $current === 'vehiculos.php' ? 'active' : '' ?>" href="vehiculos.php"><i class="fas fa-truck"></i> Vehículos</a>
                            <a class="nav-link <?= $current === 'conductores.php' ? 'active' : '' ?>" href="conductores.php"><i class="fas fa-users"></i> Conductores</a>
                            <a class="nav-link <?= $current === 'documentos.php' ? 'active' : '' ?>" href="documentos.php"><i class="fas fa-file-alt"></i> Documentos</a>
                            <a class="nav-link <?= $current === 'alistamientos.php' ? 'active' : '' ?>" href="alistamientos.php"><i class="fas fa-clipboard-check"></i> Alistamientos</a>
                            <a class="nav-link <?= $current === 'reportes.php' ? 'active' : '' ?>" href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a>
                            <a class="nav-link <?= $current === 'camaras.php' ? 'active' : '' ?>" href="camaras.php"><i class="fas fa-video"></i> Cámaras</a>
                            <a class="nav-link <?= $current === 'evasion.php' ? 'active' : '' ?>" href="evasion.php"><i class="fas fa-user-secret"></i> Evasión</a>
                            <hr class="text-light">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-10 px-md-4">
                <div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between align-items-center">
                    <h1 class="h2"><i class="fas fa-video"></i> Cámaras</h1>
                    <div>
                        <a class="btn btn-outline-primary" href="camaras_citaciones.php"><i class="fas fa-tools me-1"></i> Citaciones</a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form class="row g-3" method="get" action="camaras.php">
                            <div class="col-md-3">
                                <label class="form-label">Desde</label>
                                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado final</label>
                                <select name="estado_final" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="verde" <?= $estado_final==='verde'?'selected':'' ?>>Verde</option>
                                    <option value="amarillo" <?= $estado_final==='amarillo'?'selected':'' ?>>Amarillo</option>
                                    <option value="rojo" <?= $estado_final==='rojo'?'selected':'' ?>>Rojo</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Buscar vehículo (placa/interno)</label>
                                <input type="text" name="buscar" class="form-control" placeholder="ABC123 / 001" value="<?= htmlspecialchars($buscar) ?>">
                            </div>
                            <div class="col-md-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i> Filtrar</button>
                                <a class="btn btn-secondary" href="camaras.php"><i class="fas fa-undo me-1"></i> Limpiar</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de inspecciones -->
                <div class="card table-card">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-list"></i> Inspecciones de Cámaras</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Vehículo</th>
                                        <th>Inspector</th>
                                        <th>Estado</th>
                                        <th>Foto inicio</th>
                                        <th>Foto fin</th>
                                        <th>Evidencias</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($inspecciones)): ?>
                                        <tr><td colspan="8" class="text-center">No hay inspecciones para los filtros seleccionados.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($inspecciones as $row): ?>
                                            <?php
                                                $estado = strtolower($row['estado_final'] ?? 'verde');
                                                $class = $estado==='rojo' ? 'badge-rojo' : ($estado==='amarillo' ? 'badge-amarillo' : 'badge-verde');
                                                $inicio = $row['foto_inicio_url'] ? ('/' . ltrim($row['foto_inicio_url'], '/')) : '';
                                                $fin = $row['foto_fin_url'] ? ('/' . ltrim($row['foto_fin_url'], '/')) : '';
                                                $fechaFmt = date('d/m/Y H:i', strtotime($row['fecha'] ?? 'now'));
                                                $ni = $row['numero_interno'] ?? '';
                                                $placa = $row['placa'] ?? '';
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($fechaFmt) ?></td>
                                                <td><?= htmlspecialchars($ni) ?> • <?= htmlspecialchars($placa) ?></td>
                                                <td><?= htmlspecialchars(($row['inspector_nombre'] ?: $row['inspector_usuario']) ?? '') ?></td>
                                                <td><span class="badge <?= $class ?>"><?= strtoupper($estado) ?></span></td>
                                                <td>
                                                    <?php if ($inicio): ?>
                                                        <a href="<?= htmlspecialchars($inicio) ?>" target="_blank"><img class="thumb" src="<?= htmlspecialchars($inicio) ?>" alt="Inicio"></a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($fin): ?>
                                                        <a href="<?= htmlspecialchars($fin) ?>" target="_blank"><img class="thumb" src="<?= htmlspecialchars($fin) ?>" alt="Fin"></a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= (int)($row['evidencias_count'] ?? 0) ?></td>
                                                <td class="d-flex flex-wrap gap-2">
                                                    <a class="btn btn-sm btn-primary" href="camaras_detalle.php?id=<?= (int)$row['id'] ?>"><i class="fas fa-eye me-1"></i> Ver</a>
                                                    <a class="btn btn-sm btn-outline-primary" href="exportar_camaras_pdf.php?id=<?= (int)$row['id'] ?>" target="_blank"><i class="fas fa-file-pdf me-1"></i> PDF</a>
                                                    <a class="btn btn-sm btn-outline-secondary" href="exportar_camaras_pdf.php?id=<?= (int)$row['id'] ?>&modo=I" target="_blank"><i class="fas fa-print me-1"></i> Imprimir</a>
                                                    <button type="button" class="btn btn-sm btn-outline-success enviar-btn"
                                                        data-id="<?= (int)$row['id'] ?>"
                                                        data-ni="<?= htmlspecialchars($ni) ?>"
                                                        data-placa="<?= htmlspecialchars($placa) ?>"
                                                        data-fecha="<?= htmlspecialchars($fechaFmt) ?>">
                                                        <i class="fas fa-paper-plane me-1"></i> Enviar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Enviar PDF -->
    <div class="modal fade" id="modalEnviar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-envelope me-1"></i> Enviar reporte en PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEnviar">
                        <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>" />
                        <input type="hidden" name="id" id="envio_id" />
                        <div class="mb-3">
                            <label class="form-label">Destinatarios</label>
                            <input type="text" class="form-control" name="correos" id="envio_correos" placeholder="correo@empresa.com, otro@empresa.com" required />
                            <small class="text-muted">Separe múltiples correos con coma.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Asunto</label>
                            <input type="text" class="form-control" name="asunto" id="envio_asunto" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mensaje</label>
                            <textarea class="form-control" name="mensaje" id="envio_mensaje" rows="3" placeholder="Adjunto reporte de inspección de cámaras."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Enviar</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                    <div id="envio_resultado" class="mt-3" style="display:none"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Abrir modal con datos
        document.querySelectorAll('.enviar-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const ni = btn.getAttribute('data-ni');
                const placa = btn.getAttribute('data-placa');
                const fecha = btn.getAttribute('data-fecha');
                document.getElementById('envio_id').value = id;
                const asunto = `SIGAV - Reporte Cámaras ${ni} • ${placa} (${fecha})`;
                document.getElementById('envio_asunto').value = asunto;
                document.getElementById('envio_mensaje').value = 'Adjunto reporte de la inspección de cámaras.';
                const modal = new bootstrap.Modal(document.getElementById('modalEnviar'));
                modal.show();
            });
        });

        // Enviar por AJAX
        document.getElementById('formEnviar').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const data = new URLSearchParams(new FormData(form));
            const resultado = document.getElementById('envio_resultado');
            resultado.style.display = 'none';
            resultado.className = '';
            resultado.innerHTML = '';
            try {
                const resp = await fetch('enviar_camaras_pdf.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: data.toString()
                });
                const json = await resp.json();
                resultado.style.display = 'block';
                if (json.success) {
                    resultado.className = 'alert alert-success';
                    resultado.innerHTML = `<i class=\"fas fa-check-circle\"></i> ${json.message}`;
                } else {
                    resultado.className = 'alert alert-danger';
                    resultado.innerHTML = `<i class=\"fas fa-exclamation-triangle\"></i> ${json.message || 'No se pudo enviar.'}`;
                }
            } catch (err) {
                resultado.style.display = 'block';
                resultado.className = 'alert alert-danger';
                resultado.innerHTML = 'Error inesperado enviando el correo.';
            }
        });
    </script>
</body>
</html>