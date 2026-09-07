        // Limpiar estilos de fondo/borde en Despachador
        var despachadorEl = document.getElementById('despachador');
        if(despachadorEl){
            despachadorEl.className = 'form-control';
            despachadorEl.style.background = '';
            despachadorEl.style.border = '';
            despachadorEl.style.boxShadow = '';
        }
// Despachos Externos - versión consolidada (v2)
// Objetivo: reemplazo limpio del JS original. Contiene:
// - Inicialización de Turno por hora
// - Gestión mínima y segura de Choices.js (instancias en window.choicesInstances)
// - Sincronización destino<->ruc, chofer<->brevete, transportista<->ruc
// - Bloqueo/habilitación de controles y botones según flujo
// - Modal de anulación (dinámico, sin template literal multiline inseguro)
// - Operaciones básicas de grilla (agregar/numero/limpiar)

(function(){
    'use strict';

    // Helpers y referencias DOM
    var choicesInstances = window.choicesInstances = window.choicesInstances || {};
    // Las referencias DOM se obtendrán dentro de bindEvents para asegurar que existen
    let selBtnNuevo, selBtnGuardar, selBtnModificar, selBtnImprimir;
    let turnoEl, chkTurno, destinoEl, rucEl, direccionEl, choferEl, breveteEl, transportistaEl, rucTransportistaEl, grillaEl;
    const turnos = window.turnosData || [];

    // Utilidades
    function q(id){ return document.getElementById(id); }

    function safeSetValue(el, val){ if(!el) return; try{ el.value = val; }catch(e){} }

    function horaEnMinutosFromStr(h){
        if(!h) return 0;
        var p = String(h).split(':');
        var hh = parseInt(p[0],10)||0;
        var mm = parseInt(p[1],10)||0;
        return hh*60 + mm;
    }

    function setTurnoByHora(){
        if(!turnoEl || !turnos || !turnos.length) return false;
        var horaActual = window.horaActual || (new Date().toTimeString().slice(0,5));
        var actualMin = horaEnMinutosFromStr(horaActual);
        for(var i=0;i<turnos.length;i++){
            var t = turnos[i];
            var ini = horaEnMinutosFromStr(t.HoraInicio);
            var fin = horaEnMinutosFromStr(t.HoraFin);
            var en = (ini <= fin) ? (actualMin >= ini && actualMin < fin) : (actualMin >= ini || actualMin < fin);
            if(en){
                try{ turnoEl.value = t.Id; }catch(e){}
                if(choicesInstances['turno']){
                    try{ choicesInstances['turno'].setChoiceByValue(t.Id); }catch(e){}
                }
                return true;
            }
        }
        return false;
    }

    // Choices.js init helper (id -> instance key)
    function initChoicesIfNeeded(id){
        if(!id) return null;
        var el = q(id);
        if(!el) return null;
        if(choicesInstances[id]) return choicesInstances[id];
        if(window.Choices){
            try{
                var inst = new Choices(el, { searchEnabled: true, itemSelectText: '' });
                choicesInstances[id] = inst;
                return inst;
            }catch(e){ console.warn('Choices init failed for', id, e); }
        }
        return null;
    }

    // Inicializa algunos selects con Choices si están presentes
    function initChoicesBatch(){
    ['turno','destino','ruc','chofer','brevete','transportista','ruc_transportista','area','subarea','recepcionista','verificador','producto','placa_tracto','constancia_inscripcion','placa_carreta','constancia_inscripcion_2']
        .forEach(function(id){ initChoicesIfNeeded(id); });
    }

    // Sincronizaciones
    function syncDestinoToRuc(){
        if(!destinoEl || !rucEl) return;
        var destId = destinoEl.value;
        if(!destId) return;
        var found = (window.destinosData||[]).find(function(d){ return String(d.Id) === String(destId); });
        if(found){
            // Destruir y recrear Choices destino
            if(window.choicesInstances['destino']){
                window.choicesInstances['destino'].destroy();
                window.choicesInstances['destino'] = new Choices(destinoEl, { searchEnabled: true, itemSelectText: '' });
            }
            destinoEl.value = found.Id;
            window.choicesInstances['destino'].setChoiceByValue(found.Id);
            // RUC
            if(window.choicesInstances['ruc']){
                window.choicesInstances['ruc'].destroy();
                window.choicesInstances['ruc'] = new Choices(rucEl, { searchEnabled: true, itemSelectText: '' });
            }
            rucEl.value = found.RUC || '';
            window.choicesInstances['ruc'].setChoiceByValue(found.RUC||'');
            // Dirección
            safeSetValue(direccionEl, found.Direccion || found.DireccionEntrega || '');
        }
    }

    function syncRucToDestino(){
        if(!rucEl || !destinoEl) return;
        var ruc = rucEl.value;
        if(!ruc) return;
        var found = (window.destinosData||[]).find(function(d){ return String(d.RUC) === String(ruc); });
        if(found){
            if(window.choicesInstances['ruc']){
                window.choicesInstances['ruc'].destroy();
                window.choicesInstances['ruc'] = new Choices(rucEl, { searchEnabled: true, itemSelectText: '' });
            }
            rucEl.value = found.RUC || '';
            window.choicesInstances['ruc'].setChoiceByValue(found.RUC||'');
            if(window.choicesInstances['destino']){
                window.choicesInstances['destino'].destroy();
                window.choicesInstances['destino'] = new Choices(destinoEl, { searchEnabled: true, itemSelectText: '' });
            }
            destinoEl.value = found.Id;
            window.choicesInstances['destino'].setChoiceByValue(found.Id);
            safeSetValue(direccionEl, found.Direccion || found.DireccionEntrega || '');
        }
    }

    function syncChoferToBrevete(){
        if(!choferEl || !breveteEl) return;
        var ch = choferEl.value;
        if(!ch) return;
        var found = (window.choferesData||[]).find(function(c){ return String(c.Id) === String(ch); });
        if(found){
            if(window.choicesInstances['brevete']){
                window.choicesInstances['brevete'].destroy();
                window.choicesInstances['brevete'] = new Choices(breveteEl, { searchEnabled: true, itemSelectText: '' });
            }
            breveteEl.value = found.Brevete || '';
            window.choicesInstances['brevete'].setChoiceByValue(found.Brevete||'');
        }
    }

    function syncBreveteToChofer(){
        if(!breveteEl || !choferEl) return;
        var br = breveteEl.value;
        if(!br) return;
        var found = (window.choferesData||[]).find(function(c){ return String(c.Brevete) === String(br); });
        if(found){
            if(window.choicesInstances['chofer']){
                window.choicesInstances['chofer'].destroy();
                window.choicesInstances['chofer'] = new Choices(choferEl, { searchEnabled: true, itemSelectText: '' });
            }
            choferEl.value = found.Id || '';
            window.choicesInstances['chofer'].setChoiceByValue(found.Id||'');
        }
    }

    function syncTransportistaToRuc(){
        if(!transportistaEl || !rucTransportistaEl) return;
        var id = transportistaEl.value;
        if(!id) return;
        var found = (window.transportistasData||[]).find(function(t){ return String(t.Id) === String(id); });
        if(found){
            if(window.choicesInstances['ruc_transportista']){
                window.choicesInstances['ruc_transportista'].destroy();
                window.choicesInstances['ruc_transportista'] = new Choices(rucTransportistaEl, { searchEnabled: true, itemSelectText: '' });
            }
            rucTransportistaEl.value = found.RUC || '';
            window.choicesInstances['ruc_transportista'].setChoiceByValue(found.RUC||'');
        }
    }

    function syncRucTransportistaToTransportista(){
        if(!rucTransportistaEl || !transportistaEl) return;
        var r = rucTransportistaEl.value;
        if(!r) return;
        var found = (window.transportistasData||[]).find(function(t){ return String(t.RUC) === String(r); });
        if(found){
            if(window.choicesInstances['transportista']){
                window.choicesInstances['transportista'].destroy();
                window.choicesInstances['transportista'] = new Choices(transportistaEl, { searchEnabled: true, itemSelectText: '' });
            }
            transportistaEl.value = found.Id || '';
            window.choicesInstances['transportista'].setChoiceByValue(found.Id||'');
        }
    }

    // Controles de botones/estado
    function disableAllActionButtons(){
        try{
            // Helper: no deshabilitar elementos marcados con la clase 'keep-enabled'
            function disableIf(el){ if(!el) return; try{ if(el.classList && el.classList.contains('keep-enabled')) return; el.setAttribute('disabled','disabled'); }catch(e){} }

            disableIf(selBtnGuardar);
            disableIf(selBtnModificar);
            disableIf(document.getElementById('btnAgregar'));
            disableIf(document.getElementById('btnQuitar'));
            disableIf(document.getElementById('btnAnular'));
            disableIf(document.getElementById('btnLimpiar'));
            // Asegurar que botones globales tipo acción que tengan la clase keep-enabled se mantienen activos
            try{
                var allActionBtns = document.querySelectorAll('.btn-action');
                allActionBtns.forEach(function(b){ if(b.classList && b.classList.contains('keep-enabled')) { try{ b.removeAttribute('disabled'); }catch(e){} } });
            }catch(e){}
        }catch(e){}
    }

    // (Definición eliminada: la versión extendida es la única activa)

    // Deshabilitar todos los controles del formulario (selects/inputs) y Choices.js
    function disableAllControls(){
        try{
            var ids = ['turno','destino','ruc','direccion','chofer','brevete','transportista','ruc_transportista','producto','area','subarea','despachador','recepcionista','verificador','fecha','hora','correlativoVale','chkTurno'];
            ids.forEach(function(id){
                var el = q(id);
                if(!el) el = document.getElementById(id);
                if(el) try{ el.setAttribute('disabled','disabled'); }catch(e){}
                if(choicesInstances[id]){
                    try{ choicesInstances[id].disable(); }catch(e){}
                }
            });
            // dirección es un input normal; aseguramos su estado
            if(direccionEl) try{ direccionEl.setAttribute('disabled','disabled'); }catch(e){}
            // Deshabilitar cualquier input/select/textarea dentro de la grilla (por si la plantilla contiene controles)
            if(grillaEl){
                var controls = grillaEl.querySelectorAll('input, select, textarea, [contenteditable]');
                Array.prototype.forEach.call(controls, function(c){
                    try{ c.setAttribute('disabled','disabled'); }catch(e){}
                    // si tiene id y existe una instancia Choices asociada, intentar deshabilitarla también
                    if(c.id && choicesInstances[c.id]){
                        try{ choicesInstances[c.id].disable(); }catch(e){}
                    }
                });
            }
        }catch(e){ console.warn('disableAllControls error', e); }
    }

    // Extender enableForNew para habilitar controles de formulario (excepto turno)
    var _oldEnableForNew = enableForNew;
    function enableForNew(){
        try {
            // Habilitar todos los controles relevantes excepto los que deben quedar deshabilitados
            var idsHabilitar = [
                'fecha', 'destino', 'ruc', 'chofer', 'brevete', 'transportista', 'ruc_transportista',
                'producto', 'area', 'subarea', 'recepcionista', 'verificador', 'hora', 'correlativoVale',
                'cantidad', 'comentarios'
            ];
            idsHabilitar.forEach(function(id) {
                var el = q(id);
                if (!el) el = document.getElementById(id);
                if (el) try { el.removeAttribute('disabled'); } catch (e) {}
                if (choicesInstances[id]) {
                    try { choicesInstances[id].enable(); } catch (e) {}
                }
            });

            // Deshabilitar los controles requeridos
            var idsDeshabilitar = ['turno', 'despachador', 'direccion', 'codigo'];
            idsDeshabilitar.forEach(function(id) {
                var el = q(id);
                if (!el) el = document.getElementById(id);
                if (el) try { el.setAttribute('disabled', 'disabled'); } catch (e) {}
                if (choicesInstances[id]) {
                    try { choicesInstances[id].disable(); } catch (e) {}
                }
            });

            // Habilitar botones de acción
            if (selBtnGuardar) selBtnGuardar.removeAttribute('disabled');
            var btnAgregar = document.getElementById('btnAgregar'); if (btnAgregar) btnAgregar.removeAttribute('disabled');
            var btnQuitar = document.getElementById('btnQuitar'); if (btnQuitar) btnQuitar.removeAttribute('disabled');
            var btnLimpiar = document.getElementById('btnLimpiar'); if (btnLimpiar) btnLimpiar.removeAttribute('disabled');
            var btnAnular = document.getElementById('btnAnular'); if (btnAnular) btnAnular.setAttribute('disabled', 'disabled');

            // Limpiar campos relevantes
            if (q('fecha')) q('fecha').value = new Date().toISOString().split('T')[0];
            if (q('producto')) q('producto').value = '';
            if (q('cantidad')) q('cantidad').value = '';
            if (q('comentarios')) q('comentarios').value = '';
            if (q('subarea')) q('subarea').value = '';
            if (q('recepcionista')) q('recepcionista').value = '';
            if (q('verificador')) q('verificador').value = '';
            if (q('chofer')) q('chofer').value = '';
            if (q('brevete')) q('brevete').value = '';
            if (q('transportista')) q('transportista').value = '';
            if (q('ruc_transportista')) q('ruc_transportista').value = '';
            if (q('area')) q('area').value = '';
            if (q('hora')) q('hora').value = '';
            // Limpiar grilla si existe
            if (grillaEl) {
                var tbody = grillaEl.querySelector('tbody');
                if (tbody) tbody.innerHTML = '';
            }
        } catch (e) { console.warn('enableForNew error', e); }
    }

    // Modal de anulación (crea solo si no existe)
    function crearModalAnulacion(){
        if(document.getElementById('modalAnularDespacho')) return;
        var html = '';
        html += '<div class="modal fade" id="modalAnularDespacho" tabindex="-1" aria-hidden="true">';
        html += '<div class="modal-dialog">';
        html += '<div class="modal-content">';
        html += '<div class="modal-header"><h5 class="modal-title">Confirmar anulación</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>';
        html += '<div class="modal-body">';
        html += '<div class="mb-2">¿Está seguro que desea <b>anular</b> este despacho?</div>';
        html += '<div class="mb-2">Motivo (opcional):</div>';
        html += '<textarea id="motivoAnulacionInput" class="form-control" rows="2" maxlength="200" placeholder="Motivo..."></textarea>';
        html += '<div id="anularErrorMsg" class="text-danger mt-2" style="display:none"></div>';
        html += '</div>';
        html += '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-danger" id="btnConfirmarAnulacion">Anular</button></div>';
        html += '</div></div></div>';
        document.body.insertAdjacentHTML('beforeend', html);
    }

    function mostrarModalAnulacion(despachoId, row){
        crearModalAnulacion();
        var modalEl = document.getElementById('modalAnularDespacho');
        var motivo = modalEl.querySelector('#motivoAnulacionInput');
        var errorMsg = modalEl.querySelector('#anularErrorMsg');
        motivo.value = '';
        errorMsg.style.display = 'none';
        var btn = modalEl.querySelector('#btnConfirmarAnulacion');
        btn.replaceWith(btn.cloneNode(true));
        var nuevo = modalEl.querySelector('#btnConfirmarAnulacion');
        nuevo.addEventListener('click', function(){
            nuevo.setAttribute('disabled','disabled');
            errorMsg.style.display = 'none';
            fetch(window.BASE_URL + '/despachosexternos/anular', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'Id=' + encodeURIComponent(despachoId) + '&motivo=' + encodeURIComponent(motivo.value)
            }).then(function(r){ return r.json(); }).then(function(res){
                nuevo.removeAttribute('disabled');
                if(res && res.success){
                    var bsModal = bootstrap.Modal.getInstance(modalEl);
                    if(bsModal) bsModal.hide();
                    if(row){ row.classList.add('table-danger'); Array.from(row.querySelectorAll('td')).forEach(function(td){ td.style.textDecoration='line-through'; }); }
                    alert('Despacho anulado correctamente.');
                }else{
                    errorMsg.textContent = (res && res.message) ? res.message : 'No se pudo anular.';
                    errorMsg.style.display = 'block';
                }
            }).catch(function(){
                nuevo.removeAttribute('disabled');
                errorMsg.textContent = 'Error de conexión.';
                errorMsg.style.display = 'block';
            });
        });
        var m = new bootstrap.Modal(modalEl); m.show();
    }

    // Grilla - utilidades mínimas
    function agregarFilaProducto(data){
        try{
            var tbody = grillaEl && grillaEl.querySelector('tbody');
            if(!tbody) return;
            var row = tbody.insertRow();
            row.innerHTML = '<td></td>' +
                '<td>' + (data.descripcion||'') + '</td>' +
                '<td>' + (data.unidad||'') + '</td>' +
                '<td>' + (data.codigo||'') + '</td>' +
                '<td>' + (data.cantidad||'') + '</td>' +
                '<td>' + (data.comentarios||'') + '</td>';
            renumerarGrilla();
        }catch(e){ console.warn(e); }
    }

    function renumerarGrilla(){
        try{
            var tbody = grillaEl && grillaEl.querySelector('tbody'); if(!tbody) return;
            Array.from(tbody.rows).forEach(function(r,i){ var c = r.cells[0]; if(c) c.textContent = (i+1); });
        }catch(e){}
    }

    // Recolectar datos del formulario y la grilla
    function collectFormData(){
        var payload = {};
        try{
            payload.Id = q('Id') ? q('Id').value : undefined;
            payload.fecha = q('fecha') ? q('fecha').value : '';
            payload.turno = turnoEl ? turnoEl.value : '';
            payload.destino = destinoEl ? destinoEl.value : '';
            payload.ruc = rucEl ? rucEl.value : '';
            payload.direccion = direccionEl ? direccionEl.value : '';
            payload.chofer = choferEl ? choferEl.value : '';
            payload.brevete = breveteEl ? breveteEl.value : '';
            payload.transportista = transportistaEl ? transportistaEl.value : '';
            payload.ruc_transportista = rucTransportistaEl ? rucTransportistaEl.value : '';
            payload.Placa_Tracto = q('placa_tracto') ? q('placa_tracto').value : '';
            payload.Constancia_Inscripcion = q('constancia_inscripcion') ? q('constancia_inscripcion').value : '';
            payload.Placa_Carreta = q('placa_carreta') ? q('placa_carreta').value : '';
            payload.Constancia_Inscripcion_2 = q('constancia_inscripcion_2') ? q('constancia_inscripcion_2').value : '';
            payload.guiaRemision = q('guiaRemision') ? q('guiaRemision').value : '';
            // recoger items de la grilla
            payload.items = [];
            var tbody = grillaEl && grillaEl.querySelector('tbody');
            if(tbody){
                Array.from(tbody.rows).forEach(function(r){
                    var cells = r.cells;
                    if(!cells) return;
                    payload.items.push({
                        descripcion: cells[1] ? cells[1].textContent.trim() : '',
                        unidad: cells[2] ? cells[2].textContent.trim() : '',
                        codigo: cells[3] ? cells[3].textContent.trim() : '',
                        cantidad: cells[4] ? cells[4].textContent.trim() : '',
                        comentarios: cells[5] ? cells[5].textContent.trim() : ''
                    });
                });
            }
        }catch(e){ console.warn('collectFormData error', e); }
        return payload;
    }

    // Guardar despacho (nuevo o modificación)
    function guardarDespacho(){
        if(!selBtnGuardar) return;
        selBtnGuardar.setAttribute('disabled','disabled');
        var payload = collectFormData();
        // enviar como application/x-www-form-urlencoded con campo 'data' JSON
        var body = 'data=' + encodeURIComponent(JSON.stringify(payload));
        fetch((window.BASE_URL||'') + '/despachosexternos/guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        }).then(function(r){ return r.json(); }).then(function(res){
            _guardandoDespExtV2EnProceso = false;
            selBtnGuardar.removeAttribute('disabled');
            if(res && res.success){
                // si retorna el id, ponerlo en el formulario
                if(res.id && q('Id')) q('Id').value = res.id;
                disableAllActionButtons();
                alert('Guardado correctamente.');
            }else{
                alert((res && res.message) ? res.message : 'Error al guardar.');
            }
        }).catch(function(err){
            _guardandoDespExtV2EnProceso = false;
            selBtnGuardar.removeAttribute('disabled');
            console.error('guardarDespacho error', err);
            alert('Error de conexión al guardar.');
        });
    }

    // Cargar despacho para editar por id
    function cargarDespachoParaEditar(id){
        if(!id) return;
        // intentar endpoint 'obtener' (asunción): devuelve objeto con los campos y items
        fetch((window.BASE_URL||'') + '/despachosexternos/obtener', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'Id=' + encodeURIComponent(id)
        }).then(function(r){ return r.json(); }).then(function(res){
            if(res && res.success && res.data){
                cargarDespachoEnFormulario(res.data);
                // habilitar botones para modificar
                try{ if(selBtnModificar) selBtnModificar.removeAttribute('disabled'); }catch(e){}
            }else{
                alert((res && res.message) ? res.message : 'No se pudo cargar despacho.');
            }
        }).catch(function(err){ console.error('cargarDespachoParaEditar error', err); alert('Error de conexión.'); });
    }

    function cargarDespachoEnFormulario(data){
        if(!data) return;
        try{
            if(q('Id')) q('Id').value = data.Id || '';
            if(q('fecha')) q('fecha').value = data.Fecha || '';
            if(turnoEl) safeSetValue(turnoEl, data.Turno || ''); if(choicesInstances['turno']) try{ choicesInstances['turno'].setChoiceByValue(data.Turno||''); }catch(e){}
            if(destinoEl) safeSetValue(destinoEl, data.Destino || ''); if(choicesInstances['destino']) try{ choicesInstances['destino'].setChoiceByValue(data.Destino||''); }catch(e){}
            if(rucEl) safeSetValue(rucEl, data.RUC || ''); if(choicesInstances['ruc']) try{ choicesInstances['ruc'].setChoiceByValue(data.RUC||''); }catch(e){}
            if(direccionEl) safeSetValue(direccionEl, data.Direccion || '');
            if(choferEl) safeSetValue(choferEl, data.Chofer || ''); if(choicesInstances['chofer']) try{ choicesInstances['chofer'].setChoiceByValue(data.Chofer||''); }catch(e){}
            if(breveteEl) safeSetValue(breveteEl, data.Brevete || ''); if(choicesInstances['brevete']) try{ choicesInstances['brevete'].setChoiceByValue(data.Brevete||''); }catch(e){}
            if(transportistaEl) safeSetValue(transportistaEl, data.Transportista || ''); if(choicesInstances['transportista']) try{ choicesInstances['transportista'].setChoiceByValue(data.Transportista||''); }catch(e){}
            if(rucTransportistaEl) safeSetValue(rucTransportistaEl, data.RUCTransportista || ''); if(choicesInstances['ruc_transportista']) try{ choicesInstances['ruc_transportista'].setChoiceByValue(data.RUCTransportista||''); }catch(e){}
            if(q('placa_tracto')) safeSetValue(q('placa_tracto'), data.Placa_Tracto || ''); if(choicesInstances['placa_tracto']) try{ choicesInstances['placa_tracto'].setChoiceByValue(data.Placa_Tracto||''); }catch(e){}
            if(q('constancia_inscripcion')) safeSetValue(q('constancia_inscripcion'), data.Constancia_Inscripcion || ''); if(choicesInstances['constancia_inscripcion']) try{ choicesInstances['constancia_inscripcion'].setChoiceByValue(data.Constancia_Inscripcion||''); }catch(e){}
            if(q('placa_carreta')) safeSetValue(q('placa_carreta'), data.Placa_Carreta || ''); if(choicesInstances['placa_carreta']) try{ choicesInstances['placa_carreta'].setChoiceByValue(data.Placa_Carreta||''); }catch(e){}
            if(q('constancia_inscripcion_2')) safeSetValue(q('constancia_inscripcion_2'), data.Constancia_Inscripcion_2 || ''); if(choicesInstances['constancia_inscripcion_2']) try{ choicesInstances['constancia_inscripcion_2'].setChoiceByValue(data.Constancia_Inscripcion_2||''); }catch(e){}
            if(q('guiaRemision')) safeSetValue(q('guiaRemision'), data.GR || '');
            // limpiar y llenar grilla
            var tbody = grillaEl && grillaEl.querySelector('tbody'); if(tbody) tbody.innerHTML = '';
            if(Array.isArray(data.Items)){
                data.Items.forEach(function(it){ agregarFilaProducto({ descripcion: it.Descripcion||it.descripcion, unidad: it.Unidad||it.unidad, codigo: it.Codigo||it.codigo, cantidad: it.Cantidad||it.cantidad, comentarios: it.Comentarios||it.comentarios }); });
            }
            renumerarGrilla();
            // habilitar edición
            enableForNew();
        }catch(e){ console.error('cargarDespachoEnFormulario error', e); }
    }

    // Bind eventos de sincronización y estado
    function bindEvents(){

        // Producto: al cambiar, actualizar código y unidad de medida
        var productoEl = document.getElementById('producto');
        var codigoEl = document.getElementById('codigo');
        var unidadMedidaEl = document.getElementById('unidadMedida');
        if(productoEl){
            productoEl.addEventListener('change', function(){
                var selected = productoEl.options[productoEl.selectedIndex];
                if(!selected || !selected.value){
                    if(codigoEl) codigoEl.value = '';
                    if(unidadMedidaEl) unidadMedidaEl.value = '';
                    return;
                }
                var productosData = window.productosData || [];
                var prod = productosData.find(function(p){ return String(p.Id) === String(selected.value); });
                if(prod){
                    if(codigoEl) codigoEl.value = prod.Codigo || '';
                    if(unidadMedidaEl) unidadMedidaEl.value = prod.UnidadMedida || '';
                }else{
                    if(codigoEl) codigoEl.value = '';
                    if(unidadMedidaEl) unidadMedidaEl.value = '';
                }
            });
        }

        // Obtener referencias DOM actualizadas
        selBtnNuevo = document.querySelector('.btn-outline-success');
        selBtnGuardar = document.querySelector('.btn-outline-primary');
        selBtnModificar = document.getElementById('btnModificar');
        selBtnImprimir = document.getElementById('btnImprimir');
        turnoEl = document.getElementById('turno');
        chkTurno = document.getElementById('chkTurno');
        destinoEl = document.getElementById('destino');
        rucEl = document.getElementById('ruc');
        direccionEl = document.getElementById('direccion');
        choferEl = document.getElementById('chofer');
        breveteEl = document.getElementById('brevete');
        transportistaEl = document.getElementById('transportista');
        rucTransportistaEl = document.getElementById('ruc_transportista');
        grillaEl = document.getElementById('grillaDespacho');

        initChoicesBatch();

        if(destinoEl){ destinoEl.addEventListener('change', function(){ syncDestinoToRuc(); }); }
        if(rucEl){ rucEl.addEventListener('change', function(){ syncRucToDestino(); }); }

        if(choferEl){ choferEl.addEventListener('change', function(){ syncChoferToBrevete(); }); }
        if(breveteEl){ breveteEl.addEventListener('change', function(){ syncBreveteToChofer(); }); }

        if(transportistaEl){ transportistaEl.addEventListener('change', function(){ syncTransportistaToRuc(); }); }
        if(rucTransportistaEl){ rucTransportistaEl.addEventListener('change', function(){ syncRucTransportistaToTransportista(); }); }

        if(chkTurno){ chkTurno.addEventListener('change', function(){ if(this.checked){ if(turnoEl) turnoEl.removeAttribute('disabled'); if(choicesInstances['turno']) choicesInstances['turno'].enable(); }else{ if(turnoEl) turnoEl.setAttribute('disabled','disabled'); if(choicesInstances['turno']) choicesInstances['turno'].disable(); } }); }

        if(selBtnNuevo){ selBtnNuevo.addEventListener('click', function(){
            enableForNew();
        }); }

        if(selBtnGuardar){
            // Flag para prevenir doble clic
            var _guardandoDespExtV2EnProceso = false;
            selBtnGuardar.addEventListener('click', function(){
                if (_guardandoDespExtV2EnProceso) return;
                _guardandoDespExtV2EnProceso = true;
                guardarDespacho();
            });
        }
        if(selBtnModificar){ selBtnModificar.addEventListener('click', function(){
            var id = q('Id') ? q('Id').value : null;
            if(!id){ alert('Seleccione un despacho para modificar.'); return; }
            guardarDespacho();
        }); }

        // ejemplo: botón anular en la grilla (delegación)
        if(grillaEl){
            grillaEl.addEventListener('click', function(ev){
                var t = ev.target;
                if(!t) return;
                if(t.matches('.btn-anular-row')){
                    var row = t.closest('tr');
                    var id = row && row.getAttribute('data-id');
                    mostrarModalAnulacion(id, row);
                }
            });
        }

        // Inicializar Choices y forzar selección de turno antes de bloquear
        initChoicesBatch();
        // Selección automática de turno: habilitar temporalmente, setear valor y Choices, luego deshabilitar
        var turnoSet = false;
        var turnoId = null;
        // Mantener turno y Choices habilitados hasta que Choices renderice el valor, luego deshabilitar tras 300ms
        // Lógica idéntica a despachos internos para selección de turno
        if(turnoEl && window.turnosData && window.horaActual){
            var actualMin = horaEnMinutosFromStr(window.horaActual);
            for(var i=0;i<window.turnosData.length;i++){
                var t = window.turnosData[i];
                var ini = horaEnMinutosFromStr(t.HoraInicio);
                var fin = horaEnMinutosFromStr(t.HoraFin);
                var en = (ini <= fin) ? (actualMin >= ini && actualMin < fin) : (actualMin >= ini || actualMin < fin);
                if(en){
                    turnoId = t.Id;
                    turnoEl.value = turnoId;
                    Array.from(turnoEl.options).forEach(function(opt){
                        opt.selected = (String(opt.value) === String(turnoId));
                    });
                    if(window.choicesInstances && window.choicesInstances['turno']){
                        try{ window.choicesInstances['turno'].setChoiceByValue(turnoId); }catch(e){}
                    }
                    break;
                }
            }
        }
        // Deshabilitar después de la selección
        if(turnoEl) turnoEl.setAttribute('disabled','disabled');
        if(window.choicesInstances && window.choicesInstances['turno']){
            try{ window.choicesInstances['turno'].disable(); }catch(e){}
        }
        // Forzar disabled en botones Guardar y Modificar, y en controles principales
        var btnGuardar = document.querySelector('.btn-outline-primary');
        var btnModificar = document.getElementById('btnModificar');
        if(btnGuardar) btnGuardar.setAttribute('disabled','disabled');
        if(btnModificar) btnModificar.setAttribute('disabled','disabled');
        // Mantener habilitado solo el botón Nuevo
        var btnNuevo = document.querySelector('.btn-outline-success');
        if(btnNuevo) btnNuevo.removeAttribute('disabled');
        // Estado inicial: deshabilitar TODOS los controles y botones de acción
        disableAllControls();
        disableAllActionButtons();
    // Forzar estado disabled para botones y turno por si alguna referencia quedó habilitada
    try{ if(selBtnGuardar) selBtnGuardar.setAttribute('disabled','disabled'); }catch(e){}
    try{ if(selBtnModificar) selBtnModificar.setAttribute('disabled','disabled'); }catch(e){}
    try{ if(turnoEl) turnoEl.setAttribute('disabled','disabled'); }catch(e){}
    try{ if(choicesInstances['turno']) choicesInstances['turno'].disable(); }catch(e){}
    // Forzar disable en los inputs específicos que reportaste
    try{ var ids_force = ['codigo','cantidad','comentarios']; ids_force.forEach(function(i){ var el=document.getElementById(i); if(el) el.setAttribute('disabled','disabled'); }); }catch(e){}
        // mantener habilitado solo el botón Nuevo
        if(selBtnNuevo) selBtnNuevo.removeAttribute('disabled');
    }

    // Exponer API útil
    window.despachosExternos = window.despachosExternos || {};
    window.despachosExternos.setTurnoByHora = setTurnoByHora;
    window.despachosExternos.mostrarModalAnulacion = mostrarModalAnulacion;
    window.despachosExternos.agregarFilaProducto = agregarFilaProducto;
    // Exponer nuevas funciones
    window.despachosExternos.guardarDespacho = guardarDespacho;
    window.despachosExternos.cargarDespachoParaEditar = cargarDespachoParaEditar;
    // Exponer utilidades de control de estado para pruebas manuales
    window.despachosExternos.disableAllControls = disableAllControls;
    window.despachosExternos.enableForNew = enableForNew;
    // Diagnóstico puntual para turno (ejecutar desde consola)
    window.despachosExternos.diagnosticoTurno = function(){
        try{
            console.group('diagnosticoTurno');
            console.log('window.turnosData ->', window.turnosData);
            console.log('window.horaActual ->', window.horaActual);
            console.log('turnoEl ->', turnoEl);
            console.log('choicesInstances.turno ->', choicesInstances['turno']);
            var res = false;
            try{ res = setTurnoByHora(); }catch(e){ console.warn('setTurnoByHora error', e); }
            console.log('setTurnoByHora result ->', res);
            console.groupEnd();
            return res;
        }catch(e){ console.warn('diagnosticoTurno internal error', e); return false; }
    };

    // Inicio: toda la inicialización se ejecuta estrictamente cuando el DOM está listo
    if(typeof document !== 'undefined'){
        document.addEventListener('DOMContentLoaded', function(){
            // Re-obtener referencias DOM que pueden ser null antes
            window.choicesInstances = window.choicesInstances || {};
            // Actualizar helpers locales
            turnoEl = document.getElementById('turno');
            // Ejecutar lógica principal
            bindEvents();
        });
    }

})();
