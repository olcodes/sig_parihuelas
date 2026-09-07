// public/js/despachosinternos.js
// Lógica centralizada: inicialización Choices.js, combos, grilla, botones y estilos
document.addEventListener('DOMContentLoaded', function() {
    // Configurar fecha actual como fecha máxima por defecto
    configurarFechaInput();
    
    // Función para cargar un despacho existente y preparar para modificar
    // Función para configurar el campo de fecha con restricciones
    function configurarFechaInput(fechaRegistro) {
        const inputFecha = document.getElementById('fecha');
        if (inputFecha) {
            // Establecer fecha máxima como la fecha del registro
            // Esto impedirá seleccionar fechas posteriores a la fecha original del registro
            if (fechaRegistro) {
                inputFecha.setAttribute('max', fechaRegistro);
            } else {
                // Si no hay fecha del registro (caso nuevo), limitar a la fecha actual
                const hoy = new Date();
                const formatoHoy = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-' + String(hoy.getDate()).padStart(2, '0'); // YYYY-MM-DD local
                inputFecha.setAttribute('max', formatoHoy);
            }
        }
    }

    window.cargarDespachoParaEditar = function(id) {
        fetch((window.APP_URL || window.BASE_URL) + '/despachosinternos/getDespacho', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'Id=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                // Setear campos principales
                if (d.NVale) {
                    let num = d.NVale.toString().replace(/^VDI-/, '').padStart(6, '0');
                    document.getElementById('correlativoVale').textContent = `VDI-${num}`;
                } else {
                    document.getElementById('correlativoVale').textContent = '';
                }
                document.getElementById('correlativoVale').setAttribute('data-id', d.Id);
                document.getElementById('fecha').value = d.Fecha || '';
                
                // Configurar restricciones para el campo de fecha
                configurarFechaInput(d.Fecha || '');
                document.getElementById('turno').value = d.Turno || '';
                if (window.choicesInstances['turno']) window.choicesInstances['turno'].setChoiceByValue(d.Turno || '');
                document.getElementById('area').value = d.Area || '';
                document.getElementById('subarea').value = d.Subarea || '';
                if (window.choicesInstances['subarea']) window.choicesInstances['subarea'].setChoiceByValue(d.Subarea || '');
                document.getElementById('despachador').value = d.Despachador || '';
                if (window.choicesInstances['despachador']) window.choicesInstances['despachador'].setChoiceByValue(d.Despachador || '');
                document.getElementById('recepcionista').value = d.Recepcionista || '';
                if (window.choicesInstances['recepcionista']) window.choicesInstances['recepcionista'].setChoiceByValue(d.Recepcionista || '');
                document.getElementById('verificador').value = d.Verificador || '';
                if (window.choicesInstances['verificador']) window.choicesInstances['verificador'].setChoiceByValue(d.Verificador || '');
                // Limpiar y cargar productos en la grilla
                const grilla = document.getElementById('grillaDespacho')?.querySelector('tbody');
                if (grilla) {
                    grilla.innerHTML = '';
                        (d.productos || []).forEach(p => {
                            const row = grilla.insertRow();
                            row.setAttribute('data-codigo', p.CodigoProducto || '');
                            row.setAttribute('data-producto', p.DescripcionProducto || '');
                            row.setAttribute('data-cantidad', p.Cantidad || '');
                            row.setAttribute('data-unidadmedida', p.UnidadMedida || '');
                            row.setAttribute('data-comentarios', p.Comentarios || '');
                            row.innerHTML = '<td></td>' +
                                '<td class="campo-codigo">' + (p.CodigoProducto || '') + '</td>' +
                                '<td class="campo-producto">' + (p.DescripcionProducto || '') + '</td>' +
                                '<td class="campo-unidad">' + (p.UnidadMedida || '') + '</td>' +
                                '<td class="campo-cantidad">' + (p.Cantidad || '') + '</td>' +
                                '<td class="campo-comentarios">' + (p.Comentarios || '') + '</td>';
                    });
                    // Actualizar numeración
                    Array.from(grilla.rows).forEach((row, i) => { row.cells[0].textContent = i + 1; });
                }
                // Habilitar botón modificar
                if (btnModificar) btnModificar.removeAttribute('disabled');
                bloquearControles();
                if (btnModificar) btnModificar.removeAttribute('disabled');
            } else {
                mostrarMensajeError(res.message || 'No se pudo cargar el despacho.');
            }
        })
        .catch(() => mostrarMensajeError('Error de conexión al cargar despacho.'));
    };
    // Declarar referencias a botones y correlativo al inicio
    const btnGuardar = document.getElementById('btnGuardar') || document.querySelector('.btn-outline-primary');
    const btnAnular = document.querySelector('.btn-outline-danger');
    const btnQuitar = document.getElementById('btnQuitar');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const btnNuevo = document.getElementById('btnNuevo') || document.querySelector('.btn-outline-success');
    const btnModificar = document.getElementById('btnModificar');
    const btnAgregar = document.getElementById('btnAgregar');
    const producto = document.getElementById('producto');
    const codigo = document.getElementById('codigo');
    const cantidad = document.getElementById('cantidad');
    const comentarios = document.getElementById('comentarios');
    const correlativoVale = document.getElementById('correlativoVale');
    const unidadMedida = document.getElementById('unidadMedida');
    let despachoId = null;
    
    // Convertir comentarios a mayúsculas y aplicar autocorrección
    if (comentarios) {
        comentarios.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Agregar autocorrección al perder el foco
        comentarios.addEventListener('blur', function() {
            const textoOriginal = this.value;
            if (typeof autocorregirTexto !== 'function') {
                console.error('Error: La función autocorregirTexto no está definida');
                return;
            }
            try {
                const resultado = autocorregirTexto(textoOriginal, true, this);
                // Corregir automáticamente el valor del textarea
                if (resultado.textoCorregido !== textoOriginal) {
                    this.value = resultado.textoCorregido;
                }
                if (resultado.huboCorrecciones) {
                    this.classList.add('autocorrector-highlight');
                    setTimeout(() => {
                        this.classList.remove('autocorrector-highlight');
                    }, 1000);
                }
            } catch (error) {
                console.error('Error al aplicar autocorrección:', error);
            }
        });
    }
    
    // Al cambiar producto, rellenar unidad de medida
    if (producto) {
        producto.addEventListener('change', function() {
            const selected = producto.options[producto.selectedIndex];
            if (!selected || !selected.value) {
                if (typeof unidadMedida !== 'undefined' && unidadMedida) unidadMedida.value = '';
                if (typeof codigo !== 'undefined' && codigo) codigo.value = '';
                return;
            }
            // Buscar la unidad de medida y código en window.productosData
            const productosData = window.productosData || [];
            const prod = productosData.find(p => p.Id == selected.value);
            if (prod) {
                if (typeof unidadMedida !== 'undefined' && unidadMedida) unidadMedida.value = prod.UnidadMedida || '';
                if (typeof codigo !== 'undefined' && codigo) codigo.value = (prod.Codigo === 0 || prod.Codigo === '0') ? '0' : (prod.Codigo != null ? String(prod.Codigo) : '');
            } else {
                if (typeof unidadMedida !== 'undefined' && unidadMedida) unidadMedida.value = '';
                if (typeof codigo !== 'undefined' && codigo) codigo.value = '';
            }
        });
    }
    // Deshabilitar Agregar al cargar la página
    if (btnAgregar) btnAgregar.setAttribute('disabled', 'disabled');

    function bloquearControles() {
        [
            'fecha', 'subarea', 'despachador', 'recepcionista', 'verificador',
            'producto', 'cantidad', 'comentarios'
        ].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.setAttribute('disabled', 'disabled');
        });
    const areaEl = document.getElementById('area');
    if (areaEl) areaEl.setAttribute('disabled', 'disabled');
    const codigoEl = document.getElementById('codigo');
    if (codigoEl) codigoEl.setAttribute('disabled', 'disabled');
    const turnoEl = document.getElementById('turno');
    if (turnoEl) turnoEl.setAttribute('disabled', 'disabled');
    const chkTurnoEl = document.getElementById('chkTurno');
    if (chkTurnoEl) chkTurnoEl.setAttribute('disabled', 'disabled');
        if (window.choicesInstances) {
            Object.values(window.choicesInstances).forEach(inst => inst.disable());
        }
        // Aplicar estilo visual para todos los selects gestionados por Choices
        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e){}
        if (btnGuardar) btnGuardar.setAttribute('disabled', 'disabled');
        if (btnAnular) btnAnular.setAttribute('disabled', 'disabled');
        if (btnQuitar) btnQuitar.setAttribute('disabled', 'disabled');
        if (btnLimpiar) btnLimpiar.setAttribute('disabled', 'disabled');
        if (btnAgregar) btnAgregar.setAttribute('disabled', 'disabled');
    }

    function habilitarControles() {
        [
            'fecha', 'subarea', 'despachador', 'recepcionista', 'verificador',
            'producto', 'cantidad', 'comentarios'
        ].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.removeAttribute('disabled');
        });
        document.getElementById('area').setAttribute('disabled', 'disabled');
        document.getElementById('codigo').setAttribute('disabled', 'disabled');
        document.getElementById('turno').setAttribute('disabled', 'disabled');
        document.getElementById('chkTurno').removeAttribute('disabled');
        if (window.choicesInstances) {
            Object.values(window.choicesInstances).forEach(inst => inst.enable());
        }
        // Reaplicar estilo visual para todos los selects gestionados por Choices (ahora habilitados)
        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e){}
    if (btnGuardar) btnGuardar.removeAttribute('disabled');
    if (btnAnular) btnAnular.removeAttribute('disabled');
    if (btnQuitar) btnQuitar.removeAttribute('disabled');
    if (btnLimpiar) btnLimpiar.removeAttribute('disabled');
    if (btnAgregar) btnAgregar.removeAttribute('disabled');
    }

    // Estado inicial de controles (solo si NO estamos en modo edición)
    const btnBuscarVale = document.getElementById('btnBuscarVale');
    if (!btnBuscarVale) {
        // Estamos en "Nuevo Registro": mantener todos los controles deshabilitados hasta pulsar Nuevo
        bloquearControles();
        if (btnNuevo) btnNuevo.removeAttribute('disabled');
    }

    // Botón Modificar: habilita los controles para edición y activa modo edición
    if (btnModificar) {
        btnModificar.setAttribute('disabled', 'disabled');
        btnModificar.addEventListener('click', function() {
            // Verificar si el vale puede ser modificado (límite de 3 modificaciones)
            const correlativoValeEl = document.getElementById('correlativoVale');
            let despachoId = correlativoValeEl ? correlativoValeEl.getAttribute('data-id') : null;
            
            if (!despachoId || despachoId === 'null' || despachoId === 'undefined') {
                // No hay vale cargado, permitir modificar sin verificar
                habilitarControles();
                return;
            }
            
            // Verificar límite de modificaciones antes de habilitar controles
            fetch((window.APP_URL || window.BASE_URL) + '/despachosinternos/verificarModificaciones?id=' + despachoId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.puede_modificar) {
                        // Puede modificar, habilitar controles
                        habilitarControles();
                    } else {
                        // No puede modificar, mostrar mensaje
                        const mensaje = data.mensaje || 'Este vale ya alcanzó el límite de 3 modificaciones';
                        mostrarMensajeError(mensaje, 'error');
                    }
                })
                .catch(error => {
                    console.error('[Despachos Internos] Error al verificar modificaciones:', error);
                    mostrarMensajeError('Error al verificar límite de modificaciones', 'error');
                });
        });
    }

    // --- Inicialización Choices.js en todos los combos ---
    window.choicesInstances = {};
    
    // Declarar elementos de turno al inicio
    const turnoSelect = document.getElementById('turno');
    const chkTurnoManual = document.getElementById('chkTurno');
    const turnos = window.turnosData || [];

    // Helper robusto para obtener el valor de un campo que puede estar manejado por Choices.js
    function getFieldValue(id) {
        try {
            const el = document.getElementById(id);
            if (el && el.value) return el.value;
            // Intentar desde Choices.js si existe
            if (window.choicesInstances && window.choicesInstances[id]) {
                const ci = window.choicesInstances[id];
                if (typeof ci.getValue === 'function') {
                    try {
                        const v = ci.getValue(true);
                        if (v !== undefined && v !== null && v !== '') return v;
                    } catch(e) {}
                    try {
                        const arr = ci.getValue();
                        if (Array.isArray(arr) && arr.length > 0) return arr[0].value || '';
                    } catch(e) {}
                }
            }
        } catch (e) { console.debug('getFieldValue error', id, e); }
        return '';
    }
    
    document.querySelectorAll('.custom-dropdown').forEach(function(select) {
        window.choicesInstances[select.id] = new Choices(select, {
            searchEnabled: true,
            searchChoices: true,
            shouldSort: false,
            itemSelectText: '',
            allowHTML: false,
            renderChoiceLimit: -1,
            searchResultLimit: 100,
            position: 'auto',
            placeholder: true,
            placeholderValue: select.options[0]?.text || 'Seleccione',
            noResultsText: 'No se encontraron resultados',
            removeItemButton: false,
            duplicateItemsAllowed: false,
        });
        select.addEventListener('showDropdown', function() {
            setTimeout(function() {
                const input = select.parentElement.querySelector('.choices__input');
                if (input) {
                    input.style.display = 'block';
                    input.placeholder = 'Buscar...';
                    input.focus();
                }
            }, 100);
        });
        // Envolver enable/disable de la instancia para mantener el atributo disabled en el <select>
        try {
            const ci = window.choicesInstances[select.id];
            if (ci) {
                if (typeof ci.disable === 'function') {
                    const origDisable = ci.disable.bind(ci);
                    ci.disable = function() {
                        try { select.setAttribute('disabled', 'disabled'); } catch(e) {}
                        const res = origDisable();
                        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
                        return res;
                    };
                }
                if (typeof ci.enable === 'function') {
                    const origEnable = ci.enable.bind(ci);
                    ci.enable = function() {
                        try { select.removeAttribute('disabled'); } catch(e) {}
                        const res = origEnable();
                        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
                        return res;
                    };
                }
            }
        } catch(e) { /* ignore */ }
    });

    // Aplicar aspecto gris al select 'turno' cuando esté deshabilitado
    function syncTurnoChoicesDisabled() {
        try {
            const sel = document.getElementById('turno');
            if (!sel) return;
                // Robust lookup: select can be inside the .choices container or adjacent to it
                let choicesEl = sel.closest && sel.closest('.choices');
                if (!choicesEl) {
                    choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(sel));
                }
                if (!choicesEl) {
                    choicesEl = sel.nextElementSibling;
                }
            if (!choicesEl) return;

            const isDisabled = !!sel.disabled;
            const inner = choicesEl.querySelector('.choices__inner');
            const item = choicesEl.querySelector('.choices__list--single .choices__item');

            if (isDisabled) {
                choicesEl.classList.add('is-disabled');
                choicesEl.setAttribute('aria-disabled', 'true');
                if (inner) {
                    inner.setAttribute('aria-disabled', 'true');
                    inner.style.backgroundColor = '#eef2f6';
                    inner.style.color = '#6b7280';
                    inner.style.cursor = 'not-allowed';
                    inner.style.opacity = '1';
                }
                if (item) item.style.color = '#6b7280';
            } else {
                choicesEl.classList.remove('is-disabled');
                choicesEl.removeAttribute('aria-disabled');
                if (inner) {
                    inner.removeAttribute('aria-disabled');
                    inner.style.backgroundColor = '';
                    inner.style.color = '';
                    inner.style.cursor = '';
                    inner.style.opacity = '';
                }
                if (item) item.style.color = '';
            }
        } catch (e) { /* ignore */ }
    }

    // Llamada inicial para sincronizar el aspecto al cargar
    try { syncTurnoChoicesDisabled(); } catch(e){}

    // Sincronizar visualmente TODOS los selects gestionados por Choices (container-first)
    function syncAllChoicesDisabled() {
        try {
            const containers = Array.from(document.querySelectorAll('.choices'));
            containers.forEach(choicesEl => {
                try {
                    // Buscar <select> asociado en varias posiciones posibles
                    let sel = choicesEl.querySelector('select') || null;
                    if (!sel) {
                        const prev = choicesEl.previousElementSibling;
                        if (prev && prev.tagName === 'SELECT') sel = prev;
                    }
                    if (!sel) {
                        sel = Array.from(document.querySelectorAll('select.custom-dropdown')).find(s => (s.closest && s.closest('.choices') === choicesEl));
                    }

                    const isDisabled = !!(sel && sel.disabled);
                    const inner = choicesEl.querySelector('.choices__inner');
                    const item = choicesEl.querySelector('.choices__list--single .choices__item');

                    if (isDisabled) {
                        choicesEl.classList.add('is-disabled');
                        choicesEl.setAttribute('aria-disabled', 'true');
                        if (inner) {
                            inner.setAttribute('aria-disabled', 'true');
                            try { inner.style.setProperty('background-color', '#eef2f6', 'important'); } catch(e) { inner.style.backgroundColor = '#eef2f6'; }
                            try { inner.style.setProperty('color', '#6b7280', 'important'); } catch(e) { inner.style.color = '#6b7280'; }
                            inner.style.cursor = 'not-allowed';
                            inner.style.opacity = '1';
                        }
                        if (item) item.style.color = '#6b7280';
                    } else {
                        choicesEl.classList.remove('is-disabled');
                        choicesEl.removeAttribute('aria-disabled');
                        if (inner) {
                            inner.removeAttribute('aria-disabled');
                            try { inner.style.removeProperty('background-color'); } catch(e) { inner.style.backgroundColor = ''; }
                            try { inner.style.removeProperty('color'); } catch(e) { inner.style.color = ''; }
                            inner.style.cursor = '';
                            inner.style.opacity = '';
                        }
                        if (item) item.style.color = '';
                    }
                } catch (e) { /* per-container ignore */ }
            });
        } catch (e) { /* ignore */ }
    }

    // Exponer globalmente
    try { window.syncAllChoicesDisabled = syncAllChoicesDisabled; } catch(e) {}

    // Envolver todas las instancias existentes de Choices por si fueron creadas antes
    function wrapExistingChoicesInstances() {
        try {
            if (!window.choicesInstances) return;
            Object.keys(window.choicesInstances).forEach(key => {
                try {
                    const ci = window.choicesInstances[key];
                    const selectEl = document.getElementById(key);
                    if (!ci || !selectEl) return;

                    if (typeof ci._choicesWrapped === 'undefined') {
                        // wrap disable
                        if (typeof ci.disable === 'function') {
                            const origDisable = ci.disable.bind(ci);
                            ci.disable = function() {
                                try { selectEl.setAttribute('disabled', 'disabled'); } catch(e) {}
                                const res = origDisable();
                                try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
                                return res;
                            };
                        }
                        // wrap enable
                        if (typeof ci.enable === 'function') {
                            const origEnable = ci.enable.bind(ci);
                            ci.enable = function() {
                                try { selectEl.removeAttribute('disabled'); } catch(e) {}
                                const res = origEnable();
                                try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
                                return res;
                            };
                        }
                        ci._choicesWrapped = true;
                    }
                } catch(e) { /* ignore per-instance errors */ }
            });
        } catch (e) { /* ignore */ }
    }

    // Ejecutar wrapper ahora y también después de un pequeño delay por si hay instancias tardías
    try { wrapExistingChoicesInstances(); } catch(e) {}
    setTimeout(() => { try { wrapExistingChoicesInstances(); } catch(e) {} }, 300);
    // --- Helpers globales de fallback (aseguran disponibilidad en la consola) ---
    (function(){
        if (window._choicesGlobalFallbackInstalled) return;
        window._choicesGlobalFallbackInstalled = true;

        function syncAllChoicesDisabledGlobal() {
            try {
                const selects = Array.from(document.querySelectorAll('select.custom-dropdown'));
                selects.forEach(sel => {
                    if (!sel) return;
                    let choicesEl = sel.nextElementSibling;
                    if (!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')) {
                        choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(sel));
                    }
                    if (!choicesEl) return;

                    const isDisabled = !!sel.disabled;
                    const inner = choicesEl.querySelector('.choices__inner');
                    const item = choicesEl.querySelector('.choices__list--single .choices__item');

                    if (isDisabled) {
                        choicesEl.classList.add('is-disabled');
                        choicesEl.setAttribute('aria-disabled', 'true');
                        if (inner) {
                            inner.setAttribute('aria-disabled', 'true');
                            inner.style.backgroundColor = '#eef2f6';
                            inner.style.color = '#6b7280';
                            inner.style.cursor = 'not-allowed';
                            inner.style.opacity = '1';
                        }
                        if (item) item.style.color = '#6b7280';
                    } else {
                        choicesEl.classList.remove('is-disabled');
                        choicesEl.removeAttribute('aria-disabled');
                        if (inner) {
                            inner.removeAttribute('aria-disabled');
                            inner.style.backgroundColor = '';
                            inner.style.color = '';
                            inner.style.cursor = '';
                            inner.style.opacity = '';
                        }
                        if (item) item.style.color = '';
                    }
                });
            } catch (e) { /* ignore */ }
        }

        function wrapExistingChoicesInstancesGlobal() {
            try {
                if (!window.choicesInstances) return;
                Object.keys(window.choicesInstances).forEach(key => {
                    try {
                        const ci = window.choicesInstances[key];
                        const selectEl = document.getElementById(key);
                        if (!ci || !selectEl) return;

                        if (typeof ci._choicesWrappedGlobal === 'undefined') {
                            if (typeof ci.disable === 'function') {
                                const origDisable = ci.disable.bind(ci);
                                ci.disable = function() {
                                    try { selectEl.setAttribute('disabled', 'disabled'); } catch(e) {}
                                    const res = origDisable();
                                    try { syncAllChoicesDisabledGlobal(); } catch(e) {}
                                    return res;
                                };
                            }
                            if (typeof ci.enable === 'function') {
                                const origEnable = ci.enable.bind(ci);
                                ci.enable = function() {
                                    try { selectEl.removeAttribute('disabled'); } catch(e) {}
                                    const res = origEnable();
                                    try { syncAllChoicesDisabledGlobal(); } catch(e) {}
                                    return res;
                                };
                            }
                            ci._choicesWrappedGlobal = true;
                        }
                    } catch(e) { /* per-instance ignore */ }
                });
            } catch (e) { /* ignore */ }
        }

        // Exponer si no existe (no sobrescribir si ya definido por el archivo principal)
        if (!window.syncAllChoicesDisabled) window.syncAllChoicesDisabled = syncAllChoicesDisabledGlobal;
        if (!window.wrapExistingChoicesInstances) window.wrapExistingChoicesInstances = wrapExistingChoicesInstancesGlobal;

        // Ejecutar como fallback una vez cargado
        function runFallbackNow() {
            try { window.wrapExistingChoicesInstances(); } catch(e) {}
            try { window.syncAllChoicesDisabled(); } catch(e) {}
        }
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(runFallbackNow, 50);
        } else {
            document.addEventListener('DOMContentLoaded', runFallbackNow);
        }
    })();

    // Observer para re-aplicar estilos cuando Choices o atributos cambien (debounce)
    (function setupChoicesMutationObserver(){
        try {
            const debounce = (fn, wait) => {
                let t = null;
                return function(...args) {
                    if (t) clearTimeout(t);
                    t = setTimeout(() => { t = null; try { fn.apply(this, args); } catch(e){}; }, wait);
                };
            };

            const reapply = debounce(function() {
                try { if (typeof window.wrapExistingChoicesInstances === 'function') window.wrapExistingChoicesInstances(); } catch(e){}
                try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e){}
            }, 60);

            const mo = new MutationObserver(mutations => {
                // Simple trigger, dejar debounce manejar la frecuencia
                reapply();
            });

            mo.observe(document.documentElement || document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['disabled', 'class', 'aria-disabled']
            });

            window._choicesGlobalObserver = mo;
        } catch(e) { /* ignore */ }
    })();

    // Disabled .choices CSS moved to public/css/choices-custom.css for maintainability

    // Función para permitir wrapping de texto largo en select de producto
    function updateProductoChoiceWrap() {
        try {
            console.log('[Nuevo] updateProductoChoiceWrap ejecutándose');
            const productoSelect = document.getElementById('producto');
            if (!productoSelect) {
                console.log('[Nuevo] NO se encontró select#producto');
                return;
            }

            // Buscar la instancia .choices que contiene el select#producto
            const allChoices = Array.from(document.querySelectorAll('.choices'));
            const choicesEl = allChoices.find(c => c.querySelector('select#producto')) || productoSelect.nextElementSibling;
            if (!choicesEl) {
                console.log('[Nuevo] NO se encontró .choices para producto');
                return;
            }

            const container = choicesEl.querySelector('.choices__inner');
            const single = choicesEl.querySelector('.choices__list--single');
            const item = single ? single.querySelector('.choices__item') : null;
            if (!container || !single || !item) {
                console.log('[Nuevo] Faltan elementos:', {container: !!container, single: !!single, item: !!item});
                return;
            }

            // Medidas reales (horizontal y vertical)
            const itemWidth = item.scrollWidth || item.offsetWidth || 0;
            const containerWidth = container.clientWidth || container.offsetWidth || 0;
            const itemHeight = item.scrollHeight || item.offsetHeight || 0;
            const containerHeight = container.clientHeight || container.offsetHeight || 0;

            console.log('[Nuevo] Medidas:', {
                itemWidth, containerWidth,
                itemHeight, containerHeight,
                innerScroll: container.scrollHeight,
                singleScroll: single.scrollHeight
            });

            // Consider horizontal overflow OR vertical overflow (line-wrap hidden)
            const needWrap = (itemWidth > containerWidth - 6) || (itemHeight > containerHeight - 4) || (single.scrollHeight > containerHeight - 4) || (container.scrollHeight > containerHeight);

            console.log('[Nuevo] needWrap?', needWrap);

            if (needWrap) {
                // Aplicar estilos inline para forzar wrapping (mayor especificidad que CSS)
                choicesEl.classList.add('choices-wrap');
                try {
                    // Primero forzar el ancho del item para que el wrap ocurra
                    const containerWidth = container.offsetWidth - 40; // -40px para padding/caret
                    item.style.width = containerWidth + 'px';
                    item.style.maxWidth = containerWidth + 'px';
                    item.style.whiteSpace = 'normal';
                    item.style.wordBreak = 'break-word';
                    item.style.overflowWrap = 'anywhere';
                    item.style.display = 'block';
                    
                    // Forzar al single list a mostrar contenido completo
                    single.style.whiteSpace = 'normal';
                    single.style.overflow = 'visible';
                    single.style.height = 'auto';
                    single.style.width = '100%';
                    
                    // Esperar más tiempo para que el navegador recalcule alturas con el wrap aplicado
                    setTimeout(() => {
                        const realHeight = Math.max(single.scrollHeight, item.scrollHeight, item.offsetHeight);
                        console.log('[Nuevo] Altura real calculada:', realHeight, 'item:', item.offsetHeight, item.scrollHeight);
                        console.log('[Nuevo] Container actual:', container.clientHeight, container.offsetHeight);
                        
                        // Solo aplicar si realmente necesita más altura
                        if (realHeight > 32) {
                            // Forzar altura del container (que tiene el borde) con especificidad máxima
                            const targetHeight = realHeight + 10; // +10px para padding
                            container.style.cssText = `
                                height: auto !important;
                                min-height: ${targetHeight}px !important;
                                max-height: none !important;
                                overflow: visible !important;
                                display: block !important;
                                padding-top: 5px !important;
                                padding-bottom: 5px !important;
                            `;
                            
                            console.log('[Nuevo] Container después:', container.clientHeight, container.offsetHeight);
                        }
                    }, 100);
                } catch (e) { /* ignore */ }
            } else {
                // Revertir cambios inline si no hace falta
                choicesEl.classList.remove('choices-wrap');
                try {
                    container.style.overflow = '';
                    container.style.height = '';
                    container.style.minHeight = '';
                    container.style.paddingTop = '';
                    container.style.paddingBottom = '';
                    single.style.whiteSpace = '';
                    single.style.overflow = '';
                    single.style.height = '';
                    item.style.whiteSpace = '';
                    item.style.wordBreak = '';
                    item.style.overflowWrap = '';
                    item.style.maxWidth = '';
                } catch (e) { /* ignore */ }
            }
        } catch (e) { console.warn('[Nuevo] updateProductoChoiceWrap error', e); }
    }

    // Exponer la función para poder invocarla desde otras partes
    window.updateProductoChoiceWrap = updateProductoChoiceWrap;

    // Escuchar cambios en el select producto
    const productoSelect = document.getElementById('producto');
    if (productoSelect) {
        productoSelect.addEventListener('change', () => {
            console.log('[Nuevo] producto change event');
            setTimeout(updateProductoChoiceWrap, 50);
        });
        // Revisar al inicializar por si ya tiene valor (múltiples intentos con delays)
        setTimeout(updateProductoChoiceWrap, 200);
        setTimeout(updateProductoChoiceWrap, 500);
        setTimeout(updateProductoChoiceWrap, 1000);
        // Revisar en resize por si cambia el espacio disponible
        window.addEventListener('resize', updateProductoChoiceWrap);
    }

    // Además, usar un MutationObserver para detectar cuando Choices actualiza el texto
    try {
        const productoSelect = document.getElementById('producto');
        if (productoSelect) {
            let choicesEl = productoSelect.nextElementSibling;
            if (!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')) {
                choicesEl = document.querySelector('#producto + .choices');
            }
            if (choicesEl) {
                const targetNode = choicesEl.querySelector('.choices__list--single');
                if (targetNode) {
                    const mo = new MutationObserver(() => updateProductoChoiceWrap());
                    mo.observe(targetNode, { childList: true, subtree: true, characterData: true });
                    // Guardar en window para posible limpieza si es necesario
                    window._productoChoicesObserver = mo;
                }
            }
        }
    } catch (e) { console.warn('[Nuevo] Error al crear MutationObserver para producto', e); }

    // Product wrap CSS moved to public/css/choices-custom.css

    // Asegurar inicialización robusta (útil en hosting con carga diferente)
    function ensureProductWrapInit() {
        let attempts = 0;
        const maxAttempts = 30;
        const interval = setInterval(() => {
            const choicesEl = document.querySelector('#producto + .choices');
            if (choicesEl) {
                clearInterval(interval);
                try {
                    updateProductoChoiceWrap();
                } catch (e) { console.warn('[ensureProductWrapInit] updateProductoChoiceWrap error', e); }

                // Observer si aún no existe
                try {
                    const targetNode = choicesEl.querySelector('.choices__list--single');
                    if (targetNode && (!window._productoChoicesObserver || !(window._productoChoicesObserver instanceof MutationObserver))) {
                        const mo = new MutationObserver(() => updateProductoChoiceWrap());
                        mo.observe(targetNode, { childList: true, subtree: true, characterData: true });
                        window._productoChoicesObserver = mo;
                    }
                } catch (e) { /* ignore */ }

                // Forzar que la fila que contiene el select mantenga alineamiento superior
                try {
                    const prod = document.getElementById('producto');
                    const row = prod ? prod.closest('.row') : null;
                    if (row) row.style.alignItems = 'flex-start';
                } catch (e) { /* ignore */ }
            } else if (++attempts >= maxAttempts) {
                clearInterval(interval);
            }
        }, 200);
    }

    // Ejecutar la inicialización robusta (soporta hosting con orden de carga distinto)
    ensureProductWrapInit();

    // Deshabilitar selector de turno al cargar la interfaz
    if (turnoSelect) {
        turnoSelect.setAttribute('disabled', 'disabled');
        if (window.choicesInstances['turno']) {
            window.choicesInstances['turno'].disable();
        }
    }

    // Fallback: sincronizar la clase .is-disabled y atributos en el contenedor .choices
    function syncTurnoChoicesDisabled() {
        try {
            const sel = document.getElementById('turno');
            if (!sel) return;
            // Preferir el sibling generado por Choices, si existe
            let choicesEl = sel.nextElementSibling;
            if (!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')) {
                // Buscar una instancia .choices que contenga el select
                choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(sel));
            }
            if (!choicesEl) return;

            const isDisabled = !!sel.disabled;
            const inner = choicesEl.querySelector('.choices__inner');
            const item = choicesEl.querySelector('.choices__list--single .choices__item');
            
            if (isDisabled) {
                choicesEl.classList.add('is-disabled');
                choicesEl.setAttribute('aria-disabled', 'true');
                // Aplicar estilos inline directamente para máxima prioridad
                if (inner) {
                    inner.setAttribute('aria-disabled', 'true');
                    inner.style.backgroundColor = '#eef2f6';
                    inner.style.color = '#6b7280';
                    inner.style.cursor = 'not-allowed';
                    inner.style.opacity = '1';
                }
                if (item) {
                    item.style.color = '#6b7280';
                }
            } else {
                choicesEl.classList.remove('is-disabled');
                choicesEl.removeAttribute('aria-disabled');
                if (inner) {
                    inner.removeAttribute('aria-disabled');
                    inner.style.backgroundColor = '';
                    inner.style.color = '';
                    inner.style.cursor = '';
                    inner.style.opacity = '';
                }
                if (item) {
                    item.style.color = '';
                }
            }
        } catch (e) { /* ignore */ }
    }

    // Observar cambios en el atributo disabled del select#turno
    try {
        const turnoNode = document.getElementById('turno');
        if (turnoNode) {
            const mo = new MutationObserver(mutations => {
                for (const m of mutations) {
                    if (m.type === 'attributes' && m.attributeName === 'disabled') syncTurnoChoicesDisabled();
                }
            });
            mo.observe(turnoNode, { attributes: true, attributeFilter: ['disabled'] });
            // Llamada inicial
            syncTurnoChoicesDisabled();
        }
    } catch (e) { console.warn('syncTurnoChoicesDisabled observer error', e); }

    // Si existe la instancia Choices para turno, envolver disable/enable para sincronizar visual
    try {
        const ci = window.choicesInstances && window.choicesInstances['turno'];
        if (ci && typeof ci.disable === 'function') {
            const origDisable = ci.disable.bind(ci);
            ci.disable = function() {
                const res = origDisable();
                // Forzar que el select tenga el atributo disabled (por si Choices no lo hace)
                const s = document.getElementById('turno'); if (s) s.setAttribute('disabled', 'disabled');
                syncTurnoChoicesDisabled();
                return res;
            };
        }
        if (ci && typeof ci.enable === 'function') {
            const origEnable = ci.enable.bind(ci);
            ci.enable = function() {
                const res = origEnable();
                const s = document.getElementById('turno'); if (s) s.removeAttribute('disabled');
                syncTurnoChoicesDisabled();
                return res;
            };
        }
    } catch (e) { /* ignore */ }

    // --- Lógica de Turno automática ---
    const horaActual = window.horaActual || (new Date().toTimeString().slice(0,5));
    function parseHora(horaStr) {
        const partes = horaStr.split(':');
        return { h: parseInt(partes[0], 10), m: parseInt(partes[1], 10) };
    }
    function horaEnMinutos(horaStr) {
        const h = parseHora(horaStr);
        return h.h * 60 + h.m;
    }
    function setTurnoByHora() {
        if (!turnoSelect) return false;
        const actualMin = horaEnMinutos(horaActual);
        let found = false;
        turnoSelect.value = '';
        for (let i = 0; i < turnos.length; i++) {
            const t = turnos[i];
            const ini = horaEnMinutos(t.HoraInicio);
            const fin = horaEnMinutos(t.HoraFin);
            let enTurno = false;
            if (ini <= fin) {
                enTurno = (actualMin >= ini && actualMin < fin);
            } else {
                enTurno = (actualMin >= ini || actualMin < fin);
            }
            if (enTurno) {
                turnoSelect.value = t.Id;
                found = true;
                break;
            }
        }
        if (window.choicesInstances['turno']) {
            window.choicesInstances['turno'].setChoiceByValue(turnoSelect.value);
        }
        return found;
    }
    if (turnoSelect && turnos.length) {
        setTimeout(function() {
            setTurnoByHora();
            // Aplicar fecha efectiva para turno nocturno
            try{ if(typeof window.aplicarFechaEfectivaTurno === 'function') window.aplicarFechaEfectivaTurno(false); }catch(e){}
            turnoSelect.setAttribute('disabled', 'disabled');
            if (window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
        }, 200);
    }
    if (chkTurnoManual && turnoSelect) {
        chkTurnoManual.addEventListener('change', function() {
            if (this.checked) {
                turnoSelect.removeAttribute('disabled');
                if (window.choicesInstances['turno']) window.choicesInstances['turno'].enable();
            } else {
                turnoSelect.setAttribute('disabled', 'disabled');
                if (window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
                setTurnoByHora();
                // Aplicar fecha efectiva para turno nocturno
                try{ if(typeof window.aplicarFechaEfectivaTurno === 'function') window.aplicarFechaEfectivaTurno(false); }catch(e){}
            }
        });
        if (window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
    }

    // --- Lógica de la grilla y botones ---
    const grilla = document.getElementById('grillaDespacho')?.querySelector('tbody');
    let editIndex = null;
    // updatePhase: null | 'selected' (row selected) | 'editing' (controls populated, ready to save)
    let updatePhase = null;

    function limpiarControles() {
        producto.value = '';
        if (window.choicesInstances && window.choicesInstances['producto']) {
            window.choicesInstances['producto'].setChoiceByValue('');
        }
        codigo.value = '';
        cantidad.value = '';
        comentarios.value = '';
        editIndex = null;
        updatePhase = null;
        // Limpiar otros campos del formulario para asegurar nuevo registro limpio
        // NOTA: no vaciamos 'fecha' ni 'turno' por requerimiento
        const camposAVaciar = ['subarea','despachador','recepcionista','verificador','area'];
        camposAVaciar.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') el.value = '';
                else el.textContent = '';
            }
        });
        // Limpiar también selects manejados por Choices.js (except 'turno')
        ['subarea','despachador','recepcionista','verificador','producto'].forEach(id => {
            try { if (window.choicesInstances && window.choicesInstances[id]) window.choicesInstances[id].setChoiceByValue(''); } catch(e){}
        });
        // Quitar cualquier data-id marcado en el correlativo
        try { if (correlativoVale) correlativoVale.removeAttribute('data-id'); } catch(e){}
        
        // Restablecer el texto y la clase del botón si estábamos en modo edición
        if (btnAgregar && (btnAgregar.textContent === 'Actualizar' || btnAgregar.textContent === 'Guardar')) {
            btnAgregar.textContent = 'Agregar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
        }
        
        // Limpiar la selección de la grilla
        if (grilla) grilla.querySelectorAll('tr').forEach(tr => tr.classList.remove('table-active'));
        // Formatear correlativo si es solo número o si perdió el prefijo
        let corr = correlativoVale.textContent;
        if (/^\d+$/.test(corr)) {
            correlativoVale.textContent = `VDI-${corr.padStart(6, '0')}`;
        } else if (/^VDI-?\d+$/.test(corr)) {
            let num = corr.replace(/^VDI-?/, '').padStart(6, '0');
            correlativoVale.textContent = `VDI-${num}`;
        }
    }

    function limpiarProductoCombo() {
        const productoSelect = document.getElementById('producto');
        if (!productoSelect) return;
        
        productoSelect.value = '';
        
        // Obtener las descripciones de productos ya agregados a la grilla
        const descripcionesEnGrilla = new Set();
        const grilla = document.getElementById('grillaDespacho')?.querySelector('tbody');
        if (grilla) {
            Array.from(grilla.rows).forEach(row => {
                const descripcion = row.cells[2]?.textContent?.trim(); // Columna 3: Producto/Descripción (reordenado)
                if (descripcion) descripcionesEnGrilla.add(descripcion);
            });
        }
        
        // Guardar todas las opciones originales (para restaurar si es necesario)
        if (!productoSelect._allOptions) {
            productoSelect._allOptions = Array.from(productoSelect.options).map(opt => opt.cloneNode(true));
        }
        
        // Limpiar todas las opciones actuales
        productoSelect.innerHTML = '';
        
        // Volver a agregar la opción "Seleccione"
        const selectOption = productoSelect._allOptions.find(opt => !opt.value);
        if (selectOption) productoSelect.appendChild(selectOption.cloneNode(true));
        
        // Agregar solo las opciones que no están en la grilla
        let productosAgregados = 0;
        productoSelect._allOptions.forEach(opt => {
            if (!opt.value) return; // Saltamos la opción "Seleccione"
            
            const optText = opt.text?.trim();
            
            // Verificar si el producto ya está en la grilla (por descripción completa, no por código)
            if (optText && !descripcionesEnGrilla.has(optText)) {
                productoSelect.appendChild(opt.cloneNode(true));
                productosAgregados++;
            }
        });
        
        // Destruir y recrear la instancia de Choices para actualizar la UI
        if (window.choicesInstances && window.choicesInstances['producto']) {
            window.choicesInstances['producto'].destroy();
        }
        
        // Crear una nueva instancia con las opciones filtradas
        window.choicesInstances['producto'] = new Choices(productoSelect, {
            searchEnabled: true,
            searchChoices: true,
            shouldSort: false,
            itemSelectText: '',
            allowHTML: false,
            renderChoiceLimit: -1,
            searchResultLimit: 100,
            position: 'auto',
            placeholder: true,
            placeholderValue: productoSelect.options[0]?.text || 'Seleccione',
            noResultsText: 'No se encontraron resultados',
            removeItemButton: false,
            duplicateItemsAllowed: false,
        });
        // Forzar recalculo/resize del control producto tras recrear Choices
        try {
            if (window.updateProductoChoiceWrap) {
                setTimeout(window.updateProductoChoiceWrap, 50);
                setTimeout(window.updateProductoChoiceWrap, 200);
            }
        } catch(e) { /* ignore */ }
    }

    // Limpiar solo los campos de producto (no tocar área/fechas/turno)
    function limpiarCamposProducto() {
        if (window.choicesInstances && window.choicesInstances['producto']) {
            try { window.choicesInstances['producto'].setChoiceByValue(''); } catch(e){}
            if (window.updateProductoChoiceWrap) window.updateProductoChoiceWrap();
        }
        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        const comentariosInput = document.getElementById('comentarios');
        if (codigoInput) codigoInput.value = '';
        if (cantidadInput) cantidadInput.value = '';
        if (comentariosInput) comentariosInput.value = '';
        editIndex = null;
        updatePhase = null;
        if (btnAgregar) {
            btnAgregar.textContent = 'Agregar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
        }
    }

    function obtenerDatos() {
        const prodOpt = producto.options[producto.selectedIndex];
        // Obtener unidad de medida del option seleccionado si existe, o vacío
        let unidad = '';
        if (prodOpt && prodOpt.hasAttribute('data-unidadmedida')) {
            unidad = prodOpt.getAttribute('data-unidadmedida') || '';
        } else if (typeof unidadMedida !== 'undefined' && unidadMedida) {
            unidad = unidadMedida.value;
        }
        return {
            productoId: producto.value,
            productoText: prodOpt ? prodOpt.text : '',
            codigo: codigo.value,
            cantidad: cantidad.value,
            unidadMedida: unidad,
            comentarios: comentarios.value
        };
    }

    function validarDatos(datos) {
        if (!datos.productoId) return 'Seleccione un producto';
        if (!datos.cantidad || isNaN(datos.cantidad) || Number(datos.cantidad) <= 0) return 'Ingrese una cantidad válida';
        return null;
    }

    function agregarFila(datos) {
        if (!grilla) return;
        const row = grilla.insertRow();
        // Guardar los datos originales como atributos data-*
        row.setAttribute('data-codigo', datos.codigo);
        row.setAttribute('data-producto', datos.productoText);
        // Almacenar el id real del producto para futuras operaciones (selección por id)
        if (datos.productoId) row.setAttribute('data-producto-id', datos.productoId);
        row.setAttribute('data-cantidad', datos.cantidad);
        row.setAttribute('data-unidadmedida', datos.unidadMedida || '');
        row.setAttribute('data-comentarios', datos.comentarios);
        row.innerHTML = '<td></td>' +
            '<td class="campo-codigo">' + datos.codigo + '</td>' +
            '<td class="campo-producto">' + datos.productoText + '</td>' +
            '<td class="campo-unidad">' + (datos.unidadMedida || '') + '</td>' +
            '<td class="campo-cantidad">' + datos.cantidad + '</td>' +
            '<td class="campo-comentarios">' + datos.comentarios + '</td>';
        row.addEventListener('click', function() {
            // Seleccionar visualmente la fila, pero NO cargar inmediatamente los datos en los controles.
            grilla.querySelectorAll('tr').forEach(tr => tr.classList.remove('table-active'));
            row.classList.add('table-active');
            // Guardar el índice seleccionado y marcar la fase 'selected'
            editIndex = row.rowIndex - 1;
            updatePhase = 'selected';
            // Cambiar el texto del botón "Agregar" a "Actualizar" cuando seleccionamos una fila
            if (btnAgregar && btnAgregar.textContent !== 'Actualizar') {
                btnAgregar.textContent = 'Actualizar';
                btnAgregar.classList.remove('btn-success');
                btnAgregar.classList.add('btn-primary');
            }
        });
        actualizarNumeracion();
    }

    function cargarFilaEnControles(idx) {
        if (!grilla) return;
        const row = grilla.rows[idx];
        if (!row) return;
        
        // Obtener el código y descripción del producto seleccionado
    const codigoEdit = row.cells[1].textContent?.trim(); // Columna 2: Código (reordenado)
    const descripcionEdit = row.cells[2].textContent?.trim(); // Columna 3: Producto (reordenado)
    const productoIdEdit = row.getAttribute('data-producto-id') || '';
        
        // Limpiar el select de producto completamente
        producto.innerHTML = '';
        
        // Si hay opciones guardadas
        if (producto._allOptions) {
            // 1. Agregar opción "Seleccione"
            const selectOption = producto._allOptions.find(opt => !opt.value);
            if (selectOption) producto.appendChild(selectOption.cloneNode(true));
            
            // 2. Recopilar TODAS las descripciones que están en la grilla (excepto el que estamos editando)
            const descripcionesEnGrilla = new Set();
            Array.from(grilla.rows).forEach((r, i) => {
                if (i !== idx) { // Excluir la fila actual que estamos editando
                    const d = r.cells[2]?.textContent?.trim(); // Columna 3: Producto/Descripción (reordenado)
                    if (d) descripcionesEnGrilla.add(d);
                }
            });
            
            // 3. Agregar SOLO el producto que estamos editando
            let productoEditEncontrado = false;
            producto._allOptions.forEach(opt => {
                if (!opt.value) return; // Saltamos la opción "Seleccione"
                const codigoOpt = opt.getAttribute('data-codigo');
                const optValue = String(opt.value || '');
                // Priorizar coincidencia por product id si está disponible
                if (productoIdEdit && optValue === String(productoIdEdit)) {
                    producto.appendChild(opt.cloneNode(true));
                    productoEditEncontrado = true;
                    return;
                }
                if (codigoOpt && codigoOpt.trim() === codigoEdit) {
                    producto.appendChild(opt.cloneNode(true));
                    productoEditEncontrado = true;
                }
            });
            
            // 4. Agregar el resto de productos QUE NO ESTÁN en la grilla
            producto._allOptions.forEach(opt => {
                if (!opt.value) return; // Saltamos la opción "Seleccione"
                
                const optText = opt.text?.trim();
                // Solo agregar si NO está en la grilla (por descripción) Y no es el producto que estamos editando (que ya agregamos)
                if (optText && !descripcionesEnGrilla.has(optText) && (!productoEditEncontrado || optText !== descripcionEdit)) {
                    producto.appendChild(opt.cloneNode(true));
                }
            });
        }
        
        // Recrear la instancia de Choices
        if (window.choicesInstances && window.choicesInstances['producto']) {
            window.choicesInstances['producto'].destroy();
            window.choicesInstances['producto'] = new Choices(producto, {
                searchEnabled: true,
                searchChoices: true,
                shouldSort: false,
                itemSelectText: '',
                allowHTML: false,
                renderChoiceLimit: -1,
                searchResultLimit: 100,
                position: 'auto',
                placeholder: true,
                placeholderValue: producto.options[0]?.text || 'Seleccione',
                noResultsText: 'No se encontraron resultados',
                removeItemButton: false,
                duplicateItemsAllowed: false,
            });
        }
        
        // Seleccionar el producto correcto
        // Intentar primero por valor exacto
        if (producto.options && producto.options.length > 0) {
            // Buscar la opción por texto o por código
            const productOptions = Array.from(producto.options);
                    // Primero intentar por id si tenemos one stored
                    let opt = null;
                    if (productoIdEdit) {
                        opt = productOptions.find(o => String(o.value) === String(productoIdEdit));
                    }
                    if (!opt) {
                        opt = productOptions.find(o =>
                            o.textContent.trim() === descripcionEdit ||
                            (o.getAttribute('data-codigo') && o.getAttribute('data-codigo').trim() === codigoEdit)
                        );
                    }

                    if (opt) {
                        producto.value = opt.value;
                        // Actualizar la visualización de Choices
                        if (window.choicesInstances && window.choicesInstances['producto']) {
                            setTimeout(() => {
                                window.choicesInstances['producto'].setChoiceByValue(opt.value);
                            }, 0);
                        }
                    }
        }
        // Establecer los valores de los demás campos
        codigo.value = row.cells[1].textContent;
        cantidad.value = row.cells[4].textContent;
        if (typeof unidadMedida !== 'undefined' && unidadMedida) {
            unidadMedida.value = row.cells[3].textContent;
        }
        comentarios.value = row.cells[5].textContent;
        editIndex = idx;
        
        // Marcar que estamos en modo edición (los controles ya están poblados)
        updatePhase = 'editing';
        if (btnAgregar) {
            btnAgregar.textContent = 'Guardar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
        }
        // Forzar recalculo/resize del control producto (wrapping) tras recrear Choices
        try {
            if (window.updateProductoChoiceWrap) {
                setTimeout(window.updateProductoChoiceWrap, 50);
                setTimeout(window.updateProductoChoiceWrap, 200);
                setTimeout(window.updateProductoChoiceWrap, 500);
            }
        } catch(e) { /* ignore */ }
    }

    function actualizarNumeracion() {
        if (!grilla) return;
        Array.from(grilla.rows).forEach((row, i) => {
            row.cells[0].textContent = i + 1;
        });
    }

    // Ahora acepta un segundo parámetro opcional `level` ('error'|'warning'|'success'|'info').
    // Para errores, aplicar los mismos estilos y temporización que Recepciones Internas.
    function mostrarMensajeError(msg, level) {
        try {
            level = level || 'warning';
            var div = document.getElementById('mensajeError');
            if (!div) { alert(msg); return; }

            // Si es error, aplicar estilos inline iguales a Recepciones Internas
            try{ div.classList.remove('alert-success','alert-danger','alert-warning','alert-info'); }catch(e){}
            if(level === 'error') div.classList.add('alert-danger');
            else if(level === 'success') div.classList.add('alert-success');
            else if(level === 'info') div.classList.add('alert-info');
            else div.classList.add('alert-warning');

            if(level === 'error'){
                div.style.color = '#991b1b';
                div.style.background = '#fee';
                div.style.border = '2px solid #fca5a5';
            } else if(level === 'success'){
                div.style.color = '#064e3b';
                div.style.background = '#d1fae5';
                div.style.border = '2px solid #86efac';
            } else if(level === 'info'){
                div.style.color = '#1e40af';
                div.style.background = '#dbeafe';
                div.style.border = '2px solid #93c5fd';
            } else {
                div.style.color = '#7c4700';
                div.style.background = '#fffbe6';
                div.style.border = '2px solid #ffe082';
            }

            div.textContent = msg || '';
            div.classList.remove('d-none');
            // Mantener visible 5s como en Recepciones Internas
            setTimeout(function() { try{ div.classList.add('d-none'); }catch(e){} }, 5000);
        } catch (e) { console.log('mostrarMensajeError', e); }
    }

    if (btnAgregar) btnAgregar.addEventListener('click', function() {
        console.log('[DIAG-Nuevo] click btnAgregar (listener NUEVO) | _esPaginaEdicion=', window._esPaginaEdicion, '| editIndex=', editIndex, 'updatePhase=', updatePhase);
        // FIX: En la página de edición (despachosinternos/edicion), este listener es el del
        // script de "Nuevo Registro". El listener de despachosinternos_edicion.js es quien debe
        // manejar el botón; si este listener se ejecutara también, interferiría con la carga de
        // la fila seleccionada y el select de producto mostraría otro producto.
        if (window._esPaginaEdicion) {
            return;
        }
        // Si una fila fue seleccionada pero no cargada en controles, al pulsar "Actualizar"
        // debemos primero poblar los controles y pasar a fase 'editing'.
        if (editIndex !== null && updatePhase === 'selected') {
            cargarFilaEnControles(editIndex);
            return;
        }

        const datos = obtenerDatos();
        const fecha = document.getElementById('fecha').value;
        const turno = document.getElementById('turno').value;
        const subarea = document.getElementById('subarea').value;
        const despachador = document.getElementById('despachador').value;
        const recepcionista = document.getElementById('recepcionista').value;
        const verificador = document.getElementById('verificador').value;
        if (!fecha || !turno || !subarea || !despachador || !recepcionista || !verificador || !datos.productoId || !datos.cantidad) {
            mostrarMensajeError('Por favor, complete todos los campos requeridos.');
            return;
        }
        const error = validarDatos(datos);
        if (error) {
            mostrarMensajeError(error);
            return;
        }
        
        // Verificar si estamos en modo edición o agregar
        if (editIndex !== null) {
            // Modo edición - actualizar fila existente
            const row = grilla.rows[editIndex];
            row.cells[2].textContent = datos.productoText;
            row.cells[3].textContent = datos.unidadMedida || '';
            row.cells[1].textContent = datos.codigo;
            row.cells[4].textContent = datos.cantidad;
            row.cells[5].textContent = datos.comentarios;
            
            // Actualizar atributos de datos
            row.setAttribute('data-codigo', datos.codigo);
            row.setAttribute('data-producto', datos.productoText);
            // Actualizar/guardar también el id del producto
            if (datos.productoId) row.setAttribute('data-producto-id', datos.productoId);
            row.setAttribute('data-cantidad', datos.cantidad);
            row.setAttribute('data-unidadmedida', datos.unidadMedida || '');
            row.setAttribute('data-comentarios', datos.comentarios);
            
            // Cambiar el texto y la clase del botón de nuevo a "Agregar"
            btnAgregar.textContent = 'Agregar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
        } else {
            // Modo agregar - agregar nueva fila
            agregarFila(datos);
        }
        
        // Limpiar solo campos de producto y actualizar el combo de productos
        limpiarCamposProducto();
        limpiarProductoCombo();
    });

    // El botón Editar ha sido eliminado, ahora se usa el botón Agregar/Actualizar para ambas funciones

    if (btnQuitar) btnQuitar.addEventListener('click', function() {
        // FIX: No interferir en la página de edición (manejada por despachosinternos_edicion.js)
        if (window._esPaginaEdicion) {
            return;
        }
        if (editIndex === null) {
            mostrarMensajeError('Seleccione una fila para quitar.');
            return;
        }
        grilla.deleteRow(editIndex);
        editIndex = null;
        actualizarNumeracion();
        limpiarCamposProducto();
        limpiarProductoCombo();
    });

    if (btnLimpiar) btnLimpiar.addEventListener('click', function() {
        // FIX: No interferir en la página de edición (manejada por despachosinternos_edicion.js)
        if (window._esPaginaEdicion) {
            return;
        }
        // Limpiar únicamente los campos relacionados al producto (producto, codigo, cantidad, comentarios)
        // y restaurar el combo de productos. Esto asegura que el botón 'Agregar' vuelva a su estado.
        try { if (typeof limpiarCamposProducto === 'function') limpiarCamposProducto(); } catch(e){}
        try { if (typeof limpiarProductoCombo === 'function') limpiarProductoCombo(); } catch(e){}
    });

    if (grilla) grilla.addEventListener('click', function(e) {
        // ...sin botones de editar/quitar en la grilla...
    });

    if (producto && codigo) {
        producto.addEventListener('change', function() {
            const opt = producto.options[producto.selectedIndex];
            codigo.value = opt ? (opt.getAttribute('data-codigo') || '') : '';
        });
    }

    window.modificarDespacho = function(id, data) {
        fetch((window.APP_URL || window.BASE_URL) + '/despachosinternos/modificar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.assign({Id: id}, data))
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                mostrarMensajeError('Despacho modificado correctamente.');
                bloquearControles();
            } else {
                // Manejar error de límite de modificaciones
                if (res.limite_alcanzado) {
                    mostrarMensajeError('Error: Este vale alcanzó el límite de 3 modificaciones y no puede ser editado.');
                    bloquearControles();
                } else {
                    mostrarMensajeError(res.message || 'Error al modificar.');
                }
            }
        })
        .catch((err) => {
            console.log('Error en fetch modificar:', err);
            mostrarMensajeError('Error de conexión al modificar.');
        });
    }

    // --- Botón Imprimir Vale ---
    const btnImprimir = document.getElementById('btnImprimir');
    if (btnImprimir) {
        btnImprimir.addEventListener('click', async function() {
            let correlativo = document.getElementById('correlativoVale').textContent;
            // Si ya tiene el prefijo, no lo agregues de nuevo
            if (!correlativo.startsWith('VDI-')) {
                correlativo = 'VDI-' + correlativo.padStart(6, '0');
            }
            // Helper para obtener texto de un select filtrado (evita "Seleccione")
            function getSelectTextOrEmpty(id) {
                const el = document.getElementById(id);
                if (!el) return '';
                const text = el.options[el.selectedIndex]?.text || '';
                return text.toLowerCase().includes('seleccione') ? '' : text;
            }
            const vale = {
                nvale: correlativo,
                area: document.getElementById('area').value,
                subarea: getSelectTextOrEmpty('subarea'),
                emisor: window.usuarioActual || '',
                fecha: document.getElementById('fecha').value,
                hora: window.horaActual || '',
                turno: getSelectTextOrEmpty('turno'),
                despachador: getSelectTextOrEmpty('despachador'),
                recepcionista: getSelectTextOrEmpty('recepcionista'),
                verificador: getSelectTextOrEmpty('verificador')
            };
            const productos = [];
            const grilla = document.getElementById('grillaDespacho')?.querySelector('tbody');
            if (grilla) {
                Array.from(grilla.rows).forEach(row => {
                    productos.push({
                        codigo: row.cells[1].textContent, // Columna 2: Código (reordenado)
                        producto: row.cells[2].textContent, // Columna 3: Producto (reordenado)
                        cantidad: row.cells[4].textContent, // Columna 5: Cantidad  
                        unidadMedida: row.cells[3].textContent, // Columna 4: Unid Med (reordenado)
                        comentarios: row.cells[5].textContent // Columna 6: Comentarios
                    });
                });
            }
            // Formatear fecha a dd/mm/yyyy
            let fechaFormateada = '';
            if (vale.fecha && /^\d{4}-\d{2}-\d{2}$/.test(vale.fecha)) {
                const partes = vale.fecha.split('-');
                fechaFormateada = partes[2] + '/' + partes[1] + '/' + partes[0];
            } else {
                fechaFormateada = vale.fecha || '';
            }
            // Formatear hora a 12 horas con AM/PM
            let horaFormateada = '';
            if (vale.hora && /^\d{2}:\d{2}(:\d{2})?$/.test(vale.hora)) {
                let [h, m, s] = vale.hora.split(':');
                h = parseInt(h, 10);
                const ampm = h >= 12 ? 'PM' : 'AM';
                h = h % 12;
                if (h === 0) h = 12;
                horaFormateada = h + ':' + m + (s ? (':' + s) : '') + ' ' + ampm;
            } else {
                horaFormateada = vale.hora || '';
            }
            const _pp_baseAssets = (typeof window.BASE_URL !== 'undefined' && window.BASE_URL) ? window.BASE_URL : (window.location.origin + '/swlavoro/public');
            const logoUrl = `${_pp_baseAssets}/img/Logo-Lavoro-1536x442.png`;
            let firmaUrl = window.usuarioUsername ? `${_pp_baseAssets}/img/${window.usuarioUsername}.png` : '';
            if (firmaUrl) {
                try {
                    const head = await fetch(firmaUrl, { method: 'HEAD' });
                    if (!head.ok) {
                        // intentar rutas alternativas conocidas
                        const alt1 = window.location.origin + '/swlavoro/public/img/' + window.usuarioUsername + '.png';
                        const alt2 = window.location.origin + '/public/img/' + window.usuarioUsername + '.png';
                        const r1 = await fetch(alt1, { method: 'HEAD' }).catch(() => ({ ok: false }));
                        if (r1.ok) {
                            firmaUrl = alt1;
                        } else {
                            const r2 = await fetch(alt2, { method: 'HEAD' }).catch(() => ({ ok: false }));
                            if (r2.ok) firmaUrl = alt2; else firmaUrl = '';
                        }
                    }
                } catch (e) {
                    // en caso de error, no mostrar imagen
                    firmaUrl = '';
                }
            }
            let html = `<div style='font-family:Arial,sans-serif; padding:10px; font-size:0.65rem; line-height:1.08;'>
                    <div style='display:flex; align-items:center; justify-content:space-between;'>
                        <img src='${logoUrl}' style='height:26px; max-width:100px;' alt='Logo Lavoro' />
                        <span class='vale-number' style='font-size:0.78rem; font-weight:bold; border:1px solid #333; padding:1px 6px; border-radius:4px;'>${vale.nvale || ''}</span>
                    </div>
                    <h3 style='text-align:center; font-weight:bold; margin-top:6px; font-size:0.81rem; margin-bottom:4px;'>VALE DE DESPACHOS INTERNOS</h3>
                    <div class='vale-subtitle' style='text-align:center; font-size:0.56rem; margin-bottom:28px;'>ALMACÉN DE JABAS Y PARIHUELAS - HUACHIPA</div>
                        <table style='width:100%; margin-top:28px; font-size:0.58rem; border-spacing:0;'>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>AREA:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0; padding-bottom:1mm;'>${vale.area}</td>
                                <td class='label-cab' style='width:16mm; min-width:16mm; max-width:16mm; text-align:left; padding:0 0.5mm 0 0;'>FECHA:</td>
                                <td class='data-cab' style='width:32mm; min-width:32mm; max-width:32mm; text-align:left; padding:0 0 0 0; padding-bottom:1mm;'>${fechaFormateada}</td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>SUB AREA:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0; padding-bottom:1mm;'>${vale.subarea}</td>
                                <td class='label-cab' style='width:16mm; min-width:16mm; max-width:16mm; text-align:left; padding:0 0.5mm 0 0;'>TURNO:</td>
                                <td class='data-cab' style='width:32mm; min-width:32mm; max-width:32mm; text-align:left; padding:0 0 0 0; padding-bottom:1mm;'>${vale.turno}</td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>EMISOR:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0; padding-bottom:1mm;'>${vale.emisor}</td>
                                <td></td><td></td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>DESPACHADOR:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0; padding-bottom:1mm;'>${vale.despachador}</td>
                                <td></td><td></td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>RECEPCIONISTA:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0; padding-bottom:1mm;'>${vale.recepcionista}</td>
                                <td></td><td></td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>VERIFICADOR:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0; padding-bottom:1mm;'>${vale.verificador}</td>
                                <td></td><td></td>
                            </tr>
                        </table>
                    <table style='width:100%; margin-top:8px; border-collapse:collapse; font-size:0.58rem; table-layout:fixed;'>
                        <tr class="productos-header" style='font-weight:bold;'>
                            <td style='border:0.5pt solid #333; padding:3px; width:12%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; white-space:nowrap;'>CODIGO</td>
                            <td style='border:0.5pt solid #333; padding:3px; width:38%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; white-space:nowrap;'>PRODUCTO</td>
                            <td style='border:0.5pt solid #333; padding:3px; width:12%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; white-space:nowrap;'>UM</td>
                            <td style='border:0.5pt solid #333; padding:3px; width:10%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; white-space:nowrap;'>CANTIDAD</td>
                            <td style='border:0.5pt solid #333; padding:3px; width:28%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; white-space:nowrap;'>COMENTARIOS</td>
                        </tr>`;
                productos.forEach(p => {
                    html += `<tr>
                        <td class="campo-codigo" style='border:1px solid #000; padding:3px; word-break:break-word; white-space:normal; overflow-wrap:break-word;'>${p.codigo}</td>
                        <td class="campo-producto" style='border:1px solid #000; padding:3px; word-break:break-word; white-space:normal; overflow-wrap:break-word;'>${p.producto}</td>
                        <td class="campo-unidad" style='border:1px solid #000; padding:3px; word-break:break-word; white-space:normal; overflow-wrap:break-word;'>${p.unidadMedida}</td>
                        <td class="campo-cantidad" style='border:1px solid #000; padding:3px; text-align:center; word-break:break-word; white-space:normal; overflow-wrap:break-word;'>${p.cantidad}</td>
                        <td class="campo-comentarios" style='border:1px solid #000; padding:3px; word-break:break-word; white-space:normal; overflow-wrap:break-word;'>${p.comentarios}</td>
                    </tr>`;
                });
                html += `</table>
                <div style='margin-top:10px; border:0.5pt solid #333; border-radius:8px; padding:10px;'>
                    <div style='text-align:center; font-weight:600; font-size:0.76rem; margin-bottom:16px;'>FIRMAS DE VALIDACIÓN Y AUTORIZACIÓN</div>
                    <table style='width:100%; margin-top:16px; text-align:center; font-size:0.65rem;'>
                        <tr>
                            <td style='width:33%; vertical-align:bottom; box-sizing:border-box; padding-bottom:3px;'>
                                <div style='display:flex; flex-direction:column; align-items:center;'>
                                    ${firmaUrl ? `<img src='${firmaUrl}' style='height:117px; margin-bottom:2px; margin-top:0; display:block; max-width:100%;' alt='Firma Despachador' />` : '<div style="height:117px; margin-bottom:2px;"></div>'}
                                    <div style='border-top:0.25pt solid #000; width:68%; margin:12px 0 6px 0;'></div>
                                    <div style='margin-top:6px; font-size:0.58rem;'>DESPACHADOR</div>
                                </div>
                            </td>
                            <td style='width:33%; vertical-align:bottom; box-sizing:border-box; padding-bottom:3px;'>
                                <div style='display:flex; flex-direction:column; align-items:center;'>
                                    <div style='border-top:0.25pt solid #000; width:68%; margin:12px 0 6px 0;'></div>
                                    <div style='margin-top:6px; font-size:0.58rem;'>RECEPCIONISTA</div>
                                </div>
                            </td>
                            <td style='width:33%; vertical-align:bottom; box-sizing:border-box; padding-bottom:3px;'>
                                <div style='display:flex; flex-direction:column; align-items:center;'>
                                    <div style="height:117px; margin-bottom:2px;"></div>
                                    <div style='border-top:0.25pt solid #000; width:68%; margin:12px 0 6px 0;'></div>
                                    <div style='margin-top:6px; font-size:0.58rem;'>VERIFICADOR</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>`;
            try {
                // Quitar subrayados insertados como inline !important para que la vista previa no los muestre
                html = html.replace(/border-bottom:\s*0\.6pt\s*solid\s*#333\s*!important;/g, '');
                // Eliminar bloques .data-line si existen
                html = html.replace(/<div[^>]*class=("|')?data-line("|')?[^>]*>[\s\S]*?<\/div>/gi, '');
            } catch (e) { console.warn('[vista previa] error limpiando html:', e); }
            document.getElementById('valePreviewContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('modalValePreview'));
            modal.show();
        });
    }

    // Imprimir vale (un vale por hoja en A4 vertical)
    const btnValePrint = document.getElementById('btnValePrint');
    if (btnValePrint) {
        btnValePrint.addEventListener('click', function() {
            let printContents = document.getElementById('valePreviewContent').innerHTML;
            const baseUrl = window.location.origin;
            printContents = printContents.replace(
                /src=('|")(.*?)('|\")/g,
                function(match, quote1, url, quote2) {
                    if (url.match(/^https?:\/\//)) return match;
                    if (url.indexOf('/img/') !== -1) {
                        const correctedUrl = url.replace(/^\/?(public\/)?/, '/swlavoro/public/');
                        const absoluteUrl = baseUrl + correctedUrl;
                        return `src=${quote1}${absoluteUrl}${quote2}`;
                    }
                    return match;
                }
            );
            try {
                printContents = printContents.replace(/border-bottom:\s*0\.6pt\s*solid\s*#333\s*!important;/g, 'border-bottom: 0.6pt solid #333;');
                printContents = printContents.replace(/<div[^>]*class=("|')?data-line("|')?[^>]*>[\s\S]*?<\/div>/gi, '');
            } catch (e) {}
            var pc = document.createElement('div');
            pc.id = 'print-c';
            pc.style.cssText = 'display:none;';
            pc.innerHTML = '<style>@page{size:A4 portrait;margin:5mm;}@media print{html{font-size:18px!important;}body{margin:0;padding:0}body>:not(#print-c){display:none!important}#print-c{display:block!important;width:100%;background:#fff;}.vale-frame{border:1px solid #222;border-radius:10px;padding:14px;box-sizing:border-box;min-height:100vh;background:#fff;}.vale-frame table{margin-bottom:10px;}.vale-frame table td{padding:7px 9px;}</style><div class="vale-frame">'+printContents+'</div>';
            document.body.appendChild(pc);
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalValePreview'));
            if (modal) modal.hide();
            setTimeout(function(){ window.print(); setTimeout(function(){ if(pc.parentNode) pc.parentNode.removeChild(pc); },500); }, 400);
        });
        window.addEventListener('afterprint', function() {
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalValePreview'));
            if (modal) modal.hide();
        });
    }

    // --- Helper robusto para obtener siguiente correlativo (intenta rutas alternativas) ---
    function getSiguienteVale() {
        const endpoints = [
            // Preferir ruta bonita si .htaccess/mod_rewrite está activo
            (window.BASE_URL || '') + '/despachosinternos/siguienteVale',
            // Fallbacks con index.php?url= para entornos sin rewrite
            (window.BASE_URL || '') + '/index.php?url=despachosinternos/siguienteVale',
            (window.APP_URL || '') + '/index.php?url=despachosinternos/siguienteVale'
        ];

        function tryEndpoint(i) {
            if (i >= endpoints.length) return Promise.resolve(null);
            const url = endpoints[i];
            return fetch(url, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(res => {
                    if (res && res.success && res.correlativo) return res;
                    return tryEndpoint(i + 1);
                })
                .catch(() => tryEndpoint(i + 1));
        }

        return tryEndpoint(0);
    }

    // --- Solicitar correlativo al cargar y bloquear controles ---
    getSiguienteVale().then(res => {
        if (res && res.success && res.correlativo) {
            let num = res.correlativo.toString().replace(/^VDI-/, '').padStart(6, '0');
            if (correlativoVale) correlativoVale.textContent = `VDI-${num}`;
        }
    });

    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            // Forzar habilitación de controles (incluso si estaban bloqueados por límite)
            habilitarControles();
            try {
                // Quitar cualquier marca de id en el correlativo (preparar nuevo registro)
                if (correlativoVale) correlativoVale.removeAttribute('data-id');
                // Limpiar mensajes de error persistentes
                const mensaje = document.getElementById('mensajeError');
                if (mensaje) { mensaje.classList.add('d-none'); mensaje.textContent = ''; }
                // Forzar reactivación de Choices si existen
                if (window.choicesInstances) {
                    Object.values(window.choicesInstances).forEach(inst => {
                        try { inst.enable(); } catch(e){ /* ignore */ }
                    });
                }
                // Intentar recalcular wrapping del producto
                if (window.updateProductoChoiceWrap) setTimeout(window.updateProductoChoiceWrap, 100);
            } catch (e) { console.warn('btnNuevo: error forzando reactivar controles', e); }
            
            // Mantener el selector de turno deshabilitado incluso con "Nuevo"
            if (turnoSelect) {
                turnoSelect.setAttribute('disabled', 'disabled');
                if (window.choicesInstances['turno']) {
                    window.choicesInstances['turno'].disable();
                }
            }
            
            // Limpiar grilla de productos ANTES de limpiarProductoCombo para que
            // limpiarProductoCombo() vea la grilla vacía y NO filtre productos
            try {
                const grillaBody = document.getElementById('grillaDespacho')?.querySelector('tbody');
                if (grillaBody) {
                    grillaBody.innerHTML = '';
                }
                // Si existe arreglo de datos de grilla en modo edición, vaciarlo también
                try { if (typeof datosGrilla !== 'undefined') datosGrilla = []; } catch(e){}
                // Reset estado de edición
                editIndex = null; updatePhase = null;
                // Actualizar numeración si la función existe
                try { if (typeof actualizarNumeracion === 'function') actualizarNumeracion(); } catch(e){}
            } catch(e) { console.warn('btnNuevo: error limpiando grilla', e); }
            // Configurar restricción de fecha para nuevo registro (solo permite hasta hoy)
            configurarFechaInput();
            // Establecer la fecha actual (usando métodos de fecha local)
            const fechaEl = document.getElementById('fecha');
            if (fechaEl) {
                var d = new Date();
                fechaEl.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            }

            // Solicitar siguiente correlativo para un nuevo vale
            getSiguienteVale().then(res => {
                if (res && res.success && res.correlativo && correlativoVale) {
                    let num = res.correlativo.toString().replace(/^VDI-/, '').padStart(6, '0');
                    correlativoVale.textContent = `VDI-${num}`;
                } else if (correlativoVale) {
                    correlativoVale.textContent = '';
                }
            }).catch(() => { if (correlativoVale) correlativoVale.textContent = ''; });
            
            limpiarControles();
            try { if (typeof limpiarProductoCombo === 'function') limpiarProductoCombo(); } catch(e) {}
            
            if (btnModificar) btnModificar.setAttribute('disabled', 'disabled');
            if (btnGuardar) btnGuardar.removeAttribute('disabled');
            if (btnAgregar) btnAgregar.removeAttribute('disabled');
            if (btnLimpiar) btnLimpiar.removeAttribute('disabled');
            if (btnAnular) btnAnular.setAttribute('disabled', 'disabled');
            if (btnQuitar) btnQuitar.removeAttribute('disabled');
        });
    }

    const subareaSelect = document.getElementById('subarea');
    const areaInput = document.getElementById('area');
    if (subareaSelect && areaInput) {
        subareaSelect.addEventListener('change', function() {
            const selectedOption = subareaSelect.options[subareaSelect.selectedIndex];
            const areaNombre = selectedOption.getAttribute('data-area-nombre') || '';
            areaInput.value = areaNombre;
        });
    }

    if (btnGuardar) {
        // Flag para prevenir doble clic
        var _guardandoDespIntEnProceso = false;
        
        btnGuardar.addEventListener('click', function() {
            // FIX: Si estamos en la página de edición (despachosinternos/edicion),
            // el listener de despachosinternos_edicion.js es el que maneja el guardado.
            // Este listener solo debe ejecutarse en la página principal (index).
            if (window._esPaginaEdicion) {
                console.log('[DespachosInternos] Página de edición detectada, ignorando listener principal de guardado');
                return;
            }
            
            // FIX: Prevenir doble clic
            if (_guardandoDespIntEnProceso) return;
            _guardandoDespIntEnProceso = true;
            
            // Deshabilitar botón inmediatamente
            try {
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Guardando...';
            } catch(e) {}
            
            // Recolectar datos del formulario
            const correlativoValeEl = document.getElementById('correlativoVale');
            const correlativoVale = correlativoValeEl.textContent;
            const fecha = document.getElementById('fecha').value;
            const turno = document.getElementById('turno').value;
            const subarea = getFieldValue('subarea');
            // Determinar areaId buscando la opción cuyo value coincide con subarea
            let areaId = '';
            const subareaSelect = document.getElementById('subarea');
            if (subareaSelect) {
                const opt = Array.from(subareaSelect.options).find(o => o.value == subarea);
                areaId = opt ? (opt.getAttribute('data-area') || '') : '';
            }
            const despachador = getFieldValue('despachador');
            const recepcionista = getFieldValue('recepcionista');
            const verificador = getFieldValue('verificador');
            // Productos de la grilla
            const productos = [];
            const grilla = document.getElementById('grillaDespacho')?.querySelector('tbody');
            if (grilla) {
                Array.from(grilla.rows).forEach(row => {
                    productos.push({
                        codigo: row.getAttribute('data-codigo') || row.cells[1].textContent, // Columna 2: Código (reordenado)
                        producto: row.getAttribute('data-producto') || row.cells[2].textContent, // Columna 3: Producto (reordenado)
                        cantidad: row.getAttribute('data-cantidad') || row.cells[4].textContent, // Columna 5: Cantidad
                        unidadMedida: row.getAttribute('data-unidadmedida') || row.cells[3].textContent, // Columna 4: Unid Med (reordenado)
                        comentarios: row.getAttribute('data-comentarios') || row.cells[5].textContent // Columna 6: Comentarios
                    });
                });
            }
            // Validación básica con diagnóstico de campos faltantes
            // DEBUG: imprimir estados de controles relevantes
            try {
                console.debug('DEBUG guardar - elementos:', {
                    subareaSelectExists: !!subareaSelect,
                    subareaValue: subarea,
                    subareaSelectedIndex: subareaSelect ? subareaSelect.selectedIndex : null,
                    subareaOptionsLen: subareaSelect ? subareaSelect.options.length : 0,
                    areaIdComputed: areaId,
                    despachadorValue: document.getElementById('despachador') ? document.getElementById('despachador').value : null,
                    recepcionistaValue: document.getElementById('recepcionista') ? document.getElementById('recepcionista').value : null,
                    verificadorValue: document.getElementById('verificador') ? document.getElementById('verificador').value : null,
                    choicesInstancesKeys: Object.keys(window.choicesInstances || {})
                });
            } catch(e) { console.debug('DEBUG guardar: error al imprimir diagnostico', e); }
            const faltantes = [];
            if (!correlativoVale) faltantes.push('correlativo');
            if (!fecha) faltantes.push('fecha');
            if (!turno) faltantes.push('turno');
            if (!areaId) faltantes.push('areaId');
            if (!subarea) faltantes.push('subarea');
            if (!despachador) faltantes.push('despachador');
            if (!recepcionista) faltantes.push('recepcionista');
            if (!verificador) faltantes.push('verificador');
            if (productos.length === 0) faltantes.push('productos');
            if (faltantes.length > 0) {
                try {
                    const grillaBody = document.getElementById('grillaDespacho')?.querySelector('tbody');
                    const rows = grillaBody ? Array.from(grillaBody.rows).map(r => ({
                        codigo: r.getAttribute('data-codigo') || r.cells[1]?.textContent,
                        producto: r.getAttribute('data-producto') || r.cells[2]?.textContent,
                        cantidad: r.getAttribute('data-cantidad') || r.cells[4]?.textContent
                    })) : [];
                    console.debug('Validación fallida - campos faltantes:', faltantes, 'grillaRowsCount:', rows.length, 'grillaRows:', rows);
                } catch(e) { console.debug('Error construyendo debug grilla', e); }
                mostrarMensajeError('Por favor, complete todos los campos y agregue al menos un producto. (' + faltantes.join(', ') + ')');
                _guardandoDespIntEnProceso = false;
                if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.textContent = 'Guardar'; }
                return;
            }
            // Detectar si es modificación
            let despachoId = correlativoValeEl.getAttribute('data-id');
            // Si el valor es null, undefined o vacío, forzar a null
            if (!despachoId || despachoId === 'null' || despachoId === 'undefined') {
                despachoId = null;
            }
            const payload = {
                correlativoVale,
                fecha,
                turno,
                area: areaId,
                subarea,
                despachador,
                recepcionista,
                verificador,
                productos
            };
            let url = window.APP_URL + '/despachosinternos/guardar';
            if (despachoId) {
                payload.Id = despachoId;
                url = window.APP_URL + '/despachosinternos/modificar';
            }
            // Enviar datos al backend
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    mostrarMensajeError(despachoId ? 'Despacho modificado correctamente.' : 'Despacho guardado correctamente.');
                    // Resetear flag y texto del botón antes de bloquear
                    _guardandoDespIntEnProceso = false;
                    if (btnGuardar) { btnGuardar.textContent = 'Guardar'; }
                    bloquearControles();
                    if (btnNuevo) btnNuevo.removeAttribute('disabled');
                    if (btnModificar) btnModificar.removeAttribute('disabled');
                    // Actualizar el data-id siempre que el backend devuelva el id
                    if (res.id) {
                        correlativoValeEl.setAttribute('data-id', res.id);
                    }
                } else {
                    // Manejar error de límite de modificaciones
                    if (res.limite_alcanzado) {
                        mostrarMensajeError('Error: Este vale alcanzó el límite de 3 modificaciones y no puede ser editado.');
                        bloquearControles();
                        if (btnGuardar) btnGuardar.setAttribute('disabled', 'disabled');
                        if (btnModificar) btnModificar.setAttribute('disabled', 'disabled');
                    } else {
                        mostrarMensajeError(res.message || 'Error al guardar.');
                    }
                    // Restaurar botón
                    _guardandoDespIntEnProceso = false;
                    if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.textContent = 'Guardar'; }
                }
            })
            .catch((err) => {
                console.log('Error en fetch guardar/modificar:', err);
                mostrarMensajeError('Error de conexión al guardar/modificar.');
                _guardandoDespIntEnProceso = false;
                if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.textContent = 'Guardar'; }
            });
        });
    }

    // El listener de btnModificar ya está registrado arriba con validación de límite de modificaciones
});