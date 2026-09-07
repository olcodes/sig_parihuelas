<?php
?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-plus-circle me-2"></i>Nuevo Privilegio
                </div>
                <div class="card-body">
                    <form method="post" action="<?= app_url('privilegios/store') ?>" autocomplete="off" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="Nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="Nombre" name="Nombre" required autofocus>
                            <div class="invalid-feedback">Ingrese el nombre del privilegio.</div>
                        </div>
                        <div class="mb-3">
                            <label for="Descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="Descripcion" name="Descripcion" rows="2"></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="<?= app_url('privilegios') ?>" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar</button>
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
