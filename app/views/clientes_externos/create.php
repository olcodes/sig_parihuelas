<div class="container-fluid d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm border-0" style="width:100%; max-width:480px;">
        <div class="card-body p-4">
            <div class="mb-4 d-flex align-items-center">
                <i class="bi bi-people display-6 text-success me-2"></i>
                <div>
                    <h3 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Agregar Cliente Externo</h3>
                    <div class="small mt-1"><a href="https://e-consultaruc.sunat.gob.pe/cl-ti-itmrconsruc/FrameCriterioBusquedaWeb.jsp" target="_blank" rel="noopener noreferrer">Link de Búsqueda</a></div>
                </div>
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
                <div class="mb-3">
                    <label for="RUC" class="form-label">RUC</label>
                    <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control form-control-lg" id="RUC" name="RUC" maxlength="11" style="max-width: 230px;" value="<?= htmlspecialchars($data['RUC'] ?? '') ?>" required>
                            <button type="button" class="btn btn-outline-info" id="btnConsultarRUC" data-skip-global-ruc="1" title="Consultar SUNAT">Consultar SUNAT</button>
                    </div>
                    <div id="rucHelp" class="form-text">Ingrese el RUC y presione el botón para autocompletar la razón social desde SUNAT.</div>
                </div>
                <div class="mb-3">
                    <label for="Empresa" class="form-label">Empresa (Razón Social)</label>
                    <input type="text" class="form-control form-control-lg" id="Empresa" name="Empresa" value="<?= htmlspecialchars($data['Empresa'] ?? '') ?>" required style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="mb-4">
                    <label for="Direccion" class="form-label">Dirección Fiscal</label>
                    <input type="text" class="form-control form-control-lg" id="Direccion" name="Direccion" value="<?= htmlspecialchars($data['Direccion'] ?? '') ?>" required style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="mb-3">
                    <label for="Direccion2" class="form-label">Dirección 2</label>
                    <input type="text" class="form-control form-control-lg" id="Direccion2" name="Direccion2" value="<?= htmlspecialchars($data['Direccion2'] ?? '') ?>" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="mb-3">
                    <label for="Direccion3" class="form-label">Dirección 3</label>
                    <input type="text" class="form-control form-control-lg" id="Direccion3" name="Direccion3" value="<?= htmlspecialchars($data['Direccion3'] ?? '') ?>" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="mb-3">
                    <label for="Direccion4" class="form-label">Dirección 4</label>
                    <input type="text" class="form-control form-control-lg" id="Direccion4" name="Direccion4" value="<?= htmlspecialchars($data['Direccion4'] ?? '') ?>" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="mb-3">
                    <label for="Direccion5" class="form-label">Dirección 5</label>
                    <input type="text" class="form-control form-control-lg" id="Direccion5" name="Direccion5" value="<?= htmlspecialchars($data['Direccion5'] ?? '') ?>" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="bi bi-check-circle me-1"></i>Guardar</button>
                    <a href="<?= app_url('clientesexternos') ?>" class="btn btn-secondary px-4"><i class="bi bi-arrow-left me-1"></i>Cancelar</a>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        const empresa = document.getElementById('Empresa');
        const direccion = document.getElementById('Direccion');
        if (empresa) { empresa.readOnly = true; empresa.classList.add('auto-filled-readonly'); }
        if (direccion) { direccion.readOnly = true; direccion.classList.add('auto-filled-readonly'); }

        if (typeof window.mostrarMensajeRUC !== 'function') {
            window.mostrarMensajeRUC = function(mensaje) {
                let div = document.getElementById('mensajeRUC');
                if (!div) {
                    div = document.createElement('div');
                    div.id = 'mensajeRUC';
                    div.style.marginTop = '10px';
                    div.style.padding = '12px';
                    div.style.background = '#fff3cd';
                    div.style.color = '#856404';
                    div.style.border = '1px solid #ffeeba';
                    div.style.borderRadius = '5px';
                    div.style.fontWeight = 'bold';
                    div.style.fontSize = '1rem';
                    div.style.textAlign = 'center';
                    const rucHelp = document.getElementById('rucHelp');
                    if (rucHelp && rucHelp.parentNode) {
                        rucHelp.parentNode.insertBefore(div, rucHelp.nextSibling);
                    } else if (empresa && empresa.parentNode) {
                        empresa.parentNode.insertBefore(div, empresa);
                    }
                }
                div.textContent = mensaje;
                setTimeout(() => { div.remove(); }, 4000);
            };
        }

        const btnConsultarRUC = document.getElementById('btnConsultarRUC');
        if (btnConsultarRUC) {
            btnConsultarRUC.addEventListener('click', function() {
                const ruc = document.getElementById('RUC')?.value.trim() || '';
                if (ruc.length !== 11 || !/^[0-9]+$/.test(ruc)) {
                    window.mostrarMensajeRUC('Ingrese un RUC válido de 11 dígitos.');
                    return;
                }
                btnConsultarRUC.disabled = true;
                btnConsultarRUC.textContent = 'Consultando...';
                fetch(window.BASE_URL + '/api/consultarRuc', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ruc })
                })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success && data.razon_social) {
                        try { empresa.value = (data.razon_social || '').toUpperCase(); } catch(e){}
                        try { direccion.value = (data.direccion || '').toUpperCase(); } catch(e){}
                    } else {
                        window.mostrarMensajeRUC('No se encontró razón social para el RUC ingresado.');
                        try { empresa.value = ''; direccion.value = ''; } catch(e){}
                    }
                })
                .catch(err => {
                    console.error('Error consultando RUC', err);
                    window.mostrarMensajeRUC('No se encontró razón social para el RUC ingresado.');
                })
                .finally(() => {
                    btnConsultarRUC.disabled = false;
                    btnConsultarRUC.textContent = 'Consultar SUNAT';
                });
            });
        }
    } catch (e) {
        console.warn('No se pudo aplicar autocompletado RUC en create clientes_externos', e);
    }
});
</script>
