<div class="container-fluid d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm border-0" style="width:100%; max-width:480px;">
        <div class="card-body p-4">
            <div class="mb-4 d-flex align-items-center">
                <i class="bi bi-car-front-fill display-6 text-success me-2"></i>
                <div>
                    <h3 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Agregar Placa</h3>
                    <div class="small mt-1"><a href="https://www.mtc.gob.pe/tramitesenlinea/tweb_tLinea/tw_consultadgtt/Frm_rep_intra_mercancia.aspx" target="_blank" rel="noopener noreferrer">Link de Búsqueda</a></div>
                </div>
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
            <form method="post" action="<?= app_url('placas/store') ?>" autocomplete="off">
                <div class="mb-3">
                    <label for="Placa" class="form-label">N&deg; Placa <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-lg" id="Placa" name="Placa" required value="<?= htmlspecialchars($Placa ?? '') ?>" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()" maxlength="6" placeholder="Ej: ABC123">
                </div>
                <div class="mb-3">
                    <label for="TipoPlaca" class="form-label">Tipo Placa</label>
                    <select id="TipoPlaca" name="TipoPlaca" class="form-select form-select-lg">
                        <option value="" <?= (!isset($TipoPlaca) || $TipoPlaca === '') ? 'selected' : '' ?>>-- Seleccionar --</option>
                        <option value="TRACTO" <?= (isset($TipoPlaca) && $TipoPlaca === 'TRACTO') ? 'selected' : '' ?>>TRACTO</option>
                        <option value="CARRETA" <?= (isset($TipoPlaca) && $TipoPlaca === 'CARRETA') ? 'selected' : '' ?>>CARRETA</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="ConstanciaInscripcion" class="form-label">Constancia Inscripción</label>
                    <input type="text" class="form-control form-control-lg" id="ConstanciaInscripcion" name="ConstanciaInscripcion" value="<?= htmlspecialchars($ConstanciaInscripcion ?? '') ?>" maxlength="50" placeholder="Opcional">
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="bi bi-check-circle me-1"></i>Guardar</button>
                    <a href="<?= app_url('placas') ?>" class="btn btn-secondary px-4"><i class="bi bi-arrow-left me-1"></i>Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
