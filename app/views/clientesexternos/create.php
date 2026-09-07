<div class="container-fluid d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm border-0" style="width:100%; max-width:480px;">
        <div class="card-body p-4">
            <div class="mb-4 d-flex align-items-center">
                <i class="bi bi-building-add display-6 text-success me-2"></i>
                <div>
                    <h3 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Agregar Cliente Externo</h3>
                    <div class="small mt-1"><a href="https://e-consultaruc.sunat.gob.pe/cl-ti-itmrconsruc/FrameCriterioBusquedaWeb.jsp" target="_blank" rel="noopener noreferrer">Link de Búsqueda</a></div>
                </div>
                <div class="form-check form-switch ms-auto">
                    <input class="form-check-input" type="checkbox" id="manualToggle">
                    <label class="form-check-label small text-muted ms-1" for="manualToggle">Manual</label>
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
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="RUC" class="form-label">RUC</label>
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control" id="RUC" name="RUC" maxlength="11" style="text-transform:uppercase;" value="<?= htmlspecialchars($data['RUC'] ?? '') ?>" required>
                            <button type="button" class="btn btn-outline-info" id="btnConsultarRUC" data-skip-global-ruc="1" title="Consultar SUNAT" style="min-width:110px; font-weight:600; border-width:2px;">Consultar SUNAT</button>
                        </div>
                        <div id="rucHelp" class="form-text">Ingrese el RUC y presione el botón para autocompletar la razón social desde SUNAT.</div>
                    </div>
                </div>
                <div class="mb-4 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="Empresa" class="form-label">Empresa (Razón Social)</label>
                        <input type="text" class="form-control form-control-lg" id="Empresa" name="Empresa" value="<?= htmlspecialchars($data['Empresa'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="mb-4 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="Direccion" class="form-label">Dirección Fiscal</label>
                        <input type="text" class="form-control form-control-lg" id="Direccion" name="Direccion" value="<?= htmlspecialchars($data['Direccion'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="Direccion2" class="form-label">Dirección 2</label>
                        <input type="text" class="form-control form-control-lg" id="Direccion2" name="Direccion2" value="<?= htmlspecialchars($data['Direccion2'] ?? '') ?>" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="Direccion3" class="form-label">Dirección 3</label>
                            <input type="text" class="form-control form-control-lg" id="Direccion3" name="Direccion3" value="<?= htmlspecialchars($data['Direccion3'] ?? '') ?>" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="Direccion4" class="form-label">Dirección 4</label>
                            <input type="text" class="form-control form-control-lg" id="Direccion4" name="Direccion4" value="<?= htmlspecialchars($data['Direccion4'] ?? '') ?>" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="Direccion5" class="form-label">Dirección 5</label>
                            <input type="text" class="form-control form-control-lg" id="Direccion5" name="Direccion5" value="<?= htmlspecialchars($data['Direccion5'] ?? '') ?>" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>
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
#btnConsultarRUC.btn-outline-info {
    background: #fff;
    color: #0dcaf0;
    border-color: #0dcaf0;
    transition: background 0.18s, color 0.18s;
}
#btnConsultarRUC.btn-outline-info:hover, #btnConsultarRUC.btn-outline-info:focus {
    background: #0dcaf0;
    color: #000;
    border-color: #0dcaf0;
}
</style>
<script>
(function(){
    const btn = document.getElementById('btnConsultarRUC');
    if (!btn) return;

    // Preparar toggle Manual: deshabilitar por defecto campos excepto RUC y Direccion2..5
    try {
        const manualToggle = document.getElementById('manualToggle');
        const empresaInput = document.getElementById('Empresa');
        const direccionInput = document.getElementById('Direccion');
        const dir2 = document.getElementById('Direccion2');
        const dir3 = document.getElementById('Direccion3');
        const dir4 = document.getElementById('Direccion4');
        const dir5 = document.getElementById('Direccion5');
        // Por defecto desmarcado y campos principales deshabilitados
            if (manualToggle) {
            manualToggle.checked = false;
            if (empresaInput) { empresaInput.readOnly = true; empresaInput.classList.add('auto-filled-readonly'); }
            if (direccionInput) { direccionInput.readOnly = true; direccionInput.classList.add('auto-filled-readonly'); }
            // Direccion2..5 se mantienen habilitadas según requerimiento
            manualToggle.addEventListener('change', function() {
                const enabled = manualToggle.checked;
                if (empresaInput) {
                    empresaInput.readOnly = !enabled;
                    if (!enabled) empresaInput.classList.add('auto-filled-readonly'); else empresaInput.classList.remove('auto-filled-readonly');
                }
                if (direccionInput) {
                    direccionInput.readOnly = !enabled;
                    if (!enabled) direccionInput.classList.add('auto-filled-readonly'); else direccionInput.classList.remove('auto-filled-readonly');
                }
            });
        }
    } catch(e) { console.warn('toggle manual error (create clientesexternos)', e); }
    btn.addEventListener('click', async function (e) {
        e.preventDefault();
        const rucInput = document.getElementById('RUC');
        const empresaInput = document.getElementById('Empresa');
        const direccionInput = document.getElementById('Direccion');
        const ruc = (rucInput.value || '').trim();
        if (ruc.length !== 11 || isNaN(ruc)) {
            mostrarMensajeRUC('Ingrese un RUC válido de 11 dígitos.');
            try { rucInput.focus(); rucInput.select(); } catch(e){}
            return;
        }
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Consultando...';
        try {
            // Primero: verificar en el servidor si ya existe el RUC
            try {
                const chk = await fetch('<?= app_url("clientesexternos/checkRuc") ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ruc: ruc })
                });
                const chkText = await chk.text();
                let chkJson = null;
                try { chkJson = chkText ? JSON.parse(chkText) : null; } catch(e) { chkJson = { success: false }; }
                console.debug('checkRuc early response:', chkJson);
                if (chkJson && chkJson.success === true && chkJson.exists) {
                    mostrarMensajeRUC('El RUC ingresado ya existe en otro registro.');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    return;
                }
            } catch (e) { console.warn('checkRuc early failed', e); }

            // Luego: consultar la API proxy del backend para completar datos
            const resp = await fetch(window.BASE_URL + '/api/consultarRuc', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ruc })
            });
            const data = await resp.json().catch(() => null);
            const rucHelp = document.getElementById('rucHelp');
            if (data && data.success && data.razon_social) {
                empresaInput.value = (data.razon_social || '').toUpperCase();
                if (direccionInput) direccionInput.value = (data.direccion || '').toUpperCase();
                if (rucHelp) {
                    rucHelp.textContent = 'Datos cargados desde SUNAT.';
                    rucHelp.classList.remove('text-danger');
                    rucHelp.classList.add('text-success');
                }
                // asegurarnos que los campos sigan con la clase de solo lectura
                if (empresaInput) empresaInput.classList.add('auto-filled-readonly');
                if (direccionInput) direccionInput.classList.add('auto-filled-readonly');
            } else {
                mostrarMensajeRUC('No se encontró razón social para el RUC ingresado.');
                try { empresaInput.value = ''; if (direccionInput) direccionInput.value = ''; } catch(e){}
            }
        } catch (err) {
            console.error('Error consultando RUC:', err);
            mostrarMensajeRUC('No se encontró razón social para el RUC ingresado.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
})();
// Helper para mostrar mensaje si no existe aún
function mostrarMensajeRUC(mensaje) {
    let div = document.getElementById('mensajeRUC');
    const rucHelp = document.getElementById('rucHelp');
    const empresaInput = document.getElementById('Empresa');
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
        if (rucHelp && rucHelp.parentNode) {
            rucHelp.parentNode.insertBefore(div, rucHelp.nextSibling);
        } else if (empresaInput) {
            empresaInput.parentNode.insertBefore(div, empresaInput.nextSibling);
        }
    }
    div.textContent = mensaje;
    setTimeout(() => { div.remove(); }, 4000);
}
</script>
