<div class="container-fluid d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm border-0" style="width:100%; max-width:480px;">
        <div class="card-body p-4">
            <div class="mb-4 d-flex align-items-center">
                <i class="bi bi-pencil-square display-6 text-primary me-2"></i>
                <h3 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Editar Responsable</h3>
            </div>
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= app_url('responsables/update/' . ($Id ?? '')) ?>" id="formResponsable" autocomplete="off">
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="Nombres" class="form-label">Nombre</label>
                        <input type="text" class="form-control form-control-lg w-100" id="Nombres" name="Nombres" required value="<?= htmlspecialchars($Nombres ?? '') ?>" style="text-transform:uppercase; width:100%; max-width:480px;" oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g,'').toUpperCase()">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="ApellidoPaterno" class="form-label">Apellido Paterno</label>
                        <input type="text" class="form-control form-control-lg w-100" id="ApellidoPaterno" name="ApellidoPaterno" required value="<?= htmlspecialchars($ApellidoPaterno ?? '') ?>" style="text-transform:uppercase; width:100%; max-width:480px;" oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g,'').toUpperCase()">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="NombresApellidos" class="form-label">Nombre y Apellido</label>
                            <input type="text" class="form-control form-control-lg bg-light w-100" id="NombresApellidos" name="NombresApellidos" value="<?= htmlspecialchars(trim(($Nombres ?? '') . ' ' . ($ApellidoPaterno ?? ''))) ?>" readonly style="text-transform:uppercase; width:100%; max-width:480px;">
                    </div>
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-check-circle me-1"></i>Guardar Cambios</button>
                    <a href="<?= app_url('responsables') ?>" class="btn btn-secondary px-4"><i class="bi bi-arrow-left me-1"></i>Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Actualiza Nombre y Apellido automáticamente
function actualizarNombresApellidos() {
    const n = document.getElementById('Nombres').value.trim();
    const ap = document.getElementById('ApellidoPaterno').value.trim();
    document.getElementById('NombresApellidos').value = (n + ' ' + ap).toUpperCase();
}
document.getElementById('ApellidoPaterno').addEventListener('input', actualizarNombresApellidos);
document.getElementById('Nombres').addEventListener('input', actualizarNombresApellidos);
</script>
<style>
#btnBuscarDNI.btn-outline-info {
    background: #fff;
    color: #0dcaf0;
    border-color: #0dcaf0;
    transition: background 0.18s, color 0.18s;
}
#btnBuscarDNI.btn-outline-info:hover, #btnBuscarDNI.btn-outline-info:focus {
    background: #0dcaf0;
    color: #000;
    border-color: #0dcaf0;
}
</style>
