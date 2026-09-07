<div class="container-fluid px-0">

    <!-- Encabezado -->
    <div class="d-flex align-items-center mb-3">
        <i class="bi bi-shield-lock-fill display-6 me-2" style="color:#1a237e;"></i>
        <div>
            <h2 class="mb-0 fw-bold" style="color:#1a237e; letter-spacing:0.5px;">Bitácora de Auditoría</h2>
            <small class="text-muted">Registro de todas las transacciones: creaciones y modificaciones</small>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="get" action="<?= app_url('auditoria') ?>" class="row g-2 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Módulo</label>
                    <select name="modulo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach (['Despacho Interno','Despacho Externo','Recepción Interna','Recepción Externa'] as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"<?= ($filtros['modulo'] === $m) ? ' selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Acción</label>
                    <select name="accion" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="CREACIÓN"<?= $filtros['accion'] === 'CREACIÓN' ? ' selected' : '' ?>>CREACIÓN</option>
                        <option value="MODIFICACIÓN"<?= $filtros['accion'] === 'MODIFICACIÓN' ? ' selected' : '' ?>>MODIFICACIÓN</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Usuario</label>
                    <select name="usuario_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= (int)$u['Id'] ?>"<?= ((string)$filtros['usuario_id'] === (string)$u['Id']) ? ' selected' : '' ?>>
                                <?= htmlspecialchars($u['username']) ?> — <?= htmlspecialchars($u['NombresApellidos'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label form-label-sm mb-1 fw-semibold">N° Vale</label>
                    <input type="text" name="nvale" class="form-control form-control-sm" placeholder="VDI-000001" value="<?= htmlspecialchars($filtros['nvale']) ?>">
                </div>
                <div class="col-12 col-md-12 d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    <?php
                    $hayFiltros = array_filter($filtros, fn($v) => $v !== '');
                    if ($hayFiltros): ?>
                        <a href="<?= app_url('auditoria') ?>" class="btn btn-secondary btn-sm px-3">
                            <i class="bi bi-x-lg me-1"></i>Limpiar
                        </a>
                    <?php endif; ?>
                    <a href="<?= app_url('auditoria/exportar?' . http_build_query(array_filter($filtros, fn($v) => $v !== ''))) ?>"
                       class="btn btn-outline-success btn-sm px-3" title="Exportar a Excel">
                        <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen -->
    <div class="d-flex align-items-center mb-2 gap-3">
        <span class="text-muted small"><i class="bi bi-list-ul me-1"></i>Total registros: <strong><?= number_format($total) ?></strong></span>
        <span class="text-muted small">Página <strong><?= $pagina ?></strong> de <strong><?= $totalPaginas ?></strong></span>
    </div>

    <!-- Tabla -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0" style="font-size:0.92rem;">
                <thead style="background:#1a237e; color:#fff; position:sticky; top:0; z-index:1;">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th class="px-2 py-2">Módulo</th>
                        <th class="px-2 py-2">N° Vale</th>
                        <th class="px-2 py-2">Acción</th>
                        <th class="px-2 py-2">Usuario</th>
                        <th class="px-2 py-2">Nombres y Apellidos</th>
                        <th class="px-2 py-2">Fecha</th>
                        <th class="px-2 py-2">Hora</th>
                        <th class="px-2 py-2">IP</th>
                        <th class="px-2 py-2 text-center">N° Modif.</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-inbox me-2" style="font-size:1.5rem;"></i>No se encontraron registros
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $moduloIconos = [
                        'Despacho Interno'  => ['icon' => 'bi-truck-flatbed',      'color' => '#1565c0'],
                        'Despacho Externo'  => ['icon' => 'bi-truck-front',        'color' => '#6a1b9a'],
                        'Recepción Interna' => ['icon' => 'bi-inbox',              'color' => '#2e7d32'],
                        'Recepción Externa' => ['icon' => 'bi-box-arrow-in-right', 'color' => '#e65100'],
                    ];
                    $offset_display = ($pagina - 1) * 50;
                    foreach ($registros as $i => $r):
                        $fh    = $r['fecha_hora'] ? new DateTime($r['fecha_hora']) : null;
                        $icon  = $moduloIconos[$r['modulo']] ?? ['icon' => 'bi-circle', 'color' => '#333'];
                        $esModif = $r['accion'] === 'MODIFICACIÓN';
                    ?>
                    <tr>
                        <td class="px-3 text-muted"><?= $offset_display + $i + 1 ?></td>
                        <td class="px-2">
                            <span style="color:<?= $icon['color'] ?>; font-weight:600; white-space:nowrap;">
                                <i class="bi <?= $icon['icon'] ?> me-1"></i><?= htmlspecialchars($r['modulo']) ?>
                            </span>
                        </td>
                        <td class="px-2">
                            <span class="badge" style="background:#e8eaf6; color:#1a237e; font-size:0.88rem; font-weight:600; letter-spacing:0.5px;">
                                <?= htmlspecialchars($r['nvale'] ?? '—') ?>
                            </span>
                        </td>
                        <td class="px-2">
                            <?php if ($esModif): ?>
                                <span class="badge bg-warning text-dark" style="font-size:0.82rem;">
                                    <i class="bi bi-pencil-square me-1"></i>MODIFICACIÓN
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success" style="font-size:0.82rem;">
                                    <i class="bi bi-plus-circle me-1"></i>CREACIÓN
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-2">
                            <code style="background:#f1f3f9; padding:2px 6px; border-radius:4px; font-size:0.88rem; color:#1a237e;">
                                <?= htmlspecialchars($r['username'] ?? '—') ?>
                            </code>
                        </td>
                        <td class="px-2"><?= htmlspecialchars($r['nombres'] ?? '—') ?></td>
                        <td class="px-2 text-nowrap"><?= $fh ? $fh->format('d/m/Y') : '—' ?></td>
                        <td class="px-2 text-nowrap">
                            <span style="font-family:monospace; font-size:0.9rem;">
                                <?= $fh ? $fh->format('H:i:s') : '—' ?>
                            </span>
                        </td>
                        <td class="px-2">
                            <span class="text-muted" style="font-family:monospace; font-size:0.85rem;">
                                <?= htmlspecialchars($r['ip'] ?? '—') ?>
                            </span>
                        </td>
                        <td class="px-2 text-center">
                            <?php if ($r['nmodificacion'] !== null): ?>
                                <span class="badge rounded-pill" style="background:#fff3e0; color:#e65100; border:1px solid #ffcc80; font-size:0.88rem;">
                                    <?= (int)$r['nmodificacion'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="mt-3 d-flex justify-content-center">
        <ul class="pagination pagination-sm mb-0 flex-wrap gap-1">
            <li class="page-item<?= $pagina <= 1 ? ' disabled' : '' ?>">
                <a class="page-link rounded-pill px-2" href="<?= app_url('auditoria?' . http_build_query(array_merge($filtros, ['pagina' => $pagina - 1]))) ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php
            $start = max(1, $pagina - 3);
            $end   = min($totalPaginas, $start + 6);
            if ($start > 1): ?>
                <li class="page-item"><a class="page-link rounded-pill px-2" href="<?= app_url('auditoria?' . http_build_query(array_merge($filtros, ['pagina' => 1]))) ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link rounded-pill px-2">…</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item<?= $p === $pagina ? ' active' : '' ?>">
                    <a class="page-link rounded-pill px-2" href="<?= app_url('auditoria?' . http_build_query(array_merge($filtros, ['pagina' => $p]))) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($end < $totalPaginas): ?>
                <?php if ($end < $totalPaginas - 1): ?><li class="page-item disabled"><span class="page-link rounded-pill px-2">…</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link rounded-pill px-2" href="<?= app_url('auditoria?' . http_build_query(array_merge($filtros, ['pagina' => $totalPaginas]))) ?>"><?= $totalPaginas ?></a></li>
            <?php endif; ?>
            <li class="page-item<?= $pagina >= $totalPaginas ? ' disabled' : '' ?>">
                <a class="page-link rounded-pill px-2" href="<?= app_url('auditoria?' . http_build_query(array_merge($filtros, ['pagina' => $pagina + 1]))) ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>
