<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-person-plus me-2"></i>Nuevo Usuario
                </div>
                <div class="card-body">
                    <?php
 if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="<?= app_url('usuarios/store') ?>" autocomplete="off" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                            <div class="invalid-feedback">Ingrese el usuario.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="form-text text-muted" style="font-size:0.82rem;">
                                <i class="bi bi-shield-check me-1 text-primary"></i>
                                Mínimo 8 caracteres, debe incluir letras, números y al menos un carácter especial (<code>@</code>, <code>#</code>, <code>$</code>, <code>!</code>, etc.)
                            </div>
                            <div class="invalid-feedback">Ingrese la contraseña (mín. 8 caracteres).</div>
                        </div>
                        <div class="mb-3">
                            <label for="DocIdentidad" class="form-label">Doc. Identidad</label>
                            <input type="text" class="form-control" id="DocIdentidad" name="DocIdentidad" required>
                            <div class="invalid-feedback">Ingrese el documento de identidad.</div>
                        </div>
                        <div class="mb-3">
                            <label for="NombresApellidos" class="form-label">Nombres y Apellidos</label>
                            <input type="text" class="form-control" id="NombresApellidos" name="NombresApellidos" required>
                            <div class="invalid-feedback">Ingrese los nombres y apellidos.</div>
                        </div>
                        <div class="mb-3">
                            <label for="RoleId" class="form-label">Rol</label>
                            <select class="form-select" id="RoleId" name="RoleId" required>
                                <option value="">Seleccione un rol</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['Id'] ?>"><?= htmlspecialchars($r['Nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleccione un rol.</div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="<?= app_url('usuarios') ?>" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
// Validación Bootstrap
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
