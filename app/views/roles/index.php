
<div class="container-fluid px-0" style="padding-bottom:0;">
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-shield-lock display-6 text-primary me-2"></i>
        <h2 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Mantenimiento de Roles</h2>
    </div>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 flex-nowrap">
                <a href="<?= app_url('roles/create') ?>" class="btn btn-success fw-bold px-3">
                    <i class="bi bi-plus-circle me-1"></i> Nuevo Rol
                </a>
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 justify-content-end" style="flex:1 1 auto; min-width:0;">
                    <form class="d-flex align-items-center gap-1" method="get" action="<?= app_url('roles') ?>" style="min-width:0;">
                        <input type="text" name="busqueda" class="form-control form-control-sm" placeholder="Buscar..." value="<?= htmlspecialchars($busqueda ?? '') ?>" style="width:240px; max-width:260px;">
                        <button type="submit" class="btn btn-primary btn-sm px-2 ms-2" title="Buscar"><i class="bi bi-search"></i></button>
                        <?php
 if (!empty($busqueda)): ?>
                            <a href="<?= app_url('roles') ?>" class="btn btn-secondary btn-sm px-2 ms-1" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                        <a href="<?= app_url('roles/exportar' . (!empty($busqueda) ? ('?busqueda=' . urlencode($busqueda)) : '')) ?>" class="btn btn-outline-success btn-sm px-2 ms-2" title="Exportar a Excel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                              <path d="M5.884 6.68a.5.5 0 0 1 .09.7L4.825 8l1.15 1.12a.5.5 0 1 1-.7.72L4 8.708l-1.275 1.13a.5.5 0 1 1-.65-.76l1.1-1.02-1.1-1.02a.5.5 0 1 1 .65-.76L4 7.293l1.275-1.13a.5.5 0 0 1 .7.517z"/>
                              <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3.5-2.5V4a1 1 0 0 0 1 1h2.5L10.5 2z"/>
                            </svg>
                        </a>
                    </form>
                    <?php if (($totalPaginas ?? 1) > 1): ?>
                    <nav aria-label="Paginación de roles" class="ms-md-3" style="min-width:0;">
                        <ul class="pagination pagination-sm mb-0 flex-nowrap" style="gap:0.25rem; min-width:unset;">
                            <li class="page-item<?= ($pagina ?? 1) <= 1 ? ' disabled' : '' ?>">
                                <a class="page-link rounded-pill px-2" href="<?= app_url('roles?pagina=' . (($pagina ?? 1) - 1) . (!empty($busqueda) ? ('&busqueda=' . urlencode($busqueda)) : '')) ?>" tabindex="-1">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <li class="page-item<?= ($pagina ?? 1) == $i ? ' active' : '' ?>">
                                    <a class="page-link rounded-pill px-2<?= ($pagina ?? 1) == $i ? ' fw-bold' : '' ?>" href="<?= app_url('roles?pagina=' . $i . (!empty($busqueda) ? ('&busqueda=' . urlencode($busqueda)) : '')) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item<?= ($pagina ?? 1) >= $totalPaginas ? ' disabled' : '' ?>">
                                <a class="page-link rounded-pill px-2" href="<?= app_url('roles?pagina=' . (($pagina ?? 1) + 1) . (!empty($busqueda) ? ('&busqueda=' . urlencode($busqueda)) : '')) ?>">
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
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['Id']) ?></td>
                            <td><?= htmlspecialchars($r['Nombre']) ?></td>
                            <td><?= htmlspecialchars($r['Descripcion']) ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="<?= app_url('roles/edit/' . $r['Id']) ?>" class="btn btn-sm btn-outline-primary px-2" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-2" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $r['Id'] ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </div>
                                <!-- Modal individual por rol -->
                                <div class="modal fade" id="modalEliminar<?= $r['Id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $r['Id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg rounded-4">
                                            <div class="modal-header bg-danger bg-opacity-10 border-0 rounded-top-4">
                                                <i class="bi bi-exclamation-triangle-fill text-danger fs-3 me-2"></i>
                                                <h5 class="modal-title fw-bold text-danger" id="modalLabel<?= $r['Id'] ?>">Confirmar eliminación</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <p class="mb-2">¿Seguro que deseas eliminar el rol<br><strong><?= htmlspecialchars($r['Nombre']) ?></strong>?</p>
                                                <p class="text-muted small mb-0">Esta acción no se puede deshacer.</p>
                                            </div>
                                            <div class="modal-footer border-0 d-flex justify-content-between gap-2 pb-3">
                                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Cancelar</button>
                                                <a href="<?= app_url('roles/delete/' . $r['Id']) ?>" class="btn btn-danger px-4 fw-bold"><i class="bi bi-trash me-1"></i>Eliminar</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

