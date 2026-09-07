<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-pencil me-2"></i>Editar Rol
                </div>
                <div class="card-body">
                    <form method="post" action="<?= app_url('roles/update/' . $rol['Id']) ?>" autocomplete="off" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="Nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="Nombre" name="Nombre" required value="<?= htmlspecialchars($rol['Nombre']) ?>">
                            <div class="invalid-feedback">Ingrese el nombre del rol.</div>
                        </div>
                        <div class="mb-3">
                            <label for="Descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="Descripcion" name="Descripcion" rows="2"><?= htmlspecialchars($rol['Descripcion']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Privilegios</label>
                            <div class="row">
                                <?php
 foreach ($privilegios as $p): ?>
                                    <div class="col-12 col-md-6 mb-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="privilegios[]" id="privilegio<?= $p['Id'] ?>" value="<?= $p['Id'] ?>" <?= in_array($p['Id'], $privilegiosRol) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="privilegio<?= $p['Id'] ?>">
                                                <strong><?= htmlspecialchars($p['Nombre']) ?></strong>
                                                <?php if (!empty($p['Descripcion'])): ?>
                                                    <span class="text-muted small">- <?= htmlspecialchars($p['Descripcion']) ?></span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="<?= app_url('roles') ?>" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
