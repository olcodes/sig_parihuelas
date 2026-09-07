// recepcionesinternas_edicion.js - Modo edición de vales para Recepciones Internas
// VERSION: 2026-02-22-FIX-BUCLE-v3
console.log('%c[RecepcionesInternas-Edición] VERSIÓN 2026-02-22-FIX-BUCLE-v3 CARGADA', 'color: blue; font-weight: bold');
// Estado inicial para la edición
let editIndex = null;
let updatePhase = null; // null | 'selected' | 'editing'
let _valeUnicoId = null; // ID del vale si la última búsqueda arrojó exactamente 1 resultado
// Flag para prevenir doble clic en Guardar. Declarado en el ámbito raíz del IIFE para que
// el listener de setupBotones y guardarRecepcion() compartan la MISMA variable (si se declara
// con var dentro de setupBotones, las asignaciones de guardarRecepcion crean una global implícita
// distinta y el flag nunca se resetea, ignorando los guardados posteriores).
let _guardandoInternasEditEnProceso = false;

// Depuración: exponer acceso limitado a estado interno sin romper encapsulamiento
try {
    window._dbg = window._dbg || {};
    Object.defineProperty(window._dbg, 'getState', {
        configurable: true,
        enumerable: false,
        value: function() {
            try { return { datosGrilla: JSON.parse(JSON.stringify(window.datosGrilla || [])), editIndex: editIndex, updatePhase: updatePhase, valeIdActual: window.valeIdActual || null }; } catch(e) { return { datosGrilla: window.datosGrilla || [], editIndex: editIndex, updatePhase: updatePhase, valeIdActual: window.valeIdActual || null }; }
        }
    });
} catch(e) { /* ignore */ }
    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('[Edición] Inicializando');
        // Nota: ya no reemplazamos `btnAgregar` ni `btnGuardar` aquí para no eliminar
        // los listeners principales definidos en `recepcionesinternas.js`. Esto permite
        // que el flujo central (Actualizar -> poblar controles -> Guardar) funcione.
        
        // Configurar Choices.js para los selects
        initChoices();
        
        // Configurar turno automático
        setupTurnoAutomatico();
        
        // Configurar eventos para subárea/área
        setupSubareaArea();
        
        // Configurar eventos de productos
        setupProductos();
        
        // Configurar botones principales
        setupBotones();
        
        // Configurar modo edición específico
        setupModoEdicion();

        // Añadir listener en fase de captura sobre la grilla para marcar el bloqueo
        // ANTES de que otros handlers (del script principal) se ejecuten al click.
        try {
            const grillaTbody = document.querySelector('#grillaRecepcion tbody');
            if (grillaTbody) {
                grillaTbody.addEventListener('click', function(e) {
                    // Si el click fue sobre una fila, activar el bloqueo para que
                    // la carga automática de la fila en los controles sea evitada
                    let el = e.target;
                    while (el && el !== grillaTbody && el.nodeName !== 'TR') el = el.parentElement;
                    if (el && el.nodeName === 'TR') {
                        try { window.__recep_block_grid_updates = true; } catch(ex) {}
                // SILENCIADO: console.log('[Edición] capture listener set __recep_block_grid_updates = true for click on row');
                    }
                }, true); // useCapture = true
            }
        } catch(e) { console.warn('[Edición] No se pudo añadir capture listener a la grilla', e); }
        
        // NO bloquear controles en modo edición - los controles empiezan habilitados
        // Los controles solo se bloquean si se carga un vale y se quiere prevenir edición
        
        console.log('[Edición] Inicialización completada');

        // Asegurar sincronía visual al iniciar (usar función global si está disponible)
        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
    }

    // Mostrar mensaje con diseño (compatible con Nuevo Registro)
    function mostrarMensajeError(msg, tipo = 'warning') {
        var div = document.getElementById('mensajeError');
        if (!div) return;
        
        // Adaptar colores según el tipo
        if (tipo === 'error') {
            div.style.color = '#991b1b';
            div.style.background = '#fee';
            div.style.borderColor = '#fca5a5';
        } else if (tipo === 'info') {
            div.style.color = '#1e40af';
            div.style.background = '#dbeafe';
            div.style.borderColor = '#93c5fd';
        } else {
            // warning (default)
            div.style.color = '#7c4700';
            div.style.background = '#fffbe6';
            div.style.borderColor = '#ffe082';
        }
        
        div.textContent = msg;
        div.classList.remove('d-none');
        setTimeout(function() { div.classList.add('d-none'); }, 5000);
    }

    // Inicializar Choices.js
    function initChoices() {
        const selectores = ['turno', 'subarea', 'emisor', 'despachador', 'medioTransporte', 'verificador', 'producto'];
        
        // Reusar instancias globales si existen (creadas por recepcionesinternas.js)
        if (window.choicesInstances) {
            console.log('[Edición] Usando instancias de Choices.js globales existentes');
            // NO crear nuevas instancias, usar las globales directamente
            // PERO sí configurar el wrapping del producto (saltar al setup más abajo)
        } else {
            // Fallback: crear instancias si no existen globalmente
            if (!window.choicesInstances) {
                window.choicesInstances = {};
            }
            
            selectores.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento && !window.choicesInstances[id]) {
                    try {
                        window.choicesInstances[id] = new Choices(elemento, {
                            searchEnabled: true,
                            placeholder: true,
                            placeholderValue: 'Seleccione...',
                            noResultsText: 'No se encontraron resultados',
                            itemSelectText: '',
                            searchPlaceholderValue: 'Buscar...',
                            shouldSort: false
                        });
                    } catch(e) {
                        console.warn('[Edición] Error al inicializar Choices para', id, e);
                    }
                }
            });
        }

        // Función SEGURA de wrapping para el select de productos en edición
        // SIN MutationObserver, SIN setTimeout recursivos
        function updateProductoChoiceWrap() {
            // Buscar el select de producto (puede ser #producto o #productoEdit)
            const productoSelect = document.querySelector('#producto') || document.querySelector('#productoEdit');
            if (!productoSelect) return;
            
            const productoChoicesContainer = productoSelect.closest('.choices');
            
            if (!productoChoicesContainer) return;
            
            const itemElement = productoChoicesContainer.querySelector('.choices__list--single .choices__item');
            if (!itemElement) return;
            
            const text = itemElement.textContent || '';
            const charCount = text.length;
            
            // Aplicar o remover wrapping según la longitud del texto
            if (charCount > 10) {
                productoChoicesContainer.classList.add('choices-wrap-auto');
                console.log('[INFO] Wrapping aplicado al producto en edición (' + charCount + ' chars)');
                
                // Permitir expansión natural
                productoChoicesContainer.style.height = 'auto';
                productoChoicesContainer.style.minHeight = '38px';
                productoChoicesContainer.style.maxHeight = 'none';
                
                // Configurar contenedor interno
                const innerElement = productoChoicesContainer.querySelector('.choices__inner');
                if (innerElement) {
                    innerElement.style.height = 'auto';
                    innerElement.style.minHeight = '38px';
                    innerElement.style.maxHeight = 'none';
                    innerElement.style.overflow = 'visible';
                }
                
                // Ajustar lista
                const listElement = productoChoicesContainer.querySelector('.choices__list--single');
                if (listElement) {
                    listElement.style.height = 'auto';
                    listElement.style.maxHeight = 'none';
                    listElement.style.overflow = 'visible';
                }
            } else {
                productoChoicesContainer.classList.remove('choices-wrap-auto');
            }
        }

        // Exponer la función
        window.updateProductoChoiceWrap = updateProductoChoiceWrap;

        // Aplicar wrapping cuando cambie el producto (solo una vez por cambio)
        const productoEditSelect = document.getElementById('producto') || document.getElementById('productoEdit');
        if (productoEditSelect) {
            productoEditSelect.addEventListener('change', () => {
                // Ejecutar DESPUÉS de que Choices.js actualice el DOM
                setTimeout(() => {
                    updateProductoChoiceWrap();
                }, 100);
            });
        }
        
        // También llamar a updateAllChoicesWrap si existe (para otros selects en la página)
        setTimeout(() => {
            if (typeof window.updateAllChoicesWrap === 'function') {
                window.updateAllChoicesWrap();
            }
            updateProductoChoiceWrap();
        }, 800);
    }

    // Configurar turno automático
    function setupTurnoAutomatico() {
        const chkTurno = document.getElementById('chkTurno');
        const turnoSelect = document.getElementById('turno');
        
        if (!chkTurno || !turnoSelect) return;
        
        // Establecer turno automático al cargar
        if (!chkTurno.checked) {
            actualizarTurnoAutomatico();
        }
        
        // Evento para checkbox manual: al marcar habilitar select turno, al desmarcar restaurar automático
        chkTurno.addEventListener('change', function() {
            try {
                if (this.checked) {
                    // Modo manual: habilitar select turno y su instancia Choices
                    try { turnoSelect.removeAttribute('disabled'); } catch(e) {}
                    try { if (window.choicesInstances && window.choicesInstances['turno'] && typeof window.choicesInstances['turno'].enable === 'function') window.choicesInstances['turno'].enable(); } catch(e) {}
                } else {
                    // Volver a automático: aplicar turno automático y deshabilitar select
                    try { actualizarTurnoAutomatico(); } catch(e) {}
                    try { turnoSelect.setAttribute('disabled','disabled'); } catch(e) {}
                    try { if (window.choicesInstances && window.choicesInstances['turno'] && typeof window.choicesInstances['turno'].disable === 'function') window.choicesInstances['turno'].disable(); } catch(e) {}
                }
            } catch (e) { console.warn('[Edición] chkTurno change handler error', e); }
        });
        
        // Sincronizar aspecto visual del control turno cuando está deshabilitado
        syncTurnoChoicesDisabled();
        
        // Observar cambios en el atributo disabled del select#turno
        try {
            const mo = new MutationObserver(mutations => {
                for (const m of mutations) {
                    if (m.type === 'attributes' && m.attributeName === 'disabled') syncTurnoChoicesDisabled();
                }
            });
            mo.observe(turnoSelect, { attributes: true, attributeFilter: ['disabled'] });
        } catch (e) { /* ignore */ }
        
        // Envolver métodos disable/enable de la instancia Choices para sincronizar visual
        try {
            const ci = window.choicesInstances && window.choicesInstances['turno'];
            if (ci && typeof ci.disable === 'function') {
                const origDisable = ci.disable.bind(ci);
                ci.disable = function() {
                    const res = origDisable();
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
    }
    
    // Función para aplicar estilos gris al contenedor Choices cuando turno está deshabilitado
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

    function actualizarTurnoAutomatico() {
        const horaActual = window.horaActual || new Date().toTimeString().slice(0,8);
        const [h] = horaActual.split(':').map(Number);
        const turnos = window.turnosData || [];
        
        let turnoId = '';
        for (const t of turnos) {
            const [hi, mi] = (t.HoraInicio || '00:00:00').split(':').map(Number);
            const [hf, mf] = (t.HoraFin || '23:59:59').split(':').map(Number);
            const horaInicioMinutos = hi * 60 + mi;
            const horaFinMinutos = hf * 60 + mf;
            const horaActualMinutos = h * 60;
            
            if (horaInicioMinutos <= horaFinMinutos) {
                if (horaActualMinutos >= horaInicioMinutos && horaActualMinutos <= horaFinMinutos) {
                    turnoId = t.Id;
                    break;
                }
            } else {
                if (horaActualMinutos >= horaInicioMinutos || horaActualMinutos <= horaFinMinutos) {
                    turnoId = t.Id;
                    break;
                }
            }
        }
        
        if (turnoId && window.choicesInstances && window.choicesInstances['turno']) {
            window.choicesInstances['turno'].setChoiceByValue(turnoId.toString());
        }
        // Aplicar fecha efectiva para turno nocturno
        try{ if(typeof window.aplicarFechaEfectivaTurno === 'function') window.aplicarFechaEfectivaTurno(false); }catch(e){}
    }

    // Configurar subárea/área
    function setupSubareaArea() {
        const subareaSelect = document.getElementById('subarea');
        const areaInput = document.getElementById('area');
        
        if (!subareaSelect || !areaInput) return;
        
        subareaSelect.addEventListener('change', function() {
            const opcionSeleccionada = this.options[this.selectedIndex];
            if (opcionSeleccionada && opcionSeleccionada.value) {
                const areaNombre = opcionSeleccionada.getAttribute('data-area-nombre');
                areaInput.value = areaNombre || '';
            } else {
                areaInput.value = '';
            }
        });
    }

    // Configurar eventos de productos
    function setupProductos() {
        const productoSelect = document.getElementById('producto');
        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        
        if (productoSelect) {
            // Listener en fase de captura para interceptar el evento antes que
            // otros handlers y evitar efectos colaterales cuando estamos en
            // modo selección (fila seleccionada, esperando "Actualizar").
            productoSelect.addEventListener('change', function(e) {
                if (editIndex !== null && updatePhase === 'selected') {
                    try { e.stopImmediatePropagation(); } catch (er) {}
                    try { e.stopPropagation(); } catch (er) {}
                    try { e.preventDefault(); } catch (er) {}
                    // Actualizar sólo campo código y salir
                    const opcionSeleccionadaCap = this.options[this.selectedIndex];
                    if (opcionSeleccionadaCap && opcionSeleccionadaCap.value) {
                        const codigoCap = opcionSeleccionadaCap.getAttribute('data-codigo');
                        if (codigoInput) codigoInput.value = (codigoCap === '0' || codigoCap === 0) ? '0' : (codigoCap != null ? String(codigoCap) : '');
                    } else {
                        if (codigoInput) codigoInput.value = '';
                    }
                    return;
                }
            }, true); // useCapture = true

            productoSelect.addEventListener('change', function(e) {
                // Si hay una fila seleccionada pero todavía en fase 'selected',
                // evitamos que otros listeners (posibles handlers globales)
                // reciban este evento y que se propague una actualización automática
                if (editIndex !== null && updatePhase === 'selected') {
                    try { e.stopImmediatePropagation(); } catch (er) {}
                    try { e.stopPropagation(); } catch (er) {}
                    // Sólo actualizar los controles de edición (ej. código), sin
                    // tocar la grilla ni cambiar el estado de edición.
                    const opcionSeleccionada = this.options[this.selectedIndex];
                    if (opcionSeleccionada && opcionSeleccionada.value) {
                        const codigo = opcionSeleccionada.getAttribute('data-codigo');
                        if (codigoInput) codigoInput.value = (codigo === '0' || codigo === 0) ? '0' : (codigo != null ? String(codigo) : '');
                    } else {
                        if (codigoInput) codigoInput.value = '';
                    }
                    return;
                }

                // Comportamiento por defecto cuando NO estamos en selección de fila
                const opcionSeleccionada = this.options[this.selectedIndex];
                if (opcionSeleccionada && opcionSeleccionada.value) {
                    const codigo = opcionSeleccionada.getAttribute('data-codigo');
                    if (codigoInput) codigoInput.value = (codigo === '0' || codigo === 0) ? '0' : (codigo != null ? String(codigo) : '');
                } else {
                    if (codigoInput) codigoInput.value = '';
                }
            });
        }
        
        // Botón agregar producto: soporta flujo Seleccionar -> Actualizar -> Guardar
        const btnAgregar = document.getElementById('btnAgregar');
        if (btnAgregar) {
            try {
                // Clonar el botón para eliminar listeners previos que puedan venir de otros módulos
                const btnAgregarNuevo = btnAgregar.cloneNode(true);
                btnAgregar.parentNode.replaceChild(btnAgregarNuevo, btnAgregar);
                console.log('[Edición] btnAgregar clonado para remover listeners previos');

                // Añadir nuestro handler en fase de captura para interceptar antes que otros
                btnAgregarNuevo.addEventListener('click', function(e) {
                    try { e.stopImmediatePropagation(); } catch(ex) {}
                    try { e.preventDefault(); } catch(ex) {}

                    // Si hay una fila seleccionada pero no cargada en controles, al pulsar "Actualizar"
                    // debemos primero poblar los controles y pasar a fase 'editing'.
                    if (editIndex !== null && updatePhase === 'selected') {
                        cargarFilaEnControlesEdit(editIndex);
                        return;
                    }

                    // Si ya estamos en modo edición sobre una fila (controles poblados), guardar cambios
                    if (editIndex !== null && updatePhase === 'editing') {
                        guardarEdicionFila(editIndex);
                        return;
                    }

                    // Caso por defecto: agregar nuevo producto
                    agregarProducto();
                }, true);
            } catch (e) {
                // Fallback: si clonar falla, registramos handler directo
                console.warn('[Edición] No se pudo clonar btnAgregar, registrando handler directo', e);
                btnAgregar.addEventListener('click', function(e) {
                    try { e.stopImmediatePropagation(); } catch(ex) {}
                    try { e.preventDefault(); } catch(ex) {}
                    if (editIndex !== null && updatePhase === 'selected') { cargarFilaEnControlesEdit(editIndex); return; }
                    if (editIndex !== null && updatePhase === 'editing') { guardarEdicionFila(editIndex); return; }
                    agregarProducto();
                }, true);
            }
        }
        
        // Enter en cantidad también agrega
        if (cantidadInput) {
            cantidadInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    agregarProducto();
                }
            });
        }
    }

    function agregarProducto() {
        const productoSelect = document.getElementById('producto');
        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        const comentariosInput = document.getElementById('comentarios');
        
        if (!productoSelect || !productoSelect.value) {
            alert('Debe seleccionar un producto');
            return;
        }
        
        if (!cantidadInput || !cantidadInput.value || cantidadInput.value <= 0) {
            alert('Debe ingresar una cantidad válida');
            cantidadInput?.focus();
            return;
        }
        
        const opcionProducto = productoSelect.options[productoSelect.selectedIndex];
        const producto = {
            codigo: codigoInput?.value || '',
            producto: opcionProducto.text,
            productoId: opcionProducto.value || '',
            unidadMedida: opcionProducto.getAttribute('data-unidadmedida') || '',
            cantidad: cantidadInput.value,
            comentarios: comentariosInput?.value || ''
        };
        
        datosGrilla.push(producto);
        actualizarGrilla();
        limpiarCamposProducto();
        
        // Focus de nuevo al producto
        if (window.choicesInstances && window.choicesInstances['producto']) {
            setTimeout(() => {
                const searchInput = document.querySelector('#producto + .choices .choices__input--cloned');
                if (searchInput) searchInput.focus();
            }, 100);
        }
    }

    function actualizarGrilla() {
        const tbody = document.querySelector('#grillaRecepcion tbody');
        if (!tbody) return;
        // SILENCIADO: console.log('[Edición] actualizarGrilla reconstruyendo grilla, datosGrilla snapshot:', datosGrilla);
        try { if (window._dbg && typeof window._dbg.lastSnapshot === 'undefined') window._dbg.lastSnapshot = []; } catch(e){}
        try { window._dbg && window._dbg.lastSnapshot && window._dbg.lastSnapshot.push({when: Date.now(), datos: JSON.parse(JSON.stringify(datosGrilla)), editIndex: editIndex}); } catch(e) {}

        tbody.innerHTML = '';
        datosGrilla.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align:center;">${index + 1}</td>
                <td class="campo-codigo">${item.codigo}</td>
                <td>${item.producto}</td>
                <td class="campo-unidad">${item.unidadMedida}</td>
                <td class="campo-cantidad">${item.cantidad}</td>
                <td>${item.comentarios}</td>
            `;
            // Añadir comportamiento de selección para modo "seleccionar -> Actualizar -> Guardar"
            tr.addEventListener('click', function() {
                // Marcar visualmente la fila seleccionada
                tbody.querySelectorAll('tr').forEach(r => r.classList.remove('table-active'));
                tr.classList.add('table-active');
                // Guardar índice y marcar fase 'selected'
                editIndex = index;
                updatePhase = 'selected';
                // Bloquear cualquier handler global que intente propagar cambios
                try { window.__recep_block_grid_updates = true; } catch(e) {}
                // SILENCIADO: console.log('[Edición] tr click handler set editIndex=', editIndex, 'updatePhase=', updatePhase, 'blockFlag=', !!window.__recep_block_grid_updates);
                try { window._dbg && (window._dbg.lastClick = {when: Date.now(), index: index, item: item}); } catch(e){}
                // Cambiar el texto del botón Agregar a Actualizar
                const btnAgregar = document.getElementById('btnAgregar');
                if (btnAgregar) {
                    btnAgregar.textContent = 'Actualizar';
                    btnAgregar.classList.remove('btn-success');
                    btnAgregar.classList.add('btn-primary');
                }
            });
            tbody.appendChild(tr);
        });
        // Al reconstruir la grilla, si no hay selección activa, restablecer estado del botón
        if (editIndex === null) {
            const btnAgregar = document.getElementById('btnAgregar');
            if (btnAgregar) {
                btnAgregar.textContent = 'Agregar';
                btnAgregar.classList.remove('btn-primary');
                btnAgregar.classList.add('btn-success');
            }
            updatePhase = null;
        }
    }

    function limpiarCamposProducto() {
        if (window.choicesInstances && window.choicesInstances['producto']) {
            window.choicesInstances['producto'].setChoiceByValue('');
            // DESHABILITADO: if (window.updateProductoChoiceWrap) window.updateProductoChoiceWrap();
        }
        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        const comentariosInput = document.getElementById('comentarios');
        
        if (codigoInput) codigoInput.value = '';
        if (cantidadInput) cantidadInput.value = '';
        if (comentariosInput) comentariosInput.value = '';
        // Si estábamos en fase de edición, cancelar la edición al limpiar
        editIndex = null;
        updatePhase = null;
        try { window.__recep_block_grid_updates = false; } catch(e) {}
        const btnAgregar = document.getElementById('btnAgregar');
        if (btnAgregar) {
            btnAgregar.textContent = 'Agregar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
        }
    }

    // Poblar controles con los datos de la fila seleccionada (modo edición)
    function cargarFilaEnControlesEdit(idx) {
        // SILENCIADO: console.log('[Edición] cargarFilaEnControlesEdit called idx=', idx, 'blockFlag=', !!window.__recep_block_grid_updates);
        if (idx === null || typeof idx === 'undefined') return;
        const item = datosGrilla[idx];
        // SILENCIADO: console.log('[Edición] cargarFilaEnControlesEdit item from datosGrilla:', item, 'datosGrilla.length=', datosGrilla.length);
        try { window._dbg && (window._dbg.lastLoad = {when: Date.now(), idx: idx, item: JSON.parse(JSON.stringify(item))}); } catch(e){}
        if (!item) return;

        // Seleccionar el producto en el select buscando por DESCRIPCIÓN, id o código.
        // FIX (2026-08-27): La lógica anterior priorizaba la coincidencia por id derivado
        // del backend. Como recepciones_internas_productos sólo guarda CodigoProducto y
        // DescripcionProducto, el ProductoId se obtiene con una subconsulta LIMIT 1 por
        // código; si hay varios productos con el mismo código (ej: código '0' con varias
        // descripciones), ese id puede apuntar a otro producto. Por eso la fuente fiable
        // es la DESCRIPCIÓN guardada en el vale. Además se recupera el producto que
        // rebuildProductoSelectEdit excluye del select por estar ya en la grilla.
        const productoSelect = document.getElementById('producto');
        if (productoSelect) {
            const opts = Array.from(productoSelect.options);
            let opt = null;
            let selectModificado = false;

            const idStr = item.productoId ? String(item.productoId) : '';
            const itemProdNorm = String(item.producto || '').trim().toLowerCase();
            const itemSinPrefijo = itemProdNorm.replace(/^\d+\s*-\s*/, '');
            const itemCodigoNorm = String(item.codigo || '').trim();

            // 1) Buscar el mejor candidato en el catálogo maestro (window.productosData),
            //    que no está filtrado por la grilla. Prioridad: DESCRIPCIÓN normalizada
            //    (sin el prefijo "CÓDIGO - "), luego id, luego código.
            const catalogo = window.productosData || [];
            let maestro = null;
            if (itemSinPrefijo) {
                maestro = catalogo.find(p => {
                    const cod = (p.Codigo !== undefined && p.Codigo !== null ? String(p.Codigo) : '').trim();
                    const fullText = (cod ? cod + ' - ' : '') + (p.Producto || p.DescripcionProducto || p.Nombre || '');
                    return fullText.trim().toLowerCase().replace(/^\d+\s*-\s*/, '') === itemSinPrefijo;
                });
            }
            if (!maestro && idStr) {
                maestro = catalogo.find(p => p.Id !== undefined && String(p.Id) === idStr);
            }
            if (!maestro && itemCodigoNorm !== '') {
                maestro = catalogo.find(p => {
                    const cod = (p.Codigo !== undefined && p.Codigo !== null ? String(p.Codigo) : '').trim();
                    return cod === itemCodigoNorm;
                });
            }

            // 2) Si hay maestro, buscar su option en el select actual; si fue excluido
            //    (por estar en la grilla), re-agregarlo para poder seleccionarlo.
            if (maestro) {
                const mid = String(maestro.Id);
                opt = opts.find(o => String(o.value || '') === mid);
                if (!opt) {
                    const codigoOpt = (maestro.Codigo !== undefined && maestro.Codigo !== null ? String(maestro.Codigo) : '').trim();
                    const fullText = (codigoOpt ? codigoOpt + ' - ' : '') + (maestro.Producto || maestro.DescripcionProducto || maestro.Nombre || '');
                    const optNuevo = document.createElement('option');
                    optNuevo.value = maestro.Id;
                    optNuevo.text = fullText;
                    optNuevo.setAttribute('data-codigo', codigoOpt);
                    if (maestro.UnidadMedida) optNuevo.setAttribute('data-unidadmedida', maestro.UnidadMedida);
                    productoSelect.appendChild(optNuevo);
                    selectModificado = true;
                    opt = optNuevo;
                }
            } else if (idStr) {
                // Producto sin registro en el maestro: buscar por id en el select o
                // crear una opción sintética con los datos de la fila.
                opt = opts.find(o => String(o.value || '') === idStr);
                if (!opt) {
                    const optNuevo = document.createElement('option');
                    optNuevo.value = idStr;
                    optNuevo.text = item.producto || idStr;
                    optNuevo.setAttribute('data-codigo', itemCodigoNorm);
                    if (item.unidadMedida) optNuevo.setAttribute('data-unidadmedida', item.unidadMedida);
                    const existeTexto = Array.from(productoSelect.options).some(o => (o.textContent || '').trim().toLowerCase() === String(item.producto || '').trim().toLowerCase());
                    if (!existeTexto) {
                        productoSelect.appendChild(optNuevo);
                        selectModificado = true;
                        opt = optNuevo;
                    } else {
                        opt = Array.from(productoSelect.options).find(o => (o.textContent || '').trim().toLowerCase() === String(item.producto || '').trim().toLowerCase());
                    }
                }
            }

            // 3) Último recurso: buscar directamente en las opciones del select por
            //    texto normalizado o por código (incluye el código '0').
            if (!opt) {
                opt = opts.find(o => {
                    const oText = (o.textContent || '').trim().toLowerCase();
                    return oText === itemProdNorm || (itemSinPrefijo && oText.replace(/^\d+\s*-\s*/, '') === itemSinPrefijo);
                });
                if (!opt && itemCodigoNorm !== '') {
                    opt = opts.find(o => (o.getAttribute('data-codigo') || '').trim() === itemCodigoNorm);
                }
            }

            if (opt) {
                // Si agregamos opciones nuevas al select nativo, recrear Choices para
                // que las reconozca (conservando los data-* en los options nativos)
                if (selectModificado && window.choicesInstances && window.choicesInstances['producto']) {
                    try {
                        window.choicesInstances['producto'].destroy();
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
                    } catch(e) { /* ignore */ }
                }
                productoSelect.value = opt.value;
                if (window.choicesInstances && window.choicesInstances['producto']) {
                    // Set choice and re-run wrap function several times with delays
                    setTimeout(()=> {
                        try { window.choicesInstances['producto'].setChoiceByValue(String(opt.value)); } catch(e){}
                        // Aplicar wrapping después de cargar el producto
                        setTimeout(() => {
                            try {
                                // Usar updateAllChoicesWrap si está disponible (más confiable)
                                if (typeof window.updateAllChoicesWrap === 'function') {
                                    window.updateAllChoicesWrap();
                                } else if (typeof window.updateProductoChoiceWrap === 'function') {
                                    window.updateProductoChoiceWrap();
                                }
                            } catch(e){}
                        }, 50);
                    }, 100);
                    // Forzar que la etiqueta visible muestre la descripción tal como está en la grilla
                    try {
                        setTimeout(() => {
                            try {
                                const choicesEl = document.querySelector('#producto + .choices');
                                const visible = choicesEl && choicesEl.querySelector('.choices__list--single .choices__item');
                                if (visible && item.producto) {
                                    visible.textContent = item.producto;
                                }
                            } catch (e) {}
                        }, 120);
                    } catch(e) {}
                }
            }
        }

        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        const comentariosInput = document.getElementById('comentarios');
        const unidadInput = document.getElementById('unidadMedida');
        if (codigoInput) codigoInput.value = (item.codigo === '0' || item.codigo === 0) ? '0' : (item.codigo != null ? String(item.codigo) : '');
        if (cantidadInput) cantidadInput.value = item.cantidad || '';
        if (comentariosInput) comentariosInput.value = item.comentarios || '';
        if (unidadInput) unidadInput.value = item.unidadMedida || '';

        // Pasar a fase editing
        updatePhase = 'editing';
        const btnAgregar = document.getElementById('btnAgregar');
        if (btnAgregar) {
            btnAgregar.textContent = 'Guardar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
        }
    }

    // Guardar los cambios hechos en los controles sobre la fila editada
    function guardarEdicionFila(idx) {
        if (idx === null || typeof idx === 'undefined') return;
        const productoSelect = document.getElementById('producto');
        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        const comentariosInput = document.getElementById('comentarios');
        const opcionProducto = productoSelect?.options[productoSelect.selectedIndex];
        // Si el option seleccionado corresponde al mismo productoId que el registro
        // pero su texto en el maestro difiere de la descripción guardada en la grilla,
        // preferimos conservar la descripción original del vale para evitar reemplazarla.
        let productoText = '';
        if (opcionProducto) {
            productoText = opcionProducto.text || '';
            try {
                if (datosGrilla[idx] && datosGrilla[idx].producto && String(opcionProducto.value || '') === String(datosGrilla[idx].productoId || '')) {
                    if ((datosGrilla[idx].producto || '').trim() !== (opcionProducto.text || '').trim()) {
                        productoText = datosGrilla[idx].producto;
                    }
                }
            } catch(e) { /* ignore */ }
        } else {
            productoText = (datosGrilla[idx].producto || '');
        }
        const unidadMedida = opcionProducto ? (opcionProducto.getAttribute('data-unidadmedida') || '') : (datosGrilla[idx].unidadMedida || '');

        try { console.log('[Edición] guardarEdicionFila BEFORE idx=', idx, 'datosGrilla[idx]=', datosGrilla[idx]); } catch(e){}

        datosGrilla[idx] = {
            codigo: codigoInput?.value || '',
            producto: productoText,
            productoId: opcionProducto ? String(opcionProducto.value || '') : (datosGrilla[idx].productoId || ''),
            unidadMedida: unidadMedida,
            cantidad: cantidadInput?.value || '',
            comentarios: comentariosInput?.value || ''
        };

        try { console.log('[Edición] guardarEdicionFila AFTER idx=', idx, 'new datosGrilla[idx]=', datosGrilla[idx]); } catch(e){}
        try { window._dbg && (window._dbg.lastSave = {when: Date.now(), idx: idx, item: JSON.parse(JSON.stringify(datosGrilla[idx])), full: JSON.parse(JSON.stringify(datosGrilla))}); } catch(e){}

        // Actualizar la grilla y resetear estado
        actualizarGrilla();
        // Reconstruir el combo de productos excluyendo los que ya están en la grilla,
        // para quitar el option que cargarFilaEnControlesEdit pudo re-agregar al editar
        // y evitar que se pueda volver a seleccionar como duplicado.
        try { rebuildProductoSelectEdit(); } catch(e) { console.warn('[Edición] error rebuildProductoSelectEdit tras guardar edición', e); }
        limpiarCamposProducto();
        editIndex = null;
        updatePhase = null;
        try { window.__recep_block_grid_updates = false; } catch(e) {}
    }

    // Configurar botones principales
    function setupBotones() {
        const btnQuitar = document.getElementById('btnQuitar');
        const btnLimpiar = document.getElementById('btnLimpiar');
        const btnGuardar = document.getElementById('btnGuardar');
        const btnModificar = document.getElementById('btnModificar');
        
        if (btnQuitar) {
            btnQuitar.addEventListener('click', quitarProducto);
        }
        
        if (btnLimpiar) {
            // En edición el botón Limpiar debe resetear los campos de producto (producto, codigo, cantidad, comentarios)
            // y restaurar el combo de productos, sin limpiar automáticamente toda la grilla.
            btnLimpiar.addEventListener('click', function() {
                try { if (typeof limpiarCamposProducto === 'function') limpiarCamposProducto(); } catch(e){}
                try { if (typeof limpiarProductoCombo === 'function') limpiarProductoCombo(); } catch(e){}
            });
        }
        
        if (btnGuardar) {
            // Flag para prevenir doble clic (declarado en el ámbito raíz del IIFE)
            
            btnGuardar.addEventListener('click', function(e) {
                // FIX: Prevenir doble clic
                if (_guardandoInternasEditEnProceso) {
                    console.log('[Edición] Guardado ya en proceso, ignorando clic');
                    return;
                }
                _guardandoInternasEditEnProceso = true;
                
                // FIX: Prevenir que otros listeners del mismo evento se ejecuten
                // (especialmente el de recepcionesinternas.js que también está registrado)
                try { e.stopImmediatePropagation(); } catch(ex) {}
                guardarRecepcion();
            });
        }
        
        if (btnModificar) {
            btnModificar.addEventListener('click', habilitarEdicion);
        }
    }

    function quitarProducto() {
        if (datosGrilla.length === 0) {
            alert('No hay productos para quitar');
            return;
        }
        datosGrilla.pop();
        actualizarGrilla();
    }

    function limpiarGrilla() {
        if (datosGrilla.length === 0) return;
        if (confirm('¿Está seguro de limpiar toda la grilla?')) {
            datosGrilla = [];
            actualizarGrilla();
            // reset edición
            editIndex = null;
            updatePhase = null;
        }
    }

    function guardarRecepcion() {
        console.log('[Edición] Guardando recepción');
        
        // FIX: Deshabilitar botón inmediatamente para evitar doble clic
        try {
            const _btn = document.getElementById('btnGuardar');
            if (_btn) {
                _btn.disabled = true;
                _btn.textContent = 'Guardando...';
            }
        } catch(e) {}
        
        // Función restaurar botón
        function restaurarBtnGuardarIntEdit() {
            _guardandoInternasEditEnProceso = false;
            const btnG = document.getElementById('btnGuardar');
            if (btnG) {
                btnG.disabled = false;
                btnG.textContent = 'Guardar';
            }
        }
        
        // Validar campos obligatorios
        function getFieldValueLocal(id) {
            try {
                // Preferir obtener valor desde Choices.js si existe (más fiable cuando el select está envuelto)
                if (window.choicesInstances && window.choicesInstances[id]) {
                    const ci = window.choicesInstances[id];
                    if (typeof ci.getValue === 'function') {
                        try { const v = ci.getValue(true); if (v !== undefined && v !== null && v !== '') return v; } catch(e) {}
                        try { const arr = ci.getValue(); if (Array.isArray(arr) && arr.length) return arr[0].value || ''; } catch(e) {}
                    }
                }

                // Fallback al valor del elemento DOM
                const el = document.getElementById(id);
                if (el && el.value !== undefined && el.value !== null && String(el.value).trim() !== '') return el.value;
            } catch(e) { console.debug('getFieldValueLocal error', id, e); }
            return '';
        }

        const fecha = getFieldValueLocal('fecha');
        const turno = getFieldValueLocal('turno');
        const subarea = getFieldValueLocal('subarea');
        const emisor = getFieldValueLocal('emisor');
        const despachador = getFieldValueLocal('despachador');
        const medioTransporte = getFieldValueLocal('medioTransporte');
        const verificador = getFieldValueLocal('verificador');
        try{ console.log('[DIAG-guardarEdit] campos leídos ->', { fecha, turno, subarea, emisor, despachador, medioTransporte, verificador }, '| datosGrilla.length=', datosGrilla.length, '| valeIdActual=', valeIdActual, '| typeof valeIdActual=', typeof valeIdActual); }catch(e){}

        if (!fecha || !turno || !subarea || !emisor || !despachador || !medioTransporte || !verificador) {
            try{ console.log('[DIAG-guardarEdit] *** VALIDACIÓN FALLÓ: campo obligatorio vacío (fecha=', fecha, 'turno=', turno, 'subarea=', subarea, 'emisor=', emisor, 'despachador=', despachador, 'medioTransporte=', medioTransporte, 'verificador=', verificador, ').'); }catch(e){}
            mostrarMensajeError('Debe completar todos los campos obligatorios');
            restaurarBtnGuardarIntEdit();
            return;
        }

        if (datosGrilla.length === 0) {
            try{ console.log('[DIAG-guardarEdit] *** VALIDACIÓN FALLÓ: sin productos en datosGrilla.'); }catch(e){}
            mostrarMensajeError('Debe agregar al menos un producto');
            restaurarBtnGuardarIntEdit();
            return;
        }
        
        const subareaSelect = document.getElementById('subarea');
        const opcionSubarea = subareaSelect?.options[subareaSelect.selectedIndex];
        let areaId = '';
        if (opcionSubarea && opcionSubarea.value) {
            const opt = Array.from(subareaSelect.options).find(o => o.value == opcionSubarea.value);
            areaId = opt ? (opt.getAttribute('data-area') || '') : '';
        }
        
        const correlativoEl = document.getElementById('correlativoVale');
        const correlativoTexto = correlativoEl?.textContent || '';
        
        console.log('[Edición] valeIdActual antes de enviar:', valeIdActual);
        console.log('[Edición] typeof valeIdActual:', typeof valeIdActual);
        
        const datos = {
            Id: valeIdActual,
            correlativoVale: correlativoTexto,
            fecha: fecha,
            turno: turno,
            subarea: subarea,
            area: areaId,
            emisor: emisor,
            despachador: despachador,
            medioTransporte: medioTransporte,
            verificador: verificador,
            productos: datosGrilla
        };
        
        console.log('[Edición] Datos a guardar:', datos);
        console.log('[Edición] JSON.stringify:', JSON.stringify(datos));
        
        // Intentar múltiples endpoints (ruta amigable y fallback index.php)
        const endpoints = [
            (window.APP_URL || window.BASE_URL) + '/recepcionesinternas/guardar',
            (window.BASE_URL || window.APP_URL) + '/index.php?url=recepcionesinternas/guardar'
        ];

        function tryEndpoint(i) {
            if (i >= endpoints.length) {
                mostrarMensajeError('No se pudo contactar al servidor para guardar la recepción', 'error');
                restaurarBtnGuardarIntEdit();
                return Promise.resolve();
            }
            const urlTry = endpoints[i];
            console.log('[Edición] intentar endpoint:', urlTry);
            try { console.log('[Edición] document.cookie:', document.cookie); } catch(e) { console.warn('No se pudo leer document.cookie', e); }
            try{ console.log('[DIAG-tryEndpoint] iniciando fetch al endpoint', i, '=', urlTry); }catch(e){}
            return fetch(urlTry, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            })
            .then(r => r.text())
            .then(text => {
                try{ console.log('[DIAG-tryEndpoint] endpoint', i, 'respondió texto (primeros 300):', String(text).slice(0,300)); }catch(e){}
                try {
                    const data = JSON.parse(text);
                    try{ console.log('[DIAG-tryEndpoint] endpoint', i, 'JSON ok -> success=', data.success, '| limite_alcanzado=', data.limite_alcanzado, '| message=', data.message, '| id=', data.id); }catch(e){}
                    console.log('[Edición] Respuesta del servidor:', data);
                    if (data.success) {
                        if (valeIdActual) mostrarMensajeError('Recepción modificada correctamente.');
                        else mostrarMensajeError('Recepción guardada correctamente.');

                        // Resetear flag y texto del botón
                        _guardandoInternasEditEnProceso = false;
                        const _btnG = document.getElementById('btnGuardar');
                        if (_btnG) _btnG.textContent = 'Guardar';

                        if (!valeIdActual && data.id) valeIdActual = data.id;
                        try { window.valeIdActual = valeIdActual; } catch(e) {}

                        bloquearControles();
                        const btnModificar = document.getElementById('btnModificar'); if (btnModificar) btnModificar.disabled = false;
                        const btnGuardar = document.getElementById('btnGuardar'); if (btnGuardar) btnGuardar.disabled = true;
                        return Promise.resolve();
                    }

                    if (data.limite_alcanzado) {
                        mostrarMensajeError('Error: ' + (data.message || 'Este vale alcanzó el límite de modificaciones.'), 'error');
                        bloquearControles();
                        const btnGuardar = document.getElementById('btnGuardar'); if (btnGuardar) btnGuardar.disabled = true;
                        const btnModificar = document.getElementById('btnModificar'); if (btnModificar) btnModificar.disabled = true;
                        return Promise.resolve();
                    }

                    mostrarMensajeError('Error al guardar: ' + (data.message || 'Error desconocido'), 'error');
                    restaurarBtnGuardarIntEdit();
                    return Promise.resolve();
                } catch (e) {
                    const snippet = (text || '').slice(0,800);
                    console.error('[Edición] guardarRecepcion: respuesta no JSON en', urlTry, snippet);
                    if (snippet.indexOf('<title>Bienvenido a Lavoro-ERP') !== -1 || snippet.indexOf('Cerrar Sesión') !== -1) {
                        mostrarMensajeError('Sesión expirada. Redirigiendo al login...', 'error');
                        setTimeout(() => { window.location.href = (window.APP_URL || '/') + '/login'; }, 900);
                        return Promise.resolve();
                    }
                    return tryEndpoint(i + 1);
                }
            })
            .catch(err => {
                try{ console.log('[DIAG-tryEndpoint] endpoint', i, 'CATCH fetch error:', err && err.message ? err.message : err); }catch(e){}
                console.warn('[Edición] Error fetch guardar en', urlTry, err);
                return tryEndpoint(i + 1);
            })
            .finally(function() {
                try{ console.log('[DIAG-tryEndpoint] FINALLY ejecutado para endpoint', i, '| endpoints.length=', endpoints.length, '| restaurará botón?', endpoints.length <= 1); }catch(e){}
                if (endpoints.length <= 1) {
                    restaurarBtnGuardarIntEdit();
                }
            });
        }

        tryEndpoint(0);
    }

    function habilitarEdicion() {
        console.log('[Edición] Intentando habilitar edición (verificando límite de modificaciones)');

        // Debe existir un vale cargado
        if (!valeIdActual) {
            mostrarMensajeError('Primero cargue una recepción antes de modificar');
            return;
        }

        // Consultar al backend si se puede modificar
        try {
            const url = (window.APP_URL || window.BASE_URL) + '/recepcionesinternas/verificarModificaciones';
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: valeIdActual })
            })
            .then(r => r.json())
            .then(data => {
                if (!data || !data.hasOwnProperty('puede_modificar')) {
                    mostrarMensajeError('Error al verificar límite de modificaciones', 'error');
                    return;
                }

                if (!data.puede_modificar) {
                    // Mostrar validación y mantener los controles deshabilitados
                    mostrarMensajeError(data.mensaje || 'Esta recepción ya alcanzó el límite de 3 modificaciones', 'error');
                    try { bloquearControles(); } catch(e){}
                    try { const btnGuardar = document.getElementById('btnGuardar'); if (btnGuardar) btnGuardar.disabled = true; } catch(e){}
                    try { const btnModificar = document.getElementById('btnModificar'); if (btnModificar) btnModificar.disabled = true; } catch(e){}
                    return;
                }

                // Si puede modificar, habilitar controles salvo los explícitamente protegidos
                console.log('[Edición] Límite de modificaciones OK, habilitando edición');
                // Incluir 'chkTurno' para permitir que el usuario active el modo manual de turno
                const permitir = ['fecha','subarea','emisor','despachador','medioTransporte','verificador','producto','cantidad','comentarios','chkTurno'];
                permitir.forEach(id => {
                    try {
                        const el = document.getElementById(id);
                        if (el) {
                            el.disabled = false;
                            if (window.choicesInstances && window.choicesInstances[id] && typeof window.choicesInstances[id].enable === 'function') {
                                window.choicesInstances[id].enable();
                            }
                        }
                    } catch(e) { console.warn('[Edición] habilitarEdicion error enabling', id, e); }
                });

                // Botones de grilla
                ['btnAgregar','btnQuitar','btnLimpiar'].forEach(id => { try { const b = document.getElementById(id); if (b) b.disabled = false; } catch(e){} });

                // Mantener turno, area y codigo deshabilitados
                try { const turno = document.getElementById('turno'); if (turno) { turno.setAttribute('disabled','disabled'); if (window.choicesInstances && window.choicesInstances['turno'] && typeof window.choicesInstances['turno'].disable === 'function') window.choicesInstances['turno'].disable(); } } catch(e){}
                try { const area = document.getElementById('area'); if (area) area.disabled = true; } catch(e){}
                try { const codigo = document.getElementById('codigo'); if (codigo) codigo.disabled = true; } catch(e){}

                // Actualizar botones
                try { const btnGuardar = document.getElementById('btnGuardar'); if (btnGuardar) btnGuardar.disabled = false; } catch(e){}
                try { const btnModificar = document.getElementById('btnModificar'); if (btnModificar) { btnModificar.disabled = true; btnModificar.classList.remove('pulse-animation'); } } catch(e){}

                // Sincronizar apariencia de selects Choices
                try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}

                console.log('[Edición] Edición habilitada parcialmente');
            })
            .catch(err => {
                console.error('[Edición] Error verificando modificaciones:', err);
                mostrarMensajeError('Error al verificar límite de modificaciones', 'error');
            });
        } catch (e) {
            console.error('habilitarEdicion error', e);
            mostrarMensajeError('Error interno al verificar modificaciones', 'error');
        }
    }

    // Bloquear/desbloquear controles
    function bloquearControles() {
        const campos = ['fecha', 'turno', 'subarea', 'emisor', 'despachador', 'medioTransporte', 'verificador', 'producto', 'cantidad', 'comentarios'];
        
        campos.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.disabled = true;
                // Usar window.choicesInstances (global) en vez de choicesInstances (local)
                if (window.choicesInstances && window.choicesInstances[id]) {
                    window.choicesInstances[id].disable();
                }
            }
        });
        
        const botonesGrilla = ['btnAgregar', 'btnQuitar', 'btnLimpiar'];
        botonesGrilla.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.disabled = true;
        });
        
        const chkTurno = document.getElementById('chkTurno');
        if (chkTurno) chkTurno.disabled = true;
        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
    }

    function desbloquearControles() {
        const campos = ['fecha', 'turno', 'subarea', 'emisor', 'despachador', 'medioTransporte', 'verificador', 'producto', 'cantidad', 'comentarios'];
        
        campos.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.disabled = false;
                // Usar window.choicesInstances (global) en vez de choicesInstances (local)
                if (window.choicesInstances && window.choicesInstances[id]) {
                    window.choicesInstances[id].enable();
                }
            }
        });
        
        const botonesGrilla = ['btnAgregar', 'btnQuitar', 'btnLimpiar'];
        botonesGrilla.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.disabled = false;
        });
        
        const chkTurno = document.getElementById('chkTurno');
        if (chkTurno) chkTurno.disabled = false;
        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
    }

    // --- MODO EDICIÓN ESPECÍFICO ---
    function setupModoEdicion() {
        console.log('[Edición] Configurando modo edición');
        
        // Configurar botón Buscar Vale con múltiples formas de attach
        const btnBuscarVale = document.getElementById('btnBuscarVale');
        if (btnBuscarVale) {
            console.log('[Edición] Configurando btnBuscarVale');
            btnBuscarVale.addEventListener('click', abrirModalBusqueda);
            // Backup onclick
            btnBuscarVale.onclick = function() {
                console.log('[Edición] btnBuscarVale.onclick ejecutado');
                abrirModalBusqueda();
            };
            console.log('[Edición] btnBuscarVale configurado con addEventListener y onclick');
        } else {
            console.warn('[Edición] btnBuscarVale NO encontrado en el DOM');
        }
        // Delegated fallback: si por alguna razón el botón es reemplazado dinámicamente,
        // capturamos clicks a nivel de documento como último recurso.
        try {
            document.addEventListener('click', function(evt) {
                const el = evt.target.closest ? evt.target.closest('#btnBuscarVale') : (evt.target.id === 'btnBuscarVale' ? evt.target : null);
                if (el) {
                    console.log('[Edición] Delegated click detected on #btnBuscarVale');
                    try { abrirModalBusqueda(); } catch(e) { console.error('Delegated abrirModalBusqueda failed', e); }
                }
            });
        } catch(e) { console.warn('[Edición] No se pudo agregar delegated listener para btnBuscarVale', e); }
        
        // Exponer abrirModalBusqueda al scope global como respaldo
        window.abrirModalBusquedaInternos = abrirModalBusqueda;
        
        // Configurar modal de búsqueda
        setupModalBusqueda();
        
        // Inicialmente el botón modificar está deshabilitado
        const btnModificar = document.getElementById('btnModificar');
        if (btnModificar) btnModificar.disabled = true;
        
        // Botón guardar también deshabilitado
        const btnGuardar = document.getElementById('btnGuardar');
        if (btnGuardar) btnGuardar.disabled = true;
    }

    function abrirModalBusqueda() {
        console.log('[Edición] abrirModalBusqueda ejecutado');
        const modalEl = document.getElementById('modalBuscarVale');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            
            // Fix aria-hidden warning: removerlo cuando el modal esté visible
            // y enfocar el campo de filtro
            modalEl.addEventListener('shown.bs.modal', function() {
                modalEl.removeAttribute('aria-hidden');
                const filtro = document.getElementById('filtroNVale');
                if (filtro) {
                    filtro.value = '';
                    try { filtro.focus(); } catch(e) {}
                    // Fallbacks por si otro script roba el foco
                    try { requestAnimationFrame(() => { try { filtro.focus(); } catch(e) {} }); } catch(e) {}
                    setTimeout(() => { try { filtro.focus(); } catch(e) {} }, 50);
                    setTimeout(() => { try { filtro.focus(); } catch(e) {} }, 200);
                }
            }, { once: true });
            
            // IMPORTANTE: Asegurar que los controles NO se bloqueen al cerrar el modal
            modalEl.addEventListener('hidden.bs.modal', function() {
                console.log('[Edición] Modal cerrado - manteniendo controles desbloqueados');
                // Asegurar que los controles estén habilitados
                setTimeout(() => {
                    // Remover cualquier backdrop residual de Bootstrap
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    
                    // Remover clases de body que Bootstrap añade
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 100);
            }, { once: true });
            
            // Limpiar tabla
            const tbody = document.querySelector('#tablaVales tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-info-circle me-2"></i>Haga clic en "Buscar" para buscar vales</td></tr>';
            }
            
            // Limpiar filtro (el enfoque se realiza en shown.bs.modal)
            const filtro = document.getElementById('filtroNVale');
            if (filtro) {
                filtro.value = '';
            }
            console.log('[Edición] Modal de búsqueda abierto');
        } else {
            console.error('[Edición] modalBuscarVale NO encontrado');
        }
    }

    function setupModalBusqueda() {
        const btnFiltrar = document.getElementById('btnFiltrarVales');
        if (btnFiltrar) {
            btnFiltrar.addEventListener('click', buscarVales);
        }
        
        const filtroNVale = document.getElementById('filtroNVale');
        if (filtroNVale) {
            filtroNVale.addEventListener('input', function() {
                _valeUnicoId = null; // Al escribir, resetear resultado único
            });
            filtroNVale.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (_valeUnicoId !== null) {
                        // Ya hay un resultado único: cargar el vale directamente
                        window.cargarVale(_valeUnicoId);
                    } else {
                        buscarVales();
                    }
                }
            });
        }
    }

    function buscarVales() {
        const filtroNVale = document.getElementById('filtroNVale');
        const nvale = filtroNVale?.value.replace(/\D/g, '') || '';
        
        const params = new URLSearchParams();
        if (nvale) params.append('nvale', nvale);
        
        const url = (window.APP_URL || window.BASE_URL) + '/recepcionesinternas/buscarVales?' + params.toString();

        fetch(url, { credentials: 'same-origin' })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        mostrarResultadosVales(data.vales);
                    } else {
                        mostrarMensajeBusqueda('Error al buscar vales: ' + (data.error || 'Error desconocido'), 'danger');
                    }
                } catch (e) {
                    console.error('[Edición] buscarVales: respuesta no JSON (posible HTML de login):', text.slice(0,800));
                    mostrarMensajeBusqueda('Sesión expirada o respuesta inesperada. Por favor inicie sesión.', 'danger');
                }
            })
            .catch(error => {
                console.error('[Edición] Error al buscar vales:', error);
                mostrarMensajeBusqueda('Error de conexión al buscar vales', 'danger');
            });
    }

    function mostrarResultadosVales(vales) {
        const tbody = document.querySelector('#tablaVales tbody');
        if (!tbody) return;
        
        if (!vales || vales.length === 0) {
            _valeUnicoId = null;
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No se encontraron vales</td></tr>';
            return;
        }
        
        _valeUnicoId = vales.length === 1 ? vales[0].Id : null;
        tbody.innerHTML = '';
        vales.forEach(vale => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>VRI-${String(vale.NVale).padStart(6, '0')}</strong></td>
                <td>${formatearFecha(vale.Fecha)}</td>
                <td>${vale.Hora || ''}</td>
                <td>${vale.AreaNombre || ''}</td>
                <td>${vale.SubareaNombre || ''}</td>
                <td class="text-center actions">
                    <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                        <button class="btn btn-sm btn-primary me-1" onclick="window.cargarVale(${vale.Id})" title="Cargar vale">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="window.abrirModalAnular(${vale.Id}, 'VRI-${String(vale.NVale).padStart(6, '0')}')" title="Anular vale">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function mostrarMensajeBusqueda(mensaje, tipo) {
        const mensajeEl = document.getElementById('mensajeBusqueda');
        const textoEl = document.getElementById('textoBusqueda');
        
        if (mensajeEl && textoEl) {
            textoEl.textContent = mensaje;
            mensajeEl.className = 'alert mt-3';
            mensajeEl.classList.add('alert-' + tipo);
            mensajeEl.classList.remove('d-none');
            
            setTimeout(() => {
                mensajeEl.classList.add('d-none');
            }, 5000);
        }
    }

    // Cargar vale seleccionado
    window.cargarVale = function(valeId) {
        console.log('[Edición] Cargando vale ID:', valeId);
        
        fetch((window.APP_URL || window.BASE_URL) + '/recepcionesinternas/obtenerVale?id=' + valeId, { credentials: 'same-origin' })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    console.log('[Edición] Respuesta:', data);
                    if (data.success) {
                        poblarFormularioConVale(data.vale);
                        // Cerrar modal
                        const modalEl = document.getElementById('modalBuscarVale');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }
                    } else {
                        mostrarMensajeError('Error al cargar vale: ' + (data.message || 'Error desconocido'));
                    }
                } catch (e) {
                    console.error('[Edición] obtenerVale: respuesta no JSON (posible HTML de login):', text.slice(0,800));
                    mostrarMensajeError('Sesión expirada o respuesta inesperada. Por favor inicie sesión.', 'error');
                }
            })
            .catch(error => {
                console.error('[Edición] Error:', error);
                mostrarMensajeError('Error de conexión al cargar el vale');
            });
    };

    // Abrir modal de anulación (igual que Recepciones Externas)
    window.abrirModalAnular = function(valeId, valeNumero) {
        // Cerrar modal de búsqueda si está abierto
        const modalBuscarEl = document.getElementById('modalBuscarVale');
        if (modalBuscarEl) {
            const modalBuscar = bootstrap.Modal.getInstance(modalBuscarEl);
            if (modalBuscar) modalBuscar.hide();
        }

        const valeNumeroEl = document.getElementById('valeAnularNumero');
        const motivoEl = document.getElementById('motivoAnulacion');
        const feedbackEl = document.getElementById('anularFeedback');
        const modalEl = document.getElementById('modalAnularVale');

        if (!valeNumeroEl || !motivoEl || !feedbackEl || !modalEl) {
            console.error('[Anular] Elementos del modal no encontrados');
            mostrarMensajeBusqueda('Error interno: modal de anulación no encontrado', 'danger');
            return;
        }

        valeNumeroEl.textContent = valeNumero;
        motivoEl.value = '';
        feedbackEl.classList.add('d-none');

        setTimeout(() => {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }, 250);

        const btnConfirmar = document.getElementById('btnConfirmarAnular');
        if (btnConfirmar) {
            btnConfirmar.onclick = function() {
                anularVale(valeId, valeNumero);
            };
        }
    };

    // Anular vale (envía motivo)
    function anularVale(valeId, valeNumero) {
        const motivo = document.getElementById('motivoAnulacion').value.trim();
        const feedback = document.getElementById('anularFeedback');
        const btnConfirmar = document.getElementById('btnConfirmarAnular');

        if (!motivo) {
            feedback.textContent = 'Debe ingresar un motivo de anulación';
            feedback.className = 'alert alert-warning';
            feedback.classList.remove('d-none');
            return;
        }

        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Anulando...';
        }

        fetch((window.APP_URL || window.BASE_URL) + '/recepcionesinternas/anular', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ Id: valeId, motivo: motivo })
        })
        .then(r => r.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    feedback.textContent = `Vale ${valeNumero} anulado exitosamente`;
                    feedback.className = 'alert alert-success';
                    feedback.classList.remove('d-none');

                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAnularVale'));
                        if (modal) modal.hide();

                        const modalBuscar = document.getElementById('modalBuscarVale');
                        if (modalBuscar && modalBuscar.classList.contains('show')) {
                            buscarVales();
                        }

                        if (valeIdActual && parseInt(valeIdActual) === parseInt(valeId)) {
                            bloquearControles();
                            const btnModificar = document.getElementById('btnModificar');
                            if (btnModificar) btnModificar.disabled = true;
                        }
                    }, 1500);
                } else {
                    feedback.textContent = 'Error: ' + (data.message || data.error || 'No se pudo anular el vale');
                    feedback.className = 'alert alert-danger';
                    feedback.classList.remove('d-none');
                    if (btnConfirmar) {
                        btnConfirmar.disabled = false;
                        btnConfirmar.innerHTML = 'Anular Vale';
                    }
                }
            } catch (e) {
                console.error('anularVale: respuesta no JSON (posible HTML de login):', text.slice(0,800));
                feedback.textContent = 'Sesión expirada o respuesta inesperada. Por favor inicie sesión.';
                feedback.className = 'alert alert-danger';
                feedback.classList.remove('d-none');
                if (btnConfirmar) {
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = 'Anular Vale';
                }
            }
        })
        .catch(err => {
            console.error('Error al anular vale:', err);
            feedback.textContent = 'Error de conexión al anular el vale';
            feedback.className = 'alert alert-danger';
            feedback.classList.remove('d-none');
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = 'Anular Vale';
            }
        });
    }

    // Reconstruir el select de productos excluyendo los que ya están en la grilla
    function rebuildProductoSelectEdit() {
        const productoSelect = document.getElementById('producto');
        if (!productoSelect) return;
        
        const descripcionesEnGrilla = new Set();
        datosGrilla.forEach(item => {
            if (item.producto) descripcionesEnGrilla.add(String(item.producto).trim());
        });
        
        if (window.choicesInstances && window.choicesInstances['producto']) {
            try { window.choicesInstances['producto'].destroy(); } catch(e) {}
        }
        
        productoSelect.innerHTML = '';
        var selectOption = document.createElement('option');
        selectOption.value = '';
        selectOption.text = 'Seleccione';
        productoSelect.appendChild(selectOption);
        
        var productosData = window.productosData || [];
        productosData.forEach(function(p) {
            var codigoOpt = p.Codigo !== undefined ? String(p.Codigo).trim() : (p.CodigoProducto ? String(p.CodigoProducto).trim() : (p.codigo ? String(p.codigo).trim() : ''));
            var fullText = (codigoOpt ? codigoOpt + ' - ' : '') + (p.Producto || p.DescripcionProducto || p.Nombre || '');
            
            if (descripcionesEnGrilla.has(fullText)) {
                return;
            }
            
            var opt = document.createElement('option');
            opt.value = p.Id;
            opt.text = fullText;
            opt.setAttribute('data-codigo', codigoOpt);
            if (p.UnidadMedida) opt.setAttribute('data-unidadmedida', p.UnidadMedida);
            productoSelect.appendChild(opt);
        });
        
        window.choicesInstances = window.choicesInstances || {};
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
            placeholderValue: 'Seleccione',
            noResultsText: 'No se encontraron resultados',
            removeItemButton: false,
            duplicateItemsAllowed: false,
        });
        setTimeout(function() {
            if (typeof window.updateProductoChoiceWrap === 'function') window.updateProductoChoiceWrap();
            if (typeof window.updateAllChoicesWrap === 'function') window.updateAllChoicesWrap();
        }, 100);
    }

    function poblarFormularioConVale(vale) {
        console.log('[Edición] Poblando formulario con vale:', vale);
        console.log('[Edición] vale.Id recibido:', vale.Id, 'typeof:', typeof vale.Id);
        
        valeIdActual = vale.Id;
        // Mantener copia en window para inspección desde DevTools
        try { window.valeIdActual = valeIdActual; } catch(e) {}
        window.auditoriaIdActual = vale.Id;
        window.auditoriaModoActual = 'recepcion_interna';
        
        console.log('[Edición] valeIdActual asignado:', valeIdActual, 'typeof:', typeof valeIdActual);
        
        // FIX: Setear data-id en el span correlativoVale como respaldo de seguridad.
        // Esto asegura que si el listener de recepcionesinternas.js (main page) se
        // ejecuta accidentalmente, pueda detectar que es una modificación (tiene data-id)
        // y no dispare una creación duplicada.
        try {
            const corrEl = document.getElementById('correlativoVale');
            if (corrEl) {
                corrEl.setAttribute('data-id', vale.Id);
                console.log('[Edición] data-id seteado en correlativoVale:', vale.Id);
            }
        } catch(e) {
            console.warn('[Edición] No se pudo setear data-id en correlativoVale', e);
        }
        
        // Mostrar los bloques del formulario y la grilla
        const bloques = ['bloqueNumeroVale', 'cardFechaTurno', 'cardResponsables', 'cardProductos', 'botonesGrilla', 'cardGrilla'];
        bloques.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) elemento.classList.remove('d-none');
        });
        
        // Actualizar correlativo
        const correlativoEl = document.getElementById('correlativoVale');
        if (correlativoEl) {
            correlativoEl.textContent = 'VRI-' + String(vale.NVale).padStart(6, '0');
        }
        
        // Poblar campos básicos
        setFieldValue('fecha', vale.Fecha);
        setFieldValue('turno', vale.Turno);
        setFieldValue('subarea', vale.Subarea);
        setFieldValue('area', vale.SubareaNombre);
        // Asegurar que el texto de 'area' no quede en negrita al cargar el vale
        try {
            const areaEl = document.getElementById('area');
            if (areaEl) areaEl.style.setProperty('font-weight', '400', 'important');
            // Si Choices maneja el select, aplicar al elemento visible después de que Choices actualice el DOM
            setTimeout(() => {
                try {
                    let choicesEl = null;
                    if (areaEl && areaEl.nextElementSibling && areaEl.nextElementSibling.classList && areaEl.nextElementSibling.classList.contains('choices')) {
                        choicesEl = areaEl.nextElementSibling;
                    }
                    if (!choicesEl) choicesEl = document.querySelector('#area + .choices') || Array.from(document.querySelectorAll('.choices')).find(c => c.contains(areaEl));
                    if (choicesEl) {
                        const inner = choicesEl.querySelector('.choices__inner');
                        const item = choicesEl.querySelector('.choices__list--single .choices__item');
                        if (inner) inner.style.setProperty('font-weight', '400', 'important');
                        if (item) item.style.setProperty('font-weight', '400', 'important');
                    }
                } catch(e) { /* ignore */ }
            }, 120);
        } catch(e) {}
        setFieldValue('emisor', vale.Emisor);
        setFieldValue('despachador', vale.Despachador);
        setFieldValue('medioTransporte', vale.MedioTransporte);
        setFieldValue('verificador', vale.Verificador);
        
        // Cargar productos en la grilla
        datosGrilla = [];
        if (vale.productos && Array.isArray(vale.productos)) {
            vale.productos.forEach(prod => {
                datosGrilla.push({
                    codigo: prod.CodigoProducto || '',
                    producto: prod.DescripcionProducto || '',
                    productoId: prod.ProductoId ? String(prod.ProductoId) : (prod.CodigoProducto ? String(prod.CodigoProducto) : ''),
                    unidadMedida: prod.UnidadMedida || '',
                    cantidad: prod.Cantidad || '',
                    comentarios: prod.Comentarios || ''
                });
            });
        }
        actualizarGrilla();
        
        // Reconstruir select de productos filtrando los que ya están en la grilla
        try { rebuildProductoSelectEdit(); } catch(e) { console.warn('[Edición] error rebuildProductoSelectEdit', e); }
        
        // Mantener TODOS los controles DESHABILITADOS al mostrar el vale
        bloquearControles();
        // Forzar sincronía visual de Choices (gris cuando disabled)
        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}

        // Botones: guardar debe estar deshabilitado, modificar habilitado para activar edición
        const btnGuardar = document.getElementById('btnGuardar');
        if (btnGuardar) btnGuardar.disabled = true;
        const btnModificar = document.getElementById('btnModificar');
        if (btnModificar) {
            btnModificar.disabled = false;
            btnModificar.classList.remove('pulse-animation');
        }
        
        console.log('[Edición] Vale cargado correctamente y listo para editar');
    }

    function setFieldValue(fieldId, value) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        // Usar window.choicesInstances (global) en vez de choicesInstances (local)
        if (window.choicesInstances && window.choicesInstances[fieldId]) {
            window.choicesInstances[fieldId].setChoiceByValue(value?.toString() || '');
        } else {
            field.value = value || '';
        }
    }

    function formatearFecha(fecha) {
        if (!fecha) return '';
        const partes = fecha.split('-');
        if (partes.length === 3) {
            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        }
        return fecha;
    }

// Fin de recepcionesinternas_edicion.js
