<div class="container-fluid px-0">
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-diagram-3 display-6 text-primary me-2"></i>
        <h2 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Mantenimiento de Áreas</h2>
    </div>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 flex-nowrap">
                <a href="<?= app_url('areas/create') ?>" class="btn btn-success fw-bold px-3">
                    <i class="bi bi-plus-circle me-1"></i> Nueva Área
                </a>
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 justify-content-end" style="flex:1 1 auto; min-width:0;">
                    <form class="d-flex align-items-center gap-1" method="get" action="<?= app_url('areas') ?>" style="min-width:0;">
                        <input type="text" name="busqueda" class="form-control form-control-sm" placeholder="Buscar área..." value="<?= htmlspecialchars($search ?? '') ?>" style="width:240px; max-width:260px;">
                        <button type="submit" class="btn btn-primary btn-sm px-2 ms-2" title="Buscar"><i class="bi bi-search"></i></button>
                        <?php
                        if (!empty($search)):
                        ?>
                            <a href="<?= app_url('areas') ?>" class="btn btn-secondary btn-sm px-2 ms-1" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                        <?php
                            $exportUrl = app_url('areas/exportar' . (!empty($search) ? ('?busqueda=' . urlencode($search)) : ''));
                        ?>
                        <a href="<?= $exportUrl ?>" class="btn btn-outline-success btn-sm px-2 ms-2" title="Exportar a Excel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                              <path d="M5.884 6.68a.5.5 0 0 1 .09.7L4.825 8l1.15 1.12a.5.5 0 1 1-.7.72L4 8.708l-1.275 1.13a.5.5 0 1 1-.65-.76l1.1-1.02-1.1-1.02a.5.5 0 1 1 .65-.76L4 7.293l1.275-1.13a.5.5 0 0 1 .7.517z"/>
                              <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3.5-2.5V4a1 1 0 0 0 1 1h2.5L10.5 2z"/>
                            </svg>
                        </a>
                    </form>
                    <?php if (($totalPaginas ?? 1) > 1): ?>
                    <nav aria-label="Paginación de áreas" class="ms-md-3" style="min-width:0;">
                        <ul class="pagination pagination-sm mb-0 flex-nowrap" style="gap:0.25rem; min-width:unset;">
                            <li class="page-item<?= ($page ?? 1) <= 1 ? ' disabled' : '' ?>">
                                <?php
                                    $prevPageUrl = app_url('areas?pagina=' . (($page ?? 1) - 1) . (!empty($search) ? ('&busqueda=' . urlencode($search)) : ''));
                                ?>
                                <a class="page-link rounded-pill px-2" href="<?= $prevPageUrl ?>" tabindex="-1">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php
                            $currentPage = $page ?? 1;
                            $maxPagesToShow = 7;
                            $startPage = max(1, $currentPage - floor($maxPagesToShow / 2));
                            $endPage = min($totalPaginas, $startPage + $maxPagesToShow - 1);
                            if ($endPage - $startPage + 1 < $maxPagesToShow) {
                                $startPage = max(1, $endPage - $maxPagesToShow + 1);
                            }
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link rounded-pill px-2" href="<?= app_url('areas?pagina=1' . (!empty($search) ? ('&busqueda=' . urlencode($search)) : '')) ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link rounded-pill px-2">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item<?= $currentPage == $i ? ' active' : '' ?>">
                                    <?php
                                        $pageUrl = app_url('areas?pagina=' . $i . (!empty($search) ? ('&busqueda=' . urlencode($search)) : ''));
                                    ?>
                                    <a class="page-link rounded-pill px-2<?= $currentPage == $i ? ' fw-bold' : '' ?>" href="<?= $pageUrl ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($endPage < $totalPaginas): ?>
                                <?php if ($endPage < $totalPaginas - 1): ?>
                                    <li class="page-item disabled"><span class="page-link rounded-pill px-2">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link rounded-pill px-2" href="<?= app_url('areas?pagina=' . $totalPaginas . (!empty($search) ? ('&busqueda=' . urlencode($search)) : '')) ?>"><?= $totalPaginas ?></a>
                                </li>
                            <?php endif; ?>
                            <li class="page-item<?= ($page ?? 1) >= $totalPaginas ? ' disabled' : '' ?>">
                                <?php
                                    $nextPageUrl = app_url('areas?pagina=' . (($page ?? 1) + 1) . (!empty($search) ? ('&busqueda=' . urlencode($search)) : ''));
                                ?>
                                <a class="page-link rounded-pill px-2" href="<?= $nextPageUrl ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 mb-0" style="margin-bottom:0; box-shadow:none; border-radius:0;">
        <div class="card-body p-0" style="padding-bottom:0;">
            <div class="table-responsive" style="margin-bottom:0;">
                <table class="table table-hover align-middle mb-0" style="margin-bottom:0;">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Área</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($areas)) {
                            foreach ($areas as $a) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['Id']) ?></td>
                                    <td><?= htmlspecialchars($a['Area']) ?></td>
                                    <td class="text-center" style="white-space:nowrap; min-width:90px;">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <?php $editUrl = app_url('areas/edit/' . urlencode($a['Id'])); ?>
                                            <a href="<?= $editUrl ?>" class="btn btn-sm btn-outline-primary px-2" title="Editar"><i class="bi bi-pencil"></i></a>
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $a['Id'] ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        </div>
                                        <!-- Modal -->
                                        <div class="modal fade" id="modalEliminar<?= $a['Id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $a['Id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-4">
                                                    <div class="modal-header bg-danger bg-opacity-10 border-0 rounded-top-4">
                                                        <i class="bi bi-exclamation-triangle-fill text-danger fs-3 me-2"></i>
                                                        <h5 class="modal-title fw-bold text-danger" id="modalLabel<?= $a['Id'] ?>">Confirmar eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <p class="mb-2">¿Seguro que deseas eliminar el área<br><strong><?= htmlspecialchars($a['Area']) ?></strong>?</p>
                                                        <p class="text-muted small mb-0">Esta acción no se puede deshacer.</p>
                                                    </div>
                                                    <div class="modal-footer border-0 d-flex justify-content-between gap-2 pb-3">
                                                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Cancelar</button>
                                                        <?php $deleteUrl = app_url('areas/delete/' . urlencode($a['Id'])); ?>
                                                        <a href="<?= $deleteUrl ?>" class="btn btn-danger px-4 fw-bold"><i class="bi bi-trash me-1"></i>Eliminar</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr><td colspan="3" class="text-center text-muted">No se encontraron áreas.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<style>
    .table thead th {
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .table-hover tbody tr:hover {
        background: #f3f7fa;
        transition: background 0.18s;
    }
    .btn-outline-primary, .btn-outline-danger {
        border-width: 2px;
    }
    .btn-outline-primary:hover, .btn-outline-danger:hover {
        opacity: 0.92;
    }
    .card .form-control-sm {
        font-size: 0.95rem;
        padding: 0.25rem 0.5rem;
        height: 2rem;
        box-shadow: none;
        transition: box-shadow 0.15s;
    }
    .card .form-control-sm:focus {
        box-shadow: 0 0 0 0.15rem #b6d4fe;
        z-index: 2;
    }
    .card .btn-sm {
        font-size: 0.95rem;
        padding: 0.18rem 0.5rem;
        height: 1.8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .pagination .page-link {
        color: #1976d2;
        border: none;
        background: #f3f7fa;
        transition: background 0.15s, color 0.15s;
    }
    .pagination .page-link:hover {
        background: #e3eafc;
        color: #0d47a1;
    }
    .pagination .page-item.active .page-link {
        background: #1976d2;
        color: #fff;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(25,118,210,0.08);
    }
    .pagination .page-link:focus {
        box-shadow: 0 0 0 0.12rem #b6d4fe;
    }
    .card.border-0.mb-0 {
        margin-bottom: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    .card .card-body.p-0 {
        padding-bottom: 0 !important;
    }
    .table-responsive {
        margin-bottom: 0 !important;
    }
    .table {
        margin-bottom: 0 !important;
    }
    .container-fluid {
        padding-bottom: 0 !important;
    }
</style>
