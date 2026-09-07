<?php
/**
 * Vista: Gestión de Backups
 * Variables disponibles: $daily, $monthly, $critical, $retention, $titulo
 */

$puedeGestionar = isset($_SESSION['privilegios']) && in_array('gestionar_backups', $_SESSION['privilegios']);

$tipos = [
    'daily'    => ['label' => 'Respaldo Diario',    'icon' => 'bi-calendar-day',   'color' => '#1565c0', 'bg' => '#e3f2fd', 'border' => '#90caf9', 'freq' => 'Mínimo 1 vez cada 24 horas',    'retencion' => '30 días',     'items' => $daily],
    'monthly'  => ['label' => 'Respaldo Mensual',   'icon' => 'bi-calendar-month', 'color' => '#6a1b9a', 'bg' => '#f3e5f5', 'border' => '#ce93d8', 'freq' => '1 vez al mes (cierre de mes)',   'retencion' => '12 meses',    'items' => $monthly],
    'critical' => ['label' => 'Respaldo Crítico',   'icon' => 'bi-shield-fill-exclamation', 'color' => '#b71c1c', 'bg' => '#ffebee', 'border' => '#ef9a9a', 'freq' => 'Cada 6 a 12 horas', 'retencion' => '30 días',     'items' => $critical],
];
?>

<div class="container-fluid px-0">

    <!-- Encabezado -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1a237e;">
                <i class="bi bi-database-fill-down me-2"></i>Gestión de Backups
            </h4>
            <small class="text-muted">Política de respaldo BASC — base de datos <strong><?= htmlspecialchars($GLOBALS['currentController'] ?? 'lavoro') ?></strong></small>
        </div>
        <div class="text-muted small border rounded px-3 py-2 bg-white" style="font-size:0.82rem;">
            <i class="bi bi-info-circle me-1 text-primary"></i>
            Los backups se generan con exportación PHP segura y se almacenan cifrados en el servidor.<br>
            Acceso de descarga solo para usuarios con privilegio <code>ver_backups</code>.
        </div>
    </div>

    <!-- Mensaje flash -->
    <?php if (!empty($_SESSION['backup_msg'])): ?>
        <?php $msg = $_SESSION['backup_msg']; unset($_SESSION['backup_msg']); ?>
        <div class="alert alert-<?= $msg['tipo'] ?> alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-<?= $msg['tipo'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $msg['texto'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Instrucciones Cron (solo para gestores) -->
    <?php if ($puedeGestionar): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #37474f !important;">
        <div class="card-header d-flex align-items-center justify-content-between py-2 px-3"
             style="background:#eceff1; cursor:pointer; border-bottom:1px solid #cfd8dc;"
             onclick="this.nextElementSibling.classList.toggle('d-none')">
            <span class="fw-semibold small" style="color:#37474f;">
                <i class="bi bi-terminal me-2"></i>Configuración de Cron Jobs (automatización)
            </span>
            <i class="bi bi-chevron-down small text-muted"></i>
        </div>
        <div class="card-body d-none py-3 px-4" style="font-size:0.85rem;">
            <p class="mb-2 text-muted">En cPanel del hosting, añade estas 3 líneas en <strong>Cron Jobs</strong>:</p>
            <div class="bg-dark text-white rounded p-3 font-monospace mb-3" style="font-size:0.82rem; line-height:1.8;">
                <?php $token = defined('BACKUP_CRON_TOKEN') ? BACKUP_CRON_TOKEN : 'TOKEN_NO_GENERADO'; ?>
                <?php $base  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'tudominio.com'); ?>
                <div># Respaldo diario — 2:00 AM todos los días</div>
                <div class="text-success">0 2 * * * curl -s "<?= $base ?>/backup/cron?tipo=daily&amp;token=<?= htmlspecialchars($token) ?>"</div>
                <br>
                <div># Respaldo mensual — 3:00 AM el día 1 de cada mes</div>
                <div class="text-success">0 3 1 * * curl -s "<?= $base ?>/backup/cron?tipo=monthly&amp;token=<?= htmlspecialchars($token) ?>"</div>
                <br>
                <div># Respaldo crítico — cada 6 horas</div>
                <div class="text-success">0 */6 * * * curl -s "<?= $base ?>/backup/cron?tipo=critical&amp;token=<?= htmlspecialchars($token) ?>"</div>
            </div>
            <div class="alert alert-warning py-2 px-3 mb-0 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                El token está en <code>config/hosting.php</code>. Cámbialo si crees que fue expuesto.
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Paneles por tipo -->
    <?php foreach ($tipos as $clave => $info): ?>
    <?php
        $items     = $info['items'];
        $total     = count($items);
        $maxFiles  = $retention[$clave];
        $pct       = $maxFiles > 0 ? min(100, round($total / $maxFiles * 100)) : 0;
        $pctColor  = $pct >= 90 ? '#c62828' : ($pct >= 70 ? '#e65100' : $info['color']);
    ?>
    <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid <?= $info['border'] ?> !important;">
        <!-- Header del panel -->
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 py-3 px-4"
             style="background:<?= $info['bg'] ?>; border-bottom:1px solid <?= $info['border'] ?>;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width:42px; height:42px; background:<?= $info['color'] ?>20; border:2px solid <?= $info['border'] ?>;">
                    <i class="bi <?= $info['icon'] ?>" style="color:<?= $info['color'] ?>; font-size:1.2rem;"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color:<?= $info['color'] ?>; font-size:1rem;"><?= $info['label'] ?></div>
                    <div class="text-muted" style="font-size:0.8rem;">
                        <i class="bi bi-clock me-1"></i><?= $info['freq'] ?> &nbsp;|&nbsp;
                        <i class="bi bi-archive me-1"></i>Retención: <?= $info['retencion'] ?>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Badge de ocupación -->
                <div class="text-center">
                    <div class="fw-bold" style="color:<?= $pctColor ?>; font-size:1.1rem;"><?= $total ?> / <?= $maxFiles ?></div>
                    <div style="width:80px; height:6px; background:#e0e0e0; border-radius:3px; overflow:hidden;">
                        <div style="width:<?= $pct ?>%; height:100%; background:<?= $pctColor ?>; border-radius:3px;"></div>
                    </div>
                    <div class="text-muted" style="font-size:0.75rem;">archivos</div>
                </div>
                <!-- Botón crear -->
                <?php if ($puedeGestionar): ?>
                <a href="<?= app_url('backup/crear?tipo=' . $clave) ?>"
                   class="btn btn-sm fw-semibold"
                   style="background:<?= $info['color'] ?>; color:#fff; border:none;"
                   onclick="return confirm('¿Crear backup <?= $info['label'] ?> ahora?')">
                    <i class="bi bi-plus-circle me-1"></i>Crear ahora
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla de archivos -->
        <div class="card-body p-0">
            <?php if (empty($items)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:0.5rem;"></i>
                No hay backups de este tipo todavía.
                <?php if ($puedeGestionar): ?>
                <br><a href="<?= app_url('backup/crear?tipo=' . $clave) ?>" class="text-decoration-none small mt-2 d-inline-block"
                       onclick="return confirm('¿Crear backup <?= $info['label'] ?> ahora?')">
                    Crear el primero →
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.87rem;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th class="px-4 py-2">#</th>
                            <th class="px-3 py-2">Archivo</th>
                            <th class="px-3 py-2">Fecha</th>
                            <th class="px-3 py-2">Hora</th>
                            <th class="px-3 py-2">Tamaño</th>
                            <th class="px-3 py-2 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $f): ?>
                        <tr>
                            <td class="px-4 text-muted"><?= $i + 1 ?></td>
                            <td class="px-3">
                                <code style="font-size:0.82rem; background:#f1f3f9; padding:2px 6px; border-radius:4px; color:#1a237e;">
                                    <?= htmlspecialchars($f['filename']) ?>
                                </code>
                                <?php if ($i === 0): ?>
                                    <span class="badge ms-1" style="background:#e8f5e9; color:#2e7d32; font-size:0.7rem;">Más reciente</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 text-nowrap"><?= date('d/m/Y', $f['created_at']) ?></td>
                            <td class="px-3 text-nowrap" style="font-family:monospace;"><?= date('H:i:s', $f['created_at']) ?></td>
                            <td class="px-3"><?= BackupService::formatSize($f['size']) ?></td>
                            <td class="px-3 text-center text-nowrap">
                                <!-- Descargar -->
                                <a href="<?= app_url('backup/descargar?tipo=' . $clave . '&archivo=' . urlencode($f['filename'])) ?>"
                                   class="btn btn-sm btn-outline-primary py-0 px-2 me-1" title="Descargar">
                                    <i class="bi bi-download"></i>
                                </a>
                                <!-- Eliminar -->
                                <?php if ($puedeGestionar): ?>
                                <a href="<?= app_url('backup/eliminar?tipo=' . $clave . '&archivo=' . urlencode($f['filename'])) ?>"
                                   class="btn btn-sm btn-outline-danger py-0 px-2" title="Eliminar"
                                   onclick="return confirm('¿Eliminar este backup? Esta acción no se puede deshacer.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>
