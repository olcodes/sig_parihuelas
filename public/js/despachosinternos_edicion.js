// despachosinternos_edicion.js - Modo edición de vales para Despachos Internos

(function() {
    'use strict';
    
    console.log('[DespachosInternos - Edición] Script cargado');
    
    // Variables globales
    let valeIdActual = null;
    // Exponer para depuración desde la consola (se mantendrá sincronizado)
    try { window.valeIdActual = valeIdActual; } catch(e) {}
    let datosGrilla = [];
    let _valeUnicoId = null; // ID del vale si la última búsqueda arrojó exactamente 1 resultado
    // Estado para edición en la grilla: índice seleccionado y fase de actualización
    let editIndex = null;
    let updatePhase = null; // null | 'selected' | 'editing'
    
    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('[Edición] Inicializando');
        // Reemplazar el botón btnAgregar por un clon para eliminar listeners agregados por el script de "Nuevo"
        try {
            const oldBtn = document.getElementById('btnAgregar');
            if (oldBtn) {
                const newBtn = oldBtn.cloneNode(true);
                oldBtn.parentNode.replaceChild(newBtn, oldBtn);
                console.log('[Edición] Reemplazado btnAgregar para evitar handlers externos');
            }
        } catch (e) { console.warn('[Edición] No se pudo reemplazar btnAgregar:', e); }

        // CRÍTICO: También reemplazar btnGuardar para evitar que el script de "Nuevo" interfiera
        try {
            const oldBtnGuardar = document.getElementById('btnGuardar');
            if (oldBtnGuardar) {
                const newBtnGuardar = oldBtnGuardar.cloneNode(true);
                oldBtnGuardar.parentNode.replaceChild(newBtnGuardar, oldBtnGuardar);
                console.log('[Edición] Reemplazado btnGuardar para evitar handlers externos del script Nuevo');
            }
        } catch (e) { console.warn('[Edición] No se pudo reemplazar btnGuardar:', e); }

        // Reemplazar btnModificar para remover handlers del script "Nuevo" que habilitan controles sin validación
        try {
            const oldBtnModificar = document.getElementById('btnModificar');
            if (oldBtnModificar) {
                const newBtnModificar = oldBtnModificar.cloneNode(true);
                oldBtnModificar.parentNode.replaceChild(newBtnModificar, oldBtnModificar);
                console.log('[Edición] Reemplazado btnModificar para controlar su comportamiento en edición');
            }
        } catch (e) { console.warn('[Edición] No se pudo reemplazar btnModificar:', e); }

        // Inicializaciones necesarias
        try { initChoices(); } catch(e) { console.warn('[Edición] initChoices failed', e); }
        try { setupTurnoAutomatico(); } catch(e) { /* ignore */ }
        try { setupSubareaArea(); } catch(e) { /* ignore */ }
        try { setupProductos(); } catch(e) { /* ignore */ }
        try { setupBotones(); } catch(e) { /* ignore */ }

        // Configurar modo edición específico
        setupModoEdicion();

        // NO bloquear controles en modo edición - los controles empiezan habilitados
        // Los controles solo se bloquean si se carga un vale y se quiere prevenir edición

        console.log('[Edición] Inicialización completada');

        // Asegurar sincronía visual al iniciar (usar función global si está disponible)
        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
    }

    // Mostrar mensaje con diseño (compatible con Nuevo Registro)
    // Implementación ligera de showFloatingMessage local para edición (fallback)
    if (typeof window.showFloatingMessage !== 'function') {
        window.showFloatingMessage = function(message, level) {
            try {
                level = level || 'warning';
                var containerId = 'floatingMsgContainer';
                var container = document.getElementById(containerId);
                if (!container) {
                    container = document.createElement('div');
                    container.id = containerId;
                    container.style.position = 'fixed';
                    container.style.top = '16px';
                    container.style.left = '50%';
                    container.style.transform = 'translateX(-50%)';
                    container.style.zIndex = 99999;
                    container.style.pointerEvents = 'none';
                    document.body.appendChild(container);
                }
                var msg = document.createElement('div');
                msg.className = 'floating-msg floating-' + level;
                msg.style.pointerEvents = 'auto';
                msg.style.marginTop = '6px';
                msg.style.minWidth = '220px';
                msg.style.maxWidth = '720px';
                msg.style.boxShadow = '0 6px 18px rgba(0,0,0,0.12)';
                msg.style.borderRadius = '6px';
                msg.style.padding = '10px 14px';
                msg.style.fontSize = '0.9rem';
                msg.style.color = '#111';
                msg.style.display = 'flex';
                msg.style.alignItems = 'center';
                msg.style.gap = '10px';
                msg.style.background = (level === 'success' ? '#d1fae5' : (level === 'error' ? '#fee' : '#fff7ed'));
                msg.style.border = (level === 'error' ? '2px solid #fca5a5' : '1px solid rgba(0,0,0,0.06)');

                var icon = document.createElement('span');
                icon.style.flex = '0 0 auto';
                icon.style.fontSize = '1.05rem';
                icon.style.display = 'inline-flex';
                icon.style.alignItems = 'center';
                icon.style.justifyContent = 'center';
                icon.style.width = '34px';
                icon.style.height = '34px';
                icon.style.borderRadius = '50%';
                icon.style.fontWeight = '700';
                if (level === 'success') { icon.textContent = '✔'; icon.style.background = '#16a34a'; icon.style.color = '#fff'; }
                else if (level === 'error') { icon.textContent = '✖'; icon.style.background = '#7c3aed'; icon.style.color = '#fff'; }
                else { icon.textContent = '⚠'; icon.style.background = '#f59e0b'; icon.style.color = '#fff'; }

                var text = document.createElement('div');
                text.style.flex = '1 1 auto';
                text.innerText = message;

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.innerText = 'Cerrar';
                btn.style.background = '#f3f4f6';
                btn.style.border = '1px solid rgba(0,0,0,0.06)';
                btn.style.borderRadius = '6px';
                btn.style.padding = '6px 8px';
                btn.style.cursor = 'pointer';
                btn.style.color = '#374151';
                btn.style.fontWeight = '600';
                btn.style.fontSize = '0.85rem';
                btn.addEventListener('click', function() { try { msg.remove(); } catch(e){} });
                btn.style.marginLeft = '8px';

                msg.appendChild(icon);
                msg.appendChild(text);
                msg.appendChild(btn);

                container.appendChild(msg);
                setTimeout(function() { try { msg.remove(); } catch(e){} }, 4000);
            } catch (e) { console.warn('showFloatingMessage fallback error', e); }
        };
    }

    function mostrarMensajeError(msg, tipo = 'warning') {
        try {
            tipo = tipo || 'warning';
            // Si es error, preferir el mensaje flotante (si existe)
            if (tipo === 'error') {
                try { var _hide = document.getElementById('mensajeError'); if(_hide) _hide.classList.add('d-none'); } catch(e){}
                if (typeof window !== 'undefined' && typeof window.showFloatingMessage === 'function') {
                    try { window.showFloatingMessage(msg, 'error'); return; } catch(e) {}
                }
            }

            var div = document.getElementById('mensajeError');
            if (!div) { alert(msg); return; }

            // Adaptar colores según el tipo (inline fallback)
            if (tipo === 'error') {
                div.style.color = '#991b1b';
                div.style.background = '#fee';
                div.style.borderColor = '#fca5a5';
            } else if (tipo === 'info') {
                div.style.color = '#1e40af';
                div.style.background = '#dbeafe';
                div.style.borderColor = '#93c5fd';
            } else {
                div.style.color = '#7c4700';
                div.style.background = '#fffbe6';
                div.style.borderColor = '#ffe082';
            }

            div.textContent = msg;
            div.classList.remove('d-none');
            setTimeout(function() { try{ div.classList.add('d-none'); }catch(e){} }, 5000);
        } catch (e) { console.log('mostrarMensajeError', e); }
    }

    // Inicializar Choices.js
    function initChoices() {
        const selectores = ['turno', 'subarea', 'despachador', 'recepcionista', 'verificador', 'producto'];
        
        // Reusar instancias globales si existen (creadas por despachosinternos.js)
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

        // Detectar overflow en el select de producto y permitir salto de línea si corresponde
        // (SIEMPRE configurar esto, sin importar quién creó las instancias)
        function updateProductoChoiceWrap() {
            try {
                console.log('[Edición] updateProductoChoiceWrap ejecutándose');
                const productoSelect = document.getElementById('producto');
                if (!productoSelect) {
                    console.log('[Edición] NO se encontró select#producto');
                    return;
                }

                // Buscar la instancia .choices que contiene el select#producto
                const allChoices = Array.from(document.querySelectorAll('.choices'));
                const choicesEl = allChoices.find(c => c.querySelector('select#producto')) || productoSelect.nextElementSibling;
                if (!choicesEl) {
                    console.log('[Edición] NO se encontró .choices para producto');
                    return;
                }

                const container = choicesEl.querySelector('.choices__inner');
                const single = choicesEl.querySelector('.choices__list--single');
                const item = single ? single.querySelector('.choices__item') : null;
                if (!container || !single || !item) {
                    console.log('[Edición] Faltan elementos:', {container: !!container, single: !!single, item: !!item});
                    return;
                }

                // Medidas reales (horizontal y vertical)
                const itemWidth = item.scrollWidth || item.offsetWidth || 0;
                const containerWidth = container.clientWidth || container.offsetWidth || 0;
                const itemHeight = item.scrollHeight || item.offsetHeight || 0;
                const containerHeight = container.clientHeight || container.offsetHeight || 0;

                console.log('[Edición] Medidas:', {
                    itemWidth, containerWidth,
                    itemHeight, containerHeight,
                    innerScroll: container.scrollHeight,
                    singleScroll: single.scrollHeight
                });

                // Consider horizontal overflow OR vertical overflow (line-wrap hidden)
                const needWrap = (itemWidth > containerWidth - 6) || (itemHeight > containerHeight - 4) || (single.scrollHeight > containerHeight - 4) || (container.scrollHeight > containerHeight);

                console.log('[Edición] needWrap?', needWrap);

                if (needWrap) {
                    try {
                        // Si ya aplicamos wrapping y la altura actual ya cubre el objetivo, evitar re-aplicar
                        const prevApplied = choicesEl.dataset && choicesEl.dataset.wrapApplied === '1';
                        const prevHeight = choicesEl.dataset && Number(choicesEl.dataset.wrapHeight) || 0;
                        const currH = container.clientHeight || container.offsetHeight || 0;
                        if (prevApplied && prevHeight && currH >= (prevHeight - 2)) {
                            // Ya aplicado y suficiente altura, salir
                            return;
                        }
                    } catch(e) { /* ignore */ }

                    // Forzar wrapping mediante estilos inline (may override Choices defaults)
                    choicesEl.classList.add('choices-wrap');
                    try {
                        // Primero forzar el ancho del item para que el wrap ocurra
                        const containerWidth = Math.max(80, container.offsetWidth - 40); // -40px para padding/caret
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

                        // Función que intenta aplicar la altura requerida y reintenta si es necesario
                        const applyHeight = (attempt = 1) => {
                            try {
                                const realHeight = Math.max(single.scrollHeight, item.scrollHeight, item.offsetHeight);
                                const targetHeight = Math.max(38, realHeight + 10);
                                // Marcar que ya aplicamos wrap y almacenar la altura objetivo
                                try { choicesEl.dataset.wrapApplied = '1'; choicesEl.dataset.wrapHeight = String(targetHeight); } catch(e){}
                                // Aplicar estilos al container y al contenedor choices para asegurar visual
                                try { container.style.setProperty('height', 'auto', 'important'); } catch(e){}
                                try { container.style.setProperty('min-height', targetHeight + 'px', 'important'); } catch(e){}
                                try { container.style.setProperty('max-height', 'none', 'important'); } catch(e){}
                                try { container.style.setProperty('overflow', 'visible', 'important'); } catch(e){}
                                try { container.style.setProperty('display', 'block', 'important'); } catch(e){}
                                try { container.style.setProperty('padding-top', '5px', 'important'); } catch(e){}
                                try { container.style.setProperty('padding-bottom', '5px', 'important'); } catch(e){}
                                // También aplicar en el elemento .choices (padre) por si el borde lo limita
                                try { choicesEl.style.setProperty('min-height', targetHeight + 'px', 'important'); } catch(e){}
                                try { choicesEl.style.setProperty('height', 'auto', 'important'); } catch(e){}

                                // Forzar repaint
                                void(container.offsetHeight);

                                // Si aún no se ajustó bien, reintentar hasta 3 veces con delays crecientes
                                const currentH = container.clientHeight || container.offsetHeight || 0;
                                if (currentH < targetHeight && attempt < 4) {
                                    setTimeout(() => applyHeight(attempt + 1), attempt * 120);
                                }
                            } catch (e) { /* ignore */ }
                        };

                        // Lanzar varios intentos con pequeños delays
                        setTimeout(() => applyHeight(1), 40);
                        setTimeout(() => applyHeight(2), 180);
                        setTimeout(() => applyHeight(3), 420);
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
                        // Limpiar marca de wrap aplicada
                        try { if (choicesEl.dataset) { delete choicesEl.dataset.wrapApplied; delete choicesEl.dataset.wrapHeight; } } catch(e){}
                    } catch (e) { /* ignore */ }
                }
            } catch (e) { console.warn('[Edición] updateProductoChoiceWrap error', e); }
        }

        // Exponer la función para poder invocarla desde otras partes
        window.updateProductoChoiceWrap = updateProductoChoiceWrap;

        // Escuchar cambios en el select subyacente
        const productoSelect = document.getElementById('producto');
        if (productoSelect) {
            productoSelect.addEventListener('change', () => {
                console.log('[Edición] producto change event');
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
        } catch (e) { console.warn('[Edición] Error al crear MutationObserver para producto', e); }
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
            productoSelect.addEventListener('change', function() {
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
            btnAgregar.addEventListener('click', function(e) {
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
            });
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
        const tbody = document.querySelector('#grillaDespacho tbody');
        if (!tbody) return;
        
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
            if (window.updateProductoChoiceWrap) window.updateProductoChoiceWrap();
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
        const btnAgregar = document.getElementById('btnAgregar');
        if (btnAgregar) {
            btnAgregar.textContent = 'Agregar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
        }
    }

    // Poblar controles con los datos de la fila seleccionada (modo edición)
    function cargarFilaEnControlesEdit(idx) {
        if (idx === null || typeof idx === 'undefined') return;
        const item = datosGrilla[idx];
        if (!item) return;

        // Seleccionar el producto en el select buscando por texto o código
        const productoSelect = document.getElementById('producto');
        if (productoSelect) {
            const opts = Array.from(productoSelect.options);
            // Preferir seleccionar por el id/valor del option si está guardado
            let opt = null;
            if (item.productoId) {
                opt = opts.find(o => (o.value || '') === (item.productoId || ''));
            }
            // Si no encontramos por id, intentar por código o texto como antes
            if (!opt) {
                opt = opts.find(o => (o.getAttribute('data-codigo') || '').trim() === (item.codigo || '').trim() || (o.textContent || '').trim() === (item.producto || '').trim());
            }
            if (opt) {
                productoSelect.value = opt.value;
                if (window.choicesInstances && window.choicesInstances['producto']) {
                    // Set choice and re-run wrap function several times with delays
                    setTimeout(()=> {
                        window.choicesInstances['producto'].setChoiceByValue(opt.value);
                        try { if (typeof window.updateProductoChoiceWrap === 'function') window.updateProductoChoiceWrap(); } catch(e){}
                    }, 30);
                    setTimeout(()=> { try { if (typeof window.updateProductoChoiceWrap === 'function') window.updateProductoChoiceWrap(); } catch(e){} }, 200);
                    setTimeout(()=> { try { if (typeof window.updateProductoChoiceWrap === 'function') window.updateProductoChoiceWrap(); } catch(e){} }, 600);
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
        const productoText = opcionProducto ? opcionProducto.text : (datosGrilla[idx].producto || '');
        const unidadMedida = opcionProducto ? (opcionProducto.getAttribute('data-unidadmedida') || '') : (datosGrilla[idx].unidadMedida || '');

        datosGrilla[idx] = {
            codigo: codigoInput?.value || '',
            producto: productoText,
            productoId: opcionProducto ? (opcionProducto.value || '') : (datosGrilla[idx].productoId || ''),
            unidadMedida: unidadMedida,
            cantidad: cantidadInput?.value || '',
            comentarios: comentariosInput?.value || ''
        };

        // Actualizar la grilla y resetear estado
        actualizarGrilla();
        limpiarCamposProducto();
        editIndex = null;
        updatePhase = null;
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
            // Flag para prevenir doble clic
            var _guardandoDespIntEditEnProceso = false;
            
            btnGuardar.addEventListener('click', function() {
                // FIX: Prevenir doble clic
                if (_guardandoDespIntEditEnProceso) {
                    console.log('[Edición] Guardado ya en proceso, ignorando clic');
                    return;
                }
                _guardandoDespIntEditEnProceso = true;
                
                // Deshabilitar botón inmediatamente
                const btnG = document.getElementById('btnGuardar');
                if (btnG) {
                    btnG.disabled = true;
                    btnG.textContent = 'Guardando...';
                }
                
                guardarDespacho();
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

    function guardarDespacho() {
        console.log('[Edición] Guardando despacho');
        
        // Función restaurar botón
        function restaurarBtnGuardarDespIntEdit() {
            _guardandoDespIntEditEnProceso = false;
            const btnG = document.getElementById('btnGuardar');
            if (btnG) {
                btnG.disabled = false;
                btnG.textContent = 'Guardar';
            }
        }
        
        // Validar campos obligatorios
        function getFieldValueLocal(id) {
            try {
                const el = document.getElementById(id);
                if (el && el.value) return el.value;
                if (window.choicesInstances && window.choicesInstances[id]) {
                    const ci = window.choicesInstances[id];
                    if (typeof ci.getValue === 'function') {
                        try { const v = ci.getValue(true); if (v) return v; } catch(e) {}
                        try { const arr = ci.getValue(); if (Array.isArray(arr) && arr.length) return arr[0].value || ''; } catch(e) {}
                    }
                }
            } catch(e) { console.debug('getFieldValueLocal error', id, e); }
            return '';
        }

        const fecha = getFieldValueLocal('fecha');
        const turno = getFieldValueLocal('turno');
        const subarea = getFieldValueLocal('subarea');
        const despachador = getFieldValueLocal('despachador');
        const recepcionista = getFieldValueLocal('recepcionista');
        const verificador = getFieldValueLocal('verificador');
        
        if (!fecha || !turno || !subarea || !despachador || !recepcionista || !verificador) {
            mostrarMensajeError('Debe completar todos los campos obligatorios');
            restaurarBtnGuardarDespIntEdit();
            return;
        }

        if (datosGrilla.length === 0) {
            mostrarMensajeError('Debe agregar al menos un producto');
            restaurarBtnGuardarDespIntEdit();
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
            despachador: despachador,
            recepcionista: recepcionista,
            verificador: verificador,
            productos: datosGrilla
        };
        
        console.log('[Edición] Datos a guardar:', datos);
        console.log('[Edición] JSON.stringify:', JSON.stringify(datos));
        
                // Sincronizar con window para depuración
                try { window.valeIdActual = valeIdActual; } catch(e) {}
                
                // Usar siempre el endpoint 'guardar' (el controlador reenviará a modificar si recibe 'Id')
        const url = window.APP_URL + '/despachosinternos/guardar';
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            console.log('[Edición] Respuesta del servidor:', data);
            if (data.success) {
                // Mostrar mensaje adecuado según si fue modificación o creación
                if (valeIdActual) {
                    mostrarMensajeError('Despacho modificado correctamente.');
                } else {
                    mostrarMensajeError('Despacho guardado correctamente.');
                }

                // Resetear flag y texto del botón
                _guardandoDespIntEditEnProceso = false;
                const _btnG = document.getElementById('btnGuardar');
                if (_btnG) _btnG.textContent = 'Guardar';

                // Actualizar ID del vale si el backend devuelve uno (caso creación)
                if (!valeIdActual && data.id) {
                    valeIdActual = data.id;
                }

                // Bloquear controles después de la operación
                bloquearControles();

                // Habilitar botón modificar
                const btnModificar = document.getElementById('btnModificar');
                if (btnModificar) btnModificar.disabled = false;

                // Deshabilitar botón guardar
                const btnGuardar = document.getElementById('btnGuardar');
                if (btnGuardar) btnGuardar.disabled = true;
            } else {
                // Error al guardar
                if (data.limite_alcanzado) {
                    mostrarMensajeError('Error: ' + (data.message || 'Este vale alcanzó el límite de modificaciones.'), 'error');
                    // Bloquear controles permanentemente
                    bloquearControles();
                    const btnGuardar = document.getElementById('btnGuardar');
                    const btnModificar = document.getElementById('btnModificar');
                    if (btnGuardar) btnGuardar.disabled = true;
                    if (btnModificar) btnModificar.disabled = true;
                } else {
                    mostrarMensajeError('Error al guardar: ' + (data.message || 'Error desconocido'), 'error');
                    restaurarBtnGuardarDespIntEdit();
                }
            }
        })
        .catch(error => {
            console.error('[Edición] Error:', error);
            mostrarMensajeError('Error de conexión al guardar el despacho');
            restaurarBtnGuardarDespIntEdit();
        });
    }

    function habilitarEdicion() {
        console.log('[Edición] Intentando habilitar edición (verificando límite de modificaciones)');

        // Debe existir un vale cargado
        if (!valeIdActual) {
            mostrarMensajeError('Primero cargue un vale antes de modificar');
            return;
        }

        // Consultar al backend si se puede modificar
        fetch(window.APP_URL + '/despachosinternos/verificarModificaciones', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ Id: valeIdActual })
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.hasOwnProperty('puede_modificar')) {
                mostrarMensajeError('Error al verificar límite de modificaciones', 'error');
                return;
            }

            if (!data.puede_modificar) {
                // Mostrar validación y mantener los controles deshabilitados
                mostrarMensajeError(data.mensaje || 'Este vale ya alcanzó el límite de 3 modificaciones', 'error');
                bloquearControles();
                const btnGuardar = document.getElementById('btnGuardar'); if (btnGuardar) btnGuardar.disabled = true;
                const btnModificar = document.getElementById('btnModificar'); if (btnModificar) btnModificar.disabled = true;
                return;
            }

            // Si puede modificar, habilitar controles salvo los explícamente protegidos
            console.log('[Edición] Límite de modificaciones OK, habilitando edición');
            // Incluir 'chkTurno' para permitir que el usuario active el modo manual de turno
            const permitir = ['fecha','subarea','despachador','recepcionista','verificador','producto','cantidad','comentarios','chkTurno'];
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
    }

    // Bloquear/desbloquear controles
    function bloquearControles() {
        const campos = ['fecha', 'turno', 'subarea', 'despachador', 'recepcionista', 'verificador', 'producto', 'cantidad', 'comentarios'];
        
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
        const campos = ['fecha', 'turno', 'subarea', 'despachador', 'recepcionista', 'verificador', 'producto', 'cantidad', 'comentarios'];
        
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

        const endpoints = [
            (window.APP_URL || window.BASE_URL) + '/despachosinternos/buscarVales?' + params.toString(),
            (window.BASE_URL || window.APP_URL) + '/index.php?url=despachosinternos/buscarVales&' + params.toString(),
            (window.APP_URL || window.BASE_URL) + '/index.php?url=despachosinternos/buscarVales&' + params.toString()
        ];

        function tryEndpoint(i) {
            if (i >= endpoints.length) {
                mostrarMensajeBusqueda('No se pudo contactar al servidor para buscar vales', 'danger');
                return Promise.resolve();
            }
            const url = endpoints[i];
            return fetch(url, { credentials: 'same-origin' })
                .then(response => response.text().then(text => ({ status: response.status, text })))
                .then(({ status, text }) => {
                    // Intentar parsear JSON; si falla, mostrar diagnóstico
                    try {
                        const data = JSON.parse(text);
                        if (data && data.success) {
                            mostrarResultadosVales(data.vales);
                            return Promise.resolve();
                        } else {
                            mostrarMensajeBusqueda('Error al buscar vales: ' + (data.error || 'Error desconocido'), 'danger');
                            return Promise.resolve();
                        }
                    } catch (e) {
                        console.error('[Edición] Respuesta no JSON al buscar vales (posible HTML):', { url, status, text: text.slice(0, 800) });
                        // Si el servidor devolvió HTML (login redir, 404, u otro), intentar siguiente endpoint
                        return tryEndpoint(i + 1);
                    }
                })
                .catch(err => {
                    console.warn('[Edición] Error en fetch buscarVales en', url, err);
                    return tryEndpoint(i + 1);
                });
        }

        tryEndpoint(0);
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
                <td><strong>VDI-${String(vale.NVale).padStart(6, '0')}</strong></td>
                <td>${formatearFecha(vale.Fecha)}</td>
                <td>${vale.Hora || ''}</td>
                <td>${vale.AreaNombre || ''}</td>
                <td>${vale.SubareaNombre || ''}</td>
                <td class="text-center actions">
                    <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                        <button class="btn btn-sm btn-primary me-1" onclick="window.cargarVale(${vale.Id})" title="Cargar vale">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="window.abrirModalAnular(${vale.Id}, 'VDI-${String(vale.NVale).padStart(6, '0')}')" title="Anular vale">
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

        const endpoints = [
            (window.APP_URL || window.BASE_URL) + '/despachosinternos/obtenerVale?id=' + valeId,
            (window.BASE_URL || window.APP_URL) + '/index.php?url=despachosinternos/obtenerVale&id=' + valeId
        ];

        function tryEndpoint(i) {
            if (i >= endpoints.length) {
                mostrarMensajeError('No se pudo cargar el vale (servicio no disponible)');
                return Promise.resolve();
            }
            const url = endpoints[i];
            return fetch(url, { credentials: 'same-origin' })
                .then(r => r.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        console.log('[Edición] Respuesta:', data);
                        if (data.success) {
                            poblarFormularioConVale(data.vale);
                            const modalEl = document.getElementById('modalBuscarVale');
                            if (modalEl) {
                                const modal = bootstrap.Modal.getInstance(modalEl);
                                if (modal) modal.hide();
                            }
                            return Promise.resolve();
                        } else {
                            mostrarMensajeError('Error al cargar vale: ' + (data.message || 'Error desconocido'));
                            return Promise.resolve();
                        }
                    } catch (e) {
                        console.error('[Edición] obtenerVale devolvió HTML/no-JSON para', url, text.slice(0,800));
                        return tryEndpoint(i + 1);
                    }
                })
                .catch(err => {
                    console.warn('[Edición] Error fetch obtenerVale en', url, err);
                    return tryEndpoint(i + 1);
                });
        }

        tryEndpoint(0);
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

        fetch(window.APP_URL + '/despachosinternos/anular', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ Id: valeId, motivo: motivo })
        })
        .then(r => r.json())
        .then(data => {
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
        
        // Obtener descripciones de productos ya en la grilla
        const descripcionesEnGrilla = new Set();
        datosGrilla.forEach(item => {
            if (item.producto) descripcionesEnGrilla.add(String(item.producto).trim());
        });
        
        // Destruir instancia Choices actual
        if (window.choicesInstances && window.choicesInstances['producto']) {
            try { window.choicesInstances['producto'].destroy(); } catch(e) {}
        }
        
        // Limpiar y reconstruir el select desde window.productosData
        productoSelect.innerHTML = '';
        var selectOption = document.createElement('option');
        selectOption.value = '';
        selectOption.text = 'Seleccione';
        productoSelect.appendChild(selectOption);
        
        var productosData = window.productosData || [];
        productosData.forEach(function(p) {
            var codigoOpt = p.Codigo !== undefined ? String(p.Codigo).trim() : (p.CodigoProducto ? String(p.CodigoProducto).trim() : (p.codigo ? String(p.codigo).trim() : ''));
            var fullText = (codigoOpt ? codigoOpt + ' - ' : '') + (p.Producto || p.DescripcionProducto || p.Nombre || '');
            
            // Saltar si ya está en la grilla (por descripción completa)
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
        
        // Crear nueva instancia Choices
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
        // Reaplicar wrapping
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
        window.auditoriaModoActual = 'despacho_interno';
        
        console.log('[Edición] valeIdActual asignado:', valeIdActual, 'typeof:', typeof valeIdActual);
        
        // FIX: Setear data-id en el span correlativoVale como respaldo de seguridad.
        // Esto asegura que si el listener de despachosinternos.js (main page) se
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
            correlativoEl.textContent = 'VDI-' + String(vale.NVale).padStart(6, '0');
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
        setFieldValue('despachador', vale.Despachador);
        setFieldValue('recepcionista', vale.Recepcionista);
        setFieldValue('verificador', vale.Verificador);
        
        // Cargar productos en la grilla
        datosGrilla = [];
        if (vale.productos && Array.isArray(vale.productos)) {
            vale.productos.forEach(prod => {
                datosGrilla.push({
                    codigo: prod.CodigoProducto || '',
                    producto: prod.DescripcionProducto || '',
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

    // Fallback: asegurar que el botón "Buscar Vale" abra el modal
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const btn = document.getElementById('btnBuscarVale');
            if (!btn) return;

            const fallbackHandler = function(e) {
                try { if (e && typeof e.preventDefault === 'function') e.preventDefault(); } catch(_){}
                if (typeof window.abrirModalBusqueda === 'function') {
                    window.abrirModalBusqueda();
                    return;
                }
                if (typeof window.abrirModalBusquedaInternos === 'function') {
                    window.abrirModalBusquedaInternos();
                    return;
                }
                const modalEl = document.getElementById('modalBuscarVale');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    try { const m = new bootstrap.Modal(modalEl); m.show(); } catch(e) { console.error('No se pudo mostrar modal fallback', e); }
                }
            };

            // Añadir sin eliminar handlers existentes
            btn.addEventListener('click', fallbackHandler);
            // También asignar onclick como respaldo
            try { btn.onclick = fallbackHandler; } catch(e) {}
        } catch (e) {
            console.error('Fallback btnBuscarVale error', e);
        }
    });

})();
