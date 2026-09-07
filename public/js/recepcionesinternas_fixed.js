// public/js/recepcionesinternos.js
// LÃ³gica centralizada: inicializaciÃ³n Choices.js, combos, grilla, botones y estilos
// VERSION: 2026-02-22-FIX-BUCLE-v3
console.log('%c[RecepcionesInternas] VERSION 2026-02-22-FIX-BUCLE-v3 CARGADA', 'color: green; font-weight: bold');
document.addEventListener('DOMContentLoaded', function() {
    // Configurar fecha actual como fecha mÃ¡xima por defecto
    configurarFechaInput();
    
    // FunciÃ³n para cargar un recepción existente y preparar para modificar
    // FunciÃ³n para configurar el campo de fecha con restricciones
    function configurarFechaInput(fechaRegistro) {
        const inputFecha = document.getElementById('fecha');
        if (inputFecha) {
            // Establecer fecha mÃ¡xima como la fecha del registro
            // Esto impedirÃ¡ seleccionar fechas posteriores a la fecha original del registro
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

    window.cargarRecepcionParaEditar = function(id) {
        fetch(window.BASE_URL + '/recepcionesinternas/getRecepcion', {
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
                    let num = d.NVale.toString().replace(/^VRI-/, '').padStart(6, '0');
                    document.getElementById('correlativoVale').textContent = `VRI-${num}`;
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
                document.getElementById('medioTransporte').value = d.medioTransporte || '';
                if (window.choicesInstances['medioTransporte']) window.choicesInstances['medioTransporte'].setChoiceByValue(d.medioTransporte || '');
                document.getElementById('verificador').value = d.Verificador || '';
                if (window.choicesInstances['verificador']) window.choicesInstances['verificador'].setChoiceByValue(d.Verificador || '');
                // Limpiar y cargar productos en la grilla
                const grilla = document.getElementById('grillaRecepcion')?.querySelector('tbody');
                if (grilla) {
                    grilla.innerHTML = '';
                    (d.productos || []).forEach(prod => {
                        const row = grilla.insertRow();
                        row.setAttribute('data-codigo', prod.CodigoProducto || '');
                        row.setAttribute('data-producto', prod.DescripcionProducto || '');
                        row.setAttribute('data-cantidad', prod.Cantidad || '');
                        row.setAttribute('data-unidadmedida', prod.UnidadMedida || '');
                        row.setAttribute('data-comentarios', prod.Comentarios || '');
                            const prodId = prod.Id || prod.ProductoId || prod.IdProducto || prod.Producto || '';
                            row.setAttribute('data-producto-id', prodId || '');
                row.innerHTML = '<td style="padding:4px;border:1px solid #333;width:6%;"></td>' +
                    '<td style="padding:4px;border:1px solid #333;width:20%;word-break:break-word;white-space:normal;overflow-wrap:break-word;">' + (prod.CodigoProducto || '') + '</td>' +
                    '<td style="padding:4px;border:1px solid #333;width:40%;word-break:break-word;white-space:normal;overflow-wrap:break-word;">' + (prod.DescripcionProducto || '') + '</td>' +
                    '<td style="padding:4px;border:1px solid #333;width:12%;text-align:center;word-break:break-word;white-space:normal;overflow-wrap:break-word;">' + (prod.UnidadMedida || '') + '</td>' +
                    '<td style="padding:4px;border:1px solid #333;width:10%;text-align:right;word-break:break-word;white-space:normal;overflow-wrap:break-word;">' + (prod.Cantidad || '') + '</td>' +
                    '<td style="padding:4px;border:1px solid #333;width:18%;word-break:break-word;white-space:normal;overflow-wrap:break-word;">' + (prod.Comentarios || '') + '</td>';
                    });
                    // Actualizar numeraciÃ³n
                    Array.from(grilla.rows).forEach((row, i) => { row.cells[0].textContent = i + 1; });
                }
                // Habilitar botÃ³n modificar
                if (btnModificar) btnModificar.removeAttribute('disabled');
                bloquearControles();
                if (btnModificar) btnModificar.removeAttribute('disabled');
            } else {
                mostrarMensajeError(res.message || 'No se pudo cargar el recepción.');
            }
        })
        .catch(() => mostrarMensajeError('Error de conexiÃ³n al cargar recepción.'));
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
    let recepciónId = null;
    
    // Convertir comentarios a mayÃºsculas y aplicar autocorrecciÃ³n
    if (comentarios) {
        comentarios.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Agregar autocorrecciÃ³n al perder el foco
        comentarios.addEventListener('blur', function() {
            const textoOriginal = this.value;
            if (typeof autocorregirTexto !== 'function') {
                console.error('Error: La funciÃ³n autocorregirTexto no estÃ¡ definida');
                return;
            }
            try {
                const resultado = autocorregirTexto(textoOriginal, true, this);
                // Corregir automÃ¡ticamente el valor del textarea
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
                console.error('Error al aplicar autocorrecciÃ³n:', error);
            }
        });
    }
    
    // Al cambiar producto, rellenar unidad de medida
    if (producto) {
        producto.addEventListener('change', function() {
            // Evitar que este handler actúe cuando la edición de fila está bloqueando
            // las actualizaciones para prevenir sincronizaciones automáticas.
            if (window.__recep_block_grid_updates) return;
            const selected = producto.options[producto.selectedIndex];
            if (!selected || !selected.value) {
                if (typeof unidadMedida !== 'undefined' && unidadMedida) unidadMedida.value = '';
                if (typeof codigo !== 'undefined' && codigo) codigo.value = '';
                return;
            }
            // Buscar la unidad de medida y cÃ³digo en window.productosData
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
    // Deshabilitar Agregar al cargar la pÃ¡gina
    if (btnAgregar) btnAgregar.setAttribute('disabled', 'disabled');

    // Helper: aplicar/remover estilos visuales inline a un contenedor Choices asociado a un <select>
    function applyChoicesVisualDisabled(selectEl, disabled) {
        if (!selectEl) return;
        // localizar contenedor .choices que contenga o esté adyacente al select
        let choicesEl = null;
        try { choicesEl = selectEl.closest && selectEl.closest('.choices'); } catch(e) { choicesEl = null; }
        if (!choicesEl) choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(selectEl)) || null;
        if (!choicesEl && selectEl.nextElementSibling && selectEl.nextElementSibling.classList && selectEl.nextElementSibling.classList.contains('choices')) choicesEl = selectEl.nextElementSibling;
        if (!choicesEl) return;

        const inner = choicesEl.querySelector('.choices__inner');
        if (disabled) {
            choicesEl.classList.add('is-disabled');
            choicesEl.setAttribute('aria-disabled', 'true');
            try { choicesEl.style.setProperty('background-color', '#e9ecef', 'important'); } catch(e){}
            try { choicesEl.style.setProperty('border-color', '#ced4da', 'important'); } catch(e){}
            try { choicesEl.style.setProperty('box-shadow', 'none', 'important'); } catch(e){}
            try { choicesEl.style.setProperty('background-image', 'none', 'important'); } catch(e){}
            if (inner) {
                inner.setAttribute('aria-disabled', 'true');
                try { inner.style.setProperty('background-color', '#e9ecef', 'important'); } catch(e){}
                try { inner.style.setProperty('color', '#6c757d', 'important'); } catch(e){}
                try { inner.style.setProperty('border-color', '#ced4da', 'important'); } catch(e){}
                try { inner.style.setProperty('box-shadow', 'none', 'important'); } catch(e){}
                try { inner.style.setProperty('background-image', 'none', 'important'); } catch(e){}
            }
        } else {
            choicesEl.classList.remove('is-disabled');
            choicesEl.removeAttribute('aria-disabled');
            try { choicesEl.style.removeProperty('background-color'); } catch(e){}
            try { choicesEl.style.removeProperty('border-color'); } catch(e){}
            try { choicesEl.style.removeProperty('box-shadow'); } catch(e){}
            try { choicesEl.style.removeProperty('background-image'); } catch(e){}
            if (inner) {
                inner.removeAttribute('aria-disabled');
                try { inner.style.removeProperty('background-color'); } catch(e){}
                try { inner.style.removeProperty('color'); } catch(e){}
                try { inner.style.removeProperty('border-color'); } catch(e){}
                try { inner.style.removeProperty('box-shadow'); } catch(e){}
                try { inner.style.removeProperty('background-image'); } catch(e){}
            }
        }
    }
    // Exponer en window para que otros scripts puedan invocarla
    try { window.applyChoicesVisualDisabled = applyChoicesVisualDisabled; } catch(e) {}

    function bloquearControles() {
        [
            'fecha', 'subarea', 'despachador', 'medioTransporte', 'verificador',
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
        // Aplicar estilos inline en los contenedores generados por Choices.js
        try {
            document.querySelectorAll('select.custom-dropdown, select.form-select').forEach(sel => {
                // Encontrar el contenedor .choices de forma robusta: puede envolver al <select>
                let choicesEl = null;
                try {
                    choicesEl = sel.closest && sel.closest('.choices');
                } catch(e) { choicesEl = null; }
                if (!choicesEl) {
                    choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(sel)) || null;
                }
                if (!choicesEl && sel.nextElementSibling && sel.nextElementSibling.classList && sel.nextElementSibling.classList.contains('choices')) {
                    choicesEl = sel.nextElementSibling;
                }
                if (choicesEl) {
                    choicesEl.classList.add('is-disabled');
                    choicesEl.setAttribute('aria-disabled', 'true');
                    try {
                        choicesEl.style.setProperty('background-color', '#e9ecef', 'important');
                        choicesEl.style.setProperty('border-color', '#ced4da', 'important');
                        choicesEl.style.setProperty('box-shadow', 'none', 'important');
                        choicesEl.style.setProperty('background-image', 'none', 'important');
                    } catch(e) {}
                    const inner = choicesEl.querySelector('.choices__inner');
                    if (inner) {
                        inner.setAttribute('aria-disabled', 'true');
                        try { inner.style.setProperty('background-color', '#e9ecef', 'important'); } catch(e) {}
                        try { inner.style.setProperty('color', '#6c757d', 'important'); } catch(e) {}
                        try { inner.style.setProperty('border-color', '#ced4da', 'important'); } catch(e) {}
                        try { inner.style.setProperty('box-shadow', 'none', 'important'); } catch(e) {}
                        try { inner.style.setProperty('background-image', 'none', 'important'); } catch(e) {}
                    }
                }
            });
        } catch(e) { /* ignore */ }
        if (btnGuardar) btnGuardar.setAttribute('disabled', 'disabled');
        if (btnAnular) btnAnular.setAttribute('disabled', 'disabled');
        if (btnQuitar) btnQuitar.setAttribute('disabled', 'disabled');
        if (btnLimpiar) btnLimpiar.setAttribute('disabled', 'disabled');
        if (btnAgregar) btnAgregar.setAttribute('disabled', 'disabled');
    }

    function habilitarControles() {
        [
            'fecha', 'subarea', 'despachador', 'medioTransporte', 'verificador',
            'producto', 'cantidad', 'comentarios'
        ].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.removeAttribute('disabled');
        });
        // Nota: no aplicar aún el disabled visual a `area`, `codigo` y `turno` aquí
        // porque más abajo re-aplicamos estilos a los contenedores Choices después
        // de habilitar/deshabilitar instancias. Haremos esto al final.
        // (Se re-aplicará explícitamente al final de la función.)
        document.getElementById('chkTurno').removeAttribute('disabled');
        if (window.choicesInstances) {
            Object.values(window.choicesInstances).forEach(inst => inst.enable());
        }
        // Quitar estilos inline aplicados anteriormente
        try {
            document.querySelectorAll('select.custom-dropdown, select.form-select').forEach(sel => {
                // Encontrar el contenedor .choices de forma robusta: puede envolver al <select>
                let choicesEl = null;
                try {
                    choicesEl = sel.closest && sel.closest('.choices');
                } catch(e) { choicesEl = null; }
                if (!choicesEl) {
                    choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(sel)) || null;
                }
                if (!choicesEl && sel.nextElementSibling && sel.nextElementSibling.classList && sel.nextElementSibling.classList.contains('choices')) {
                    choicesEl = sel.nextElementSibling;
                }
                if (choicesEl) {
                    choicesEl.classList.remove('is-disabled');
                    choicesEl.removeAttribute('aria-disabled');
                    try { choicesEl.style.removeProperty('background-color'); choicesEl.style.removeProperty('border-color'); choicesEl.style.removeProperty('box-shadow'); choicesEl.style.removeProperty('background-image'); } catch(e){}
                    const inner = choicesEl.querySelector('.choices__inner');
                    if (inner) {
                        inner.removeAttribute('aria-disabled');
                        try { inner.style.removeProperty('background-color'); } catch(e){}
                        try { inner.style.removeProperty('color'); } catch(e){}
                        try { inner.style.removeProperty('border-color'); } catch(e){}
                        try { inner.style.removeProperty('box-shadow'); } catch(e){}
                        try { inner.style.removeProperty('background-image'); } catch(e){}
                    }
                }
            });
        } catch(e) { /* ignore */ }
    // Re-aplicar estado 'disabled' visual y funcional a los controles que deben quedar bloqueados
    try {
        const keepDisabled = ['area', 'codigo', 'turno'];
        keepDisabled.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            // Asegurar atributo disabled en el control
            try { el.setAttribute('disabled', 'disabled'); } catch(e) {}
            // Si es un select gestionado por Choices, deshabilitar la instancia y aplicar estilo visual
            if (el.tagName === 'SELECT' && window.choicesInstances && window.choicesInstances[id]) {
                try { window.choicesInstances[id].disable(); } catch(e) {}
                try { if (window.applyChoicesVisualDisabled) window.applyChoicesVisualDisabled(el, true); } catch(e) {}
            } else {
                // Para inputs normales (ej. codigo), aplicar estilos inline para que se vean grisados
                try { el.style.setProperty('background-color', '#e9ecef', 'important'); } catch(e) {}
                try { el.style.setProperty('color', '#6c757d', 'important'); } catch(e) {}
            }
        });
    } catch(e) { /* ignore */ }
    if (btnGuardar) btnGuardar.removeAttribute('disabled');
    if (btnAnular) btnAnular.removeAttribute('disabled');
    if (btnQuitar) btnQuitar.removeAttribute('disabled');
    if (btnLimpiar) btnLimpiar.removeAttribute('disabled');
    if (btnAgregar) btnAgregar.removeAttribute('disabled');
    }

    // Estado inicial de controles
    bloquearControles();
    if (btnNuevo) btnNuevo.removeAttribute('disabled');

    // BotÃ³n Modificar: habilita los controles para ediciÃ³n y activa modo ediciÃ³n
    if (btnModificar) {
        btnModificar.setAttribute('disabled', 'disabled');
        btnModificar.addEventListener('click', function() {
            habilitarControles();
            // No modificar el atributo data-id aquÃ­, solo habilitar controles
        });
    }

    // --- InicializaciÃ³n Choices.js en todos los combos ---
    window.choicesInstances = {};
    
    // Declarar elementos de turno al inicio
    const turnoSelect = document.getElementById('turno');
    const chkTurnoManual = document.getElementById('chkTurno');
    const turnos = window.turnosData || [];
    
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

        // Si el select ya está deshabilitado, forzamos el estilo visual inmediatamente
        try { if (select.disabled) applyChoicesVisualDisabled(select, true); } catch(e) {}
        
        // Listeners de wrapping DESHABILITADOS para evitar bucles infinitos
        
        select.addEventListener('showDropdown', function() {
            setTimeout(function() {
                const input = select.parentElement.querySelector('.choices__input');
                if (input) {
                    input.style.display = 'block';
                    input.placeholder = 'Buscar...';
                    input.focus();
                }
            }, 100);

            try { moveChoicesDropdownToBody(select); } catch(e) { console.warn('[RecepcionesInternas] move dropdown', e); }
        });

        select.addEventListener('hideDropdown', function() {
            try { restoreChoicesDropdown(select); } catch(e) { console.warn('[RecepcionesInternas] restore dropdown', e); }
        });
        
        // IMPORTANTE: Agregar listener de 'change' para actualizar wrapping
        select.addEventListener('change', function() {
            // Ejecutar después de que Choices.js actualice su DOM
            setTimeout(() => {
                if (typeof window.updateAllChoicesWrap === 'function') {
                    window.updateAllChoicesWrap();
                }
            }, 100);
        });
    });

    // Deshabilitar selector de turno al cargar la interfaz
    if (turnoSelect) {
        turnoSelect.setAttribute('disabled', 'disabled');
        if (window.choicesInstances['turno']) {
            window.choicesInstances['turno'].disable();
        }
    }

    // --- LÃ³gica de Turno automÃ¡tica ---
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
            turnoSelect.setAttribute('disabled', 'disabled');
            if (window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
        }, 200);
    }
    if (chkTurnoManual && turnoSelect) {
        chkTurnoManual.addEventListener('change', function() {
            if (this.checked) {
                turnoSelect.removeAttribute('disabled');
                if (window.choicesInstances['turno']) window.choicesInstances['turno'].enable();
                try { if (window.applyChoicesVisualDisabled) window.applyChoicesVisualDisabled(turnoSelect, false); } catch(e) {}
            } else {
                turnoSelect.setAttribute('disabled', 'disabled');
                if (window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
                try { if (window.applyChoicesVisualDisabled) window.applyChoicesVisualDisabled(turnoSelect, true); } catch(e) {}
                setTurnoByHora();
            }
        });
        if (window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
    }

    // --- Helpers simples para elevar dropdowns de Choices ---
    function moveChoicesDropdownToBody(select) {
        var container = select.parentElement.querySelector('.choices');
        if (!container) return;
        var dropdown = container.querySelector('.choices__list--dropdown');
        if (!dropdown) return;
        dropdown.style.zIndex = '9999';
        document.body.classList.add('select-open');
    }

    function restoreChoicesDropdown(select) {
        setTimeout(function() {
            document.body.classList.remove('select-open');
        }, 200);
    }

    function repositionPortal(select) {
        // No necesario en versión simplificada
    }

    // --- LÃ³gica de la grilla y botones ---
    const grilla = document.getElementById('grillaRecepcion')?.querySelector('tbody');
    let editIndex = null;

    function limpiarControles() {
        producto.value = '';
        if (window.choicesInstances && window.choicesInstances['producto']) {
            window.choicesInstances['producto'].setChoiceByValue('');
        }
        codigo.value = '';
        cantidad.value = '';
        comentarios.value = '';
        editIndex = null;
        
        // Restablecer el texto y la clase del botÃ³n si estÃ¡bamos en modo ediciÃ³n
            if (btnAgregar && (btnAgregar.dataset.mode === 'update' || btnAgregar.textContent === 'Actualizar')) {
                btnAgregar.textContent = 'Agregar';
                try { btnAgregar.dataset.mode = 'add'; } catch(e) {}
                btnAgregar.classList.remove('btn-primary');
                btnAgregar.classList.add('btn-success');
        }
        
        // Limpiar la selecciÃ³n de la grilla
        if (grilla) grilla.querySelectorAll('tr').forEach(tr => tr.classList.remove('table-active'));
        // Formatear correlativo si es solo nÃºmero o si perdiÃ³ el prefijo
        let corr = correlativoVale.textContent;
        if (/^\d+$/.test(corr)) {
            correlativoVale.textContent = `VRI-${corr.padStart(6, '0')}`;
        } else if (/^VRI-?\d+$/.test(corr)) {
            let num = corr.replace(/^VRI-?/, '').padStart(6, '0');
            correlativoVale.textContent = `VRI-${num}`;
        }
    }

    function limpiarProductoCombo() {
        const productoSelect = document.getElementById('producto');
        if (!productoSelect) return;
        
        productoSelect.value = '';
        
        // Obtener las descripciones de productos ya agregados a la grilla
        const descripcionesEnGrilla = new Set();
        const grilla = document.getElementById('grillaRecepcion')?.querySelector('tbody');
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
        
        // Volver a agregar la opciÃ³n "Seleccione"
        const selectOption = productoSelect._allOptions.find(opt => !opt.value);
        if (selectOption) productoSelect.appendChild(selectOption.cloneNode(true));
        
        // Agregar solo las opciones que no estÃ¡n en la grilla
        let productosAgregados = 0;
        productoSelect._allOptions.forEach(opt => {
            if (!opt.value) return; // Saltamos la opciÃ³n "Seleccione"
            
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
    }

    function obtenerDatos() {
        const prodOpt = producto.options[producto.selectedIndex];
        // Obtener unidad de medida del option seleccionado si existe, o vacÃ­o
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
        if (!datos.cantidad || isNaN(datos.cantidad) || Number(datos.cantidad) <= 0) return 'Ingrese una cantidad vÃ¡lida';
        return null;
    }

    function agregarFila(datos) {
        if (!grilla) return;
        const row = grilla.insertRow();
        // Guardar los datos originales como atributos data-*
        row.setAttribute('data-codigo', datos.codigo);
        row.setAttribute('data-producto', datos.productoText);
        if (datos.productoId) row.setAttribute('data-producto-id', datos.productoId);
        row.setAttribute('data-cantidad', datos.cantidad);
        row.setAttribute('data-unidadmedida', datos.unidadMedida || '');
        row.setAttribute('data-comentarios', datos.comentarios);
        row.innerHTML = '<td></td>' +
            '<td>' + datos.codigo + '</td>' +
            '<td>' + datos.productoText + '</td>' +
            '<td>' + (datos.unidadMedida || '') + '</td>' +
            '<td>' + datos.cantidad + '</td>' +
            '<td>' + datos.comentarios + '</td>';
        row.addEventListener('click', function() {
            grilla.querySelectorAll('tr').forEach(tr => tr.classList.remove('table-active'));
            row.classList.add('table-active');
            // SILENCIADO: console.log('[RecepcionesInternas] row click handler invoked; rowIndex=', row.rowIndex - 1, 'blockFlag=', !!window.__recep_block_grid_updates);
            // Si hay un bloqueo global de edición (otro módulo quiere controlar cuándo cargar),
            // no realizar la carga automática de la fila en los controles.
            if (window.__recep_block_grid_updates) {
                // SILENCIADO: console.log('[RecepcionesInternas] row click handler: exiting early due to block flag');
                // Sólo cambiar el texto del botón y salir
                if (btnAgregar && btnAgregar.textContent !== 'Actualizar') {
                    btnAgregar.textContent = 'Actualizar';
                    btnAgregar.classList.remove('btn-success');
                    btnAgregar.classList.add('btn-primary');
                }
                return;
            }

            // Marcar el índice seleccionado y activar la bandera que bloquea cargas automáticas
            try { editIndex = row.rowIndex - 1; } catch(e) { editIndex = null; }
            try { window.__recep_block_grid_updates = true; } catch(e) {}
            // Cambiar el botón a "Actualizar" (sin poblar aún los controles)
            if (btnAgregar && btnAgregar.textContent !== 'Actualizar') {
                btnAgregar.textContent = 'Actualizar';
                btnAgregar.classList.remove('btn-success');
                btnAgregar.classList.add('btn-primary');
            }
        });
        actualizarNumeracion();
    }

    function cargarFilaEnControles(idx) {
        // SILENCIADO: console.log('[RecepcionesInternas] cargarFilaEnControles invoked idx=', idx, 'blockFlag=', !!window.__recep_block_grid_updates);
        // Si otro módulo ha marcado bloqueo (modo edición que requiere click en "Actualizar"),
        // no realizamos la carga automática de la fila en los controles.
        if (window.__recep_block_grid_updates) {
            // SILENCIADO: console.log('[RecepcionesInternas] cargarFilaEnControles aborted due to block flag');
            return;
        }
        if (!grilla) return;
        const row = grilla.rows[idx];
        if (!row) return;
        
        // Obtener el cÃ³digo y descripciÃ³n del producto seleccionado
    const codigoEdit = row.cells[1].textContent?.trim(); // Columna 2: CÃ³digo (reordenado)
    const descripcionEdit = row.cells[2].textContent?.trim(); // Columna 3: Producto (reordenado)
        
        // Limpiar el select de producto completamente
        producto.innerHTML = '';
        
        // Si hay opciones guardadas
        if (producto._allOptions) {
            // 1. Agregar opciÃ³n "Seleccione"
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
            producto.innerHTML = '';
            const prodIdFromRow = row.getAttribute('data-producto-id') || '';
            let productoEditEncontrado = false;
            producto._allOptions.forEach(opt => {
                if (!opt.value) return; // Saltamos la opciÃ³n "Seleccione"
                const codigoOpt = opt.getAttribute('data-codigo');
                // Priorizar coincidencia por id (valor de la option)
                if (prodIdFromRow && String(opt.value) === String(prodIdFromRow)) {
                    producto.appendChild(opt.cloneNode(true));
                    productoEditEncontrado = true;
                    return;
                }
                // Si no hay id disponible, o no encontramos por id, intentar por cÃ³digo
                if (codigoOpt && codigoOpt.trim() === codigoEdit) {
                    producto.appendChild(opt.cloneNode(true));
                    productoEditEncontrado = true;
                }
            });
            
            // 4. Agregar el resto de productos QUE NO ESTÁN en la grilla
            producto._allOptions.forEach(opt => {
                if (!opt.value) return; // Saltamos la opciÃ³n "Seleccione"
                
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
        // Priorizar coincidencia por id (data-producto-id en la fila). Si no existe,
        // caer a la coincidencia por texto/código como respaldo.
        if (producto.options && producto.options.length > 0) {
            const productOptions = Array.from(producto.options);
            const prodIdFromRow = row.getAttribute('data-producto-id') || '';
            let opt = null;

            if (prodIdFromRow) {
                opt = productOptions.find(o => String(o.value) === String(prodIdFromRow));
            }

            // Si no se halló por id, intentar por descripciÃ³n o cÃ³digo (respaldo)
            if (!opt) {
                opt = productOptions.find(o => 
                    o.textContent.trim() === descripcionEdit || 
                    (o.getAttribute('data-codigo') && o.getAttribute('data-codigo').trim() === codigoEdit)
                );
            }

            if (opt) {
                producto.value = opt.value;
                // Actualizar la visualizaciÃ³n de Choices
                if (window.choicesInstances && window.choicesInstances['producto']) {
                    setTimeout(() => {
                        window.choicesInstances['producto'].setChoiceByValue(opt.value);
                    }, 0);
                }
            }
        }
        // Establecer los valores de los demÃ¡s campos
        codigo.value = row.cells[1].textContent;
        cantidad.value = row.cells[4].textContent;
        if (typeof unidadMedida !== 'undefined' && unidadMedida) {
            unidadMedida.value = row.cells[3].textContent;
        }
        comentarios.value = row.cells[5].textContent;
        editIndex = idx;
        
        // Marcar que estamos en modo ediciÃ³n
        if (btnAgregar && btnAgregar.textContent === 'Agregar') {
            btnAgregar.textContent = 'Actualizar';
        }
    }

    function actualizarNumeracion() {
        if (!grilla) return;
        Array.from(grilla.rows).forEach((row, i) => {
            row.cells[0].textContent = i + 1;
        });
    }

    function mostrarMensajeError(msg) {
        var div = document.getElementById('mensajeError');
        if (!div) return;
        div.textContent = msg;
        div.classList.remove('d-none');
        setTimeout(function() {
            div.classList.add('d-none');
        }, 3200);
    }

    if (btnAgregar) btnAgregar.addEventListener('click', function() {
        // Si el botón está en 'Actualizar' y hay una fila seleccionada, primero
        // poblar los controles desde la grilla (no guardar). El siguiente
        // clic (ahora 'Guardar') realizará la actualización.
        if (btnAgregar.textContent === 'Actualizar' && editIndex !== null) {
            // Si hay una bandera global que bloqueó la carga automática,
            // temporalmente desactivarla para permitir poblar los controles.
            let prevBlock = !!window.__recep_block_grid_updates;
            try { window.__recep_block_grid_updates = false; } catch(e) {}
            cargarFilaEnControles(editIndex);
            try { if (prevBlock) window.__recep_block_grid_updates = true; } catch(e) {}
            // Cambiar el texto a 'Guardar' para que el siguiente click guarde
            btnAgregar.textContent = 'Guardar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
            return;
        }

        const datos = obtenerDatos();
        const fecha = document.getElementById('fecha').value;
        const turno = document.getElementById('turno').value;
        const subarea = document.getElementById('subarea').value;
        const despachador = document.getElementById('despachador').value;
        const medioTransporte = document.getElementById('medioTransporte').value;
        const verificador = document.getElementById('verificador').value;
        if (!fecha || !turno || !subarea || !despachador || !medioTransporte || !verificador || !datos.productoId || !datos.cantidad) {
            mostrarMensajeError('Por favor, complete todos los campos requeridos.');
            return;
        }
        const error = validarDatos(datos);
        if (error) {
            mostrarMensajeError(error);
            return;
        }
        
        // Verificar si estamos en modo edición o agregar
        if (editIndex !== null && btnAgregar.textContent === 'Guardar') {
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
            // Guardar también el id del producto para selección exacta
            if (datos.productoId) row.setAttribute('data-producto-id', datos.productoId);
            row.setAttribute('data-cantidad', datos.cantidad);
            row.setAttribute('data-unidadmedida', datos.unidadMedida || '');
            row.setAttribute('data-comentarios', datos.comentarios);

            // Cambiar el texto y la clase del botón de nuevo a "Agregar"
            btnAgregar.textContent = 'Agregar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
            // Limpiar índice de edición
            editIndex = null;
        } else if (editIndex !== null) {
            // Si hay índice pero no estamos en fase 'Guardar', ignorar (seguridad)
            mostrarMensajeError('Presione "Actualizar" para editar la fila, luego "Guardar" para confirmar.');
            return;
        } else {
            // Modo agregar - agregar nueva fila
            agregarFila(datos);
        }
        
        // Limpiar los controles y actualizar el combo de productos
        limpiarControles();
        limpiarProductoCombo();
    });

    // El botÃ³n Editar ha sido eliminado, ahora se usa el botÃ³n Agregar/Actualizar para ambas funciones

    if (btnQuitar) btnQuitar.addEventListener('click', function() {
        if (editIndex === null) {
            mostrarMensajeError('Seleccione una fila para quitar.');
            return;
        }
        grilla.deleteRow(editIndex);
        editIndex = null;
        actualizarNumeracion();
        limpiarControles();
        limpiarProductoCombo();
    });

    if (btnLimpiar) btnLimpiar.addEventListener('click', function() {
        limpiarControles();
        limpiarProductoCombo(); // Restaurar todas las opciones del combo de productos
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

    window.modificarrecepción = function(id, data) {
        fetch(window.BASE_URL + '/recepcionesinternas/modificar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.assign({Id: id}, data))
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                mostrarMensajeError('recepción modificado correctamente.');
                bloquearControles();
            } else {
                mostrarMensajeError(res.message || 'Error al modificar.');
            }
        })
        .catch((err) => {
            console.log('Error en fetch modificar:', err);
            mostrarMensajeError('Error de conexiÃ³n al modificar.');
        });
    }

    // --- BotÃ³n Imprimir Vale ---
    const btnImprimir = document.getElementById('btnImprimir');
    if (btnImprimir) {
        btnImprimir.addEventListener('click', function(event) {
            // Evitar que otros módulos intercepten el click y muestren otra vista previa
            try { event.stopImmediatePropagation(); } catch(e) {}
            try { event.preventDefault(); } catch(e) {}
            let correlativo = document.getElementById('correlativoVale').textContent;
            // Si ya tiene el prefijo, no lo agregues de nuevo
            if (!correlativo.startsWith('VRI-')) {
                correlativo = 'VRI-' + correlativo.padStart(6, '0');
            }
            const vale = {
                nvale: correlativo,
                area: document.getElementById('area').value,
                subarea: document.getElementById('subarea').options[document.getElementById('subarea').selectedIndex]?.text || '',
                emisor: window.usuarioActual || '',
                fecha: document.getElementById('fecha').value,
                hora: window.horaActual || '',
                turno: document.getElementById('turno').options[document.getElementById('turno').selectedIndex]?.text || '',
                despachador: document.getElementById('despachador').options[document.getElementById('despachador').selectedIndex]?.text || '',
                medioTransporte: document.getElementById('medioTransporte').options[document.getElementById('medioTransporte').selectedIndex]?.text || '',
                verificador: document.getElementById('verificador').options[document.getElementById('verificador').selectedIndex]?.text || ''
            };
            const productos = [];
            const grilla = document.getElementById('grillaRecepcion')?.querySelector('tbody');
            if (grilla) {
                Array.from(grilla.rows).forEach(row => {
                    productos.push({
                        codigo: row.cells[1].textContent, // Columna 2: CÃ³digo (reordenado)
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
            const firmaUrl = window.usuarioUsername ? `${_pp_baseAssets}/img/${window.usuarioUsername}.png` : '';
            // Usar tamaños y alturas de línea iguales a Despachos Internos para la vista previa en pantalla
            let html = `<div style='font-family:Arial,sans-serif; padding:10px; font-size:0.58rem; line-height:1.08;'>
                    <div style='display:flex; align-items:center; justify-content:space-between;'>
                        <img src='${logoUrl}' style='height:26px; max-width:100px;' alt='Logo Lavoro' />
                        <span class='vale-number' style='font-size:0.7rem; font-weight:bold; border:1px solid #333; padding:1px 6px; border-radius:4px;'>${vale.nvale || ''}</span>
                    </div>
                    <h3 style='text-align:center; font-weight:bold; margin-top:6px; font-size:0.72rem; margin-bottom:4px;'>VALE DE RECEPCIÓN INTERNA</h3>
                    <div class='vale-subtitle' style='text-align:center; font-size:0.5rem; margin-bottom:28px;'>ALMACÉN DE JABAS Y PARIHUELAS - HUACHIPA</div>
                        <table style='width:100%; margin-top:28px; font-size:0.52rem; border-spacing:0;'>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>AREA:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0;'>${vale.area}</td>
                                <td class='label-cab' style='width:16mm; min-width:16mm; max-width:16mm; text-align:left; padding:0 0.5mm 0 0;'>FECHA:</td>
                                <td class='data-cab' style='width:32mm; min-width:32mm; max-width:32mm; text-align:left; padding:0 0 0 0;'>${fechaFormateada}</td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>SUB AREA:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0;'>${vale.subarea}</td>
                                <td class='label-cab' style='width:16mm; min-width:16mm; max-width:16mm; text-align:left; padding:0 0.5mm 0 0;'>HORA:</td>
                                <td class='data-cab' style='width:32mm; min-width:32mm; max-width:32mm; text-align:left; padding:0 0 0 0;'>${horaFormateada}</td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>EMISOR:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0;'>${vale.emisor}</td>
                                <td class='label-cab' style='width:16mm; min-width:16mm; max-width:16mm; text-align:left; padding:0 0.5mm 0 0;'>TURNO:</td>
                                <td class='data-cab' style='width:32mm; min-width:32mm; max-width:32mm; text-align:left; padding:0 0 0 0;'>${vale.turno}</td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>DESPACHADOR:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0;'>${vale.despachador}</td>
                                <td></td><td></td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>MEDIO TRANSPORTE:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0;'>${vale.medioTransporte}</td>
                                <td></td><td></td>
                            </tr>
                            <tr>
                                <td class='label-cab' style='width:24mm; min-width:24mm; max-width:24mm; padding:0 0.5mm 0 0;'>VERIFICADOR:</td>
                                <td class='data-cab' style='width:38mm; min-width:38mm; max-width:38mm; padding:0 1mm 0 0;'>${vale.verificador}</td>
                                <td></td><td></td>
                            </tr>
                        </table>
                    <table style='width:100%; margin-top:10px; border-collapse:collapse; font-size:0.65rem; table-layout:fixed;'>
                        <tr class="productos-header" style='font-weight:bold;'>
                            <td style='border:0.5pt solid #333; padding:3px; width:20%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; font-size:0.65rem;'>CODIGO</td>
                            <td style='border:0.5pt solid #333; padding:3px; width:40%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; font-size:0.65rem;'>PRODUCTO</td>
                            <td style='border:0.5pt solid #333; padding:3px; width:12%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; font-size:0.65rem;'>UM</td>
                            <td style='border:0.5pt solid #333; padding:3px; width:10%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; font-size:0.65rem;'>CANTIDAD</td>
                            <td style='border:0.5pt solid #333; padding:3px; width:18%; background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222; text-align:center; font-size:0.65rem;'>COMENTARIOS</td>
                        </tr>`;
                productos.forEach(p => {
                    html += `<tr>
                        <td style='border:0.5pt solid #333; padding:3px; width:20%; word-break:break-word; white-space:normal; overflow-wrap:break-word; text-align:center;'>${p.codigo}</td>
                        <td style='border:0.5pt solid #333; padding:3px; width:40%; word-break:break-word; white-space:normal; overflow-wrap:break-word;'>${p.producto}</td>
                        <td style='border:0.5pt solid #333; padding:3px; width:12%; word-break:break-word; white-space:normal; overflow-wrap:break-word;'>${p.unidadMedida}</td>
                        <td style='border:0.5pt solid #333; padding:3px; width:10%; text-align:right; word-break:break-word; white-space:normal; overflow-wrap:break-word;'>${p.cantidad}</td>
                        <td style='border:0.5pt solid #333; padding:3px; width:18%; word-break:break-word; white-space:normal; overflow-wrap:break-word;'>${p.comentarios}</td>
                    </tr>`;
                });
                html += `</table>
                <div class='firma-block' style='margin-top:12px; border:0.5pt solid #333; border-radius:10px; padding:12px;'>
                    <div style='text-align:center; font-weight:600; font-size:0.65rem; margin-bottom:20px;'>FIRMAS DE AUTORIZACIÓN Y VALIDACIÓN</div>
                    <table style='width:100%; margin-top:20px; text-align:center; font-size:0.65rem;'>
                        <tr>
                            <td style='width:33%; vertical-align:bottom; box-sizing:border-box; padding-bottom:6px;'>
                                <div style='display:flex; flex-direction:column; align-items:center;'>
                                    <div style='border-top:1px solid #333; width:80%; margin:15px 0 8px 0;'></div>
                                    <div style='margin-top:8px; font-size:0.65rem;'>DESPACHADOR</div>
                                </div>
                            </td>
                            <td style='width:33%; vertical-align:bottom; box-sizing:border-box; padding-bottom:6px;'>
                                <div style='display:flex; flex-direction:column; align-items:center;'>
                                    ${firmaUrl ? `<img src='${firmaUrl}' style='height:117px; margin-bottom:2px; margin-top:0; display:block; max-width:100%;' alt='Firma Recepcionista' />` : '<div style="height:117px; margin-bottom:2px;"></div>'}
                                    <div style='border-top:1px solid #333; width:80%; margin:15px 0 8px 0;'></div>
                                    <div style='margin-top:8px; font-size:0.65rem;'>RECEPCIONISTA</div>
                                </div>
                            </td>
                            <td style='width:33%; vertical-align:bottom; box-sizing:border-box; padding-bottom:6px;'>
                                <div style='display:flex; flex-direction:column; align-items:center;'>
                                    <div style='border-top:1px solid #333; width:80%; margin:15px 0 8px 0;'></div>
                                    <div style='margin-top:8px; font-size:0.65rem;'>VERIFICADOR</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>`;
            document.getElementById('valePreviewContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('modalValePreview'));
            modal.show();
        });
    }

    // Replicar impresiÃ³n profesional de reportes: sin ventanas, con doble copia y estilos
    const btnValePrint = document.getElementById('btnValePrint');
    if (btnValePrint) {
        btnValePrint.addEventListener('click', function() {
            // Obtener el contenido y corregir URLs de imÃ¡genes
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
            // Crear contenedor temporal para impresiÃ³n directa
            const printContainer = document.createElement('div');
            printContainer.id = 'print-only-container';
            printContainer.style.display = 'none';
            // Copiar estilos de impresión doble exactamente de Despachos Internos para consistencia
            printContainer.innerHTML = `
                <style>
                    @media screen {
                        #print-only-container .vale-copy table tr.productos-header,
                        #print-only-container .vale-copy table tr.productos-header > td,
                        #print-only-container .vale-copy table tr.productos-header > th {
                            background: #f2f2f2 !important;
                            background-color: #f2f2f2 !important;
                            color: #222 !important;
                        }
                    }
                    @media screen {
                        #print-only-container .vale-copy table tr.productos-header,
                        #print-only-container .vale-copy table tr.productos-header > td {
                            background: #f2f2f2 !important;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                    }
                    @media screen {
                        .vale-copy table .productos-header > td {
                            background: #f2f2f2 !important;
                        }
                        .vale-copy table tr.productos-header {
                            background: #f2f2f2 !important;
                        }
                    }
                    .vale-copy table .productos-header > td {
                        background: #f2f2f2 !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                        white-space: nowrap !important;
                    }
                    .vale-copy table tr.productos-header {
                        background: #f2f2f2 !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    @page {
                        size: A4 landscape;
                        margin: 5mm;
                    }
                    @media print {
                        body * { visibility: hidden; }
                        #print-only-container, #print-only-container * { visibility: visible; }
                        #print-only-container {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 287mm;
                            height: 200mm;
                            display: flex !important;
                            flex-direction: row;
                            justify-content: space-between;
                            align-items: stretch;
                            padding: 0;
                            margin: 0;
                            box-sizing: border-box;
                            page-break-after: avoid;
                            overflow: hidden;
                        }
                        /* Poner en negrita las etiquetas de cabecera (AREA..TURNO) en la impresión doble */
                        #print-only-container .vale-frame td.label-cab {
                            font-weight: 700 !important;
                        }
                        #print-only-container::after {
                            content: '';
                            position: absolute;
                            left: 50%;
                            top: 0;
                            height: 100%;
                            width: 0;
                            border-left: 2px dashed #333;
                            z-index: 1000;
                            transform: translateX(-1px);
                        }
                        .vale-copy {
                            width: 141.5mm;
                            height: 200mm;
                            padding: 4px;
                            box-sizing: border-box;
                            background: transparent;
                            font-family: Arial, sans-serif;
                            font-size: 8px;
                            overflow: visible;
                            page-break-inside: avoid;
                            flex-shrink: 0;
                            position: relative;
                            display: flex;
                            align-items: stretch;
                        }
                        .vale-frame {
                            border: 1px solid #222;
                            border-radius: 10px;
                            padding: 6px;
                            box-sizing: border-box;
                            background: #fff;
                            page-break-inside: avoid;
                            height: 100%;
                            width: 100%;
                            display: flex;
                            flex-direction: column;
                            justify-content: space-between;
                        }
                        .vale-frame > * {
                            flex: 1 1 auto;
                        }
                        .vale-frame .firma-block {
                            margin-top: auto;
                        }
                        .vale-copy:first-child {
                            margin-right: 2mm;
                        }
                        .vale-copy:last-child {
                            /* Sin borde izquierdo punteado */
                        }
                        .vale-frame table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 1mm 0;
                            font-size: 7px;
                        }
                        /* QUITAR GRILLA COMPLETAMENTE DE DATOS INFORMATIVOS */
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) {
                            border: none !important;
                            background: transparent !important;
                        }
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) td,
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) th {
                            border: none !important;
                            padding: 0.2mm 0.2mm 0.2mm 0.8mm !important;
                            background: transparent !important;
                            font-size: 5px !important;
                            line-height: 1.1 !important;
                            text-align: left;
                        }
                        /* Reducir aún más el espacio entre columnas de la cabecera */
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) tr > td:first-child,
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) tr > th:first-child {
                            padding-right: 0.5mm !important;
                            min-width: 10mm !important;
                            width: 10mm !important;
                        }
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) tr > td:nth-child(2) {
                            padding-left: 0.2mm !important;
                            min-width: 8mm !important;
                        }
                        /* Reducir aún más el espacio entre label y dato */
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) td.label-cab,
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) th.label-cab {
                            padding-right: 0.5mm !important;
                            min-width: 16mm !important;
                            max-width: 24mm !important;
                            font-size: 9px !important;
                        }
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) tr > td:nth-child(3).label-cab,
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) tr > th:nth-child(3).label-cab {
                            min-width: 16mm !important;
                            width: 16mm !important;
                        }
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) td.data-cab {
                            padding-left: 0.2mm !important;
                            min-width: 10mm !important;
                            font-size: 9px !important;
                        }
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) tr > td:nth-child(4).data-cab {
                            min-width: 32mm !important;
                            width: 32mm !important;
                        }
                        /* MANTENER GRILLA SOLO EN TABLA DE PRODUCTOS */
                        .vale-frame table[style*="border-collapse"] td,
                        .vale-frame table[style*="border-collapse"] th,
                        .vale-frame .table td,
                        .vale-frame .table th,
                        .vale-frame table[class*="productos"] td,
                        .vale-frame table[class*="productos"] th {
                            /* Bordes estándar y color negro para impresión */
                            border: 1px solid #000 !important;
                            padding: 1mm !important;
                            font-size: 6px;
                            vertical-align: top;
                            line-height: 1.2;
                        }
                        .vale-frame h3 {
                            text-align: center;
                            font-size: 16px !important;
                            margin: 6mm 0 2mm 0 !important;
                            font-weight: bold;
                        }
                        /* Subtítulo del vale (solo en impresión) */
                        #print-only-container .vale-frame .vale-subtitle {
                            font-size: 12px !important;
                            margin-bottom: 3mm !important;
                        }
                        /* Elevar un poco todo el contenido del vale para ocupar espacio superior */
                        .vale-frame .vale-header-table {
                            margin-top: 0mm !important;
                        }
                        .vale-frame img {
                            /* aumentar tamaño del logo en la impresión para que coincida con externos */
                            max-height: 42px;
                            width: auto;
                        }
                        /* aumentar ligeramente número de vale solo en impresión */
                        #print-only-container .vale-frame .vale-number {
                            font-size: 1.1rem !important;
                            font-weight: bold !important;
                            border: 1.2px solid #333 !important;
                            padding: 2px 8px !important;
                            border-radius: 6px !important;
                        }
                        /* Centrar columnas específicas (impresión) */
                        #print-only-container .vale-frame td.campo-codigo,
                        #print-only-container .vale-frame td.campo-unidad,
                        #print-only-container .vale-frame td.campo-cantidad {
                            text-align: center !important;
                            vertical-align: middle !important;
                        }
                        /* subrayado para los datos de cabecera en la impresión (td.data-cab se añadió al HTML) */
                        /* Mantener espacio bajo el valor; no forzar border-bottom: none para permitir subrayados inline */
                        #print-only-container .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) td.data-cab {
                            /* quitar líneas bajo los datos de cabecera SOLO en impresión */
                            padding-bottom: 0 !important;
                            border-bottom: none !important;
                        }
                        /* ocultar el bloque .data-line en la copia impresa */
                        #print-only-container .vale-frame td.data-cab .data-line {
                            display:none !important;
                        }
                        /* Reducir ligeramente el tamaño de la cabecera de la grilla y de los datos de cabecera
                           (despachador / recepcionista / verificador) para igualar Despachos Internos */
                        #print-only-container .vale-frame table tr.productos-header > td {
                            font-size: 5px !important;
                            padding: 0.8mm 3px !important;
                        }
                        #print-only-container .vale-frame td.data-cab {
                            font-size: 8px !important;
                        }
                        @page { size: A4 landscape; margin: 5mm; }
                        @media print { html, body { height: 200mm !important; overflow: hidden !important; } }
                    }
                    @media screen {
                        #print-only-container {
                            display: block !important;
                        }
                        /* mostrar la línea también en la vista previa (pantalla) */
                        .vale-frame td.data-cab .data-line {
                            display: block;
                            width: 80%;
                            height: 1px;
                            background: #333;
                            margin-top: 4px;
                        }
                        /* Ajustes visuales en la vista previa: cabecera de la grilla y datos de cabecera ligeramente más pequeños */
                        #print-only-container .vale-copy table tr.productos-header > td {
                            font-size: 5px !important;
                        }
                        #print-only-container .vale-frame td.data-cab {
                            font-size: 0.70rem !important;
                        }
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) td,
                        .vale-frame table:not([style*="border-collapse"]):not(.table):not([class*="productos"]) th {
                            border: none !important;
                            padding: 0.2mm 0.2mm 0.2mm 0.8mm !important;
                            background: transparent !important;
                            font-size: 5px !important;
                            line-height: 1.1 !important;
                            text-align: left;
                        }
                    }
                </style>
                <div class="vale-copy"><div class="vale-frame">${printContents}</div></div>
                <div class="vale-copy"><div class="vale-frame">${printContents}</div></div>
            `;
            document.body.appendChild(printContainer);
            // Cerrar el modal de vista previa antes de imprimir
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalValePreview'));
            if (modal) modal.hide();
            setTimeout(() => {
                window.print();
                setTimeout(() => {
                    if (printContainer && printContainer.parentNode) {
                        printContainer.parentNode.removeChild(printContainer);
                    }
                }, 1000);
            }, 500);
        });
        // Cerrar modal si el usuario cancela la impresiÃ³n
        window.addEventListener('afterprint', function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalValePreview'));
            if (modal) modal.hide();
        });
    }

    // --- Solicitar correlativo al cargar y bloquear controles ---
    fetch(window.BASE_URL + '/recepcionesinternas/siguienteVale')
        .then(r => r.json())
        .then(res => {
            if (res.success && res.correlativo) {
                let num = res.correlativo.toString().replace(/^VRI-/, '').padStart(6, '0');
                if (correlativoVale) correlativoVale.textContent = `VRI-${num}`;
            }
        });

    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            habilitarControles();
            
            // Mantener el selector de turno deshabilitado incluso con "Nuevo"
            if (turnoSelect) {
                turnoSelect.setAttribute('disabled', 'disabled');
                if (window.choicesInstances['turno']) {
                    window.choicesInstances['turno'].disable();
                }
                try { applyChoicesVisualDisabled(turnoSelect, true); } catch(e) {}
            }
            
            limpiarControles();
            // Configurar restricciÃ³n de fecha para nuevo registro (solo permite hasta hoy)
            configurarFechaInput();
            // Establecer la fecha actual (usando mÃ©todos de fecha local)
            var d = new Date();
            document.getElementById('fecha').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            
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
        btnGuardar.addEventListener('click', function() {
            // Recolectar datos del formulario
            const correlativoValeEl = document.getElementById('correlativoVale');
            const correlativoVale = correlativoValeEl.textContent;
            const fecha = document.getElementById('fecha').value;
            const turno = document.getElementById('turno').value;
            const subareaSelect = document.getElementById('subarea');
            const areaId = subareaSelect.options[subareaSelect.selectedIndex]?.getAttribute('data-area') || '';
            const subarea = subareaSelect.value;
            const despachador = document.getElementById('despachador').value;
            const medioTransporte = document.getElementById('medioTransporte').value;
            const verificador = document.getElementById('verificador').value;
            // Productos de la grilla
            const productos = [];
            const grilla = document.getElementById('grillaRecepcion')?.querySelector('tbody');
            if (grilla) {
                Array.from(grilla.rows).forEach(row => {
                    productos.push({
                        codigo: row.getAttribute('data-codigo') || row.cells[1].textContent, // Columna 2: CÃ³digo (reordenado)
                        producto: row.getAttribute('data-producto') || row.cells[2].textContent, // Columna 3: Producto (reordenado)
                        cantidad: row.getAttribute('data-cantidad') || row.cells[4].textContent, // Columna 5: Cantidad
                        unidadMedida: row.getAttribute('data-unidadmedida') || row.cells[3].textContent, // Columna 4: Unid Med (reordenado)
                        comentarios: row.getAttribute('data-comentarios') || row.cells[5].textContent // Columna 6: Comentarios
                    });
                });
            }
            // ValidaciÃ³n bÃ¡sica
            if (!correlativoVale || !fecha || !turno || !areaId || !subarea || !despachador || !medioTransporte || !verificador || productos.length === 0) {
                mostrarMensajeError('Por favor, complete todos los campos y agregue al menos un producto.');
                return;
            }
            // Detectar si es modificaciÃ³n
            let recepciónId = correlativoValeEl.getAttribute('data-id');
            // Si el valor es null, undefined o vacÃ­o, forzar a null
            if (!recepciónId || recepciónId === 'null' || recepciónId === 'undefined') {
                recepciónId = null;
            }
            const payload = {
                correlativoVale,
                fecha,
                turno,
                area: areaId,
                subarea,
                despachador,
                medioTransporte,
                verificador,
                productos
            };
            let url = window.BASE_URL + '/recepcionesinternas/guardar';
            if (recepciónId) {
                payload.Id = recepciónId;
                url = window.BASE_URL + '/recepcionesinternas/modificar';
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
                    mostrarMensajeError(recepciónId ? 'recepción modificado correctamente.' : 'recepción guardado correctamente.');
                    bloquearControles();
                    if (btnNuevo) btnNuevo.removeAttribute('disabled');
                    if (btnModificar) btnModificar.removeAttribute('disabled');
                    // Actualizar el data-id siempre que el backend devuelva el id
                    if (res.id) {
                        correlativoValeEl.setAttribute('data-id', res.id);
                    }
                } else {
                    mostrarMensajeError(res.message || 'Error al guardar.');
                }
            })
            .catch((err) => {
                console.log('Error en fetch guardar/modificar:', err);
                mostrarMensajeError('Error de conexiÃ³n al guardar/modificar.');
            });
        });
    }

    if (btnModificar) {
        btnModificar.addEventListener('click', function() {
            // Habilitar controles excepto turno, Ã¡rea y cÃ³digo
            [
                'fecha', 'subarea', 'despachador', 'medioTransporte', 'verificador',
                'producto', 'cantidad', 'comentarios'
            ].forEach(id => {
                const el = document.getElementById(id);
                if (el && id !== 'area' && id !== 'codigo' && id !== 'turno') {
                    el.removeAttribute('disabled');
                }
            });
            document.getElementById('area').setAttribute('disabled', 'disabled');
            document.getElementById('codigo').setAttribute('disabled', 'disabled');
            document.getElementById('turno').setAttribute('disabled', 'disabled');
            document.getElementById('chkTurno').removeAttribute('disabled');
            if (window.choicesInstances) {
                Object.values(window.choicesInstances).forEach(inst => inst.enable());
                if (window.choicesInstances['area']) window.choicesInstances['area'].disable();
                if (window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
            }
            if (btnGuardar) btnGuardar.removeAttribute('disabled');
            if (btnAgregar) btnAgregar.removeAttribute('disabled');
            if (btnLimpiar) btnLimpiar.removeAttribute('disabled');
            if (btnAnular) btnAnular.setAttribute('disabled', 'disabled');
            if (btnQuitar) btnQuitar.removeAttribute('disabled');
        });
    }
    
    // ============================================================
    // SISTEMA GLOBAL DE WRAPPING AUTOMÁTICO PARA SELECTS LARGOS
    // ============================================================
    
    /**
     * Función global SEGURA para aplicar wrapping a selects largos
     * SIN MutationObserver ni setTimeout recursivos
     */
    window.updateAllChoicesWrap = function() {
        // Buscar todos los contenedores de Choices.js en el documento
        const choicesContainers = document.querySelectorAll('.choices');
        
        console.log('[Wrapping] Ejecutando updateAllChoicesWrap() - encontrados ' + choicesContainers.length + ' contenedores');
        
        let aplicados = 0;
        choicesContainers.forEach(container => {
            const itemElement = container.querySelector('.choices__list--single .choices__item');
            
            if (!itemElement) {
                console.log('[Wrapping] Contenedor sin item seleccionado, saltando');
                return;
            }
            
            const text = itemElement.textContent || '';
            const charCount = text.length;
            
            console.log('[Wrapping] Analizando select: "' + text.substring(0, 50) + '..." (' + charCount + ' chars)');
            
            // Si el texto es largo, aplicar wrapping
            if (charCount > 10) {
                container.classList.add('choices-wrap-auto');
                aplicados++;
                console.log('[Wrapping] ✓ Aplicando wrapping a este select');
                
                // Permitir expansión natural del contenedor
                container.style.height = 'auto';
                container.style.minHeight = '38px';
                container.style.maxHeight = 'none';
                
                // Configurar contenedor interno para expansión
                const innerElement = container.querySelector('.choices__inner');
                if (innerElement) {
                    innerElement.style.height = 'auto';
                    innerElement.style.minHeight = '38px';
                    innerElement.style.maxHeight = 'none';
                    innerElement.style.overflow = 'visible';
                    console.log('[Wrapping] ✓ Expansión habilitada - altura automática');
                }
                
                // Ajustar lista
                const listElement = container.querySelector('.choices__list--single');
                if (listElement) {
                    listElement.style.height = 'auto';
                    listElement.style.maxHeight = 'none';
                    listElement.style.overflow = 'visible';
                }
            } else {
                // Texto corto, remover wrapping
                container.classList.remove('choices-wrap-auto');
                console.log('[Wrapping] Texto corto, sin wrapping necesario');
            }
        });
        
        console.log('[Wrapping] Resumen: wrapping aplicado a ' + aplicados + ' select(s) de ' + choicesContainers.length + ' totales');
    };

    /**
     * Inyectar estilos CSS globales para wrapping
     */
    function injectProductoWrapCSS() {
        if (document.getElementById('custom-choices-wrap-styles')) return;

        const style = document.createElement('style');
        style.id = 'custom-choices-wrap-styles';
        style.textContent = `
            /* Estilos para wrapping sin afectar layout horizontal */
            
            /* Contenedor principal - permitir expansión vertical */
            .choices.choices-wrap-auto,
            div.choices.choices-wrap-auto {
                height: auto !important;
                min-height: 38px !important;
                max-height: none !important;
                position: relative !important;
            }
            
            /* Contenedor interno - expansión normal */
            .choices-wrap-auto .choices__inner,
            .choices.choices-wrap-auto .choices__inner,
            div.choices.choices-wrap-auto div.choices__inner {
                height: auto !important;
                min-height: 38px !important;
                max-height: none !important;
                overflow: visible !important;
                display: flex !important;
                flex-wrap: wrap !important;
                align-items: flex-start !important;
                padding: 0.375rem 2.5rem 0.375rem 0.75rem !important;
            }
            
            /* Lista single */
            .choices-wrap-auto .choices__list--single,
            .choices.choices-wrap-auto .choices__list--single {
                overflow: visible !important;
                display: flex !important;
                flex-wrap: wrap !important;
                width: 100% !important;
                height: auto !important;
                max-height: none !important;
            }
            
            /* Item seleccionado - texto con wrap */
            .choices-wrap-auto .choices__list--single .choices__item,
            .choices.choices-wrap-auto .choices__list--single .choices__item {
                white-space: normal !important;
                word-break: break-word !important;
                overflow-wrap: anywhere !important;
                line-height: 1.3 !important;
                width: 100% !important;
                height: auto !important;
            }
        `;
        document.head.appendChild(style);
        console.log('[RecepcionesInternas] CSS global de wrapping inyectado');
    }

    // Inyectar estilos al cargar
    injectProductoWrapCSS();

    // Aplicar wrapping inicial después de que se cargue la página
    // SOLO UNA VEZ, sin timers recursivos
    setTimeout(() => {
        window.updateAllChoicesWrap();
    }, 800);

    // Listener de resize con debounce para evitar ejecuciones excesivas
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            window.updateAllChoicesWrap();
        }, 300);
    });

    console.log('[RecepcionesInternas] Sistema de wrapping automático inicializado (versión segura)');

    // ============================================================
    // FIN DEL SISTEMA DE WRAPPING
    // ============================================================
});



