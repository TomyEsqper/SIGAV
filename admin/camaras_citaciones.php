<?php
require_once '../config/auth.php';
require_once '../config/database.php';

verificarSesion(['admin']);

$db = getDB();
$current = 'camaras_citaciones.php';

$estado = trim($_GET['estado'] ?? ($_POST['estado'] ?? '')); // pendiente, programada, resuelta, cancelada
$desde = trim($_GET['desde'] ?? ($_POST['desde'] ?? ''));
$hasta = trim($_GET['hasta'] ?? ($_POST['hasta'] ?? ''));
$buscar = trim($_GET['buscar'] ?? ($_POST['buscar'] ?? '')); // placa o número interno

$alert = '';
$alert_type = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $id = (int)($_POST['id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    if (!verificarTokenCSRF($token)) {
        $alert = 'Token CSRF inválido.';
        $alert_type = 'danger';
    } else if ($id <= 0) {
        $alert = 'Solicitud inválida: ID no proporcionado.';
        $alert_type = 'danger';
    } else {
        try {
            $stmtCheck = $db->prepare('SELECT fecha_programada FROM camaras_citaciones WHERE id = ?');
            $stmtCheck->execute([$id]);
            $rowDel = $stmtCheck->fetch();
            if (!$rowDel) {
                $alert = 'La citación no existe o ya fue eliminada.';
                $alert_type = 'warning';
            } else {
                $fp = $rowDel['fecha_programada'] ?? null;
                if (!$fp) {
                    $alert = 'No se puede eliminar: sin fecha y hora programada.';
                    $alert_type = 'warning';
                } else {
                    $ts = strtotime($fp);
                    if ($ts === false) {
                        $alert = 'Fecha programada inválida en la citación.';
                        $alert_type = 'warning';
                    } else if ($ts > time()) {
                        $alert = 'No se puede eliminar antes de la fecha y hora programada.';
                        $alert_type = 'warning';
                    } else {
                        $del = $db->prepare('DELETE FROM camaras_citaciones WHERE id = ?');
                        $del->execute([$id]);
                        $alert = 'Citación eliminada correctamente.';
                        $alert_type = 'success';
                        registrarActividad('Eliminar citación cámaras', 'id=' . $id);
                    }
                }
            }
        } catch (Exception $e) {
            $alert = 'Error eliminando la citación: ' . $e->getMessage();
            $alert_type = 'danger';
        }
    }
}
$params = [];
$where = [];
if ($estado !== '') { $where[] = 'c.estado_citacion = ?'; $params[] = $estado; }
if ($desde !== '' && $hasta !== '') { $where[] = 'DATE(c.creado_en) BETWEEN ? AND ?'; $params[] = $desde; $params[] = $hasta; }
if ($buscar !== '') { $where[] = "c.vehiculo_id IN (SELECT id FROM vehiculos WHERE placa LIKE ? OR numero_interno LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }

$sql = "SELECT c.*, v.numero_interno, v.placa, ci.fecha AS fecha_inspeccion
        FROM camaras_citaciones c
        JOIN vehiculos v ON v.id = c.vehiculo_id
        LEFT JOIN camaras_inspecciones ci ON ci.id = c.inspeccion_id";
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }

try {
    $stmt = $db->prepare($sql . ' ORDER BY c.creado_en DESC');
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    $rows = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Administrador - Citaciones Cámaras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { background: url('../imagendefondo.jpg') center/cover no-repeat fixed; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; position: relative; }
        body::before { content: ''; position: fixed; inset: 0; background: rgba(11,30,63,0.55); pointer-events: none; z-index: 0; }
        .container-fluid, .sidebar { position: relative; z-index: 1; }
        .sidebar { min-height: 100vh; position: sticky; top: 0; padding: 1rem; background: rgba(33,37,41,.85); color: #fff; }
        .sidebar a { color: #ddd; }
        .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: #fff; }
        .table-card .card-header { background: rgba(33,37,41,.85); color: #fff; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar">
            <div class="text-center mb-4">
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
                <a class="nav-link" href="camaras.php"><i class="fas fa-video"></i> Cámaras</a>
                <a class="nav-link <?= $current === 'camaras_citaciones.php' ? 'active' : '' ?>" href="camaras_citaciones.php"><i class="fas fa-tools"></i> Citaciones Cámaras</a>
                <a class="nav-link" href="evasion.php"><i class="fas fa-user-secret"></i> Evasión</a>
                <hr class="text-light">
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </nav>
        </div>
        <main class="col-md-10 px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between align-items-center">
                <h1 class="h2"><i class="fas fa-tools"></i> Citaciones de Cámaras</h1>
                <div>
                    <a class="btn btn-outline-light" href="camaras.php"><i class="fas fa-video me-1"></i> Ir a Inspecciones</a>
                </div>
            </div>
            <?php if (!empty($alert)): ?>
            <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($alert) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card mb-3">
                <div class="card-body">
                    <form class="row g-3" method="get" action="camaras_citaciones.php">
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="">Todos</option>
                                <?php foreach (['pendiente','programada','resuelta','cancelada'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $estado===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Desde</label>
                            <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vehículo</label>
                            <input type="text" name="buscar" class="form-control" placeholder="Placa o NI" value="<?= htmlspecialchars($buscar) ?>">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i> Filtrar</button>
                            <a class="btn btn-secondary" href="camaras_citaciones.php"><i class="fas fa-undo me-1"></i> Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card table-card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-list"></i> Citaciones</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Creada</th>
                                    <th>Vehículo</th>
                                    <th>Estado</th>
                                    <th>Motivo</th>
                                    <th>Lugar</th>
                                    <th>Fecha programada</th>
                                    <th>Inspección</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="8" class="text-center">No hay citaciones para los filtros seleccionados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <?php $fechaProg = $r['fecha_programada'] ? date('d/m/Y H:i', strtotime($r['fecha_programada'])) : '-'; ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['creado_en'] ?? 'now'))) ?></td>
                                            <td><?= htmlspecialchars(($r['numero_interno'] ?? '') . ' • ' . ($r['placa'] ?? '')) ?></td>
                                            <td><span class="badge bg-<?= $r['estado_citacion']==='resuelta'?'success':($r['estado_citacion']==='cancelada'?'secondary':($r['estado_citacion']==='programada'?'info':'warning')) ?>"><?= strtoupper($r['estado_citacion']) ?></span></td>
                                            <td><?= htmlspecialchars($r['motivo'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($r['lugar'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($fechaProg) ?></td>
                                            <td>
                                                <?php if (!empty($r['inspeccion_id'])): ?>
                                                    <a class="btn btn-sm btn-outline-primary" href="camaras_detalle.php?id=<?= (int)$r['inspeccion_id'] ?>"><i class="fas fa-eye"></i> Ver</a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="post" action="camaras_citaciones.php" class="d-inline">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                                                    <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
                                                    <input type="hidden" name="desde" value="<?= htmlspecialchars($desde) ?>">
                                                    <input type="hidden" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
                                                    <input type="hidden" name="buscar" value="<?= htmlspecialchars($buscar) ?>">
                                                    <?php $permitido = !empty($r['fecha_programada']) && strtotime($r['fecha_programada']) <= time(); ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" <?= $permitido ? '' : 'disabled' ?> title="<?= $permitido ? 'Eliminar citación' : 'Disponible después de la fecha programada' ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>