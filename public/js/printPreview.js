/* printPreview.js
   Módulo reutilizable para generar vista previa y disparar impresión (two-up) usando iframe o Bootstrap modal.
   API expone window.printPreview { showModalPreview(html, opts), printFromHtml(fullHtml, opts), makeTwoUp(innerHtml, opts) }
   Opciones principales: { twoUp: true|false, title: string, autoPrint: false }
*/
(function(window, document){
    'use strict';
    var EXP_ID = 'printPreviewModule_v1';

    function safeBool(v, def){ if(typeof v === 'undefined') return def; return !!v; }

    function makeTwoUp(innerHtml, opts){
        opts = opts || {};
        // two-up side-by-side layout with dotted separator
        return '<div style="width:100%; box-sizing:border-box; display:flex; gap:12px; align-items:stretch;">'
            + '<div style="flex:1; box-sizing:border-box; padding-right:8px;">'
            + '<div class="vale-frame">' + innerHtml + '</div></div>'
            + '<div style="width:12px; display:flex; justify-content:center;">'
            + '<div style="border-left:1px dotted #666; height:200mm;"></div></div>'
            + '<div style="flex:1; box-sizing:border-box; padding-left:8px;"><div class="vale-frame">' + innerHtml + '</div></div>'
            + '</div>';
    }

    function buildDocument(innerHtml, opts){
        opts = opts || {};
        var title = opts.title || 'Vista previa';
        var twoUp = safeBool(opts.twoUp, true);
        // Normalize/adjust innerHtml to ensure firma block elements don't rely on inline styles
        var normalizedInner = innerHtml || '';
        try{
            var tmp = document.createElement('div'); tmp.innerHTML = normalizedInner;
            // Ajustar imágenes dentro de .firma-block para eliminar estilos inline que tapan la línea
            var imgs = tmp.querySelectorAll('.firma-block img');
            imgs.forEach(function(img){
                // imponer estilos controlados por CSS en el documento (evita inline conflict)
                // usar 42px para que coincida con la impresión doble de despachos internos/externos
                 img.setAttribute('style', 'display:block; margin:0 auto 28px; max-height:360px; z-index:0; position:relative;');
            });
            // Reemplazar o insertar una línea visual (.pp-line) en cada celda de la fila de firmas
                var tds = tmp.querySelectorAll('.firma-block td');
                // Si el bloque de firmas existe, reconstruir su estructura para evitar solapamientos:
                var fb = tmp.querySelector('.firma-block');
                if(fb){
                    try{
                        var oldTds = Array.prototype.slice.call(fb.querySelectorAll('td')) || [];
                        if(oldTds.length >= 1){
                            // extraer imagen si existe en la primera celda
                            var imgsHtml = [];
                            var labels = [];
                            for(var i=0;i<oldTds.length;i++){
                                var cell = oldTds[i];
                                var imgEl = cell.querySelector('img');
                                imgsHtml.push(imgEl ? imgEl.outerHTML : '');
                                // tomar texto de la celda excluyendo underscores y saltos
                                var txt = cell.textContent || '';
                                txt = txt.replace(/_{2,}/g, ''); txt = txt.trim();
                                // si la celda contiene label y breaks, intentar extraer last line
                                var parts = txt.split('\n').map(function(s){ return s.trim(); }).filter(Boolean);
                                var label = parts.length ? parts[parts.length-1] : '';
                                labels.push(label);
                            }
                            // construir nuevo HTML para el bloque de firmas
                            var newHtml = '';
                            // mantener título si existe (primer child div con texto fuerte)
                            var titleEl = fb.querySelector('div');
                            if(titleEl) newHtml += titleEl.outerHTML;
                            newHtml += '<table style="width:100%; margin-top:12px; text-align:center; font-size:0.8rem;"><tr>';
                            for(var j=0;j<3;j++){
                                var im = imgsHtml[j] || '';
                                var lab = labels[j] || (j===0? 'DESPACHADOR': (j===1? 'RECEPCIONISTA':'VERIFICADOR'));
                                newHtml += '<td style="width:33%; vertical-align:bottom;">';
                                if(im) newHtml += '<div style="text-align:center;">' + im + '</div>';
                                // línea y etiqueta (usar <hr> por mayor compatibilidad en impresión)
                                newHtml += '<hr class="pp-line" style="border:none; border-top:0.25pt solid #000; width:80%; margin:12px auto 8px; display:block;" />';
                                newHtml += '<div style="margin-top:6px;">' + escapeHtml(lab) + '</div>';
                                newHtml += '</td>';
                            }
                            newHtml += '</tr></table>';
                            fb.innerHTML = newHtml;
                            // remover bordes del contenedor de firmas en el HTML de impresión
                            try{ fb.style.border = 'none'; fb.style.borderTop = 'none'; }catch(e){}
                        }
                    }catch(e){}
                }
                tds.forEach(function(td){
                try{
                    // eliminar guiones/underscore que previenen una línea consistente
                    td.innerHTML = td.innerHTML.replace(/_{4,}/g, '');
                    // buscar una línea existente (div con height pequeño) y convertirla a pp-line
                    var existingLine = td.querySelector('div');
                    if(existingLine){
                        // reemplazar/normalizar a <hr.pp-line>
                        try{
                                if(existingLine.tagName && existingLine.tagName.toLowerCase() === 'hr'){
                                	existingLine.className = 'pp-line';
                                	existingLine.setAttribute('style','border:none; border-top:0.25pt solid #000; width:80%; margin:12px auto 8px; display:block; position:relative; z-index:1001;');
                            } else {
                                // convertir div a hr
                                var hr = document.createElement('hr');
                                hr.className = 'pp-line';
                                hr.setAttribute('style','border:none; border-top:0.25pt solid #000; width:80%; margin:12px auto 8px; display:block; position:relative; z-index:1001;');
                                existingLine.parentNode.replaceChild(hr, existingLine);
                            }
                        }catch(e){}
                    } else {
                        // insertar antes del texto final (la etiqueta) una línea <hr>
                        var line = document.createElement('hr'); line.className = 'pp-line';
                        line.setAttribute('style', 'border:none; border-top:0.25pt solid #000; width:80%; margin:12px auto 8px; display:block; position:relative; z-index:1001;');
                        // colocar al final del contenido pero antes del texto (para mantener orden: firma, linea, etiqueta)
                        // si el td contiene una imagen, insertar la línea después de la imagen
                        var img = td.querySelector('img');
                        if(img){
                            // insertar un espaciador para garantizar separación visual y evitar solapamiento
                            var spacer = document.createElement('div'); spacer.setAttribute('style', 'display:block; height:36px;');
                            if(img.nextSibling) img.parentNode.insertBefore(spacer, img.nextSibling);
                            else img.parentNode.appendChild(spacer);
                            if(spacer.nextSibling) spacer.parentNode.insertBefore(line, spacer.nextSibling);
                            else spacer.parentNode.appendChild(line);
                        } else { td.insertBefore(line, td.firstChild.nextSibling || null); }
                    }
                }catch(e){}
            });
            // Asegurar padding-top en celdas de firma
            var tds = tmp.querySelectorAll('.firma-block td');
            tds.forEach(function(td){
                // si el td tiene style inline, respetar pero asegurar padding-top mínimo
                try{
                    var existing = td.getAttribute('style') || '';
                    if(!/padding-top/i.test(existing)){
                        td.setAttribute('style', existing + (existing ? ';' : '') + 'padding-top:14px; vertical-align:bottom;');
                    }
                }catch(e){}
            });

            // NOTE: removed previous fallback that forced `border-top` on .firma-block tds
            // because it produced a continuous line under the title in two-up prints.
            // Rely on the inserted <hr class="pp-line"> inside each td instead.
            normalizedInner = tmp.innerHTML;
        }catch(e){ /* si falla DOM parsing, usar el innerHtml original */ }
        var content = twoUp ? makeTwoUp(normalizedInner, opts) : normalizedInner;
        var styles = ''+
            'body{font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#111; margin:0; padding:0;}' +
            '.vale-copy{padding:8px; box-sizing:border-box; width:100%; height:200mm; display:flex; align-items:stretch;}' +
                '.vale-frame{ border:1px solid #222; border-radius:10px; padding:10px; box-sizing:border-box; background:#fff; page-break-inside:avoid; height:200mm; display:flex; flex-direction:column; justify-content:space-between; }' +
                '.vale-frame > * { flex: 1 1 auto; }' +
                '/* Reducir tamaño de fuente para datos del encabezado del vale y la grilla de productos (ajuste menor) */' +
                '.vale-frame .detalle-header, .vale-frame .detalle-header td, .vale-frame .detalle-header th { font-size:9px !important; }' +
                '.vale-frame .detalle-header td { padding:4px 4px !important; }' +
                '.vale-frame .productos, .vale-frame .productos th, .vale-frame .productos td { font-size:9px !important; }' +
            // Ajustes para la zona de firmas: espacio para firmas más grandes
            ' .vale-frame .firma-block { margin-top: auto; padding-top:20px; }' +
            '.vale-frame .firma-block img { display:block; margin:0 auto 4px; max-height:216px; position:relative; z-index:0; }' +
            '.vale-frame .firma-block .firma-img { display:block !important; margin:0 auto 4px !important; max-height:216px !important; position:relative !important; z-index:0 !important; }' +
            // Normalizar la línea de firma: usar un trazo fino (0.25pt) para impresión
            '.vale-frame .firma-block .pp-line { display:block !important; border:0 !important; border-top:0.25pt solid #000 !important; width:80% !important; margin:20px auto 12px !important; height:0 !important; position:relative !important; z-index:9999 !important; }' +
            '.vale-frame .firma-block td { padding-top:20px; vertical-align:bottom; }' +
            'table{width:100%; border-collapse:collapse; table-layout:auto;} .vale-frame .productos, .vale-frame .productos th, .vale-frame .productos td { font-size:9px !important; word-break:break-word; overflow-wrap:break-word; } .productos th, .productos td{border-bottom:1px solid #ddd; padding:2px 4px;}' +
            '.productos tr:first-child, .productos th { background:#f2f2f2 !important; background-color:#f2f2f2 !important; color:#222 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }' +
            '.productos th { white-space: nowrap !important; }' +
            '.productos tbody td { background:#fff !important; background-color:#fff !important; }' +
            '.vale-frame table td{word-break:break-word; white-space:normal; overflow-wrap:break-word;} ' +
            '@page { size: A4 landscape; margin: 5mm; }' +
            '@media print { #pp-print-container::after{ content:""; position:absolute; left:50%; top:0; height:200mm; width:0; border-left:2px dashed #333; transform:translateX(-1px); } }';
        var full = '<!doctype html><html><head><meta charset="utf-8"/><title>' + escapeHtml(title) + '</title><style>' + styles + '</style></head><body>' +
            '<div id="pp-print-container" style="width:100%; height:200mm;">' + content + '</div>' +
            '</body></html>';
        return full;
    }

    function escapeHtml(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function printFromHtml(fullHtml, opts){
        opts = opts || {};
        return new Promise(function(resolve, reject){
            try{
                var iframe = document.createElement('iframe');
                iframe.style.position = 'fixed'; iframe.style.right = '0'; iframe.style.bottom = '0';
                iframe.style.width = '0'; iframe.style.height = '0'; iframe.style.border = '0'; iframe.style.visibility = 'hidden';
                // try srcdoc
                try{ iframe.srcdoc = fullHtml; }
                catch(e){ iframe.src = 'about:blank'; }
                document.body.appendChild(iframe);
                var called = false;
                function cleanup(){ try{ if(iframe && iframe.parentNode) iframe.parentNode.removeChild(iframe); }catch(e){} }
                function doPrint(){
                    if(called) return; called = true;
                    try{ iframe.contentWindow.focus(); iframe.contentWindow.print(); }catch(e){}
                    setTimeout(function(){ try{ cleanup(); }catch(e){} resolve(true); }, 700);
                }
                iframe.onload = function(){
                    try{ setTimeout(doPrint, 60); }catch(e){ setTimeout(doPrint, 200); }
                };
                // fallback timer if onload doesn't fire
                setTimeout(function(){ if(!called){ try{ doPrint(); }catch(e){} } }, 1200);
            }catch(err){ reject(err); }
        });
    }

    // showModalPreview: recibe innerHtml o fullHtml; opts: { title, twoUp, autoPrint, modalId }
    function showModalPreview(innerHtml, opts){
        opts = opts || {};
        var modalId = opts.modalId || 'printPreviewModal';
        var contentId = modalId + '_content';
        var title = opts.title || 'Vista previa';
        // modalSize: one of 'sm','md','lg','xl' (default 'xl')
        var modalSize = opts.modalSize || 'xl';
        var modalSizeClass = (modalSize ? ('modal-' + modalSize) : 'modal-xl');
        // crear modal si no existe
        var modalEl = document.getElementById(modalId);
        if(!modalEl){
            var modalHtml = '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-hidden="true">' +
                '<div class="modal-dialog ' + modalSizeClass + ' modal-dialog-centered"><div class="modal-content">' +
                '<div class="modal-header"><h5 class="modal-title">' + escapeHtml(title) + '</h5>' +
                '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>' +
                '<div class="modal-body" style="min-height:360px; max-height:85vh; overflow:auto;"><div id="' + contentId + '"></div></div>' +
                '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>' +
                '<button type="button" class="btn btn-primary" id="' + modalId + '_print">Imprimir</button></div>' +
                '</div></div></div>';
            var wrap = document.createElement('div'); wrap.innerHTML = modalHtml;
            document.body.appendChild(wrap.firstElementChild);
            modalEl = document.getElementById(modalId);
        }

        var contentEl = document.getElementById(contentId);
        if(contentEl){ contentEl.innerHTML = innerHtml; }

        // print button behavior: attach a single handler stored on the modal element to avoid duplicates
        var printBtn = document.getElementById(modalId + '_print');
        function makeOnPrintClick(){
            return function onPrintClick(){
                var twoUp = safeBool(opts.twoUp, true);
                var inner = contentEl ? contentEl.innerHTML : innerHtml;
                var full = buildDocument(inner, { title: opts.title || title, twoUp: twoUp });
                printFromHtml(full, {}).then(function(){
                    try{ hideModal(); }catch(e){}
                }).catch(function(){
                    try{ hideModal(); }catch(e){}
                });
            };
        }

        try{
            if(printBtn){
                // remove any previous handler attached through this module
                if(modalEl._pp_onPrint && typeof modalEl._pp_onPrint === 'function'){
                    try{ printBtn.removeEventListener('click', modalEl._pp_onPrint); }catch(e){}
                    modalEl._pp_onPrint = null;
                }
                modalEl._pp_onPrint = makeOnPrintClick();
                printBtn.addEventListener('click', modalEl._pp_onPrint);
            }
        }catch(e){ /* ignore attach errors */ }

        // show modal using bootstrap if available
        function hideModal(){
            try{
                if(typeof bootstrap !== 'undefined' && bootstrap && bootstrap.Modal){ 
                    var inst = bootstrap.Modal.getInstance(modalEl);
                    if(inst) inst.hide(); else { modalEl.classList.remove('show'); modalEl.style.display='none'; }
                } else { 
                    modalEl.classList.remove('show'); modalEl.style.display='none'; document.body.classList.remove('modal-open');
                    var bd = document.querySelector('.modal-backdrop'); if(bd && bd.parentNode) bd.parentNode.removeChild(bd);
                }
            }catch(e){}
            // cleanup to avoid accumulating modal elements / listeners
            try{
                if(modalEl._pp_onPrint && printBtn){ try{ printBtn.removeEventListener('click', modalEl._pp_onPrint); }catch(e){} }
                modalEl._pp_onPrint = null;
                setTimeout(function(){ try{ if(modalEl && modalEl.parentNode){ modalEl.parentNode.removeChild(modalEl); } }catch(e){} }, 220);
                setTimeout(function(){ try{ var bd2 = document.querySelector('.modal-backdrop'); if(bd2 && bd2.parentNode) bd2.parentNode.removeChild(bd2); }catch(e){} }, 300);
            }catch(e){}
        }

        if(typeof bootstrap !== 'undefined' && bootstrap && typeof bootstrap.Modal === 'function'){
            try{ var modalInstance = new bootstrap.Modal(modalEl); modalInstance.show(); }catch(e){ modalEl.style.display='block'; modalEl.classList.add('show'); }
        } else {
            // Simple fallback display with backdrop
            modalEl.style.display = 'block'; modalEl.classList.add('show'); document.body.classList.add('modal-open');
            if(!document.querySelector('.modal-backdrop')){
                var bd = document.createElement('div'); bd.className = 'modal-backdrop fade show'; document.body.appendChild(bd);
            }
        }

        // autoPrint: trigger printing immediately
        if(opts.autoPrint){ setTimeout(function(){ try{ if(modalEl._pp_onPrint) modalEl._pp_onPrint(); }catch(e){} }, 200); }

        // Ensure modal is removed from DOM when hidden to prevent accumulation and duplicated listeners
        try{
            modalEl.addEventListener('hidden.bs.modal', function onHidden(){
                try{ modalEl.removeEventListener('hidden.bs.modal', onHidden); }catch(e){}
                try{ if(typeof bootstrap !== 'undefined' && bootstrap && bootstrap.Modal){ var inst2 = bootstrap.Modal.getInstance(modalEl); if(inst2){ try{ inst2.dispose(); }catch(e){} } } }catch(e){}
                try{ if(modalEl && modalEl.parentNode) modalEl.parentNode.removeChild(modalEl); }catch(e){}
                try{ var bd3 = document.querySelector('.modal-backdrop'); if(bd3 && bd3.parentNode) bd3.parentNode.removeChild(bd3); }catch(e){}
            });
        }catch(e){}
    }

    // Export API (renombrado para Despachos Externos: printPreviewDE)
    window.printPreviewDE = window.printPreviewDE || {
        makeTwoUp: makeTwoUp,
        buildDocument: buildDocument,
        printFromHtml: printFromHtml,
        showModalPreview: showModalPreview
    };

})(window, document);
