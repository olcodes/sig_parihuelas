<div class="container-fluid d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm border-0" style="width:100%; max-width:480px;">
        <div class="card-body p-4">
            <div class="mb-4 d-flex align-items-center">
                <i class="bi bi-geo-alt display-6 text-success me-2"></i>
                <h3 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Agregar Origen</h3>
            </div>
            <?php
 if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="mb-4">
                    <label for="Origen" class="form-label">Origen</label>
                    <input type="text" class="form-control form-control-lg" id="Origen" name="Origen" value="<?= htmlspecialchars($data['Origen'] ?? '') ?>" required style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="bi bi-check-circle me-1"></i>Guardar</button>
                    <a href="<?= app_url('origen') ?>" class="btn btn-secondary px-4"><i class="bi bi-arrow-left me-1"></i>Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    .card label.form-label {
        font-weight: 500;
        color: #374151;
    }
    .card .form-control-lg {
        font-size: 1.1rem;
        border-radius: 0.5rem;
    }
    .card .form-control-lg:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 0.15rem #1976d233;
    }

</style>

