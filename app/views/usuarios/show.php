<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white fw-bold">
                    <i class="bi bi-person-lines-fill me-2"></i>Detalle de Usuario
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Usuario</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($usuario['username']) ?></dd>
                        <dt class="col-sm-4">Doc. Identidad</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($usuario['DocIdentidad']) ?></dd>
                        <dt class="col-sm-4">Nombres y Apellidos</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($usuario['NombresApellidos']) ?></dd>
                        <dt class="col-sm-4">Rol</dt>
                        <dd class="col-sm-8">
                            <?php
 if (isset($usuario['RoleId'])): ?>
                                <?php
                                $roleModel = new \Role();
                                $rol = $roleModel->getById($usuario['RoleId']);
                                echo htmlspecialchars($rol['Nombre'] ?? '');
                                ?>
                            <?php endif; ?>
                        </dd>
                    </dl>
                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?= app_url('usuarios') ?>" class="btn btn-outline-secondary">Volver</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
