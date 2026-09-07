
	// Reescritura limpia de despachosexternos.js

(function(){
    'use strict';
    try{ console.log('[despachosExt] script loaded'); }catch(e){}

    // Helpers
    function setSelectValue(selectEl, value, choicesKey){
        if(!selectEl) return;
        var prevDisabled = !!selectEl.disabled;
        var valueStr = (value === null || value === undefined) ? '' : String(value);
        // Si es un SELECT, intentar asignar por opciones; si no, tratar como input/text
        try{
            if(selectEl.tagName && selectEl.tagName.toLowerCase() === 'select' && typeof selectEl.options !== 'undefined'){
                var assigned = false;
                for(var i=0;i<selectEl.options.length;i++){
                    var opt = selectEl.options[i];
                    if(String(opt.value) === valueStr){ selectEl.value = opt.value; assigned = true; break; }
                }
                if(!assigned){
                    for(var j=0;j<selectEl.options.length;j++){
                        var opt2 = selectEl.options[j];
                        if((opt2.text||'') === valueStr){ selectEl.value = opt2.value; assigned = true; break; }
                    }
                }
                if(!assigned) try{ selectEl.value = valueStr; }catch(e){}
            } else {
                // No es un select (por ejemplo un input). Asignar valor directamente.
                try{ selectEl.value = valueStr; }catch(e){ }
            }
        }catch(e){ try{ selectEl.value = valueStr; }catch(e){} }

        // Actualizar instancia de Choices si corresponde
        if(window.choicesInstances && choicesKey && window.choicesInstances[choicesKey]){
            var inst = window.choicesInstances[choicesKey];
            try{
                if(prevDisabled){ try{ inst.enable(); }catch(e){} }
                inst.setChoiceByValue((selectEl.value || ''));
                if(prevDisabled){ try{ inst.disable(); }catch(e){} }
            }catch(e){}
        }
    }

    function findById(arr, id){
        if(!Array.isArray(arr)) return null;
        return arr.find(function(x){ return String(x.Id) === String(id) || String(x.id) === String(id); }) || null;
    }

    function __despExt_init(){
        try{ console.log('[despachosExt] init start, readyState=', document.readyState); }catch(e){}
    // Elementos principales
    var turnoEl = document.getElementById('turno');
    var btnNuevo = document.getElementById('btnNuevo') || document.querySelector('button.btn-outline-success');
    var btnAgregar = document.getElementById('btnAgregar');
    var grilla = document.getElementById('grillaDespacho') && document.getElementById('grillaDespacho').querySelector('tbody');
    // botón Imprimir (debe permanecer siempre habilitado)
    var btnImprimir = document.getElementById('btnImprimir');
    // Preferir botones por id si existen (compatibilidad con nuevo markup)
    var btnGuardarById = document.getElementById('btnGuardar');
    if(btnGuardarById) {
        try{ btnGuardar = btnGuardarById; }catch(e){}
    }

    // Helper: normalizar la apariencia de un botón (quitar clases btn-outline-*, añadir btn-action y variante)
    function normalizeButtonLook(btn, variantClass){
        if(!btn) return;
        try{
            // quitar clases outline de bootstrap si existen
            var toRemove = [];
            btn.classList.forEach(function(c){ if(c.indexOf('btn-outline')===0) toRemove.push(c); });
            toRemove.forEach(function(c){ try{ btn.classList.remove(c); }catch(e){} });
            // asegurarse de tener la clase base y la variante
            if(!btn.classList.contains('btn-action')) btn.classList.add('btn-action');
            if(variantClass && !btn.classList.contains(variantClass)) btn.classList.add(variantClass);
            // mantener boton como .btn para consistencia con bootstrap
            if(!btn.classList.contains('btn')) btn.classList.add('btn');
        }catch(e){ }
    }

    // Helper: aplicar estado (habilitado/deshabilitado) y actualizar apariencia
    function setButtonEnabled(btn, enabled){
        if(!btn) return;
        try{
            if(enabled){ btn.removeAttribute('disabled'); btn.style.opacity = ''; btn.style.cursor = ''; }
            else { btn.setAttribute('disabled','disabled'); btn.style.opacity = ''; btn.style.cursor = 'not-allowed'; }
        }catch(e){}
    }

    // Normalizar botones iniciales
    try{ normalizeButtonLook(btnNuevo, 'btn-new'); normalizeButtonLook(btnGuardar, 'btn-save'); normalizeButtonLook(document.getElementById('btnModificar'), 'btn-edit'); }catch(e){}
    // Estado inicial: Nuevo habilitado, Guardar y Modificar deshabilitados
    try{ if(btnNuevo) setButtonEnabled(btnNuevo, true); if(btnGuardar) setButtonEnabled(btnGuardar, false); var btnModInit = document.getElementById('btnModificar'); if(btnModInit) setButtonEnabled(btnModInit, false); }catch(e){}

        // Inicializar Choices.js en selects (igual que despachosinternos)
        window.choicesInstances = window.choicesInstances || {};
        document.querySelectorAll('.custom-dropdown').forEach(function(select){
            try{
                // FIX (doble inicialización de Choices.js): si la vista de edición
                // (despachosexternos_edicion.js) ya creó la instancia de este select
                // (fallback en initChoices) o el select ya está envuelto con .choices,
                // NO volver a inicializar. Recrear sobre un elemento ya inicializado
                // deja instancias corruptas (sin isDisabled) que rompen enable()/disable()
                // y hacen que el dropdown del turno no despliegue al marcar "Manual".
                if (window.choicesInstances && window.choicesInstances[select.id]) {
                    return; // ya hay instancia válida, reutilizarla
                }
                if (select.closest && select.closest('.choices')) {
                    return; // select ya envuelto por Choices, no recrear
                }
                window.choicesInstances[select.id] = new Choices(select, {
                    searchEnabled: true,
                    searchChoices: true,
                    shouldSort: false,
                    itemSelectText: '',
                    allowHTML: false,
                    searchResultLimit: 100,
                    position: 'auto',
                    placeholder: true,
                    placeholderValue: (select.options && select.options[0] && select.options[0].text) ? select.options[0].text : 'Seleccione',
                    noResultsText: 'No se encontraron resultados',
                    removeItemButton: false,
                    duplicateItemsAllowed: false,
                });
                // showDropdown focus behavior
                select.addEventListener('showDropdown', function(){
                    setTimeout(function(){
                        var input = select.parentElement.querySelector('.choices__input');
                        if(input){ input.style.display = 'block'; input.placeholder = 'Buscar...'; input.focus(); }
                    },100);
                });
                // Agregar listener de change para aplicar wrapping automático cuando cambian
                select.addEventListener('change', function(){
                    setTimeout(function(){
                        if(typeof updateAllChoicesWrap === 'function'){ 
                            updateAllChoicesWrap();
                            console.log('[despExt] updateAllChoicesWrap triggered by change on', select.id);
                        }
                    }, 100);
                });
            }catch(e){ /* ignore */ }
        });
        
        // Aplicar wrapping automático a todos los selects después de inicializar
        setTimeout(function(){
            if(typeof updateAllChoicesWrap === 'function'){ updateAllChoicesWrap(); }
        }, 300);

        // --- Lógica de Turno automática (copiada de public/js/despachosinternos.js) ---
        // Usamos alias para reutilizar los elementos ya referenciados en este archivo
    var turnoSelect = turnoEl || document.getElementById('turno');
    // Evitar referenciar una variable global inexistente (antes: "|| chkTurno")
    var chkTurnoManual = document.getElementById('chkTurno');
        var turnos = window.turnosData || [];
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
            console.log('[despachosExt][DIAG-TURNO] setTurnoByHora ejecutado -> horaActual:', horaActual, '| turnoSelect.value antes:', turnoSelect.value, '| turnosData:', turnos);
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
                try { window.choicesInstances['turno'].setChoiceByValue(turnoSelect.value); } catch (e) {}
            }
            return found;
        }
        // Bloquear controles al cargar: todos deshabilitados excepto el botón 'Nuevo'
        function bloquearControles(){
            // NOTA: No incluir 'btnImprimir' en esta lista porque el botón debe permanecer siempre habilitado
            var ids = ['fecha','destino','ruc','chofer','brevete','transportista','ruc_transportista','placa_tracto','placa_carreta','constancia_inscripcion','constancia_inscripcion_2','guiaRemision','producto','cantidad','comentarios','unidadMedida','despachador','direccion','codigo','turno','btnAgregar','btnQuitar','btnLimpiar','btnGuardar','btnModificar'];
            ids.forEach(function(id){ var el = document.getElementById(id); if(el) el.setAttribute('disabled','disabled'); });
            // Intentar deshabilitar controles dentro del formulario específico si existe
            var container = document.getElementById('formDespacho') || document.querySelector('.despacho-form') || document.querySelector('#main') || document.body;
            Array.prototype.slice.call(container.querySelectorAll('input,select,textarea,button')).forEach(function(el){
                if(!el) return;
                // Mantener activos los controles marcados con la clase 'keep-enabled'
                try{ if(el.classList && el.classList.contains('keep-enabled')) return; }catch(e){}
                // No deshabilitar el botón Nuevo ni el botón Imprimir (debe permanecer activo)
                if(el === btnNuevo || el === btnImprimir) return;
                // No deshabilitar botones pertenecientes al modal de vista previa (cerrar/imprimir)
                try{
                    if(el.id === 'btnValePrintExt') return;
                    if(el.closest && el.closest('#modalValePreviewExt')) return;
                    if(el.hasAttribute && el.hasAttribute('data-bs-dismiss')) return;
                    if(el.classList && el.classList.contains('btn-close')) return;
                }catch(e){}
                // Algunos botones de navegación u otros deben mantenerse; limitar a los que estén dentro del contenedor
                try{ el.setAttribute('disabled','disabled'); }catch(e){}
            });
            // Deshabilitar instancias de Choices si existen
            if(window.choicesInstances){ Object.keys(window.choicesInstances).forEach(function(k){ try{ window.choicesInstances[k].disable(); }catch(e){} }); }
            // Asegurar que el botón Nuevo quede habilitado
            if(btnNuevo) btnNuevo.removeAttribute('disabled');
            // Asegurar que el botón Imprimir quede habilitado
            if(btnImprimir) try{ btnImprimir.removeAttribute('disabled'); }catch(e){}
        }

        if (turnoSelect && turnos.length) {
            setTimeout(function() {
                // Solo ejecutar la lógica de nuevo registro si NO estamos en la vista de edición
                // (la vista de edición tiene su propio bloquearControles en despachosexternos_edicion.js)
                var esVistaEdicion = !!document.getElementById('btnBuscarVale') || !!document.getElementById('modalBuscarVale');
                
                if(!esVistaEdicion) {
                    // FIX: solo en vista de nuevo registro se recalcula el turno por hora.
                    // En la vista de edición NO se debe sobrescribir el turno cargado desde la BD
                    // (poblarFormularioConVale ya lo setea con el valor del vale).
                    setTurnoByHora();
                    // Aplicar fecha efectiva para turno nocturno en carga inicial
                    try{ if(typeof window.aplicarFechaEfectivaTurno === 'function') window.aplicarFechaEfectivaTurno(false); }catch(e){}
                    // bloquear todos los controles excepto Nuevo (solo en vista de nuevo registro)
                    try{ bloquearControles(); }catch(e){}
                }
                
                // mantener turno deshabilitado (Choices ya deshabilitadas por bloquearControles, pero lo repetimos por si acaso)
                turnoSelect.setAttribute('disabled', 'disabled');
                if (window.choicesInstances && window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
            }, 200);
        }

    // --- Forzar wrapping en select 'destino' para descripciones largas (no empujar controles a la derecha) ---
    function updateDestinoChoiceWrap() {
        try {
            var destinoSelect = document.getElementById('destino');
            if (!destinoSelect) return;
            var choicesEl = document.querySelector('#destino + .choices') || destinoSelect.nextElementSibling;
            if (!choicesEl) return;
            var container = choicesEl.querySelector('.choices__inner');
            var single = choicesEl.querySelector('.choices__list--single');
            var item = single ? single.querySelector('.choices__item') : null;
            if (!container || !single || !item) return;

            var itemWidth = item.scrollWidth || item.offsetWidth || 0;
            var containerWidth = container.clientWidth || container.offsetWidth || 0;
            var itemHeight = item.scrollHeight || item.offsetHeight || 0;
            var containerHeight = container.clientHeight || container.offsetHeight || 0;

            var needWrap = (itemWidth > containerWidth - 6) || (itemHeight > containerHeight - 4) || (single.scrollHeight > containerHeight - 4) || (container.scrollHeight > containerHeight);

            if (needWrap) {
                choicesEl.classList.add('choices-wrap-destino');
                try {
                    var targetW = Math.max(container.offsetWidth - 40, 80);
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
                    setTimeout(function(){
                        var realHeight = Math.max(single.scrollHeight, item.scrollHeight, item.offsetHeight);
                            if (realHeight > 32) {
                            var targetHeight = realHeight + 6;
                            container.style.cssText = 'height:auto !important; min-height:' + targetHeight + 'px !important; max-height:none !important; overflow:visible !important; display:flex !important; align-items:center !important; padding-top:0 !important; padding-bottom:0 !important;';
                        }
                    }, 80);
                } catch (e) {}
            } else {
                choicesEl.classList.remove('choices-wrap-destino');
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
                } catch(e){}
            }
        } catch (e) { console.warn('[despExt] updateDestinoChoiceWrap error', e); }
    }

    function injectDestinoWrapCSS() {
        if (document.getElementById('destino-wrap-fix')) return;
        var css = '\n            #destino + .choices { display:inline-block !important; overflow:visible !important; max-width:100% !important; box-sizing:border-box !important; }\n            #destino + .choices .choices__inner { height:auto !important; min-height:38px !important; overflow:visible !important; display:flex !important; flex-wrap:wrap !important; align-items:flex-start !important; padding-top:0.25rem !important; padding-bottom:0.25rem !important; box-sizing:border-box !important; }\n            #destino + .choices .choices__list--single { overflow:visible !important; display:flex !important; flex-wrap:wrap !important; align-items:flex-start !important; width:100% !important; }\n            #destino + .choices .choices__list--single .choices__item { white-space:normal !important; word-break:break-word !important; overflow-wrap:anywhere !important; display:inline-block !important; line-height:1.15 !important; max-width:100% !important; width:100% !important; }\n            #destino + .choices .choices__inner .choices__list--single { display:block !important; }\n        ';
        var st = document.createElement('style'); st.id = 'destino-wrap-fix'; st.appendChild(document.createTextNode(css)); document.head.appendChild(st);
    }

    function ensureDestinoWrapInit() {
        injectDestinoWrapCSS();
        var attempts = 0;
        var maxAttempts = 30;
        var interval = setInterval(function(){
            var choicesEl = document.querySelector('#destino + .choices');
            if (choicesEl) {
                clearInterval(interval);
                try { updateDestinoChoiceWrap(); } catch(e){}
                try {
                    var targetNode = choicesEl.querySelector('.choices__list--single');
                    if (targetNode) {
                        var mo = new MutationObserver(function(){ updateDestinoChoiceWrap(); });
                        mo.observe(targetNode, { childList:true, subtree:true, characterData:true });
                        window._destinoChoicesObserver = mo;
                    }
                } catch(e){}
            }
            attempts++; if (attempts >= maxAttempts) clearInterval(interval);
        }, 200);
    }

    try { if (document.getElementById('destino')) { ensureDestinoWrapInit(); document.addEventListener('DOMContentLoaded', ensureDestinoWrapInit); window.addEventListener('resize', updateDestinoChoiceWrap); } } catch(e){}

        // --- Forzar wrapping en select 'producto' para nombres largos (similar a destino) ---
        function updateProductoChoiceWrap() {
            try {
                var productoSelect = document.getElementById('producto');
                if (!productoSelect) return;
                function findChoicesForSelect(sel){
                    if(!sel) return null;
                    // check common next sibling
                    var n = sel.nextElementSibling;
                    if(n && n.classList && n.classList.contains('choices')) return n;
                    // check previous sibling
                    var p = sel.previousElementSibling;
                    if(p && p.classList && p.classList.contains('choices')) return p;
                    // scan all .choices and find one that shares a common parent or contains the select
                    var all = document.querySelectorAll('.choices');
                    for(var i=0;i<all.length;i++){
                        var c = all[i];
                        try{
                            if(c.contains(sel) || sel.contains(c)) return c;
                            if(c.parentElement && c.parentElement.contains(sel)) return c;
                            if(sel.parentElement && sel.parentElement.contains(c)) return c;
                            if(c.previousElementSibling === sel || c.nextElementSibling === sel) return c;
                        }catch(e){}
                    }
                    return null;
                }
                var choicesEl = findChoicesForSelect(productoSelect);
                if(!choicesEl){ console.log('[despExt] updateProductoChoiceWrap: no choicesEl found by DOM scan; instancesKeys=', Object.keys(window.choicesInstances||{})); return; }
                var container = choicesEl.querySelector('.choices__inner');
                var single = choicesEl.querySelector('.choices__list--single');
                var item = single ? single.querySelector('.choices__item') : null;
                if (!container || !single || !item) return;

                var itemWidth = item.scrollWidth || item.offsetWidth || 0;
                var containerWidth = container.clientWidth || container.offsetWidth || 0;
                var itemHeight = item.scrollHeight || item.offsetHeight || 0;
                var containerHeight = container.clientHeight || container.offsetHeight || 0;

                var needWrap = (itemWidth > containerWidth - 6) || (itemHeight > containerHeight - 4) || (single.scrollHeight > containerHeight - 4) || (container.scrollHeight > containerHeight);

                console.log('[despExt] updateProductoChoiceWrap measurements', {
                    itemWidth: itemWidth, containerWidth: containerWidth,
                    itemHeight: itemHeight, containerHeight: containerHeight,
                    singleScrollHeight: single.scrollHeight, containerScrollHeight: container.scrollHeight,
                    needWrap: needWrap
                });

                if (needWrap) {
                    choicesEl.classList.add('choices-wrap-producto');
                    try {
                        var targetW = Math.max(container.offsetWidth - 40, 80);
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
                        setTimeout(function(){
                            var realHeight = Math.max(single.scrollHeight, item.scrollHeight, item.offsetHeight);
                            if (realHeight > 32) {
                                var targetHeight = realHeight + 6;
                                container.style.cssText = 'height:auto !important; min-height:' + targetHeight + 'px !important; max-height:none !important; overflow:visible !important; display:flex !important; align-items:center !important; padding-top:0 !important; padding-bottom:0 !important;';
                            }
                        }, 80);
                    } catch (e) {}
                } else {
                    choicesEl.classList.remove('choices-wrap-producto');
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
                    } catch(e){}
                }
            } catch (e) { console.warn('[despExt] updateProductoChoiceWrap error', e); }
        }

        // ===== GLOBAL CHOICES WRAPPING SYSTEM =====
        // IDs de campos que NO deben aplicar wrapping (deben usar ellipsis para mantener altura compacta)
        var CHOICES_NO_WRAP_IDS = ['transportista'];
        
        function updateAllChoicesWrap() {
            try {
                // Iteramos directamente sobre window.choicesInstances (mapa ID -> instancia Choices.js),
                // que es la fuente autoritativa. Así evitamos problemas de detección DOM.
                if (!window.choicesInstances) return;
                var ids = Object.keys(window.choicesInstances);
                if (ids.length === 0) return;

                ids.forEach(function(key) {
                    try {
                        var inst = window.choicesInstances[key];
                        if (!inst) return;
                        
                        // Obtener el elemento .choices (containerOuter.element)
                        var choicesEl = null;
                        try { choicesEl = inst.containerOuter && inst.containerOuter.element; } catch(e){}
                        if (!choicesEl || !choicesEl.querySelector) return;

                        var container = choicesEl.querySelector('.choices__inner');
                        var single = choicesEl.querySelector('.choices__list--single');
                        var item = single ? single.querySelector('.choices__item') : null;
                        if (!container || !single || !item) return;

                        // Verificar si este select debe usar ellipsis en vez de wrapping
                        var useEllipsis = CHOICES_NO_WRAP_IDS.indexOf(key) !== -1;

                        if (useEllipsis) {
                            // Aplicar ellipsis en lugar de wrap: mantener altura fija y truncar con puntos suspensivos
                            choicesEl.classList.remove('choices-wrap-auto');
                            try {
                                container.style.overflow = 'hidden';
                                container.style.height = '';
                                container.style.minHeight = '32px';
                                container.style.maxHeight = '36px';
                                container.style.paddingTop = '';
                                container.style.paddingBottom = '';
                                container.style.display = '';
                                single.style.whiteSpace = 'nowrap';
                                single.style.overflow = 'hidden';
                                single.style.textOverflow = 'ellipsis';
                                single.style.height = '';
                                single.style.width = '';
                                item.style.whiteSpace = 'nowrap';
                                item.style.overflow = 'hidden';
                                item.style.textOverflow = 'ellipsis';
                                item.style.wordBreak = '';
                                item.style.overflowWrap = '';
                                item.style.maxWidth = '';
                                item.style.width = '';
                                item.style.display = '';
                            } catch(e){}
                            return; // Saltar el resto del procesamiento para este elemento
                        }

                        // Verificar longitud del texto directamente
                        var textContent = item.textContent || '';
                        var needWrap = textContent.length > 35; // Si tiene más de 35 caracteres, aplicar wrap

                        if (needWrap) {
                            choicesEl.classList.add('choices-wrap-auto');
                            try {
                                var targetW = Math.max(container.offsetWidth - 40, 80);
                                
                                // Aplicar estilos de wrap
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
                                
                                // Forzar reflow leyendo una propiedad
                                void item.offsetHeight;
                                void single.offsetHeight;
                                
                                // Función para medir y aplicar altura DESPUÉS del reflow
                                function applyHeightNow() {
                                    // Forzar otro reflow antes de medir
                                    void container.offsetHeight;
                                    
                                    var realHeight = Math.max(single.scrollHeight, item.scrollHeight, item.offsetHeight);
                                    
                                    if (realHeight > 32) {
                                        var targetHeight = realHeight + 6;
                                        var cssText = 'height:auto !important; min-height:' + targetHeight + 'px !important; max-height:none !important; overflow:visible !important; display:flex !important; align-items:center !important; padding-top:0 !important; padding-bottom:0 !important;';
                                        container.style.cssText = cssText;
                                    }
                                }
                                
                                // Aplicar con delays para dar tiempo al navegador
                                setTimeout(applyHeightNow, 100);
                                setTimeout(applyHeightNow, 300);
                                setTimeout(applyHeightNow, 500);
                            } catch (e) { console.error('[despExt] Error applying wrap:', e); }
                        } else {
                            choicesEl.classList.remove('choices-wrap-auto');
                            try {
                                container.style.overflow = '';
                                container.style.height = '';
                                container.style.minHeight = '';
                                container.style.paddingTop = '';
                                container.style.paddingBottom = '';
                                container.style.display = '';
                                single.style.whiteSpace = '';
                                single.style.overflow = '';
                                single.style.height = '';
                                single.style.width = '';
                                item.style.whiteSpace = '';
                                item.style.wordBreak = '';
                                item.style.overflowWrap = '';
                                item.style.maxWidth = '';
                                item.style.width = '';
                            } catch(e){}
                        }
                    } catch(e) { console.warn('[despExt] Error procesando choices individual', e); }
                });
            } catch (e) { console.warn('[despExt] updateAllChoicesWrap error', e); }
        }

        function injectProductoWrapCSS() {
            if (document.getElementById('producto-wrap-fix')) return;
            var css = '\n            /* Specific when choices element is adjacent to #producto */\n            #producto + .choices { display:inline-block !important; overflow:visible !important; max-width:100% !important; box-sizing:border-box !important; }\n            #producto + .choices .choices__inner { height:auto !important; min-height:38px !important; overflow:visible !important; display:flex !important; flex-wrap:wrap !important; align-items:flex-start !important; padding-top:0.25rem !important; padding-bottom:0.25rem !important; box-sizing:border-box !important; }\n            #producto + .choices .choices__list--single { overflow:visible !important; display:flex !important; flex-wrap:wrap !important; align-items:flex-start !important; width:100% !important; }\n            #producto + .choices .choices__list--single .choices__item { white-space:normal !important; word-break:break-word !important; overflow-wrap:anywhere !important; display:inline-block !important; line-height:1.15 !important; max-width:100% !important; width:100% !important; }\n            #producto + .choices .choices__inner .choices__list--single { display:block !important; }\n            /* Generic fallback when we add the helper class dynamically */\n            .choices-wrap-producto { display:block !important; overflow:visible !important; max-width:100% !important; box-sizing:border-box !important; }\n            .choices-wrap-producto .choices__inner { height:auto !important; min-height:38px !important; overflow:visible !important; display:flex !important; flex-wrap:wrap !important; align-items:flex-start !important; padding-top:0.25rem !important; padding-bottom:0.25rem !important; box-sizing:border-box !important; }\n            .choices-wrap-producto .choices__list--single { overflow:visible !important; display:flex !important; flex-wrap:wrap !important; align-items:flex-start !important; width:100% !important; }\n            .choices-wrap-producto .choices__list--single .choices__item { white-space:normal !important; word-break:break-word !important; overflow-wrap:anywhere !important; display:inline-block !important; line-height:1.15 !important; max-width:100% !important; width:100% !important; }\n            .choices-wrap-producto .choices__inner .choices__list--single { display:block !important; }\n            /* Auto wrap from global updateAllChoicesWrap */\n            .choices-wrap-auto { display:block !important; overflow:visible !important; max-width:100% !important; box-sizing:border-box !important; }\n            .choices-wrap-auto .choices__inner { height:auto !important; min-height:38px !important; overflow:visible !important; display:flex !important; flex-wrap:wrap !important; align-items:flex-start !important; padding-top:0.25rem !important; padding-bottom:0.25rem !important; box-sizing:border-box !important; }\n            .choices-wrap-auto .choices__list--single { overflow:visible !important; display:flex !important; flex-wrap:wrap !important; align-items:flex-start !important; width:100% !important; }\n            .choices-wrap-auto .choices__list--single .choices__item { white-space:normal !important; word-break:break-word !important; overflow-wrap:anywhere !important; display:inline-block !important; line-height:1.15 !important; max-width:100% !important; width:100% !important; }\n            .choices-wrap-auto .choices__inner .choices__list--single { display:block !important; }\n        ';
            var st = document.createElement('style'); st.id = 'producto-wrap-fix'; st.appendChild(document.createTextNode(css)); document.head.appendChild(st);
            console.log('[despExt] injectProductoWrapCSS: injected producto-wrap-fix con estilos globales');
            try{
                if(!document.getElementById('producto-wrap-override')){
                    var ov = '\n                        /* Override to ensure vertical centering of wrapped producto choices */\n                        #cardProductos #producto + .choices, #cardProductos .choices-wrap-producto, #cardProductos .choices-wrap-auto { vertical-align:middle !important; }\n                        #cardProductos #producto + .choices .choices__inner, #cardProductos .choices-wrap-producto .choices__inner, #cardProductos .choices-wrap-auto .choices__inner { align-items:center !important; padding-top:0.25rem !important; padding-bottom:0.25rem !important; display:flex !important; }\n                    ';
                    var sto = document.createElement('style'); sto.id = 'producto-wrap-override'; sto.appendChild(document.createTextNode(ov)); document.head.appendChild(sto);
                    console.log('[despExt] injectProductoWrapCSS: injected override producto-wrap-override');
                }
            }catch(e){ }
        }
        
        // Exponer función global para uso externo
        if (typeof window.updateAllChoicesWrap === 'undefined') {
            window.updateAllChoicesWrap = updateAllChoicesWrap;
        }
        
        // Listener para resize de ventana: reaplica wrapping a todos los selects
        window.addEventListener('resize', function(){
            setTimeout(function(){
                if(typeof updateAllChoicesWrap === 'function'){ updateAllChoicesWrap(); }
            }, 150);
        });

        function ensureProductoWrapInit() {
            injectProductoWrapCSS();
            var attempts = 0;
            var maxAttempts = 30;
            var interval = setInterval(function(){
                attempts++;
                var choicesEl = document.querySelector('#producto + .choices') || document.querySelector('#producto') && (function(){
                    var sel = document.querySelector('#producto');
                    if(!sel) return null;
                    var all = document.querySelectorAll('.choices');
                    for(var i=0;i<all.length;i++){
                        var c = all[i];
                        try{ if(c.contains(sel) || sel.parentElement === c.parentElement || c.previousElementSibling === sel || c.nextElementSibling === sel) return c; }catch(e){}
                    }
                    return null;
                })();
                if (choicesEl) {
                    clearInterval(interval);
                    try { updateProductoChoiceWrap(); } catch(e){}
                    try {
                        var targetNode = choicesEl.querySelector('.choices__list--single');
                        if (targetNode) {
                            var mo = new MutationObserver(function(){ updateProductoChoiceWrap(); });
                            mo.observe(targetNode, { childList:true, subtree:true, characterData:true });
                            window._productoChoicesObserver = mo;
                        }
                    } catch(e){}
                }
                if (attempts >= maxAttempts) { clearInterval(interval); }
            }, 200);
        }

        try { if (document.getElementById('producto')) { ensureProductoWrapInit(); document.addEventListener('DOMContentLoaded', ensureProductoWrapInit); window.addEventListener('resize', updateProductoChoiceWrap); } } catch(e){}

            // Forzar mayúsculas en Guía Remisión al tipear (mantiene números y caracteres especiales)
            try {
                var guiaElMain = document.getElementById('guiaRemision');
                if (guiaElMain) {
                    guiaElMain.addEventListener('input', function (e) {
                        try {
                            var s = this.selectionStart;
                            var epos = this.selectionEnd;
                            var before = this.value;
                            var after = before.toUpperCase();
                            if (after !== before) {
                                this.value = after;
                                try { this.setSelectionRange(s, epos); } catch (err) { /* ignore */ }
                            }
                        } catch (err) { /* noop */ }
                    });
                }
            } catch (err) { /* noop */ }

        // Asegurar que el contenedor del modal de vista previa exista desde el inicio (evita fallos cuando se crea dinámicamente)
        try{
            if(!document.getElementById('modalValePreviewExt')){
                var preModalHtml = `
                <div class="modal fade" id="modalValePreviewExt" tabindex="-1" aria-hidden="true" style="display:none;">
                    <div class="modal-dialog modal-md modal-dialog-centered" style="max-width:620px;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" style="font-size:0.9rem;">Vista previa de Vale de Despacho Externo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="valePreviewContentExt" style="background:#fff; padding:8px; font-size:0.75rem;">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-primary" id="btnValePrintExt"> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align:middle; margin-right:6px;"><path d="M2 7a1 1 0 0 0-1 1v3h3v2h8v-2h3V8a1 1 0 0 0-1-1H2zm11 6H3v-3h10v3zM0 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3h-2V5H2v3H0V5z"/></svg>Imprimir</button>
                            </div>
                        </div>
                    </div>
                </div>`;
                var wrapperPre = document.createElement('div'); wrapperPre.innerHTML = preModalHtml;
                document.body.appendChild(wrapperPre.firstElementChild);
                console.log('[despachosExt] modal placeholder pre-created');
            }
        }catch(e){ console.warn('[despachosExt] no se pudo pre-crear modal placeholder', e); }

        // Solicitar siguiente correlativo al cargar (formato VDE-000001)
        try{
              fetch((window.APP_URL || window.BASE_URL) + '/despachosexternos/siguienteVale')
                .then(function(r){ return r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)); })
                .then(function(res){ if(res && res.success && res.correlativo){ var corrEl = document.getElementById('correlativoVale'); if(corrEl) corrEl.textContent = 'VDE-' + String(res.correlativo).padStart(6,'0'); } })
                .catch(function(){ /* no bloquear por error */ });
        }catch(e){}
        if (chkTurnoManual && turnoSelect) {
            chkTurnoManual.addEventListener('change', function() {
                console.log('[despachosExt][DIAG-TURNO] chkTurno change -> checked:', this.checked, '| turnoSelect.disabled ANTES:', turnoSelect.disabled, '| choices.turno.isDisabled ANTES:', window.choicesInstances && window.choicesInstances['turno'] ? window.choicesInstances['turno'].isDisabled : 'N/A');
                if (this.checked) {
                    turnoSelect.removeAttribute('disabled');
                    if (window.choicesInstances && window.choicesInstances['turno']) window.choicesInstances['turno'].enable();
                    var _ci = window.choicesInstances && window.choicesInstances['turno'];
                    console.log('[despachosExt][DIAG-TURNO] modo MANUAL activado -> turnoSelect.disabled:', turnoSelect.disabled, '| choices.turno.isDisabled:', _ci ? _ci.isDisabled : 'N/A');
                } else {
                    turnoSelect.setAttribute('disabled', 'disabled');
                    if (window.choicesInstances && window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
                    setTurnoByHora();
                    console.log('[despachosExt][DIAG-TURNO] modo AUTO activado -> turnoSelect.disabled:', turnoSelect.disabled);
                }
            });
            if (window.choicesInstances && window.choicesInstances['turno']) window.choicesInstances['turno'].disable();
        }

        // Sincronizaciones sencillas (origen -> destino)
        function wireSync(selectA, selectB, keyA, keyB, mapFn){
            if(!selectA || !selectB) return;
            selectA.addEventListener('change', function(){
                var val = selectA.value || '';
                var dataKey = window[keyA] || window[keyA+'s'] || window[keyA+'Data'];
                var found = Array.isArray(dataKey) ? (mapFn ? mapFn(dataKey, val) : dataKey.find(function(x){ return String(x.Id)===String(val) || String(x.id)===String(val); })) : null;
                if(found){ setSelectValue(selectB, found[keyB] || found[keyB.toUpperCase()] || '', keyB); }
                else setSelectValue(selectB, '', keyB);
            });
        }

        // destino <-> ruc (bidireccional)
        var destinoSelect = document.getElementById('destino');
        var rucSelect = document.getElementById('ruc');
        if(destinoSelect && rucSelect){
            destinoSelect.addEventListener('change', function(){
                var id = destinoSelect.value;
                var destinos = window.destinosData || [];
                var found = destinos.find(function(d){ return String(d.Id)===String(id) || String(d.id)===String(id); });
                if(found){
                    setSelectValue(rucSelect, found.RUC || found.ruc || '', 'ruc');
                    // llenar direccion si existe
                    var dirEl = document.getElementById('direccion'); if(dirEl) dirEl.value = found.Direccion || found.direccion || '';
                } else {
                    setSelectValue(rucSelect, '', 'ruc');
                    var dirEl = document.getElementById('direccion'); if(dirEl) dirEl.value = '';
                }
            });
            rucSelect.addEventListener('change', function(){
                var r = rucSelect.value;
                var destinos = window.destinosData || [];
                var found = destinos.find(function(d){ return String(d.RUC)===String(r) || String(d.ruc)===String(r); });
                if(found){
                    setSelectValue(destinoSelect, found.Id || found.id || '', 'destino');
                    var dirEl = document.getElementById('direccion'); if(dirEl) dirEl.value = found.Direccion || found.direccion || '';
                } else {
                    setSelectValue(destinoSelect, '', 'destino');
                    var dirEl = document.getElementById('direccion'); if(dirEl) dirEl.value = '';
                }
            });
        }

        // chofer <-> brevete
        var choferSelect = document.getElementById('chofer');
        var breveteSelect = document.getElementById('brevete');
        if(choferSelect && breveteSelect){
            choferSelect.addEventListener('change', function(){
                var id = choferSelect.value; var arr = window.choferesData || [];
                var f = arr.find(function(x){ return String(x.Id)===String(id) || String(x.id)===String(id); });
                setSelectValue(breveteSelect, f ? (f.Brevete||f.brevete||'') : '', 'brevete');
            });
            breveteSelect.addEventListener('change', function(){
                var r = breveteSelect.value; var arr = window.choferesData || [];
                var f = arr.find(function(x){ return String(x.Brevete)===String(r) || String(x.brevete)===String(r); });
                setSelectValue(choferSelect, f ? (f.Id||f.id||'') : '', 'chofer');
            });
        }

        // transportista <-> ruc_transportista
        var transportistaSelect = document.getElementById('transportista');
        var rucTransportistaSelect = document.getElementById('ruc_transportista');
        // helper: set value safely on a select + its Choices instance (if existe)
        function safeSetChoiceValue(selectEl, value){
            if(!selectEl) return;
            try{ selectEl.value = (value === null || value === undefined) ? '' : String(value); }catch(e){}
            var inst = (window.choicesInstances && selectEl.id) ? window.choicesInstances[selectEl.id] : null;
            if(inst){ try{ inst.setChoiceByValue(selectEl.value || ''); }catch(e){ /* ignore */ } }
        }
        var placaSelect = document.getElementById('placa_tracto');
        // debug: monitorizar cambios en los selects RUC para detectar sobrescrituras
        try{
            if(rucTransportistaSelect){
                rucTransportistaSelect.addEventListener('change', function(e){
                    try{ if(window.__DESP_DEBUG) console.log('[despachosExt] EVENT ruc_transportista change -> value=', this.value, ' transportista=', (transportistaSelect?transportistaSelect.value:null), ' destino_ruc=', (rucSelect?rucSelect.value:null)); }catch(e){}
                    try{ if(window.__DESP_DEBUG) console.trace(); }catch(e){}
                });
            }
            if(rucSelect){
                rucSelect.addEventListener('change', function(e){
                    try{ if(window.__DESP_DEBUG) console.log('[despachosExt] EVENT ruc (destino) change -> value=', this.value, ' destino=', (destinoSelect?destinoSelect.value:null)); }catch(e){}
                    try{ if(window.__DESP_DEBUG) console.trace(); }catch(e){}
                });
            }
        }catch(e){}
        if(transportistaSelect && rucTransportistaSelect){
            transportistaSelect.addEventListener('change', function(){
                var id = transportistaSelect.value; var arr = window.transportistasData || [];
                var f = arr.find(function(x){ return String(x.Id)===String(id) || String(x.id)===String(id); });
                var desiredRuc = f ? (f.RUC||f.ruc||'') : '';
                // debug info (activar con window.__DESP_DEBUG = true)
                try{ if(window.__DESP_DEBUG) console.log('[despachosExt] transportista change -> id=', id, 'found=', f); }catch(e){}
                // establecer explícitamente el RUC del transportista en su select
                safeSetChoiceValue(rucTransportistaSelect, desiredRuc);
                // Reforzar asignación tras un corto delay en caso otro handler la sobreescriba
                try{ setTimeout(function(){ safeSetChoiceValue(rucTransportistaSelect, desiredRuc); }, 40); }catch(e){}
                // sincronizar placa si hay datos (establecer directamente también)
                try{ if(placaSelect){ safeSetChoiceValue(placaSelect, f ? (f.Placa || f.placa || f.Placa_Tracto || f.placa_vehiculo || '') : ''); } }catch(e){}
            });
            rucTransportistaSelect.addEventListener('change', function(){
                var r = rucTransportistaSelect.value; var arr = window.transportistasData || [];
                var f = arr.find(function(x){ return String(x.RUC)===String(r) || String(x.ruc)===String(r); });
                setSelectValue(transportistaSelect, f ? (f.Id||f.id||'') : '', 'transportista');
                // sincronizar placa si hay datos
                try{ if(placaSelect){ setSelectValue(placaSelect, f ? (f.Placa || f.placa || f.Placa_Tracto || '') : '', 'placa'); } }catch(e){}
            });
        }

        // placa -> transportista, ruc_transportista (cuando usuario elige placa)
        if(placaSelect){
            placaSelect.addEventListener('change', function(){
                var placaVal = placaSelect.value; var arr = window.transportistasData || [];
                // Buscar transportista que tenga esa placa (coincidencia en Placa o placa)
                var f = arr.find(function(x){
                    try{
                        return String((x.Placa||x.placa||'')).trim() === String(placaVal).trim();
                    }catch(e){ return false; }
                });
                if(f){
                    var desiredT = f.Id||f.id||'';
                    var desiredR = f.RUC||f.ruc||'';
                    try{ if(window.__DESP_DEBUG) console.log('[despachosExt] placa change -> placa=', placaVal, 'found transportista=', f); }catch(e){}
                    // establecer transportista (por id) y su RUC explícitamente
                    safeSetChoiceValue(transportistaSelect, desiredT);
                    safeSetChoiceValue(rucTransportistaSelect, desiredR);
                    // reforzar en corto delay
                    try{ setTimeout(function(){ safeSetChoiceValue(transportistaSelect, desiredT); safeSetChoiceValue(rucTransportistaSelect, desiredR); }, 40); }catch(e){}
                } else {
                    // Si no se encuentra ningún transportista para la placa, NO limpiar
                    // los selects relacionados automáticamente (evita borrar una selección
                    // previa del usuario). Sólo limpiar si el campo placa quedó vacío.
                    try{
                        if(!placaVal || String(placaVal).trim() === ''){
                            safeSetChoiceValue(transportistaSelect, '');
                            safeSetChoiceValue(rucTransportistaSelect, '');
                        } else {
                            // mantener valores actuales del select (no sobreescribir)
                            if(window.__DESP_DEBUG) console.log('[despachosExt] placa change no match - keeping existing transportista');
                        }
                    }catch(e){ /* no bloquear por error */ }
                }
            });
        }

        // placa_tracto -> constancia_inscripcion (sincronización)
        (function(){
            var ptSelect = document.getElementById('placa_tracto');
            var ciSelect = document.getElementById('constancia_inscripcion');
            if(ptSelect && ciSelect){
                ptSelect.addEventListener('change', function(){
                    var opt = this.options[this.selectedIndex];
                    var constanciaVal = opt ? (opt.getAttribute('data-constancia') || '') : '';
                    if(constanciaVal && ciSelect.options){
                        for(var i=0;i<ciSelect.options.length;i++){
                            if(String(ciSelect.options[i].value) === String(constanciaVal)){
                                ciSelect.value = ciSelect.options[i].value;
                                if(window.choicesInstances && window.choicesInstances['constancia_inscripcion']){
                                    try{ window.choicesInstances['constancia_inscripcion'].setChoiceByValue(ciSelect.value); }catch(e){}
                                }
                                break;
                            }
                        }
                    } else {
                        ciSelect.value = '';
                        if(window.choicesInstances && window.choicesInstances['constancia_inscripcion']){
                            try{ window.choicesInstances['constancia_inscripcion'].setChoiceByValue(''); }catch(e){}
                        }
                    }
                });
            }
        })();

        // placa_carreta -> constancia_inscripcion_2 (sincronización)
        (function(){
            var pcSelect = document.getElementById('placa_carreta');
            var ci2Select = document.getElementById('constancia_inscripcion_2');
            if(pcSelect && ci2Select){
                pcSelect.addEventListener('change', function(){
                    var opt = this.options[this.selectedIndex];
                    var constanciaVal = opt ? (opt.getAttribute('data-constancia') || '') : '';
                    if(constanciaVal && ci2Select.options){
                        for(var i=0;i<ci2Select.options.length;i++){
                            if(String(ci2Select.options[i].value) === String(constanciaVal)){
                                ci2Select.value = ci2Select.options[i].value;
                                if(window.choicesInstances && window.choicesInstances['constancia_inscripcion_2']){
                                    try{ window.choicesInstances['constancia_inscripcion_2'].setChoiceByValue(ci2Select.value); }catch(e){}
                                }
                                break;
                            }
                        }
                    } else {
                        ci2Select.value = '';
                        if(window.choicesInstances && window.choicesInstances['constancia_inscripcion_2']){
                            try{ window.choicesInstances['constancia_inscripcion_2'].setChoiceByValue(''); }catch(e){}
                        }
                    }
                });
            }
        })();

        // producto -> codigo, unidad
        var productoEl = document.getElementById('producto');
        var codigoEl = document.getElementById('codigo');
        var unidadMedidaEl = document.getElementById('unidadMedida');
        if(productoEl){
            productoEl.addEventListener('change', function(){
                var id = productoEl.value; var arr = window.productosData || [];
                var f = arr.find(function(x){ return String(x.Id)===String(id) || String(x.id)===String(id); });
                if(f){
                    var codigoVal = (typeof f.Codigo !== 'undefined' && f.Codigo !== null) ? f.Codigo : ((typeof f.CodigoProducto !== 'undefined' && f.CodigoProducto !== null) ? f.CodigoProducto : ((typeof f.codigo !== 'undefined' && f.codigo !== null) ? f.codigo : ''));
                    var unidadVal = (typeof f.UnidadMedida !== 'undefined' && f.UnidadMedida !== null) ? f.UnidadMedida : ((typeof f.unidad !== 'undefined' && f.unidad !== null) ? f.unidad : '');
                    setSelectValue(codigoEl, codigoVal, 'codigo');
                    setSelectValue(unidadMedidaEl, unidadVal, 'unidadMedida');
                }
                else { setSelectValue(codigoEl, '', 'codigo'); setSelectValue(unidadMedidaEl, '', 'unidadMedida'); }
            });
        }

        // Botón Nuevo: habilita campos principales excepto turno, despachador, direccion y codigo
        if(btnNuevo){
            btnNuevo.addEventListener('click', function(){
                // --- 1. LIMPIAR TODOS LOS VALORES DE LOS CONTROLES ---
                // Campos de texto/select que deben limpiarse para nuevo registro
                var camposALimpiar = ['fecha','destino','ruc','chofer','brevete','transportista','ruc_transportista','placa_tracto','placa_carreta','constancia_inscripcion','constancia_inscripcion_2','guiaRemision','direccion'];
                camposALimpiar.forEach(function(id){
                    var el = document.getElementById(id);
                    if(el){
                        if(window.choicesInstances && window.choicesInstances[id]){
                            try{ window.choicesInstances[id].setChoiceByValue(''); }catch(e){}
                        } else {
                            el.value = '';
                        }
                    }
                });
                // Limpiar campos de producto
                var productoEl = document.getElementById('producto');
                if(productoEl){
                    productoEl.value = '';
                    if(window.choicesInstances && window.choicesInstances['producto']){
                        try{ window.choicesInstances['producto'].setChoiceByValue(''); }catch(e){}
                    }
                }
                var codigoEl = document.getElementById('codigo'); if(codigoEl) codigoEl.value = '';
                var cantEl = document.getElementById('cantidad'); if(cantEl) cantEl.value = '';
                var comEl = document.getElementById('comentarios'); if(comEl) comEl.value = '';
                var unidadEl = document.getElementById('unidadMedida'); if(unidadEl) unidadEl.value = '';

                // --- 2. LIMPIAR LA GRILLA DE PRODUCTOS Y RECONSTRUIR SELECT ---
                var grillaBody = document.getElementById('grillaDespacho')?.querySelector('tbody');
                if(grillaBody) grillaBody.innerHTML = '';
                // Resetear arreglo de datos de productos (si existe)
                try{ if(typeof datosGrilla !== 'undefined') datosGrilla = []; }catch(e){}
                // Resetear estado de edición
                try{ editIndex = null; }catch(e){}
                try{ updatePhase = null; }catch(e){}
                // Reconstruir el select de productos con todas las opciones disponibles
                // (la grilla ya está vacía, así que no se filtrará ningún producto)
                try{ if(typeof rebuildProductoSelect === 'function') rebuildProductoSelect(); }catch(e){}

                // --- 3. LIMPIAR CORRELATIVO Y SOLICITAR NUEVO NÚMERO ---
                var correlativoVale = document.getElementById('correlativoVale');
                if(correlativoVale){
                    correlativoVale.removeAttribute('data-id');
                    correlativoVale.textContent = '';
                }
                // Obtener siguiente correlativo
                (function(){
                    var endpoints = [
                        (window.APP_URL || window.BASE_URL || '') + '/despachosexternos/siguienteVale',
                        (window.BASE_URL || window.APP_URL || '') + '/index.php?url=despachosexternos/siguienteVale'
                    ];
                    function tryEndpoint(i){
                        if(i >= endpoints.length) return;
                        fetch(endpoints[i], { credentials: 'same-origin' })
                            .then(function(r){ return r.json(); })
                            .then(function(res){
                                if(res && res.success && res.correlativo && correlativoVale){
                                    correlativoVale.textContent = String(res.correlativo);
                                }
                            })
                            .catch(function(){ tryEndpoint(i+1); });
                    }
                    tryEndpoint(0);
                })();

                // --- 4. ESTABLECER FECHA ACTUAL (usando métodos de fecha local) ---
                var fechaEl = document.getElementById('fecha');
                if(fechaEl) {
                    var d = new Date();
                    fechaEl.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                }

                // --- 5. RESETEAR turno automático ---
                try{
                    var chk = document.getElementById('chkTurno');
                    if(chk) chk.checked = false;
                    if(typeof setTurnoByHora === 'function') setTurnoByHora();
                }catch(e){}

                // --- 5b. APLICAR FECHA EFECTIVA PARA TURNO NOCTURNO ---
                try{ if(typeof window.aplicarFechaEfectivaTurno === 'function') window.aplicarFechaEfectivaTurno(false); }catch(e){}

                // --- 6. HABILITAR CONTROLES ---
                var habilitar = ['fecha','destino','ruc','chofer','brevete','transportista','ruc_transportista','placa_tracto','placa_carreta','constancia_inscripcion','constancia_inscripcion_2','guiaRemision','producto','cantidad','comentarios','unidadMedida','btnAgregar','btnQuitar','btnLimpiar','btnGuardar','btnImprimir'];
                habilitar.forEach(function(id){ var el = document.getElementById(id); if(el) el.removeAttribute('disabled');
                    if(window.choicesInstances && window.choicesInstances[id]){ try{ window.choicesInstances[id].enable(); }catch(e){} }
                    try{ limpiarChoicesVisuales(id); }catch(e){}
                });

                // IDs que deben permanecer deshabilitados
                var permanecerDeshabilitados = ['turno','despachador','direccion','codigo'];
                permanecerDeshabilitados.forEach(function(id){ var el = document.getElementById(id); if(el) el.setAttribute('disabled','disabled');
                    if(window.choicesInstances && window.choicesInstances[id]){ try{ window.choicesInstances[id].disable(); }catch(e){} }
                });

                // Deshabilitar el botón Modificar si existe
                var btnModificar = document.getElementById('btnModificar'); if(btnModificar) btnModificar.setAttribute('disabled','disabled');
                // Ajustar visual de botones: Nuevo -> deshabilitado, Guardar -> habilitado
                try{ normalizeButtonLook(btnNuevo,'btn-new'); normalizeButtonLook(btnGuardar,'btn-save'); normalizeButtonLook(btnModificar,'btn-edit'); }catch(e){}
                try{ setButtonEnabled(btnNuevo, false); setButtonEnabled(btnGuardar, true); setButtonEnabled(btnModificar, false); }catch(e){}
                // Habilitar el checkbox de modo manual para turno (pero dejar turno deshabilitado)
                try{
                    var chk = document.getElementById('chkTurno');
                    if(chk){ chk.removeAttribute('disabled'); }
                }catch(e){}
            });
        }

        // Botón Modificar: habilita los mismos controles que Nuevo pero partiendo de una edición existente
        var btnModificar = document.getElementById('btnModificar');
        if(btnModificar){
            btnModificar.addEventListener('click', function(){
                // Verificar si el vale puede ser modificado (límite de 3 modificaciones)
                var correlativoValeEl = document.getElementById('correlativoVale');
                var despachoId = correlativoValeEl ? correlativoValeEl.getAttribute('data-id') : null;
                
                if(!despachoId || despachoId === 'null' || despachoId === 'undefined'){
                    despachoId = null;
                }
                
                if(!despachoId){
                    // No hay vale cargado, permitir modificar sin verificar
                    habilitarControlesParaModificar();
                    return;
                }
                
                // Verificar límite de modificaciones antes de habilitar controles
                fetch((window.APP_URL || window.BASE_URL) + '/despachosexternos/verificarModificaciones?id=' + despachoId)
                    .then(function(response){ return response.json(); })
                    .then(function(data){
                        if(data.success && data.puede_modificar){
                            // Puede modificar, habilitar controles
                            habilitarControlesParaModificar();
                        } else {
                            // No puede modificar, mostrar mensaje
                            var mensaje = data.mensaje || 'Este vale ya alcanzó el límite de 3 modificaciones';
                            mostrarMensajeError(mensaje, 'error');
                        }
                    })
                    .catch(function(error){
                        console.error('[Despachos Externos] Error al verificar modificaciones:', error);
                        mostrarMensajeError('Error al verificar límite de modificaciones', 'error');
                    });
            });
        }
        
        // Limpiar estilos inline !important de deshabilitado en el contenedor Choices de un select.
        // Necesario porque bloquearControles (vista edición) aplica pointer-events:none !important
        // al contenedor, y .enable() de Choices no elimina estilos inline.
        function limpiarChoicesVisuales(id){
            var el = document.getElementById(id);
            if(!el) return;
            var choicesEl = el.nextElementSibling;
            if(!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')){
                try{ choicesEl = document.querySelector('#' + id + ' + .choices'); }catch(e){}
            }
            if(!choicesEl || !choicesEl.classList || !choicesEl.classList.contains('choices')){
                choicesEl = Array.prototype.slice.call(document.querySelectorAll('.choices')).find(function(c){ return c.contains(el); });
            }
            if(!choicesEl) return;
            choicesEl.classList.remove('is-disabled');
            choicesEl.removeAttribute('aria-disabled');
            var inner = choicesEl.querySelector('.choices__inner');
            if(inner){
                inner.removeAttribute('aria-disabled');
                ['background-color','color','cursor','pointer-events','box-shadow','padding','min-height','max-height','height','border','border-radius','opacity'].forEach(function(p){ try{ inner.style.removeProperty(p); }catch(e){} });
            }
            ['background-color','border','border-radius','box-shadow','opacity','pointer-events'].forEach(function(p){ try{ choicesEl.style.removeProperty(p); }catch(e){} });
            Array.prototype.slice.call(choicesEl.querySelectorAll('input,button')).forEach(function(i){
                try{ i.removeAttribute('disabled'); i.disabled = false; }catch(e){}
                try{ i.style.removeProperty('pointer-events'); }catch(e){}
            });
        }

        // Función auxiliar para habilitar controles (extraída del listener original)
        function habilitarControlesParaModificar(){
            // Habilitar los mismos campos que hace el botón Nuevo
            var habilitar = ['fecha','destino','ruc','chofer','brevete','transportista','ruc_transportista','placa_tracto','placa_carreta','constancia_inscripcion','constancia_inscripcion_2','guiaRemision','producto','cantidad','comentarios','unidadMedida','btnAgregar','btnQuitar','btnLimpiar','btnGuardar','btnImprimir'];
            habilitar.forEach(function(id){ var el = document.getElementById(id); if(el) el.removeAttribute('disabled');
                if(window.choicesInstances && window.choicesInstances[id]){ try{ window.choicesInstances[id].enable(); }catch(e){} }
                try{ limpiarChoicesVisuales(id); }catch(e){}
            });
            // Recrear instancias Choices de los selects principales para garantizar que queden
            // habilitados e interactuables (función expuesta por despachosexternos_edicion.js).
            try {
                ['destino','chofer','transportista','placa_tracto','placa_carreta'].forEach(function(id){
                    if (typeof window.recrearChoicesSelectEdit === 'function') {
                        try { window.recrearChoicesSelectEdit(id); } catch(e){}
                    }
                });
            } catch(e){}

            // Mantener deshabilitados los que indica la UI (turno, despachador, direccion, codigo)
            var permanecerDeshabilitados = ['turno','despachador','direccion','codigo'];
            permanecerDeshabilitados.forEach(function(id){ var el = document.getElementById(id); if(el) el.setAttribute('disabled','disabled');
                if(window.choicesInstances && window.choicesInstances[id]){ try{ window.choicesInstances[id].disable(); }catch(e){} }
            });

            // Deshabilitar el propio botón Modificar y habilitar Guardar
            try{ btnModificar.setAttribute('disabled','disabled'); }catch(e){}
            try{ if(btnGuardar) btnGuardar.removeAttribute('disabled'); }catch(e){}
            // Ajustar apariencia
            try{ normalizeButtonLook(btnGuardar,'btn-save'); normalizeButtonLook(btnModificar,'btn-edit'); normalizeButtonLook(btnNuevo,'btn-new'); }catch(e){}
            try{ setButtonEnabled(btnModificar, false); setButtonEnabled(btnGuardar, true); setButtonEnabled(btnNuevo, false); }catch(e){}

            // Asegurar checkbox de turno manual está disponible pero sin cambiar el turno actual
            try{
                var chk = document.getElementById('chkTurno');
                if(chk){ chk.removeAttribute('disabled'); }
            }catch(e){}

            // No recargar ni limpiar controles automáticamente: sólo habilitar y cargar la fila de producto
            try{
                // Si el formulario ya muestra los valores en pantalla, no debemos limpiar ni recargar nada.
                // Mantener valores actuales y sólo cargar la fila seleccionada para edición de producto.
                cargarFilaEnControles(editIndex);
            }catch(e){ console.error('Error al preparar modificación:', e); }
        }

        // Soporte de edición: índice de fila en edición
        var editIndex = null;
        // Fase de actualización: null | 'selected' | 'editing' (evita ReferenceError desde handlers)
        var updatePhase = null;

        function rebuildProductoSelect(selectedCodigo, skipIndex){
            var productoSelect = productoEl;
            if(!productoSelect) return;
            productoSelect.innerHTML = '';
            var selectOption = document.createElement('option'); selectOption.value = ''; selectOption.text = 'Seleccione'; productoSelect.appendChild(selectOption);
            // Construir set de descripciones presentes en la grilla.
            var descripcionesEnGrilla = new Set();
            if (grilla) {
                Array.from(grilla.rows).forEach(function(r, rIdx){
                    // No excluir la fila que se está editando para que su producto siga disponible en el select
                    if (typeof skipIndex !== 'undefined' && skipIndex !== null && rIdx === skipIndex) return;
                    var d = r.cells[2] ? (r.cells[2].textContent || '').trim() : '';
                    if (d) descripcionesEnGrilla.add(d);
                });
            }
            var productosData = window.productosData || [];
            productosData.forEach(function(p){
                var codigoOpt = p.Codigo || p.CodigoProducto || p.codigo || '';
                var texto = p.Producto || p.DescripcionProducto || p.Nombre || '';
                if(!codigoOpt) codigoOpt = String(p.Id);
                var fullText = (codigoOpt ? codigoOpt + ' - ' : '') + (texto || (p.Id ? String(p.Id) : codigoOpt));
                // Saltar si el producto (por descripción) ya está en la grilla
                if (descripcionesEnGrilla.has(fullText)) {
                    return;
                }
                // Si llegó aquí no está en la grilla, así que agregar
                var opt = document.createElement('option');
                opt.value = p.Id;
                opt.text = fullText;
                opt.setAttribute('data-codigo', codigoOpt);
                if(p.UnidadMedida) opt.setAttribute('data-unidadmedida', p.UnidadMedida);
                productoSelect.appendChild(opt);
            });
            if(window.choicesInstances && window.choicesInstances['producto']){ try{ window.choicesInstances['producto'].destroy(); }catch(e){} }
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
                placeholderValue: (productoSelect.options && productoSelect.options[0] && productoSelect.options[0].text) ? productoSelect.options[0].text : 'Seleccione',
                noResultsText: 'No se encontraron resultados',
                removeItemButton: false,
                duplicateItemsAllowed: false,
            });
            // Aplicar wrapping después de reconstruir
            setTimeout(function(){
                if(typeof updateAllChoicesWrap === 'function'){ updateAllChoicesWrap(); }
            }, 100);
            // Agregar listener de change
            try{
                productoSelect.addEventListener('change', function(){
                    var opt = this.options[this.selectedIndex];
                    try{ console.log('[despExt] producto select change -> value:', this.value, 'text:', opt ? opt.textContent : null, 'data-codigo:', opt ? opt.getAttribute('data-codigo') : null); }catch(e){}
                    setTimeout(function(){
                        if(typeof updateAllChoicesWrap === 'function'){ updateAllChoicesWrap(); }
                    }, 100);
                });
            }catch(e){}
        }

        function limpiarControlesExternos(){
            if(productoEl){ try{ productoEl.value = ''; if(window.choicesInstances && window.choicesInstances['producto']) window.choicesInstances['producto'].setChoiceByValue(''); }catch(e){} }
            if(codigoEl) codigoEl.value = '';
            var cant = document.getElementById('cantidad'); if(cant) cant.value = '';
            if(unidadMedidaEl) unidadMedidaEl.value = '';
            var com = document.getElementById('comentarios'); if(com) com.value = '';
            editIndex = null;
            if(btnAgregar){ btnAgregar.textContent = 'Agregar'; btnAgregar.classList.remove('btn-primary'); btnAgregar.classList.add('btn-success'); }
        }

        function cargarFilaEnControles(idx){
            if(!grilla) return;
            var row = grilla.rows[idx]; if(!row) return;
            var codigoEdit = row.getAttribute('data-codigo') || (row.cells[1] ? row.cells[1].textContent.trim() : '');
            var descripcionEdit = row.getAttribute('data-producto') || (row.cells[2] ? row.cells[2].textContent.trim() : '');
            var productoIdEdit = row.getAttribute('data-producto-id') || '';
            try{ console.log('[DIAG-cargarFila] idx=', idx, '| codigoEdit=', codigoEdit, '| descripcionEdit=', descripcionEdit, '| productoIdEdit=', productoIdEdit); }catch(e){}
            rebuildProductoSelect(codigoEdit, idx);
            try{ console.log('[DIAG-cargarFila] tras rebuildProductoSelect -> options count=', productoEl ? productoEl.options.length : -1, '| values=', productoEl ? Array.from(productoEl.options).map(function(o){return o.value+':'+o.textContent;}) : 'NO_PRODUCTO_EL'); }catch(e){}
            if(productoEl && productoEl.options && productoEl.options.length>0){
                var opts = Array.from(productoEl.options);
                var optFound = null;
                if(productoIdEdit){
                    optFound = opts.find(function(o){ try{ return String(o.value) === String(productoIdEdit); }catch(e){ return false; } });
                }
                if(!optFound){
                    optFound = opts.find(function(o){ try{ return (o.getAttribute('data-codigo') && o.getAttribute('data-codigo').trim() === String(codigoEdit).trim()) || (o.textContent && o.textContent.trim() === descripcionEdit); }catch(e){ return false; } });
                }
                try{ console.log('[DIAG-cargarFila] optFound=', optFound ? (optFound.value + ':' + optFound.textContent) : 'NULL (el producto de la fila NO está en el select; probablemente excluido por rebuildProductoSelect)'); }catch(e){}
                if(optFound){
                    productoEl.value = optFound.value;
                    if(window.choicesInstances && window.choicesInstances['producto']){
                        setTimeout(function(){ try{ window.choicesInstances['producto'].setChoiceByValue(optFound.value); }catch(e){} },0);
                    }
                }
            }
            if(codigoEl) codigoEl.value = row.getAttribute('data-codigo') || (row.cells[1] ? row.cells[1].textContent : '');
            var cant = document.getElementById('cantidad'); if(cant) cant.value = row.getAttribute('data-cantidad') || (row.cells[4] ? row.cells[4].textContent : '');
            if(unidadMedidaEl) unidadMedidaEl.value = row.getAttribute('data-unidadmedida') || (row.cells[3] ? row.cells[3].textContent : '');
            var com = document.getElementById('comentarios'); if(com) com.value = row.getAttribute('data-comentarios') || (row.cells[5] ? row.cells[5].textContent : '');
            editIndex = idx;
            if(btnAgregar){ btnAgregar.textContent = 'Guardar'; btnAgregar.classList.remove('btn-primary'); btnAgregar.classList.add('btn-success'); }
        }

        // Cargar despacho (cabecera + productos) desde el servidor y poblar el formulario
        function cargarDespachoEnForm(despachoId){
            if(!despachoId) return;
            var url = (window.APP_URL || window.BASE_URL) + '/despachosexternos/getById?id=' + encodeURIComponent(despachoId);
            fetch(url, { method: 'GET' })
                .then(function(resp){ if(!resp.ok) return resp.text().then(function(t){ throw new Error('HTTP ' + resp.status + ': ' + (t||resp.statusText)); }); return resp.json(); })
                .then(function(res){
                    if(!res || !res.success){ throw new Error((res && res.message) ? res.message : 'Respuesta inválida del servidor'); }
                    var data = res.data || {};
                    // Poblar cabecera
                    try{
                        var fechaEl = document.getElementById('fecha'); if(fechaEl) fechaEl.value = data.Fecha || data.fecha || '';
                        var turnoElLocal = document.getElementById('turno'); if(turnoElLocal) setSelectValue(turnoElLocal, data.Turno || data.turno || '');
                        var destinoEl = document.getElementById('destino'); if(destinoEl) setSelectValue(destinoEl, data.Destino || data.destino || '','destino');
                        var rucEl = document.getElementById('ruc'); if(rucEl) setSelectValue(rucEl, data.RUC || data.ruc || '','ruc');
                        var direccionEl = document.getElementById('direccion'); if(direccionEl) direccionEl.value = data.Direccion || data.direccion || '';
                        var despachadorEl = document.getElementById('despachador'); if(despachadorEl) despachadorEl.value = data.Despachador || data.despachador || '';
                        var choferEl = document.getElementById('chofer'); if(choferEl) setSelectValue(choferEl, data.Chofer || data.chofer || '', 'chofer');
                        var breveteEl = document.getElementById('brevete'); if(breveteEl) setSelectValue(breveteEl, data.Licencia || data.LicenciaChofer || data.brevete || data.Brevete || '','brevete');
                        var transportistaEl = document.getElementById('transportista'); if(transportistaEl) setSelectValue(transportistaEl, data.Transportista || data.transportista || '', 'transportista');
                        var rucTransportistaEl = document.getElementById('ruc_transportista'); if(rucTransportistaEl) safeSetChoiceValue(rucTransportistaEl, data.RUC_Transportista || data.ruc_transportista || '');
                        var placaEl = document.getElementById('placa_tracto'); if(placaEl) setSelectValue(placaEl, data.Placa_Tracto || data.Placa || data.placa || '', 'placa');
                        var constanciaEl = document.getElementById('constancia_inscripcion'); if(constanciaEl) setSelectValue(constanciaEl, data.Constancia_Inscripcion || data.ConstanciaInscripcion || '');
                        var constanciaCarretaEl = document.getElementById('constancia_inscripcion_2'); if(constanciaCarretaEl) setSelectValue(constanciaCarretaEl, data.Constancia_Inscripcion_2 || data.ConstanciaInscripcion_2 || '');
                        var guiaEl = document.getElementById('guiaRemision'); if(guiaEl) guiaEl.value = data.GR || data.guiaRemision || data.guia || '';
                        // asegurar correlativo tiene el data-id correcto
                        var correlEl = document.getElementById('correlativoVale'); if(correlEl) correlEl.setAttribute('data-id', despachoId);
                    }catch(e){ console.warn('Error al poblar cabecera de despacho:', e); }

                    // Poblar grilla de productos (limpiar existente y reconstruir)
                    try{
                        if(grilla){
                            // vaciar
                            grilla.innerHTML = '';
                            var productos = data.productos || data.Productos || data.productosDespacho || [];
                            productos.forEach(function(p, idx){
                                var row = grilla.insertRow();
                                var c0 = row.insertCell(0); c0.innerText = idx+1; c0.style.textAlign = 'center';
                                var c1 = row.insertCell(1); c1.innerText = p.codigo || p.Codigo || p.CodigoProducto || ''; c1.style.textAlign = 'center';
                                var c2 = row.insertCell(2); c2.innerText = p.producto || p.Producto || p.descripcion || p.Descripcion || ''; c2.style.textAlign = 'left';
                                var c3 = row.insertCell(3); c3.innerText = p.unidadMedida || p.UnidadMedida || p.unidad || ''; c3.style.textAlign = 'center';
                                var c4 = row.insertCell(4); c4.innerText = p.cantidad || p.Cantidad || ''; c4.style.textAlign = 'center';
                                var c5 = row.insertCell(5); c5.innerText = p.comentarios || p.Comentarios || ''; c5.style.textAlign = 'left';
                                try{
                                    row.setAttribute('data-codigo', p.codigo || p.Codigo || p.CodigoProducto || '');
                                    row.setAttribute('data-producto', p.producto || p.Producto || p.descripcion || p.Descripcion || '');
                                    // intentar mantener el id del producto si viene desde el servidor
                                    try{ row.setAttribute('data-producto-id', p.Id || p.id || p.ProductoId || p.IdProducto || ''); }catch(e){}
                                    row.setAttribute('data-unidadmedida', p.unidadMedida || p.UnidadMedida || p.unidad || '');
                                    row.setAttribute('data-cantidad', p.cantidad || p.Cantidad || '');
                                    row.setAttribute('data-comentarios', p.comentarios || p.Comentarios || '');
                                }catch(e){}
                                row.addEventListener('click', function(){
                                    var idxRow = Array.prototype.indexOf.call(grilla.rows, row);
                                    grilla.querySelectorAll('tr').forEach(function(tr){ tr.classList.remove('table-active'); });
                                    row.classList.add('table-active');
                                    // marcar selección pero NO cargar controles inmediatamente
                                    editIndex = idxRow;
                                    updatePhase = 'selected';
                                    if (btnAgregar && btnAgregar.textContent !== 'Actualizar') {
                                        btnAgregar.textContent = 'Actualizar';
                                        btnAgregar.classList.remove('btn-success');
                                        btnAgregar.classList.add('btn-primary');
                                    }
                                });
                            });
                            // reconstruir select producto para reflejar exclusiones
                            rebuildProductoSelect();
                        }
                    }catch(e){ console.warn('Error al poblar productos del despacho:', e); }
                }).catch(function(err){
                    console.error('Error cargando despacho:', err);
                    mostrarMensajeError('No se pudo cargar el despacho para modificar: ' + (err && err.message ? err.message : 'error'));
                });
        }

        // Agregar/Actualizar producto a grilla
        if(btnAgregar && grilla){
            btnAgregar.addEventListener('click', function(){
                // Si hay una fila seleccionada pero aún no se cargó en los controles,
                // cargarla ahora en vez de exigir que el usuario vuelva a elegir producto.
                if(updatePhase === 'selected' && editIndex !== null){
                    try{ cargarFilaEnControles(editIndex); updatePhase = 'editing'; }catch(e){ console.warn('Error al cargar fila en controles:', e); }
                    return;
                }
                var producto = productoEl; var unidad = unidadMedidaEl; var codigo = codigoEl; var cantidad = document.getElementById('cantidad'); var comentarios = document.getElementById('comentarios');
                if(!producto || !cantidad || !producto.value || !cantidad.value){ mostrarMensajeError('Debe seleccionar un producto y especificar la cantidad.'); return; }
                var productoOpt = producto.options[producto.selectedIndex];
                var productoTxt = productoOpt ? productoOpt.text : '';
                var unidadTxt = '';
                if (productoOpt && productoOpt.getAttribute) {
                    unidadTxt = productoOpt.getAttribute('data-unidadmedida') || (unidad && unidad.options && unidad.options[unidad.selectedIndex] ? unidad.options[unidad.selectedIndex].text : (unidad ? unidad.value : ''));
                } else {
                    unidadTxt = (unidad && unidad.options && unidad.options[unidad.selectedIndex]) ? unidad.options[unidad.selectedIndex].text : (unidad ? unidad.value : '');
                }

                if(editIndex !== null){
                    var row = grilla.rows[editIndex];
                    if(!row){ alert('Fila a actualizar no encontrada.'); limpiarControlesExternos(); return; }
                    row.cells[1].textContent = codigo ? (codigo.value||'') : ''; row.cells[1].style.textAlign = 'center';
                    row.cells[2].textContent = productoTxt; row.cells[2].style.textAlign = 'left';
                    row.cells[3].textContent = unidadTxt; row.cells[3].style.textAlign = 'center';
                    row.cells[4].textContent = cantidad.value; row.cells[4].style.textAlign = 'center';
                    row.cells[5].textContent = comentarios ? (comentarios.value||'') : ''; row.cells[5].style.textAlign = 'left';
                    row.setAttribute('data-codigo', codigo ? (codigo.value||'') : '');
                    row.setAttribute('data-producto', productoTxt);
                    // Guardar id del producto para edición precisa
                    try{ row.setAttribute('data-producto-id', (producto && producto.value) ? String(producto.value) : ''); }catch(e){}
                    row.setAttribute('data-unidadmedida', (productoOpt && productoOpt.getAttribute) ? (productoOpt.getAttribute('data-unidadmedida') || '') : (unidad ? unidad.value : unidadTxt));
                    row.setAttribute('data-cantidad', cantidad.value);
                    row.setAttribute('data-comentarios', comentarios ? (comentarios.value||'') : '');
                    rebuildProductoSelect();
                    limpiarControlesExternos();
                } else {
                    console.log('[despachosExt] Agregando producto a grilla:', {productoTxt, codigo: codigo?.value, cantidad: cantidad?.value});
                    var row = grilla.insertRow();
                    var cell0 = row.insertCell(0); cell0.innerText = grilla.rows.length; cell0.style.textAlign = 'center';
                    var cell1 = row.insertCell(1); cell1.innerText = codigo ? (codigo.value||'') : ''; cell1.style.textAlign = 'center';
                    var cell2 = row.insertCell(2); cell2.innerText = productoTxt; cell2.style.textAlign = 'left';
                    var cell3 = row.insertCell(3); cell3.innerText = unidadTxt; cell3.style.textAlign = 'center';
                    var cell4 = row.insertCell(4); cell4.innerText = cantidad.value; cell4.style.textAlign = 'center';
                    var cell5 = row.insertCell(5); cell5.innerText = comentarios ? (comentarios.value||'') : ''; cell5.style.textAlign = 'left';
                    console.log('[despachosExt] Estilos aplicados - cell2 (Producto):', {
                        textAlign: cell2.style.textAlign,
                        computedAlign: window.getComputedStyle(cell2).textAlign
                    });
                    try{
                        row.setAttribute('data-codigo', codigo ? (codigo.value||'') : '');
                        row.setAttribute('data-producto', productoTxt);
                        // Guardar id del producto para edición precisa
                        try{ row.setAttribute('data-producto-id', (producto && producto.value) ? String(producto.value) : ''); }catch(e){}
                        var unidadAttr = (productoOpt && productoOpt.getAttribute) ? (productoOpt.getAttribute('data-unidadmedida') || '') : '';
                        row.setAttribute('data-unidadmedida', unidadAttr || (unidad ? unidad.value : unidadTxt));
                        row.setAttribute('data-cantidad', cantidad.value);
                        row.setAttribute('data-comentarios', comentarios ? (comentarios.value||'') : '');
                    }catch(e){}
                    row.addEventListener('click', function(){
                        var idx = Array.prototype.indexOf.call(grilla.rows, row);
                        grilla.querySelectorAll('tr').forEach(function(tr){ tr.classList.remove('table-active'); });
                        row.classList.add('table-active');
                        // marcar selección pero NO cargar controles inmediatamente
                        editIndex = idx;
                        updatePhase = 'selected';
                        if (btnAgregar && btnAgregar.textContent !== 'Actualizar') {
                            btnAgregar.textContent = 'Actualizar';
                            btnAgregar.classList.remove('btn-success');
                            btnAgregar.classList.add('btn-primary');
                        }
                    });
                    try{
                        var selVal = producto.value;
                        if(window.choicesInstances && window.choicesInstances['producto']){ try{ window.choicesInstances['producto'].setChoiceByValue(''); }catch(e){} }
                        for(var k=0;k<producto.options.length;k++){
                            if(String(producto.options[k].value) === String(selVal)){
                                producto.remove(k);
                                break;
                            }
                        }
                        if(window.choicesInstances && window.choicesInstances['producto']){ try{ window.choicesInstances['producto'].destroy(); }catch(e){} try{ delete window.choicesInstances['producto']; }catch(e){} }
                        try{ window.choicesInstances = window.choicesInstances || {}; window.choicesInstances['producto'] = new Choices(producto, {
                            searchEnabled: true,
                            searchChoices: true,
                            shouldSort: false,
                            itemSelectText: '',
                            allowHTML: false,
                            renderChoiceLimit: -1,
                            searchResultLimit: 100,
                            position: 'auto',
                            placeholder: true,
                            placeholderValue: (producto.options && producto.options[0] && producto.options[0].text) ? producto.options[0].text : 'Seleccione',
                            noResultsText: 'No se encontraron resultados',
                            removeItemButton: false,
                            duplicateItemsAllowed: false,
                        }); }catch(e){}
                        // Agregar listener de change
                        try{
                            producto.addEventListener('change', function(){
                                setTimeout(function(){
                                    if(typeof updateAllChoicesWrap === 'function'){ updateAllChoicesWrap(); }
                                }, 100);
                            });
                        }catch(e){}
                        // Aplicar wrapping después de recrear Choices
                        setTimeout(function(){
                            if(typeof updateAllChoicesWrap === 'function'){ updateAllChoicesWrap(); }
                        }, 100);
                    }catch(e){}
                    limpiarControlesExternos();
                }
            });
        }

        // Botón Quitar: elimina la fila seleccionada y restaura el producto al selector
        var btnQuitar = document.getElementById('btnQuitar');
        if(btnQuitar){
            btnQuitar.addEventListener('click', function(){
                if(editIndex === null){
                    alert('Seleccione una fila para quitar.');
                    return;
                }
                // eliminar la fila
                grilla.deleteRow(editIndex);
                // reconstruir select de productos (restaurará opciones faltantes)
                rebuildProductoSelect();
                // actualizar numeración
                Array.from(grilla.rows).forEach(function(r,i){ if(r.cells[0]) r.cells[0].textContent = i+1; });
                // limpiar controles
                limpiarControlesExternos();
            });
        }

        // Botón Limpiar: limpiar sólo los campos de producto y restaurar el combo (igual que en Despachos Internos)
        var btnLimpiar = document.getElementById('btnLimpiar');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', function(){
                try{ rebuildProductoSelect(); }catch(e){}
                try{ limpiarControlesExternos(); }catch(e){}
            });
        }

        // Mostrar mensaje (utilidad local similar a despachosinternos)
        // Ahora acepta un segundo parámetro opcional `level` ('error'|'warning'|'success'|'info').
        // Si `level === 'error'` y existe `showFloatingMessage`, delega para mostrar el aviso en rojo.
        function mostrarMensajeError(msg, level){
            try{
                level = level || 'warning';
                // Para errores, aplicar el mismo estilo inline usado en Recepciones Internas
                // (evitar delegar a showFloatingMessage para mantener la misma apariencia)
                if(level === 'error'){
                    try{
                        el.style.color = '#991b1b';
                        el.style.background = '#fee';
                        el.style.border = '2px solid #fca5a5';
                    }catch(e){}
                } else {
                    // Si hay una función global showFloatingMessage (usada en recepciones), reutilizarla para otros niveles
                    if(typeof window !== 'undefined' && typeof window.showFloatingMessage === 'function'){
                        try{ window.showFloatingMessage(msg, level); return; }catch(e){}
                    }
                }

                var el = document.getElementById('mensajeError');
                if(!el) { alert(msg); return; }

                // Ajuste visual según nivel (mantener retrocompatibilidad)
                try{ el.classList.remove('alert-success','alert-danger','alert-warning','alert-info'); }catch(e){}
                if(level === 'success') el.classList.add('alert-success');
                else if(level === 'error') el.classList.add('alert-danger');
                else if(level === 'info') el.classList.add('alert-info');
                else el.classList.add('alert-warning');

                // Si el nivel es error, preferir estilos similares a showFloatingMessage (rojo claro)
                if(level === 'error'){
                    el.style.color = '#7f1d1d';
                    el.style.background = '#fee2e2';
                    el.style.border = '2px solid #fca5a5';
                } else if(level === 'success'){
                    el.style.color = '#064e3b';
                    el.style.background = '#d1fae5';
                    el.style.border = '2px solid #86efac';
                } else if(level === 'info'){
                    el.style.color = '#0c4a6e';
                    el.style.background = '#e0f2fe';
                    el.style.border = '2px solid #7dd3fc';
                } else {
                    // warning (default)
                    el.style.color = '#7c4700';
                    el.style.background = '#fffbe6';
                    el.style.border = '2px solid #ffe082';
                }

                el.textContent = msg || '';
                el.classList.remove('d-none');
                // ocultar después de 5s (coincide con Recepciones Internas)
                clearTimeout(window._msgTimeoutExt);
                window._msgTimeoutExt = setTimeout(function(){ try{ el.classList.add('d-none'); }catch(e){} }, 5000);
            }catch(e){ console.log('mostrarMensajeError', e); }
        }

        // Guardar / Modificar despacho externo
        var btnGuardar = document.getElementById('btnGuardar');
    if(btnGuardar){
            // Flag para prevenir doble clic
            var _guardandoDespExtEnProceso = false;
            
            btnGuardar.addEventListener('click', function(){
                // FIX: Si estamos en la página de edición (despachosexternos/edicion),
                // el listener de despachosexternos_edicion.js es el que maneja el guardado.
                // Este listener solo debe ejecutarse en la página principal (index).
                if (window._esPaginaEdicion) {
                    console.log('[DespachosExternos] Página de edición detectada, ignorando listener principal de guardado');
                    return;
                }
                
                // FIX: Prevenir doble clic
                if (_guardandoDespExtEnProceso) return;
                _guardandoDespExtEnProceso = true;
                
                // Deshabilitar botón inmediatamente
                try {
                    btnGuardar.disabled = true;
                    btnGuardar.textContent = 'Guardando...';
                } catch(e) {}
                
                var correlativoValeEl = document.getElementById('correlativoVale');
                var correlativoVale = correlativoValeEl ? (correlativoValeEl.textContent || '') : '';
                var fecha = document.getElementById('fecha') ? document.getElementById('fecha').value : '';
                var hora = window.horaActual || (new Date().toTimeString().slice(0,5));
                var turno = document.getElementById('turno') ? document.getElementById('turno').value : '';
                var destino = document.getElementById('destino') ? document.getElementById('destino').value : '';
                var ruc = document.getElementById('ruc') ? document.getElementById('ruc').value : '';
                var direccion = document.getElementById('direccion') ? document.getElementById('direccion').value : '';
                var despachador = document.getElementById('despachador') ? document.getElementById('despachador').value : '';
                var chofer = document.getElementById('chofer') ? document.getElementById('chofer').value : '';
                var brevete = document.getElementById('brevete') ? document.getElementById('brevete').value : '';
                var transportista = document.getElementById('transportista') ? document.getElementById('transportista').value : '';
                var ruc_transportista = document.getElementById('ruc_transportista') ? document.getElementById('ruc_transportista').value : '';
                var placa_tracto = document.getElementById('placa_tracto') ? document.getElementById('placa_tracto').value : '';
                var placa_carreta = document.getElementById('placa_carreta') ? document.getElementById('placa_carreta').value : '';
                var constancia_inscripcion = (function(){ var el = document.getElementById('constancia_inscripcion'); if(!el) return ''; var v = el.value; return (v === null || v === undefined) ? '' : String(v); })();
                var constancia_inscripcion_2 = (function(){ var el = document.getElementById('constancia_inscripcion_2'); if(!el) return ''; var v = el.value; return (v === null || v === undefined) ? '' : String(v); })();
                var guiaRemision = document.getElementById('guiaRemision') ? document.getElementById('guiaRemision').value : '';

                // Productos de la grilla
                var productos = [];
                var gr = document.getElementById('grillaDespacho') && document.getElementById('grillaDespacho').querySelector('tbody');
                if(gr){ Array.from(gr.rows).forEach(function(row){
                    productos.push({
                        codigo: row.getAttribute('data-codigo') || (row.cells[1] ? row.cells[1].textContent : ''),
                        producto: row.getAttribute('data-producto') || (row.cells[2] ? row.cells[2].textContent : ''),
                        cantidad: row.getAttribute('data-cantidad') || (row.cells[4] ? row.cells[4].textContent : ''),
                        unidadMedida: row.getAttribute('data-unidadmedida') || (row.cells[3] ? row.cells[3].textContent : ''),
                        comentarios: row.getAttribute('data-comentarios') || (row.cells[5] ? row.cells[5].textContent : '')
                    });
                }); }

                // ===== DIAGNÓSTICO: loguear valores reales de campos antes de validar =====
                try {
                    console.log('[DIAG_guardarExterno] VALORES RECIBIDOS:', {
                        correlativoVale: correlativoVale,
                        fecha: fecha,
                        destino: destino,
                        ruc: ruc,
                        despachador: despachador,
                        chofer: chofer,
                        transportista: transportista,
                        placa_tracto: placa_tracto,
                        placa_carreta: placa_carreta,
                        productosCount: productos.length
                    });
                    // Log detallado del estado de cada select con Choices.js
                    ['destino','ruc','chofer','transportista','placa_tracto','placa_carreta','despachador'].forEach(function(id){
                        var el = document.getElementById(id);
                        if (el) {
                            var nativeVal = el.value;
                            var choicesVal = '';
                            try {
                                if (window.choicesInstances && window.choicesInstances[id]) {
                                    choicesVal = window.choicesInstances[id].getValue(true) || '';
                                }
                            } catch(e){}
                            var isDisabled = el.disabled;
                            if (!nativeVal || (nativeVal === '' && choicesVal !== '')) {
                                console.warn('[DIAG_guardarExterno] CAMPO "#' + id + '" -> native.value="' + nativeVal + '", Choices.value="' + choicesVal + '", disabled=' + isDisabled);
                            }
                        }
                    });
                } catch(e) { console.warn('[DIAG_guardarExterno] error logging', e); }

                // ===== MEJORA: Leer valor real desde Choices.js si el nativo está vacío =====
                function getRealSelectValue(selectId) {
                    var el = document.getElementById(selectId);
                    if (!el) return '';
                    var val = el.value || '';
                    // Si el nativo está vacío pero hay instancia Choices con valor, usarlo
                    if (!val && window.choicesInstances && window.choicesInstances[selectId]) {
                        try {
                            var choicesVal = window.choicesInstances[selectId].getValue(true);
                            if (choicesVal) {
                                val = String(choicesVal);
                                // Sincronizar de vuelta al nativo
                                try { el.value = val; } catch(e){}
                                console.log('[DIAG_guardarExterno] Fallback Choices -> nativo para #' + selectId + ': "' + val + '"');
                            }
                        } catch(e){}
                    }
                    return val;
                }

                // Re-leer campos usando fallback Choices.js
                destino = getRealSelectValue('destino');
                ruc = getRealSelectValue('ruc');
                despachador = getRealSelectValue('despachador');

                // Validación mínima: NOTA: 'despachador' está deshabilitado en UI
                // pero el backend lo auto-asigna desde la sesión. Por eso NO lo validamos aquí.
                var camposFaltantes = [];
                if (!correlativoVale) camposFaltantes.push('N° Vale');
                if (!fecha) camposFaltantes.push('Fecha');
                if (!destino) camposFaltantes.push('Destino');
                if (!ruc) camposFaltantes.push('RUC');
                // despachador NO se valida - está deshabilitado y backend lo auto-asigna
                if (!despachador) {
                    console.log('[DIAG_guardarExterno] despachador vacío - será auto-asignado por backend');
                }
                if (productos.length === 0) camposFaltantes.push('al menos un producto');
                if (camposFaltantes.length > 0) {
                    var msg = 'Por favor complete los siguientes campos requeridos: ' + camposFaltantes.join(', ') + '.';
                    try { console.warn('[DIAG_guardarExterno] VALIDACIÓN FALLÓ - campos:', camposFaltantes.join(', '), '| valores:', {destino: destino, ruc: ruc, correlativoVale: correlativoVale, fecha: fecha}); } catch(e){}
                    mostrarMensajeError(msg);
                    _guardandoDespExtEnProceso = false;
                    if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.textContent = 'Guardar'; }
                    return;
                }

                // Detectar si es modificación
                var despachoId = correlativoValeEl ? correlativoValeEl.getAttribute('data-id') : null;
                if(!despachoId || despachoId === 'null' || despachoId === 'undefined') despachoId = null;

                var payload = {
                    correlativoVale,
                    fecha,
                    hora,
                    turno,
                    destino,
                    ruc,
                    direccion,
                    despachador,
                    chofer,
                    brevete,
                    transportista,
                    ruc_transportista,
                    Placa_Tracto: placa_tracto,
                    Placa_Carreta: placa_carreta,
                    Constancia_Inscripcion: constancia_inscripcion,
                    Constancia_Inscripcion_2: constancia_inscripcion_2,
                    guiaRemision,
                    productos
                };

                var url = (window.APP_URL || window.BASE_URL) + '/despachosexternos/guardar';
                if(despachoId){ payload.Id = despachoId; url = (window.APP_URL || window.BASE_URL) + '/despachosexternos/modificar'; }

                // Diagnostic payload log
                try{ console.log('[despExt] guardar payload', { url: url, payload: payload }); }catch(e){}

                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                }).then(function(response){
                    // Manejar códigos HTTP no-200
                    if(!response.ok){
                        // Intentar leer texto de respuesta para mostrar detalle
                        return response.text().then(function(text){
                            throw new Error('HTTP ' + response.status + ': ' + (text || response.statusText));
                        });
                    }
                    // Intentar parsear JSON, pero manejar caso de body vacío
                    return response.text().then(function(txt){
                        if(!txt) return null;
                        try{ return JSON.parse(txt); }catch(e){ throw new Error('Respuesta no JSON: ' + txt); }
                    });
                }).then(function(res){
                    if(res === null){
                        mostrarMensajeError('Operación completada pero servidor no devolvió JSON. Revise logs del servidor.');
                        return;
                    }
                    if(res && res.success){
                        mostrarMensajeError(despachoId ? 'Despacho modificado correctamente.' : 'Despacho guardado correctamente.');
                        // Resetear flag y texto del botón
                        _guardandoDespExtEnProceso = false;
                        try { btnGuardar.textContent = 'Guardar'; } catch(e) {}
                        try{ bloquearControles(); }catch(e){}
                        try{ if(btnNuevo) btnNuevo.removeAttribute('disabled'); }catch(e){}
                        try{ var btnModificar = document.getElementById('btnModificar'); if(btnModificar) btnModificar.removeAttribute('disabled'); }catch(e){}
                        // Guardar el id devuelto por el backend en el atributo data-id
                        if(res.id && correlativoValeEl){
                            try{ correlativoValeEl.setAttribute('data-id', res.id); }catch(e){}
                        }
                        // Ajustar apariencia de botones después de guardar: Nuevo habilitado, Guardar deshabilitado, Modificar habilitado
                        try{ normalizeButtonLook(btnNuevo,'btn-new'); normalizeButtonLook(btnGuardar,'btn-save'); normalizeButtonLook(document.getElementById('btnModificar'),'btn-edit'); }catch(e){}
                        try{ if(btnGuardar) setButtonEnabled(btnGuardar, false); if(btnNuevo) setButtonEnabled(btnNuevo, true); var bm = document.getElementById('btnModificar'); if(bm) setButtonEnabled(bm, true); }catch(e){}
                    } else {
                        // Manejar error de límite de modificaciones
                        if (res.limite_alcanzado) {
                            mostrarMensajeError('Error: Este vale alcanzó el límite de 3 modificaciones y no puede ser editado.', 'error');
                            bloquearControles();
                            try{ if(btnGuardar) btnGuardar.setAttribute('disabled', 'disabled'); }catch(e){}
                            try{ var btnModificar = document.getElementById('btnModificar'); if(btnModificar) btnModificar.setAttribute('disabled', 'disabled'); }catch(e){}
                        } else {
                            mostrarMensajeError((res && res.message) ? res.message : 'Error al guardar el despacho.', 'error');
                        }
                        // Restaurar botón en caso de error
                        _guardandoDespExtEnProceso = false;
                        if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.textContent = 'Guardar'; }
                    }
                }).catch(function(err){
                    console.log('Error en fetch despachosexternos guarda/modif:', err);
                    mostrarMensajeError('Error al guardar: ' + (err && err.message ? err.message : 'Error de conexión'), 'error');
                    _guardandoDespExtEnProceso = false;
                    if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.textContent = 'Guardar'; }
                });
            });
        }

    }

    // Ejecutar init inmediatamente si el documento ya está listo, o enganchar al evento si todavía está cargando
    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', __despExt_init);
    } else {
        try{ __despExt_init(); }catch(e){ console.error('[despachosExt] init error', e); }
    }

        // --- Botón Imprimir Vale (Despegue de diseño desde despachosinternos.js, versión para externos) ---
        (function(){
            try{
                // Evitar reinstalaciones
                if(window._desp_ext_print_handler_installed) return; window._desp_ext_print_handler_installed = true;

                async function showValePreviewExt(){
                    try{ if(window._despExt_ignoreShow) { console.log('[despachosExt] showValePreviewExt ignored due _despExt_ignoreShow'); return; } }catch(e){}
                    try{ console.log('[despachosExt] showValePreviewExt() called', { usuarioUsername: window.usuarioUsername || '', usuarioActual: window.usuarioActual || '' }); }catch(e){}
                    // Si no hay username expuesto, intentar derivarlo desde el DOM o desde usuarioActual (fallback local)
                    try {
                        if ((!window.usuarioUsername || window.usuarioUsername === '')) {
                            // 1) Intentar extraerlo del menú de usuario en la cabecera (p.ej. '.dropdown-menu .dropdown-item')
                            try {
                                var domCandidate = null;
                                // Buscar primer elemento .dropdown-item dentro del menu de usuario
                                var menuItem = document.querySelector('.dropdown-menu .dropdown-item');
                                if (menuItem && menuItem.textContent && menuItem.textContent.trim()) domCandidate = menuItem.textContent.trim();
                                // Si no hay .dropdown-item, intentar leer el texto del enlace #userDropdown
                                if (!domCandidate) {
                                    var ud = document.getElementById('userDropdown');
                                    if (ud && ud.textContent) domCandidate = ud.textContent.trim();
                                }
                                if (domCandidate) {
                                    // Si el texto contiene espacios (ej. nombre completo), intentar tomar la parte más corta
                                    var parts = domCandidate.split(/\s+/).filter(Boolean);
                                    var candidateUser = parts.length === 1 ? parts[0] : parts[parts.length-1];
                                    candidateUser = candidateUser.toString().trim().toLowerCase();
                                    candidateUser = candidateUser.normalize ? candidateUser.normalize('NFD').replace(/\p{Diacritic}/gu,'') : candidateUser.replace(/\s+/g,'');
                                    candidateUser = candidateUser.replace(/[^a-z0-9_]/g,'');
                                    if (candidateUser) {
                                        window.usuarioUsername = candidateUser;
                                        try{ console.log('[despachosExt] usuarioUsername derived from DOM ->', window.usuarioUsername, domCandidate); }catch(e){}
                                    }
                                }
                            } catch(domErr) {}

                            // 2) Si aún no hay username, intentar derivarlo de window.usuarioActual
                            if ((!window.usuarioUsername || window.usuarioUsername === '') && window.usuarioActual) {
                                var normalizeName = function(s){
                                    try{
                                        s = (s||'').toString().trim().toLowerCase();
                                        s = s.normalize('NFD').replace(/\p{Diacritic}/gu, '');
                                        s = s.replace(/[^a-z0-9_]/g, '');
                                        return s;
                                    }catch(e){ return (s||'').toLowerCase().replace(/\s+/g,''); }
                                };
                                var guessed = normalizeName(window.usuarioActual);
                                if (guessed) {
                                    window.usuarioUsername = guessed;
                                    try{ console.log('[despachosExt] usuarioUsername derived from usuarioActual ->', window.usuarioUsername); }catch(e){}
                                }
                            }
                        }
                    } catch(e) {}
                    try{
                        // Recolectar datos del formulario similar al payload de guardar
                        var correlativoValeEl = document.getElementById('correlativoVale');
                        var correlativo = correlativoValeEl ? (correlativoValeEl.textContent || '') : '';
                        var fecha = document.getElementById('fecha') ? document.getElementById('fecha').value : '';
                        var hora = window.horaActual || (new Date().toTimeString().slice(0,5));
                        var turnoElLocal = document.getElementById('turno'); var turno = getDisplayValue(turnoElLocal);
                        // Helper para obtener texto visible de selects/elementos (soporta select nativo y opciones con Choices.js)
                        function getDisplayValue(el){
                            if(!el) return '';
                            try{
                                if(el.selectedOptions && el.selectedOptions[0] && el.selectedOptions[0].text) {
                                    var txt = el.selectedOptions[0].text;
                                    return txt.toLowerCase().includes('seleccione') ? '' : txt;
                                }
                            }catch(e){}
                            try{
                                // Si es select nativo, usar options[selectedIndex]
                                if(el.tagName && el.tagName.toLowerCase() === 'select' && typeof el.selectedIndex === 'number' && el.options && el.options[el.selectedIndex]){
                                    var txt = el.options[el.selectedIndex].text || '';
                                    return txt.toLowerCase().includes('seleccione') ? '' : txt;
                                }
                            }catch(e){}
                            try{ if(el.value) return el.value; }catch(e){}
                            try{ if(el.textContent) return el.textContent.trim(); }catch(e){}
                            // fallback: buscar elemento cercano con clase .choices__inner o .choice-display
                            try{ var near = el.parentNode && el.parentNode.querySelector && el.parentNode.querySelector('.choices__inner, .choice-display'); if(near) return near.textContent.trim(); }catch(e){}
                            return '';
                        }
                        var destinoElLocal = document.getElementById('destino'); var destino = getDisplayValue(destinoElLocal);
                        var rucElLocal = document.getElementById('ruc'); var ruc = getDisplayValue(rucElLocal);
                        var direccion = document.getElementById('direccion') ? (document.getElementById('direccion').value || '') : '';
                        var despachadorElLocal = document.getElementById('despachador'); var despachador = getDisplayValue(despachadorElLocal);
                        var choferElLocal = document.getElementById('chofer'); var chofer = getDisplayValue(choferElLocal);
                        var breveteElLocal = document.getElementById('brevete'); var brevete = getDisplayValue(breveteElLocal);
                        var transportistaElLocal = document.getElementById('transportista'); var transportista = getDisplayValue(transportistaElLocal);
                        var rucTransportistaElLocal = document.getElementById('ruc_transportista'); var ruc_transportista = getDisplayValue(rucTransportistaElLocal);
                        var placaElLocal = document.getElementById('placa_tracto'); var placa = getDisplayValue(placaElLocal);
                        var placaCarretaElLocal = document.getElementById('placa_carreta'); var placaCarreta = placaCarretaElLocal ? (placaCarretaElLocal.value || '') : '';
                        var constanciaElLocal = document.getElementById('constancia_inscripcion'); var constancia = constanciaElLocal ? (constanciaElLocal.value || '') : '';
                        var constanciaCarretaElLocal = document.getElementById('constancia_inscripcion_2'); var constanciaCarreta = constanciaCarretaElLocal ? (constanciaCarretaElLocal.value || '') : '';
                        var guiaRemision = document.getElementById('guiaRemision') ? (document.getElementById('guiaRemision').value || '') : '';

                    // Productos desde la grilla (usar mismo formato que en reportes para parity)
                    var productosHtml = '';
                    var gr = document.getElementById('grillaDespacho') && document.getElementById('grillaDespacho').querySelector('tbody');
            if(gr){ Array.from(gr.rows).forEach(function(row, idx){
                var codigo = row.getAttribute('data-codigo') || (row.cells[1] ? row.cells[1].textContent : '');
                var producto = row.getAttribute('data-producto') || (row.cells[2] ? row.cells[2].textContent : '');
                var unidad = row.getAttribute('data-unidadmedida') || (row.cells[3] ? row.cells[3].textContent : '');
                var cantidad = row.getAttribute('data-cantidad') || (row.cells[4] ? row.cells[4].textContent : '');
                var comentarios = row.getAttribute('data-comentarios') || (row.cells[5] ? row.cells[5].textContent : '');
                productosHtml += '<tr>' +
                    '<td class="prod-codigo" style="border:0.25pt solid #000; padding:4px; text-align:center; word-break:break-word; white-space:normal; overflow-wrap:break-word;">' + (codigo || '') + '</td>' +
                    '<td class="prod-nombre" style="border:0.25pt solid #000; padding:4px; word-break:break-word; white-space:normal; overflow-wrap:break-word;">' + (producto || '') + '</td>' +
                    '<td class="prod-udm" style="border:0.25pt solid #000; padding:4px; text-align:center; word-break:break-word; white-space:normal; overflow-wrap:break-word;">' + (unidad || '') + '</td>' +
                    '<td class="prod-cantidad" style="border:0.25pt solid #000; padding:4px; text-align:center; word-break:break-word; white-space:normal; overflow-wrap:break-word;">' + (cantidad || '') + '</td>' +
                    '<td class="prod-comentarios" style="border:0.25pt solid #000; padding:4px; word-break:break-word; white-space:normal; overflow-wrap:break-word;">' + (comentarios || '') + '</td>' +
                    '</tr>';
            }); }

                    // Construir HTML idéntico al usado en reportes_despachosexternos.js
                    var fechaFormateada = '';
                    if (fecha && new RegExp('^\\d{4}-\\d{2}-\\d{2}$').test(fecha)) {
                        var partesF = fecha.split('-'); fechaFormateada = partesF[2] + '/' + partesF[1] + '/' + partesF[0];
                    } else { fechaFormateada = fecha || ''; }
                    var horaFormateada = '';
                    if (hora && new RegExp('^\\d{2}:\\d{2}(:\\d{2})?$').test(hora)){
                        var partsH = hora.split(':'); var hnum = parseInt(partsH[0],10); var ampm = hnum >= 12 ? 'PM' : 'AM'; hnum = hnum % 12; if(hnum === 0) hnum = 12; horaFormateada = hnum + ':' + partsH[1] + (partsH[2] ? (':' + partsH[2]) : '') + ' ' + ampm;
                    } else { horaFormateada = hora || ''; }
                    var _pp_baseAssets = (typeof window.BASE_URL !== 'undefined' && window.BASE_URL) ? window.BASE_URL : (window.location.origin + '/swlavoro/public');
                    var logoUrl = _pp_baseAssets + '/img/Logo-Lavoro-1536x442.png';
                    var uname = window.usuarioUsername || '';
                    // Construir URL de la firma directamente (sin pre-validaciÃ³n con HEAD)
                    // El onerror del <img> se encargarÃ¡ de probar variantes si la imagen no carga
                    var firmaUrl = uname ? _pp_baseAssets + '/img/' + uname + '.png' : '';
                                        var correlDisplay = String(correlativo || '').replace(new RegExp('^VDE-?'), '').padStart(6,'0');
                                        var html = `
<div style="font-family:Arial,sans-serif; padding:10px; font-size:0.58rem; line-height:1.08;">
    <div style="display:flex; align-items:center; justify-content:space-between;">
        <img src="${logoUrl}" style="height:26px; max-width:100px;" alt="Logo Lavoro" />
        <span style="font-size:0.7rem; font-weight:bold; border:1px solid #333; padding:1px 6px; border-radius:4px;">VDE-${correlDisplay}</span>
    </div>
    <h3 style="text-align:center; font-weight:bold; margin-top:6px; font-size:0.72rem; margin-bottom:4px;">VALE DE DESPACHOS EXTERNOS</h3>
    <div style="text-align:center; font-size:0.5rem; margin-bottom:28px;">ALMACÉN DE JABAS Y PARIHUELAS - HUACHIPA</div>
    <table class="detalle-header" style="width:100%; margin-top:28px; font-size:0.52rem; border-spacing:0;">
        <tr><td style="font-weight:700; width:22%; font-size:9px;">Fecha:</td><td style="font-size:9px;">${fechaFormateada}</td><td style="font-weight:700; width:12%; font-size:9px;">Hora:</td><td style="font-size:9px;">${horaFormateada}</td></tr>
        <tr><td style="font-weight:700; font-size:9px;">Despachador:</td><td style="font-size:9px;">${(despachador||'')}</td><td style="font-weight:700; font-size:9px;">Turno:</td><td style="font-size:9px;">${(turno||'')}</td></tr>
        <tr><td style="font-weight:700; font-size:9px;">Destino:</td><td style="font-size:9px;">${(destino||'')}</td><td style="font-weight:700; font-size:9px;">RUC:</td><td style="font-size:9px;">${(ruc||'')}</td></tr>
        <tr><td style="font-weight:700; font-size:9px;">Dirección:</td><td style="font-size:9px;" colspan="3">${(direccion||'')}</td></tr>
        <tr><td style="font-weight:700; font-size:9px;">Chofer:</td><td style="font-size:9px;">${(chofer||'')}</td><td style="font-weight:700; font-size:9px;">Brevete:</td><td style="font-size:9px;">${(brevete||'')}</td></tr>
        <tr><td style="font-weight:700; font-size:9px;">Placa Tracto:</td><td style="font-size:9px;">${(placa||'')}</td><td style="font-weight:700; font-size:9px;">Constancia Tracto:</td><td style="font-size:9px;">${(constancia||'')}</td></tr>
        <tr><td style="font-weight:700; font-size:9px;">Placa Carreta:</td><td style="font-size:9px;">${(placaCarreta||'')}</td><td style="font-weight:700; font-size:9px;">Constancia Carreta:</td><td style="font-size:9px;">${(constanciaCarreta||'')}</td></tr>
        <tr><td style="font-weight:700; font-size:9px;">Transportista:</td><td style="font-size:9px;">${(transportista||'')}</td><td style="font-weight:700; font-size:9px;">RUC Transportista:</td><td style="font-size:9px;">${(ruc_transportista||'')}</td></tr>
        <tr><td style="font-weight:700; font-size:9px;">Guía:</td><td style="font-size:9px;" colspan="3">${(guiaRemision||'')}</td></tr>
    </table>
    <table class="productos" style="width:100%; margin-top:8px; border-collapse:collapse; font-size:0.52rem; table-layout:auto;">
        <tr style="font-weight:bold;">
            <th style="border:0.25pt solid #000; padding:3px; text-align:center; background:#f2f2f2 !important; white-space:nowrap; -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact;">CÓDIGO</th>
            <th style="border:0.25pt solid #000; padding:3px; text-align:center; background:#f2f2f2 !important; white-space:nowrap;">PRODUCTO</th>
            <th style="border:0.25pt solid #000; padding:3px; text-align:center; background:#f2f2f2 !important; white-space:nowrap;">UM</th>
            <th style="border:0.25pt solid #000; padding:3px; text-align:center; background:#f2f2f2 !important; white-space:nowrap;">CANTIDAD</th>
            <th style="border:0.25pt solid #000; padding:3px; background:#f2f2f2 !important; white-space:nowrap;">COMENTARIOS</th>
        </tr>
        <tbody>${productosHtml || '<tr><td colspan="6" style="padding:6px;">No hay productos</td></tr>'}</tbody>
    </table>
    <div class="firma-block" style="margin-top:10px; border:0.25pt solid #000; border-radius:8px; padding:10px;">
                        <div style="text-align:center; font-weight:600; font-size:0.68rem; margin-bottom:16px;">FIRMAS DE VALIDACIÓN Y AUTORIZACIÓN</div>
        <table style="width:100%; margin-top:16px; text-align:center; font-size:0.58rem;">
            <tr>
                <td style="width:33%; vertical-align:bottom; box-sizing:border-box; padding-bottom:3px;">
                    <div style="display:flex; flex-direction:column; align-items:center;">
                        ${firmaUrl ? `<img src="${firmaUrl}" onerror="this.onerror=null; var _uname=window.usuarioUsername||'';var _up=_uname.toUpperCase();var _base=window.location.origin;var _paths=[_base+'/swlavoro/public/img/'+_up+'.png',_base+'/public/img/'+_up+'.png',_base+'/swlavoro/public/img/'+_uname+'.png',_base+'/public/img/'+_uname+'.png'];var _i=0;function _try(){if(_i>=_paths.length){this.style.display='none';return;}this.src=_paths[_i];_i++;}this.addEventListener('error',_try,{once:true});_try.call(this);" style="height:117px; margin-bottom:2px; margin-top:0; display:block; max-width:100%;" alt="Firma Despachador" />` : '<div style="height:117px; margin-bottom:2px;"></div>'}
                        <div style="border-top:0.25pt solid #000; width:68%; margin:12px 0 6px 0;"></div>
                        <div style="margin-top:6px; font-size:0.52rem;">DESPACHADOR</div>
                    </div>
                </td>
                <td style="width:33%; vertical-align:bottom; box-sizing:border-box; padding-bottom:3px;">
                    <div style="display:flex; flex-direction:column; align-items:center;">
                        <div style="border-top:0.25pt solid #000; width:68%; margin:12px 0 6px 0;"></div>
                        <div style="margin-top:6px; font-size:0.52rem;">RECEPCIONISTA</div>
                    </div>
                </td>
                <td style="width:33%; vertical-align:bottom; box-sizing:border-box; padding-bottom:3px;">
                    <div style="display:flex; flex-direction:column; align-items:center;">
                        <div style="border-top:0.25pt solid #000; width:68%; margin:12px 0 6px 0;"></div>
                        <div style="margin-top:6px; font-size:0.52rem;">VERIFICADOR</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>`;

                    // Si existe el módulo reutilizable printPreview, delegar la vista previa/impresión ahí y salir.
                    try{
                        if(window.printPreviewDE && typeof window.printPreviewDE.showModalPreview === 'function'){
                            window.printPreviewDE.showModalPreview(html, {
                                title: 'Vale de Despacho Externo',
                                twoUp: (typeof window._despExt_doublePrint === 'undefined') ? false : !!window._despExt_doublePrint,
                                modalSize: 'md'
                            });
                            return; // delegamos, no ejecutar el fallback interno
                        }
                    }catch(e){ /* si falla la delegación, continuamos con el comportamiento anterior */ }

                    // Helper: abrir la vista previa en una nueva ventana (fallback garantizado)
                    // Config: impresión simple (un vale por hoja)
                    try{ if(typeof window._despExt_doublePrint === 'undefined') window._despExt_doublePrint = false; }catch(e){}
                    function openPreviewWindow(printHtml, autoPrint){
                        try{
                            // Construir un Blob con el HTML para evitar abrir una pestaña inicial about:blank
                            // Si la impresión doble por hoja está habilitada, generar layout side-by-side (two-up) con separador punteado
                            function makeTwoUp(innerHtml){
                                // contenedor flexible con dos columnas y separador central punteado
                                // cada copia va dentro de .vale-frame para mostrar el marco
                                return '<div style="width:100%; box-sizing:border-box;">'
                                    + '<div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">'
                                    + '<div style="flex:1; box-sizing:border-box; padding-right:8px;"><div class="vale-frame">' + innerHtml + '</div></div>'
                                    + '<div style="width:12px; display:flex; justify-content:center;">'
                                    + '<div style="border-left:1px dotted #666; height:100%;"></div>'
                                    + '</div>'
                                    + '<div style="flex:1; box-sizing:border-box; padding-left:8px;"><div class="vale-frame">' + innerHtml + '</div></div>'
                                    + '</div>'
                                    + '</div>';
                            }
                            var contentForPrint = printHtml;
                            try{ if(window._despExt_doublePrint){ contentForPrint = makeTwoUp(printHtml); } }catch(e){}
                            var full = '<!doctype html><html><head><meta charset="utf-8"/><title>Vale de Despacho Externo</title>' +
                                '<style>' +
                                'body{font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; margin:0; padding:0;}' +
                                'table{width:100%; border-collapse:collapse; table-layout:auto; } .productos, .productos th, .productos td{ font-size:9px; word-break:break-word; overflow-wrap:break-word; } .productos th, .productos td{border-bottom:1px solid #ddd; padding:2px 4px;}' +
                                ' .productos th:first-child, .productos td:first-child { white-space: nowrap !important; overflow: visible !important; width: auto !important; max-width: none !important; }' +
                                '.productos tr:first-child, .productos th, .productos thead th { background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; background-image: none !important; }' +
                                '.productos tbody td { background:#fff !important; background-color:#fff !important; }' +
                                '@page { size: A4 landscape; margin: 5mm; }' +
                                '@media print {' +
                                '  body * { visibility: hidden; }' +
                                '  #print-only-container, #print-only-container * { visibility: visible; }' +
                                '  #print-only-container { position: absolute; left: 0; top: 0; width: 287mm; height: 200mm; display: flex !important; flex-direction: row; justify-content: space-between; align-items: stretch; padding: 0; margin: 0; box-sizing: border-box; page-break-after: avoid; overflow: hidden; }' +
                                '  #print-only-container::after { content: ""; position: absolute; left: 50%; top: 0; height: 100%; width: 0; border-left: 2px dashed #333; z-index: 1000; transform: translateX(-1px); }' +
                                '  .vale-copy { width: 141.5mm; height: 200mm; padding: 4px; box-sizing: border-box; background: transparent; font-family: Arial, sans-serif; font-size: 8px; overflow: visible; page-break-inside: avoid; flex-shrink: 0; position: relative; display: flex; align-items: stretch; }' +
                                '  .vale-frame { border: 1px solid #222; border-radius: 10px; padding: 6px; box-sizing: border-box; background: #fff; page-break-inside: avoid; height: 100%; display: flex; flex-direction: column; justify-content: space-between; }' +
                                '  .vale-frame .firma-block { margin-top: auto; }' +
                                '}' +
                                '</style>' +
                                '</head><body><div id="print-only-container"><div class="vale-copy">' + contentForPrint + '</div></div></body></html>';
                            var blob = new Blob([full], { type: 'text/html' });
                            var url = URL.createObjectURL(blob);
                            var win = null;
                            try{
                                win = window.open(url, '_blank');
                            }catch(e){ win = null; }
                            if(!win){
                                // último recurso: abrir ventana en blanco y escribir (puede mostrar about:blank)
                                try{
                                    win = window.open('', '_blank');
                                    if(!win){ alert('No se pudo abrir la ventana de impresión. Revisa el bloqueador de popups.'); return null; }
                                    win.document.write(full);
                                    win.document.close();
                                }catch(e){ alert('No se pudo abrir la ventana de impresión. Revisa el bloqueador de popups.'); return null; }
                            }
                            // No almacenamos referencia de ventana para evitar reutilización de pestañas externas
                            // Exponer helper makeTwoUp globalmente
                            try{ window._despExt_makeTwoUp = window._despExt_makeTwoUp || (function(html){ return '<div style="width:100%; box-sizing:border-box;"><div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;"><div style="flex:1; box-sizing:border-box; padding-right:8px;"><div class="vale-frame">'+html+'</div></div><div style="width:12px; display:flex; justify-content:center;"><div style="border-left:1px dotted #666; height:100%;"></div></div><div style="flex:1; box-sizing:border-box; padding-left:8px;"><div class="vale-frame">'+html+'</div></div></div></div>'; }); }catch(e){}
                            try{ win.focus(); }catch(e){}
                            if(autoPrint){
                                setTimeout(function(){
                                    try{
                                        // El documento ya contendrá dos copias cuando _despExt_doublePrint=true,
                                        // por lo que basta con imprimir una vez.
                                        try{
                                            if(window._despExt_printing) { console.log('[despachosExt] print suppressed (already printing)'); }
                                            else { window._despExt_printing = true; try{ win.print(); }catch(e){} }
                                        }catch(e){}
                                        }catch(e){}
                                        try{ win.close(); }catch(e){}
                                        // limpiar flag de printing después de un breve delay
                                        try{ setTimeout(function(){ try{ window._despExt_printing = false; }catch(e){} }, 1200); }catch(e){}
                                }, 300);
                            }
                            return win;
                        }catch(e){ try{ console.warn('[despachosExt] openPreviewWindow fallo', e); }catch(_){} return null; }
                    }
                    // Exponer para pruebas manuales
                    try{ window._despExt_openPreviewWindow = openPreviewWindow; }catch(e){}

                    // Crear modal si no existe
                    if(!document.getElementById('modalValePreviewExt')){
                        var modalHtml = `
                        <div class="modal fade" id="modalValePreviewExt" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-md modal-dialog-centered" style="max-width:620px;">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold" style="font-size:0.9rem;">Vista previa de Vale de Despacho Externo</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" id="valePreviewContentExt" style="background:#fff; padding:8px; font-size:0.75rem;">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        <button type="button" class="btn btn-primary" id="btnValePrintExt"> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align:middle; margin-right:6px;"><path d="M2 7a1 1 0 0 0-1 1v3h3v2h8v-2h3V8a1 1 0 0 0-1-1H2zm11 6H3v-3h10v3zM0 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3h-2V5H2v3H0V5z"/></svg>Imprimir</button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                        var wrapper = document.createElement('div'); wrapper.innerHTML = modalHtml;
                        document.body.appendChild(wrapper.firstElementChild);
                    }

                    var contentEl = document.getElementById('valePreviewContentExt'); if(contentEl) contentEl.innerHTML = html;
                    // Si Bootstrap Modal está disponible, usarlo; si no, usar fallback ligero
                    try{
                        var modalEl = document.getElementById('modalValePreviewExt');
                        var modalInstance = null;
                        function cleanupModal(){
                            try{
                                var bd = document.getElementById('modalValePreviewExt_backdrop');
                                if(bd && bd.parentNode) bd.parentNode.removeChild(bd);
                            }catch(e){}
                            try{ if(modalEl){ modalEl.classList.remove('show'); modalEl.style.display = 'none'; modalEl.removeAttribute('aria-modal'); modalEl.setAttribute('aria-hidden','true'); } }catch(e){}
                            try{ document.body.classList.remove('modal-open'); }catch(e){}
                            try{ if(typeof bootstrap !== 'undefined' && bootstrap && typeof bootstrap.Modal === 'function'){ var inst = bootstrap.Modal.getInstance(modalEl); if(inst) inst.dispose(); } }catch(e){}
                            try{ window._despExt_previewOpen = false; }catch(e){}
                            try{ window._despExt_ignoreShow = true; setTimeout(function(){ try{ window._despExt_ignoreShow = false; }catch(e){} }, 800); }catch(e){}
                        }

                        if(typeof bootstrap !== 'undefined' && bootstrap && typeof bootstrap.Modal === 'function'){
                            try{
                                modalInstance = new bootstrap.Modal(modalEl);
                                modalInstance.show();
                                try{ window._despExt_previewOpen = true; }catch(e){}
                                try{ modalEl.addEventListener('hidden.bs.modal', function(){ cleanupModal(); }); }catch(e){}
                                // Asegurar que los botones de cierre usen la instancia de Bootstrap para cerrar correctamente
                                try{
                                    if(modalInstance && typeof modalInstance.hide === 'function'){
                                        modalEl.querySelectorAll('[data-bs-dismiss], .btn-close, .modal-footer .btn').forEach(function(b){
                                            try{ b.addEventListener('click', function(ev){ try{ ev.stopPropagation(); }catch(e){} try{ modalInstance.hide(); }catch(e){ cleanupModal(); } }); }catch(e){}
                                        });
                                    }
                                }catch(e){}
                                // Protección adicional para el botón Cancel/Cerrar: detener propagación, forzar hide y bloquear reapertura breve
                                try{
                                    var cancelBtn = modalEl.querySelector('.modal-footer .btn[data-bs-dismiss], .modal-footer .btn-secondary');
                                    if(cancelBtn){
                                        cancelBtn.addEventListener('click', function(evt){
                                            try{ evt.stopPropagation(); evt.preventDefault(); }catch(e){}
                                            try{ if(modalInstance && typeof modalInstance.hide === 'function') modalInstance.hide(); else cleanupModal(); }catch(e){}
                                            try{ window._despExt_ignoreShow = true; setTimeout(function(){ try{ window._despExt_ignoreShow = false; }catch(e){} }, 1000); }catch(e){}
                                        }, true);
                                    }
                                }catch(e){}
                            }catch(be){ console.warn('[despachosExt] bootstrap.Modal threw', be); }
                        } else {
                            // Fallback simple: mostrar modal con estilos inline y crear backdrop
                            if(modalEl){
                                try{ modalEl.classList.add('show'); modalEl.style.display = 'block'; modalEl.style.opacity = '1'; modalEl.style.zIndex = 20000; modalEl.setAttribute('aria-modal','true'); modalEl.removeAttribute('aria-hidden'); document.body.classList.add('modal-open'); try{ window._despExt_previewOpen = true; }catch(e){} }catch(e){}
                                var backdrop = document.getElementById('modalValePreviewExt_backdrop');
                                if(!backdrop){ try{ backdrop = document.createElement('div'); backdrop.className = 'modal-backdrop fade show'; backdrop.id = 'modalValePreviewExt_backdrop'; backdrop.style.zIndex = 19999; document.body.appendChild(backdrop); }catch(e){} }
                                modalEl.querySelectorAll('[data-bs-dismiss], .btn-close').forEach(function(b){ b.addEventListener('click', function(ev){ try{ ev.stopPropagation(); }catch(e){} try{ cleanupModal(); }catch(e){} }); });
                                try{ modalEl.addEventListener('click', function(ev){ if(ev.target === modalEl){ try{ cleanupModal(); }catch(e){} } }); }catch(e){}
                                // Protección específica para el botón Cancel en fallback modal
                                try{
                                    var cancelBtnFb = modalEl.querySelector('.modal-footer .btn[data-bs-dismiss], .modal-footer .btn-secondary');
                                    if(cancelBtnFb){
                                        cancelBtnFb.addEventListener('click', function(evt){
                                            try{ evt.stopPropagation(); evt.preventDefault(); }catch(e){}
                                            try{ cleanupModal(); }catch(e){}
                                            try{ window._despExt_ignoreShow = true; setTimeout(function(){ try{ window._despExt_ignoreShow = false; }catch(e){} }, 1000); }catch(e){}
                                        }, true);
                                    }
                                }catch(e){}
                            }
                        }

                        // Comprobación inmediata: si por algún motivo el modal sigue oculto, abrir fallback en ventana nueva
                        try{
                            var visible = false;
                            if(modalEl){ var cs = window.getComputedStyle(modalEl); if(cs && cs.display !== 'none' && (parseFloat(cs.opacity) > 0 || cs.visibility === 'visible')) visible = true; }
                            // No abrir fallback automático; el usuario debe usar el botón Imprimir si desea abrir la vista previa en nueva ventana.
                        }catch(e){ }
                    }catch(e){ console.error('[despachosExt] error mostrando modal/fallback', e); }

                    // Imprimir desde modal: listener de una sola ejecución para evitar duplicados
                    setTimeout(function(){
                        var btnPrint = document.getElementById('btnValePrintExt');
                        if(btnPrint){
                            // usar option once:true para que se ejecute solo una vez
                            btnPrint.addEventListener('click', function(){
                                if(window._despExt_printing) return;
                                window._despExt_printing = true;
                                var printContents = document.getElementById('valePreviewContentExt').innerHTML;
                                // Limpiar fondos grises de cabeceras que vienen del modal
                                printContents = printContents.replace(/background:#f2f2f2\s*!important;?/gi, 'background:#fff;');
                                // Alinear cabecera COMENTARIOS a la derecha
                                printContents = printContents.replace(/(<th[^>]*>COMENTARIOS<\/th>)/i, function(m){ return m.replace('white-space:nowrap;', 'white-space:nowrap;text-align:center;'); });
                                var pc = document.createElement('div');
                                pc.id = 'print-c';
                                pc.style.cssText = 'display:none;';
                                pc.innerHTML = '<style>@page{size:A4 portrait;margin:5mm;}@media print{body{margin:0;padding:0}body>:not(#print-c){display:none!important}#print-c{display:block!important;width:100%;background:#fff;}}.vale-frame{border:1px solid #222;border-radius:10px;padding:12px;box-sizing:border-box;min-height:100vh;background:#fff;}.vale-frame table{margin-bottom:10px;}.vale-frame table td{padding:6px 8px;}</style><div class="vale-frame">'+printContents+'</div>';
                                document.body.appendChild(pc);
                                var modal = bootstrap.Modal.getInstance(document.getElementById('modalValePreviewExt'));
                                if(modal) modal.hide();
                                setTimeout(function(){ window.print(); setTimeout(function(){ if(pc.parentNode) pc.parentNode.removeChild(pc); window._despExt_printing = false; },500); }, 400);
                            }, { once: true });
                        }
                    }, 150);
                    // Exponer función para pruebas desde la consola (también se expone fuera de la ejecución)
                    try{ window._despExt_showPreview = showValePreviewExt; console.log && console.log('[despachosExt] showValePreviewExt exposed as window._despExt_showPreview (inner)'); }catch(e){}
                    }catch(err){
                        try{ console.error('[despachosExt] showValePreviewExt ERROR', err, err && err.stack); }catch(e){}
                        // No abrir fallback automático en caso de error; dejar que el usuario use el botón Imprimir si desea abrir la vista previa en nueva ventana.
                    }
                }

                // Exponer la función aunque no se haya invocado aún (para llamadas manuales desde consola)
                try{ window._despExt_showPreview = showValePreviewExt; console.log && console.log('[despachosExt] showValePreviewExt assigned to window._despExt_showPreview'); }catch(e){}

                // Attach handler to button if present, otherwise use delegated listener
                function attachToButton(btn){ if(!btn) return; try{ console.log('[despachosExt] attachToButton: attaching listener to', btn); }catch(e){}; btn.addEventListener('click', function(e){ try{ if(window._despExt_previewOpen || window._despExt_ignoreShow){ console.log('[despachosExt] preview already open or ignored, ignoring click'); return; } console.log('[despachosExt] btnImprimir click (bubble) detected'); e.preventDefault(); }catch(ex){} showValePreviewExt(); });
                    // attach a capture-phase listener as a fallback if another handler stops propagation
                    try{ btn.addEventListener('click', function(e){ try{ if(window._despExt_previewOpen || window._despExt_ignoreShow){ console.log('[despachosExt] preview already open (capture) or ignored, ignoring click'); return; } console.log('[despachosExt] btnImprimir click (capture) detected'); e.preventDefault(); }catch(ex){} showValePreviewExt(); }, true); }catch(e){}
                }

                var btnImpr = document.getElementById('btnImprimir');
                if(btnImpr){ attachToButton(btnImpr); }
                else {
                    // Delegated listener: captura clicks incluso si el botón se crea después
                    document.addEventListener('click', function(e){
                        var t = e.target || e.srcElement;
                        try{
                            // Ignorar clicks realizados dentro del modal de preview para evitar re-aperturas
                            var modalElCheck = document.getElementById('modalValePreviewExt');
                            if (modalElCheck && t && (t.closest && t.closest('#modalValePreviewExt'))) return;
                            if(window._despExt_previewOpen || window._despExt_ignoreShow) return;
                            if(t && (t.id === 'btnImprimir' || (t.closest && t.closest('#btnImprimir')))) {
                                console.log('[despachosExt] delegated click detected for #btnImprimir');
                                e.preventDefault(); showValePreviewExt();
                            }
                        }catch(ex){}
                    });
                }

            }catch(e){ console.error('Error preparando vista previa de impresión (externos):', e); }
        })();

})();
