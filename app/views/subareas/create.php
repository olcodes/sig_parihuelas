<div class="container-fluid d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm border-0" style="width:100%; max-width:480px;">
        <div class="card-body p-4">
            <div class="mb-4 d-flex align-items-center">
                <i class="bi bi-diagram-2 display-6 text-success me-2"></i>
                <h3 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Agregar Subárea</h3>
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
            <form method="post" autocomplete="off">
                <div class="mb-4">
                    <label for="IdArea" class="form-label">Área</label>
                    <div>
                        <select class="form-select form-select-lg w-100" id="IdArea" name="IdArea" required>
                            <option value="">Seleccione un área</option>
                            <?php foreach ($areas as $a): ?>
                                <option value="<?= $a['Id'] ?>" <?= (isset($data['IdArea']) && $data['IdArea'] == $a['Id']) ? 'selected' : '' ?>><?= htmlspecialchars($a['Area']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="Subarea" class="form-label">Subárea</label>
                    <input type="text" class="form-control form-control-lg" id="Subarea" name="Subarea" maxlength="100" value="<?= htmlspecialchars($data['Subarea'] ?? '') ?>" required>
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="bi bi-check-circle me-1"></i>Guardar</button>
                    <a href="<?= BASE_URL ?>/subareas" class="btn btn-secondary px-4"><i class="bi bi-arrow-left me-1"></i>Cancelar</a>
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
    .card .form-control-lg, .card .form-select-lg {
        font-size: 1.1rem;
        border-radius: 0.5rem;
    }
    .card .form-control-lg:focus, .card .form-select-lg:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 0.15rem #1976d233;
    }
    .form-select.custom-dropdown:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37,99,235,.25);
    }
    .form-select.custom-dropdown {
        background-color: #fff;
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    /* Mejora visual del menú desplegable del select */
    .form-select.custom-dropdown:focus-visible,
    .form-select.custom-dropdown:active {
        outline: none;
    }
    /* Solo navegadores que soportan ::-webkit-scrollbar y ::-webkit-inner-spin-button */
    select.form-select.custom-dropdown option {
        border-radius: 0 !important;
        border-bottom: 1px solid #e5e7eb;
        padding: 0.5rem 1rem;
    }
    select.form-select.custom-dropdown option:last-child {
        border-bottom: none;
    }
    /* Sombra al menú desplegable (solo navegadores modernos) */
    select.form-select.custom-dropdown:focus {
        box-shadow: 0 8px 24px 0 rgba(37,99,235,0.10);
    }
        /* Ajuste visual para Bootstrap Select */
        .bootstrap-select,
        .bootstrap-select .dropdown-toggle {
            width: 100% !important;
            min-height: 48px;
            font-size: 1.1rem;
            border-radius: 0.5rem;
        }
        .bootstrap-select .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: 0 8px 24px 0 rgba(37,99,235,0.10);
            border: 1px solid #d1d5db;
        }
        .bootstrap-select .dropdown-item.active, .bootstrap-select .dropdown-item:active {
            background-color: #e3eaf6;
            color: #1976d2;
        }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && $('.selectpicker').length) {
        $('.selectpicker').selectpicker();
    }
});
</script>
