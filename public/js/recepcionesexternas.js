// recepcionesexternas.js - Vista previa en tiempo real para Recepciones Externas

(function() {
    'use strict';
    
    console.log('[RecepcionesExternas] Script cargado');
    
    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // --- Gestión de creación rápida de Series desde el header ---
    function setupAddSerieModal() {
        try {
            var btn = document.getElementById('btnAddSerieHeader');
            var modalEl = document.getElementById('modalAddSerie');
            if (!btn || !modalEl) return;

            var bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
            var input = document.getElementById('inputNuevaSerie');
            var inputCentro = document.getElementById('inputNuevaSerieCentroDistribucion');
            var feedback = document.getElementById('addSerieFeedback');
            var btnSave = document.getElementById('btnSaveNuevaSerie');

            btn.addEventListener('click', function() {
                if (input) { input.value = ''; input.classList.remove('is-invalid'); }
                if (inputCentro) { inputCentro.value = ''; inputCentro.classList.remove('is-invalid'); }
                if (feedback) { feedback.style.display = 'none'; feedback.textContent = ''; }
                bsModal.show();
            });

            // Autofocus al terminar la animación de apertura
            modalEl.addEventListener('shown.bs.modal', function() {
                if (input) { try { input.focus(); } catch(e) {} }
            });

            // Enter en el input guarda directamente
            if (input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        btnSave.click();
                    }
                });
            }

            if (inputCentro) {
                inputCentro.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        btnSave.click();
                    }
                });
            }

            btnSave.addEventListener('click', function() {
                var raw = input.value ? input.value.toString().trim() : '';
                var val = raw.toUpperCase();
                var centro = inputCentro ? inputCentro.value.toString().trim().toUpperCase() : '';

                // Valid format: 4 caracteres alfanumericos (ej: T086, 2056, EG07)
                var ok = /^[A-Z0-9]{4}$/.test(val);
                if (!ok) {
                    input.classList.add('is-invalid');
                    feedback.style.display = 'block';
                    feedback.textContent = 'Use 4 caracteres.';
                    return;
                }
                if (!centro) {
                    if (inputCentro) inputCentro.classList.add('is-invalid');
                    feedback.style.display = 'block';
                    feedback.textContent = 'Centro de Distribución es obligatorio.';
                    return;
                }
                // Reflejar uppercase en input
                try { input.value = val; } catch(e) {}
                if (inputCentro) try { inputCentro.value = centro; } catch(e) {}

                // Deshabilitar mientras se guarda
                btnSave.disabled = true;
                feedback.style.display = 'none';

                // Enviar al endpoint para insertar
                fetch(window.BASE_URL + '/api/series_create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ serie: val, centroDistribucion: centro })
                }).then(function(resp) { return resp.json(); }).then(function(data) {
                    btnSave.disabled = false;
                    if (data && data.success) {
                        // Actualizar window.seriesData y todos los selects en la grilla
                        window.seriesData = window.seriesData || [];
                        window.seriesData.push({ Id: data.id, Serie: data.serie, CentroDistribucion: centro });
                        actualizarSelectsSeries(data.id, data.serie);
                        bsModal.hide();
                    } else {
                        feedback.style.display = 'block';
                        feedback.textContent = data && data.message ? data.message : 'Error al guardar';
                    }
                }).catch(function(err){
                    btnSave.disabled = false;
                    feedback.style.display = 'block';
                    feedback.textContent = 'Error de red al guardar';
                });
            });
        } catch (e) { console.warn('setupAddSerieModal error', e); }
    }

    function actualizarSelectsSeries(id, serie) {
        try {
            var opts = document.createElement('option');
            opts.value = serie;
            opts.text = serie;

            // Añadir a cada select existente
            document.querySelectorAll('.select-serie-guia').forEach(function(sel) {
                try {
                    var optionExists = Array.from(sel.options).some(function(o){ return (o.value||'').toString().toUpperCase() === serie.toString().toUpperCase(); });
                    if (!optionExists) {
                        var newOpt = opts.cloneNode(true);
                        sel.appendChild(newOpt);
                    }
                } catch(e){}
            });

            // Si hay Choices.js y están inicializados, actualizar la instancia (si corresponde)
            if (window.choicesInstances) {
                Object.keys(window.choicesInstances).forEach(function(key){
                    try {
                        var inst = window.choicesInstances[key];
                        if (!inst) return;
                        var el = inst.passedElement || inst._currentState || inst.input || inst._input;
                        if (!el || !el.classList) return;
                        if (el.classList && el.classList.contains('select-serie-guia')) {
                            inst.setChoices([{ value: serie, label: serie }], 'value', 'label', false);
                        }
                    } catch(e){}
                });
            }
        } catch (e) { console.warn('actualizarSelectsSeries error', e); }
    }
    
    // Obtener el número de guía combinado (serie-correlativo) desde una fila
    function obtenerNumeroGuiaDesdeFila(fila) {
        if (!fila) return '';
        const serieEl = fila.querySelector('.select-serie-guia') || fila.querySelector('.input-serie-guia');
        const serie = serieEl ? serieEl.value.trim() : '';
        const correl = fila.querySelector('.input-correlativo-guia') ? fila.querySelector('.input-correlativo-guia').value.trim() : '';
        if (serie && correl) return serie + '-' + correl;
        return (serie || correl || '').toString();
    }

    function init() {
        console.log('[RecepcionesExternas] Inicializando...');
        console.log('[RecepcionesExternas] Versión con debugging activado');
        
        // Agregar estilos CSS para controlar z-index de selects en la grilla
        agregarEstilosGrilla();
        
        // Inicializar Choices.js en todos los selects
        initializeChoices();
        
        // Configurar turno automático
        setupTurnoAutomatico();
        
        // Configurar dependencias entre selects
        setupSelectDependencies();
        
        // Configurar listeners para actualización en tiempo real
        setupRealtimePreview();
        
        // Actualizar hora en tiempo real
        setupRealtimeClock();
        // Forzar que el campo hora quede permanentemente deshabilitado
        enforceHoraDisabled();
        
        // Cargar datos iniciales (productos y observaciones)
        cargarDatosIniciales();
        
        // Cargar siguiente correlativo
        cargarSiguienteCorrelativo();
        
        // Renderizar vista previa inicial
        actualizarVistaPrevia();
        
        // Forzar actualización después de que Choices.js termine de inicializar
        setTimeout(function() {
            actualizarVistaPrevia();
            // Configurar gestión de guías DESPUÉS de que el preview esté listo
            setupGuiasManager();
            // Inicializar estado del formulario (todo bloqueado)
            try { setupFormMode(); } catch (e) {}
            // Configurar botón de colapsar datos
            setupToggleDatos();
            // Forzar mayúsculas en textarea de comentarios mientras escribes
            try { setupComentariosUppercase(); } catch(e) {}
            // Inicializar creación rápida de series
            try { setupAddSerieModal(); } catch(e) {}
            // Adjuntar listeners de correlativo existentes (pad a 7) en caso ya haya filas
            try { attachCorrelativoListeners(); } catch(e) {}
            // DESACTIVADO: z-index forzado por observer (causa conflictos)
            // setupDropdownZIndexForcer();
        }, 500);
    }

    // Attach blur listeners to all existing correlativo inputs to pad to 7 digits
    function attachCorrelativoListeners() {
        try {
            document.querySelectorAll('.input-correlativo-guia').forEach(function(input) {
                // avoid duplicate listeners by removing previous ones (best-effort)
                try { input.removeEventListener && input.removeEventListener('blur', null); } catch(e) {}
                input.addEventListener('blur', function() {
                    try {
                        var v = (this.value || '').toString().trim();
                        var digits = v.replace(/\D/g, '');
                        if (!digits) return;
                        if (digits.length < 7) this.value = digits.padStart(7, '0');
                    } catch(e) {}
                });
            });
        } catch(e) { console.warn('attachCorrelativoListeners error', e); }
    }

    // Forzar mayúsculas en textarea comentarios en tiempo real
    function setupComentariosUppercase() {
        const ta = document.getElementById('comentarios');
        if (!ta) return;
        ta.addEventListener('input', function(e) {
            try {
                const start = ta.selectionStart;
                const end = ta.selectionEnd;
                const upper = ta.value.toUpperCase();
                if (ta.value !== upper) {
                    ta.value = upper;
                    // Restaurar posición del cursor
                    ta.setSelectionRange(start, end);
                }
            } catch (err) {}
        });
    }

    // Inyecta estilos necesarios para normalizar dropdowns y apariencia de campos
    function agregarEstilosGrilla() {
        try {
            if (document.getElementById('recep-ext-styles')) return;
            var css = '\n'
                + '.choices { position: relative !important; overflow: visible !important; }\n'
                + '.choices__inner { overflow: visible !important; }\n'
                + '.choices__list--dropdown { position: absolute !important; z-index: 9999 !important; max-height: 300px !important; overflow: auto !important; pointer-events: auto !important; }\n'
                + '.choices__list--dropdown .choices__item { pointer-events: auto !important; cursor: pointer !important; }\n'
                + '.choices__input--cloned { pointer-events: auto !important; }\n'
                + '.turno-wrap, .origen-container { position: relative !important; z-index: 100 !important; }\n'
                + '#hora[disabled] { background:#f3f4f6 !important; color:#6b7280 !important; pointer-events:none !important; }\n'
                + '.custom-dropdown { white-space:nowrap; }\n'
                + '#contenedorGrillaGuias { max-height: 500px !important; overflow-y: auto !important; }\n';

            var style = document.createElement('style');
            style.id = 'recep-ext-styles';
            style.type = 'text/css';
            style.appendChild(document.createTextNode(css));
            document.head.appendChild(style);
            console.log('[RecepcionesExternas] estilos inyectados');
        } catch (e) {
            console.warn('[RecepcionesExternas] agregarEstilosGrilla error', e);
        }
    }

    function setupDropdownZIndexForcer() {
        var FORCE_USE_CSS_FIXED = true;

        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                var added = m.addedNodes;
                added = Array.prototype.slice.call(added || []);
                added.forEach(function(n) {
                    try {
                        if (n && n.classList && n.classList.contains && (n.classList.contains('choices__list--dropdown') || n.classList.contains('choices__list'))) {
                            handleDropdownNode(n);
                        } else if (n && n.querySelectorAll) {
                            var q = n.querySelectorAll('.choices__list--dropdown, .choices__list');
                            Array.prototype.slice.call(q).forEach(function(dd){ handleDropdownNode(dd); });
                        }
                    } catch(e){}
                });
            });
        });

        // Observar todo el documento
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // También revisar dropdowns existentes cada vez que un select recibe foco
        document.addEventListener('focusin', function(e) {
            if (e.target.classList && e.target.classList.contains('custom-dropdown')) {
                setTimeout(function() {
                    var allDropdowns = document.querySelectorAll('.choices__list--dropdown');
                    allDropdowns.forEach(function(dd) {
                        // No tocar dropdowns que ya movimos al body
                        if (dd.getAttribute && dd.getAttribute('data-moved-to-body') === '1') return;
                        dd.style.zIndex = '999999';
                        dd.style.position = 'absolute';
                    });
                }, 100);
            }
        }, true);

        // Handler que mueve/eleva un dropdown node al body si procede
        function handleDropdownNode(dropdown) {
            if (!dropdown || dropdown.getAttribute && dropdown.getAttribute('data-moved-to-body') === '1') return;

            // Encontrar el contenedor .choices asociado
            var container = dropdown.closest && dropdown.closest('.choices');
            if (!container) {
                // Si no hay container, aplicar z-index alto por si acaso
                dropdown.style.zIndex = '999999';
                return;
            }

            // Posicionar y mover al body (o aplicar CSS-only si está activado)
            try {
                var rect = container.getBoundingClientRect();

                // Si estamos usando el modo CSS-only, no movemos el nodo: aplicamos estilos fixed in-place
                if (FORCE_USE_CSS_FIXED) {
                    dropdown.setAttribute('data-fixed-handled', '1');
                    dropdown.setAttribute('data-associated-left', String(Math.round(rect.left)));
                    dropdown.style.position = 'fixed';
                    dropdown.style.left = rect.left + 'px';
                    dropdown.style.top = rect.bottom + 'px';
                    dropdown.style.width = rect.width + 'px';
                    dropdown.style.zIndex = '2147483646';
                    dropdown.style.maxHeight = (window.innerHeight - rect.bottom - 10) + 'px';
                    dropdown.style.overflow = 'auto';
                    dropdown.style.pointerEvents = 'auto';
                    console.log('[Dropdown Forcer] (CSS-only) estilos aplicados al dropdown para', container);
                } else {
                    // Guardar metadatos en el dropdown para restauración
                    dropdown.setAttribute('data-moved-to-body', '1');
                    dropdown.setAttribute('data-associated-left', String(Math.round(rect.left)));

                    // Aplicar estilos fixed para evitar stacking context de ancestros
                    dropdown.style.position = 'fixed';
                    dropdown.style.left = rect.left + 'px';
                    dropdown.style.top = rect.bottom + 'px';
                    dropdown.style.width = rect.width + 'px';
                    dropdown.style.zIndex = '2147483646';
                    dropdown.style.maxHeight = (window.innerHeight - rect.bottom - 10) + 'px';
                    dropdown.style.overflow = 'auto';

                    // Mover al body
                    document.body.appendChild(dropdown);
                    console.log('[Dropdown Forcer] Moved dropdown node to body', dropdown, 'for container', container);
                }
            } catch (e) {
                console.warn('[Dropdown Forcer] Error handling dropdown node', e);
            }
        }
    }
    
    // Configurar botón para colapsar/expandir sección de datos
    function setupToggleDatos() {
        const btnToggle = document.getElementById('btnToggleDatos');
        const headerDatos = document.getElementById('headerDatosRecepcion');
        const seccionDatos = document.getElementById('seccionDatosRecepcion');
        const iconToggle = document.getElementById('iconToggleDatos');
        
        if (!btnToggle || !seccionDatos || !iconToggle) return;
        
        // Por defecto la sección está expandida
        let isCollapsed = false;
        
        const toggleDatos = function(e) {
            e.stopPropagation();
            isCollapsed = !isCollapsed;
            
            if (isCollapsed) {
                seccionDatos.style.display = 'none';
                seccionDatos.classList.add('collapsed');
                iconToggle.className = 'bi bi-chevron-down';
            } else {
                seccionDatos.style.display = 'block';
                seccionDatos.classList.remove('collapsed');
                iconToggle.className = 'bi bi-chevron-up';
            }
        };
        
        btnToggle.addEventListener('click', toggleDatos);
        headerDatos.addEventListener('click', toggleDatos);
    }

    // Notificación flotante (overlay) reutilizable
    function showFloatingMessage(message, level) {
        try {
            // Si existe la versión de bootstrap/inline de mensajes, usarla para mantener diseño consistente
            try {
                if (typeof window !== 'undefined' && typeof window.mostrarMensajeBusqueda === 'function') {
                    // mapear niveles: 'error' -> 'danger'
                    const tipo = (level === 'error') ? 'danger' : (level || 'warning');
                    window.mostrarMensajeBusqueda(message, tipo);
                    return;
                }
            } catch (e) {}
            level = level || 'warning'; // 'success' | 'warning' | 'error' | 'info'
            // Evitar creación múltiple inmediata
            const containerId = 'floatingMsgContainer';
            let container = document.getElementById(containerId);
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

            const msg = document.createElement('div');
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
            msg.style.background = (level === 'success' ? '#d1fae5' : (level === 'error' ? '#fee2e2' : '#fff7ed'));
            msg.style.border = '1px solid rgba(0,0,0,0.06)';
            msg.style.display = 'flex';
            msg.style.alignItems = 'center';
            msg.style.gap = '10px';

            // Icon
            const icon = document.createElement('span');
            icon.style.flex = '0 0 auto';
            if (level === 'success') icon.textContent = '✔';
            else if (level === 'error') icon.textContent = '✖';
            else icon.textContent = '⚠';
            icon.style.fontSize = '1.05rem';

            const text = document.createElement('div');
            text.style.flex = '1 1 auto';
            text.innerText = message;

            msg.appendChild(icon);
            msg.appendChild(text);

            // Close button
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerText = 'Cerrar';
            btn.style.background = 'transparent';
            btn.style.border = 'none';
            btn.style.cursor = 'pointer';
            btn.style.color = '#374151';
            btn.style.fontWeight = '600';
            btn.addEventListener('click', function() {
                try { msg.remove(); } catch(e){}
            });
            btn.style.marginLeft = '8px';
            msg.appendChild(btn);

            container.appendChild(msg);

            // Auto dismiss
            setTimeout(function() {
                try { msg.remove(); } catch(e){}
            }, 4000);
        } catch (e) {
            console.warn('[RecepcionesExternas] showFloatingMessage error', e);
        }
    }

    // Sobrescribir window.alert para usar la notificación flotante
    try {
        if (typeof window !== 'undefined') {
            window._nativeAlert = window.alert;
            window.alert = function(msg) { showFloatingMessage(msg, 'warning'); };
            window.showFloatingMessage = showFloatingMessage;
        }
    } catch (e) {}
    
    // Inicializar Choices.js
    function initializeChoices() {
        window.choicesInstances = window.choicesInstances || {};
        
        document.querySelectorAll('.custom-dropdown').forEach(function(select) {
            try {
                if (window.choicesInstances[select.id]) {
                    return; // Ya inicializado
                }
                
                window.choicesInstances[select.id] = new Choices(select, {
                    searchEnabled: true,
                    searchChoices: true,
                    shouldSort: false,
                    itemSelectText: '',
                    allowHTML: false,
                    renderChoiceLimit: -1,
                    searchResultLimit: 100,
                    position: 'bottom',
                    appendTo: document.body,
                    placeholder: true,
                    placeholderValue: (select.options && select.options[0] && select.options[0].text) ? select.options[0].text : 'Seleccione',
                    noResultsText: 'No se encontraron resultados',
                    removeItemButton: false,
                    duplicateItemsAllowed: false
                });
                // Ocultar el select nativo para evitar que el dropdown nativo del navegador
                // se muestre simultáneamente con el dropdown de Choices (provoca doble scrollbar).
                try {
                    select.setAttribute('data-choices-hidden', '1');
                    select.style.setProperty('display', 'none', 'important');
                    select.style.setProperty('visibility', 'hidden', 'important');
                    select.style.setProperty('height', '0px', 'important');
                    select.style.setProperty('min-height', '0px', 'important');
                    select.style.setProperty('overflow', 'hidden', 'important');
                    select.style.setProperty('position', 'absolute', 'important');
                    select.style.setProperty('left', '-9999px', 'important');
                    select.style.setProperty('top', '-9999px', 'important');
                } catch (e) {}
                
                // Mostrar input de búsqueda al abrir
                select.addEventListener('showDropdown', function() {
                    setTimeout(function() {
                        var input = select.parentElement.querySelector('.choices__input');
                        if (input) {
                            input.style.display = 'block';
                            input.placeholder = 'Buscar...';
                            input.focus();
                        }
                    }, 50);
                });

                select.addEventListener('hideDropdown', function() {
                    // No hacer nada: dejar que el CSS global maneje todo
                });
            } catch (e) {
                console.warn('[RecepcionesExternas] Error al inicializar Choices en', select.id, e);
            }
        });
    }

    // Solución adicional: para el select `turno`, ocultar temporalmente visualmente (visibility:hidden)
    // los contenedores de `empresa` y `chofer` mientras el dropdown esté abierto. Esto evita
    // stacking-contexts problemáticos en Chrome que dejan el dropdown por debajo de esos selects.
    function setupTurnoDropdownVisibilityHack() {
        try {
            setTimeout(function(){
                var turnoInst = window.choicesInstances && window.choicesInstances['turno'];
                var empresaCol = document.getElementById('empresa')?.closest('.col-md-7, .col-md-6, .col-md-5');
                var choferCol  = document.getElementById('chofer')?.closest('.col-md-7, .col-md-6, .col-md-5');
                if (!turnoInst) return;

                turnoInst.passedElement.element.addEventListener('showDropdown', function(){
                    if (empresaCol) empresaCol.style.visibility = 'hidden';
                    if (choferCol)  choferCol.style.visibility = 'hidden';
                });

                turnoInst.passedElement.element.addEventListener('hideDropdown', function(){
                    if (empresaCol) empresaCol.style.visibility = '';
                    if (choferCol)  choferCol.style.visibility = '';
                });
            }, 300);
        } catch (e) {
            console.warn('[RecepcionesExternas] setupTurnoDropdownVisibilityHack error', e);
        }
    }

    // --- Helpers para elevar dropdowns de Choices ---
    function moveChoicesDropdownToBody(select) {
        // Choices.js envuelve el select dentro de un contenedor .choices
        // Buscar el contenedor .choices más cercano hacia arriba
        var container = select.closest('.choices');
        
        if (!container) {
            console.warn('[moveChoicesDropdownToBody] No se encontró contenedor .choices para:', select.id);
            console.log('[moveChoicesDropdownToBody] Select:', select);
            return;
        }
        
        console.log('[moveChoicesDropdownToBody] Contenedor .choices encontrado:', container);
        
        // Intentar encontrar el dropdown con múltiples estrategias y pequeños reintentos
        function _findDropdownOnce() {
            var dd = container.querySelector('.choices__list--dropdown') || 
                     container.querySelector('.choices__list[aria-expanded="true"]') ||
                     container.querySelector('.choices__list--dropdown.is-active');

            if (dd) return dd;

            // Buscar en el documento: candidatos que estén activos o desplegables
            var candidates = Array.prototype.slice.call(document.querySelectorAll('.choices__list--dropdown, .choices__list'));
            for (var i = 0; i < candidates.length; i++) {
                var c = candidates[i];
                // Omitir nodos que ya movimos
                if (c.getAttribute && c.getAttribute('data-moved-to-body') === '1') continue;
                // Preferir los que están marcados como is-active o aria-expanded=true
                if (c.classList.contains('is-active') || c.getAttribute('aria-expanded') === 'true') {
                    // comprobar si su contenedor .choices coincide con nuestro container
                    var cContainer = c.closest && c.closest('.choices');
                    if (cContainer === container) return c;
                }
            }

            // Si aún no encontramos, probar por proximidad geométrica: el dropdown cuyo rect esté justo debajo del select
            var selectRect = container.getBoundingClientRect();
            for (var j = 0; j < candidates.length; j++) {
                var cand = candidates[j];
                try {
                    var r = cand.getBoundingClientRect();
                    // Si el centro horizontal está aproximadamente alineado y el top está debajo del select
                    var cx = (r.left + r.right) / 2;
                    if (Math.abs(cx - (selectRect.left + selectRect.width/2)) < Math.max(40, selectRect.width/2)) {
                        if (r.top >= (selectRect.bottom - 4) && r.height > 0) return cand;
                    }
                } catch(e) { continue; }
            }

            return null;
        }

        var dropdown = null;
        var attempts = 0;
        while (!dropdown && attempts < 5) {
            dropdown = _findDropdownOnce();
            if (dropdown) break;
            // esperar un poco antes de reintentar
            var wait = 30 * (attempts + 1);
            var start = Date.now();
            // busy-wait with short timeout to allow DOM updates (can't use async here easily within same function flow)
            while (Date.now() - start < wait) {}
            attempts++;
        }

        if (!dropdown) {
            console.error('[moveChoicesDropdownToBody] No se pudo encontrar el dropdown después de múltiples intentos para:', select.id);
            return;
        }

        console.log('=== DROPDOWN ABIERTO ===', select.id, dropdown);

        // Intento robusto: mover el dropdown al body y posicionarlo con fixed
        try {
            window._movedChoices = window._movedChoices || {};

            // Evitar moverlo dos veces
            if (!window._movedChoices[select.id]) {
                var rect = container.getBoundingClientRect();

                // Guardar referencias para restaurar luego
                window._movedChoices[select.id] = {
                    dropdown: dropdown,
                    originalParent: dropdown.parentNode,
                    originalNextSibling: dropdown.nextSibling
                };

                // Ajustar estilos para que se muestre por encima de todo
                dropdown.style.position = 'fixed';
                dropdown.style.left = rect.left + 'px';
                dropdown.style.top = rect.bottom + 'px';
                dropdown.style.width = rect.width + 'px';
                dropdown.style.zIndex = '9999999';
                dropdown.style.maxHeight = (window.innerHeight - rect.bottom - 10) + 'px';
                dropdown.style.overflow = 'auto';

                // Añadir clase para identificar que fue movido
                dropdown.setAttribute('data-moved-to-body', '1');
                dropdown.setAttribute('data-associated-select-id', select.id);
                dropdown.setAttribute('data-associated-left', String(Math.round(rect.left)));

                // Forzar overflow visible en contenedores superiores
                var contenedorGrilla = document.getElementById('contenedorGrillaGuias');
                if (contenedorGrilla) contenedorGrilla.style.overflow = 'visible';

                // Normalizar ancestros problemáticos (transform, z-index, overflow) añadiendo una clase temporal
                try {
                    window._normalizedAncestors = window._normalizedAncestors || [];
                    var anc = container.parentElement;
                    while (anc && anc !== document.body) {
                        try {
                            var cs = window.getComputedStyle(anc);
                            if ((cs.transform && cs.transform !== 'none') || (cs.zIndex && cs.zIndex !== 'auto') || cs.filter && cs.filter !== 'none' || cs.overflow && (cs.overflow === 'hidden' || cs.overflow === 'auto')) {
                                if (window._normalizedAncestors.indexOf(anc) === -1) {
                                    window._normalizedAncestors.push(anc);
                                    anc.classList.add('choices-normalize-anc');
                                }
                            }
                        } catch(e){}
                        anc = anc.parentElement;
                    }
                } catch(e) { console.warn('[moveChoicesDropdownToBody] no se pudo normalizar ancestros', e); }

                // Asegurar que los ancestros clave reciban z-index (incluir .turno-wrap y .origen-container)
                var ancestorsToMark = [
                    select.closest('.turno-wrap'),
                    select.closest('.origen-container'),
                    select.closest('.col-auto'),
                    select.closest('.col-md-6'),
                    select.closest('.col-md-7'),
                    select.closest('.col-md-5'),
                    select.closest('.col-12'),
                    select.closest('.row')
                ];

                ancestorsToMark.forEach(function(el) {
                    if (el && !el.classList.contains('select-active')) {
                        el.classList.add('select-active');
                        try { el.style.position = 'relative'; el.style.zIndex = '999997'; } catch(e){}
                    }
                });

                // Mover el nodo al body
                document.body.appendChild(dropdown);
                            console.log('[moveChoicesDropdownToBody] Dropdown movido al body para', select.id);

                // Diagnóstico: comprobar qué elemento está por encima del dropdown en su centro
                setTimeout(function() {
                    try {
                        var ddRect = dropdown.getBoundingClientRect();
                        var cx = Math.round(ddRect.left + ddRect.width/2);
                        var cy = Math.round(ddRect.top + Math.min(12, ddRect.height/2));
                        var topEl = document.elementFromPoint(cx, cy);
                        console.log('[moveChoicesDropdownToBody] Elemento en el punto central:', topEl, 'zIndex:', topEl && window.getComputedStyle(topEl).zIndex);
                        if (topEl && topEl !== dropdown && !dropdown.contains(topEl)) {
                            // Forzar z-index extremo y asegurar visibilidad
                            dropdown.style.zIndex = '2147483647';
                            dropdown.style.pointerEvents = 'auto';
                            dropdown.style.background = window.getComputedStyle(dropdown).backgroundColor || '#fff';
                            console.log('[moveChoicesDropdownToBody] Forzado z-index extremo por solapamiento detectado', topEl);
                        }
                    } catch(e) { console.warn('[moveChoicesDropdownToBody] diagnóstico fallo', e); }
                }, 60);

                // Forzar re-apertura si el dropdown fue cerrado por el movimiento
                try {
                    if (window.choicesInstances && window.choicesInstances[select.id] && typeof window.choicesInstances[select.id].showDropdown === 'function') {
                        setTimeout(function() {
                            try { window.choicesInstances[select.id].showDropdown(); } catch(e){}
                        }, 25);
                    }
                } catch(e) {}
            } else {
                // Ya fue movido: recalcular posición por si hay scroll/resize
                var rect2 = container.getBoundingClientRect();
                dropdown.style.left = rect2.left + 'px';
                dropdown.style.top = rect2.bottom + 'px';
                dropdown.style.width = rect2.width + 'px';
            }
        } catch (e) {
            console.error('[moveChoicesDropdownToBody] Error moviendo dropdown:', e);
            // Fallback: intentar aplicar z-index en sitio
            container.style.position = 'relative';
            container.style.zIndex = '999998';
            dropdown.style.zIndex = '999999';
            dropdown.style.position = 'absolute';
        }
    }

    function restoreChoicesDropdown(select) {
        // Remover marca de body y restaurar overflow; si movimos el dropdown, devolverlo a su padre
        setTimeout(function() {
            document.body.classList.remove('select-open');

            var contenedorGrilla = document.getElementById('contenedorGrillaGuias');
            if (contenedorGrilla) {
                contenedorGrilla.style.overflow = 'auto';
            }

            try {
                // Restaurar cualquier dropdown movido al body que corresponda al select actual
                var container = select.closest && select.closest('.choices');
                var containerRect = container ? container.getBoundingClientRect() : null;
                var movedNodes = document.querySelectorAll('[data-moved-to-body="1"], [data-fixed-handled="1"]');
                movedNodes.forEach(function(dd) {
                    try {
                        var assocId = dd.getAttribute('data-associated-select-id');
                        var assocLeft = parseInt(dd.getAttribute('data-associated-left') || '0', 10);
                        var shouldRestore = false;

                        if (assocId && assocId === select.id) shouldRestore = true;
                        else if (containerRect && Math.abs(assocLeft - Math.round(containerRect.left)) <= 10) shouldRestore = true;

                        if (shouldRestore && container) {
                            // Si fue movido al body físicamente, restaurar la ubicación
                            if (dd.getAttribute('data-moved-to-body') === '1') {
                                container.appendChild(dd);
                                dd.removeAttribute('data-moved-to-body');
                            }
                            // Si fue tratado en modo CSS-only, solo limpiar marcas y estilos
                            if (dd.getAttribute('data-fixed-handled') === '1') {
                                dd.removeAttribute('data-fixed-handled');
                            }

                            dd.removeAttribute('data-associated-left');
                            dd.removeAttribute('data-associated-select-id');
                            dd.style.position = '';
                            dd.style.left = '';
                            dd.style.top = '';
                            dd.style.width = '';
                            dd.style.zIndex = '';
                            dd.style.maxHeight = '';
                            dd.style.overflow = '';
                            dd.style.pointerEvents = '';
                            console.log('[restoreChoicesDropdown] Dropdown restaurado o limpiado para select cercano a', select.id);
                        }
                    } catch(e) { console.warn('[restoreChoicesDropdown] error restaurando dd', e); }
                });
            } catch (e) {
                console.warn('[restoreChoicesDropdown] Error restaurando dropdown:', e);
            }

            // Limpiar estilos de ancestros relevantes
            var ancestorsToClear = [
                select.closest('.turno-wrap'),
                select.closest('.origen-container'),
                select.closest('.col-auto'),
                select.closest('.col-md-6'),
                select.closest('.col-md-7'),
                select.closest('.col-md-5'),
                select.closest('.col-12'),
                select.closest('.row')
            ];
            ancestorsToClear.forEach(function(el) {
                if (el && el.classList.contains('select-active')) {
                    el.classList.remove('select-active');
                    try { el.style.position = ''; el.style.zIndex = ''; } catch(e){}
                }
            });

            var container = select.closest('.choices');
            if (container) {
                container.style.position = '';
                container.style.zIndex = '';
            }

            var form = select.closest('form');
            if (form) { form.style.position = ''; form.style.zIndex = ''; }

            var seccionDatos = document.getElementById('seccionDatosRecepcion');
            if (seccionDatos) { seccionDatos.style.position = ''; seccionDatos.style.zIndex = ''; }
            // Restaurar visibilidad de elementos ocultos temporalmente
            try {
                if (window._temporarilyHidden && window._temporarilyHidden.length) {
                    window._temporarilyHidden.forEach(function(el) {
                        try { if (el) el.style.visibility = ''; } catch(e){}
                    });
                    window._temporarilyHidden = [];
                }
            } catch(e) { console.warn('[restoreChoicesDropdown] no se pudo restaurar visibilidad', e); }

            // Restaurar ancestros normalizados si los hay
            try {
                if (window._normalizedAncestors && window._normalizedAncestors.length) {
                    window._normalizedAncestors.forEach(function(el) {
                        try { el.classList.remove('choices-normalize-anc'); } catch(e){}
                    });
                    window._normalizedAncestors = [];
                }
            } catch(e) { console.warn('[restoreChoicesDropdown] no se pudo restaurar ancestros normalizados', e); }

        }, 160);
    }

    function repositionPortal(select) {
        // No necesario
    }
    
    // Configurar turno automático
    function setupTurnoAutomatico() {
        const turnoSelect = document.getElementById('turno');
        const chkTurnoManual = document.getElementById('chkTurno');
        const turnos = window.turnosData || [];
        const horaActual = window.horaActual || new Date().toTimeString().slice(0, 5);
        
        if (!turnoSelect || !turnos.length) return;
        
        function parseHora(horaStr) {
            const partes = horaStr.split(':');
            return { h: parseInt(partes[0], 10), m: parseInt(partes[1], 10) };
        }
        
        function horaEnMinutos(horaStr) {
            const h = parseHora(horaStr);
            return h.h * 60 + h.m;
        }
        
        function setTurnoByHora() {
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
            
            if (window.choicesInstances && window.choicesInstances['turno']) {
                try {
                    window.choicesInstances['turno'].setChoiceByValue(turnoSelect.value);
                } catch (e) {}
            }
            
            return found;
        }
        
        // Aplicar turno automático al cargar
        setTimeout(function() {
            setTurnoByHora();
            // Aplicar fecha efectiva para turno nocturno
            try{ if(typeof window.aplicarFechaEfectivaTurno === 'function') window.aplicarFechaEfectivaTurno(false); }catch(e){}
            turnoSelect.setAttribute('disabled', 'disabled');
            if (window.choicesInstances && window.choicesInstances['turno']) {
                window.choicesInstances['turno'].disable();
            }
        }, 200);
        
        // Checkbox para modo manual
        if (chkTurnoManual) {
            chkTurnoManual.addEventListener('change', function() {
                if (this.checked) {
                    turnoSelect.removeAttribute('disabled');
                    if (window.choicesInstances && window.choicesInstances['turno']) {
                        window.choicesInstances['turno'].enable();
                    }
                } else {
                    turnoSelect.setAttribute('disabled', 'disabled');
                    if (window.choicesInstances && window.choicesInstances['turno']) {
                        window.choicesInstances['turno'].disable();
                    }
                    setTurnoByHora();
                    // Aplicar fecha efectiva para turno nocturno
                    try{ if(typeof window.aplicarFechaEfectivaTurno === 'function') window.aplicarFechaEfectivaTurno(false); }catch(e){}
                    actualizarVistaPrevia();
                }
            });
        }
    }
    
    // Configurar dependencias entre selects (Empresa<->RUC, Chofer<->Brevete)
    function setupSelectDependencies() {
        const empresaSelect = document.getElementById('empresa');
        const rucSelect = document.getElementById('ruc');
        const choferSelect = document.getElementById('chofer');
        const breveteSelect = document.getElementById('brevete');
        
        // Sincronizar Empresa -> RUC
        if (empresaSelect && rucSelect) {
            empresaSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const rucValue = selectedOption.getAttribute('data-ruc');
                
                if (rucValue) {
                    // Buscar y seleccionar el RUC correspondiente
                    for (let i = 0; i < rucSelect.options.length; i++) {
                        if (rucSelect.options[i].text === rucValue) {
                            rucSelect.value = rucSelect.options[i].value;
                            if (window.choicesInstances && window.choicesInstances['ruc']) {
                                window.choicesInstances['ruc'].setChoiceByValue(rucSelect.options[i].value);
                            }
                            break;
                        }
                    }
                    actualizarVistaPrevia();
                }
            });
        }
        
        // Sincronizar RUC -> Empresa
        if (rucSelect && empresaSelect) {
            rucSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const empresaValue = selectedOption.getAttribute('data-empresa');
                
                if (empresaValue) {
                    // Buscar y seleccionar la Empresa correspondiente
                    for (let i = 0; i < empresaSelect.options.length; i++) {
                        if (empresaSelect.options[i].text === empresaValue) {
                            empresaSelect.value = empresaSelect.options[i].value;
                            if (window.choicesInstances && window.choicesInstances['empresa']) {
                                window.choicesInstances['empresa'].setChoiceByValue(empresaSelect.options[i].value);
                            }
                            break;
                        }
                    }
                    actualizarVistaPrevia();
                }
            });
        }
        
        // Sincronizar Chofer -> Brevete
        if (choferSelect && breveteSelect) {
            choferSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const breveteValue = selectedOption.getAttribute('data-brevete');
                
                if (breveteValue) {
                    // Buscar y seleccionar el Brevete correspondiente
                    for (let i = 0; i < breveteSelect.options.length; i++) {
                        if (breveteSelect.options[i].text === breveteValue) {
                            breveteSelect.value = breveteSelect.options[i].value;
                            if (window.choicesInstances && window.choicesInstances['brevete']) {
                                window.choicesInstances['brevete'].setChoiceByValue(breveteSelect.options[i].value);
                            }
                            break;
                        }
                    }
                    actualizarVistaPrevia();
                }
            });
        }
        
        // Sincronizar Brevete -> Chofer
        if (breveteSelect && choferSelect) {
            breveteSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const choferValue = selectedOption.getAttribute('data-chofer');
                
                if (choferValue) {
                    // Buscar y seleccionar el Chofer correspondiente
                    for (let i = 0; i < choferSelect.options.length; i++) {
                        if (choferSelect.options[i].text === choferValue) {
                            choferSelect.value = choferSelect.options[i].value;
                            if (window.choicesInstances && window.choicesInstances['chofer']) {
                                window.choicesInstances['chofer'].setChoiceByValue(choferSelect.options[i].value);
                            }
                            break;
                        }
                    }
                    actualizarVistaPrevia();
                }
            });
        }
    }
    
    // Actualizar hora en tiempo real
    function setupRealtimeClock() {
        const horaInput = document.getElementById('hora');
        if (!horaInput) return;
        
        function updateHora() {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            horaInput.value = hh + ':' + mm;
            actualizarVistaPrevia();
        }
        
        // Actualizar inmediatamente
        updateHora();
        
        // Actualizar cada segundo
        setInterval(updateHora, 1000);
    }

    // Enforce: asegurar que el campo hora permanezca deshabilitado y no seleccionable
    function enforceHoraDisabled() {
        try {
            const horaInput = document.getElementById('hora');
            if (!horaInput) return;

            function applyDisabled() {
                // marcar disabled y atributos ARIA
                horaInput.disabled = true;
                horaInput.setAttribute('aria-disabled', 'true');
                horaInput.setAttribute('tabindex', '-1');
                horaInput.style.pointerEvents = 'none';
                horaInput.style.userSelect = 'none';
            }

            // Aplicar inmediatamente
            applyDisabled();

            // Reaplicar periódicamente (en caso otro script intente habilitarlo)
            setInterval(function() { applyDisabled(); }, 1000);

            // Evitar foco/selección por captura (por si disabled es removido momentáneamente)
            horaInput.addEventListener('focus', function(e){
                if (!horaInput.disabled) { horaInput.blur(); applyDisabled(); }
            }, true);
            horaInput.addEventListener('mousedown', function(e){
                if (!horaInput.disabled) { e.preventDefault(); applyDisabled(); }
            }, true);
        } catch (e) {
            console.warn('[RecepcionesExternas] enforceHoraDisabled error', e);
        }
    }
    
    // Configurar listeners para actualización en tiempo real
    function setupRealtimePreview() {
        const campos = ['fecha', 'hora', 'turno', 'origen', 'empresa', 'ruc', 'chofer', 'brevete', 'observaciones', 'comentarios'];
        
        // Crear versión con debounce de actualizarVistaPrevia para evitar llamadas excesivas
        let previewTimeout = null;
        const debouncedPreview = function() {
            if (previewTimeout) clearTimeout(previewTimeout);
            previewTimeout = setTimeout(function() {
                previewTimeout = null;
                actualizarVistaPrevia();
            }, 300); // 300ms de debounce
        };
        
        campos.forEach(function(campoId) {
            const campo = document.getElementById(campoId);
            if (!campo) return;
            
            // Input/change events con debounce
            campo.addEventListener('input', debouncedPreview);
            campo.addEventListener('change', debouncedPreview);
        });
    }
    
    // Cargar siguiente correlativo
    function cargarSiguienteCorrelativo() {
        try {
            const url = (window.APP_URL || window.BASE_URL) + '/recepcionesexternas/siguienteVale';
            console.log('[RecepcionesExternas] intentar endpoint:', url);
            fetch(url, { credentials: 'same-origin' })
                .then(r => r.text())
                .then(text => {
                    try {
                        const res = JSON.parse(text);
                        if (res && res.success && res.correlativo) {
                            const corrEl = document.getElementById('correlativoVale');
                            if (corrEl) {
                                const codeEl = corrEl.querySelector('.code-vale');
                                if (codeEl) codeEl.textContent = String(res.correlativo);
                                else corrEl.textContent = String(res.correlativo);
                            }
                        } else {
                            console.warn('[RecepcionesExternas] respuesta inesperada siguienteVale:', res, 'raw:', text.slice(0,300));
                        }
                    } catch (e) {
                        console.error('[RecepcionesExternas] siguienteVale returned non-JSON (possible HTML):', text.slice(0,300));
                    }
                })
                .catch(function(err) {
                    console.warn('[RecepcionesExternas] Error al cargar correlativo:', err);
                });
        } catch (e) {
            console.warn('[RecepcionesExternas] Error en cargarSiguienteCorrelativo:', e);
        }
    }
    
    // Helper: Renderizar grilla de guías para la vista previa
    function renderGuiasGrid(guiasArray, activeColIndices) {
        // Intentamos usar el orden real de columnas del header para construir la vista previa
        const headerCols = Array.from(document.querySelectorAll('#headerGrillaGuiasPrincipales .col-producto'));
        const NUM_PRODUCTOS_ALL = headerCols.length || Object.keys(FIXED_PRODUCTOS_BY_COL).length;

        // Filtrar solo las columnas que tienen datos
        let filteredHeaderCols;
        if (headerCols.length > 0 && activeColIndices && activeColIndices.size > 0) {
            filteredHeaderCols = headerCols.filter(function(_, i) { return activeColIndices.has(i); });
        } else {
            filteredHeaderCols = headerCols.slice();
        }
        const NUM_PRODUCTOS = filteredHeaderCols.length || NUM_PRODUCTOS_ALL;

        // Mapa de colores por abreviatura (fallbacks)
        const COLOR_BY_ABRE = {
            'EAN': '#e3f2fd',
            'JNG': '#e8f5e9',
            'JBL': '#fff3e0',
            'EXI': '#fce4ec',
            'RCK': '#f3e5f5',
            'PL AZUL': '#e8eaf6',
            'REV': '#f1f8e9',
            'NWZ': '#fff8e1'
        };

        if (!guiasArray || guiasArray.length === 0) {
            // Construir encabezado vacío usando headerCols si existe
            let emptyHead = `
                <thead>
                    <tr style="background:#e5e7eb;">
                        <th rowspan="2" style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; width:1%;">n° guía</th>
                        <th rowspan="2" style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; width:1%;">n° doc. ref.</th>
            `;

            if (filteredHeaderCols.length) {
                filteredHeaderCols.forEach(function(th) {
                    // Intentar resolver el producto y mostrar su CÓDIGO en la cabecera del preview
                    const raw = (th.textContent || '').trim();
                    const abreAttr = th.getAttribute('data-producto-fijo') || raw;
                    let foundProd = null;
                    try {
                        if (productosData && productosData.length) {
                            foundProd = productosData.find(function(p) {
                                return ((p.Abreviatura || '').toString().trim().toUpperCase() === (abreAttr || '').toString().trim().toUpperCase())
                                    || ((p.Producto || '').toString().trim().toUpperCase() === (abreAttr || '').toString().trim().toUpperCase());
                            });
                        }
                    } catch (e) { foundProd = null; }
                    const title = (foundProd && foundProd.Codigo) ? String(foundProd.Codigo) : (raw.toUpperCase() || 'P');
                    const abre = (foundProd && foundProd.Abreviatura) ? foundProd.Abreviatura : (abreAttr || raw);
                    const bg = COLOR_BY_ABRE[(abre || '').toString().toUpperCase()] || '#ffffff';
                    emptyHead += `<th colspan="4" style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; background:${bg};">${title}</th>`;
                });
            } else {
                // Fallback a FIXED_PRODUCTOS_BY_COL por compatibilidad
                for (let i = 1; i <= NUM_PRODUCTOS; i++) {
                    const title = (FIXED_PRODUCTOS_BY_COL[i] || `P${i}`).toUpperCase();
                    const bg = COLOR_BY_ABRE[title] || '#ffffff';
                    emptyHead += `<th colspan="4" style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; background:${bg};">${title}</th>`;
                }
            }

            emptyHead += `
                    </tr>
                    <tr style="background:#e5e7eb;">
            `;

            // Subcabeceras: cant/pend/adic/total por cada producto
            for (let i = 0; i < NUM_PRODUCTOS; i++) {
                emptyHead += `
                    <th style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.45rem; white-space:nowrap; width:1%;">cant</th>
                    <th style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.45rem; white-space:nowrap; width:1%;">pend</th>
                    <th style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.45rem; white-space:nowrap; width:1%;">adic</th>
                    <th style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.45rem; white-space:nowrap; width:1%;">total</th>
                `;
            }

            emptyHead += `
                    </tr>
                </thead>
                <tbody>
                            <tr>
                                <td colspan="${2 + NUM_PRODUCTOS * 4}" style="border:1px solid #999; padding:3px; text-align:center; color:#999; font-size:0.5rem;">&nbsp;</td>
                            </tr>
                            <tr style="background:#f3f4f6; font-weight:700;">
                                <td colspan="2" style="border:1px solid #999; padding:1px 3px; text-align:center; font-size:0.5rem;">TOTALES</td>
                                ${ (function(){
                                    let out = '';
                                    for (let i=0;i<NUM_PRODUCTOS;i++) {
                                        out += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; width:1%;">0</td>`;
                                        out += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; width:1%;">0</td>`;
                                        out += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; width:1%;">0</td>`;
                                        out += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; font-weight:700; width:1%;">0</td>`;
                                    }
                                    return out;
                                })() }
                            </tr>
                        </tbody>
                    `;

            // Si no hay guías, devolvemos el encabezado y una fila VACÍA para mantener la estructura visual
            return emptyHead;
        }

        // Cabecera dinámica
        let html = `
            <thead>
                <tr style="background:#e5e7eb;">
                    <th rowspan="2" style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; width:1%;">n° guía</th>
                    <th rowspan="2" style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; width:1%;">n° doc. ref.</th>
        `;

        // Primera fila: una celda por producto (colspan 4)
        if (filteredHeaderCols.length) {
            filteredHeaderCols.forEach(function(th) {
                // Resolver producto y mostrar CÓDIGO en la cabecera del preview cuando sea posible
                const raw = (th.textContent || '').trim();
                const abreAttr = th.getAttribute('data-producto-fijo') || raw;
                let foundProd = null;
                try {
                    if (productosData && productosData.length) {
                        foundProd = productosData.find(function(p) {
                            return ((p.Abreviatura || '').toString().trim().toUpperCase() === (abreAttr || '').toString().trim().toUpperCase())
                                || ((p.Producto || '').toString().trim().toUpperCase() === (abreAttr || '').toString().trim().toUpperCase());
                        });
                    }
                } catch (e) { foundProd = null; }
                const title = (foundProd && foundProd.Codigo) ? String(foundProd.Codigo) : (raw.toUpperCase() || '');
                const abre = (foundProd && foundProd.Abreviatura) ? foundProd.Abreviatura : (abreAttr || raw);
                const bg = COLOR_BY_ABRE[(abre || '').toString().toUpperCase()] || '#ffffff';
                html += `<th colspan="4" style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; background:${bg};">${title}</th>`;
            });
        } else {
            for (let i = 1; i <= NUM_PRODUCTOS; i++) {
                const title = (FIXED_PRODUCTOS_BY_COL[i] || `P${i}`).toUpperCase();
                const bg = COLOR_BY_ABRE[title] || '#ffffff';
                html += `<th colspan="4" style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; background:${bg};">${title}</th>`;
            }
        }

        html += `
                </tr>
                <tr style="background:#e5e7eb;">
        `;

        // Segunda fila: subcabeceras
        for (let i = 0; i < NUM_PRODUCTOS; i++) {
            html += `
                <th style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.45rem; white-space:nowrap; width:1%;">cant</th>
                <th style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.45rem; white-space:nowrap; width:1%;">pend</th>
                <th style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.45rem; white-space:nowrap; width:1%;">adic</th>
                <th style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.45rem; white-space:nowrap; width:1%;">total</th>
            `;
        }

        html += `
                </tr>
            </thead>
        `;

        // Renderizar cada guía como una fila con todos sus productos
        // Inicializar acumuladores por columna de producto (cant/pend/adic/total)
        const acumCant = new Array(NUM_PRODUCTOS).fill(0);
        const acumPend = new Array(NUM_PRODUCTOS).fill(0);
        const acumAdic = new Array(NUM_PRODUCTOS).fill(0);
        const acumTotal = new Array(NUM_PRODUCTOS).fill(0);

        guiasArray.forEach(function(guiaData) {
            // Crear un mapa de productos por código para acceso rápido
            const prodMap = {};
            if (guiaData.productos && guiaData.productos.length > 0) {
                guiaData.productos.forEach(function(prod) {
                    prodMap[prod.codigo] = prod;
                });
            }

            html += `<tr>`;
            html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; width:1%;">${guiaData.numeroGuia}</td>`;
            html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; width:1%;">${guiaData.numeroRef || ''}</td>`;

            // Para cada producto visto en el header, intentar mostrar cant/pend/adic/total
            for (let col = 0; col < NUM_PRODUCTOS; col++) {
                // Determinar nombre/codigo desde header o FIXED_PRODUCTOS_BY_COL
                const th = filteredHeaderCols[col];
                const headerTitle = th ? (th.textContent || '').trim() : (FIXED_PRODUCTOS_BY_COL[col+1] || `P${col+1}`);
                const targetName = th && th.getAttribute('data-producto-fijo') ? th.getAttribute('data-producto-fijo') : headerTitle;
                
                // Buscar primero por nombre completo del producto, luego por abreviatura
                const codigoProducto = (targetName && productosData && productosData.length) ? (
                    (productosData.find(p => ((p.Producto||'').toString().trim().toUpperCase() === targetName.toString().trim().toUpperCase())))
                    || (productosData.find(p => ((p.Abreviatura||'').toString().trim().toUpperCase() === targetName.toString().trim().toUpperCase())))
                )?.Codigo || '' : '';

                const prod = codigoProducto ? prodMap[codigoProducto] : null;
                const cant = prod ? (prod.cantidad || 0) : 0;
                const pend = prod ? (prod.pendiente || 0) : 0;
                const adic = prod ? (prod.adicional || 0) : 0;
                const regu = prod ? (prod.regularizado || 0) : 0;
                const total = cant + adic - pend + regu;

                const abre = th ? (th.getAttribute('data-producto-fijo') || headerTitle) : (FIXED_PRODUCTOS_BY_COL[col+1] || `P${col+1}`);
                const bg = COLOR_BY_ABRE[(abre || '').toString().toUpperCase()] || '#ffffff';

                html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; width:1%; background:${bg};">${cant > 0 ? cant : ''}</td>`;
                html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; width:1%; background:${bg};">${pend > 0 ? pend : ''}</td>`;
                html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; width:1%; background:${bg};">${adic > 0 ? adic : ''}</td>`;
                html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; font-weight:600; white-space:nowrap; width:1%; background:${bg};">${total > 0 ? total : ''}</td>`;

                // Acumular para totales
                acumCant[col] += Number(cant) || 0;
                acumPend[col] += Number(pend) || 0;
                acumAdic[col] += Number(adic) || 0;
                acumTotal[col] += Number(total) || 0;
            }

            html += `</tr>`;
        });

        // Construir fila de totales por cada columna de producto
        html += `<tr style="background:#f3f4f6; font-weight:700;">`;
        // Celda inicial de totales (abarcar n° guía y n° doc. ref.)
        html += `<td colspan="2" style="border:1px solid #999; padding:1px 3px; text-align:center; font-size:0.5rem; width:1%;">TOTALES</td>`;

        for (let col = 0; col < NUM_PRODUCTOS; col++) {
            const bg = COLOR_BY_ABRE[( (filteredHeaderCols[col] && (filteredHeaderCols[col].getAttribute('data-producto-fijo')||filteredHeaderCols[col].textContent)) || '' ).toString().toUpperCase()] || '#ffffff';
            const tc = acumCant[col] || 0;
            const tp = acumPend[col] || 0;
            const ta = acumAdic[col] || 0;
            const tt = acumTotal[col] || 0;

            html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; width:1%; background:${bg};">${tc}</td>`;
            html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; width:1%; background:${bg};">${tp}</td>`;
            html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; width:1%; background:${bg};">${ta}</td>`;
            html += `<td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; font-weight:700; width:1%; background:${bg};">${tt}</td>`;
        }

        html += `</tr>`;

        return html;
    }
    
    // Actualizar vista previa en tiempo real
    async function actualizarVistaPrevia() {
        try { console.log('[RecepcionesExternas] actualizarVistaPrevia start', { modoEdicion: window.modoEdicion || false, usuarioUsername: window.usuarioUsername || '' }); } catch(e) {}
        const valePreview = document.getElementById('valePreview');
        if (!valePreview) return;
        
        // Obtener valores del formulario
        const fecha = document.getElementById('fecha')?.value || '';
        const hora = document.getElementById('hora')?.value || '';
        const turnoSelect = document.getElementById('turno');
        let turno = turnoSelect?.options[turnoSelect.selectedIndex]?.text || '';
        if (turno && turno.toLowerCase().includes('seleccione')) turno = '';
        const origenSelect = document.getElementById('origen');
        let origen = origenSelect?.options[origenSelect.selectedIndex]?.text || '';
        if (origen && origen.toLowerCase().includes('seleccione')) origen = '';
        
        const empresaSelect = document.getElementById('empresa');
        let empresa = empresaSelect?.options[empresaSelect.selectedIndex]?.text || '';
        if (empresa && empresa.toLowerCase().includes('seleccione')) empresa = '';
        
        const rucSelect = document.getElementById('ruc');
        let ruc = rucSelect?.options[rucSelect.selectedIndex]?.text || '';
        if (ruc && ruc.toLowerCase().includes('seleccione')) ruc = '';
        
        const choferSelect = document.getElementById('chofer');
        let chofer = choferSelect?.options[choferSelect.selectedIndex]?.text || '';
        if (chofer && chofer.toLowerCase().includes('seleccione')) chofer = '';
        
        const breveteSelect = document.getElementById('brevete');
        let brevete = breveteSelect?.options[breveteSelect.selectedIndex]?.text || '';
        if (brevete && brevete.toLowerCase().includes('seleccione')) brevete = '';
        
        const correlativoEl = document.getElementById('correlativoVale');
        const correlativo = correlativoEl ? (correlativoEl.querySelector('.code-vale')?.textContent || correlativoEl.textContent) : 'VRE-000001';
        
        const observacionesEl = document.getElementById('observaciones');
        const observaciones = observacionesEl ? observacionesEl.value.trim() : '';
        
        const comentariosEl = document.getElementById('comentarios');
        const comentarios = comentariosEl ? comentariosEl.value.trim() : '';
        
        // Recopilar datos de la NUEVA GRILLA
        const guiasArray = []; // Array de objetos de guía con sus productos
        const productosConsolidados = {}; // Consolidado por código de producto
        const observacionesTexto = []; // Array para almacenar las observaciones formateadas
        const colsConDatos = new Set(); // Columnas (0-based) con datos en al menos una fila
        
        const tbody = document.getElementById('tbodyGrillaGuias');
        if (tbody) {
            const filas = tbody.querySelectorAll('.fila-guia');
            const numColumnas = document.querySelectorAll('.col-producto').length;
            
            // Obtener códigos, nombres y abreviaturas de producto de los selects del header
            const codigosProducto = [];
            const nombresProducto = [];
            const abreviaturasProducto = [];
            
            // Primero procesar las columnas fijas (EAN, JAB, JAN, EXI)
            const columnasFijas = document.querySelectorAll('.col-producto-fija');
            columnasFijas.forEach(function(th) {
                const abreviaturaFija = th.getAttribute('data-producto-fijo') || '';
                const colIndex = parseInt(th.getAttribute('data-producto-col')) || null;
                // Buscar el producto preferentemente por nombre completo (mapeo FIXED_PRODUCTOS_BY_COL), si no, por Abreviatura
                let codigo = '';
                let nombre = '';
                let abre = abreviaturaFija;
                if (productosData && productosData.length) {
                    try {
                        let found = null;
                        
                        // Primero intentar buscar por nombre exacto usando el mapeo por columna
                        if (colIndex && FIXED_PRODUCTOS_BY_COL[colIndex]) {
                            const targetName = FIXED_PRODUCTOS_BY_COL[colIndex];
                            found = productosData.find(function(p) { return (p.Producto || '').toString().trim().toUpperCase() === targetName.toUpperCase(); });
                        }

                        // Si no se encontró por nombre, intentar por abreviatura (fallback)
                        if (!found && abreviaturaFija) {
                            found = productosData.find(function(p) { 
                                return (p.Abreviatura || '').toString().toUpperCase() === abreviaturaFija.toUpperCase();
                            });
                        }

                        if (found) {
                            codigo = (found.Codigo || '').toString();
                            nombre = found.Producto || '';
                            abre = found.Abreviatura || abreviaturaFija;
                        }
                    } catch (e) {}
                }

                codigosProducto.push(codigo);
                nombresProducto.push(nombre);
                abreviaturasProducto.push(abre);
            });
            
            // Luego procesar las columnas dinámicas (selects)
            const selectsProducto = document.querySelectorAll('.select-header-producto');
            selectsProducto.forEach(function(select) {
                const codigo = select.value.trim();
                let nombre = '';
                let abreviatura = '';
                try {
                    nombre = (select.options[select.selectedIndex] && select.options[select.selectedIndex].getAttribute('data-nombre')) ? select.options[select.selectedIndex].getAttribute('data-nombre') : '';
                } catch (e) {
                    nombre = '';
                }

                // Fallback: buscar en productosData por código si no hay data-nombre
                if ((!nombre || nombre.trim() === '') && codigo) {
                    try {
                        const found = productosData.find(function(p) { return (p.Codigo || '').toString() === codigo; });
                        if (found) {
                            nombre = found.Producto || '';
                            abreviatura = found.Abreviatura || '';
                        }
                    } catch (e) {
                        nombre = '';
                    }
                }

                codigosProducto.push(codigo);
                nombresProducto.push(nombre);
                abreviaturasProducto.push(abreviatura);
            });
            
            filas.forEach(function(fila) {
                const numeroGuia = obtenerNumeroGuiaDesdeFila(fila);
                
                const inputDocRef = fila.querySelector('.input-doc-ref');
                const numeroRef = inputDocRef ? inputDocRef.value.trim() : '';
                
                // Crear objeto de guía si tiene número
                const guiaObj = {
                    numeroGuia: numeroGuia,
                    numeroRef: numeroRef,
                    productos: []
                };
                
                // Mapa temporal para acumular productos de esta guía
                const productosGuiaMap = {};

                // Procesar cada columna de producto (ahora cada producto tiene 3 celdas: cantidad, obs, cant obs)
                for (let col = 1; col <= numColumnas; col++) {
                    const inputCant = fila.querySelector(`.input-cant-producto[data-producto-col="${col}"]`);
                    const cantidad = parseInt(inputCant ? inputCant.value : 0) || 0;
                    const codigo = codigosProducto[col - 1] || `COD-${col}`;

                    // Obtener obs y cant obs específicos de este producto
                    const selectObsProd = fila.querySelector(`.select-obs-producto[data-producto-col="${col}"]`);
                    const inputCantObsProd = fila.querySelector(`.input-cant-obs-producto[data-producto-col="${col}"]`);
                    
                    let obsText = '';
                    if (selectObsProd) {
                        try {
                            const opt = selectObsProd.options[selectObsProd.selectedIndex];
                            obsText = opt && opt.text ? String(opt.text).trim() : '';
                            
                            // Si no hay texto útil, buscar por Id en observacionesData
                            const lowCheck = (obsText || '').toLowerCase();
                            if (!obsText || lowCheck.includes('seleccione') || lowCheck === '') {
                                const val = selectObsProd.value;
                                if (val && observacionesData && observacionesData.length) {
                                    try {
                                        const found = observacionesData.find(function(o) { return (o.Id || '').toString() === val.toString(); });
                                        if (found) obsText = (found.Item || '').toString();
                                    } catch (e) {}
                                }
                            }
                        } catch (e) {
                            obsText = '';
                        }
                    }
                    
                    const cantObs = parseInt(inputCantObsProd ? inputCantObsProd.value : 0) || 0;

                    if (cantidad > 0) {
                        // Consolidado global
                        if (!productosConsolidados[codigo]) {
                            productosConsolidados[codigo] = {
                                codigo: codigo,
                                abreviatura: abreviaturasProducto[col - 1] || '',
                                nombre: nombresProducto[col - 1] || '',
                                cantidadTotal: 0,
                                pendiente: 0,
                                adicional: 0
                            };
                        }
                        productosConsolidados[codigo].cantidadTotal += cantidad;
                        
                        // Productos de la guía
                        if (!productosGuiaMap[codigo]) {
                            productosGuiaMap[codigo] = {
                                codigo: codigo,
                                nombre: nombresProducto[col - 1] || '',
                                cantidad: 0,
                                pendiente: 0,
                                adicional: 0
                            };
                        }
                        productosGuiaMap[codigo].cantidad += cantidad;
                    }

                    // Procesar observación de este producto específico si tiene cant obs > 0
                    if (cantObs > 0 && obsText) {
                        // Asegurar que exista la entrada en el consolidado global
                        if (!productosConsolidados[codigo]) {
                            productosConsolidados[codigo] = {
                                codigo: codigo,
                                abreviatura: abreviaturasProducto[col - 1] || '',
                                nombre: nombresProducto[col - 1] || '',
                                cantidadTotal: 0,
                                pendiente: 0,
                                adicional: 0
                            };
                        }
                        
                        // Asegurar que exista en productos de la guía
                        if (!productosGuiaMap[codigo]) {
                            productosGuiaMap[codigo] = {
                                codigo: codigo,
                                nombre: nombresProducto[col - 1] || '',
                                cantidad: 0,
                                pendiente: 0,
                                adicional: 0
                            };
                        }

                        const lowerObs = (obsText || '').toLowerCase();
                        if (lowerObs === 'p' || lowerObs.includes('pend')) {
                            productosConsolidados[codigo].pendiente += cantObs;
                            productosGuiaMap[codigo].pendiente += cantObs;
                        } else if (lowerObs === 'de' || lowerObs.includes('deja')) {
                            productosConsolidados[codigo].pendiente += cantObs;
                            productosGuiaMap[codigo].pendiente += cantObs;
                        } else if (lowerObs === 'le' || lowerObs.includes('lleva')) {
                            productosConsolidados[codigo].pendiente += cantObs;
                            productosGuiaMap[codigo].pendiente += cantObs;
                        } else if (lowerObs === 'a' || lowerObs.includes('adici')) {
                            productosConsolidados[codigo].adicional += cantObs;
                            productosGuiaMap[codigo].adicional += cantObs;
                        } else if (lowerObs === 'r' || lowerObs.includes('regular')) {
                            // Regulariza: el total es la cantidad observada (no la cantidad base)
                            if (!productosConsolidados[codigo].regularizado) productosConsolidados[codigo].regularizado = 0;
                            productosConsolidados[codigo].regularizado += cantObs - cantidad;
                            if (!productosGuiaMap[codigo].regularizado) productosGuiaMap[codigo].regularizado = 0;
                            productosGuiaMap[codigo].regularizado += cantObs - cantidad;
                        } else if (lowerObs === 'pt' || lowerObs.includes('producto terminado')) {
                            // PT con DE (Deja) o LE (Lleva) también resta como pendiente
                            productosConsolidados[codigo].pendiente += cantObs;
                            productosGuiaMap[codigo].pendiente += cantObs;
                        }

                        // Generar texto de observación para la vista previa
                        if (numeroGuia && obsText && !obsText.toLowerCase().includes('seleccione')) {
                            let textoObs = '';
                            let codigoDisplay = codigo;

                            // Cuando la observacion es DE (Deja) o LE (Lleva) y el producto es EAN,
                            // reemplazar el codigo por el de EXI (Parihuela Ex Importacion)
                            if ((lowerObs === 'de' || lowerObs.includes('deja') || lowerObs === 'le' || lowerObs.includes('lleva'))) {
                                const thEan = document.querySelector(`.col-producto-fija[data-producto-col="${col}"][data-producto-fijo="EAN"]`);
                                if (thEan) {
                                    try {
                                        const exiProducto = (window.productosData || productosData || []).find(function(p) {
                                            var abre = (p.Abreviatura || '').toString().toUpperCase();
                                            var prod = (p.Producto || '').toString().trim().toUpperCase();
                                            return abre === 'EXI' || prod === 'PARIHUELA EX IMPORTACION - ESTANDAR';
                                        });
                                        if (exiProducto && exiProducto.Codigo) {
                                            codigoDisplay = exiProducto.Codigo.toString();
                                        }
                                    } catch(e) {}
                                }
                            }

                            // Obtener el valor del sub-select PT (DE/LE) si existe
                            var ptDeLe = '';
                            try {
                                var ptSelect = fila.querySelector(`.select-pt-de-le[data-producto-col="${col}"]`);
                                if (ptSelect) {
                                    ptDeLe = ptSelect.options[ptSelect.selectedIndex] ? ptSelect.options[ptSelect.selectedIndex].text.trim().toUpperCase() : '';
                                }
                            } catch(e) {}

                            // Detectar si el producto es JABA (negra JNG o blanca JBL)
                            // Solo aplica para observaciones DE o LE
                            var esJaba = (col == 3 || col == 4);
                            if (!esJaba && codigo) {
                                try {
                                    var prodNombre = (nombresProducto[col - 1] || '').toUpperCase();
                                    if (prodNombre.indexOf('JABA') >= 0) esJaba = true;
                                } catch(e) {}
                            }

                            if (lowerObs === 'p' || lowerObs.includes('pend')) {
                                textoObs = `PENDIENTE ${cantObs} UND DEL CODIGO ${codigoDisplay} GUIA ${numeroGuia}`;
                            } else if (lowerObs === 'de' || lowerObs.includes('deja')) {
                                textoObs = `DEJA ${cantObs}${esJaba ? ' UND OBSERVADA/AS' : ' UND'} DEL CODIGO ${codigoDisplay} GUIA ${numeroGuia}`;
                            } else if (lowerObs === 'le' || lowerObs.includes('lleva')) {
                                textoObs = `LLEVA ${cantObs}${esJaba ? ' UND OBSERVADA/AS' : ' UND'} DEL CODIGO ${codigoDisplay} GUIA ${numeroGuia}`;
                            } else if (lowerObs === 'a' || lowerObs.includes('adici')) {
                                textoObs = `ADICIONAL ${cantObs} UND DEL CODIGO ${codigoDisplay} GUIA ${numeroGuia}`;
                            } else if (lowerObs === 'r' || lowerObs.includes('regular')) {
                                textoObs = `REGULARIZA ${cantObs} UND DEL CODIGO ${codigoDisplay} GUIA ${numeroGuia}`;
                            } else if (lowerObs === 'pt' || lowerObs.includes('producto terminado')) {
                                if (ptDeLe === 'DE') {
                                    textoObs = `DEJA ${cantObs} UND CON PT A`;
                                } else if (ptDeLe === 'LE') {
                                    textoObs = `LLEVA ${cantObs} UND CON PT A`;
                                }
                            }

                            if (textoObs) {
                                observacionesTexto.push(textoObs);
                            }
                        }
                    }
                    // Rastrear columna con datos
                    if (cantidad > 0 || cantObs > 0) colsConDatos.add(col - 1);
                }

                // Agregar productos al objeto guía
                guiaObj.productos = Object.values(productosGuiaMap);
                
                // Solo agregar la guía si tiene número
                if (numeroGuia) {
                    guiasArray.push(guiaObj);
                }
            });
        }
        
        // Construir HTML de productos consolidados
        let productosHtml = '';
        const productosArray = Object.values(productosConsolidados);
        if (productosArray.length > 0) {
            productosArray.forEach(function(prod) {
                const totalProd = (parseInt(prod.cantidadTotal) || 0) + (parseInt(prod.adicional) || 0) - (parseInt(prod.pendiente) || 0) + (parseInt(prod.regularizado) || 0);
                productosHtml += `
                    <tr>
                        <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap;">${prod.codigo || prod.abreviatura}</td>
                        <td style="border:1px solid #999; padding:1px 2px; font-size:0.5rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:140px;">${prod.nombre || '-'}</td>
                        <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap;">${prod.cantidadTotal}</td>
                        <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap;">${prod.pendiente || ''}</td>
                        <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap;">${prod.adicional || ''}</td>
                        <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; font-weight:600;">${totalProd}</td>
                    </tr>
                `;
            });
        } else {
            // Si no hay productos consolidados, devolver una fila vacía para mantener la estructura
            productosHtml = `
                <tr>
                    <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap;">&nbsp;</td>
                    <td style="border:1px solid #999; padding:1px 2px; font-size:0.5rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:140px;">&nbsp;</td>
                    <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap;">&nbsp;</td>
                    <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap;">&nbsp;</td>
                    <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap;">&nbsp;</td>
                    <td style="border:1px solid #999; padding:1px 2px; text-align:center; font-size:0.5rem; white-space:nowrap; font-weight:600;">&nbsp;</td>
                </tr>
            `;
        }
        
        // Formatear fecha
        let fechaFormateada = fecha;
        if (fecha && /^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
            const partes = fecha.split('-');
            fechaFormateada = partes[2] + '/' + partes[1] + '/' + partes[0];
        }
        
        // Formatear hora a 12h
        let horaFormateada = hora;
        if (hora && /^\d{2}:\d{2}(:\d{2})?$/.test(hora)) {
            const partes = hora.split(':');
            let hh = parseInt(partes[0], 10);
            const mm = partes[1];
            const ampm = hh >= 12 ? 'PM' : 'AM';
            hh = hh % 12;
            if (hh === 0) hh = 12;
            horaFormateada = hh + ':' + mm + ' ' + ampm;
        }
        
        // === AUTO-POBLAR TEXTAREA DE OBSERVACIONES ===
        // Poblar el textarea #observaciones con el contenido generado automaticamente de la grilla,
        // solo si el usuario NO lo ha editado manualmente.
        try {
            var obsTa = document.getElementById('observaciones');
            if (obsTa) {
                var textoGenerado = observacionesTexto.length > 0 ? observacionesTexto.join('\n') : '';
                // Inicializar flag de edicion manual si no existe
                if (typeof window._observacionesEditado === 'undefined') {
                    window._observacionesEditado = false;
                    // Listener para detectar cuando el usuario edita manualmente
                    obsTa.addEventListener('input', function() {
                        window._observacionesEditado = true;
                    });
                }
                // Auto-poblar solo si el usuario no ha editado manualmente
                if (!window._observacionesEditado) {
                    obsTa.value = textoGenerado;
                }
            }
        } catch(e) {}
        
        // Obtener el valor final del textarea (ya sea auto-poblado o editado manualmente)
        var observacionesFinal = '';
        try {
            var obsEl = document.getElementById('observaciones');
            if (obsEl) observacionesFinal = obsEl.value.trim();
        } catch(e) {}
        
        // Construir HTML de la vista previa (basado en la imagen adjunta)
        // Determinar URL base segura para assets (imagen de firma) y verificar existencia
        const _pp_baseAssets = (typeof window.BASE_URL !== 'undefined' && window.BASE_URL) ? window.BASE_URL : (window.location.origin + '/swlavoro/public');
        let firmaUrl = '';
        
        // Cache de URL de firma para evitar peticiones repetitivas
        if (!window._firmaUrlCache) {
            window._firmaUrlCache = {};
        }
        
        try {
            if (window.usuarioUsername) {
                const uname = window.usuarioUsername || '';
                const cacheKey = uname.toUpperCase();
                
                // Usar cache si ya se verificó antes
                if (window._firmaUrlCache[cacheKey] !== undefined) {
                    firmaUrl = window._firmaUrlCache[cacheKey];
                } else {
                    // Solo verificar si no está en caché
                    const candidates = [
                        `${_pp_baseAssets}/img/${uname}.png`,
                        window.location.origin + '/swlavoro/public/img/' + uname + '.png',
                        window.location.origin + '/public/img/' + uname + '.png',
                        `${_pp_baseAssets}/img/${uname.toUpperCase()}.png`,
                        window.location.origin + '/swlavoro/public/img/' + uname.toUpperCase() + '.png',
                        window.location.origin + '/public/img/' + uname.toUpperCase() + '.png'
                    ];

                    const attempted = [];
                    for (let i = 0; i < candidates.length; i++) {
                        const url = candidates[i];
                        try {
                            const res = await fetch(url, { method: 'HEAD' });
                            attempted.push({ url: url, ok: !!res.ok, status: res.status });
                            if (res.ok) { firmaUrl = url; break; }
                        } catch (err) {
                            attempted.push({ url: url, ok: false, status: 'error' });
                        }
                    }
                    console.log('[RecepcionesExternas] Firma: URLs probadas', attempted);
                    // Guardar en caché (aunque sea vacío, para no repetir)
                    window._firmaUrlCache[cacheKey] = firmaUrl;
                }
            }
        } catch (e) {
            console.warn('[RecepcionesExternas] Error comprobando firma', e);
            firmaUrl = '';
        }
        // Usar logoHtmlCache en lugar de recrear el elemento cada vez
        
        const html = `
            <div style="font-family:Arial,sans-serif; font-size:0.75rem;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                    <div style="flex:1;">
                        ${logoHtmlCache}
                    </div>
                    <div style="flex:0 0 auto; margin-left:12px;">
                        <span style="font-size:1.3rem; font-weight:bold; border:1px solid #333; padding:3px 14px; border-radius:5px;">${correlativo || ''}</span>
                    </div>
                </div>
                <h3 style="text-align:center; font-weight:bold; margin-top:6px; font-size:1.15rem;">VALE DE RECEPCIÓN EXTERNA</h3>
                <div style="text-align:center; font-size:0.9rem; margin-bottom:20px;">ALMACÉN DE JABAS Y PARIHUELAS - HUACHIPA</div>
                
                <table style="width:100%; border-collapse:collapse; font-size:0.7rem; margin-bottom:12px;">
                    <tr>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">RUC:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${ruc || ''}</td>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">ORIGEN:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${origen || ''}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600;">EMPRESA:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${empresa || ''}</td>
                        <td style="padding:3px 6px; font-weight:600;">FECHA:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${fechaFormateada || '_____________'}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">BREVETE:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${brevete || ''}</td>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">TURNO:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${turno || ''}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">CHOFER:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${chofer || ''}</td>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">HORA:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${horaFormateada || '_____________'}</td>
                    </tr>
                </table>
                
                <div style="margin-top:8px;">
                    <div style="background:#d1d5db; padding:4px 6px; font-weight:bold; font-size:0.65rem; border:1px solid #999; border-bottom:none; border-radius:4px 4px 0 0; text-align:center;">
                        GUÍAS
                    </div>
                    <table class="grid-table" style="width:100%; border-collapse:collapse; font-size:0.5rem; margin-bottom:8px; border:1px solid #999; border-top:none;">
                        ${renderGuiasGrid(guiasArray, colsConDatos)}
                    </table>
                </div>
                
                <div style="margin-top:8px;">
                    <div style="background:#d1d5db; padding:4px 6px; font-weight:bold; font-size:0.65rem; border:1px solid #999; border-bottom:none; border-radius:4px 4px 0 0; text-align:center;">
                        CONSOLIDADO DE PRODUCTOS
                    </div>
                    <table class="grid-table" style="width:100%; border-collapse:collapse; font-size:0.5rem; margin-bottom:8px; border:1px solid #999; border-top:none;">
                        <thead>
                            <tr style="background:#e5e7eb;">
                                <th style="border:1px solid #999; padding:1px 2px; text-align:center; width:10%; font-size:0.5rem; white-space:nowrap;">CÓDIGO</th>
                                <th style="border:1px solid #999; padding:1px 2px; text-align:center; width:40%; font-size:0.5rem; white-space:nowrap;">PRODUCTO</th>
                                <th style="border:1px solid #999; padding:1px 2px; text-align:center; width:10%; font-size:0.5rem; white-space:nowrap;">CANT</th>
                                <th style="border:1px solid #999; padding:1px 2px; text-align:center; width:10%; font-size:0.5rem; white-space:nowrap;">PEND</th>
                                <th style="border:1px solid #999; padding:1px 2px; text-align:center; width:15%; font-size:0.5rem; white-space:nowrap;">ADIC</th>
                                <th style="border:1px solid #999; padding:1px 2px; text-align:center; width:15%; font-size:0.5rem; white-space:nowrap;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${productosHtml}
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:8px;">
                    <div style="border:1px solid #999; border-radius:4px; overflow:hidden;">
                        <div style="background:#d1d5db; padding:2px 4px; font-weight:bold; font-size:0.55rem; border-bottom:1px solid #999; text-align:center;">
                            OBSERVACIONES
                        </div>
                        <div style="min-height:20px; padding:2px; font-size:0.5rem; white-space:pre-wrap; ${observacionesFinal ? 'color:#000;' : 'color:#999;'}">${observacionesFinal || '&nbsp;'}</div>
                    </div>
                </div>

                <div style="margin-top:8px;">
                    <div style="border:1px solid #999; border-radius:4px; overflow:hidden;">
                        <div style="background:#d1d5db; padding:2px 4px; font-weight:bold; font-size:0.55rem; border-bottom:1px solid #999; text-align:center;">
                            COMENTARIOS ADICIONALES
                        </div>
                        <div style="min-height:20px; padding:2px; font-size:0.5rem; ${comentarios ? 'color:#000;' : 'color:#999;'}">
                            ${comentarios || '&nbsp;'}
                        </div>
                    </div>
                </div>

                <div style="margin-top:12px; border:0.5pt solid #333; border-radius:8px; padding:8px;">
                    <div style="text-align:center; font-weight:600; font-size:0.75rem; margin-bottom:16px;">FIRMAS DE VALIDACIÓN Y AUTORIZACIÓN</div>
                    <table style="width:100%; text-align:center; font-size:0.65rem;">
                        <tr>
                            <td style="width:33%; vertical-align:bottom; padding-bottom:4px;">
                                <div style="display:flex; flex-direction:column; align-items:center;">
                                    <div style="height:80px; margin-bottom:1px;"></div>
                                    <div style="border-top:1px solid #333; width:75%; margin:8px 0 4px 0;"></div>
                                    <div style="margin-top:4px; font-size:0.65rem;">TRANSPORTISTA</div>
                                </div>
                            </td>
                            <td style="width:33%; vertical-align:bottom; padding-bottom:4px;">
                                <div style="display:flex; flex-direction:column; align-items:center;">
                                    ${firmaUrl ? `<img src="${firmaUrl}" onerror="this.onerror=null; this.src=window.location.origin + '/swlavoro/public/img/' + (window.usuarioUsername ? window.usuarioUsername.toUpperCase() : '') + '.png';" style="height:117px; margin-bottom:1px; display:block; max-width:100%;" alt="Firma Recepcionista" />` : '<div style="height:117px; margin-bottom:1px;"></div>'}
                                    <div style="border-top:1px solid #333; width:75%; margin:8px 0 4px 0;"></div>
                                    <div style="margin-top:4px; font-size:0.65rem;">RECEPCIONISTA</div>
                                </div>
                            </td>
                            <td style="width:33%; vertical-align:bottom; padding-bottom:4px;">
                                <div style="display:flex; flex-direction:column; align-items:center;">
                                    <div style="height:80px; margin-bottom:1px;"></div>
                                    <div style="border-top:1px solid #333; width:75%; margin:8px 0 4px 0;"></div>
                                    <div style="margin-top:4px; font-size:0.65rem;">PATRIMONIO</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        
        valePreview.innerHTML = html;
    }

    // API para poblar la grilla de GUÍAS desde otras partes del código
    function addGuiaToGrid(text) {
        try {
            const tbody = document.getElementById('guiaGridBody');
            if (!tbody) return false;

            // Buscar la primera celda vacía (trim === '') en las filas existentes
            const rows = Array.from(tbody.querySelectorAll('tr'));
            for (let r = 0; r < rows.length; r++) {
                const cells = Array.from(rows[r].querySelectorAll('td'));
                for (let c = 0; c < cells.length; c++) {
                    if (!cells[c].textContent.trim()) {
                        cells[c].textContent = String(text);
                        return true;
                    }
                }
            }

            // Si no hay celdas vacías, añadir una nueva fila y colocar el texto en la primera celda
            const newRow = document.createElement('tr');
            for (let i = 0; i < 5; i++) {
                const td = document.createElement('td');
                td.className = 'guia-cell';
                td.style.cssText = 'border:1px solid #999; padding:8px; text-align:center; width:20%; min-height:36px; vertical-align:middle; word-break:break-word;';
                if (i === 0) td.textContent = String(text);
                newRow.appendChild(td);
            }
            tbody.appendChild(newRow);
            return true;
        } catch (e) {
            console.warn('[RecepcionesExternas] addGuiaToGrid error', e);
            return false;
        }
    }

    function clearGuiaGrid() {
        const tbody = document.getElementById('guiaGridBody');
        if (!tbody) return;
        const firstRow = tbody.querySelector('tr');
        if (!firstRow) return;
        // Vaciar todas las celdas y dejar solo la primera fila con 5 celdas vacías
        tbody.innerHTML = '';
        const row = document.createElement('tr');
        for (let i = 0; i < 5; i++) {
            const td = document.createElement('td');
            td.className = 'guia-cell';
            td.style.cssText = 'border:1px solid #999; padding:8px; text-align:center; width:20%; min-height:36px; vertical-align:middle; word-break:break-word;';
            row.appendChild(td);
        }
        tbody.appendChild(row);
    }

    function setGuiaList(arr) {
        clearGuiaGrid();
        if (!Array.isArray(arr) || !arr.length) return;
        arr.forEach(function(item) {
            addGuiaToGrid(item);
        });
    }


    // Exponer API global para manipular la grilla de guías desde otros scripts
    window.guiaGrid = window.guiaGrid || {};
    window.guiaGrid.add = addGuiaToGrid;
    window.guiaGrid.clear = clearGuiaGrid;
    window.guiaGrid.set = setGuiaList;
    
    // ========== GESTIÓN DE GUÍAS DINÁMICAS ==========
    
    // Variables globales para productos y observaciones
    let productosData = [];
    let productosLoaded = false; // flag para indicar que la carga asíncrona de productos finalizó
    let observacionesData = [];
    let guiasDelVale = []; // Array de guías guardadas en el vale
    let recepcionIdActual = null; // ID de la recepción actual (para modificar)
    let modoFormulario = 'nuevo'; // 'nuevo' o 'modificar'
    
    // Exponer globalmente para que serializarDatos() pueda acceder
    window.productosData = productosData;
    window.observacionesData = observacionesData;
    window.productosLoaded = productosLoaded;

    // Mapeo estático de los productos fijos (según campo Producto en la tabla Productos)
    // Orden previsto por el negocio: columna 1..8
    // Deseado: EAN, EXI, JNG, JBL, EAN (COMPRAS GLORIA), PL AZUL, REV, NWZ
    const FIXED_PRODUCTOS_BY_COL = {
        1: 'PARIHUELA MADERA EAN 1.00 X 1.20 MTRS',
        2: 'PARIHUELA EX IMPORTACION - ESTANDAR',
        3: 'JABA PLASTICA DE COLOR NEGRO 52 X 35 X 31',
        4: 'JABA PLASTICA DE COLOR BLANCO 60 X 40 X 10',
        5: 'PARIHUELA MADERA EAN 1.00 X 1.20 MTRS - COMPRAS GLORIA NUEVAS',
        6: 'PARIHUELA PLASTICA AZUL',
        7: 'PARIHUELA REVERSIBLE MADERA 1.12M X 1.16M X 15CM',
        8: 'PARIHUELA NEW ZELAND - ESTANDAR'
    };

    // Etiquetas de cabecera personalizadas (SOLO visuales) para diferenciar columnas
    // cuyo producto comparte abreviatura (ej: col 5 = COMPRAS GLORIA NUEVAS -> 'EAN CN')
    const HEADER_LABEL_OVERRIDE_BY_COL = {
        5: 'EAN CN'
    };

    // Actualizar los textos y atributos de las cabeceras fijas según los datos cargados
    function updateFixedHeaders() {
        try {
            const columnasFijas = document.querySelectorAll('.col-producto-fija');
            columnasFijas.forEach(function(th) {
                const colIndex = parseInt(th.getAttribute('data-producto-col')) || null;
                const abreviaturaFija = th.getAttribute('data-producto-fijo') || '';
                let found = null;

                if (productosData && productosData.length) {
                    // Primero intentar por nombre completo según mapeo por columna
                    if (colIndex && FIXED_PRODUCTOS_BY_COL[colIndex]) {
                        try {
                            const targetName = FIXED_PRODUCTOS_BY_COL[colIndex];
                            found = productosData.find(function(p) { return (p.Producto || '').toString().trim().toUpperCase() === targetName.toUpperCase(); });
                        } catch (e) { found = null; }
                    }
                    
                    // Si no se encontró por nombre, intentar por Abreviatura (fallback)
                    if (!found && abreviaturaFija) {
                        try {
                            found = productosData.find(function(p) { return (p.Abreviatura || '').toString().toUpperCase() === abreviaturaFija.toUpperCase(); });
                        } catch (e) { found = null; }
                    }
                }

                if (found) {
                    // Mantener el atributo con la abreviatura y mostrar la ABREVIATURA
                    try { th.setAttribute('data-producto-fijo', found.Abreviatura || abreviaturaFija); } catch (e) {}
                    const span = th.querySelector('span');
                    if (span) {
                        const etiquetaPersonalizada = (colIndex && HEADER_LABEL_OVERRIDE_BY_COL[colIndex]) ? HEADER_LABEL_OVERRIDE_BY_COL[colIndex] : '';
                        span.textContent = etiquetaPersonalizada || (found.Abreviatura || abreviaturaFija);
                    }
                }
            });
        } catch (e) {
            console.warn('[RecepcionesExternas] updateFixedHeaders error', e);
        }
    }
    
    // Precargar logo para evitar parpadeo - crear una sola vez
    const baseUrl = (typeof window.BASE_URL !== 'undefined' && window.BASE_URL) ? window.BASE_URL : (window.location.origin + '/swlavoro/public');
    const logoUrl = baseUrl + '/img/Logo-Lavoro-1536x442.png';
    
    // Crear un elemento de imagen oculto en el DOM para forzar caché del navegador
    const logoImg = document.createElement('img');
    logoImg.src = logoUrl;
    logoImg.style.position = 'absolute';
    logoImg.style.left = '-9999px';
    logoImg.style.width = '1px';
    logoImg.style.height = '1px';
    document.body.appendChild(logoImg);
    
    // Cachear el HTML del logo para reutilizar sin recrear el elemento
    const logoHtmlCache = `<img src="${logoUrl}" style="height:32px; max-width:120px; display:block; object-fit:contain; will-change:auto;" alt="Logo Lavoro" />`;
    
    // Cargar productos y observaciones desde el backend
    function cargarDatosIniciales() {
        // Cargar productos (robusto)
        (function() {
            const url = (window.APP_URL || window.BASE_URL) + '/recepcionesexternas/obtenerProductos';
            console.log('[RecepcionesExternas] cargarProductos intentar endpoint:', url);
            fetch(url, { credentials: 'same-origin' })
                .then(r => r.text())
                .then(text => {
                    try {
                        const res = JSON.parse(text);
                        if (res && res.success && res.productos) {
                            productosData = res.productos;
                            productosLoaded = true;
                            window.productosData = productosData; // Actualizar referencia global
                            window.productosLoaded = true;
                            console.log('[RecepcionesExternas] Productos cargados:', productosData.length);
                            console.log('[RecepcionesExternas] Ejemplo producto:', productosData[0]); // Ver estructura
                            try { poblarSelectsProductos(); poblarSelectsCodObs(); updateFixedHeaders(); } catch (e) {}
                            try { actualizarVistaPrevia(); } catch(e) {}
                        } else {
                            console.warn('[RecepcionesExternas] obtenerProductos respuesta inesperada:', res, 'raw:', text.slice(0,300));
                        }
                    } catch (e) {
                        console.error('[RecepcionesExternas] obtenerProductos returned non-JSON (possible HTML):', text.slice(0,300));
                    }
                })
                .catch(function(err) { console.warn('[RecepcionesExternas] Error al cargar productos:', err); });
        })();

        // Cargar observaciones (robusto)
        (function() {
            const url = (window.APP_URL || window.BASE_URL) + '/recepcionesexternas/obtenerObservaciones';
            console.log('[RecepcionesExternas] cargarObservaciones intentar endpoint:', url);
            fetch(url, { credentials: 'same-origin' })
                .then(r => r.text())
                .then(text => {
                    try {
                        const res = JSON.parse(text);
                        if (res && res.success && res.observaciones) {
                            observacionesData = res.observaciones;
                            window.observacionesData = observacionesData; // Actualizar referencia global
                            console.log('[RecepcionesExternas] Observaciones cargadas:', observacionesData.length);
                            // Poblar cualquier select de observaciones ya creado
                            try { poblarSelectsObservaciones(); } catch (e) {}
                        } else {
                            console.warn('[RecepcionesExternas] obtenerObservaciones respuesta inesperada:', res, 'raw:', text.slice(0,300));
                        }
                    } catch (e) {
                        console.error('[RecepcionesExternas] obtenerObservaciones returned non-JSON (possible HTML):', text.slice(0,300));
                    }
                })
                .catch(function(err) { console.warn('[RecepcionesExternas] Error al cargar observaciones:', err); });
        })();
    }
    
    // Configurar eventos para gestión de guías
    function setupGuiasManager() {
        // Botones de Agregar/Quitar Guía ya no se usan (la lógica se mantiene pero los botones están ocultos)
        // const btnAgregarFila = document.getElementById('btnAgregarFilaGuia');
        // const btnQuitar = document.getElementById('btnQuitarFilaGuia');
        
        const btnAgregarCol = document.getElementById('btnAgregarColumnaProducto');
        const btnEliminarCol = document.getElementById('btnEliminarColumnaProducto');
        
        // Las filas ahora se agregan/eliminan automáticamente al escribir/borrar N° Guía
        // if (btnAgregarFila) {
        //     btnAgregarFila.addEventListener('click', agregarFilaGuia);
        // }
        // 
        // if (btnQuitar) {
        //     btnQuitar.addEventListener('click', quitarUltimaGuia);
        // }
        
        if (btnAgregarCol) {
            btnAgregarCol.addEventListener('click', agregarColumnaProducto);
        }
        
        if (btnEliminarCol) {
            btnEliminarCol.addEventListener('click', eliminarUltimaColumnaProducto);
        }
        
        // Ocultar botones de agregar/quitar guía ya que ahora es automático
        const btnAgregarFilaHide = document.getElementById('btnAgregarFilaGuia');
        const btnQuitarHide = document.getElementById('btnQuitarFilaGuia');
        if (btnAgregarFilaHide) btnAgregarFilaHide.style.display = 'none';
        if (btnQuitarHide) btnQuitarHide.style.display = 'none';
        
        // Poblar selects de productos en headers
        poblarSelectsProductos();
        try { poblarSelectsCodObs(); } catch (e) {}
        
        // Iniciar con una sola fila; se agregan automáticamente al llenar cant GR
        agregarFilaGuia();
        
        // Asegurar índices correctos
        try { refreshColumnIndices(); } catch (e) {}
        
        // Inicializar funcionalidad de colapsar/expandir columnas de productos
        inicializarColapsarColumnas();
    }
    
    // Sistema de colapsar/expandir columnas de productos
    const columnasColapsadas = new Set(); // Mantener estado de columnas colapsadas
    
    function inicializarColapsarColumnas() {
        // Delegación de eventos en el header de la tabla
        const headerRow = document.getElementById('headerGrillaGuiasPrincipales');
        if (!headerRow) return;
        
        headerRow.addEventListener('click', function(e) {
            const btnCollapse = e.target.closest('.btn-collapse-col');
            const btnExpand = e.target.closest('.btn-expand-col');
            
            if (btnCollapse) {
                const productoCol = btnCollapse.getAttribute('data-producto-col');
                colapsarColumna(productoCol);
            } else if (btnExpand) {
                const productoCol = btnExpand.getAttribute('data-producto-col');
                expandirColumna(productoCol);
            }
        });
    }
    
    function colapsarColumna(productoCol) {
        if (!productoCol) return;
        columnasColapsadas.add(productoCol);
        
        const tabla = document.getElementById('tablaGrillaGuias');
        if (!tabla) return;
        
        // Obtener el th principal y las subcolumnas
        const thPrincipal = tabla.querySelector(`th.col-producto[data-producto-col="${productoCol}"]`);
        if (!thPrincipal) return;
        
        const productoFijo = thPrincipal.getAttribute('data-producto-fijo') || '';
        const bg = thPrincipal.style.backgroundColor || '#f0f0f0';
        
        // Ocultar el th principal
        thPrincipal.style.display = 'none';
        
        // Obtener índice de columna en la fila de headers principales (para calcular qué subcolumnas ocultar)
        const headerRow = document.getElementById('headerGrillaGuiasPrincipales');
        const allProductHeaders = Array.from(headerRow.querySelectorAll('.col-producto'));
        const colIndex = allProductHeaders.indexOf(thPrincipal);
        
        // Ocultar las 3 subcolumnas correspondientes en la segunda fila de headers
        const subheaderRow = document.getElementById('headerGrillaGuiasSubcabeceras');
        if (subheaderRow) {
            // Usar data-producto-col para encontrar las subcolumnas correctas
            const subcolsEnSubcabecera = subheaderRow.querySelectorAll(`.subcol[data-producto-col="${productoCol}"]`);
            subcolsEnSubcabecera.forEach(function(subcol) {
                subcol.style.display = 'none';
            });
        }
        
        // Ocultar las 3 celdas correspondientes en todas las filas del tbody
        const tbody = document.getElementById('tbodyGrillaGuias');
        if (tbody) {
            const filas = tbody.querySelectorAll('.fila-guia');
            filas.forEach(function(fila) {
                // Usar data-producto-col para encontrar las celdas correctas
                const celdasProducto = fila.querySelectorAll(`[data-producto-col="${productoCol}"]`);
                celdasProducto.forEach(function(celda) {
                    celda.style.display = 'none';
                });
            });
        }
        
        // Crear columna placeholder con botón [+] en el header principal
        const thPlaceholder = document.createElement('th');
        thPlaceholder.className = 'col-producto-collapsed';
        thPlaceholder.setAttribute('data-producto-col', productoCol);
        thPlaceholder.setAttribute('data-producto-fijo', productoFijo);
        thPlaceholder.style.cssText = `vertical-align:middle; text-align:center; padding:2px; background:${bg}; width:25px; min-width:25px; max-width:25px;`;
        thPlaceholder.innerHTML = `<button type="button" class="btn-expand-col" data-producto-col="${productoCol}" title="Mostrar columna" style="cursor:pointer; border:none; background:transparent; font-size:0.7rem; padding:0;">[+]</button>`;
        
        // Insertar el placeholder después del th oculto
        thPrincipal.parentNode.insertBefore(thPlaceholder, thPrincipal.nextSibling);
        
        // Crear placeholders en subheader y tbody
        if (subheaderRow) {
            const firstHiddenSubCol = subheaderRow.querySelector(`.subcol[data-producto-col="${productoCol}"]`);
            if (firstHiddenSubCol) {
                const thSubPlaceholder = document.createElement('th');
                thSubPlaceholder.className = 'subcol-collapsed';
                thSubPlaceholder.setAttribute('data-producto-col', productoCol);
                thSubPlaceholder.style.cssText = `width:25px; min-width:25px; max-width:25px; background:${bg};`;
                thSubPlaceholder.innerHTML = '&nbsp;';
                firstHiddenSubCol.parentNode.insertBefore(thSubPlaceholder, firstHiddenSubCol);
            }
        }
        
        if (tbody) {
            const filas = tbody.querySelectorAll('.fila-guia');
            filas.forEach(function(fila) {
                const firstCellProducto = fila.querySelector(`.celda-producto-cantidad[data-producto-col="${productoCol}"]`);
                if (firstCellProducto) {
                    const tdPlaceholder = document.createElement('td');
                    tdPlaceholder.className = 'cell-collapsed';
                    tdPlaceholder.setAttribute('data-producto-col', productoCol);
                    tdPlaceholder.style.cssText = `width:25px; min-width:25px; max-width:25px; background:${bg}; opacity:0.3;`;
                    tdPlaceholder.innerHTML = '&nbsp;';
                    firstCellProducto.parentNode.insertBefore(tdPlaceholder, firstCellProducto);
                }
            });
        }
    }
    
    function expandirColumna(productoCol) {
        if (!productoCol) return;
        columnasColapsadas.delete(productoCol);
        
        const tabla = document.getElementById('tablaGrillaGuias');
        if (!tabla) return;
        
        // Remover placeholder del header principal
        const thPlaceholder = tabla.querySelector(`th.col-producto-collapsed[data-producto-col="${productoCol}"]`);
        if (thPlaceholder) thPlaceholder.remove();
        
        // Mostrar el th principal
        const thPrincipal = tabla.querySelector(`th.col-producto[data-producto-col="${productoCol}"]`);
        if (thPrincipal) {
            thPrincipal.style.display = '';
            
            // Obtener índice
            const headerRow = document.getElementById('headerGrillaGuiasPrincipales');
            const allProductHeaders = Array.from(headerRow.querySelectorAll('.col-producto'));
            const colIndex = allProductHeaders.indexOf(thPrincipal);
            
            // Mostrar las 3 subcolumnas en el subheader
            const subheaderRow = document.getElementById('headerGrillaGuiasSubcabeceras');
            if (subheaderRow) {
                // Remover placeholder
                const subPlaceholder = subheaderRow.querySelector(`.subcol-collapsed[data-producto-col="${productoCol}"]`);
                if (subPlaceholder) subPlaceholder.remove();
                
                // Mostrar subcolumnas usando data-producto-col
                const subcolsEnSubcabecera = subheaderRow.querySelectorAll(`.subcol[data-producto-col="${productoCol}"]`);
                subcolsEnSubcabecera.forEach(function(subcol) {
                    subcol.style.display = '';
                });
            }
            
            // Mostrar las 3 celdas en todas las filas del tbody
            const tbody = document.getElementById('tbodyGrillaGuias');
            if (tbody) {
                const filas = tbody.querySelectorAll('.fila-guia');
                filas.forEach(function(fila) {
                    // Remover placeholder
                    const cellPlaceholder = fila.querySelector(`.cell-collapsed[data-producto-col="${productoCol}"]`);
                    if (cellPlaceholder) cellPlaceholder.remove();
                    
                    // Mostrar celdas usando data-producto-col
                    const celdasProducto = fila.querySelectorAll(`[data-producto-col="${productoCol}"]`);
                    celdasProducto.forEach(function(celda) {
                        celda.style.display = '';
                    });
                });
            }
        }
    }
    
    // Poblar selects de productos en los headers (muestra ABREVIATURA como texto, value sigue siendo el Código)
    // Solo pobla los selects de columnas NO fijas (a partir de la 5ta)
    function poblarSelectsProductos() {
        // Construir opciones: por defecto mostramos el nombre completo del producto (Producto)
        // y guardamos la abreviatura en data-abre. Al abrir el select mostraremos las abreviaturas.
        // Limitar los productos disponibles en las columnas adicionales a estas tres opciones
        var allowedProductNames = [
            'PARIHUELA PLASTICA AZUL',
            'PARIHUELA REVERSIBLE MADERA 1.12M X 1.16M X 15CM',
            'PARIHUELA NEW ZELAND - ESTANDAR'
        ];

        let optsProductos = '<option value="">Producto</option>';

        optsProductos = buildAllowedProductOptions();

        // Solo poblar los selects que NO son fijos (los que tienen clase select-header-producto)
        const selectsHeader = document.querySelectorAll('.select-header-producto');
        selectsHeader.forEach(function(select) {
            select.innerHTML = optsProductos;

            // Al abrir (focus) mostrar NOMBRE completo en el dropdown y ocultar opciones de productos fijos o ya usados
            select.addEventListener('focus', function() {
                try {
                    const fixedNames = getFixedProductNames();
                    const usedNames = getSelectedHeaderNames(select);
                    Array.from(select.options).forEach(function(opt) {
                        const nombreOpt = (opt.getAttribute('data-nombre') || '').toString().trim();
                        if (nombreOpt) opt.text = nombreOpt;

                        // Ocultar opciones cuyo campo Producto coincide con uno de los fijos o ya seleccionados en otras cabeceras
                        const nombreNorm = nombreOpt.toUpperCase();
                        if (nombreNorm && nombreNorm !== (select.options[select.selectedIndex]?.getAttribute('data-nombre') || '').toString().trim().toUpperCase() && (fixedNames.indexOf(nombreNorm) >= 0 || usedNames.indexOf(nombreNorm) >= 0)) {
                            try { opt.hidden = true; opt.disabled = true; } catch (e) {}
                        } else {
                            try { opt.hidden = false; opt.disabled = false; } catch (e) {}
                        }
                    });
                } catch (e) {}
            });

            // Al perder foco, restaurar visibilidad y textos: la opción seleccionada mostrará su abreviatura (si aplica), las demás mostrarán nombre
            select.addEventListener('blur', function() {
                try {
                    Array.from(select.options).forEach(function(opt) {
                        try { opt.hidden = false; opt.disabled = false; } catch (e) {}
                        const nombre = opt.getAttribute('data-nombre') || '';
                        const abre = opt.getAttribute('data-abre') || '';
                        if (opt.selected && opt.getAttribute('data-selected-abre') === '1') {
                            opt.text = abre || nombre || opt.value;
                        } else {
                            opt.text = nombre || opt.value;
                        }
                    });
                } catch (e) {}
            });

            // Al cambiar selección, marcar la opción seleccionada para mostrar su abreviatura en la cabecera
            select.addEventListener('change', function() {
                try {
                    Array.from(select.options).forEach(function(opt) { opt.removeAttribute('data-selected-abre'); });
                    const sel = select.options[select.selectedIndex];
                    if (sel && (sel.getAttribute('data-abre') || '').trim() !== '') {
                        sel.setAttribute('data-selected-abre', '1');
                        // Mostrar la abreviatura inmediatamente en el select visible
                        sel.text = sel.getAttribute('data-abre') || sel.getAttribute('data-nombre') || sel.value;
                    }
                } catch (e) {}

                actualizarVistaPrevia();
                try { poblarSelectsCodObs(); } catch (e) {}
            });
        });
    }

    // Helper para escapar texto dentro de atributos/HTML simple
    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Generar el texto de observacion para un producto especifico (misma logica que actualizarVistaPrevia)
    function generarTextoObservacionProducto(obsText, cantObs, codigo, numeroGuia, colIndex, ptDeLe) {
        if (!obsText || !cantObs || cantObs <= 0 || !numeroGuia) return '';
        var lowerObs = (obsText || '').toLowerCase();
        var codigoDisplay = codigo;
        
        if ((lowerObs === 'de' || lowerObs.includes('deja') || lowerObs === 'le' || lowerObs.includes('lleva'))) {
            try {
                var exiProducto = (window.productosData || []).find(function(p) {
                    var abre = (p.Abreviatura || '').toString().toUpperCase();
                    var prod = (p.Producto || '').toString().trim().toUpperCase();
                    return abre === 'EXI' || prod === 'PARIHUELA EX IMPORTACION - ESTANDAR';
                });
                if (exiProducto && exiProducto.Codigo) codigoDisplay = exiProducto.Codigo.toString();
            } catch(e) {}
        }
        
        var esJaba = (colIndex == 3 || colIndex == 4);
        if (!esJaba && codigo) {
            try {
                var headerFijo = document.querySelector('.col-producto-fija[data-producto-col="' + colIndex + '"]');
                if (headerFijo) {
                    var prodNombre = (headerFijo.getAttribute('data-producto-fijo') || '').toUpperCase();
                    if (prodNombre.indexOf('J') === 0 || prodNombre.indexOf('JAB') >= 0) esJaba = true;
                }
            } catch(e) {}
        }
        
        if (lowerObs === 'p' || lowerObs.includes('pend'))
            return 'PENDIENTE ' + cantObs + ' UND DEL CODIGO ' + codigoDisplay + ' GUIA ' + numeroGuia;
        else if (lowerObs === 'de' || lowerObs.includes('deja'))
            return 'DEJA ' + cantObs + (esJaba ? ' UND OBSERVADA/AS' : ' UND') + ' DEL CODIGO ' + codigoDisplay + ' GUIA ' + numeroGuia;
        else if (lowerObs === 'le' || lowerObs.includes('lleva'))
            return 'LLEVA ' + cantObs + (esJaba ? ' UND OBSERVADA/AS' : ' UND') + ' DEL CODIGO ' + codigoDisplay + ' GUIA ' + numeroGuia;
        else if (lowerObs === 'a' || lowerObs.includes('adici'))
            return 'ADICIONAL ' + cantObs + ' UND DEL CODIGO ' + codigoDisplay + ' GUIA ' + numeroGuia;
        else if (lowerObs === 'r' || lowerObs.includes('regular'))
            return 'REGULARIZA ' + cantObs + ' UND DEL CODIGO ' + codigoDisplay + ' GUIA ' + numeroGuia;
        else if (lowerObs === 'pt' || lowerObs.includes('producto terminado')) {
            if (ptDeLe === 'DE') return 'DEJA ' + cantObs + ' UND CON PT A';
            else if (ptDeLe === 'LE') return 'LLEVA ' + cantObs + ' UND CON PT A';
            return 'PT ' + cantObs + ' UND DEL CODIGO ' + codigoDisplay + ' GUIA ' + numeroGuia;
        }
        
        return '';
    }

    // Construir las opciones permitidas para los selects de productos adicionales
    function buildAllowedProductOptions() {
        var allowedProductNames = [
            'PARIHUELA PLASTICA AZUL',
            'PARIHUELA REVERSIBLE MADERA 1.12M X 1.16M X 15CM',
            'PARIHUELA NEW ZELAND - ESTANDAR'
        ];

        let optsProductos = '<option value="">Producto</option>';

        var filtered = (productosData || []).filter(function(p) {
            try { return allowedProductNames.indexOf((p.Producto || '').toString().trim().toUpperCase()) >= 0; }
            catch(e) { return false; }
        });

        if (filtered.length > 0) {
            filtered.forEach(function(p) {
                const codigo = (p.Codigo || '').toString();
                const nombre = (p.Producto || '').toString();
                const abre = (p.Abreviatura || '').toString();
                optsProductos += '<option value="' + codigo + '" data-nombre="' + escapeHtml(nombre) + '" data-abre="' + escapeHtml(abre) + '">' + escapeHtml(nombre) + '</option>';
            });
        } else {
            allowedProductNames.forEach(function(name) {
                optsProductos += '<option value="" data-nombre="' + escapeHtml(name) + '">' + escapeHtml(name) + '</option>';
            });
        }

        return optsProductos;
    }

    // Devuelve array de códigos correspondientes a los 4 productos fijos (si existen en productosData)
    function getFixedProductCodes() {
        const codes = [];
        try {
            if (!productosData || !productosData.length) return codes;
            Object.keys(FIXED_PRODUCTOS_BY_COL).forEach(function(col) {
                const targetName = FIXED_PRODUCTOS_BY_COL[col];
                if (!targetName) return;
                const found = productosData.find(function(p) { return (p.Producto || '').toString().trim().toUpperCase() === targetName.toUpperCase(); });
                if (found && found.Codigo) codes.push((found.Codigo || '').toString());
            });
        } catch (e) {}
        return codes;
    }

    // Devuelve códigos seleccionados en los selects de cabecera, excluyendo el select actual (opcional)
    function getSelectedHeaderCodes(excludeSelect) {
        const codes = [];
        try {
            const selects = document.querySelectorAll('.select-header-producto');
            selects.forEach(function(s) {
                if (excludeSelect && s === excludeSelect) return;
                const v = (s.value || '').toString();
                if (v) codes.push(v);
            });
        } catch (e) {}
        return codes;
    }

    // Devuelve array de nombres (Producto) correspondientes a los 4 productos fijos (normalizados)
    function getFixedProductNames() {
        const names = [];
        try {
            if (!productosData || !productosData.length) return names;
            Object.keys(FIXED_PRODUCTOS_BY_COL).forEach(function(col) {
                const targetName = FIXED_PRODUCTOS_BY_COL[col];
                if (!targetName) return;
                // Buscar el producto por nombre (insensible a mayúsculas)
                const found = productosData.find(function(p) { return (p.Producto || '').toString().trim().toUpperCase() === targetName.toUpperCase(); });
                if (found && found.Producto) names.push(((found.Producto || '').toString().trim()).toUpperCase());
            });
        } catch (e) {}
        return names;
    }

    // Devuelve nombres (Producto) seleccionados en los selects de cabecera, excluyendo el select actual (opcional)
    function getSelectedHeaderNames(excludeSelect) {
        const names = [];
        try {
            const selects = document.querySelectorAll('.select-header-producto');
            selects.forEach(function(s) {
                if (excludeSelect && s === excludeSelect) return;
                const sel = s.options[s.selectedIndex];
                const nombre = sel ? (sel.getAttribute('data-nombre') || '') : '';
                if (nombre && nombre.trim() !== '') names.push(nombre.toString().trim().toUpperCase());
            });
        } catch (e) {}
        return names;
    }

    // Poblar selects Cod. Obs. en cada fila (lista de códigos disponibles desde los headers)
    function poblarSelectsCodObs() {
        // Construir opciones a partir de las columnas fijas y los selects del header (mostrar el texto visible del header, normalmente la abreviatura)
        let opts = '<option value=""></option>';
        
        // Primero las columnas fijas
        const columnasFijas = document.querySelectorAll('.col-producto-fija');
        columnasFijas.forEach(function(th) {
            const abreviaturaFija = th.getAttribute('data-producto-fijo') || '';
            const colIndex = parseInt(th.getAttribute('data-producto-col')) || null;
            if (abreviaturaFija) {
                // Buscar el código del producto primero por nombre completo (mapeo FIXED_PRODUCTOS_BY_COL), luego por Abreviatura
                let codigo = '';
                let texto = abreviaturaFija;
                try {
                    let found = null;
                    
                    // Primero intentar por nombre completo usando el mapeo por columna
                    if (colIndex && FIXED_PRODUCTOS_BY_COL[colIndex]) {
                        const targetName = FIXED_PRODUCTOS_BY_COL[colIndex];
                        found = productosData.find(function(p) { return (p.Producto || '').toString().trim().toUpperCase() === targetName.toUpperCase(); });
                    }
                    
                    // Si no se encontró por nombre, intentar por Abreviatura (fallback)
                    if (!found) {
                        found = productosData.find(function(p) { 
                            return (p.Abreviatura || '').toString().toUpperCase() === abreviaturaFija.toUpperCase(); 
                        });
                    }

                    if (found) {
                        codigo = (found.Codigo || '').toString();
                        texto = found.Abreviatura || texto;
                    }
                } catch (e) {}

                if (codigo) {
                    opts += '<option value="' + codigo + '">' + texto + '</option>';
                }
            }
        });
        
        // Luego los selects dinámicos
        const selectsHeader = document.querySelectorAll('.select-header-producto');
        selectsHeader.forEach(function(s) {
            const code = (s.value || '').toString();
            if (code) {
                let texto = code;
                try {
                    texto = (s.options[s.selectedIndex] && s.options[s.selectedIndex].text) ? s.options[s.selectedIndex].text : code;
                } catch (e) {}
                opts += '<option value="' + code + '">' + texto + '</option>';
            }
        });

        // Si no hay códigos, mantener una opción vacía
        const selectsCodObs = document.querySelectorAll('.select-cod-obs');
        selectsCodObs.forEach(function(sel) {
            const prev = sel.value || '';
            sel.innerHTML = opts;
            // volver a intentar setear el valor si existía y sigue disponible
            try { if (prev) sel.value = prev; } catch(e){}
            sel.addEventListener('change', actualizarVistaPrevia);
        });
    }

    // Poblar selects de observaciones (campo Item)
    function poblarSelectsObservaciones() {
        let optsObs = '<option value=""></option>';
        observacionesData.forEach(function(o) {
            const item = (o.Item || '').toString();
            optsObs += '<option value="' + (o.Id || '') + '">' + item + '</option>';
        });

        const selectsObs = document.querySelectorAll('.select-obs, .select-obs-producto');
        selectsObs.forEach(function(s) {
            const prev = s.value || '';
            s.innerHTML = optsObs;
            try { if (prev) s.value = prev; } catch(e){}
        });
    }
    
    // Agregar nueva fila de guía
    // ─── Helpers: info persistente dentro de la tabla por fila ────────────────
    /**
     * Inserta (o reemplaza) una sub-fila informativa justo debajo de filaTR.
     * tipo: 'warning' (aviso, permite registrar) | 'error' (bloqueo)
     */
    function mostrarInfoEnFila(filaTR, htmlContenido, tipo) {
        try {
            limpiarInfoEnFila(filaTR);
            const numCols = filaTR.querySelectorAll('td').length || 20;
            const infoRow = document.createElement('tr');
            infoRow.className = 'guia-info-row';
            infoRow.setAttribute('data-for-fila', filaTR.id || '');
            const td = document.createElement('td');
            td.colSpan = numCols;
            td.style.cssText = tipo === 'warning'
                ? 'padding:5px 12px; background:#fef3c7; border-left:4px solid #f59e0b; border-bottom:1px solid #f59e0b; font-size:0.75rem; color:#78350f;'
                : 'padding:5px 12px; background:#fee2e2; border-left:4px solid #ef4444; border-bottom:1px solid #ef4444; font-size:0.75rem; color:#7f1d1d;';
            td.innerHTML = htmlContenido;
            infoRow.appendChild(td);
            filaTR.insertAdjacentElement('afterend', infoRow);
        } catch (e) { console.warn('mostrarInfoEnFila error', e); }
    }

    /** Elimina la sub-fila informativa de filaTR si existe */
    function limpiarInfoEnFila(filaTR) {
        try {
            const siguiente = filaTR.nextElementSibling;
            if (siguiente && siguiente.classList.contains('guia-info-row')) {
                siguiente.remove();
            }
        } catch (e) {}
    }

    /** Construye el HTML del panel de bloqueo */
    function _buildHtmlBloqueo(numeroGuia, registros) {
        const primerReg = registros[0] || {};
        const esRegularizada = (primerReg.obsItem || '') === 'R';
        let motivo = esRegularizada
            ? '⛔ La guía <strong>' + numeroGuia + '</strong> ya fue <strong>REGULARIZADA</strong> y no puede volver a registrarse.'
            : '⛔ La guía <strong>' + numeroGuia + '</strong> ya está completamente registrada y no puede volver a ingresarse.';

        let detalle = '';
        registros.forEach(function(reg) {
            const partes = [];
            if (reg.fecha)             partes.push('Fecha: <strong>' + reg.fecha + '</strong>');
            if (reg.turno)             partes.push('Turno: <strong>' + reg.turno + '</strong>');

            // Si viene con el nuevo array 'productos[]', mostrar TODOS los productos con obs
            if (reg.productos && reg.productos.length > 0) {
                var prodsHtml = '';
                reg.productos.forEach(function(prod) {
                    var prodPartes = [];
                    if (prod.codigo)       prodPartes.push('Cód: <strong>' + prod.codigo + '</strong>');
                    if (prod.cantidad > 0) prodPartes.push('Cant: <strong>' + prod.cantidad + '</strong>');
                    if (prod.obsItem)      prodPartes.push('Obs: <strong>' + prod.obsItem + '</strong>');
                    if (prod.cantObs > 0)  prodPartes.push('Cant Obs: <strong>' + prod.cantObs + '</strong>');
                    if (prodPartes.length) {
                        prodsHtml += '<div style="margin-top:2px; padding-left:10px; font-size:0.85em;">• ' + prodPartes.join(' · ') + '</div>';
                    }
                });
                if (prodsHtml) {
                    partes.push('Productos:');
                    detalle += '<div style="margin-top:4px; padding-left:6px; border-left:2px solid #fca5a5;">'
                        + partes.join(' &nbsp;·&nbsp; ')
                        + prodsHtml
                        + '</div>';
                    return; // Saltar el bloque genérico de abajo
                }
            }

            // Fallback: si NO hay productos[] (backward compatibility con datos antiguos)
            if (reg.codigoProductoObs) partes.push('Cód: <strong>' + reg.codigoProductoObs + '</strong>');
            if (reg.cantObs > 0)       partes.push('Cant Obs: <strong>' + reg.cantObs + '</strong>');
            if (reg.obsItem)           partes.push('Obs: <strong>' + reg.obsItem + '</strong>');

            if (partes.length) {
                detalle += '<div style="margin-top:4px; padding-left:6px; border-left:2px solid #fca5a5;">' + partes.join(' &nbsp;·&nbsp; ') + '</div>';
            }
        });
        return motivo + detalle;
    }

    /** Construye el HTML del panel de advertencia (permite registrar) */
    function _buildHtmlAdvertencia(numeroGuia, registros) {
        let cabecera = '⚠ La guía <strong>' + numeroGuia + '</strong> ya existe con observaciones pendientes. Puede registrarla nuevamente.';
        let detalle = '';
        registros.forEach(function(reg) {
            const partes = [];
            if (reg.fecha)             partes.push('Fecha: <strong>' + reg.fecha + '</strong>');
            if (reg.turno)             partes.push('Turno: <strong>' + reg.turno + '</strong>');

            // Si viene con el nuevo array 'productos[]', mostrar TODOS los productos con obs
            if (reg.productos && reg.productos.length > 0) {
                var prodsHtml = '';
                reg.productos.forEach(function(prod) {
                    var prodPartes = [];
                    if (prod.codigo)       prodPartes.push('Cód: <strong>' + prod.codigo + '</strong>');
                    if (prod.cantidad > 0) prodPartes.push('Cant: <strong>' + prod.cantidad + '</strong>');
                    if (prod.obsItem)      prodPartes.push('Obs: <strong>' + prod.obsItem + '</strong>');
                    if (prod.cantObs > 0)  prodPartes.push('Cant Obs: <strong>' + prod.cantObs + '</strong>');
                    if (prodPartes.length) {
                        prodsHtml += '<div style="margin-top:2px; padding-left:10px; font-size:0.85em;">• ' + prodPartes.join(' · ') + '</div>';
                    }
                });
                if (prodsHtml) {
                    detalle += '<div style="margin-top:4px; padding-left:6px; border-left:2px solid #fcd34d;">📋 ' + partes.join(' &nbsp;·&nbsp; ') + prodsHtml + '</div>';
                    return; // Saltar el bloque genérico de abajo
                }
            }

            // Fallback: si NO hay productos[] (backward compatibility con datos antiguos)
            if (reg.codigoProductoObs) partes.push('Cód: <strong>' + reg.codigoProductoObs + '</strong>');
            if (reg.cantObs > 0)       partes.push('Cant Obs: <strong>' + reg.cantObs + '</strong>');
            if (reg.obsItem)           partes.push('Obs: <strong>' + reg.obsItem + '</strong>');

            if (partes.length) {
                detalle += '<div style="margin-top:4px; padding-left:6px; border-left:2px solid #fcd34d;">📋 ' + partes.join(' &nbsp;·&nbsp; ') + '</div>';
            }
        });
        return cabecera + detalle;
    }
    // ─────────────────────────────────────────────────────────────────────────

    // Verificar si la guía (serie + correlativo) ya existe en la BD
    // Verificar si la guía (serie + correlativo) ya existe en la BD
    function verificarGuiaDuplicada(filaTR) {
        try {
            const serieEl  = filaTR.querySelector('.select-serie-guia') || filaTR.querySelector('.input-serie-guia');
            const correlEl = filaTR.querySelector('.input-correlativo-guia');
            if (!serieEl || !correlEl) return;
            const serie      = (serieEl.value  || '').trim().toUpperCase();
            const correlativo = (correlEl.value || '').replace(/\D/g, '').trim();
            if (!serie || !correlativo) return;

            const url = (window.APP_URL || window.BASE_URL) + '/recepcionesexternas/verificarGuia?serie='
                + encodeURIComponent(serie) + '&correlativo=' + encodeURIComponent(correlativo);

            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) return;

                    if (!data.existe) {
                        // Guía nueva: limpiar estados
                        serieEl.classList.remove('is-invalid');
                        correlEl.classList.remove('is-invalid');
                        limpiarInfoEnFila(filaTR);
                        return;
                    }

                    if (data.bloqueado) {
                        // ── BLOQUEO: guía ya recibida plena o regularizada ──────────
                        serieEl.classList.add('is-invalid');
                        correlEl.classList.add('is-invalid');
                        filaTR.querySelectorAll('input, select').forEach(function(inp) {
                            if (inp !== serieEl && inp !== correlEl) {
                                inp.setAttribute('disabled', 'disabled');
                                inp.setAttribute('data-blocked', 'duplicate');
                            }
                        });
                        mostrarInfoEnFila(filaTR, _buildHtmlBloqueo(data.numeroGuia, data.registros || []), 'error');
                        showFloatingMessage('La guía ' + data.numeroGuia + ' ya está registrada y no puede volver a ingresarse.', 'error');
                        try { correlEl.focus(); correlEl.select(); } catch(e) {}

                    } else {
                        // ── AVISO: tiene obs pendientes, se permite registrar ────────
                        serieEl.classList.remove('is-invalid');
                        correlEl.classList.remove('is-invalid');
                        // Desbloquear campos por si estaban bloqueados de una verificación anterior
                        filaTR.querySelectorAll('[data-blocked="duplicate"]').forEach(function(inp) {
                            inp.removeAttribute('disabled');
                            inp.removeAttribute('data-blocked');
                        });
                        mostrarInfoEnFila(filaTR, _buildHtmlAdvertencia(data.numeroGuia, data.registros || []), 'warning');
                    }
                })
                .catch(function(err) {
                    console.warn('[RecepcionesExternas] Error al verificar guía duplicada:', err);
                });
        } catch(e) { console.warn('verificarGuiaDuplicada error', e); }
    }

    // Limpiar bloqueo de fila originado por guía duplicada
    function desbloquearFilaGuia(filaTR) {
        try {
            const serieEl  = filaTR.querySelector('.select-serie-guia') || filaTR.querySelector('.input-serie-guia');
            const correlEl = filaTR.querySelector('.input-correlativo-guia');
            if (serieEl) serieEl.classList.remove('is-invalid');
            if (correlEl) correlEl.classList.remove('is-invalid');
            filaTR.querySelectorAll('[data-blocked="duplicate"]').forEach(function(inp) {
                inp.removeAttribute('disabled');
                inp.removeAttribute('data-blocked');
            });
            // Limpiar también el panel informativo persistente
            limpiarInfoEnFila(filaTR);
        } catch(e) { console.warn('desbloquearFilaGuia error', e); }
    }

    function agregarFilaGuia() {
        const tbody = document.getElementById('tbodyGrillaGuias');
        if (!tbody) return;
        
        const rowId = 'guiaRow_' + Date.now();
        const numColumnasProducto = document.querySelectorAll('.col-producto').length;
        
        // Construir opciones de observaciones
        let optsObs = '<option value=""></option>';
        observacionesData.forEach(function(o) {
            optsObs += '<option value="' + o.Id + '">' + o.Item + '</option>';
        });
        // Construir opciones de series (desde window.seriesData)
        let optsSeries = '<option value=""></option>';
        try {
            (window.seriesData || []).forEach(function(s) {
                // usar el valor de la columna 'Serie' como value y etiqueta
                const val = (s.Serie !== undefined) ? s.Serie : (s.SERIE !== undefined ? s.SERIE : '');
                optsSeries += '<option value="' + (val || '') + '">' + (val || '') + '</option>';
            });
        } catch (e) { optsSeries = optsSeries; }
        
        const tr = document.createElement('tr');
        tr.id = rowId;
        tr.className = 'fila-guia';
        
        let html = `
            <td style="padding:4px;">
                <select class="form-select form-select-sm select-serie-guia" style="font-size:0.7rem; padding:2px 4px; height:24px; text-align:center; width:60px;">${optsSeries}</select>
            </td>
            <td style="padding:4px;">
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="7" class="form-control form-control-sm input-correlativo-guia" placeholder="" style="font-size:0.7rem; padding:2px 4px; height:24px; text-align:center; width:80px;">
            </td>
            <td style="padding:4px;">
                <input type="text" class="form-control form-control-sm input-doc-ref" placeholder="" style="font-size:0.7rem; padding:2px 4px; height:24px; text-align:center;">
            </td>
        `;
        
        // Agregar columnas de productos existentes (cada producto tiene 3 celdas: cantidad, obs+pt, cant obs)
        for (let i = 1; i <= numColumnasProducto; i++) {
            html += `
                <td style="padding:2px;" class="celda-producto-cantidad" data-producto-col="${i}">
                    <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control form-control-sm input-cant-producto" placeholder="" style="font-size:0.7rem; padding:2px 4px; height:22px; text-align:center;" data-producto-col="${i}">
                </td>
                <td style="padding:2px; position:relative;" class="celda-producto-obs" data-producto-col="${i}">
                    <div style="display:flex; align-items:center; gap:1px; white-space:nowrap;">
                        <select class="form-select form-select-sm select-obs-producto" data-producto-col="${i}" style="font-size:0.65rem; padding:1px 2px; height:22px; line-height:1; width:30px;">
                            ${optsObs}
                        </select>
                        <select class="form-select form-select-sm select-pt-de-le" data-producto-col="${i}" style="font-size:0.65rem; padding:1px 2px; height:22px; line-height:1; width:1px; min-width:0; border:none; background:transparent; position:absolute; left:-9999px;" tabindex="-1" aria-hidden="true">
                            <option value=""></option>
                            <option value="DE">DE</option>
                            <option value="LE">LE</option>
                        </select>
                    </div>
                </td>
                <td style="padding:2px;" class="celda-producto-cantobs" data-producto-col="${i}">
                    <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control form-control-sm input-cant-obs-producto" placeholder="" style="font-size:0.7rem; padding:2px 4px; height:22px; text-align:center;" data-producto-col="${i}">
                </td>
            `;
        }
        
        tr.innerHTML = html;
        tbody.appendChild(tr);

        // Attach correlativo pad listener for the newly created input
        try {
            var inputCor = tr.querySelector('.input-correlativo-guia');
            if (inputCor) {
                inputCor.addEventListener('blur', function() {
                    try {
                        var v = (this.value || '').toString().trim();
                        var digits = v.replace(/\D/g, '');
                        if (!digits) return;
                        if (digits.length < 7) {
                            this.value = digits.padStart(7, '0');
                        }
                    } catch(e) {}
                });
            }
        } catch(e) {}
        
        // Event listeners para actualizar totales y preview
        const inputsProducto = tr.querySelectorAll('.input-cant-producto');
        inputsProducto.forEach(function(input) {
            input.addEventListener('input', function() {
                try {
                    const col = input.getAttribute('data-producto-col');
                    const row = input.closest('tr');
                    const selectObs = row.querySelector(`.select-obs-producto[data-producto-col="${col}"]`);
                    const inputCantObs = row.querySelector(`.input-cant-obs-producto[data-producto-col="${col}"]`);
                    const val = parseInt(input.value) || 0;
                    if (val > 0) {
                        if (selectObs) selectObs.removeAttribute('disabled');
                    } else {
                        if (selectObs) { selectObs.value = ''; selectObs.setAttribute('disabled', 'disabled'); }
                        if (inputCantObs) { inputCantObs.value = ''; inputCantObs.setAttribute('disabled', 'disabled'); }
                    }
                } catch (e) {}
                // Resetear flag de edicion manual para que el textarea de observaciones se auto-poblar
                window._observacionesEditado = false;
                calcularTotales();
                actualizarVistaPrevia();
                // Gestión dinámica de filas: nueva fila al llenar cant GR, quitar filas vacías al borrar
                const tieneCantGr = Array.from(tr.querySelectorAll('.input-cant-producto'))
                    .some(function(i) { return (parseInt(i.value) || 0) > 0; });
                if (tieneCantGr) {
                    agregarFilaSiguienteSiEsNecesario(tr);
                } else {
                    limpiarFilasVacias(tr);
                }
            });
        });

        // Event listeners para los selects de obs por producto
        const selectsObsProducto = tr.querySelectorAll('.select-obs-producto');
        selectsObsProducto.forEach(function(select) {
            // iniciar deshabilitados hasta que haya cantidad
            try { select.setAttribute('disabled', 'disabled'); } catch(e) {}
            
            select.addEventListener('change', function() {
                try {
                    const col = select.getAttribute('data-producto-col');
                    const row = select.closest('tr');
                    const inputCantObs = row.querySelector(`.input-cant-obs-producto[data-producto-col="${col}"]`);
                    const selectPtDeLe = row.querySelector(`.select-pt-de-le[data-producto-col="${col}"]`);
                    const tdObs = row.querySelector(`.celda-producto-obs[data-producto-col="${col}"]`);
                    
                    var obsVal = select.value ? select.options[select.selectedIndex].text.trim().toUpperCase() : '';
                    if (obsVal === 'PT') {
                        // Limpiar badge previo
                        try { var oldB = row.querySelector('.celda-producto-obs[data-producto-col="' + col + '"] .pt-badge'); if(oldB) oldB.remove(); } catch(e) {}
                        // Cerrar popup PT previo si existe
                        document.querySelectorAll('.pt-sub-menu').forEach(function(el) { el.remove(); });
                        if (selectPtDeLe) selectPtDeLe.value = '';
                        // Crear popup flotante a la derecha
                        var popup = document.createElement('div');
                        popup.className = 'pt-sub-menu';
                        popup.setAttribute('data-pt-col', col);
                        popup.style.cssText = 'position:absolute;left:100%;top:0;z-index:9999;background:#fff;border:1px solid #999;border-radius:3px;box-shadow:0 2px 8px rgba(0,0,0,0.15);font-size:0.7rem;white-space:nowrap;';
                        
                        var optDe = document.createElement('div');
                        optDe.textContent = 'DE';
                        optDe.style.cssText = 'padding:3px 10px;cursor:pointer;border-bottom:1px solid #eee;';
                        // Funcion helper local para badge
                        function mostrarPtBadge(valor) {
                            try {
                                var td = row.querySelector('.celda-producto-obs[data-producto-col="' + col + '"]');
                                if (!td) return;
                                var oldB = td.querySelector('.pt-badge');
                                if (oldB) oldB.remove();
                                if (valor) {
                                    var b = document.createElement('span');
                                    b.className = 'pt-badge';
                                    b.textContent = '(' + valor + ')';
                                    b.style.cssText = 'display:inline-block;font-size:0.6rem;font-weight:700;color:#fff;background:#1a237e;border-radius:2px;padding:0 4px;margin-left:2px;line-height:16px;vertical-align:middle;';
                                    (td.querySelector('div') || td).appendChild(b);
                                }
                            } catch(e) {}
                        }
                        
                        optDe.addEventListener('click', function(e) {
                            e.stopPropagation();
                            if (selectPtDeLe) selectPtDeLe.value = 'DE';
                            if (inputCantObs) inputCantObs.removeAttribute('disabled');
                            this.parentElement.remove();
                            mostrarPtBadge('DE');
                            // Resetear flag de edicion manual para que el textarea de observaciones se auto-poblar
                            window._observacionesEditado = false;
                            calcularTotales();
                            actualizarVistaPrevia();
                        });
                        
                        var optLe = document.createElement('div');
                        optLe.textContent = 'LE';
                        optLe.style.cssText = 'padding:3px 10px;cursor:pointer;';
                        optLe.addEventListener('click', function(e) {
                            e.stopPropagation();
                            if (selectPtDeLe) selectPtDeLe.value = 'LE';
                            if (inputCantObs) inputCantObs.removeAttribute('disabled');
                            this.parentElement.remove();
                            mostrarPtBadge('LE');
                            // Resetear flag de edicion manual para que el textarea de observaciones se auto-poblar
                            window._observacionesEditado = false;
                            calcularTotales();
                            actualizarVistaPrevia();
                        });
                        
                        popup.appendChild(optDe);
                        popup.appendChild(optLe);
                        
                        if (tdObs) {
                            tdObs.style.position = 'relative';
                            tdObs.appendChild(popup);
                        }
                    } else {
                        if (selectPtDeLe) selectPtDeLe.value = '';
                        // Limpiar badge
                        try { var tb = row.querySelector('.celda-producto-obs[data-producto-col="' + col + '"] .pt-badge'); if(tb) tb.remove(); } catch(e) {}
                        if (select.value && select.value !== '') {
                            if (inputCantObs) inputCantObs.removeAttribute('disabled');
                        } else {
                            if (inputCantObs) { inputCantObs.value = ''; inputCantObs.setAttribute('disabled', 'disabled'); }
                        }
                    }
                } catch (e) {}
                // Resetear flag de edicion manual para que el textarea de observaciones se auto-poblar
                window._observacionesEditado = false;
                calcularTotales();
                actualizarVistaPrevia();
            });
        });
        
        // Global click handler to close any open PT popup
        try {
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.pt-sub-menu') && !e.target.closest('.select-obs-producto')) {
                    document.querySelectorAll('.pt-sub-menu').forEach(function(el) { el.remove(); });
                }
            });
        } catch(e) {}

        // Event listeners para los inputs de cant obs por producto
        const inputsCantObsProducto = tr.querySelectorAll('.input-cant-obs-producto');
        inputsCantObsProducto.forEach(function(input) {
            // iniciar deshabilitados
            try { input.setAttribute('disabled', 'disabled'); } catch(e) {}
            input.addEventListener('input', function() {
                try {
                    const col = input.getAttribute('data-producto-col');
                    const row = input.closest('tr');
                    const inputCantGR = row.querySelector(`.input-cant-producto[data-producto-col="${col}"]`);
                    // Determinar si la observación seleccionada es "A" (Adicional)
                    // NOTA: el value del select es el Id numérico, por eso obtenemos el texto de la option
                    let isAdicional = false;
                    const selectObs = row.querySelector(`.select-obs-producto[data-producto-col="${col}"]`);
                    if (selectObs && selectObs.selectedIndex > 0) {
                        const obsText = (selectObs.options[selectObs.selectedIndex].text || '').toLowerCase();
                        isAdicional = (obsText === 'a' || obsText.includes('adici'));
                    }
                    const max = parseFloat(inputCantGR ? inputCantGR.value : 0) || 0;
                    let val = parseFloat(input.value) || 0;
                    // Si la observación es "A" (Adicional), permitir cualquier cantidad sin restricción
                    if (!isAdicional && val > max) {
                        input.value = max;
                        try { input.classList.add('input-error'); } catch(e) {}
                        setTimeout(function(){ try { input.classList.remove('input-error'); } catch(e) {} }, 1200);
                    }
                } catch (e) {}
                // Resetear flag de edicion manual para que el textarea de observaciones se auto-poblar
                window._observacionesEditado = false;
                calcularTotales();
                actualizarVistaPrevia();
            });
        });
        
        const inputSerie = tr.querySelector('.select-serie-guia') || tr.querySelector('.input-serie-guia');
        const inputCorrel = tr.querySelector('.input-correlativo-guia');
        function onInputGuia() {
            try { actualizarVistaPrevia(); } catch(e) {}
        }
        if (inputSerie) {
            // si es select (nueva implementación), usar 'change'
            if (inputSerie.tagName === 'SELECT') {
                inputSerie.addEventListener('change', function() {
                    desbloquearFilaGuia(tr);
                    onInputGuia();
                    if ((inputCorrel && (inputCorrel.value || '').trim() !== '')) verificarGuiaDuplicada(tr);
                });
            } else {
                inputSerie.addEventListener('input', function(e) {
                    // permitir solo dígitos y hasta 4
                    this.value = (this.value || '').replace(/\D/g, '').slice(0,4);
                    desbloquearFilaGuia(tr);
                    onInputGuia();
                });
            }
        }
        if (inputCorrel) {
            inputCorrel.addEventListener('input', function(e) {
                // permitir solo dígitos y hasta 7
                this.value = (this.value || '').replace(/\D/g, '').slice(0,7);
                desbloquearFilaGuia(tr);
                onInputGuia();
            });
            // al perder foco, rellenar con ceros a la izquierda hasta 7 y verificar duplicado
            inputCorrel.addEventListener('blur', function() {
                if ((this.value || '').trim() !== '') {
                    this.value = this.value.padStart(7, '0');
                    verificarGuiaDuplicada(tr);
                }
            });
        }
        
        // Agregar listener para Enter en todos los campos de la fila (comportamiento como Tab)
        const todosLosCampos = tr.querySelectorAll('input, select');
        todosLosCampos.forEach(function(campo) {
            campo.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    navegarAlSiguienteCampo(campo, tr);
                }
            });
        });
        
        calcularTotales();
        
        // Aplicar estado de columnas colapsadas a la nueva fila
        aplicarEstadoColumnasColapsadasAFila(tr);
        
        // Aplicar estilos alternados después de agregar la fila
        setTimeout(function() {
            aplicarEstilosAlternadosFilas();
        }, 50);
    }
    
    // Aplicar estado de columnas colapsadas a una fila específica
    function aplicarEstadoColumnasColapsadasAFila(fila) {
        if (columnasColapsadas.size === 0) return;
        
        const headerRow = document.getElementById('headerGrillaGuiasPrincipales');
        if (!headerRow) return;
        const allProductHeaders = Array.from(headerRow.querySelectorAll('.col-producto'));
        
        columnasColapsadas.forEach(function(productoCol) {
            const thPrincipal = allProductHeaders.find(th => th.getAttribute('data-producto-col') === productoCol);
            if (!thPrincipal) return;
            
            const colIndex = allProductHeaders.indexOf(thPrincipal);
            const bg = thPrincipal.style.backgroundColor || '#f0f0f0';
            
            // Ocultar las 3 celdas del producto en esta fila
            const celdasProducto = fila.querySelectorAll(`[data-producto-col="${productoCol}"]`);
            celdasProducto.forEach(function(celda) {
                celda.style.display = 'none';
            });
            
            // Verificar si ya existe el placeholder para evitar duplicados
            const existingPlaceholder = fila.querySelector(`.cell-collapsed[data-producto-col="${productoCol}"]`);
            if (!existingPlaceholder) {
                // Crear placeholder en la fila
                const firstCellProducto = fila.querySelector(`.celda-producto-cantidad[data-producto-col="${productoCol}"]`);
                if (firstCellProducto) {
                    const tdPlaceholder = document.createElement('td');
                    tdPlaceholder.className = 'cell-collapsed';
                    tdPlaceholder.setAttribute('data-producto-col', productoCol);
                    tdPlaceholder.style.cssText = `width:25px; min-width:25px; max-width:25px; background:${bg}; opacity:0.3;`;
                    tdPlaceholder.innerHTML = '&nbsp;';
                    firstCellProducto.parentNode.insertBefore(tdPlaceholder, firstCellProducto);
                }
            }
        });
    }
    
    // Navegar al siguiente campo al presionar Enter (como Tab)
    function navegarAlSiguienteCampo(campoActual, filaActual) {
        const todosLosCampos = Array.from(filaActual.querySelectorAll('input, select'));
        const indiceActual = todosLosCampos.indexOf(campoActual);
        
        if (indiceActual >= 0 && indiceActual < todosLosCampos.length - 1) {
            // Ir al siguiente campo en la misma fila
            todosLosCampos[indiceActual + 1].focus();
            todosLosCampos[indiceActual + 1].select();
        } else if (indiceActual === todosLosCampos.length - 1) {
            // Si es el último campo de la fila, ir al primer campo de la siguiente fila
            const siguienteFila = filaActual.nextElementSibling;
            if (siguienteFila && siguienteFila.classList.contains('fila-guia')) {
                const primerCampo = siguienteFila.querySelector('input, select');
                if (primerCampo) {
                    primerCampo.focus();
                    if (primerCampo.tagName === 'INPUT') {
                        primerCampo.select();
                    }
                }
            }
        }
    }
    
    // Agregar fila siguiente si es necesario (cuando se está en la penúltima fila)
    function agregarFilaSiguienteSiEsNecesario(filaActual) {
        const tbody = document.getElementById('tbodyGrillaGuias');
        if (!tbody) return;
        
        const filas = Array.from(tbody.querySelectorAll('.fila-guia'));
        const indiceActual = filas.indexOf(filaActual);
        
        // Si es la penúltima fila o la última fila, agregar una nueva
        if (indiceActual >= filas.length - 1) {
            agregarFilaGuia();
            aplicarEstilosAlternadosFilas();
        }
    }
    
    // Limpiar filas vacías cuando se borran datos de guías en cualquier orden
    function limpiarFilasVacias(filaActual) {
        const tbody = document.getElementById('tbodyGrillaGuias');
        if (!tbody) return;
        
        const filas = Array.from(tbody.querySelectorAll('.fila-guia'));
        
        // Solo limpiar si hay más de 1 fila
        if (filas.length <= 1) return;
        
        // Recorrer TODAS las filas (excepto la última) y eliminar las que estén vacías
        let algunaEliminada = false;
        for (let i = filas.length - 2; i >= 0; i--) {
            const fila = filas[i];
            if (fila !== filaActual && esFilaVacia(fila)) {
                fila.remove();
                algunaEliminada = true;
            }
        }
        
        // Verificar también si la última fila está vacía (solo si no es la fila actual)
        const ultimaFila = filas[filas.length - 1];
        if (ultimaFila !== filaActual && esFilaVacia(ultimaFila)) {
            // Verificar que después de eliminar vacías intermedias quede al menos 1 fila
            const filasRestantes = Array.from(tbody.querySelectorAll('.fila-guia'));
            if (filasRestantes.length > 1) {
                ultimaFila.remove();
                algunaEliminada = true;
            }
        }
        
        if (algunaEliminada) {
            calcularTotales();
            actualizarVistaPrevia();
            aplicarEstilosAlternadosFilas();
        }
    }
    
    // Verificar si una fila está completamente vacía
    function esFilaVacia(fila) {
        const numeroGuia = obtenerNumeroGuiaDesdeFila(fila);
        const inputDocRef = fila.querySelector('.input-doc-ref');
        const inputsProducto = fila.querySelectorAll('.input-cant-producto');
        const selectsObsProd = fila.querySelectorAll('.select-obs-producto');
        const inputsCantObsProd = fila.querySelectorAll('.input-cant-obs-producto');
        
        // Verificar N° Guía y Doc. Ref.
        if (numeroGuia && numeroGuia.trim() !== '') return false;
        if (inputDocRef && inputDocRef.value.trim() !== '') return false;
        
        // Verificar inputs de productos
        for (let i = 0; i < inputsProducto.length; i++) {
            if (inputsProducto[i].value.trim() !== '' && inputsProducto[i].value.trim() !== '0') {
                return false;
            }
        }
        
        // Verificar selects de obs por producto
        for (let i = 0; i < selectsObsProd.length; i++) {
            if (selectsObsProd[i].value && selectsObsProd[i].value !== '') return false;
        }
        
        // Verificar inputs de cant obs por producto
        for (let i = 0; i < inputsCantObsProd.length; i++) {
            if (inputsCantObsProd[i].value.trim() !== '' && inputsCantObsProd[i].value.trim() !== '0') return false;
        }
        
        return true;
    }
    
    // Aplicar estilos alternados a las filas (tipo Excel)
    function aplicarEstilosAlternadosFilas() {
        const tbody = document.getElementById('tbodyGrillaGuias');
        if (!tbody) return;
        
        const filas = tbody.querySelectorAll('.fila-guia');
        filas.forEach(function(fila, index) {
            // Alternar entre blanco y gris medio (más contraste tipo Excel)
            const bgColor = index % 2 === 0 ? '#ffffff' : '#d9d9d9';
            
            // Aplicar el color a todas las celdas TD de la fila
            const celdas = fila.querySelectorAll('td');
            celdas.forEach(function(celda) {
                celda.style.backgroundColor = bgColor;
            });
            // Las guia-info-row conservan su propio color (no se alteran aquí)
        });
    }

    // Quitar la última fila de guía (no permite eliminar si hay 1 o menos)
    function quitarUltimaGuia() {
        const tbody = document.getElementById('tbodyGrillaGuias');
        if (!tbody) return;
        const filas = tbody.querySelectorAll('.fila-guia');
        if (!filas || filas.length === 0) return;

        if (filas.length <= 1) {
            alert('Debe mantener al menos 1 fila de guías');
            return;
        }

        const ultima = filas[filas.length - 1];
        ultima.remove();
        calcularTotales();
        actualizarVistaPrevia();
        aplicarEstilosAlternadosFilas();
    }
    
    // Agregar nueva columna de producto (a partir de la 5ta)
    function agregarColumnaProducto() {
        const numColumnasActuales = document.querySelectorAll('.col-producto').length;
        
        // Ya no hay límite máximo
        const nuevaCol = numColumnasActuales + 1;
        
        // Construir opciones de productos (solo permitidos)
        let optsProductos = buildAllowedProductOptions();
        
        // Agregar columna en el header
        const headerRow = document.getElementById('headerGrillaGuias');

        // Determinar la celda de referencia (celda de OBS en el header) usando la primera fila
        let insertBeforeTh = null;
        try {
            const firstRow = document.querySelector('#tbodyGrillaGuias tr');
            if (firstRow) {
                const obsCell = firstRow.querySelector('.select-obs');
                if (obsCell) {
                    const obsTd = obsCell.closest('td');
                    const children = Array.from(headerRow.children);
                    const idx = Array.from(firstRow.children).indexOf(obsTd);
                    insertBeforeTh = children[idx];
                }
            }
        } catch (e) { insertBeforeTh = null; }

        // Fallback: si no se pudo obtener, insertar antes del último 2do TH
        if (!insertBeforeTh) {
            insertBeforeTh = headerRow.querySelector('th:nth-last-child(2)');
        }

        const newTh = document.createElement('th');
        newTh.className = 'col-producto';
        newTh.setAttribute('data-producto-col', nuevaCol);
        newTh.style.cssText = 'width:70px; vertical-align:middle; text-align:center; padding:4px;';
        newTh.innerHTML = `<select class="form-select form-select-sm select-header-producto" data-producto-col="${nuevaCol}" style="font-size:0.7rem; padding:2px 4px; height:24px; line-height:1;">${optsProductos}</select>`;

        headerRow.insertBefore(newTh, insertBeforeTh);

        // Agregar event listeners al nuevo select (misma lógica que en poblarSelectsProductos)
        const newSelect = newTh.querySelector('.select-header-producto');
        if (newSelect) {
            newSelect.addEventListener('focus', function() {
                try {
                    const fixedNames = getFixedProductNames();
                    const usedNames = getSelectedHeaderNames(newSelect);
                    Array.from(newSelect.options).forEach(function(opt) {
                        const nombreOpt = (opt.getAttribute('data-nombre') || '').toString().trim();
                        if (nombreOpt) opt.text = nombreOpt;
                        const nombreNorm = nombreOpt.toUpperCase();
                        if (nombreNorm && nombreNorm !== (newSelect.options[newSelect.selectedIndex]?.getAttribute('data-nombre') || '').toString().trim().toUpperCase() && (fixedNames.indexOf(nombreNorm) >= 0 || usedNames.indexOf(nombreNorm) >= 0)) {
                            try { opt.hidden = true; opt.disabled = true; } catch (e) {}
                        } else {
                            try { opt.hidden = false; opt.disabled = false; } catch (e) {}
                        }
                    });
                } catch (e) {}
            });
            newSelect.addEventListener('blur', function() {
                try {
                    Array.from(newSelect.options).forEach(function(opt) {
                        try { opt.hidden = false; opt.disabled = false; } catch (e) {}
                        const nombre = opt.getAttribute('data-nombre') || '';
                        const abre = opt.getAttribute('data-abre') || '';
                        if (opt.selected && opt.getAttribute('data-selected-abre') === '1') {
                            opt.text = abre || nombre || opt.value;
                        } else {
                            opt.text = nombre || opt.value;
                        }
                    });
                } catch (e) {}
            });
            newSelect.addEventListener('change', function() {
                try { Array.from(newSelect.options).forEach(opt => opt.removeAttribute('data-selected-abre')); const sel = newSelect.options[newSelect.selectedIndex]; if (sel && (sel.getAttribute('data-abre') || '').trim() !== '') { sel.setAttribute('data-selected-abre','1'); sel.text = sel.getAttribute('data-abre') || sel.getAttribute('data-nombre') || sel.value; } } catch(e) {}
                actualizarVistaPrevia();
                try { poblarSelectsCodObs(); } catch (e) {}
            });
        }

        // Agregar columna en cada fila del tbody (antes de la celda OBS)
        const filas = document.querySelectorAll('.fila-guia');
        filas.forEach(function(fila) {
            const obsCelda = fila.querySelector('.select-obs')?.closest('td');
            const newTd = document.createElement('td');
            newTd.className = 'celda-producto';
            newTd.setAttribute('data-producto-col', nuevaCol);
            newTd.style.padding = '4px';
            newTd.innerHTML = `<input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control form-control-sm input-cant-producto" placeholder="0" style="font-size:0.7rem; padding:2px 4px; height:24px; text-align:center;" data-producto-col="${nuevaCol}">`;

            if (obsCelda) fila.insertBefore(newTd, obsCelda);
            else fila.appendChild(newTd);

            // Agregar event listener
            const input = newTd.querySelector('.input-cant-producto');
            input.addEventListener('input', function() {
                calcularTotales();
                actualizarVistaPrevia();
            });
            
            // Agregar listener para Enter (comportamiento como Tab)
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    navegarAlSiguienteCampo(input, fila);
                }
            });
        });

        // Agregar columna en el footer (totales) antes del último th (CANT. OBS.)
        const footerRow = document.getElementById('footerTotales');
        const lastTh = footerRow.querySelector('th:last-child');
        if (footerRow) {
            // Crear Cant GR total
            const thGR = document.createElement('th');
            thGR.className = 'total-producto-gr';
            thGR.setAttribute('data-producto-col', nuevaCol);
            thGR.style.cssText = 'text-align:center; font-weight:700;';
            thGR.textContent = '0';
            footerRow.insertBefore(thGR, lastTh);

            // Placeholder Obs
            const thObsPlaceholder = document.createElement('th');
            thObsPlaceholder.style.cssText = 'text-align:center;';
            footerRow.insertBefore(thObsPlaceholder, lastTh);

            // Crear Cant Obs total
            const thObsTotal = document.createElement('th');
            thObsTotal.className = 'total-producto-obs';
            thObsTotal.setAttribute('data-producto-col', nuevaCol);
            thObsTotal.style.cssText = 'text-align:center; font-weight:700;';
            thObsTotal.textContent = '0';
            footerRow.insertBefore(thObsTotal, lastTh);
        }

        // Actualizar selects Cod. Obs. en cada fila
        try { poblarSelectsCodObs(); } catch (e) {}

        // Reindexar columnas y recalcular totales
        try { refreshColumnIndices(); } catch (e) {}
        calcularTotales();
    }
    
    // Eliminar la última columna de producto
    function eliminarUltimaColumnaProducto() {
        const columnasProducto = document.querySelectorAll('.col-producto');
        
        // No permitir eliminar si solo hay 4 columnas (las fijas)
        if (columnasProducto.length <= 4) {
            alert('No se pueden eliminar las columnas fijas (EAN, JAB, JAN, EXI)');
            return;
        }
        
        const ultimaCol = columnasProducto.length;
        
        // Eliminar del header
        const ultimaColHeader = document.querySelector(`.col-producto[data-producto-col="${ultimaCol}"]`);
        if (ultimaColHeader) {
            ultimaColHeader.remove();
        }
        
        // Eliminar de todas las filas
        const celdasProducto = document.querySelectorAll(`.celda-producto[data-producto-col="${ultimaCol}"]`);
        celdasProducto.forEach(function(celda) {
            celda.remove();
        });
        
        // Eliminar del footer: remover todas las celdas que tengan atributo data-producto-col == ultimaCol
        try {
            const footerRow = document.getElementById('footerTotales');
            if (footerRow) {
                const footerCells = footerRow.querySelectorAll(`th[data-producto-col="${ultimaCol}"]`);
                footerCells.forEach(function(fc) { fc.remove(); });
                // Además, intentar eliminar el placeholder Obs contiguo si quedó (no tiene data-producto-col)
                // Buscamos cualquier th vacío adyacente entre las celdas prev/next y eliminamos si corresponde
                const possiblePlaceholders = footerRow.querySelectorAll('th');
                // No forzamos más limpieza para evitar borrar accidentalmente cabeceras
            }
        } catch (e) {}

        // Actualizar selects Cod. Obs. en filas
        try { poblarSelectsCodObs(); } catch (e) {}
        
        // Reindexar columnas y recalcular totales
        try { refreshColumnIndices(); } catch (e) {}
        calcularTotales();
        actualizarVistaPrevia();
    }
    
    // Calcular totales por columna de producto
    function calcularTotales() {
        try {
            // Calcular totales por columna de producto: Cant GR y Cant Obs
            const headerColsCount = document.querySelectorAll('#headerGrillaGuiasPrincipales .col-producto').length || 0;

            for (let col = 1; col <= headerColsCount; col++) {
                // Sumar Cant GR (inputs de cantidad por producto)
                let totalGR = 0;
                const inputsGR = document.querySelectorAll(`.input-cant-producto[data-producto-col="${col}"]`);
                inputsGR.forEach(function(input) {
                    const v = parseInt(input.value) || 0;
                    totalGR += v;
                });

                // Sumar Cant Obs (inputs de cant obs por producto)
                let totalObs = 0;
                const inputsObs = document.querySelectorAll(`.input-cant-obs-producto[data-producto-col="${col}"]`);
                inputsObs.forEach(function(input) {
                    const v = parseInt(input.value) || 0;
                    totalObs += v;
                });

                // Escribir totales en el footer si existen
                const cellGR = document.querySelector(`.total-producto-gr[data-producto-col="${col}"]`);
                if (cellGR) cellGR.textContent = totalGR;

                const cellObs = document.querySelector(`.total-producto-obs[data-producto-col="${col}"]`);
                if (cellObs) cellObs.textContent = totalObs;
            }
        } catch (e) {
            console.warn('[RecepcionesExternas] calcularTotales error', e);
        }
    }

    // Reasigna índices secuenciales `data-producto-col` a headers, celdas y footer
    function refreshColumnIndices() {
        // Actualizar header
        const headerCols = Array.from(document.querySelectorAll('#headerGrillaGuiasPrincipales .col-producto'));
        headerCols.forEach(function(th, idx) {
            const newIndex = idx + 1;
            th.setAttribute('data-producto-col', newIndex);
            const sel = th.querySelector('.select-header-producto');
            if (sel) sel.setAttribute('data-producto-col', newIndex);
        });

        // Actualizar cada fila: reasignar data-producto-col en inputs/selects de producto
        const filas = Array.from(document.querySelectorAll('.fila-guia'));
        filas.forEach(function(fila) {
            // Obtener los elementos de producto en el orden esperado: Cant, Obs, CantObs
            const elems = Array.from(fila.querySelectorAll('.input-cant-producto, .select-obs-producto, .input-cant-obs-producto'));
            // Agrupar de a 3 (estructura Cant / Obs / CantObs por producto)
            let prodIdx = 0;
            for (let k = 0; k < elems.length; k += 3) {
                prodIdx++;
                const elCant = elems[k];
                const elObs = elems[k+1];
                const elCantObs = elems[k+2];
                try { if (elCant) elCant.setAttribute('data-producto-col', prodIdx); } catch(e) {}
                try { if (elObs) elObs.setAttribute('data-producto-col', prodIdx); } catch(e) {}
                try { if (elCantObs) elCantObs.setAttribute('data-producto-col', prodIdx); } catch(e) {}
            }
        });

        // Reconstruir footer para garantizar alineamiento con el header
        const footerRow = document.getElementById('footerTotales');
        if (footerRow) {
            // Contar columnas de producto en header
            const headerColsCount = headerCols.length;

            // Limpiar contenido del footer
            footerRow.innerHTML = '';

            // Celda TOTALES inicial con colspan="2" para cubrir N° Guía (serie+correlativo)
            const thTotales = document.createElement('th');
            thTotales.setAttribute('colspan', '2');
            thTotales.style.cssText = 'text-align:center; font-weight:700;';
            thTotales.textContent = 'TOTALES';
            footerRow.appendChild(thTotales);

            // Placeholder para N° Doc. Ref. (dejar vacío) — necesario para mantener alineamiento
            const thDocRefPlaceholder = document.createElement('th');
            thDocRefPlaceholder.style.cssText = 'text-align:center;';
            footerRow.appendChild(thDocRefPlaceholder);

            // Para cada columna de producto, crear tres celdas: Cant GR (total), Obs (vacía), Cant Obs (total)
            for (let i = 1; i <= headerColsCount; i++) {
                // Cant GR total
                const thGR = document.createElement('th');
                thGR.className = 'total-producto-gr';
                thGR.setAttribute('data-producto-col', i);
                thGR.style.cssText = 'text-align:center; font-weight:700;';
                thGR.textContent = '0';
                footerRow.appendChild(thGR);

                // Obs (vacía)
                const thObsPlaceholder = document.createElement('th');
                thObsPlaceholder.style.cssText = 'text-align:center;';
                footerRow.appendChild(thObsPlaceholder);

                // Cant Obs total
                const thObsTotal = document.createElement('th');
                thObsTotal.className = 'total-producto-obs';
                thObsTotal.setAttribute('data-producto-col', i);
                thObsTotal.style.cssText = 'text-align:center; font-weight:700;';
                thObsTotal.textContent = '0';
                footerRow.appendChild(thObsTotal);
            }
            // Asegurar estado disabled/habilitado de controles según valores existentes en las filas
            try {
                const filasInit = Array.from(document.querySelectorAll('.fila-guia'));
                filasInit.forEach(function(fila) {
                    for (let i = 1; i <= headerColsCount; i++) {
                        try {
                            const inputCant = fila.querySelector(`.input-cant-producto[data-producto-col="${i}"]`);
                            const selectObs = fila.querySelector(`.select-obs-producto[data-producto-col="${i}"]`);
                            const inputCantObs = fila.querySelector(`.input-cant-obs-producto[data-producto-col="${i}"]`);
                            const val = inputCant ? (parseInt(inputCant.value) || 0) : 0;
                            if (val > 0) {
                                if (selectObs) selectObs.removeAttribute('disabled');
                            } else {
                                if (selectObs) { selectObs.value = ''; selectObs.setAttribute('disabled', 'disabled'); }
                                if (inputCantObs) { inputCantObs.value = ''; inputCantObs.setAttribute('disabled', 'disabled'); }
                            }

                            if (selectObs && selectObs.value && selectObs.value !== '') {
                                if (inputCantObs) inputCantObs.removeAttribute('disabled');
                            } else {
                                if (inputCantObs) { inputCantObs.value = ''; inputCantObs.setAttribute('disabled', 'disabled'); }
                            }
                        } catch (e) {}
                    }
                });
            } catch (e) {}
            try { calcularTotales(); } catch (e) {}
        }
    }
    
    // Limpiar toda la grilla
    function limpiarGrilla() {
        const tbody = document.getElementById('tbodyGrillaGuias');
        if (tbody) {
            tbody.innerHTML = '';
        }
        
        // Resetear a una sola columna de producto
        const columnasProducto = document.querySelectorAll('.col-producto');
        for (let i = columnasProducto.length - 1; i > 0; i--) {
            const colIndex = i;
            columnasProducto[i].remove();

            // Remover del footer todas las celdas con data-producto-col = colIndex
            try {
                const footerRow = document.getElementById('footerTotales');
                if (footerRow) {
                    const footerCells = footerRow.querySelectorAll(`th[data-producto-col="${colIndex}"]`);
                    footerCells.forEach(function(fc) { fc.remove(); });
                }
            } catch (e) {}
        }
        
        // Reindexar columnas después del reset
        try { refreshColumnIndices(); } catch (e) {}
        calcularTotales();
        agregarFilaGuia();
        actualizarVistaPrevia();
    }

    // Variable global para prevenir doble clic en guardado (ámbito IIFE)
    var _guardandoEnProceso = false;

    // ---------- Gestión del modo del formulario (Nuevo / Guardado / Editar) ----------
    function setupFormMode() {
        // Inicial: bloquear todos los controles del formulario
        setFormEnabled(false);

        // Botones
        const btnNuevo = document.getElementById('btnNuevo');
        const btnGuardar = document.getElementById('btnGuardar');
        const btnModificar = document.getElementById('btnModificar');
        const btnImprimir = document.getElementById('btnImprimir');

        if (btnNuevo) btnNuevo.addEventListener('click', function() {
            // --- 1. LIMPIAR TODOS LOS VALORES DE LOS CONTROLES ---
            // NOTA: no se limpia 'turno' (es automático) ni 'hora' (se actualiza sola)
            var camposALimpiar = ['fecha','origen','empresa','ruc','chofer','brevete','observaciones','comentarios'];
            camposALimpiar.forEach(function(id){
                var el = document.getElementById(id);
                if(el){
                    if(window.choicesInstances && window.choicesInstances[id]){
                        try{ window.choicesInstances[id].setChoiceByValue(''); }catch(e){}
                    }
                    el.value = '';
                }
            });

            // --- 2. LIMPIAR SOLO LAS FILAS DE LA GRILLA (sin tocar columnas/encabezados) ---
            try{
                var tbodyGrilla = document.getElementById('tbodyGrillaGuias');
                if(tbodyGrilla) tbodyGrilla.innerHTML = '';
                // Agregar una fila vacía por defecto
                try{ if(typeof agregarFilaGuia === 'function') agregarFilaGuia(); }catch(e){}
                // Recalcular totales después de limpiar
                try{ if(typeof calcularTotales === 'function') calcularTotales(); }catch(e){}
            }catch(e){}

            // --- 3. LIMPIAR CORRELATIVO Y SOLICITAR NUEVO NÚMERO ---
            var correlativoVale = document.getElementById('correlativoVale');
            if(correlativoVale){
                correlativoVale.removeAttribute('data-vale-id');
                var codeVale = correlativoVale.querySelector('.code-vale');
                if(codeVale) codeVale.textContent = '';
                else correlativoVale.textContent = '';
            }
            // Obtener siguiente correlativo
            (function(){
                var url = (window.APP_URL || window.BASE_URL || '') + '/recepcionesexternas/siguienteVale';
                fetch(url, { credentials: 'same-origin' })
                    .then(function(r){ return r.json(); })
                    .then(function(res){
                        if(res && res.success && res.correlativo && correlativoVale){
                            var codeEl = correlativoVale.querySelector('.code-vale');
                            if(codeEl) codeEl.textContent = String(res.correlativo);
                            else correlativoVale.textContent = String(res.correlativo);
                        }
                    })
                    .catch(function(err){ console.warn('[RecepcionesExternas] Error obteniendo siguiente correlativo:', err); });
            })();

            // --- 4. ESTABLECER FECHA ACTUAL ---
            var fechaEl = document.getElementById('fecha');
            if(fechaEl) {
                var d = new Date();
                fechaEl.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            }

            // --- 5. RESETEAR TURNO AUTOMÁTICO ---
            try{
                var chkTurno = document.getElementById('chkTurno');
                if(chkTurno) chkTurno.checked = false;
                var turnoSelect = document.getElementById('turno');
                if(turnoSelect && typeof setTurnoByHora === 'function') setTurnoByHora();
            }catch(e){}

            // --- 6. HABILITAR CONTROLES ---
            setFormEnabled(true);
            // turno sigue controlado por el checkbox manual
            const chk = document.getElementById('chkTurno');
            if (chk) chk.removeAttribute('disabled');

            // Establecer modo nuevo
            modoFormulario = 'nuevo';
            recepcionIdActual = null;

            // Ajustar botones
            if (btnGuardar) btnGuardar.removeAttribute('disabled');
            if (btnModificar) btnModificar.setAttribute('disabled', 'disabled');
            btnNuevo.setAttribute('disabled', 'disabled');
        });

        if (btnGuardar) {
            btnGuardar.addEventListener('click', function() {
                console.log('[DIAG] Click en btnGuardar en:', new Date().toISOString());
                
                // PREVENIR DOBLE CLIC: deshabilitar inmediatamente
                if (_guardandoEnProceso || btnGuardar.disabled) {
                    console.log('[RecepcionesExternas] Guardado ya en proceso, ignorando clic');
                    return;
                }
                _guardandoEnProceso = true;
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Guardando...';
                
                // Llamar a la función real de guardado
                guardarRecepcion();
            });
        }

        if (btnModificar) btnModificar.addEventListener('click', function() {
            // Si el modo edición está activo (script separado), no ejecutar este handler
            if (typeof window !== 'undefined' && window.RECEPCIONES_EXTERNAS_EDITION) {
                console.log('[RecepcionesExternas] modo edición externo activo, omitiendo handler por defecto');
                return;
            }
            
            // Verificar si el vale puede ser modificado (límite de 3 modificaciones)
            if (!recepcionIdActual) {
                // No hay vale cargado, permitir modificar sin verificar
                setFormEnabled(true);
                modoFormulario = 'modificar';
                if (btnGuardar) btnGuardar.removeAttribute('disabled');
                btnModificar.setAttribute('disabled', 'disabled');
                return;
            }
            
            // Verificar límite de modificaciones antes de habilitar controles
            fetch((window.APP_URL || window.BASE_URL) + '/recepcionesexternas/verificarModificaciones?id=' + recepcionIdActual)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.puede_modificar) {
                        // Puede modificar, habilitar controles
                        setFormEnabled(true);
                        modoFormulario = 'modificar';
                        if (btnGuardar) btnGuardar.removeAttribute('disabled');
                        btnModificar.setAttribute('disabled', 'disabled');
                    } else {
                        // No puede modificar, mostrar mensaje
                        const mensaje = data.mensaje || 'Este vale ya alcanzó el límite de 3 modificaciones';
                        showFloatingMessage(mensaje, 'error');
                    }
                })
                .catch(error => {
                    console.error('[Recepciones Externas] Error al verificar modificaciones:', error);
                    showFloatingMessage('Error al verificar límite de modificaciones', 'error');
                });
        });

        // Helper para imprimir HTML (usa iframe oculto y fallback a ventana)
        function printHtml(html) {
            try {
                // Helper para cerrar el modal de vista previa de forma segura
                function closePreviewModal() {
                    try {
                        var modalEl = document.getElementById('modalValePreview');
                        if (!modalEl) return;
                        if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                            var inst = bootstrap.Modal.getInstance(modalEl) || (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : null);
                            if (inst && typeof inst.hide === 'function') return inst.hide();
                        }
                        // Fallback manual: remover clases y backdrop
                        modalEl.classList.remove('show');
                        document.body.classList.remove('modal-open');
                        var backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                    } catch (e) {}
                }

                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.style.visibility = 'hidden';
                document.body.appendChild(iframe);

                const idoc = iframe.contentDocument || iframe.contentWindow.document;
                idoc.open();

                // === AUTO-SCALING: Inyectar script al final del body para escalar si es necesario ===
                var autoScaleScript = '<script>' +
                '(function(){' +
                'try{' +
                'var c=document.querySelector(".print-container");' +
                'if(!c)return;' +
                'var ch=c.scrollHeight;' +
                // Altura imprimible A4 vertical en pixeles: 297mm - 12mm (padding) = 285mm ≈ 1077px
                'var ah=285*3.7795;' +
                'if(ch>ah){' +
                'var s=ah/ch;' +
                's=Math.max(0.55,Math.min(1,s));' + // límite inferior 0.55
                'c.style.transform="scale("+s+")";' +
                'c.style.transformOrigin="top left";' +
                'c.style.width=(100/s)+"%";' + // compensar ancho para que no se encoja horizontal
                'c.style.height=ah+"px";' +
                'c.style.overflow="hidden";' +
                '}' +
                'setTimeout(function(){window.print();},100);' +
                '}catch(e){setTimeout(function(){window.print();},100);}' +
                '})();' +
                '<\/script>';
                // Insertar el script antes de </body>
                html = html.replace('</body>', autoScaleScript + '</body>');

                idoc.write(html);
                idoc.close();

                setTimeout(function() {
                    try {
                        // NO llamar a print() aquí porque el script inline ya lo hace
                        // Solo enfocar y cerrar modal
                        try { iframe.contentWindow.focus(); } catch(e) {}
                        try { closePreviewModal(); } catch(e) {}
                    } catch (e) {
                        const wnd2 = window.open('', '_blank');
                        if (wnd2) {
                            wnd2.document.open();
                            wnd2.document.write(html);
                            wnd2.document.close();
                            wnd2.focus();
                            setTimeout(function(){ try{ wnd2.print(); try { closePreviewModal(); } catch(e){} } catch(e){} }, 600);
                        }
                    }

                    setTimeout(function(){ try{ document.body.removeChild(iframe); } catch(e){} }, 30000);
                }, 600);
            } catch (err) {
                try {
                    const wnd2 = window.open('', '_blank');
                    if (wnd2) {
                        wnd2.document.open();
                        // También aplicar auto-scaling en fallback
                        var fallbackScaleScript = '<script>' +
                        '(function(){' +
                        'try{' +
                        'var c=document.querySelector(".print-container");' +
                        'if(!c)return;' +
                        'var ch=c.scrollHeight;' +
                        'var ah=285*3.7795;' +
                        'if(ch>ah){' +
                        'var s=ah/ch;' +
                        's=Math.max(0.55,Math.min(1,s));' +
                        'c.style.transform="scale("+s+")";' +
                        'c.style.transformOrigin="top left";' +
                        'c.style.width=(100/s)+"%";' +
                        'c.style.height=ah+"px";' +
                        'c.style.overflow="hidden";' +
                        '}' +
                        'setTimeout(function(){window.print();},100);' +
                        '}catch(e){setTimeout(function(){window.print();},100);}' +
                        '})();' +
                        '<\/script>';
                        html = html.replace('</body>', fallbackScaleScript + '</body>');
                        wnd2.document.open();
                        wnd2.document.write(html);
                        wnd2.document.close();
                        wnd2.focus();
                    }
                } catch (e) {}
            }
        }

        if (btnImprimir) btnImprimir.addEventListener('click', async function() {
            try { console.log('[RecepcionesExternas] btnImprimir clicked', { modoEdicion: window.modoEdicion || false, usuarioUsername: window.usuarioUsername || '' }); } catch(e) {}
            // Mostrar modal con la vista previa
            try {
                const prev = document.getElementById('valePreview');
                const modalBody = document.getElementById('valePreviewContent');
                if (!prev || !modalBody) return;

                // Forzar una actualización rápida de la vista previa y esperar
                try { if (typeof actualizarVistaPrevia === 'function') await actualizarVistaPrevia(); } catch(e) {}

                // Esperar hasta que los productos estén cargados (o expirar) antes de copiar el HTML
                var attempts = 0;
                var maxAttempts = 20; // 20 * 50ms = 1s máximo

                async function copyWhenReady() {
                    var productosReady = (window.productosLoaded === true) || (Array.isArray(window.productosData) && window.productosData.length > 0);
                                if (productosReady || attempts >= maxAttempts) {
                                    try { if (typeof actualizarVistaPrevia === 'function') await actualizarVistaPrevia(); } catch(e) {}

                                    // Copiar contenido actualizado al modal
                                    modalBody.innerHTML = prev.innerHTML || '<div class="text-center text-muted py-5"><i class="bi bi-file-earmark-text" style="font-size:3rem;"></i><p class="mt-2">La vista previa se actualizará conforme ingreses los datos</p></div>';

                                    var modalEl = document.getElementById('modalValePreview');
                                    if (modalEl && typeof bootstrap !== 'undefined') {
                                        var modal = new bootstrap.Modal(modalEl);
                                        modal.show();
                                    } else {
                                        // Fallback: si no hay Bootstrap, abrir nueva ventana con la vista previa
                                        const html = '<!doctype html><html><head><meta charset="utf-8"><title>Vista previa</title></head><body>' + modalBody.innerHTML + '</body></html>';
                                        const wnd = window.open('', '_blank');
                                        if (wnd) { wnd.document.open(); wnd.document.write(html); wnd.document.close(); wnd.focus(); }
                                    }
                                    return;
                                }
                    attempts++;
                    setTimeout(copyWhenReady, 50);
                }

                copyWhenReady();
            } catch (e) { console.warn('[btnImprimir] error al abrir modal', e); }
        });

        // Botón dentro del modal que dispara la impresión real
        try {
            document.addEventListener('click', function(e) {
                if (e.target && (e.target.id === 'btnValePrint' || e.target.closest && e.target.closest('#btnValePrint'))) {
                    e.preventDefault();
                    const modalBody = document.getElementById('valePreviewContent');
                    if (!modalBody) return;
                    // Intento de eliminar cabeceras/pies del navegador: minimizar márgenes en @page y body
                    // Nota: algunos navegadores aún imprimirán cabeceras/pies si la configuración del usuario lo exige.
                    const html = `<!doctype html><html><head><meta charset="utf-8"><title>Imprimir Vale</title><style>@page{size:A4 portrait;margin:0;}@media print{@page{margin:0;}html{font-size:15px!important;}body{height:100%;margin:0;padding:0;}}body{margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;color:#111;font-size:0.6rem;-webkit-print-color-adjust:exact;print-color-adjust:exact;} .print-container{width:100%;box-sizing:border-box;overflow:visible;padding:6mm 8mm;} .vale-frame{border:1px solid #222;border-radius:8px;padding:8px;box-sizing:border-box;background:#fff;page-break-inside:avoid;} .print-container table{width:100%;border-collapse:collapse;font-size:0.55rem;table-layout:fixed;} .print-container table.grid-table{table-layout:auto;} .print-container table.grid-table td, .print-container table.grid-table th{border:1px solid #999;padding:1px 2px;white-space:nowrap;} .firma-block{page-break-inside:avoid;}</style></head><body><div class="print-container"><div class="vale-frame">` + modalBody.innerHTML + '</div></div></body></html>';
                    printHtml(html);
                }
            }, true);
        } catch (e) { console.warn('Error registrando listener btnValePrint', e); }
    }

    // Habilita/deshabilita todos los controles del formulario excepto elementos marcados con .keep-enabled
    // NOTA: solo afecta a #formRecepcionExterna, NO a #formGrillaGuias (la grilla maneja su propio estado)
    function setFormEnabled(enabled) {
        try {
            const form = document.getElementById('formRecepcionExterna');
            if (!form) return;
            // Inputs, selects, textareas inside form
            const controls = form.querySelectorAll('input, select, textarea, button');
            controls.forEach(function(ctrl) {
                // nunca deshabilitar botones de acción
                if (ctrl.classList && ctrl.classList.contains('keep-enabled')) return;
                // No tocar el botón imprimir que tiene clase keep-enabled
                if (ctrl.id && (ctrl.id === 'btnNuevo' || ctrl.id === 'btnGuardar' || ctrl.id === 'btnModificar' || ctrl.id === 'btnImprimir')) return;

                if (enabled) {
                    ctrl.removeAttribute('disabled');
                } else {
                    ctrl.setAttribute('disabled', 'disabled');
                }
            });

            // También gestionar instances de Choices.js si existen (habilitar/deshabilitar)
            if (window.choicesInstances) {
                for (const key in window.choicesInstances) {
                    try {
                        const inst = window.choicesInstances[key];
                        if (!inst) continue;
                        if (enabled) inst.enable(); else inst.disable();
                    } catch (e) {}
                }
            }

            // Mantener turno deshabilitado hasta que el checkbox manual esté activo
            const chk = document.getElementById('chkTurno');
            const turno = document.getElementById('turno');
            if (!enabled) {
                if (chk) chk.setAttribute('disabled', 'disabled');
                if (turno) {
                    turno.setAttribute('disabled', 'disabled');
                    try { if (window.choicesInstances && window.choicesInstances['turno']) window.choicesInstances['turno'].disable(); } catch(e){}
                }
            } else {
                // habilitar checkbox manual; turno sólo si checkbox ya está marcado
                if (chk) chk.removeAttribute('disabled');
                if (turno) {
                    if (chk && chk.checked) {
                        turno.removeAttribute('disabled');
                        try { if (window.choicesInstances && window.choicesInstances['turno']) window.choicesInstances['turno'].enable(); } catch(e){}
                    } else {
                        turno.setAttribute('disabled', 'disabled');
                        try { if (window.choicesInstances && window.choicesInstances['turno']) window.choicesInstances['turno'].disable(); } catch(e){}
                    }
                }
            }
        } catch (e) {
            console.warn('[RecepcionesExternas] setFormEnabled error', e);
        }
    }
    
    /**
     * Serializar los datos del formulario y la grilla en un objeto listo para enviar
     */
    function serializarDatos() {
        try {
            const empresaSelect = document.getElementById('empresa');
            const rucSelect = document.getElementById('ruc');
            const choferSelect = document.getElementById('chofer');
            const breveteSelect = document.getElementById('brevete');
            
            const data = {
                correlativoVale: document.getElementById('correlativoVale')?.querySelector('.code-vale')?.textContent || '',
                fecha: document.getElementById('fecha')?.value || '',
                hora: document.getElementById('hora')?.value || '',
                turno: document.getElementById('turno')?.value || '',
                origen: document.getElementById('origen')?.value || '',
                empresa: empresaSelect?.value || '', // ID de la empresa (transportista)
                ruc: rucSelect?.options[rucSelect?.selectedIndex]?.text || '', // Texto del RUC (no el ID)
                chofer: choferSelect?.value || '', // ID del chofer
                brevete: breveteSelect?.options[breveteSelect?.selectedIndex]?.text || '', // Texto del brevete (no el ID)
                observaciones: document.getElementById('observaciones') ? document.getElementById('observaciones').value.trim() : '',
                comentarios: document.getElementById('comentarios') ? document.getElementById('comentarios').value.trim() : '',
                guias: []
            };
            
            // Leer todas las filas de la grilla
            const tbody = document.getElementById('tbodyGrillaGuias');
            if (!tbody) {
                throw new Error('No se encontró la tabla de guías');
            }
            
            const filas = tbody.querySelectorAll('tr');
            if (filas.length === 0) {
                throw new Error('Debe agregar al menos una guía');
            }
            
            filas.forEach((fila, idx) => {
                const numeroGuia = obtenerNumeroGuiaDesdeFila(fila);
                const docRefInput = fila.querySelector('.input-doc-ref');

                if (!numeroGuia || numeroGuia.trim() === '') return; // Saltar guías sin número
                
                // Inicializar objeto de guía
                const guia = {
                    numeroGuia: numeroGuia,
                    numeroDocRef: docRefInput?.value.trim() || null,
                    observacion: null,
                    textoObservaciones: null,
                    codigoProductoObs: null,
                    cantidadObservada: 0,
                    productos: []
                };
                
                // Variables para consolidar observaciones de productos
                let primeraObservacion = null;
                let primerCodigoObs = null;
                let totalCantidadObs = 0;
                
                // Leer productos (celdas con input-cant-producto en esta fila)
                const celdasProducto = fila.querySelectorAll('.input-cant-producto');
                celdasProducto.forEach(inputCant => {
                    const cantidad = parseFloat(inputCant.value) || 0;
                    if (cantidad <= 0) return; // Saltar productos sin cantidad
                    
                    const colIndex = parseInt(inputCant.getAttribute('data-producto-col')) || 1;
                    
                    // Variables para almacenar el producto encontrado
                    let codigoProducto = '';
                    let descripcion = '';
                    let unidadMedida = '';
                    let productoEncontrado = null;
                    
                    // Buscar código del producto: primero en columnas fijas, luego en selects dinámicos
                    const headerFijo = document.querySelector(`.col-producto-fija[data-producto-col="${colIndex}"]`);
                    if (headerFijo) {
                        if (window.productosData) {
                            // Primero intentar por nombre completo usando el mapeo FIXED_PRODUCTOS_BY_COL
                            // Esto evita confusiones cuando hay múltiples productos con la misma abreviatura
                            let prodFijo = null;
                            const targetName = FIXED_PRODUCTOS_BY_COL[colIndex] || '';
                            
                            console.log(`[DEBUG] Columna ${colIndex}: Buscando producto con nombre: "${targetName}"`);
                            
                            if (targetName) {
                                prodFijo = window.productosData.find(p => {
                                    const productoNombre = (p.Producto || '').toString().trim().toUpperCase();
                                    const targetUpper = targetName.toUpperCase();
                                    const match = productoNombre === targetUpper;
                                    if (colIndex === 2 || colIndex === 8) {
                                        console.log(`[DEBUG] Comparando: "${productoNombre}" === "${targetUpper}" = ${match}`);
                                    }
                                    return match;
                                });
                                
                                if (prodFijo) {
                                    console.log(`[DEBUG] Columna ${colIndex}: Producto encontrado por nombre:`, prodFijo);
                                }
                            }

                            // Si no se encuentra por nombre, intentar por Abreviatura (fallback)
                            if (!prodFijo) {
                                const abreviaturaFija = headerFijo.getAttribute('data-producto-fijo');
                                console.log(`[DEBUG] Columna ${colIndex}: No encontrado por nombre, buscando por abreviatura: "${abreviaturaFija}"`);
                                if (abreviaturaFija) {
                                    prodFijo = window.productosData.find(p => (p.Abreviatura || '').toString().toUpperCase() === abreviaturaFija.toUpperCase());
                                    if (prodFijo) {
                                        console.log(`[DEBUG] Columna ${colIndex}: Producto encontrado por abreviatura:`, prodFijo);
                                    }
                                }
                            }

                            if (prodFijo) {
                                // Usar directamente el producto encontrado sin hacer segunda búsqueda
                                productoEncontrado = prodFijo;
                                codigoProducto = prodFijo.Codigo || '';
                                descripcion = prodFijo.Producto || '';
                                unidadMedida = prodFijo.UnidadMedida || '';
                                console.log('[serializarDatos] Producto encontrado:', { codigo: codigoProducto, descripcion, unidadMedida });
                            } else {
                                console.warn(`[DEBUG] Columna ${colIndex}: NO SE ENCONTRÓ PRODUCTO`);
                            }
                        }
                    } else {
                        // Buscar en selects dinámicos
                        const headerSelect = document.querySelector(`.select-header-producto[data-producto-col="${colIndex}"]`);
                        codigoProducto = headerSelect?.value || '';
                        
                        // Para selects dinámicos, buscar el producto por código
                        if (codigoProducto && window.productosData && Array.isArray(window.productosData)) {
                            const prod = window.productosData.find(p => String(p.Codigo) === String(codigoProducto));
                            if (prod) {
                                productoEncontrado = prod;
                                descripcion = prod.Producto || '';
                                unidadMedida = prod.UnidadMedida || '';
                            }
                        }
                    }
                    
                    if (!codigoProducto) return; // Saltar si no hay producto seleccionado
                    
                    // Leer observación y cantidad observada para ESTE producto específico
                    const selectObsProducto = fila.querySelector(`.select-obs-producto[data-producto-col="${colIndex}"]`);
                    const inputCantObsProducto = fila.querySelector(`.input-cant-obs-producto[data-producto-col="${colIndex}"]`);
                    
                    const observacionProducto = selectObsProducto?.value || null;
                    const cantidadObservadaProducto = parseFloat(inputCantObsProducto?.value) || 0;
                    
                    // Si este producto tiene observación y aún no hemos capturado una observación para la guía, usarla
                    if (observacionProducto && cantidadObservadaProducto > 0 && !primeraObservacion) {
                        primeraObservacion = observacionProducto;
                        primerCodigoObs = codigoProducto;
                        totalCantidadObs = cantidadObservadaProducto;
                        
                        // Obtener texto de la observación
                        const textoObs = selectObsProducto?.options[selectObsProducto.selectedIndex]?.text || null;
                        guia.textoObservaciones = textoObs;
                    }

                    // Generar texto de observacion para este producto especifico
                    var obsTextProducto = '';
                    if (observacionProducto && cantidadObservadaProducto > 0) {
                        try {
                            // Leer sub-opción DE/LE cuando la observación es PT
                            var ptDeLeSeleccionado = '';
                            try {
                                var selPtDeLe = fila.querySelector(`.select-pt-de-le[data-producto-col="${colIndex}"]`);
                                if (selPtDeLe && selPtDeLe.selectedIndex >= 0) {
                                    ptDeLeSeleccionado = (selPtDeLe.options[selPtDeLe.selectedIndex]?.text || selPtDeLe.value || '').toString().trim().toUpperCase();
                                }
                            } catch(e) {}

                            obsTextProducto = generarTextoObservacionProducto(
                                selectObsProducto?.options[selectObsProducto.selectedIndex]?.text || '',
                                cantidadObservadaProducto,
                                codigoProducto,
                                numeroGuia,
                                colIndex,
                                ptDeLeSeleccionado
                            );
                        } catch(e) {}
                    }

                    guia.productos.push({
                        codigo: codigoProducto,
                        descripcion: descripcion,
                        unidadMedida: unidadMedida,
                        cantidad: cantidad,
                        columna: colIndex,
                        observacion: observacionProducto,
                        cantidadObservada: cantidadObservadaProducto,
                        textoObservaciones: obsTextProducto
                    });
                });
                
                // Asignar la observación consolidada a nivel de guía
                if (primeraObservacion) {
                    guia.observacion = primeraObservacion;
                    guia.codigoProductoObs = primerCodigoObs;
                    guia.cantidadObservada = totalCantidadObs;
                }
                
                data.guias.push(guia);
            });
            
            if (data.guias.length === 0) {
                throw new Error('No se encontraron guías válidas para guardar');
            }
            
            return data;
        } catch (e) {
            console.error('[serializarDatos] Error:', e);
            throw e;
        }
    }
    
    /**
     * Validar campos obligatorios del formulario antes de guardar
     * Retorna true si todos los campos obligatorios están llenos, false en caso contrario
     */
    function validarCamposObligatorios() {
        const campos = [
            { id: 'fecha', nombre: 'Fecha' },
            { id: 'hora', nombre: 'Hora' },
            { id: 'turno', nombre: 'Turno' },
            { id: 'origen', nombre: 'Origen' },
            { id: 'empresa', nombre: 'Empresa' },
            { id: 'ruc', nombre: 'RUC' },
            { id: 'chofer', nombre: 'Chofer' },
            { id: 'brevete', nombre: 'Brevete' }
        ];
        
        let faltantes = [];
        
        campos.forEach(function(campo) {
            const el = document.getElementById(campo.id);
            if (!el) return;
            
            let valor = '';
            if (el.tagName === 'SELECT') {
                valor = el.options[el.selectedIndex] ? el.options[el.selectedIndex].value : '';
                // Si es un select con Choices, verificar también el valor directamente
                if (!valor) valor = el.value || '';
            } else {
                valor = el.value || '';
            }
            
            if (!valor || valor.toString().trim() === '') {
                faltantes.push(campo.nombre);
                // Resaltar el campo con borde rojo
                el.style.borderColor = '#dc3545';
                el.style.boxShadow = '0 0 0 0.2rem rgba(220,53,69,0.25)';
                setTimeout(function() {
                    el.style.borderColor = '';
                    el.style.boxShadow = '';
                }, 3000);
            }
        });
        
        if (faltantes.length > 0) {
            showFloatingMessage('Campos obligatorios pendientes: ' + faltantes.join(', '), 'error');
            return false;
        }
        
        return true;
    }

    /**
     * Enviar datos al servidor para guardar
     */
    function guardarRecepcion() {
        try {
            // Validar campos obligatorios primero
            if (!validarCamposObligatorios()) {
                restaurarBotonGuardar(); // Restaurar botón si validación falla
                return; // Detener si hay campos obligatorios vacíos
            }
            
            // Validar que haya datos para guardar
            const data = serializarDatos();
            
            // Si estamos en modo modificar, agregar el ID y verificar límite de modificaciones
            if (modoFormulario === 'modificar' && recepcionIdActual) {
                data.Id = recepcionIdActual;
                
                // Verificar límite de modificaciones antes de guardar
                fetch((window.APP_URL || window.BASE_URL) + '/recepcionesexternas/verificarModificaciones', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: recepcionIdActual })
                })
                .then(response => response.json())
                .then(validacion => {
                    if (!validacion.puede_modificar) {
                        showFloatingMessage(validacion.mensaje || 'Este vale ya alcanzó el límite de 3 modificaciones', 'error');
                        // Restaurar botón
                        restaurarBotonGuardar();
                        return; // Detener el guardado
                    }
                    
                    // Si puede modificar, proceder con el guardado
                    procederConGuardado(data);
                })
                .catch(error => {
                    console.error('[guardarRecepcion] Error al verificar modificaciones:', error);
                    showFloatingMessage('Error al verificar modificaciones: ' + error.message, 'error');
                    restaurarBotonGuardar();
                });
                
                return; // Salir de la función para esperar validación asíncrona
            }
            
            // Si no es modo modificar (es nuevo), proceder directamente
            procederConGuardado(data);
            
        } catch (e) {
            console.error('[guardarRecepcion] Error:', e);
            showFloatingMessage('Error al preparar datos: ' + e.message, 'error');
            restaurarBotonGuardar();
        }
    }
    
    function procederConGuardado(data) {
        try {
            console.log('[guardarRecepcion] Datos a enviar:', data);
            console.log('[guardarRecepcion] Modo:', modoFormulario);
            
            // Mostrar mensaje de carga
            showFloatingMessage(modoFormulario === 'modificar' ? 'Actualizando...' : 'Guardando...', 'info');
            
            // Enviar POST al endpoint (usar APP_URL para rutas de aplicación, no BASE_URL que es para assets)
            fetch((window.APP_URL || window.BASE_URL) + '/recepcionesexternas/guardar?t=' + Date.now(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                console.log('[guardarRecepcion] Respuesta:', result);
                
                if (result.success) {
                    showFloatingMessage(result.message || (modoFormulario === 'modificar' ? 'Recepción actualizada correctamente' : 'Recepción guardada correctamente'), 'success');
                    
                    // Guardar el ID si es un registro nuevo
                    if (result.id && modoFormulario === 'nuevo') {
                        recepcionIdActual = result.id;
                    }
                    
                    // Actualizar correlativo en pantalla si el servidor devuelve uno nuevo
                    if (result.nVale) {
                        const codeVale = document.querySelector('#correlativoVale .code-vale');
                        if (codeVale) codeVale.textContent = result.nVale;
                    }
                    
                    // Bloquear formulario
                    setFormEnabled(false);
                    
                    // Ajustar botones
                    const btnGuardar = document.getElementById('btnGuardar');
                    const btnNuevo = document.getElementById('btnNuevo');
                    const btnModificar = document.getElementById('btnModificar');
                    
                    if (btnGuardar) {
                        btnGuardar.setAttribute('disabled', 'disabled');
                        btnGuardar.textContent = 'Guardado';
                    }
                    if (btnNuevo) btnNuevo.removeAttribute('disabled');
                    if (btnModificar) btnModificar.removeAttribute('disabled');
                    
                    // Resetear flag de guardado
                    _guardandoEnProceso = false;
                    
                } else {
                    // Si el backend retorna límite alcanzado, mostrar mensaje
                    if (result.limite_alcanzado) {
                        showFloatingMessage('Este vale ya alcanzó el límite de 3 modificaciones', 'error');
                    } else {
                        showFloatingMessage(result.message || 'Error al guardar la recepción', 'error');
                    }
                    // Restaurar botón en caso de error
                    restaurarBotonGuardar();
                }
            })
            .catch(error => {
                console.error('[guardarRecepcion] Error de red:', error);
                showFloatingMessage('Error de conexión al guardar: ' + error.message, 'error');
                // Restaurar botón en caso de error de red
                restaurarBotonGuardar();
            });
            
        } catch (e) {
            console.error('[procederConGuardado] Error:', e);
            showFloatingMessage('Error al preparar datos: ' + e.message, 'error');
            restaurarBotonGuardar();
        }
    }
    
    /**
     * Restaurar botón Guardar después de un error
     */
    function restaurarBotonGuardar() {
        _guardandoEnProceso = false;
        const btnG = document.getElementById('btnGuardar');
        if (btnG) {
            btnG.disabled = false;
            btnG.textContent = 'Guardar';
        }
    }
    
})();
