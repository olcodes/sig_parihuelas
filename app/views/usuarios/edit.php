<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-pencil me-2"></i>Editar Usuario
                </div>
                <div class="card-body">
                    <?php
 if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="<?= app_url('usuarios/update/' . $usuario['Id']) ?>" autocomplete="off" class="needs-validation" novalidate>
                        <!-- Datos del Usuario -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required value="<?= htmlspecialchars($usuario['username']) ?>">
                            <div class="invalid-feedback">Ingrese el usuario.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña <span class="text-muted fw-normal">(dejar en blanco para no cambiar)</span></label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8">
                            <div class="form-text text-muted" style="font-size:0.82rem;">
                                <i class="bi bi-shield-check me-1 text-primary"></i>
                                Si cambia la contraseña: mínimo 8 caracteres, letras, números y al menos un carácter especial (<code>@</code>, <code>#</code>, <code>$</code>, <code>!</code>, etc.)
                            </div>
                            <input type="hidden" name="password_actual" value="<?= htmlspecialchars($usuario['password']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="DocIdentidad" class="form-label">Doc. Identidad</label>
                            <input type="text" class="form-control" id="DocIdentidad" name="DocIdentidad" required value="<?= htmlspecialchars($usuario['DocIdentidad']) ?>">
                            <div class="invalid-feedback">Ingrese el documento de identidad.</div>
                        </div>
                        <div class="mb-3">
                            <label for="NombresApellidos" class="form-label">Nombres y Apellidos</label>
                            <input type="text" class="form-control" id="NombresApellidos" name="NombresApellidos" required value="<?= htmlspecialchars($usuario['NombresApellidos']) ?>">
                            <div class="invalid-feedback">Ingrese los nombres y apellidos.</div>
                        </div>
                        <div class="mb-3">
                            <label for="RoleId" class="form-label">Rol</label>
                            <select class="form-select" id="RoleId" name="RoleId" required>
                                <option value="">Seleccione un rol</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['Id'] ?>" <?= $usuario['RoleId'] == $r['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['Nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleccione un rol.</div>
                        </div>

                        <!-- Sección: Límites de Modificación -->
                        <div class="card mt-4 mb-3 border-primary">
                            <div class="card-header bg-primary bg-opacity-10 text-primary fw-bold">
                                <i class="bi bi-shield-lock me-2"></i>Límites de Modificación
                            </div>
                            <div class="card-body">
                                <div class="text-muted small mb-3">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Configuración personalizada para controlar las modificaciones que este usuario puede realizar en los vales de los 5 módulos (Recepciones Externas/Internas, Despachos Externos/Internos, Kardex Parihuelas, Plan Abastecimiento).
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" 
                                           id="permite_multiples" name="permite_multiples" value="1"
                                           <?= !empty($limiteUsuario['permite_multiples']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="permite_multiples">
                                        <strong>Permitir múltiples modificaciones</strong>
                                        <span class="text-muted d-block small">
                                            El usuario podrá modificar vales sin límite de count ni restricción de tiempo.
                                            Marcar esta opción anula cualquier límite configurado abajo.
                                        </span>
                                    </label>
                                </div>

                                <hr class="my-3">

                                <div class="row g-3" id="camposLimites">
                                    <div class="col-md-6">
                                        <label for="max_modificaciones" class="form-label">Máximo de modificaciones</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-pencil"></i></span>
                                            <input type="number" class="form-control" id="max_modificaciones" name="max_modificaciones" 
                                                   value="<?= htmlspecialchars($limiteUsuario['max_modificaciones'] ?? '') ?>"
                                                   min="1" placeholder="Default: 1">
                                        </div>
                                        <div class="form-text">Dejar vacío = usa el valor por defecto (1 modificación por vale).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ventana_horas" class="form-label">Ventana de tiempo (horas)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                            <input type="number" class="form-control" id="ventana_horas" name="ventana_horas" 
                                                   value="<?= htmlspecialchars($limiteUsuario['ventana_horas'] ?? '') ?>"
                                                   min="1" placeholder="Ej: 24">
                                        </div>
                                        <div class="form-text">
                                            Dejar vacío = sin restricción de tiempo.<br>
                                            Ej: <strong>24</strong> = solo puede modificar dentro de las 24 horas posteriores a la creación del vale.
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 p-3 bg-light rounded">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <span class="badge bg-secondary fs-6"><?= !empty($limiteUsuario['permite_multiples']) ? 'Ilimitadas' : (($limiteUsuario['max_modificaciones'] ?? '1') ?: '1') ?> mod.</span>
                                            <div class="small text-muted mt-1">Modificaciones</div>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge bg-secondary fs-6"><?= !empty($limiteUsuario['permite_multiples']) ? 'Sin límite' : (!empty($limiteUsuario['ventana_horas']) ? ($limiteUsuario['ventana_horas'] . 'h') : 'Sin restricción') ?></span>
                                            <div class="small text-muted mt-1">Ventana de tiempo</div>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge bg-secondary fs-6"><?= !empty($limiteUsuario['permite_multiples']) ? 'Ilimitado' : (empty($limiteUsuario) ? 'Default' : 'Personalizado') ?></span>
                                            <div class="small text-muted mt-1">Tipo de límite</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= app_url('usuarios') ?>" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar cambios</button>
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

// Si se marca "permite_multiples", deshabilitar los campos de límite
document.getElementById('permite_multiples').addEventListener('change', function() {
    const campos = document.getElementById('camposLimites').querySelectorAll('input');
    campos.forEach(c => c.disabled = this.checked);
});

// Estado inicial: si permite_multiples está marcado, deshabilitar campos
(function() {
    const check = document.getElementById('permite_multiples');
    if (check.checked) {
        const campos = document.getElementById('camposLimites').querySelectorAll('input');
        campos.forEach(c => c.disabled = true);
    }
})();
</script>
