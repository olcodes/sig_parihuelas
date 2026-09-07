<div class="container-fluid d-flex justify-content-center align-items-center" style="min-height:80vh;">
    <div class="card shadow-sm border-0" style="width:100%; max-width:480px;">
        <div class="card-body p-4">
            <div class="mb-4 d-flex align-items-center">
                <i class="bi bi-person-plus display-6 text-success me-2"></i>
                <div class="d-flex flex-column">
                    <h3 class="mb-0 fw-bold" style="letter-spacing:0.5px; color:#1a237e;">Agregar Chofer</h3>
                    <a href="https://slcp.mtc.gob.pe/" target="_blank" rel="noopener noreferrer" class="small text-decoration-none text-primary mt-1">Link de Búsqueda (SLCP)</a>
                </div>
                <div class="form-check form-switch ms-auto">
                    <input class="form-check-input" type="checkbox" id="manualToggle" disabled>
                    <label class="form-check-label small text-muted ms-1" for="manualToggle">Manual</label>
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
            <form method="post" action="<?= app_url('choferes/store') ?>" id="formChofer" autocomplete="off">
                <!-- Campo unificado para enviar al servidor (DocIdentidad) -->
                <input type="hidden" id="DocIdentidad" name="DocIdentidad" value="<?= htmlspecialchars($DocIdentidad ?? '') ?>">
                <div class="mb-3" style="max-width:480px; margin:0 auto;">
                    <label for="Nacionalidad" class="form-label">Nacionalidad</label>
                    <select class="form-select form-select-lg w-100" id="Nacionalidad" name="Nacionalidad" required style="max-width:480px; width:100%;">
                        <option value="" disabled selected></option>
                        <option value="PERUANO">PERUANO</option>
                        <option value="EXTRANJERO">EXTRANJERO</option>
                    </select>
                </div>
                <div id="documentoGroup"></div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="ApellidosPaterno" class="form-label">Apellido Paterno</label>
                        <input type="text" class="form-control form-control-lg w-100" id="ApellidosPaterno" name="ApellidosPaterno" required value="<?= htmlspecialchars($ApellidosPaterno ?? '') ?>" style="text-transform:uppercase; width:100%; max-width:480px;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="ApellidoMaterno" class="form-label">Apellido Materno</label>
                        <input type="text" class="form-control form-control-lg w-100" id="ApellidoMaterno" name="ApellidoMaterno" required value="<?= htmlspecialchars($ApellidoMaterno ?? '') ?>" style="text-transform:uppercase; width:100%; max-width:480px;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="Nombres" class="form-label">Nombres</label>
                        <input type="text" class="form-control form-control-lg w-100" id="Nombres" name="Nombres" required value="<?= htmlspecialchars($Nombres ?? '') ?>" style="text-transform:uppercase; width:100%; max-width:480px;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="ApellidosNombres" class="form-label">Apellidos y Nombres</label>
                        <input type="text" class="form-control form-control-lg bg-light w-100" id="ApellidosNombres" name="ApellidosNombres" value="<?= htmlspecialchars($ApellidosNombres ?? '') ?>" readonly style="text-transform:uppercase; width:100%; max-width:480px;">
                    </div>
                </div>
                <div class="mb-4 d-flex justify-content-center">
                    <div style="width:100%; max-width:480px; margin:0 auto;">
                        <label for="Brevete" class="form-label">Brevete</label>
                        <input type="text" class="form-control form-control-lg w-100" id="Brevete" name="Brevete" required value="<?= htmlspecialchars($Brevete ?? '') ?>" style="text-transform:uppercase; width:100%; max-width:480px;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="bi bi-check-circle me-1"></i>Guardar</button>
                    <a href="<?= app_url('choferes') ?>" class="btn btn-secondary px-4"><i class="bi bi-arrow-left me-1"></i>Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const nacionalidadSelect = document.getElementById('Nacionalidad');
        const documentoGroup = document.getElementById('documentoGroup');
        const apellidosNombresInput = document.getElementById('ApellidosNombres');
        const breveteInput = document.getElementById('Brevete');
        const apPaternoInput = document.getElementById('ApellidosPaterno');
        const apMaternoInput = document.getElementById('ApellidoMaterno');
        const nombresInput = document.getElementById('Nombres');

        // Bloquear campos solicitados al cargar y preparar toggle Manual
            try {
            if (apPaternoInput) apPaternoInput.readOnly = true;
            if (apMaternoInput) apMaternoInput.readOnly = true;
            if (nombresInput) nombresInput.readOnly = true;
            if (apellidosNombresInput) apellidosNombresInput.readOnly = true; // ya lo estaba, reforzamos
            if (breveteInput) breveteInput.readOnly = true;

            const manualToggle = document.getElementById('manualToggle');
            if (manualToggle) {
                manualToggle.checked = false; // por defecto sin marcar
                manualToggle.addEventListener('change', function() {
                    const enabled = manualToggle.checked;
                    if (apPaternoInput) apPaternoInput.readOnly = !enabled;
                    if (apMaternoInput) apMaternoInput.readOnly = !enabled;
                    if (nombresInput) nombresInput.readOnly = !enabled;
                    if (breveteInput) breveteInput.readOnly = !enabled;
                    // no habilitamos el campo compuesto (ApellidosNombres) que debe seguir readonly
                    // Si se deshabilitan (se marca manual=false), actualizar el campo compuesto
                    if (!enabled && typeof updateApellidosNombres === 'function') {
                        updateApellidosNombres();
                    }
                });
            }
        } catch(e) { console.warn('No se pudo bloquear campos iniciales', e); }

        function setIndividualNameFields(apPaterno, apMaterno, nombres) {
            if (apPaternoInput && typeof apPaterno === 'string' && apPaterno.trim() !== '') {
                apPaternoInput.value = apPaterno.trim().toUpperCase();
            }
            if (apMaternoInput && typeof apMaterno === 'string' && apMaterno.trim() !== '') {
                apMaternoInput.value = apMaterno.trim().toUpperCase();
            }
            if (nombresInput && typeof nombres === 'string' && nombres.trim() !== '') {
                nombresInput.value = nombres.trim().toUpperCase();
            }
            // También actualizar el campo compuesto
            if (apellidosNombresInput) {
                const parts = [apPaterno || '', apMaterno || '', nombres || ''].join(' ').trim();
                if (parts) apellidosNombresInput.value = parts.toUpperCase();
            }
        }

        function clearPersonFields() {
            try {
                if (apPaternoInput) apPaternoInput.value = '';
                if (apMaternoInput) apMaternoInput.value = '';
                if (nombresInput) nombresInput.value = '';
                if (apellidosNombresInput) apellidosNombresInput.value = '';
                if (breveteInput) { breveteInput.value = ''; breveteInput.readOnly = true; }
                const hiddenDoc = document.getElementById('DocIdentidad');
                if (hiddenDoc) hiddenDoc.value = '';
                // reset manual toggle programmatically
                if (manualToggle) { manualToggle.checked = false; manualToggle.dispatchEvent(new Event('change')); }
            } catch(e) { console.warn('clearPersonFields error', e); }
        }

        function parseFullNameAndSet(fullName) {
            if (!fullName || typeof fullName !== 'string') return;
            // Limpiar comas que puedan venir de la API
            const cleaned = fullName.replace(/,/g, ' ').trim();
            const toks = cleaned.split(/\s+/).filter(t => t.length > 0);
            if (toks.length === 0) return;
            // Heurística simple: primer token = paterno, segundo = materno, resto = nombres
            const apPaterno = toks[0] || '';
            const apMaterno = toks[1] || '';
            const nombres = toks.slice(2).join(' ') || '';
            setIndividualNameFields(apPaterno, apMaterno, nombres);
        }

        function renderDocumentoField() {
            if (!documentoGroup) return;
            documentoGroup.innerHTML = '';
            // No mostrar controles hasta que el usuario seleccione una nacionalidad
            if (!nacionalidadSelect.value) {
                documentoGroup.innerHTML = '';
                return;
            }
            if (nacionalidadSelect.value === 'PERUANO') {
                documentoGroup.innerHTML = `
                <div class="mb-3 d-flex justify-content-center">\n            <div style="width:100%; max-width:480px; margin:0 auto;">\n                <label for="Dni" class="form-label">DNI<\/label>\n                <div class="input-group input-group-lg">\n                    <input type="text" class="form-control" id="Dni" name="Dni" maxlength="8" style="text-transform:uppercase;" autocomplete="off" placeholder="Ingrese DNI">\n                    <button type="button" class="btn btn-outline-info" id="btnConsultarDni" title="Consultar DNI" style="min-width:110px; font-weight:600; border-width:2px;">Consultar<\/button>\n                <\/div>\n            <\/div>\n        <\/div>`;
            } else {
                documentoGroup.innerHTML = `
                <div class="mb-3 d-flex justify-content-center">\n            <div style="width:100%; max-width:480px; margin:0 auto;">\n                <label for="CarnetExtranjeria" class="form-label">Carnet de Extranjería<\/label>\n                <div class="input-group input-group-lg">\n                    <input type="text" class="form-control" id="CarnetExtranjeria" name="CarnetExtranjeria" maxlength="9" style="text-transform:uppercase;" placeholder="Ingrese número de carnet" autocomplete="off">\n                    <button type="button" class="btn btn-outline-info" id="btnConsultarCarnet" title="Consultar Carnet" style="min-width:110px; font-weight:600; border-width:2px;">Consultar<\/button>\n                <\/div>\n            <\/div>\n        <\/div>`;
            }
            setTimeout(asignarEventosDocumento, 0);
        }

        function asignarEventosDocumento() {
            const dniInput = document.getElementById('Dni');
            const btnConsultarDni = document.getElementById('btnConsultarDni');
            const hiddenDoc = document.getElementById('DocIdentidad');
            if (btnConsultarDni && dniInput) {
                // Attach to a named global handler so we can debug/trigger it reliably
                window.consultarDni = function() {
                    try {
                        const dniVal = (document.getElementById('Dni')?.value || '').trim();
                        if (hiddenDoc) hiddenDoc.value = dniVal;
                        console.debug('consultarDni called, dni=', dniVal);
                        if (dniVal.length !== 8 || isNaN(dniVal)) {
                            mostrarMensajeDni('Ingrese un DNI válido de 8 dígitos.');
                            document.getElementById('Dni').focus();
                            return;
                        }
                        // Limpiar campos antes de iniciar la búsqueda
                        try {
                            if (apPaternoInput) apPaternoInput.value = '';
                            if (apMaternoInput) apMaternoInput.value = '';
                            if (nombresInput) nombresInput.value = '';
                            if (apellidosNombresInput) apellidosNombresInput.value = '';
                            if (breveteInput) breveteInput.value = '';
                        } catch (e) { console.warn('No se pudo limpiar campos antes de consultar DNI', e); }

                        btnConsultarDni.disabled = true;
                        btnConsultarDni.textContent = 'Consultando...';

                        // bandera para detectar si alguna respuesta devolvió información útil
                        let foundInfo = false;
                        // indica si la primera consulta por DNI devolvió nombres/apellidos (aunque no tenga brevete)
                        let initialHasName = false;
                        // indica si la primera respuesta (proxy) ya trajo brevete
                        let initialHasBrevete = false;

                        // Primera llamada: obtener apellidos/nombres
                        try {
                            console.debug('[DNI] iniciando fetch a factiliza, dni=', dniVal);
                        } catch(e){}
                        fetch(window.APP_URL + '/api/consultarDni', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ dni: dniVal })
                        })
                        .then(function(r){
                            return r.text().then(function(text){
                                // No volcar el HTML crudo en consola para evitar trazas largas.
                                try {
                                    return JSON.parse(text);
                                } catch (err) {
                                    console.warn('Respuesta no-JSON desde backend; se intentará extraer nombres del HTML.');
                                    return { __raw: text };
                                }
                            });
                        })
                        .then(data => {
                            // Si la respuesta fue non-JSON pero devolvimos {__raw}, intentar extraer nombres
                            if (data && data.__raw) {
                                try {
                                    let raw = data.__raw.replace(/<[^>]*>/g, ' ');
                                    raw = raw.replace(/\s+/g, ' ').trim();
                                    // seleccionar la línea más larga como candidato
                                    const lines = raw.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
                                    let candidate = lines.length ? lines.reduce((a,b) => a.length >= b.length ? a : b, '') : raw;
                                    candidate = candidate.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, ' ').replace(/\s+/g, ' ').trim();
                                    if (candidate.length >= 4 && candidate.split(/\s+/).length >= 2) {
                                        // Filtrar outputs que claramente son errores o stack traces
                                        const blacklist = /fatal|uncaught|typeerror|call to|on line|stack trace|deprecated|warning|notice|php|index\(|<\/html>/i;
                                        if (!blacklist.test(candidate)) {
                                            const toksRaw = candidate.split(/\s+/);
                                            // heurística: dos últimos tokens = apellidos
                                            const apPR = toksRaw[toksRaw.length - 2] || '';
                                            const apMR = toksRaw[toksRaw.length - 1] || '';
                                            const nomsR = toksRaw.slice(0, toksRaw.length - 2).join(' ');
                                            setIndividualNameFields(apPR, apMR, nomsR || candidate);
                                            apellidosNombresInput.value = candidate.toUpperCase();
                                            foundInfo = true;
                                            initialHasName = true;
                                        } else {
                                            console.warn('Raw response parece error; ignorando candidato de nombre:', candidate);
                                        }
                                    }
                                } catch (e) { console.warn('No se pudo extraer nombre del raw response', e); }
                            }

                            console.debug('DNI proxy response', data);
                            // Si la respuesta incluye estructura data (dni) usarla para nombres
                            // Priorizar campos separados desde la API cuando estén disponibles
                            if (data) {
                                const src = data.data || data; // data.data es la respuesta DNI; data raíz puede contener licencia
                                const api_ap = (src.apellido_paterno || src.apellidoPaterno || '').trim();
                                const api_am = (src.apellido_materno || src.apellidoMaterno || '').trim();
                                const api_nombres = (src.nombres || src.nombre || src.nombre_completo || '').trim();
                                const apellidosNombres = (api_ap + ' ' + api_am + ' ' + api_nombres).trim();
                                if (apellidosNombres) {
                                    apellidosNombresInput.value = apellidosNombres.toUpperCase();
                                    foundInfo = true;
                                    initialHasName = true;
                                }
                                // Si el proxy devuelve `apellidos_nombres` en la raíz, usarlo también
                                if (!initialHasName && data.apellidos_nombres) {
                                    try {
                                        apellidosNombresInput.value = (data.apellidos_nombres || '').toUpperCase();
                                        parseFullNameAndSet(data.apellidos_nombres);
                                        foundInfo = true;
                                        initialHasName = true;
                                    } catch (e) { console.warn('parseFullNameAndSet error on apellidos_nombres', e); }
                                }
                                // Si la API devuelve los apellidos separados, usarlos.
                                if (api_ap || api_am) {
                                    setIndividualNameFields(api_ap, api_am, api_nombres);
                                    foundInfo = true;
                                    initialHasName = true;
                                } else if (api_nombres) {
                                    // Caso común: la API devuelve el nombre completo dentro de `nombres`.
                                    // Heurística: asumir que los DOS ÚLTIMOS tokens son apellidos (paterno, materno)
                                    // y el resto son los nombres. Esto funciona mejor cuando la cadena viene
                                    // como "Nombres ApellidoPaterno ApellidoMaterno".
                                    const toks = api_nombres.trim().split(/\s+/);
                                    if (toks.length >= 2) {
                                        const apP = toks[toks.length - 2] || '';
                                        const apM = toks[toks.length - 1] || '';
                                        const noms = toks.slice(0, toks.length - 2).join(' ');
                                        setIndividualNameFields(apP, apM, noms || api_nombres);
                                        foundInfo = true;
                                        initialHasName = true;
                                    } else {
                                        setIndividualNameFields('', '', api_nombres);
                                        foundInfo = true;
                                        initialHasName = true;
                                    }
                                }
                            }
                            // asegurar que el hidden tenga el valor del documento consultado
                            if (hiddenDoc) hiddenDoc.value = dniVal;
                            // Si la respuesta ya trae numero_brevete (enriquecida por el proxy), setear y evitar segunda llamada
                            if (data && data.numero_brevete) {
                                breveteInput.value = (data.numero_brevete || '').toUpperCase();
                                foundInfo = true;
                                initialHasBrevete = true;
                                // si la respuesta trajo nombre completo o campos separados desde la licencia, aplicarlo
                                if (data.apellidos_nombres) {
                                    apellidosNombresInput.value = (data.apellidos_nombres || '').toUpperCase();
                                    parseFullNameAndSet(data.apellidos_nombres);
                                }
                                // también priorizar campos separados si vienen en la raíz
                                if (data.apellido_paterno || data.apellido_materno || data.nombres) {
                                    const api_ap = (data.apellido_paterno || '').trim();
                                    const api_am = (data.apellido_materno || '').trim();
                                    const api_nombres = (data.nombres || '').trim();
                                    setIndividualNameFields(api_ap, api_am, api_nombres);
                                }
                                // después de obtener brevete desde proxy, validar si ya existe
                                checkBrevete(breveteInput.value).then(cb => {
                                    if (cb.exists) {
                                        mostrarMensajeDni(cb.message || 'El brevete ya está registrado para otro chofer.');
                                    }
                                });
                                return null; // no hacer segunda llamada
                            }
                            // Si no hay numero_brevete en la respuesta, realizar la consulta separada a consultarBrevete
                            return fetch(window.APP_URL + '/api/consultarBrevete', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ brevete: dniVal })
                            }).then(r2 => r2.json());
                        })
                        .then(res => {
                            // bandera para saber si la consulta de brevete devolvió número
                            let breveteFound = false;
                            if (!res) {
                                // si no hubo información en la consulta de brevete
                                // Si la primera respuesta ya trajo brevete, no mostrar mensaje
                                if (initialHasBrevete) {
                                    return;
                                }
                                if (initialHasName) {
                                    // El DNI existe y devolvió nombres, pero no encontró brevete
                                    mostrarMensajeDni('El documento no cuenta con brevete.');
                                    try {
                                        // Marcar toggle como checked PERO NO disparar el evento change (que habilita todos los campos)
                                        if (manualToggle) manualToggle.checked = true;
                                        // Solo habilitar el campo brevete manualmente, los nombres ya están llenos
                                        if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); }
                                    } catch(e) { console.warn('No se pudo habilitar modo manual', e); }
                                } else if (!foundInfo) {
                                    // No se encontró información en ninguna consulta
                                    mostrarMensajeDni('No se encontró información para el documento solicitado.');
                                    try {
                                        if (manualToggle) { manualToggle.checked = true; manualToggle.dispatchEvent(new Event('change')); }
                                            try {
                                                if (apPaternoInput) apPaternoInput.readOnly = false;
                                                if (apMaternoInput) apMaternoInput.readOnly = false;
                                                if (nombresInput) nombresInput.readOnly = false;
                                                if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); }
                                            } catch(e) { console.warn('No se pudo forzar habilitación de campos create', e); }
                                    } catch(e) { console.warn('No se pudo habilitar modo manual tras no encontrar info DNI', e); }
                                }
                                return;
                            }
                            console.debug('consultarBrevete response', res);
                            if (res) {
                                // Priorizar campos separados en la respuesta de consultarBrevete
                                const api_ap = (res.apellido_paterno || res.apellidoPaterno || '').trim();
                                const api_am = (res.apellido_materno || res.apellidoMaterno || '').trim();
                                const api_nombres = (res.nombres || res.nombre || res.apellidos_nombres || '').trim();
                                if (res.numero_brevete) {
                                    breveteInput.value = (res.numero_brevete || '').toUpperCase();
                                    breveteFound = true;
                                    foundInfo = true;
                                    // validar brevete si viene en la respuesta
                                    checkBrevete(breveteInput.value).then(cb => {
                                        if (cb.exists) {
                                            mostrarMensajeDni(cb.message || 'El brevete ya está registrado para otro chofer.');
                                        }
                                    });
                                }
                                if (api_ap || api_am) {
                                    setIndividualNameFields(api_ap, api_am, api_nombres);
                                    foundInfo = true;
                                } else if (api_nombres) {
                                    const toks2 = api_nombres.trim().split(/\s+/);
                                    if (toks2.length >= 2) {
                                        const apP2 = toks2[toks2.length - 2] || '';
                                        const apM2 = toks2[toks2.length - 1] || '';
                                        const noms2 = toks2.slice(0, toks2.length - 2).join(' ');
                                        setIndividualNameFields(apP2, apM2, noms2 || api_nombres);
                                        foundInfo = true;
                                    } else {
                                        setIndividualNameFields('', '', api_nombres);
                                        foundInfo = true;
                                    }
                                } else if (res.apellidos_nombres) {
                                    apellidosNombresInput.value = (res.apellidos_nombres || '').toUpperCase();
                                    parseFullNameAndSet(res.apellidos_nombres);
                                    foundInfo = true;
                                }
                                // Si el DNI existía (initialHasName) pero no obtuvimos brevete, informar al usuario
                                if (initialHasName && !breveteFound) {
                                    // asegurarnos que el campo brevete quede vacío
                                    try { breveteInput.value = ''; } catch (e) {}
                                    mostrarMensajeDni('El documento no cuenta con brevete.');
                                    try {
                                        // Marcar toggle como checked PERO NO disparar el evento change
                                        if (manualToggle) manualToggle.checked = true;
                                        // Solo habilitar el campo brevete manualmente, los nombres ya están llenos
                                        if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); }
                                    } catch(e) { console.warn('No se pudo habilitar modo manual', e); }
                                }
                            }
                        })
                        .catch(err => {
                            console.error('Error en consultas DNI/brevete', err);
                            if (!foundInfo) mostrarMensajeDni('No se pudo consultar el DNI o brevete.');
                            // Si falló la consulta por red/502, habilitar modo manual para ingreso
                            try {
                                if (manualToggle) { manualToggle.checked = true; manualToggle.dispatchEvent(new Event('change')); }
                                if (apPaternoInput) apPaternoInput.readOnly = false;
                                if (apMaternoInput) apMaternoInput.readOnly = false;
                                if (nombresInput) nombresInput.readOnly = false;
                                if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); }
                            } catch(e) { console.warn('No se pudo habilitar modo manual tras error DNI', e); }
                        })
                        .finally(() => {
                            // Si no encontramos nada y no se mostró mensaje antes, mostrar uno genérico
                            if (!foundInfo) {
                                // evitar sobrescribir mensajes más específicos mostrados antes
                                mostrarMensajeDni('No se encontró información para el documento solicitado.');
                                try {
                                    if (manualToggle) { manualToggle.checked = true; manualToggle.dispatchEvent(new Event('change')); }
                                    if (apPaternoInput) apPaternoInput.readOnly = false;
                                    if (apMaternoInput) apMaternoInput.readOnly = false;
                                    if (nombresInput) nombresInput.readOnly = false;
                                    if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); }
                                } catch(e) { console.warn('No se pudo habilitar modo manual en finally DNI', e); }
                            }
                            btnConsultarDni.disabled = false;
                            btnConsultarDni.textContent = 'Consultar';
                        });
                    } catch (e) {
                        console.error('Error en window.consultarDni', e);
                    }
                };
                btnConsultarDni.addEventListener('click', function(ev) {
                    try { ev.stopPropagation(); } catch(e) {}
                    // pequeño debounce local para evitar doble disparo por listener delegado
                    if (window._consultarDniBtnPending) return;
                    window._consultarDniBtnPending = true;
                    setTimeout(() => { window._consultarDniBtnPending = false; }, 300);
                    if (typeof window.consultarDni === 'function') window.consultarDni();
                });
                // Delegated fallback: si el listener original no se atan por alguna razón
                document.addEventListener('click', function (ev) {
                    if (ev.target && ev.target.id === 'btnConsultarDni') {
                        // small debounce
                        if (window._consultarDniPending) return;
                        window._consultarDniPending = true;
                        setTimeout(() => { window._consultarDniPending = false; }, 300);
                        if (typeof window.consultarDni === 'function') window.consultarDni();
                    }
                });
                    // Mantener el hidden sincronizado mientras se escribe
                    if (dniInput && hiddenDoc) {
                        dniInput.addEventListener('input', function() { hiddenDoc.value = dniInput.value.trim(); });
                    }
            }
            

            const carnetInput = document.getElementById('CarnetExtranjeria');
            const btnConsultarCarnet = document.getElementById('btnConsultarCarnet');
            const hiddenDoc2 = document.getElementById('DocIdentidad');
            if (btnConsultarCarnet && carnetInput) {
                btnConsultarCarnet.addEventListener('click', function() {
                    const carnet = carnetInput.value.trim();
                    if (hiddenDoc2) hiddenDoc2.value = carnet;
                    // Limpiar brevete por defecto antes de consultar
                    try { document.getElementById('Brevete').value = ''; } catch(e) {}
                    if (carnet.length < 8) {
                        mostrarMensajeDni('Ingrese un número de carnet válido.');
                        carnetInput.focus();
                        return;
                    }
                    btnConsultarCarnet.disabled = true;
                    btnConsultarCarnet.textContent = 'Consultando...';
                    fetch(window.APP_URL + '/api/consultarCarnet', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ carnet: carnet })
                    })
                    .then(async resp => {
                        let data = null;
                        let errorMostrado = false;
                        try {
                            data = await resp.json();
                        } catch (e) {
                            mostrarMensajeDni('No se encontró información para este carnet.');
                            errorMostrado = true;
                        }
                        if (!errorMostrado) {
                            if (data && data.success && data.apellidos_nombres) {
                                apellidosNombresInput.value = data.apellidos_nombres.toUpperCase();
                                // separar en campos individuales (Apellido Paterno, Apellido Materno, Nombres)
                                try { parseFullNameAndSet(data.apellidos_nombres); } catch(e) { console.warn('parseFullNameAndSet error', e); }
                                // asignar brevete si viene
                                if (data.numero_brevete) {
                                    try { document.getElementById('Brevete').value = (data.numero_brevete || '').toUpperCase(); } catch(e) {}
                                } else {
                                    // dejar brevete vacío si no viene
                                    try { document.getElementById('Brevete').value = ''; } catch(e) {}
                                }
                                // Si obtuvimos nombres pero no brevete, comportarnos igual que para DNI: informar y activar modo manual
                                try {
                                    if (!data.numero_brevete) {
                                        mostrarMensajeDni('El documento no cuenta con brevete.');
                                        // Marcar toggle como checked PERO NO disparar el evento change
                                        if (manualToggle) manualToggle.checked = true;
                                        // Solo habilitar el campo brevete manualmente, los nombres ya están llenos
                                        if (breveteInput) { breveteInput.readOnly = false; breveteInput.focus(); }
                                    }
                                } catch(e) { console.warn('No se pudo habilitar modo manual para carnet', e); }
                            } else {
                                // limpiar brevete si no se encontró info
                                try { document.getElementById('Brevete').value = ''; } catch(e) {}
                                mostrarMensajeDni((data && data.message) || 'No se encontró información para este carnet.');
                                try {
                                    if (manualToggle) { manualToggle.checked = true; manualToggle.dispatchEvent(new Event('change')); }
                                    if (apPaternoInput) apPaternoInput.focus();
                                } catch(e) { console.warn('No se pudo habilitar modo manual tras no encontrar info carnet', e); }
                            }
                        }
                        btnConsultarCarnet.disabled = false;
                        btnConsultarCarnet.textContent = 'Consultar';
                    })
                    .catch(() => {
                        mostrarMensajeDni('No se pudo consultar el carnet.');
                        btnConsultarCarnet.disabled = false;
                        btnConsultarCarnet.textContent = 'Consultar';
                    });
                });
                // mantener hidden sincronizado mientras se escribe
                if (carnetInput && hiddenDoc2) {
                    carnetInput.addEventListener('input', function() { hiddenDoc2.value = carnetInput.value.trim(); });
                }
            }
        }

        renderDocumentoField();
        nacionalidadSelect.addEventListener('change', function() {
            clearPersonFields();
            renderDocumentoField();
        });

        function mostrarMensajeDni(mensaje) {
            // Buscar el input visible (DNI o Carnet)
            const dniInput = document.getElementById('Dni');
            const carnetInput = document.getElementById('CarnetExtranjeria');
            const input = (dniInput && dniInput.offsetParent !== null) ? dniInput : carnetInput;
            if (!input) return;
            let div = document.getElementById('mensajeDni');
            if (!div) {
                div = document.createElement('div');
                div.id = 'mensajeDni';
                div.style.marginBottom = '10px';
                div.style.padding = '12px';
                div.style.background = '#fff3cd';
                div.style.color = '#856404';
                div.style.border = '1px solid #ffeeba';
                div.style.borderRadius = '5px';
                div.style.fontWeight = 'bold';
                div.style.fontSize = '1rem';
                div.style.textAlign = 'center';
                input.parentNode.parentNode.insertBefore(div, input.parentNode);
            }
            div.textContent = mensaje;
            setTimeout(() => { div.remove(); }, 4000);
        }

        // Helper: comprobar brevete duplicado vía AJAX al controlador
        function checkBrevete(brevete) {
            return new Promise((resolve) => {
                if (!brevete) return resolve({ success: false, exists: false });
                const body = new URLSearchParams();
                body.append('brevete', brevete);
                fetch(window.APP_URL + '/choferes/checkBrevete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                .then(r => r.json())
                .then(json => resolve(json))
                .catch(err => {
                    console.error('Error comprobando brevete', err);
                    resolve({ success: false, exists: false });
                });
            });
        }
    });
    </script>
<script>
function updateApellidosNombres() {
    const ap = document.getElementById('ApellidosPaterno')?.value.trim() || '';
    const am = document.getElementById('ApellidoMaterno')?.value.trim() || '';
    const n = document.getElementById('Nombres')?.value.trim() || '';
    const an = document.getElementById('ApellidosNombres');
    if (an) {
        an.value = (ap + ' ' + am + ' ' + n).trim();
    }
}
if (document.getElementById('ApellidosPaterno')) {
    document.getElementById('ApellidosPaterno').addEventListener('input', updateApellidosNombres);
}
if (document.getElementById('ApellidoMaterno')) {
    document.getElementById('ApellidoMaterno').addEventListener('input', updateApellidosNombres);
}
if (document.getElementById('Nombres')) {
    document.getElementById('Nombres').addEventListener('input', updateApellidosNombres);
}

// Validar longitud exacta del documento antes de guardar
const formChofer = document.getElementById('formChofer');
if (formChofer) {
    formChofer.addEventListener('submit', function(e) {
        const nacionalidad = document.getElementById('Nacionalidad')?.value;
        const docIdentidad = document.getElementById('DocIdentidad')?.value.trim();
        const brevete = document.getElementById('Brevete')?.value.trim();
        
        if (nacionalidad === 'PERUANO') {
            // DNI debe tener exactamente 8 caracteres
            if (!docIdentidad || docIdentidad.length !== 8) {
                e.preventDefault();
                alert('El DNI debe tener exactamente 8 dígitos.');
                const dniInput = document.getElementById('Dni');
                if (dniInput) dniInput.focus();
                return false;
            }
            // Brevete no puede ser mayor a 9 caracteres para peruanos
            if (brevete && brevete.length > 9) {
                e.preventDefault();
                alert('El Brevete no puede tener más de 9 caracteres para un ciudadano peruano.');
                const breveteInput = document.getElementById('Brevete');
                if (breveteInput) breveteInput.focus();
                return false;
            }
            // Brevete debe contener al menos una letra
            if (brevete && !/[A-Za-z]/.test(brevete)) {
                e.preventDefault();
                alert('El Brevete debe contener al menos una letra.');
                const breveteInput = document.getElementById('Brevete');
                if (breveteInput) breveteInput.focus();
                return false;
            }
        } else if (nacionalidad === 'EXTRANJERO') {
            // Carnet debe tener exactamente 9 caracteres
            if (!docIdentidad || docIdentidad.length !== 9) {
                e.preventDefault();
                alert('El Carnet de Extranjería debe tener exactamente 9 caracteres.');
                const carnetInput = document.getElementById('CarnetExtranjeria');
                if (carnetInput) carnetInput.focus();
                return false;
            }
            // Brevete no puede ser mayor a 10 caracteres para extranjeros
            if (brevete && brevete.length > 10) {
                e.preventDefault();
                alert('El Brevete no puede tener más de 10 caracteres para un ciudadano extranjero.');
                const breveteInput = document.getElementById('Brevete');
                if (breveteInput) breveteInput.focus();
                return false;
            }
            // Brevete debe contener al menos una letra
            if (brevete && !/[A-Za-z]/.test(brevete)) {
                e.preventDefault();
                alert('El Brevete debe contener al menos una letra.');
                const breveteInput = document.getElementById('Brevete');
                if (breveteInput) breveteInput.focus();
                return false;
            }
        }
    });
}
</script>
</div>
<style>
    .card label.form-label {
        font-weight: 500;
        color: #374151;
    }
    .card .form-control-lg {
        font-size: 1.08rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
    }
    .card .form-control-lg:focus {
        box-shadow: 0 0 0 0.15rem #b6d4fe;
        border-color: #1976d2;
    }
    .card .btn-success, .card .btn-secondary {
        font-size: 1.05rem;
        border-radius: 0.45rem;
        box-shadow: 0 1px 2px rgba(60,60,60,0.04);
    }
    #btnConsultarDni.btn-outline-info, #btnConsultarCarnet.btn-outline-info {
    background: #fff;
    color: #0dcaf0;
    border-color: #0dcaf0;
    transition: background 0.18s, color 0.18s;
}
#btnConsultarDni.btn-outline-info:hover, #btnConsultarDni.btn-outline-info:focus,
#btnConsultarCarnet.btn-outline-info:hover, #btnConsultarCarnet.btn-outline-info:focus {
    background: #0dcaf0;
    color: #000;
    border-color: #0dcaf0;
}
/* Alinear apariencia de campos readonly/disabled en el formulario crear chofer */
#formChofer input[readonly],
#formChofer .form-control[readonly],
#formChofer input:disabled,
#formChofer .form-control:disabled {
    background-color: var(--bs-secondary-bg) !important;
    color: var(--bs-secondary-color) !important;
    border-color: var(--bs-light-border-subtle) !important;
    opacity: 1 !important;
}
</style>

</style>
