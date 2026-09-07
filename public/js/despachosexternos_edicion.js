// despachosexternos_edicion.js - Modo edición de vales para Despachos Externos

(function() {
    'use strict';
    
    console.log('[DespachosExternos - Edición] Script cargado');
    
    // Variables globales
    let valeIdActual = null;
    let choicesInstances = {};
    let datosGrilla = [];
    let editIndex = null;
    let updatePhase = null; // null | 'selected' | 'editing'
    let _valeUnicoId = null; // ID del vale si la última búsqueda arrojó exactamente 1 resultado
    // Flag para prevenir doble clic en guardado (declarado aquí para que todas las funciones puedan acceder)
    let _guardandoDespExtEditEnProceso = false;
    // Flag para evitar que forceDisableAll (reintentos por setTimeout) re-bloquee los controles
    // una vez que el usuario ya habilitó la edición con el botón "Modificar".
    let _edicionHabilitada = false;

    // Notificación flotante (overlay) reutilizable — fallback local para este script
    function showFloatingMessage(message, level) {
        try {
            level = level || 'warning';
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

            var createBootstrapAlert = (lvl, txt) => {
                const alert = document.createElement('div');
                const cls = (lvl === 'success') ? 'success' : ((lvl === 'error' || lvl === 'danger') ? 'danger' : (lvl === 'info' ? 'info' : 'warning'));
                alert.className = 'alert alert-' + cls;
                alert.style.minWidth = '220px';
                alert.style.maxWidth = '720px';
                alert.style.boxShadow = '0 6px 18px rgba(0,0,0,0.12)';
                alert.style.borderRadius = '6px';
                alert.style.padding = '10px 14px';
                alert.style.fontSize = '0.95rem';
                alert.style.textAlign = 'center';
                alert.style.pointerEvents = 'auto';
                alert.innerHTML = '<i class="bi bi-info-circle me-2"></i><span>' + txt + '</span>';
                return alert;
            };

            if (level === 'error' || level === 'danger') {
                const boot = createBootstrapAlert(level, message);
                container.appendChild(boot);
                setTimeout(() => { try { boot.remove(); } catch(e){} }, 5000);
            } else {
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

                const icon = document.createElement('span');
                icon.style.flex = '0 0 auto';
                if (level === 'success') icon.textContent = '✔';
                else if (level === 'error') icon.textContent = '✖';
                else icon.textContent = '⚠';
                icon.style.fontSize = '1.05rem';

                const text = document.createElement('div');
                text.style.flex = '1 1 auto';
                text.innerText = message;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.innerText = 'Cerrar';
                btn.style.background = 'transparent';
                btn.style.border = 'none';
                btn.style.cursor = 'pointer';
                btn.style.color = '#374151';
                btn.style.fontWeight = '600';
                btn.addEventListener('click', function() { try { msg.remove(); } catch(e){} });
                btn.style.marginLeft = '8px';

                msg.appendChild(icon);
                msg.appendChild(text);
                msg.appendChild(btn);

                container.appendChild(msg);
                setTimeout(function() { try { msg.remove(); } catch(e){} }, 4000);
            }
        } catch (e) { console.warn('showFloatingMessage fallback error', e); }
    }

    // Mostrar mensaje con diseño (compatible con Nuevo Registro)
    // Acepta segundo parámetro opcional `level` ('error'|'warning'|'success'|'info').
    // Usar el mismo inline styling y temporización que Recepciones Internas para errores.
    function mostrarMensajeError(msg, level) {
        try {
            level = level || 'warning';
            var div = document.getElementById('mensajeError');
            if (!div) { alert(msg); return; }

            // Ajustar clases según nivel
            try{ div.classList.remove('alert-success','alert-danger','alert-warning','alert-info'); }catch(e){}
            if(level === 'error') div.classList.add('alert-danger');
            else if(level === 'success') div.classList.add('alert-success');
            else if(level === 'info') div.classList.add('alert-info');
            else div.classList.add('alert-warning');

            // Inline style overrides exactamente iguales a Recepciones Internas
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
            // Mantener visible 5s para error (coincide con Recepciones Internas), 5s también para otros niveles para consistencia
            setTimeout(function() { try{ div.classList.add('d-none'); }catch(e){} }, 5000);
        } catch (e) {
            console.log('mostrarMensajeError', e);
        }
    }

    // Inicializar Choices.js (usar instancias ya creadas por despachosexternos.js si existen)
    function initChoices() {
        // Usar instancias globales de Choices si ya existen (creadas por despachosexternos.js)
        if (window.choicesInstances && Object.keys(window.choicesInstances).length > 0) {
            console.log('[Edición] Usando instancias de Choices.js ya existentes de despachosexternos.js');
            choicesInstances = window.choicesInstances;
            return;
        }
        
        // Si no existen, crear nuevas instancias (fallback)
        const selectores = ['turno', 'destino', 'chofer', 'transportista', 'placa_tracto', 'placa_carreta', 'producto'];
        
        selectores.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento && !choicesInstances[id]) {
                try {
                    choicesInstances[id] = new Choices(elemento, {
                        searchEnabled: true,
                        searchChoices: true,
                        shouldSort: false,
                        itemSelectText: '',
                        allowHTML: false,
                        searchResultLimit: 100,
                        position: 'auto',
                        placeholder: true,
                        placeholderValue: 'Seleccione...',
                        noResultsText: 'No se encontraron resultados',
                        removeItemButton: false,
                        duplicateItemsAllowed: false
                    });
                    // FIX (doble inicialización de Choices.js): guardar también en
                    // window.choicesInstances para que despachosexternos.js detecte que
                    // este select ya está inicializado y NO lo recree (evita instancias
                    // corruptas sin isDisabled que rompen enable()/disable() y el dropdown).
                    window.choicesInstances = window.choicesInstances || {};
                    window.choicesInstances[id] = choicesInstances[id];
                    // Replicar el listener showDropdown del script principal (muestra el
                    // input de búsqueda y le da foco), ya que ahora esta instancia es la definitiva.
                    elemento.addEventListener('showDropdown', function(){
                        setTimeout(function(){
                            var input = elemento.parentElement.querySelector('.choices__input');
                            if(input){ input.style.display = 'block'; input.placeholder = 'Buscar...'; input.focus(); }
                        },100);
                    });
                    // Agregar listener de change para wrapping automático
                    elemento.addEventListener('change', function(){
                        setTimeout(function(){
                            if(typeof window.updateAllChoicesWrap === 'function'){
                                window.updateAllChoicesWrap();
                            }
                        }, 100);
                    });
                } catch(e) {
                    console.warn('[Edición] Error al inicializar Choices para', id, e);
                }
            }
        });
        
        // Aplicar wrapping inicial después de cargar
        setTimeout(function(){
            if(typeof window.updateAllChoicesWrap === 'function'){ 
                window.updateAllChoicesWrap();
            }
        }, 300);
    }

    // Configurar turno automático
    function setupTurnoAutomatico() {
        const chkTurno = document.getElementById('chkTurno');
        const turnoSelect = document.getElementById('turno');
        
        if (!chkTurno || !turnoSelect) return;
        
        // Establecer turno automático al cargar
        if (!chkTurno.checked) {
            // Aplicar turno automático y mantener control deshabilitado
            actualizarTurnoAutomatico();
            try { const s = document.getElementById('turno'); if(s) s.setAttribute('disabled','disabled'); } catch(e){}
            try { const ci = choicesInstances['turno'] || (window.choicesInstances && window.choicesInstances['turno']); if(ci && typeof ci.disable === 'function') ci.disable(); } catch(e){}
        }
        
        // Evento para checkbox manual: alterna enable/disable del select y Choices
        chkTurno.addEventListener('change', function() {
            const sel = document.getElementById('turno');
            const ci = choicesInstances['turno'] || (window.choicesInstances && window.choicesInstances['turno']);
            if (!sel) return;
            if (this.checked) {
                // Modo manual: habilitar
                sel.removeAttribute('disabled');
                try { if (ci && typeof ci.enable === 'function') ci.enable(); } catch(e){}
                try { syncTurnoChoicesDisabled(); } catch(e){}
            } else {
                // Volver a automático: aplicar turno y deshabilitar
                try { actualizarTurnoAutomatico(); } catch(e){}
                sel.setAttribute('disabled','disabled');
                try { if (ci && typeof ci.disable === 'function') ci.disable(); } catch(e){}
                try { syncTurnoChoicesDisabled(); } catch(e){}
            }
        });
        
        // Sincronizar aspecto visual del control turno cuando está deshabilitado
        try { syncTurnoChoicesDisabled(); } catch(e){}
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
            // Usar la instancia local si existe; si no, la global (creada por despachosexternos.js)
            const ci = (choicesInstances && choicesInstances['turno']) || (window.choicesInstances && window.choicesInstances['turno']);
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

    // Fuerza la apertura visual del dropdown de turno manipulando el DOM directamente.
    // No depende de que la instancia Choices responda al clic (robusto ante estados rotos
    // o listeners que no se ejecutan). Solo actúa si el select está habilitado (Manual activo).
    function forzarAperturaTurnoManual() {
        try {
            const sel = document.getElementById('turno');
            if (!sel) return;
            if (sel.disabled) return;
            let choicesEl = sel.nextElementSibling;
            if (!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')) {
                choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(sel));
            }
            if (!choicesEl) return;
            choicesEl.classList.add('is-open');
            const dd = choicesEl.querySelector('.choices__list--dropdown');
            if (dd) {
                dd.classList.add('is-active');
                dd.setAttribute('aria-expanded', 'true');
                try { dd.style.setProperty('display', 'block'); } catch(e){}
                try { dd.style.setProperty('visibility', 'visible'); } catch(e){}
                try { dd.style.setProperty('opacity', '1'); } catch(e){}
                // Limpiar estilos residuales de "deshabilitado" dentro del dropdown
                try { dd.style.removeProperty('color'); } catch(e){}
                try { dd.style.removeProperty('cursor'); } catch(e){}
                Array.from(dd.querySelectorAll('input,button')).forEach(function(i){
                    try { i.removeAttribute('disabled'); i.disabled = false; } catch(e){}
                    try { i.style.removeProperty('pointer-events'); } catch(e){}
                    try { i.style.removeProperty('color'); } catch(e){}
                    try { i.style.removeProperty('cursor'); } catch(e){}
                    try { i.style.removeProperty('background'); } catch(e){}
                });
            }
        } catch(e) { /* ignore */ }
    }

    // Adjunta un listener que fuerza la apertura del dropdown de turno cuando el usuario hace click
    function attachTurnoClickToShow() {
        try {
            const sel = document.getElementById('turno');
            if (!sel) return;
            let choicesEl = sel.nextElementSibling;
            if (!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')) {
                choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(sel));
            }
            if (!choicesEl) return;
            if (choicesEl.dataset.turnoClickAttached === '1') return;
            const ci = choicesInstances['turno'] || (window.choicesInstances && window.choicesInstances['turno']);
            choicesEl.addEventListener('click', function() {
                try {
                    if (sel.disabled) return;
                    forzarAperturaTurnoManual();
                    // Solo forzar vía instancia si el dropdown sigue cerrado (no romper el toggle)
                    try {
                        if (!choicesEl.classList.contains('is-open') && ci && typeof ci.showDropdown === 'function') ci.showDropdown();
                    } catch (e) {}
                } catch (e) { /* ignore */ }
            });
            // FIX (dropdown de turno no desplegaba en modo Manual): interceptar el clic en el
            // .choices__inner en fase de CAPTURA con stopImmediatePropagation(), para impedir que
            // el handler nativo de Choices haga toggle y cierre el dropdown que forzamos a abrir.
            // El clic real cae sobre el inner; bloquearlo aquí garantiza que el dropdown quede abierto.
            const inner = choicesEl.querySelector('.choices__inner');
            if (inner && !inner.dataset.turnoInnerCapture) {
                inner.addEventListener('click', function(evt) {
                    try {
                        if (sel.disabled) return;
                        evt.stopImmediatePropagation();
                        evt.preventDefault();
                        forzarAperturaTurnoManual();
                        const ciActual = (window.choicesInstances && window.choicesInstances['turno']) || null;
                        if (ciActual && typeof ciActual.showDropdown === 'function') { try { ciActual.showDropdown(); } catch(e){} }
                    } catch (e) { /* ignore */ }
                }, true);
                inner.dataset.turnoInnerCapture = '1';
            }
            choicesEl.dataset.turnoClickAttached = '1';
        } catch(e) { /* ignore */ }
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
            // Fallback adicional: intentar localizar el contenedor a través de la instancia de Choices (si existe)
            if ((!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')) && window.choicesInstances && window.choicesInstances['turno']) {
                const ci = window.choicesInstances['turno'];
                try {
                    for (const k in ci) {
                        try {
                            const v = ci[k];
                            if (v && v.nodeType === 1 && v.classList && v.classList.contains('choices')) { choicesEl = v; break; }
                        } catch (ee) { /* ignore */ }
                    }
                } catch (ee) { /* ignore */ }
            }
            // Último recurso: buscar .choices cercano dentro del card
            if ((!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices'))) {
                choicesEl = document.querySelector('#cardFechaTurno .choices');
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
                    // Restaurar apariencia (bordes y radio) para coincidir con otros selects
                    try { if (choicesEl) choicesEl.style.setProperty('border','1px solid #ced4da','important'); } catch(e){}
                    try { if (choicesEl) choicesEl.style.setProperty('border-radius','0.25rem','important'); } catch(e){}
                    try { inner.style.boxShadow = 'none'; } catch(e){}
                    try { inner.style.padding = '0.375rem 0.75rem'; } catch(e){}
                }
                if (item) {
                    item.style.color = '#6b7280';
                }
            } else {
                choicesEl.classList.remove('is-disabled');
                choicesEl.removeAttribute('aria-disabled');
                if (inner) {
                    inner.removeAttribute('aria-disabled');
                    // Limpiar TODOS los estilos inline (incluidos los !important) aplicados al deshabilitar
                    try { inner.style.removeProperty('background-color'); } catch(e){}
                    try { inner.style.removeProperty('color'); } catch(e){}
                    try { inner.style.removeProperty('cursor'); } catch(e){}
                    try { inner.style.removeProperty('opacity'); } catch(e){}
                    try { inner.style.removeProperty('box-shadow'); } catch(e){}
                    try { inner.style.removeProperty('padding'); } catch(e){}
                    try { inner.style.removeProperty('min-height'); } catch(e){}
                    try { inner.style.removeProperty('pointer-events'); } catch(e){}
                }
                // Limpiar estilos del contenedor externo
                try { choicesEl.style.removeProperty('background-color'); } catch(e){}
                try { choicesEl.style.removeProperty('border'); } catch(e){}
                try { choicesEl.style.removeProperty('border-radius'); } catch(e){}
                try { choicesEl.style.removeProperty('box-shadow'); } catch(e){}
                try { choicesEl.style.removeProperty('opacity'); } catch(e){}
                try { choicesEl.style.removeProperty('pointer-events'); } catch(e){}
                if (item) {
                    try { item.style.removeProperty('color'); } catch(e){}
                    try { item.style.removeProperty('display'); } catch(e){}
                    try { item.style.removeProperty('white-space'); } catch(e){}
                }
                // Re-habilitar inputs/buttons internos de Choices y limpiar su pointer-events
                Array.from(choicesEl.querySelectorAll('input,button')).forEach(function(i){
                    try { i.removeAttribute('disabled'); i.disabled = false; } catch(e){}
                    try { i.style.removeProperty('pointer-events'); } catch(e){}
                    try { i.style.removeProperty('background'); } catch(e){}
                });
                // Adjuntar handler para permitir abrir dropdown con click
                try { attachTurnoClickToShow(); } catch(e) {}
                // Forzar pointer-events en el contenedor para garantizar interacción
                try { choicesEl.style.setProperty('pointer-events', 'auto', 'important'); } catch(e) {}
                try { if (inner) inner.style.setProperty('pointer-events', 'auto', 'important'); } catch(e) {}
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
        
        if (turnoId && choicesInstances['turno']) {
            choicesInstances['turno'].setChoiceByValue(turnoId.toString());
        }
        // Aplicar fecha efectiva para turno nocturno
        try{ if(typeof window.aplicarFechaEfectivaTurno === 'function') window.aplicarFechaEfectivaTurno(false); }catch(e){}
    }

    // Configurar campos sincronizados (Destino-RUC-Dirección, Chofer-Brevete, Transportista-RUC, Placas-Constancias)
    // Sincronización BIDIRECCIONAL: cambiar el padre autocompleta al hijo y viceversa.
    function setupCamposSincronizados() {
        // Destino <-> RUC (+ Dirección)
        const destinoSelect = document.getElementById('destino');
        const direccionInput = document.getElementById('direccion');
        const rucSelect = document.getElementById('ruc');
        
        if (destinoSelect) {
            destinoSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                if (opcion && opcion.value) {
                    setSelectValueEdit('ruc', opcion.getAttribute('data-ruc') || '');
                    if (direccionInput) direccionInput.value = opcion.getAttribute('data-direccion') || '';
                } else {
                    setSelectValueEdit('ruc', '');
                    if (direccionInput) direccionInput.value = '';
                }
            });
        }
        if (rucSelect) {
            rucSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                const rucVal = (opcion && opcion.value) ? opcion.value : '';
                const destinos = window.destinosData || [];
                const found = destinos.find(function(d){ return String(d.RUC || d.ruc || '') === String(rucVal); });
                if (found) {
                    setSelectValueEdit('destino', found.Id || found.id || '');
                    if (direccionInput) direccionInput.value = found.Direccion || found.direccion || '';
                } else if (!rucVal) {
                    setSelectValueEdit('destino', '');
                    if (direccionInput) direccionInput.value = '';
                }
            });
        }
        
        // Chofer <-> Brevete
        const choferSelect = document.getElementById('chofer');
        const breveteSelect = document.getElementById('brevete');
        
        if (choferSelect) {
            choferSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                if (opcion && opcion.value) {
                    setSelectValueEdit('brevete', opcion.getAttribute('data-brevete') || '');
                } else {
                    setSelectValueEdit('brevete', '');
                }
            });
        }
        if (breveteSelect) {
            breveteSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                const bVal = (opcion && opcion.value) ? opcion.value : '';
                const choferes = window.choferesData || [];
                const found = choferes.find(function(c){ return String(c.Brevete || c.brevete || '') === String(bVal); });
                if (found) {
                    setSelectValueEdit('chofer', found.Id || found.id || '');
                } else if (!bVal) {
                    setSelectValueEdit('chofer', '');
                }
            });
        }
        
        // Transportista <-> RUC Transportista
        const transportistaSelect = document.getElementById('transportista');
        const rucTransportistaSelect = document.getElementById('ruc_transportista');
        
        if (transportistaSelect) {
            transportistaSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                if (opcion && opcion.value) {
                    setSelectValueEdit('ruc_transportista', opcion.getAttribute('data-ruc') || '');
                } else {
                    setSelectValueEdit('ruc_transportista', '');
                }
            });
        }
        if (rucTransportistaSelect) {
            rucTransportistaSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                const rVal = (opcion && opcion.value) ? opcion.value : '';
                const transportistas = window.transportistasData || [];
                const found = transportistas.find(function(t){ return String(t.RUC || t.ruc || '') === String(rVal); });
                if (found) {
                    setSelectValueEdit('transportista', found.Id || found.id || '');
                } else if (!rVal) {
                    setSelectValueEdit('transportista', '');
                }
            });
        }
        
        // Placa Tracto <-> Constancia Tracto
        const placaTractoSelect = document.getElementById('placa_tracto');
        const constanciaTractoSelect = document.getElementById('constancia_inscripcion');
        
        if (placaTractoSelect) {
            placaTractoSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                if (opcion && opcion.value) {
                    setSelectValueEdit('constancia_inscripcion', opcion.getAttribute('data-constancia') || '');
                } else {
                    setSelectValueEdit('constancia_inscripcion', '');
                }
            });
        }
        if (constanciaTractoSelect) {
            constanciaTractoSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                const cVal = (opcion && opcion.value) ? opcion.value : '';
                const placas = window.placasData || [];
                const found = placas.find(function(p){
                    return String(p.ConstanciaInscripcion || '') === String(cVal) && String(p.TipoPlaca || '').toUpperCase() === 'TRACTO';
                });
                if (found) {
                    setSelectValueEdit('placa_tracto', found.Placa || found.placa || '');
                } else if (!cVal) {
                    setSelectValueEdit('placa_tracto', '');
                }
            });
        }
        
        // Placa Carreta <-> Constancia Carreta
        const placaCarretaSelect = document.getElementById('placa_carreta');
        const constanciaCarretaSelect = document.getElementById('constancia_inscripcion_2');
        
        if (placaCarretaSelect) {
            placaCarretaSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                if (opcion && opcion.value) {
                    setSelectValueEdit('constancia_inscripcion_2', opcion.getAttribute('data-constancia') || '');
                } else {
                    setSelectValueEdit('constancia_inscripcion_2', '');
                }
            });
        }
        if (constanciaCarretaSelect) {
            constanciaCarretaSelect.addEventListener('change', function() {
                const opcion = this.options[this.selectedIndex];
                const cVal = (opcion && opcion.value) ? opcion.value : '';
                const placas = window.placasData || [];
                const found = placas.find(function(p){
                    return String(p.ConstanciaInscripcion || '') === String(cVal) && String(p.TipoPlaca || '').toUpperCase() === 'CARRETA';
                });
                if (found) {
                    setSelectValueEdit('placa_carreta', found.Placa || found.placa || '');
                } else if (!cVal) {
                    setSelectValueEdit('placa_carreta', '');
                }
            });
        }
    }

    // Setear un valor en un select/input (con manejo de Choices.js).
    // Si el elemento es <select> y el valor no existe entre sus opciones,
    // lo agrega automáticamente para no perder datos guardados previamente.
    function setSelectValueEdit(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        if (value === null || value === undefined) value = '';
        value = String(value);
        if (el.tagName === 'SELECT' && value !== '') {
            let existe = false;
            for (let i = 0; i < el.options.length; i++) {
                if (String(el.options[i].value) === value) { existe = true; break; }
            }
            if (!existe) {
                const opt = document.createElement('option');
                opt.value = value;
                opt.text = value;
                el.appendChild(opt);
            }
        }
        try { el.value = value; } catch(e){}
        const ci = (choicesInstances && choicesInstances[id]) || (window.choicesInstances && window.choicesInstances[id]);
        if (ci && typeof ci.setChoiceByValue === 'function') {
            try { ci.setChoiceByValue(value); } catch(e){}
        }
    }

    // Limpiar estilos inline de deshabilitado en el contenedor Choices de un elemento
    // (incluye los !important aplicados por bloquearControles/forceDisableAll).
    // Localiza el contenedor con varias estrategias para máxima robustez.
    function limpiarEstilosChoices(el) {
        if (!el) return;
        let choicesEl = null;
        // 1) Hermano inmediato (posición estándar de Choices.js)
        const sib = el.nextElementSibling;
        if (sib && sib.classList && sib.classList.contains('choices')) choicesEl = sib;
        // 2) Selector por id adyacente (#id + .choices)
        if (!choicesEl && el.id) {
            try { choicesEl = document.querySelector('#' + el.id + ' + .choices'); } catch(e){}
        }
        // 3) Cualquier .choices que contenga el select
        if (!choicesEl) {
            choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(el));
        }
        // 4) Desde la propia instancia Choices (propiedades DOM con clase .choices)
        if (!choicesEl && window.choicesInstances && window.choicesInstances[el.id]) {
            const ci = window.choicesInstances[el.id];
            try {
                for (const k in ci) {
                    const v = ci[k];
                    if (v && v.nodeType === 1 && v.classList && v.classList.contains('choices')) { choicesEl = v; break; }
                }
            } catch(ee){}
        }
        if (!choicesEl) return;
        // Limpiar clases, atributos y estado del contenedor
        choicesEl.classList.remove('is-disabled');
        choicesEl.removeAttribute('aria-disabled');
        choicesEl.removeAttribute('disabled');
        try { choicesEl.disabled = false; } catch(e){}
        const inner = choicesEl.querySelector('.choices__inner');
        if (inner) {
            inner.removeAttribute('aria-disabled');
            inner.removeAttribute('disabled');
            try { inner.disabled = false; } catch(e){}
            ['background-color','color','cursor','pointer-events','box-shadow','padding','min-height','max-height','height','border','border-radius','opacity','user-select'].forEach(function(p){
                try { inner.style.removeProperty(p); } catch(e){}
            });
        }
        ['background-color','border','border-radius','box-shadow','opacity','pointer-events'].forEach(function(p){
            try { choicesEl.style.removeProperty(p); } catch(e){}
        });
        const item = choicesEl.querySelector('.choices__list--single .choices__item');
        if (item) {
            try { item.style.removeProperty('color'); } catch(e){}
            try { item.style.removeProperty('display'); } catch(e){}
            try { item.style.removeProperty('white-space'); } catch(e){}
        }
        Array.from(choicesEl.querySelectorAll('input,button')).forEach(function(i){
            try { i.removeAttribute('disabled'); i.disabled = false; } catch(e){}
            try { i.style.removeProperty('pointer-events'); } catch(e){}
            try { i.style.removeProperty('background'); } catch(e){}
        });
    }

    // Limpiar TODOS los contenedores .choices de la página (red de seguridad).
    // Quita cualquier estilo !important residual de deshabilitado y la clase is-disabled.
    function limpiarTodosLosChoices() {
        Array.from(document.querySelectorAll('.choices')).forEach(function(c){
            try {
                c.classList.remove('is-disabled');
                c.removeAttribute('aria-disabled');
                c.removeAttribute('disabled');
                try { c.disabled = false; } catch(e){}
                const inner = c.querySelector('.choices__inner');
                if (inner) {
                    inner.removeAttribute('aria-disabled');
                    inner.removeAttribute('disabled');
                    try { inner.disabled = false; } catch(e){}
                    ['background-color','color','cursor','pointer-events','box-shadow','padding','min-height','max-height','height','border','border-radius','opacity'].forEach(function(p){
                        try { inner.style.removeProperty(p); } catch(e){}
                    });
                }
                ['background-color','border','border-radius','box-shadow','opacity','pointer-events'].forEach(function(p){
                    try { c.style.removeProperty(p); } catch(e){}
                });
                Array.from(c.querySelectorAll('input,button')).forEach(function(i){
                    try { i.removeAttribute('disabled'); i.disabled = false; } catch(e){}
                    try { i.style.removeProperty('pointer-events'); } catch(e){}
                    try { i.style.removeProperty('background'); } catch(e){}
                });
            } catch(e){}
        });
    }

    // Destruir y recrear la instancia Choices de un select, garantizando que quede
    // HABILITADA e interactuable (independientemente del estado previo de la instancia).
    function recrearChoicesSelect(id, placeholderText) {
        const el = document.getElementById(id);
        if (!el) return;
        try {
            const ci = (choicesInstances && choicesInstances[id]) || (window.choicesInstances && window.choicesInstances[id]);
            if (ci && typeof ci.destroy === 'function') { try { ci.destroy(); } catch(e){} }
            const cont = el.nextElementSibling;
            if (cont && cont.classList && cont.classList.contains('choices')) {
                try { cont.remove(); } catch(e){}
            }
            try { el.disabled = false; el.removeAttribute('disabled'); } catch(e){}
            const nueva = new Choices(el, {
                searchEnabled: true,
                searchChoices: true,
                shouldSort: false,
                itemSelectText: '',
                allowHTML: false,
                searchResultLimit: 100,
                position: 'auto',
                placeholder: true,
                placeholderValue: placeholderText || 'Seleccione',
                noResultsText: 'No se encontraron resultados',
                removeItemButton: false,
                duplicateItemsAllowed: false,
            });
            if (window.choicesInstances) window.choicesInstances[id] = nueva;
            if (choicesInstances) choicesInstances[id] = nueva;
            // Re-aplicar la selección actual del select nativo a la nueva instancia
            try { if (el.value) { nueva.setChoiceByValue(el.value); } } catch(e){}
            try { if (typeof window.updateAllChoicesWrap === 'function') setTimeout(window.updateAllChoicesWrap, 120); } catch(e){}
        } catch(e) { console.warn('[Edición] recrearChoicesSelect error #' + id, e); }
    }

    // Exponer globalmente para que despachosexternos.js pueda recrear instancias Choices
    // si su propio listener de "Modificar" (habilitarControlesParaModificar) es el que actúa.
    try { window.recrearChoicesSelectEdit = recrearChoicesSelect; } catch(e){}

    // Configurar eventos de productos
    function setupProductos() {
        // Sincronizar instancias Choices locales con las globales (creadas por despachosexternos.js)
        try { initChoices(); } catch(e) { console.warn('[Edición] initChoices en setupProductos falló:', e); }
        const productoSelect = document.getElementById('producto');
        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        
        if (productoSelect) {
            productoSelect.addEventListener('change', function() {
                const opcionSeleccionada = this.options[this.selectedIndex];
                try{ console.log('[despExtEdit] producto change selected:', opcionSeleccionada ? (opcionSeleccionada.text || opcionSeleccionada.value) : null); }catch(e){}
                if (opcionSeleccionada && opcionSeleccionada.value) {
                    const codigo = opcionSeleccionada.getAttribute('data-codigo');
                    try{ console.log('[despExtEdit] producto change data-codigo=', codigo); }catch(e){}
                    if (codigoInput) {
                        const val = (codigo === '0' || codigo === 0) ? '0' : (codigo != null ? String(codigo) : '');
                        try{ console.log('[despExtEdit] asignando codigoInput.value =', val); }catch(e){}
                        codigoInput.value = val;
                    }
                    try { setTimeout(function(){ if(window.updateProductoChoiceWrap) window.updateProductoChoiceWrap(); }, 50); } catch(e){}
                } else {
                    if (codigoInput) codigoInput.value = '';
                }
            });
        }
        
        // Botón agregar producto - clonar para remover listeners previos del script compartido
        const btnAgregar = document.getElementById('btnAgregar');
        if (btnAgregar) {
            try {
                const btnAgregarNuevo = btnAgregar.cloneNode(true);
                btnAgregar.parentNode.replaceChild(btnAgregarNuevo, btnAgregar);
                console.log('[Edición] btnAgregar clonado para remover listeners previos');
                btnAgregarNuevo.addEventListener('click', agregarProducto);
            } catch (e) {
                // Fallback: si no se pudo clonar por X motivo, adjuntar el listener adicionalmente
                console.warn('[Edición] No fue posible clonar btnAgregar, adjuntando listener adicional', e);
                btnAgregar.addEventListener('click', agregarProducto);
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
        
        // Botón quitar producto - clonar para remover listeners previos del script compartido
        const btnQuitar = document.getElementById('btnQuitar');
        if (btnQuitar) {
            try {
                const btnQuitarNuevo = btnQuitar.cloneNode(true);
                btnQuitar.parentNode.replaceChild(btnQuitarNuevo, btnQuitar);
                console.log('[Edición] btnQuitar clonado para remover listeners previos');
                btnQuitarNuevo.addEventListener('click', quitarProducto);
            } catch (e) {
                console.warn('[Edición] No fue posible clonar btnQuitar, adjuntando listener adicional', e);
                btnQuitar.addEventListener('click', quitarProducto);
            }
        }
        
        // Botón limpiar productos - clonar para remover listeners previos del script compartido
        const btnLimpiar = document.getElementById('btnLimpiar');
        if (btnLimpiar) {
            try {
                const btnLimpiarNuevo = btnLimpiar.cloneNode(true);
                btnLimpiar.parentNode.replaceChild(btnLimpiarNuevo, btnLimpiar);
                console.log('[Edición] btnLimpiar clonado para remover listeners previos');
                btnLimpiarNuevo.addEventListener('click', function(){
                    try{ if (typeof rebuildProductoSelectEdit === 'function') rebuildProductoSelectEdit(); }catch(e){}
                    try{ if (typeof limpiarFormularioProducto === 'function') limpiarFormularioProducto(); }catch(e){}
                });
            } catch (e) {
                console.warn('[Edición] No fue posible clonar btnLimpiar, adjuntando listener adicional', e);
                btnLimpiar.addEventListener('click', function(){
                    try{ if (typeof rebuildProductoSelectEdit === 'function') rebuildProductoSelectEdit(); }catch(e){}
                    try{ if (typeof limpiarFormularioProducto === 'function') limpiarFormularioProducto(); }catch(e){}
                });
            }
        }
    }

    // Ensure product choice wrapping/resizing exists for long product names
    if (!window.updateProductoChoiceWrap) {
        function updateProductoChoiceWrap() {
            try {
                const productoSelect = document.getElementById('producto');
                if (!productoSelect) return;

                // Locate the Choices container for this select
                let choicesEl = productoSelect.nextElementSibling;
                if (!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')) {
                    choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(productoSelect));
                }
                if (!choicesEl) return;

                const container = choicesEl.querySelector('.choices__inner');
                const single = choicesEl.querySelector('.choices__list--single');
                const item = single ? single.querySelector('.choices__item') : null;
                if (!container || !single || !item) return;

                const itemWidth = item.scrollWidth || item.offsetWidth || 0;
                const containerWidth = container.clientWidth || container.offsetWidth || 0;
                const itemHeight = item.scrollHeight || item.offsetHeight || 0;
                const containerHeight = container.clientHeight || container.offsetHeight || 0;

                const needWrap = (itemWidth > containerWidth - 6) || (itemHeight > containerHeight - 4) || (single.scrollHeight > containerHeight - 4) || (container.scrollHeight > containerHeight);

                if (needWrap) {
                    // apply wrapping styles
                    choicesEl.classList.add('choices-wrap');
                    try {
                        const effectiveWidth = Math.max(80, container.offsetWidth - 40);
                        item.style.width = effectiveWidth + 'px';
                        item.style.maxWidth = effectiveWidth + 'px';
                        item.style.whiteSpace = 'normal';
                        item.style.wordBreak = 'break-word';
                        item.style.overflowWrap = 'anywhere';
                        item.style.display = 'block';

                        single.style.whiteSpace = 'normal';
                        single.style.overflow = 'visible';
                        single.style.height = 'auto';
                        single.style.width = '100%';

                        const applyHeight = (attempt = 1) => {
                            try {
                                const realHeight = Math.max(single.scrollHeight, item.scrollHeight, item.offsetHeight);
                                const targetHeight = Math.max(38, realHeight + 10);
                                try { choicesEl.dataset.wrapApplied = '1'; choicesEl.dataset.wrapHeight = String(targetHeight); } catch(e){}
                                try { container.style.setProperty('min-height', targetHeight + 'px', 'important'); } catch(e){}
                                try { container.style.setProperty('overflow', 'visible', 'important'); } catch(e){}
                                try { choicesEl.style.setProperty('min-height', targetHeight + 'px', 'important'); } catch(e){}
                                void(container.offsetHeight);
                                const currentH = container.clientHeight || container.offsetHeight || 0;
                                if (currentH < targetHeight && attempt < 4) setTimeout(() => applyHeight(attempt + 1), attempt * 120);
                            } catch(e){}
                        };

                        setTimeout(() => applyHeight(1), 40);
                        setTimeout(() => applyHeight(2), 180);
                        setTimeout(() => applyHeight(3), 420);
                    } catch(e){}
                } else {
                    // revert
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
                        try { if (choicesEl.dataset) { delete choicesEl.dataset.wrapApplied; delete choicesEl.dataset.wrapHeight; } } catch(e){}
                    } catch(e){}
                }
            } catch (e) { console.warn('updateProductoChoiceWrap error', e); }
        }
        window.updateProductoChoiceWrap = updateProductoChoiceWrap;
    }

    // attach listeners to producto select if present
    try {
        const pSel = document.getElementById('producto');
        if (pSel) {
            pSel.addEventListener('change', () => { try { setTimeout(window.updateProductoChoiceWrap, 50); } catch(e){} });
            window.addEventListener('resize', () => { try { setTimeout(window.updateProductoChoiceWrap, 50); } catch(e){} });
            setTimeout(() => { try { if (window.updateProductoChoiceWrap) window.updateProductoChoiceWrap(); } catch(e){} }, 200);
            setTimeout(() => { try { if (window.updateProductoChoiceWrap) window.updateProductoChoiceWrap(); } catch(e){} }, 600);
        }
    } catch(e){}

    function agregarProducto() {
        console.log('[Edición][DIAG] agregarProducto invoked - editIndex:', editIndex, 'updatePhase:', updatePhase);
        // Obtener campos del DOM (autónomo)
        const productoSelect = document.getElementById('producto');
        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        const comentariosInput = document.getElementById('comentarios');

        console.log('[Edición][DIAG] estado campos antes validación - productoSelect.value=', productoSelect ? productoSelect.value : 'NOSELECT', 'cantidad=', cantidadInput ? cantidadInput.value : 'NOCANT', 'choicesProducto=', choicesInstances['producto'] ? 'yes' : 'no');

        // If a row was selected but not yet loaded into controls, the Add button acts as 'Actualizar' (populate controls)
        if (editIndex !== null && updatePhase === 'selected') {
            console.log('[Edición][DIAG] agregarProducto -> cargarFilaEnControlesLocal(', editIndex, ')');
            cargarFilaEnControlesLocal(editIndex);
            return;
        }

        if (!productoSelect || !productoSelect.options || !productoSelect.value) {
            mostrarMensajeError('Debe seleccionar un producto');
            return;
        }

        if (!cantidadInput || !cantidadInput.value || Number(cantidadInput.value) <= 0) {
            mostrarMensajeError('Debe ingresar una cantidad válida');
            try { cantidadInput.focus(); } catch(e){}
            return;
        }

        const opcionSeleccionada = productoSelect.options[productoSelect.selectedIndex];
        const productoNombre = opcionSeleccionada ? opcionSeleccionada.text : '';
        const codigo = codigoInput ? codigoInput.value : '';
        const unidadMedida = opcionSeleccionada ? (opcionSeleccionada.getAttribute('data-unidadmedida') || '') : '';
        const cantidad = cantidadInput.value;
        const comentarios = comentariosInput ? comentariosInput.value : '';

        // If we are in editing mode, save changes to the selected row
        if (editIndex !== null && updatePhase === 'editing') {
            console.log('[Edición][DIAG] guardar edición fila index=', editIndex, 'producto=', productoNombre, 'codigo=', codigo, 'cantidad=', cantidad);
            datosGrilla[editIndex] = { producto: productoNombre, codigo: codigo, unidadMedida: unidadMedida, cantidad: cantidad, comentarios: comentarios, productoId: productoSelect.value || '' };
            actualizarGrilla();
            try { limpiarFormularioProducto(); } catch(e) {}
            // Mantener el botón Guardar habilitado para que el usuario pueda guardar el vale
            habilitarBotonesGuardado(true);
            return;
        }

        // Default: add new product
        datosGrilla.push({ producto: productoNombre, codigo: codigo, unidadMedida: unidadMedida, cantidad: cantidad, comentarios: comentarios, productoId: productoSelect.value || '' });

        actualizarGrilla();
        try { limpiarFormularioProducto(); } catch(e) {}
        habilitarBotonesGuardado();
    }

    function quitarProducto() {
        const grilla = document.getElementById('grillaDespacho')?.querySelector('tbody');
        if (!grilla) return;
        
        const filaSeleccionada = grilla.querySelector('tr.table-active');
        if (!filaSeleccionada) {
            mostrarMensajeError('Debe seleccionar un producto de la grilla');
            return;
        }
        
        const indice = parseInt(filaSeleccionada.cells[0].textContent) - 1;
        datosGrilla.splice(indice, 1);
        
        actualizarGrilla();
        
        if (datosGrilla.length === 0) {
            deshabilitarBotonesGuardado();
        }
    }

    function limpiarProductos() {
        if (datosGrilla.length === 0) {
            mostrarMensajeError('No hay productos para limpiar');
            return;
        }
        
        if (!confirm('¿Está seguro de limpiar todos los productos?')) {
            return;
        }
        
        datosGrilla = [];
        actualizarGrilla();
        limpiarFormularioProducto();
        deshabilitarBotonesGuardado();
    }

    function actualizarGrilla() {
        const grilla = document.getElementById('grillaDespacho')?.querySelector('tbody');
        console.log('[Edición] actualizarGrilla ejecutándose. Grilla encontrada:', !!grilla, 'datosGrilla.length:', datosGrilla.length);
        if (!grilla) {
            console.error('[Edición] No se encontró el tbody de grillaDespacho');
            return;
        }
        
        grilla.innerHTML = '';
        
        datosGrilla.forEach((item, index) => {
            console.log('[Edición] Agregando fila', index+1, ':', item);
            const fila = document.createElement('tr');
            fila.style.cursor = 'pointer';
            fila.innerHTML = `
                <td class="text-center">${index + 1}</td>
                <td class="campo-codigo text-center">${String(item.codigo ?? '')}</td>
                <td class="text-left">${item.producto || ''}</td>
                <td class="campo-unidad text-center">${item.unidadMedida || ''}</td>
                <td class="campo-cantidad text-center">${item.cantidad || ''}</td>
                <td>${item.comentarios || ''}</td>
            `;
            
            fila.addEventListener('click', function() {
                grilla.querySelectorAll('tr').forEach(r => r.classList.remove('table-active'));
                this.classList.add('table-active');
                // marcar selección pero no cargar controles aún
                editIndex = index;
                updatePhase = 'selected';
                console.log('[Edición][DIAG] fila seleccionada index=', editIndex, 'updatePhase=', updatePhase);
                const btnAgregar = document.getElementById('btnAgregar');
                if (btnAgregar) {
                    btnAgregar.textContent = 'Actualizar';
                    btnAgregar.classList.remove('btn-success');
                    btnAgregar.classList.add('btn-primary');
                }
            });
            
            grilla.appendChild(fila);
        });
        console.log('[Edición] actualizarGrilla completado. Filas agregadas:', grilla.children.length);
        try {
            if (window.updateProductoChoiceWrap) {
                setTimeout(window.updateProductoChoiceWrap, 50);
                setTimeout(window.updateProductoChoiceWrap, 200);
                setTimeout(window.updateProductoChoiceWrap, 500);
            }
        } catch(e) { /* ignore */ }
    }

    function limpiarFormularioProducto() {
        if (choicesInstances['producto']) {
            choicesInstances['producto'].setChoiceByValue('');
        }
        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        const comentariosInput = document.getElementById('comentarios');
        
        if (codigoInput) codigoInput.value = '';
        if (cantidadInput) cantidadInput.value = '';
        if (comentariosInput) comentariosInput.value = '';
        editIndex = null;
        updatePhase = null;
        // Restaurar el botón a "Agregar" tras guardar/limpiar (igual que en nuevo registro)
        const btnAgregar = document.getElementById('btnAgregar');
        if (btnAgregar) {
            btnAgregar.textContent = 'Agregar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
        }
    }

    function cargarFilaEnControlesLocal(idx) {
        console.log('[Edición][DIAG] cargarFilaEnControlesLocal called with idx=', idx);
        if (idx === null || typeof idx === 'undefined') return;
        const item = datosGrilla[idx];
        if (!item) return;
        const productoSelect = document.getElementById('producto');
        const codigoInput = document.getElementById('codigo');
        const cantidadInput = document.getElementById('cantidad');
        const comentariosInput = document.getElementById('comentarios');
        const unidadInput = document.getElementById('unidadMedida');
        try{ console.log('[Edición][DIAG] item=', JSON.stringify(item), '| productoSelect options count=', productoSelect ? productoSelect.options.length : 'NO_SELECT', '| productosData length=', window.productosData ? window.productosData.length : 0); }catch(e){}

        // Reconstruir el select incluyendo el producto de la fila en edición (no excluirlo),
        // para que el producto de la fila seleccionada esté disponible y pueda mostrarse en el select.
        try { rebuildProductoSelectEdit(idx); } catch(e){ console.warn('[Edición][DIAG] error rebuildProductoSelectEdit en cargarFilaEnControlesLocal', e); }

        // Seleccionar producto por id si lo tenemos, si no por texto
        if (productoSelect && productoSelect.options && productoSelect.options.length > 0) {
            const opts = Array.from(productoSelect.options);
            let opt = null;
            if (item.productoId) opt = opts.find(o => String(o.value) === String(item.productoId));
            if (!opt) opt = opts.find(o => (o.getAttribute('data-codigo')||'').trim() === (item.codigo||'').trim() || (o.textContent||'').trim() === (item.producto||'').trim());
            try{ console.log('[Edición][DIAG] opt encontrado para select:', opt ? (opt.value + ':' + opt.text) : 'NULL (el producto de la fila NO está en el select; probablemente excluido por rebuildProductoSelectEdit)'); }catch(e){}
            if (opt) {
                productoSelect.value = opt.value;
                try { if (choicesInstances['producto']) choicesInstances['producto'].setChoiceByValue(opt.value); } catch(e){}
                console.log('[Edición][DIAG] producto seleccionado en controls: value=', productoSelect.value, 'opt.value=', opt.value, 'opt.text=', opt.text);
                // Si por alguna razón Choices no aplicó el valor, intentar reintentar tras breve delay
                if ((!productoSelect.value || String(productoSelect.value).trim() === '') && choicesInstances['producto']) {
                    setTimeout(function(){ try { choicesInstances['producto'].setChoiceByValue(opt.value); productoSelect.value = opt.value; console.log('[Edición][DIAG] re-setChoiceByValue attempted'); }catch(e){} }, 60);
                }
            }
        }

        if (codigoInput) codigoInput.value = (item.codigo === '0' || item.codigo === 0) ? '0' : (item.codigo != null ? String(item.codigo) : '');
        if (cantidadInput) cantidadInput.value = item.cantidad || '';
        if (comentariosInput) comentariosInput.value = item.comentarios || '';
        if (unidadInput) unidadInput.value = item.unidadMedida || '';

        // pasar a editing
        updatePhase = 'editing';
        console.log('[Edición][DIAG] cargarFilaEnControlesLocal - campos cargados, updatePhase=', updatePhase, 'editIndex=', idx);
        const btnAgregar = document.getElementById('btnAgregar');
        if (btnAgregar) {
            btnAgregar.textContent = 'Guardar';
            btnAgregar.classList.remove('btn-primary');
            btnAgregar.classList.add('btn-success');
        }
    }

    // Cargar despacho desde servidor para modo edición: poblar controles, grilla y bloquear controles
    function cargarDespachoParaEdicion(despachoId) {
        if(!despachoId) { mostrarMensajeError('Id inválido'); return; }
        // FIX: usar APP_URL (raíz) en vez de BASE_URL (/public) para que el front controller enrute correctamente
        var url = (window.APP_URL || window.BASE_URL || '') + '/despachosexternos/getById?id=' + encodeURIComponent(despachoId);
        try{
            fetch(url, { method: 'GET', credentials: 'same-origin' }).then(function(r){ if(!r.ok) return r.text().then(function(t){ throw new Error('HTTP ' + r.status + ': ' + (t||r.statusText)); }); return r.json(); })
            .then(function(res){
                if(!res || !res.success){ mostrarMensajeError((res && res.message) ? res.message : 'No se encontró el vale'); return; }
                var data = res.data || {};
                // Poblar cabecera
                try{
                    var fechaEl = document.getElementById('fecha'); if(fechaEl) fechaEl.value = data.Fecha || data.fecha || '';
                    var turnoElLocal = document.getElementById('turno'); if(turnoElLocal && data.Turno) { turnoElLocal.value = data.Turno; if(window.choicesInstances && window.choicesInstances['turno']) try{ window.choicesInstances['turno'].setChoiceByValue(String(data.Turno)); }catch(e){} }
                    var destinoEl = document.getElementById('destino'); if(destinoEl) destinoEl.value = data.Destino || data.destino || '';
                    var rucEl = document.getElementById('ruc'); if(rucEl) rucEl.value = data.RUC || data.ruc || '';
                    var direccionEl = document.getElementById('direccion'); if(direccionEl) direccionEl.value = data.Direccion || data.direccion || '';
                    var despachadorEl = document.getElementById('despachador'); if(despachadorEl) despachadorEl.value = data.Despachador || data.despachador || '';
                    var choferEl = document.getElementById('chofer'); if(choferEl && data.Chofer) { choferEl.value = data.Chofer; if(window.choicesInstances && window.choicesInstances['chofer']) try{ window.choicesInstances['chofer'].setChoiceByValue(String(data.Chofer)); }catch(e){} }
                    var breveteEl = document.getElementById('brevete'); if(breveteEl) breveteEl.value = data.Licencia || data.LicenciaChofer || data.brevete || data.Brevete || '';
                    var transportistaEl = document.getElementById('transportista'); if(transportistaEl && data.Transportista){ transportistaEl.value = data.Transportista; if(window.choicesInstances && window.choicesInstances['transportista']) try{ window.choicesInstances['transportista'].setChoiceByValue(String(data.Transportista)); }catch(e){} }
                    var rucTransportistaEl = document.getElementById('ruc_transportista'); if(rucTransportistaEl) rucTransportistaEl.value = data.RUC_Transportista || data.ruc_transportista || '';
                    var placaEl = document.getElementById('placa_tracto'); if(placaEl && data.Placa_Tracto){ placaEl.value = data.Placa_Tracto; if(window.choicesInstances && window.choicesInstances['placa_tracto']) try{ window.choicesInstances['placa_tracto'].setChoiceByValue(String(data.Placa_Tracto)); }catch(e){} }
                    var constanciaEl = document.getElementById('constancia_inscripcion'); if(constanciaEl) constanciaEl.value = data.Constancia_Inscripcion || data.ConstanciaInscripcion || '';
                    var constanciaCarretaEl = document.getElementById('constancia_inscripcion_2'); if(constanciaCarretaEl) constanciaCarretaEl.value = data.Constancia_Inscripcion_2 || data.ConstanciaInscripcion_2 || '';
                    var guiaEl = document.getElementById('guiaRemision'); if(guiaEl) guiaEl.value = data.GR || data.guiaRemision || data.guia || '';
                    var correlEl = document.getElementById('correlativoVale'); if(correlEl) correlEl.setAttribute('data-id', despachoId);
                }catch(e){ console.warn('[Edición] error poblando cabecera desde servidor', e); }

                // Poblar grilla
                try{
                    datosGrilla = [];
                    var productos = data.productos || data.Productos || [];
                    productos.forEach(function(p){
                        datosGrilla.push({ producto: p.producto || p.Producto || p.descripcion || p.Descripcion || '', codigo: p.codigo || p.Codigo || p.CodigoProducto || '', unidadMedida: p.unidadMedida || p.UnidadMedida || p.unidad || '', cantidad: p.cantidad || p.Cantidad || '', comentarios: p.comentarios || p.Comentarios || '', productoId: p.Id || p.id || p.ProductoId || p.IdProducto || '' });
                    });
                    actualizarGrilla();
                }catch(e){ console.warn('[Edición] error poblando grilla desde servidor', e); }

                // Bloquear controles para evitar ediciones accidentales (igual que vista Nuevo/Internos)
                try{
                    // Preferir container con id formDespacho si existe, sino usar body
                    var container = document.getElementById('formDespacho') || document.querySelector('.despacho-form') || document.body;
                    Array.prototype.slice.call(container.querySelectorAll('input,select,textarea,button')).forEach(function(el){
                        if(!el) return;
                        // No deshabilitar botones de navegación/imprimir o el propio btnModificar
                        if(el.id === 'btnModificar' || el.id === 'btnImprimir') return;
                        try{ el.setAttribute('disabled','disabled'); }catch(e){}
                    });

                    // Deshabilitar instancias Choices (tanto las locales como las globales si existen)
                    try{
                        var ciObj = (typeof choicesInstances !== 'undefined' && choicesInstances) ? choicesInstances : {};
                        var globalCI = (window.choicesInstances) ? window.choicesInstances : {};
                        Object.keys(ciObj || {}).forEach(function(k){ try{ if(ciObj[k] && typeof ciObj[k].disable === 'function') ciObj[k].disable(); }catch(e){} });
                        Object.keys(globalCI || {}).forEach(function(k){ try{ if(globalCI[k] && typeof globalCI[k].disable === 'function') globalCI[k].disable(); }catch(e){} });
                    }catch(e){}

                    // Asegurar que btnModificar quede habilitado para permitir activar edición
                    try{ var bm = document.getElementById('btnModificar'); if(bm) bm.removeAttribute('disabled'); }catch(e){}
                }catch(e){ console.warn('[Edición] error bloqueando controles', e); }

            }).catch(function(err){ console.error('Error cargando despacho para edición:', err); mostrarMensajeError('No se pudo cargar el vale: ' + (err && err.message ? err.message : 'error')); });
        }catch(e){ console.error('fetch error cargarDespachoParaEdicion', e); mostrarMensajeError('Error interno al cargar el vale'); }
    }

    // Forzar ajuste (wrap/resize) del control Choices del producto cuando el texto es largo
    function updateProductoChoiceWrap() {
        try {
            const productoSelect = document.getElementById('producto');
            if (!productoSelect) return;

            const allChoices = Array.from(document.querySelectorAll('.choices'));
            const choicesEl = allChoices.find(c => c.querySelector('select#producto')) || productoSelect.nextElementSibling;
            if (!choicesEl) return;

            const container = choicesEl.querySelector('.choices__inner');
            const single = choicesEl.querySelector('.choices__list--single');
            const item = single ? single.querySelector('.choices__item') : null;
            if (!container || !single || !item) return;

            const itemWidth = item.scrollWidth || item.offsetWidth || 0;
            const containerWidth = container.clientWidth || container.offsetWidth || 0;
            const itemHeight = item.scrollHeight || item.offsetHeight || 0;
            const containerHeight = container.clientHeight || container.offsetHeight || 0;

            const needWrap = (itemWidth > containerWidth - 6) || (itemHeight > containerHeight - 4) || (single.scrollHeight > containerHeight - 4) || (container.scrollHeight > containerHeight);

            if (needWrap) {
                choicesEl.classList.add('choices-wrap');
                try {
                    const targetW = Math.max(80, container.offsetWidth - 40);
                    item.style.width = targetW + 'px';
                    item.style.maxWidth = targetW + 'px';
                    item.style.whiteSpace = 'normal';
                    item.style.wordBreak = 'break-word';
                    item.style.overflowWrap = 'anywhere';
                    item.style.display = 'block';

                    single.style.whiteSpace = 'normal';
                    single.style.overflow = 'visible';
                    single.style.height = 'auto';
                    single.style.width = '100%';

                    setTimeout(() => {
                        const realHeight = Math.max(single.scrollHeight, item.scrollHeight, item.offsetHeight);
                        if (realHeight > 32) {
                            const targetHeight = realHeight + 10;
                            container.style.cssText = `height: auto !important; min-height: ${targetHeight}px !important; max-height: none !important; overflow: visible !important; display: block !important; padding-top: 5px !important; padding-bottom: 5px !important;`;
                        }
                    }, 80);
                } catch (e) { /* ignore */ }
            } else {
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
                    item.style.width = '';
                } catch (e) { /* ignore */ }
            }
        } catch (e) { console.warn('[Edición Externos] updateProductoChoiceWrap error', e); }
    }

    // Exponer para que otros scripts puedan invocarlo si es necesario
    try { window.updateProductoChoiceWrap = updateProductoChoiceWrap; } catch(e) {}

    // Configurar botones principales
    function setupBotones() {
        const btnBuscarVale = document.getElementById('btnBuscarVale');
        const btnGuardar = document.getElementById('btnGuardar');
        const btnModificar = document.getElementById('btnModificar');
        
        if (btnBuscarVale) {
            console.log('[Edición] Configurando btnBuscarVale');
            try{
                btnBuscarVale.addEventListener('click', function(e) {
                    console.log('[Edición] Click en btnBuscarVale detectado');
                    abrirModalBusqueda();
                });
                console.log('[Edición] Listener addEventListener configurado en btnBuscarVale');
            }catch(e){ console.warn('[Edición] no se pudo adjuntar listener a btnBuscarVale', e); }
            // Backup: asignar también onclick en caso de que addEventListener no se ejecute
            try{ 
                btnBuscarVale.onclick = function(e){ 
                    console.log('[Edición] Click en btnBuscarVale (onclick) detectado'); 
                    abrirModalBusqueda(); 
                }; 
                console.log('[Edición] onclick configurado en btnBuscarVale');
            }catch(e){}
        } else {
            console.warn('[Edición] btnBuscarVale NO encontrado en setupBotones');
        }
        
        if (btnGuardar) {
            // IMPORTANTE: Remover listeners previos de despachosexternos.js clonando el botón
            const btnGuardarNuevo = btnGuardar.cloneNode(true);
            btnGuardar.parentNode.replaceChild(btnGuardarNuevo, btnGuardar);
            console.log('[Edición] btnGuardar clonado para remover listeners previos');
            
            // Agregar solo el listener de edición con protección
            btnGuardarNuevo.addEventListener('click', function() {
                if (_guardandoDespExtEditEnProceso) {
                    console.log('[Edición] Guardado ya en proceso, ignorando clic');
                    return;
                }
                _guardandoDespExtEditEnProceso = true;
                
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
            try {
                // Clonar y reemplazar para eliminar listeners previos cargados por otros scripts
                const btnModificarNuevo = btnModificar.cloneNode(true);
                btnModificar.parentNode.replaceChild(btnModificarNuevo, btnModificar);
                btnModificarNuevo.addEventListener('click', modificarDespacho);
                console.log('[Edición] btnModificar clonado y listener asignado');
            } catch (e) {
                console.warn('[Edición] No fue posible clonar btnModificar, adjuntando listener directamente', e);
                btnModificar.addEventListener('click', modificarDespacho);
            }
        }
    }

    // Hacer disponible la función de abrir modal en el scope global como respaldo
    try{ window.abrirModalBusquedaExternos = abrirModalBusqueda; }catch(e){}

    function setupModoEdicion() {
        // Configurar modal de búsqueda
        const btnFiltrarVales = document.getElementById('btnFiltrarVales');
        
        if (btnFiltrarVales) {
            btnFiltrarVales.addEventListener('click', buscarVales);
        }
        
        // Enter en campo de búsqueda
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
                        window.cargarValeEdicion(_valeUnicoId);
                    } else {
                        buscarVales();
                    }
                }
            });
        }
    }

    function abrirModalBusqueda() {
        console.log('[Edición] abrirModalBusqueda ejecutado');
        const modalEl = document.getElementById('modalBuscarVale');
        const modal = new bootstrap.Modal(modalEl);
        
        // Corregir aria-hidden para evitar warning
        modalEl.addEventListener('shown.bs.modal', function() {
            modalEl.removeAttribute('aria-hidden');
            // Enfocar el campo de filtro al mostrarse el modal
            const filtro = document.getElementById('filtroNVale');
            if (filtro) {
                filtro.value = '';
                try { filtro.focus(); } catch(e) {}
                // Fallbacks: algunos scripts o navegadores roban foco; reintentar
                try { requestAnimationFrame(() => { try { filtro.focus(); } catch(e) {} }); } catch(e) {}
                setTimeout(() => { try { filtro.focus(); } catch(e) {} }, 50);
                setTimeout(() => { try { filtro.focus(); } catch(e) {} }, 200);
            }
        }, { once: true });
        
        modal.show();
    }

    function buscarVales() {
        const filtroNVale = document.getElementById('filtroNVale');
        const nvale = filtroNVale?.value.replace(/\D/g, '') || '';

        const params = new URLSearchParams();
        if (nvale) params.append('nvale', nvale);

        const endpoints = [
            (window.APP_URL || window.BASE_URL) + '/despachosexternos/buscarVales?' + params.toString(),
            (window.BASE_URL || window.APP_URL) + '/index.php?url=despachosexternos/buscarVales&' + params.toString(),
            (window.APP_URL || window.BASE_URL) + '/index.php?url=despachosexternos/buscarVales&' + params.toString()
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
                    try {
                        const data = JSON.parse(text);
                        if (data && data.success) {
                            mostrarResultadosVales(data.vales || []);
                            return Promise.resolve();
                        } else {
                            mostrarMensajeBusqueda('Error al buscar vales: ' + (data.error || 'Error desconocido'), 'danger');
                            return Promise.resolve();
                        }
                    } catch (e) {
                        console.error('[Edición] Respuesta no JSON al buscar vales (posible HTML):', { url, status, text: text.slice(0, 800) });
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
                <td><strong>VDE-${String(vale.NVale).padStart(6, '0')}</strong></td>
                <td>${formatearFecha(vale.Fecha)}</td>
                <td>${vale.Hora || ''}</td>
                <td>${vale.DestinoNombre || ''}</td>
                <td>${vale.ChoferNombre || ''}</td>
                <td class="text-center actions">
                    <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                        <button class="btn btn-sm btn-primary me-1 btn-cargar-vale" data-vale-id="${vale.Id}" title="Cargar vale">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="window.abrirModalAnular(${vale.Id}, 'VDE-${String(vale.NVale).padStart(6, '0')}')" title="Anular vale">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
            
            // Agregar event listener al botón de cargar
            const btnCargar = tr.querySelector('.btn-cargar-vale');
            if (btnCargar) {
                btnCargar.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const valeId = this.getAttribute('data-vale-id');
                    console.log('[Edición] Cargando vale ID:', valeId);
                    window.cargarValeEdicion(valeId);
                });
            }
        });
    }
    
    function formatearFecha(fecha) {
        if (!fecha) return '';
        try {
            const partes = fecha.split('-');
            if (partes.length === 3) {
                return `${partes[2]}/${partes[1]}/${partes[0]}`;
            }
        } catch(e) {}
        return fecha;
    }

    // Cargar vale para edición (robusto: probar endpoints y parseo seguro)
    window.cargarValeEdicion = function(valeId) {
        if (!valeId) return;

        console.log('[Edición] cargarValeEdicion iniciado para ID:', valeId);

        const endpoints = [
            (window.APP_URL || window.BASE_URL) + '/despachosexternos/obtenerVale?id=' + valeId,
            (window.BASE_URL || window.APP_URL) + '/index.php?url=despachosexternos/obtenerVale&id=' + valeId
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
                        if (data.success && data.vale) {
                            console.log('[Edición] Vale obtenido, guardando datos...');
                            window._valeTemp = data.vale;

                            // Cerrar modal de búsqueda - método robusto (reutiliza lógica previa)
                            try {
                                if (document.activeElement && document.activeElement.closest && document.activeElement.closest('#modalBuscarVale')) {
                                    document.activeElement.blur();
                                    document.body.focus();
                                }
                            } catch(e){}

                            const modalEl = document.getElementById('modalBuscarVale');
                            if (modalEl) {
                                try {
                                    const modal = bootstrap.Modal.getInstance(modalEl);
                                    if (modal) modal.hide(); else new bootstrap.Modal(modalEl).hide();
                                    modalEl.classList.remove('show');
                                    modalEl.style.display = 'none';
                                    modalEl.setAttribute('aria-hidden', 'true');
                                    modalEl.removeAttribute('aria-modal');
                                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                                    document.body.classList.remove('modal-open');
                                    setTimeout(() => {
                                        try {
                                            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                                            document.body.classList.remove('modal-open');
                                            document.documentElement.classList.remove('modal-open');
                                            document.body.style.overflow = 'auto';
                                            document.body.style.overflowY = 'auto';
                                            document.documentElement.style.overflow = 'auto';
                                            document.documentElement.style.overflowY = 'auto';
                                            Array.from(document.querySelectorAll('body *')).filter(el => {
                                                try {
                                                    const cs = getComputedStyle(el);
                                                    const pos = cs.position;
                                                    const z = cs.zIndex === 'auto' ? 0 : parseInt(cs.zIndex) || 0;
                                                    const rect = el.getBoundingClientRect();
                                                    return (pos === 'fixed' || pos === 'absolute') && z >= 1000 && rect.width >= window.innerWidth - 2 && rect.height >= window.innerHeight - 2;
                                                } catch(e) { return false; }
                                            }).forEach(el => { el.style.pointerEvents = 'none'; el.style.display = 'none'; });
                                            if (window._valeTemp) { poblarFormularioConVale(window._valeTemp); window._valeTemp = null; }
                                            void(document.body.offsetHeight);
                                        } catch(e) { console.error('[Edición] Error durante limpieza post-modal:', e); }
                                    }, 150);
                                } catch(e) { console.error('[Edición] Error cerrando modal:', e); }
                            } else {
                                console.warn('[Edición] No se encontró el elemento del modal');
                                // Si no existe modal, poblar formulario directamente
                                try { poblarFormularioConVale(window._valeTemp); window._valeTemp = null; } catch(e){}
                            }

                            return Promise.resolve();
                        } else {
                            mostrarMensajeError(data.message || 'Error al cargar el vale');
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

    // Reconstruir el select de productos excluyendo los que ya están en la grilla
    function rebuildProductoSelectEdit(skipIndex) {
        const productoSelect = document.getElementById('producto');
        if (!productoSelect) return;
        
        const descripcionesEnGrilla = new Set();
        datosGrilla.forEach((item, index) => {
            // No excluir la fila que se está editando para que su producto siga disponible en el select
            if (typeof skipIndex !== 'undefined' && skipIndex !== null && index === skipIndex) return;
            if (item.producto) descripcionesEnGrilla.add(String(item.producto).trim());
        });
        
        if (choicesInstances && choicesInstances['producto']) {
            try { choicesInstances['producto'].destroy(); } catch(e) {}
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
        
        choicesInstances = choicesInstances || {};
        choicesInstances['producto'] = new Choices(productoSelect, {
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
        valeIdActual = vale.Id;
        window.auditoriaIdActual = vale.Id;
        window.auditoriaModoActual = 'despacho_externo';
        console.log('[Edición][DEBUG] poblarFormularioConVale - vale.Id:', vale.Id, 'valeIdActual establecido a:', valeIdActual);
        
        // FIX: Setear data-id en el span correlativoVale como respaldo de seguridad.
        // Esto asegura que si el listener de despachosexternos.js (main page) se
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
        const bloques = ['bloqueNumeroVale', 'cardFechaTurno', 'cardDestino', 'cardTransporte', 'cardProductos', 'botonesGrilla', 'cardGrilla'];
        console.log('[Edición] Intentando mostrar bloques:', bloques);
        bloques.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                console.log('[Edición] Mostrando elemento:', id, 'clases antes:', elemento.className);
                elemento.classList.remove('d-none');
                console.log('[Edición] Mostrando elemento:', id, 'clases después:', elemento.className);
            } else {
                console.warn('[Edición] Elemento no encontrado en DOM:', id);
            }
        });
        
        // Correlativo
        const correlativoSpan = document.getElementById('correlativoVale');
        if (correlativoSpan) {
            correlativoSpan.textContent = 'VDE-' + String(vale.NVale).padStart(6, '0');
        }
        
        // Fecha
        const fechaInput = document.getElementById('fecha');
        if (fechaInput) fechaInput.value = vale.Fecha || '';
        
        // Turno
        // [DIAG-TURNO] Log del valor recibido y estado de la instancia antes de setear
        try {
            console.log('[Edición][DIAG-TURNO] vale.Turno recibido:', vale.Turno, '| TurnoNombre:', vale.TurnoNombre, '| choices.turno existe:', !!(choicesInstances && choicesInstances['turno']));
            if (choicesInstances && choicesInstances['turno']) {
                try { console.log('[Edición][DIAG-TURNO] ANTES setChoiceByValue -> getValue:', choicesInstances['turno'].getValue(true), '| isDisabled:', choicesInstances['turno'].isDisabled === true ? 'SÍ' : 'no'); } catch(e){}
            }
        } catch(e) {}
        // Reforzar el seteo del turno: setear el select nativo Y la instancia Choices para que
        // el valor de BD siempre se refleje.
        // IMPORTANTE: en Choices.js, setChoiceByValue() NO actualiza la selección visible si la
        // instancia está deshabilitada (guard interno `if (this._isDisabled) return;`). Por eso se
        // habilita temporalmente la instancia, se setea el valor y luego se vuelve a deshabilitar
        // (el bloqueo final de poblarFormularioConVale la deja deshabilitada con el valor correcto).
        if (vale.Turno) {
            const turnoSelectEl = document.getElementById('turno');
            if (turnoSelectEl) {
                try { turnoSelectEl.value = String(vale.Turno); } catch(e){}
            }
            const ciTurno = (choicesInstances && choicesInstances['turno']) || (window.choicesInstances && window.choicesInstances['turno']);
            if (ciTurno && typeof ciTurno.setChoiceByValue === 'function') {
                const estabaDeshabilitado = !!(turnoSelectEl && turnoSelectEl.disabled);
                try {
                    if (estabaDeshabilitado && typeof ciTurno.enable === 'function') ciTurno.enable();
                } catch(e) {}
                ciTurno.setChoiceByValue(String(vale.Turno));
                // Log de verificación: valor interno + texto visible del item del contenedor Choices
                try {
                    var _itemVisible = '';
                    if (turnoSelectEl) {
                        var _cElT = turnoSelectEl.nextElementSibling;
                        if (!_cElT || !_cElT.classList || !_cElT.classList.contains('choices')) {
                            _cElT = Array.from(document.querySelectorAll('.choices')).find(function(c){ return c.contains(turnoSelectEl); });
                        }
                        if (_cElT) _itemVisible = ((_cElT.querySelector('.choices__list--single .choices__item') || {}).textContent || '');
                    }
                    console.log('[Edición][DIAG-TURNO] DESPUÉS setChoiceByValue -> getValue:', ciTurno.getValue(true), '| select#turno.value:', (turnoSelectEl || {}).value, '| item visible:', _itemVisible);
                } catch(e) {}
                try {
                    if (estabaDeshabilitado && typeof ciTurno.disable === 'function') ciTurno.disable();
                } catch(e) {}
            } else {
                console.warn('[Edición][DIAG-TURNO] instancia Choices de turno no existe; solo se seteó el select nativo');
            }
        } else {
            console.warn('[Edición][DIAG-TURNO] vale.Turno vacío - no se seteó turno');
        }
        
        // Despachador
        const despachadorInput = document.getElementById('despachador');
        if (despachadorInput) despachadorInput.value = window.usuarioActual || '';

        // Destino: puede venir como Id, como nombre (Empresa) o como campo DestinoId
        try {
            console.log('[Edición] Vale recibido en cliente:', vale);
            console.log('[Edición] Campos destino - Destino:', vale.Destino, 'DestinoIdReal:', vale.DestinoIdReal, 'DestinoNombre:', vale.DestinoNombre, 'DestinoRUC:', vale.DestinoRUC);
        } catch (e) {}

        var destCandidate = vale.DestinoIdReal || vale.Destino || vale.DestinoId || vale.DestinoNombre || '';
        var destinoEl = document.getElementById('destino');
        var destinoSet = false;

        // ===== MEJORA: Si DestinoIdReal es NULL pero hay texto/nombre, buscar el ID en window.destinosData =====
        if ((!vale.DestinoIdReal || String(vale.DestinoIdReal) === '') && destCandidate && !/^\d+$/.test(String(destCandidate))) {
            // destCandidate es texto (nombre de empresa), buscar el ID correspondiente
            var destinosData = window.destinosData || [];
            var matchPorNombre = destinosData.find(function(d) {
                return String(d.Empresa || '').toLowerCase() === String(destCandidate).toLowerCase();
            });
            if (matchPorNombre) {
                console.log('[Edición] Destino resuelto por nombre: "' + destCandidate + '" -> ID=' + matchPorNombre.Id);
                destCandidate = matchPorNombre.Id;
            } else {
                // Búsqueda flexible: si el texto contiene parte del nombre
                var matchFlexible = destinosData.find(function(d) {
                    var empresa = String(d.Empresa || '').toLowerCase();
                    var cand = String(destCandidate).toLowerCase();
                    return empresa.indexOf(cand) !== -1 || cand.indexOf(empresa) !== -1;
                });
                if (matchFlexible) {
                    console.log('[Edición] Destino resuelto por nombre (flexible): "' + destCandidate + '" -> ID=' + matchFlexible.Id);
                    destCandidate = matchFlexible.Id;
                }
            }
        }

        console.log('[Edición] Seteando destino ID:', destCandidate);
        
        // Usar Choices.js (preferir window.choicesInstances si la local no tiene el destino)
        var choicesDestino = choicesInstances['destino'] || (window.choicesInstances && window.choicesInstances['destino']);
        if (choicesDestino && destCandidate) {
            try {
                // Limpiar selección actual
                choicesDestino.removeActiveItems();
                // Setear el nuevo valor como string
                choicesDestino.setChoiceByValue(String(destCandidate));
                // Verificar
                const val = choicesDestino.getValue(true);
                console.log('[Edición] Choices seteado a:', val);
                destinoSet = (String(val) === String(destCandidate));
            } catch (e) {
                console.warn('[Edición] Error con Choices:', e);
            }
        }
        
        // Si Choices falló, setear el select nativo directamente
        if (!destinoSet && destinoEl) {
            // Buscar en las opciones del select por value (ID) o por texto
            var optFound = null;
            for (var i = 0; i < destinoEl.options.length; i++) {
                if (String(destinoEl.options[i].value) === String(destCandidate)) {
                    optFound = destinoEl.options[i];
                    break;
                }
            }
            if (!optFound && window.destinosData) {
                // Buscar por nombre de empresa en los datos
                var match = window.destinosData.find(function(d) {
                    return String(d.Empresa || '').toLowerCase() === String(destCandidate).toLowerCase();
                });
                if (match) {
                    for (var j = 0; j < destinoEl.options.length; j++) {
                        if (String(destinoEl.options[j].value) === String(match.Id)) {
                            optFound = destinoEl.options[j];
                            destCandidate = match.Id;
                            break;
                        }
                    }
                }
            }
            if (optFound) {
                destinoEl.value = optFound.value;
                destinoSet = true;
                console.log('[Edición] Select nativo forzado a:', destinoEl.value, '(' + optFound.text + ')');
            } else {
                destinoEl.value = String(destCandidate);
                console.log('[Edición] Select nativo forzado (sin match exacto) a:', destinoEl.value);
            }
            // Triggerar evento change
            const event = new Event('change', { bubbles: true });
            destinoEl.dispatchEvent(event);
        }

        if (!destinoSet) console.warn('[Edición] No se pudo seleccionar destino automáticamente - destCandidate=', destCandidate, 'vale.DestinoRUC=', vale.DestinoRUC);
        
        // RUC y Dirección (RUC ahora es <select> dependiente)
        setSelectValueEdit('ruc', vale.DestinoRUC || vale.RUC || '');
        
        const direccionInput = document.getElementById('direccion');
        if (direccionInput) direccionInput.value = vale.DestinoDireccion || '';
        
        // Chofer
        setSelectValueEdit('chofer', vale.Chofer || '');
        
        // Brevete (ahora es <select> dependiente)
        setSelectValueEdit('brevete', vale.Licencia || vale.LicenciaChofer || vale.Brevete || vale.brevete || '');
        
        // Transportista
        setSelectValueEdit('transportista', vale.Transportista || '');
        
        // RUC Transportista (ahora es <select> dependiente)
        setSelectValueEdit('ruc_transportista', vale.RUC_Transportista || vale.ruc_transportista || '');
        
        // Placas
        setSelectValueEdit('placa_tracto', vale.Placa_Tracto || '');
        setSelectValueEdit('placa_carreta', vale.Placa_Carreta || '');
        
        // Constancias (ahora son <select> dependientes)
        setSelectValueEdit('constancia_inscripcion', vale.Constancia_Inscripcion || vale.ConstanciaInscripcion || '');
        setSelectValueEdit('constancia_inscripcion_2', vale.Constancia_Inscripcion_2 || vale.ConstanciaInscripcion_2 || '');
        
        // Guía Remisión
        const guiaRemisionInput = document.getElementById('guiaRemision');
        if (guiaRemisionInput) guiaRemisionInput.value = vale.GR || '';
        
        // Productos
        datosGrilla = [];
        try { console.log('[Edición] productos en vale:', Array.isArray(vale.productos) ? vale.productos.length : typeof vale.productos, vale.productos); } catch(e) {}
        if (vale.productos && Array.isArray(vale.productos) && vale.productos.length) {
            vale.productos.forEach(prod => {
                datosGrilla.push({
                    producto: prod.DescripcionProducto || '',
                    codigo: prod.CodigoProducto || '',
                    unidadMedida: prod.UnidadMedida || '',
                    cantidad: prod.Cantidad || '',
                    comentarios: prod.Comentarios || '',
                    productoId: prod.Id || prod.id || prod.ProductoId || prod.IdProducto || ''
                });
            });
            console.log('[Edición] datosGrilla después de poblar:', datosGrilla);
        } else {
            console.warn('[Edición] No se recibieron productos para este vale.');
        }
        console.log('[Edición] Llamando actualizarGrilla()...');
        actualizarGrilla();
        // Reconstruir select de productos filtrando los que ya están en la grilla
        try { rebuildProductoSelectEdit(); } catch(e) { console.warn('[Edición] error rebuildProductoSelectEdit', e); }
        try { if (window.updateProductoChoiceWrap) { setTimeout(window.updateProductoChoiceWrap,50); setTimeout(window.updateProductoChoiceWrap,200); setTimeout(window.updateProductoChoiceWrap,500); } } catch(e){}
        // Asegurar scroll hacia la grilla para que el usuario la vea
        setTimeout(() => {
            const cardGrilla = document.getElementById('cardGrilla');
            if (cardGrilla) {
                try { cardGrilla.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch(e) { /* ignore */ }
            } else {
                const primerBloque = document.getElementById('bloqueNumeroVale');
                if (primerBloque) try { primerBloque.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch(e) {}
            }
        }, 160);
        console.log('[Edición] actualizarGrilla() completado');
        
        // Mantener controles bloqueados tras poblar el vale y habilitar solo acciones permitidas
        // (antes se llamaba a desbloquearControles() aquí, lo que re-habilitaba todo)
        bloquearControles();

        // Mantener el campo Despachador bloqueado y con estilo de gris (igual que nuevo registro)
        try {
            if (despachadorInput) {
                despachadorInput.disabled = true;
                despachadorInput.style.background = '#e9ecef';
                despachadorInput.style.color = '#6c757d';
            }
        } catch(e) {}
        // Mantener el select Turno bloqueado y con estilo gris en edición
        try {
            const turnoEl = document.getElementById('turno');
            const chkTurno = document.getElementById('chkTurno');
            if (turnoEl) {
                turnoEl.setAttribute('disabled', 'disabled');
                console.log('[Edición] select#turno disabled set');
            }
            // Mantener checkbox de Manual disponible para permitir modo manual en edición
            if (chkTurno) {
                try { chkTurno.checked = false; } catch(e){}
            }

            // Intentar deshabilitar la instancia Choices y también aplicar estilos directos al contenedor
            if (choicesInstances && choicesInstances['turno']) {
                try {
                    choicesInstances['turno'].disable();
                    console.log('[Edición] choicesInstances.turno.disable() llamado');
                } catch(e) { console.warn('[Edición] Error al deshabilitar Choices turno', e); }
            }

            // Aplicar estilos directos al contenedor Choices por si sync no aplicó
            try {
                let choicesEl = null;
                if (turnoEl) choicesEl = turnoEl.nextElementSibling;
                if (!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')) {
                    choicesEl = Array.from(document.querySelectorAll('.choices')).find(c => c.contains(turnoEl));
                }
                if (choicesEl) {
                    const inner = choicesEl.querySelector('.choices__inner');
                    const item = choicesEl.querySelector('.choices__list--single .choices__item');
                    choicesEl.classList.add('is-disabled');
                    choicesEl.setAttribute('aria-disabled', 'true');
                    // Estilos inline con prioridad !important para sobreescribir CSS existentes
                    try {
                        choicesEl.style.setProperty('background-color', '#eef2f6', 'important');
                        choicesEl.style.setProperty('border', '1px solid #d1d5db', 'important');
                        choicesEl.style.setProperty('box-shadow', 'none', 'important');
                        choicesEl.style.setProperty('opacity', '1', 'important');
                        choicesEl.style.setProperty('pointer-events', 'none', 'important');
                    } catch(e){}
                    // (overlay removido) usar estilos inline para forzar aspecto gris
                    if (inner) {
                        inner.setAttribute('aria-disabled', 'true');
                        inner.style.setProperty('background-color', '#eef2f6', 'important');
                        inner.style.setProperty('color', '#374151', 'important');
                        inner.style.setProperty('cursor', 'not-allowed', 'important');
                        inner.style.setProperty('pointer-events', 'none', 'important');
                        // Aplicar borde y border-radius en el contenedor externo para evitar artefactos
                        try { if (choicesEl) choicesEl.style.setProperty('border','1px solid #ced4da','important'); } catch(e){}
                        try { if (choicesEl) choicesEl.style.setProperty('border-radius','0.25rem','important'); } catch(e){}
                        inner.style.setProperty('padding','6px 10px','important');
                        inner.style.setProperty('min-height','38px','important');
                    }
                    if (item) {
                        item.style.setProperty('color', '#374151', 'important');
                        item.style.setProperty('display','block','important');
                        item.style.setProperty('white-space','normal','important');
                    }
                    // Desactivar input interno de Choices si existe
                    try {
                        const chInput = choicesEl.querySelector('input, button');
                        if (chInput) {
                            chInput.setAttribute('disabled','disabled');
                            chInput.style.setProperty('pointer-events', 'none', 'important');
                            chInput.style.setProperty('background', 'transparent', 'important');
                        }
                    } catch(e){}
                    console.log('[Edición] Estilos agresivos aplicados al contenedor Choices turno (with !important)');
                } else {
                    console.log('[Edición] Contenedor Choices turno no encontrado para aplicar estilos directos');
                }
            } catch(e) { console.warn('[Edición] Error aplicando estilos directos a Choices turno', e); }

            try { if (typeof syncTurnoChoicesDisabled === 'function') syncTurnoChoicesDisabled(); } catch(e) {}
        } catch(e) { console.warn('[Edición] Error bloqueando turno en edición', e); }
        habilitarBotonesGuardado();
        
        const btnModificar = document.getElementById('btnModificar');
        if (btnModificar) {
            btnModificar.disabled = false;
            btnModificar.removeAttribute('disabled');
            // Quitar estilos inline de deshabilitado aplicados por bloquearControles
            try { btnModificar.style.removeProperty('cursor'); btnModificar.style.cursor = 'pointer'; } catch(e) {}
            try { btnModificar.style.removeProperty('pointer-events'); btnModificar.style.pointerEvents = 'auto'; } catch(e) {}
            try { btnModificar.style.removeProperty('background-color'); } catch(e) {}
            try { btnModificar.style.removeProperty('color'); } catch(e) {}
        }
        // Mover el panel de historial debajo de la parte principal del formulario (comportamiento igual a Despachos Internos)
        try {
            const panel = document.getElementById('panelHistorialAuditoria');
            const target = document.getElementById('cardGrilla') || document.getElementById('botonesGrilla') || document.getElementById('cardProductos') || document.getElementById('cardTransporte');
            if (panel && target && target.parentNode) {
                target.parentNode.insertBefore(panel, target.nextSibling);
                console.log('[Edición] Panel historial movido debajo de', target.id);
            }
        } catch(e) { console.warn('[Edición] No se pudo mover panelHistorialAuditoria:', e); }
    }
        // DIAGNÓSTICO: Enumerar controles habilitados y estado de Choices para ayudar a detectar por qué
        function logDiagnostics(label) {
            try {
                var diagContainer = document.getElementById('formDespacho') || document.querySelector('.despacho-form') || document.getElementById('formDespachoExterno') || document.body;
                var enabledInside = Array.from(diagContainer.querySelectorAll('input,select,textarea,button')).filter(function(el){ return el && !el.disabled; });
                console.log('[Edición][DIAG]['+label+'] Controles habilitados DENTRO:', enabledInside.map(function(e){ return { id: e.id||'??', name: e.name||'??', tag: e.tagName, type: e.type||'??', classes: (e.className||'').substring(0,40) }; }));

                console.log('[Edición][DIAG]['+label+'] keys choicesInstances:', Object.keys(choicesInstances || {}));
                console.log('[Edición][DIAG]['+label+'] keys window.choicesInstances:', Object.keys(window.choicesInstances || {}));

                var choicesEls = Array.from(document.querySelectorAll('.choices'));
                console.log('[Edición][DIAG]['+label+'] choices DOM encontrados:', choicesEls.length);

                // Diagnosticar estilos computados de algunos inputs específicos
                var sampleInputs = ['fecha','ruc','direccion','brevete','ruc_transportista','constancia_inscripcion','guiaRemision'];
                sampleInputs.forEach(function(id){
                    var el = document.getElementById(id);
                    if(el) {
                        var cs = window.getComputedStyle(el);
                        console.log('[Edición][DIAG]['+label+'] #'+id+' disabled='+el.disabled+' bg='+cs.backgroundColor+' color='+cs.color+' cursor='+cs.cursor);
                    }
                });
            } catch (e) { console.warn('[Edición][DIAG]['+label+'] error', e); }
        }
        logDiagnostics('T0-inicial');

        // Función local para forzar deshabilitado en un segundo pase (reintento en caso de race conditions)
        function forceDisableAll() {
            try {
                // Si el usuario ya habilitó la edición con "Modificar", NO volver a bloquear
                if (_edicionHabilitada) return;
                var container2 = document.getElementById('formDespacho') || document.querySelector('.despacho-form') || document.getElementById('formDespachoExterno') || document.body;
                Array.from(container2.querySelectorAll('input,select,textarea,button')).forEach(function(el){
                    if (!el) return;
                    if (el.id === 'btnModificar' || el.id === 'btnImprimir' || el.classList.contains('keep-enabled')) return;
                    // NO deshabilitar controles dentro de modales (cualquier modal)
                    if (el.closest && el.closest('.modal')) return;
                    try {
                        el.setAttribute('disabled','disabled');
                        el.disabled = true;
                        // Aplicar apariencia visual de deshabilitado igual que en Despachos Internos
                        try { el.style.setProperty('background-color', '#e9ecef', 'important'); } catch(e) { el.style.background = '#e9ecef'; }
                        try { el.style.setProperty('color', '#6c757d', 'important'); } catch(e) { el.style.color = '#6c757d'; }
                        try { el.style.setProperty('cursor', 'not-allowed', 'important'); } catch(e) { el.style.cursor = 'not-allowed'; }
                        try { el.style.setProperty('pointer-events', 'none', 'important'); } catch(e) { el.style.pointerEvents = 'none'; }
                        // Inputs tipo button/submit mantener apariencia pero deshabilitados
                        if (el.tagName === 'BUTTON' || (el.tagName === 'INPUT' && (el.type === 'button' || el.type === 'submit'))) {
                            try { el.style.setProperty('opacity', '0.9', 'important'); } catch(e) { el.style.opacity = '0.9'; }
                        }
                    } catch(e){}
                });

                // Forzar estado en contenedores Choices dentro del container
                Array.from(container2.querySelectorAll('.choices')).forEach(function(c){
                    try {
                        c.classList.add('is-disabled');
                        c.setAttribute('aria-disabled','true');
                        var inner = c.querySelector('.choices__inner'); if(inner) inner.setAttribute('aria-disabled','true');
                        Array.from(c.querySelectorAll('input,button')).forEach(function(i){ try{ i.setAttribute('disabled','disabled'); i.disabled = true; i.style.pointerEvents = 'none'; }catch(e){} });
                    } catch(e){}
                });

                // Llamar .disable() en instancias de Choices conocidas
                try { Object.keys(choicesInstances || {}).forEach(function(k){ try{ if(choicesInstances[k] && typeof choicesInstances[k].disable === 'function') choicesInstances[k].disable(); }catch(e){} }); } catch(e){}
                try { Object.keys(window.choicesInstances || {}).forEach(function(k){ try{ if(window.choicesInstances[k] && typeof window.choicesInstances[k].disable === 'function') window.choicesInstances[k].disable(); }catch(e){} }); } catch(e){}

                console.log('[Edición][DIAG] forceDisableAll aplicado');
            } catch (e) { console.warn('[Edición][DIAG] forceDisableAll error', e); }
        }

        // Re-intentar después de delays más largos para capturar Choices después de su creación
        setTimeout(function(){ try{ forceDisableAll(); logDiagnostics('T1-800ms'); }catch(e){ console.warn('forceDisableAll(800) error', e); } }, 800);
        setTimeout(function(){ try{ forceDisableAll(); logDiagnostics('T2-1500ms'); }catch(e){ console.warn('forceDisableAll(1500) error', e); } }, 1500);

    function bloquearControles() {
        try {
            // Resetear bandera de edición: mientras esté bloqueado, forceDisableAll puede actuar
            _edicionHabilitada = false;
            console.log('[Edición] bloquearControles INICIO');
            // Buscar todos los inputs/selects/textarea en el DOCUMENTO (no solo un container)
            var allControls = Array.from(document.querySelectorAll('input,select,textarea,button'));
            console.log('[Edición] bloquearControles encontró', allControls.length, 'controles totales en documento');
            
            var disabled = 0;
            allControls.forEach(function(el){
                if (!el) return;
                // NO deshabilitar botones con keep-enabled o IDs específicos
                if (el.id === 'btnModificar' || el.id === 'btnImprimir' || el.id === 'btnBuscarVale' || el.classList.contains('keep-enabled')) {
                    console.log('[Edición] bloquearControles SKIP (keep-enabled):', el.id || el.name || el.tagName);
                    return;
                }
                // NO deshabilitar controles dentro de cualquier modal abierto
                if (el.closest && el.closest('.modal')) {
                    console.log('[Edición] bloquearControles SKIP (dentro de modal):', el.id || el.name || el.tagName);
                    return;
                }
                try {
                    el.setAttribute('disabled','disabled');
                    el.disabled = true;
                    disabled++;
                    // Aplicar apariencia visual de deshabilitado
                    try { el.style.setProperty('background-color', '#e9ecef', 'important'); } catch(e) { el.style.background = '#e9ecef'; }
                    try { el.style.setProperty('color', '#6c757d', 'important'); } catch(e) { el.style.color = '#6c757d'; }
                    try { el.style.setProperty('cursor', 'not-allowed', 'important'); } catch(e) { el.style.cursor = 'not-allowed'; }
                    try { el.style.setProperty('pointer-events', 'none', 'important'); } catch(e) { el.style.pointerEvents = 'none'; }
                } catch(e){ console.warn('[Edición] bloquearControles error al deshabilitar', el.id, e); }
            });
            console.log('[Edición] bloquearControles deshabilitó', disabled, 'controles');

            // Deshabilitar contenedores Choices
            Array.from(document.querySelectorAll('.choices')).forEach(function(c){
                try {
                    c.classList.add('is-disabled');
                    c.setAttribute('aria-disabled','true');
                    var inner = c.querySelector('.choices__inner');
                    if(inner) {
                        inner.setAttribute('aria-disabled','true');
                        try { inner.style.setProperty('background-color', '#e9ecef', 'important'); } catch(e) { inner.style.background = '#e9ecef'; }
                        try { inner.style.setProperty('color', '#6c757d', 'important'); } catch(e) { inner.style.color = '#6c757d'; }
                        try { inner.style.setProperty('cursor', 'not-allowed', 'important'); } catch(e) { inner.style.cursor = 'not-allowed'; }
                        try { inner.style.setProperty('pointer-events', 'none', 'important'); } catch(e) { inner.style.pointerEvents = 'none'; }
                    }
                    Array.from(c.querySelectorAll('.choices__list--single .choices__item')).forEach(function(item){
                        try { item.style.setProperty('color', '#6c757d', 'important'); } catch(e) { item.style.color = '#6c757d'; }
                    });
                } catch(e){}
            });

            // Deshabilitar instancias de Choices conocidas
            try { Object.keys(choicesInstances || {}).forEach(function(k){ try{ if(choicesInstances[k] && typeof choicesInstances[k].disable === 'function') choicesInstances[k].disable(); }catch(e){} }); } catch(e){}
            try { Object.keys(window.choicesInstances || {}).forEach(function(k){ try{ if(window.choicesInstances[k] && typeof window.choicesInstances[k].disable === 'function') window.choicesInstances[k].disable(); }catch(e){} }); } catch(e){}
            
            // Forzar sync visual de Choices
            try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
            
            console.log('[Edición] bloquearControles FIN');
        } catch(e) { console.warn('[Edición] bloquearControles error', e); }
    }

    function desbloquearControles() {
        const controles = document.querySelectorAll('#formDespachoExterno input, #formDespachoExterno select, #formDespachoExterno textarea');
        controles.forEach(c => c.disabled = false);
    }

    // Habilitar solo los campos para edición (los que NO son: turno, despachador, codigo, fecha)
    function habilitarEdicion() {
        console.log('[Edición] Habilitando edición parcial');
        // Marcar que la edición está habilitada (evita que forceDisableAll la re-bloquee)
        _edicionHabilitada = true;
        
        // Lista de campos que SE PUEDEN habilitar para edición.
        // Incluye los campos dependientes (ruc, brevete, ruc_transportista, constancias)
        // y 'chkTurno' para permitir que el usuario active el modo manual de turno.
        const camposEditables = ['fecha', 'destino', 'ruc', 'chofer', 'brevete', 'transportista', 'ruc_transportista', 'placa_tracto', 'constancia_inscripcion', 'placa_carreta', 'constancia_inscripcion_2', 'producto', 'cantidad', 'comentarios', 'guiaRemision', 'chkTurno'];
        
        camposEditables.forEach(function(id){
            try {
                const el = document.getElementById(id);
                if (el) {
                    el.disabled = false;
                    el.removeAttribute('disabled');
                    // Quitar estilos inline de deshabilitado y restaurar apariencia normal
                    try { el.style.removeProperty('background-color'); el.style.backgroundColor = ''; } catch(e) {}
                    try { el.style.removeProperty('color'); el.style.color = ''; } catch(e) {}
                    try { el.style.removeProperty('cursor'); el.style.cursor = ''; } catch(e) {}
                    try { el.style.removeProperty('pointer-events'); el.style.pointerEvents = ''; } catch(e) {}
                    try { el.style.removeProperty('opacity'); el.style.opacity = ''; } catch(e) {}
                }
                // Habilitar instancia Choices si existe
                if (window.choicesInstances && window.choicesInstances[id] && typeof window.choicesInstances[id].enable === 'function') {
                    window.choicesInstances[id].enable();
                }
                // Limpiar estilos !important del contenedor Choices
                // (bloquearControles/forceDisableAll los dejan con pointer-events:none important)
                try { limpiarEstilosChoices(el); } catch(e) { console.warn('[Edición] Error limpiando estilos Choices', id, e); }
            } catch(e) { console.warn('[Edición] Error habilitando', id, e); }
        });
        
        // Habilitar botones de grilla
        ['btnAgregar', 'btnQuitar', 'btnLimpiar'].forEach(function(id){
            try {
                const btn = document.getElementById(id);
                if (btn) {
                    btn.disabled = false;
                    btn.removeAttribute('disabled');
                    try { btn.style.removeProperty('cursor'); btn.style.cursor = 'pointer'; } catch(e) {}
                    try { btn.style.removeProperty('pointer-events'); btn.style.pointerEvents = 'auto'; } catch(e) {}
                    try { btn.style.removeProperty('background-color'); btn.style.backgroundColor = ''; } catch(e) {}
                    try { btn.style.removeProperty('color'); btn.style.color = ''; } catch(e) {}
                    try { btn.style.removeProperty('opacity'); btn.style.opacity = ''; } catch(e) {}
                }
            } catch(e) {}
        });
        
        // Red de seguridad: limpiar TODOS los contenedores .choices de la página
        // (por si algún estilos !important residual quedó sin limpiar en los selects)
        try { limpiarTodosLosChoices(); } catch(e) { console.warn('[Edición] limpiarTodosLosChoices error', e); }
        // Re-bloquear el turno (debe permanecer deshabilitado por diseño) tras la limpieza global
        try {
            const turnoEl = document.getElementById('turno');
            if (turnoEl) turnoEl.setAttribute('disabled','disabled');
            if (window.choicesInstances && window.choicesInstances['turno']) {
                try { window.choicesInstances['turno'].disable(); } catch(e){}
            }
            if (typeof syncTurnoChoicesDisabled === 'function') syncTurnoChoicesDisabled();
        } catch(e) { console.warn('[Edición] re-bloqueo turno error', e); }
        
        // GARANTIZAR que los selects principales con Choices queden interactuables:
        // se recrea SIEMPRE la instancia Choices (destruir + crear) para que el dropdown
        // responda al clic sin depender del estado previo de la instancia.
        try {
            ['destino','chofer','transportista','placa_tracto','placa_carreta'].forEach(function(id){
                try { recrearChoicesSelect(id); } catch(e){ console.warn('[Edición] recrear garantizado #' + id, e); }
            });
        } catch(e) { console.warn('[Edición] Recreación garantizada error', e); }
        
        // Sincronizar aspecto visual de Choices habilitados
        try { if (typeof window.syncAllChoicesDisabled === 'function') window.syncAllChoicesDisabled(); } catch(e) {}
        
        // Cambiar estado de botones: deshabilitar Modificar, habilitar Guardar
        const btnModificar = document.getElementById('btnModificar');
        const btnGuardar = document.getElementById('btnGuardar');
        if (btnModificar) {
            btnModificar.disabled = true;
            btnModificar.setAttribute('disabled', 'disabled');
        }
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.removeAttribute('disabled');
            // Limpiar estilos residuales de deshabilitado
            try { btnGuardar.style.removeProperty('background-color'); btnGuardar.style.backgroundColor = ''; } catch(e) {}
            try { btnGuardar.style.removeProperty('color'); btnGuardar.style.color = ''; } catch(e) {}
            try { btnGuardar.style.removeProperty('cursor'); btnGuardar.style.cursor = 'pointer'; } catch(e) {}
            try { btnGuardar.style.removeProperty('pointer-events'); btnGuardar.style.pointerEvents = 'auto'; } catch(e) {}
            try { btnGuardar.style.removeProperty('opacity'); btnGuardar.style.opacity = ''; } catch(e) {}
        }
        
        // [DIAG-TURNO] Estado del turno y chkTurno tras habilitar edición
        try {
            var _tEl = document.getElementById('turno');
            var _chkEl = document.getElementById('chkTurno');
            var _ciTurno = window.choicesInstances && window.choicesInstances['turno'];
            var _choicesContainer = null;
            if (_tEl) {
                _choicesContainer = _tEl.nextElementSibling;
                if (!_choicesContainer || !_choicesContainer.classList || !_choicesContainer.classList.contains('choices')) {
                    _choicesContainer = Array.from(document.querySelectorAll('.choices')).find(function(c){ return c.contains(_tEl); });
                }
            }
            console.log('[Edición][DIAG-TURNO] habilitarEdicion -> #turno.disabled:', _tEl ? _tEl.disabled : 'N/A',
                '| #chkTurno.disabled:', _chkEl ? _chkEl.disabled : 'N/A',
                '| #chkTurno.checked:', _chkEl ? _chkEl.checked : 'N/A',
                '| choices.turno.isDisabled:', _ciTurno ? _ciTurno.isDisabled : 'N/A',
                '| contenedor .choices encontrado:', !!_choicesContainer,
                '| pointer-events contenedor:', _choicesContainer ? getComputedStyle(_choicesContainer).pointerEvents : 'N/A',
                '| pointer-events inner:', (_choicesContainer && _choicesContainer.querySelector('.choices__inner')) ? getComputedStyle(_choicesContainer.querySelector('.choices__inner')).pointerEvents : 'N/A');
        } catch(e) { console.warn('[Edición][DIAG-TURNO] error log habilitarEdicion', e); }

        console.log('[Edición] Edición habilitada parcialmente');
    }

    // Habilitar botones de guardado/modificar.
    // Si keepGuardar === true, forzar que `Guardar` permanezca habilitado y `Modificar` deshabilitado.
    function habilitarBotonesGuardado(keepGuardar) {
        const btnGuardar = document.getElementById('btnGuardar');
        const btnModificar = document.getElementById('btnModificar');

        if (typeof keepGuardar === 'undefined') keepGuardar = false;

        if (keepGuardar) {
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.removeAttribute('disabled');
                try { btnGuardar.style.cursor = 'pointer'; } catch(e) {}
                try { btnGuardar.style.pointerEvents = 'auto'; } catch(e) {}
            }
            if (btnModificar) {
                btnModificar.disabled = true;
                try { btnModificar.setAttribute('disabled','disabled'); } catch(e) {}
            }
            return;
        }

        // Resetear flag y texto del botón guardar
        _guardandoDespExtEditEnProceso = false;
        if (btnGuardar) {
            btnGuardar.textContent = 'Guardar';
        }
        
        if (valeIdActual) {
            if (btnModificar) {
                btnModificar.disabled = false;
                btnModificar.removeAttribute('disabled');
                // Asegurar que tenga cursor pointer normal
                try { btnModificar.style.cursor = 'pointer'; } catch(e) {}
                try { btnModificar.style.pointerEvents = 'auto'; } catch(e) {}
            }
            if (btnGuardar) btnGuardar.disabled = true;
        } else {
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnModificar) btnModificar.disabled = true;
        }
    }

    function deshabilitarBotonesGuardado() {
        const btnGuardar = document.getElementById('btnGuardar');
        const btnModificar = document.getElementById('btnModificar');
        
        if (btnGuardar) btnGuardar.disabled = true;
        if (btnModificar) btnModificar.disabled = true;
    }

    function restaurarBtnGuardarDespExtEdit() {
        _guardandoDespExtEditEnProceso = false;
        const btnG = document.getElementById('btnGuardar');
        if (btnG) {
            btnG.disabled = false;
            btnG.textContent = 'Guardar';
        }
    }

    function guardarDespacho() {
        console.log('[Edición][DEBUG] guardarDespacho - valeIdActual al inicio:', valeIdActual, 'tipo:', typeof valeIdActual);
        
        // ===== DIAGNÓSTICO: loguear estado actual de todos los campos antes de validar =====
        try {
            var _diagFields = ['fecha','turno','destino','ruc','direccion','despachador','chofer','brevete','transportista','ruc_transportista','placa_tracto','placa_carreta','constancia_inscripcion','constancia_inscripcion_2','guiaRemision'];
            var _diagValues = {};
            _diagFields.forEach(function(id){
                var el = document.getElementById(id);
                var nativeVal = el ? el.value : 'NO-EXISTE';
                var choicesVal = '';
                try {
                    var ci = choicesInstances[id] || (window.choicesInstances && window.choicesInstances[id]);
                    if (ci && typeof ci.getValue === 'function') choicesVal = ci.getValue(true) || '';
                } catch(e){}
                if (!nativeVal && choicesVal) {
                    console.warn('[Edición][DIAG] CAMPO "#' + id + '" -> native="' + nativeVal + '" Choices="' + choicesVal + '" (DESINCRONIZADO)');
                }
                _diagValues[id] = { native: nativeVal, choices: choicesVal, disabled: el ? el.disabled : 'N/A' };
            });
            console.log('[Edición][DIAG] Diagnóstico campos:', _diagValues);
            console.log('[Edición][DIAG] datosGrilla.length:', datosGrilla.length);
        } catch(e) { console.warn('[Edición][DIAG] error diagnóstico', e); }

        // ===== MEJORA: Leer valor real desde Choices.js si el nativo está vacío =====
        function getRealSelectValueEdit(selectId) {
            var el = document.getElementById(selectId);
            if (!el) return '';
            var val = el.value || '';
            if (!val) {
                var ci = choicesInstances[selectId] || (window.choicesInstances && window.choicesInstances[selectId]);
                if (ci && typeof ci.getValue === 'function') {
                    try {
                        var choicesVal = ci.getValue(true);
                        if (choicesVal) {
                            val = String(choicesVal);
                            try { el.value = val; } catch(e){}
                            console.log('[Edición][DIAG] Fallback Choices -> nativo para #' + selectId + ': "' + val + '"');
                        }
                    } catch(e){}
                }
            }
            return val;
        }

        if (datosGrilla.length === 0) {
            mostrarMensajeError('Debe agregar al menos un producto');
            restaurarBtnGuardarDespExtEdit();
            return;
        }
        
        const data = recopilarDatos();
        console.log('[Edición][DEBUG] guardarDespacho - data recopilado:', data);
        
        // Si hay valeIdActual, es una modificación (enviar a endpoint modificar)
        if (valeIdActual) {
            data.Id = valeIdActual;
            console.log('[Edición][DEBUG] Ruta MODIFICAR - data.Id:', data.Id, 'valeIdActual:', valeIdActual);
            // FIX: usar APP_URL (raíz de la app) para el endpoint; BASE_URL incluye /public
            // y devuelve HTML (front controller no enruta correctamente), rompiendo el JSON.
            fetch((window.APP_URL || window.BASE_URL) + '/despachosexternos/modificar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        mostrarMensajeError('Despacho modificado correctamente');
                        // Resetear flag y texto del botón
                        _guardandoDespExtEditEnProceso = false;
                        const _btnG = document.getElementById('btnGuardar');
                        if (_btnG) _btnG.textContent = 'Guardar';
                        // Bloquear controles después de guardar modificación
                        bloquearControles();
                        // Habilitar botón modificar para permitir nuevas ediciones
                        const btnModificar = document.getElementById('btnModificar');
                        if (btnModificar) btnModificar.disabled = false;
                        const btnGuardar = document.getElementById('btnGuardar');
                        if (btnGuardar) btnGuardar.disabled = true;
                    } else {
                        // Manejar error de límite de modificaciones
                        if (response.limite_alcanzado) {
                            mostrarMensajeError('Error: Este vale alcanzó el límite de 3 modificaciones y no puede ser editado.', 'error');
                            // Bloquear controles permanentemente
                            bloquearControles();
                            const btnGuardar = document.getElementById('btnGuardar');
                            const btnModificar = document.getElementById('btnModificar');
                            if (btnGuardar) btnGuardar.disabled = true;
                            if (btnModificar) btnModificar.disabled = true;
                        } else {
                            mostrarMensajeError(response.message || 'Error al modificar');
                            restaurarBtnGuardarDespExtEdit();
                        }
                    }
                })
                .catch(err => {
                    console.error('[Edición] Error al modificar:', err);
                    mostrarMensajeError('Error al conectar con el servidor');
                    restaurarBtnGuardarDespExtEdit();
                });
        } else {
            // Nuevo despacho (guardar normalmente)
            console.log('[Edición][DEBUG] Ruta GUARDAR NUEVO - valeIdActual es null/undefined');
            fetch((window.APP_URL || window.BASE_URL) + '/despachosexternos/guardar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        mostrarMensajeError('Despacho guardado correctamente');
                        valeIdActual = response.id;
                        habilitarBotonesGuardado();
                    } else {
                        mostrarMensajeError(response.message || 'Error al guardar');
                        restaurarBtnGuardarDespExtEdit();
                    }
                })
                .catch(err => {
                    console.error('[Edición] Error al guardar:', err);
                    mostrarMensajeError('Error al conectar con el servidor');
                    restaurarBtnGuardarDespExtEdit();
                });
        }
    }

    function modificarDespacho() {
        // Este botón ahora solo HABILITA los campos para edición, no guarda inmediatamente
        if (!valeIdActual) {
            mostrarMensajeError('No hay un vale cargado para modificar');
            return;
        }
        // Antes de habilitar, verificar con el servidor si aún se permite modificar (máx 3 modificaciones)
        const tryEndpointPuedeModificar = function(i) {
            const endpoints = [
                (window.APP_URL || window.BASE_URL) + '/despachosexternos/puedeModificar?id=' + encodeURIComponent(valeIdActual),
                (window.BASE_URL || window.APP_URL) + '/index.php?url=despachosexternos/puedeModificar&id=' + encodeURIComponent(valeIdActual)
            ];
            if (i >= endpoints.length) {
                mostrarMensajeError('No se pudo verificar permiso de modificación (servicio no disponible)', 'error');
                return;
            }
            const url = endpoints[i];
            fetch(url, { method: 'GET', credentials: 'same-origin' })
            .then(function(r){ if(!r.ok) return r.text().then(function(t){ throw new Error('HTTP ' + r.status + ': ' + (t||r.statusText)); }); return r.json(); })
            .then(function(res){
                if (!res || !res.success) {
                    mostrarMensajeError((res && res.message) ? res.message : 'Error validando permiso de modificación', 'error');
                    return;
                }
                if (!res.puede_modificar) {
                    // No permitir edición: mostrar mensaje y mantener controles bloqueados
                    try { mostrarMensajeError(res.mensaje || 'Este vale alcanzó el límite de modificaciones', 'error'); } catch(e) { console.warn(e); }
                    try { bloquearControles(); } catch(e) {}
                    try { const btnGuardar = document.getElementById('btnGuardar'); if (btnGuardar) btnGuardar.disabled = true; } catch(e) {}
                    try { const btnModificar = document.getElementById('btnModificar'); if (btnModificar) btnModificar.disabled = true; } catch(e) {}
                    return;
                }
                // Permitido: habilitar edición parcial
                try { habilitarEdicion(); } catch(e) { console.error('Error habilitando edición tras verificación', e); }
            })
            .catch(function(err){
                console.error('[Edición] Error verificando permiso de modificación:', err);
                tryEndpointPuedeModificar(i + 1);
            });
        };
        tryEndpointPuedeModificar(0);
    }

    // Helper para leer valor real de un select con fallback Choices.js
    function getRealSelectValueEdit(selectId) {
        var el = document.getElementById(selectId);
        if (!el) return '';
        var val = el.value || '';
        if (!val) {
            var ci = choicesInstances[selectId] || (window.choicesInstances && window.choicesInstances[selectId]);
            if (ci && typeof ci.getValue === 'function') {
                try {
                    var choicesVal = ci.getValue(true);
                    if (choicesVal) {
                        val = String(choicesVal);
                        try { el.value = val; } catch(e){}
                        console.log('[Edición][recopilarDatos] Fallback Choices -> nativo para #' + selectId + ': "' + val + '"');
                    }
                } catch(e){}
            }
        }
        return val;
    }

    function recopilarDatos() {
        // Usar getRealSelectValueEdit para leer valores con fallback Choices.js
        var destinoVal = getRealSelectValueEdit('destino');
        var rucVal = getRealSelectValueEdit('ruc');
        var choferVal = getRealSelectValueEdit('chofer');
        var transportistaVal = getRealSelectValueEdit('transportista');
        var placaTractoVal = getRealSelectValueEdit('placa_tracto');
        var placaCarretaVal = getRealSelectValueEdit('placa_carreta');
        var turnoVal = getRealSelectValueEdit('turno');
        var despachadorVal = document.getElementById('despachador')?.value || '';

        // ===== MEJORA: Forzar que destino sea SIEMPRE un ID numérico =====
        if (destinoVal && !/^\d+$/.test(String(destinoVal))) {
            // El valor no es numérico (es texto), intentar resolverlo a ID
            var destinosData = window.destinosData || [];
            var match = destinosData.find(function(d) {
                return String(d.Empresa || '').toLowerCase() === String(destinoVal).toLowerCase();
            });
            if (match) {
                console.log('[Edición][recopilarDatos] Destino resuelto de texto a ID: "' + destinoVal + '" -> ' + match.Id);
                destinoVal = String(match.Id);
            } else {
                // Búsqueda flexible
                var matchFlex = destinosData.find(function(d) {
                    var empresa = String(d.Empresa || '').toLowerCase();
                    var cand = String(destinoVal).toLowerCase();
                    return empresa.indexOf(cand) !== -1 || cand.indexOf(empresa) !== -1;
                });
                if (matchFlex) {
                    console.log('[Edición][recopilarDatos] Destino resuelto (flexible): "' + destinoVal + '" -> ' + matchFlex.Id);
                    destinoVal = String(matchFlex.Id);
                } else {
                    console.warn('[Edición][recopilarDatos] NO se pudo resolver destino texto a ID: "' + destinoVal + '"');
                }
            }
        }

        var data = {
            correlativoVale: document.getElementById('correlativoVale')?.textContent || '',
            fecha: document.getElementById('fecha')?.value || '',
            turno: turnoVal,
            despachador: despachadorVal,
            destino: destinoVal,
            ruc: rucVal,
            direccion: document.getElementById('direccion')?.value || '',
            chofer: choferVal,
            brevete: getRealSelectValueEdit('brevete'),
            transportista: transportistaVal,
            ruc_transportista: getRealSelectValueEdit('ruc_transportista'),
            Placa_Tracto: placaTractoVal,
            Placa_Carreta: placaCarretaVal,
            Constancia_Inscripcion: getRealSelectValueEdit('constancia_inscripcion'),
            Constancia_Inscripcion_2: getRealSelectValueEdit('constancia_inscripcion_2'),
            guiaRemision: document.getElementById('guiaRemision')?.value || '',
            productos: datosGrilla
        };

        // ===== DIAGNÓSTICO: loguear datos recopilados =====
        try {
            console.log('[Edición][DIAG] recopilarDatos - valores finales:', data);
            var camposVacios = Object.keys(data).filter(function(k){
                if (k === 'productos') return false;
                return data[k] === '' || data[k] === null || data[k] === undefined;
            });
            if (camposVacios.length > 0) {
                console.warn('[Edición][DIAG] recopilarDatos - CAMPOS VACÍOS:', camposVacios.join(', '));
            }
        } catch(e) {}

        return data;
    }

    // Función para abrir modal de anular
    window.abrirModalAnular = function(valeId, valeNumero) {
        const modal = new bootstrap.Modal(document.getElementById('modalAnularVale'));
        document.getElementById('valeAnularNumero').textContent = valeNumero;
        document.getElementById('motivoAnulacion').value = '';
        document.getElementById('anularFeedback').classList.add('d-none');
        
        // Configurar botón de confirmación
        const btnConfirmar = document.getElementById('btnConfirmarAnular');
        btnConfirmar.onclick = function() {
            anularVale(valeId, valeNumero);
        };
        
        modal.show();
    };

    function anularVale(valeId, valeNumero) {
        const motivo = document.getElementById('motivoAnulacion').value.trim();
        const feedback = document.getElementById('anularFeedback');
        
        if (!motivo) {
            feedback.className = 'alert alert-danger';
            feedback.textContent = 'Debe ingresar un motivo de anulación';
            feedback.classList.remove('d-none');
            return;
        }
        
        // FIX: usar APP_URL (raíz) en vez de BASE_URL (/public) para que el front controller enrute correctamente
        fetch((window.APP_URL || window.BASE_URL) + '/despachosexternos/anular', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: valeId, motivo: motivo })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    feedback.className = 'alert alert-success';
                    feedback.textContent = 'Vale anulado correctamente';
                    feedback.classList.remove('d-none');
                    
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAnularVale'));
                        if (modal) modal.hide();
                        buscarVales();
                    }, 1500);
                } else {
                    feedback.className = 'alert alert-danger';
                    feedback.textContent = data.message || 'Error al anular el vale';
                    feedback.classList.remove('d-none');
                }
            })
            .catch(err => {
                console.error('[Edición] Error al anular:', err);
                feedback.className = 'alert alert-danger';
                feedback.textContent = 'Error al conectar con el servidor';
                feedback.classList.remove('d-none');
            });
    }

    // Inicializar modo edición y botones para adjuntar listeners (seguro si falla)
    try { setupBotones(); } catch (e) { console.warn('[Edición] setupBotones init falló:', e); }
    // FIX: invocar setupProductos() para clonar btnAgregar/btnQuitar/btnLimpiar y reemplazar
    // los listeners del script compartido (despachosexternos.js) por la lógica de edición.
    // Sin esto, al hacer clic en "Actualizar" se ejecutaba la validación del script compartido
    // ("Debe seleccionar un producto y especificar la cantidad.") porque la fila seleccionada
    // aún no se cargaba en los controles.
    try { setupProductos(); } catch (e) { console.warn('[Edición] setupProductos init falló:', e); }
    try { setupModoEdicion(); } catch (e) { console.warn('[Edición] setupModoEdicion init falló:', e); }
    // FIX: invocar setupTurnoAutomatico para adjuntar el listener del checkbox "Manual"
    // (sin esto, el checkbox no limpiaba los estilos !important y el dropdown de turno quedaba bloqueado)
    try { setupTurnoAutomatico(); } catch (e) { console.warn('[Edición] setupTurnoAutomatico init falló:', e); }
    // Configurar campos sincronizados (Destino-RUC, Chofer-Brevete, Transportista-RUC, Placas-Constancias)
    try { setupCamposSincronizados(); } catch (e) { console.warn('[Edición] setupCamposSincronizados init falló:', e); }

    // FIX (dropdown de turno no desplegaba en modo Manual): listener de CAPTURA global que
    // garantiza que el clic sobre el select de turno (con el checkbox Manual activo) abra el
    // dropdown, incluso si el handler nativo de Choices u otros listeners no responden.
    // Se ejecuta ANTES que cualquier otro listener (fase de captura) y solo actúa cuando:
    //   - #turno no está deshabilitado (Manual marcado / edición habilitada)
    //   - el clic ocurre dentro del contenedor .choices del turno
    // Si el dropdown ya está abierto no fuerza nada, para no romper el toggle de cierre.
    try {
        document.addEventListener('click', function(evt) {
            try {
                var selT = document.getElementById('turno');
                if (!selT) return;
                if (selT.disabled) return; // solo cuando el turno está habilitado (Manual)
                var contT = selT.closest('.choices');
                if (!contT) return;
                if (!contT.contains(evt.target)) return; // solo clic sobre el select de turno
                if (!contT.classList.contains('is-open')) {
                    forzarAperturaTurnoManual();
                    var ciT = (window.choicesInstances && window.choicesInstances['turno']) || null;
                    if (ciT && typeof ciT.showDropdown === 'function') { try { ciT.showDropdown(); } catch(e){} }
                }
            } catch(e) { /* ignore */ }
        }, true);
    } catch(e) { console.warn('[Edición] Error adjuntando listener de captura para turno', e); }

})();
