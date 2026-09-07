/**
 * reportes_despachosexternos.js - REPORTE DE DESPACHOS EXTERNOS
 * VERSIÓN PLANA: Muestra todos los registros en una grilla plana
 * con filtros tipo Excel en la cabecera.
 * 
 * Dependencias: jQuery, Bootstrap 5, column-filters.js, printPreview.js
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var grilla = document.getElementById('grillaReporteBody');
        var paginaActual = 1;

        try { window.paginaActual = paginaActual; } catch(e) {}

        // ---- Mostrar mensaje ----
        function mostrarMensaje(mensaje, tipo) {
            tipo = tipo || 'success';
            try {
                var cont = document.getElementById('mensajes-container');
                if (!cont) {
                    cont = document.createElement('div');
                    cont.id = 'mensajes-container';
                    cont.style.cssText = 'position:fixed;top:18px;right:18px;z-index:1060;max-width:420px;';
                    document.body.appendChild(cont);
                }
                var alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-' + tipo + ' alert-dismissible fade show shadow-sm';
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = mensaje + ' <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';
                cont.appendChild(alertDiv);
                setTimeout(function() {
                    try { alertDiv.classList.remove('show'); setTimeout(function() { try { alertDiv.remove(); } catch(e){} }, 300); } catch(e){}
                }, 4500);
            } catch(e) {
                try { alert(mensaje); } catch(ex) {}
            }
        }

        window.mostrarMensaje = mostrarMensaje;

        // ---- Formateo ----
        function formatCodigo(valor) {
            if (valor === 0 || valor === '0') return '0';
            return valor || '';
        }

        function formatFechaDDMMYYYY(raw) {
            try {
                if (!raw) return '';
                var s = String(raw).trim();
                if (/^\d{2}-\d{2}-\d{4}$/.test(s)) return s;
                var m = s.match(/(\d{4})-(\d{2})-(\d{2})/);
                if (m) return m[3] + '-' + m[2] + '-' + m[1];
                return s;
            } catch(e) { return raw; }
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"');
        }

        // ---- Obtener filtros ----
        function obtenerFiltros() {
            var filtros = {};
            document.querySelectorAll('.filtro-grilla').forEach(function(el) {
                var campo = el.getAttribute('data-campo') || el.name || el.id;
                if (!campo) return;
                var val = el.value || '';
                if (val) filtros[campo] = val;
            });
            return filtros;
        }

        // ---- Cargar datos ----
        function cargarDatos(pagina) {
            paginaActual = pagina || 1;
            try { window.paginaActual = paginaActual; } catch(e) {}
            var filtros = obtenerFiltros();

            if (grilla) {
                grilla.innerHTML = '<tr><td colspan="23" class="text-center py-2">Cargando...</td></tr>';
            }

            fetch(window.BASE_URL + '/index.php?url=reportesexternos/obtenerDespachosExternos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pagina: paginaActual, filtros: filtros, filtrosColumna: window._filtrosColumna || {} })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res || !Array.isArray(res.data)) {
                    if (grilla) grilla.innerHTML = '<tr><td colspan="23" class="text-center text-warning">Sin registros</td></tr>';
                    renderPaginacion(1);
                    return;
                }
                window.datosDespachosExternos = res.data;
                var table = document.getElementById('grillaReporte');
                if (table && res.totalRegistros) {
                    table.setAttribute('data-total-registros', res.totalRegistros);
                }
                // Pasar valores únicos del servidor a ColumnFilters
                if (window.ColumnFilters && res.uniqueValues) {
                    window.ColumnFilters.setServerUniqueValues(res.uniqueValues);
                }
                if (res.data.length === 0) {
                    if (grilla) grilla.innerHTML = '<tr><td colspan="23" class="text-center">Sin registros</td></tr>';
                    renderPaginacion(res.totalPaginas || 1);
                    return;
                }
                renderGrilla(res.data);
                renderPaginacion(res.totalPaginas || 1);
            })
            .catch(function(err) {
                console.error('Error cargarDatos Externos', err);
                if (grilla) grilla.innerHTML = '<tr><td colspan="23" class="text-center text-danger">Error al cargar datos</td></tr>';
                renderPaginacion(1);
            });
        }

        try {
            window.cargarDatos = function(pagina) {
                try { return cargarDatos(pagina); } catch(e) { console.warn('window.cargarDatos error', e); }
            };
        } catch(e) {}

        // ---- Render grilla plana ----
        function renderGrilla(datos) {
            if (!grilla) return;
            grilla.innerHTML = '';

            datos.forEach(function(d) {
                var nvale = d.NVale || '';
                var fecha = formatFechaDDMMYYYY(d.Fecha || '');
                var turno = d.Turno || '';
                var destino = d.Destino || '';
                var ruc = d.RUC || '';
                var direccion = d.Direccion || '';
                var despachador = d.Despachador || '';
                var chofer = d.Chofer || '';
                var licencia = d.Brevete || '';
                var transportista = d.Transportista || '';
                var ruc_transportista = d.RUC_Transportista || '';
                var placa_tracto = d.Placa_Tracto || '';
                var placa_carreta = d.Placa_Carreta || '';
                var constancia_insc = d.Constancia_Inscripcion || '';
                var constancia_insc_2 = d.Constancia_Inscripcion_2 || '';
                var gr = d.GR || '';
                var estado = d.Estado || '';

                var estadoHtml = (function() {
                    var ev = String(estado).toUpperCase().trim();
                    var color = (ev === 'ANULADO') ? '#dc3545' : '#198754';
                    return '<span style="color:' + color + '; font-weight:700">' + escapeHtml(ev) + '</span>';
                })();

                var codigoProd = formatCodigo(d.CodigoProducto);
                var nombreProd = d.NombreProducto || '';
                var udm = d.UnidadMedida || '';
                var cantidad = d.Cantidad || '';
                var comentarios = d.ComentariosProducto || '';

                var tr = document.createElement('tr');
                tr.setAttribute('data-id', d.Id || '');
                tr.style.fontSize = '12px';

                tr.innerHTML =
                    '<td>' +
                        '<div class="dropdown">' +
                            '<button type="button" class="btn btn-sm btn-primary action-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Acciones">' +
                                '<i class="bi bi-three-dots-vertical"></i>' +
                            '</button>' +
                            '<ul class="dropdown-menu shadow-sm">' +
                                '<li><a class="dropdown-item btn-editar-despacho" href="javascript:void(0)" data-id="' + escapeHtml(d.Id) + '" title="Ver detalle"><i class="bi bi-eye-fill me-2"></i>Ver detalle</a></li>' +
                                '<li><hr class="dropdown-divider"></li>' +
                                (function() {
                                    var ev2 = String(estado).toUpperCase();
                                    if (ev2 === 'ANULADO') {
                                        return '<li><a class="dropdown-item btn-reactivar-despacho" href="javascript:void(0)" data-id="' + escapeHtml(d.Id) + '" title="Reactivar"><i class="bi bi-arrow-counterclockwise me-2 text-success"></i>Reactivar</a></li>';
                                    } else {
                                        return '<li><a class="dropdown-item btn-anular-despacho" href="javascript:void(0)" data-id="' + escapeHtml(d.Id) + '" title="Anular"><i class="bi bi-x-circle-fill me-2 text-danger"></i>Anular</a></li>';
                                    }
                                })() +
                            '</ul>' +
                        '</div>' +
                    '</td>' +
                    '<td>' + escapeHtml(nvale) + '</td>' +
                    '<td>' + fecha + '</td>' +
                    '<td>' + escapeHtml(turno) + '</td>' +
                    '<td>' + escapeHtml(despachador) + '</td>' +
                    '<td>' + escapeHtml(destino) + '</td>' +
                    '<td>' + escapeHtml(ruc) + '</td>' +
                    '<td>' + escapeHtml(direccion) + '</td>' +
                    '<td>' + escapeHtml(chofer) + '</td>' +
                    '<td>' + escapeHtml(licencia) + '</td>' +
                    '<td>' + escapeHtml(transportista) + '</td>' +
                    '<td>' + escapeHtml(ruc_transportista) + '</td>' +
                    '<td>' + escapeHtml(placa_tracto) + '</td>' +
                    '<td>' + escapeHtml(constancia_insc) + '</td>' +
                    '<td>' + escapeHtml(placa_carreta) + '</td>' +
                    '<td>' + escapeHtml(constancia_insc_2) + '</td>' +
                    '<td>' + escapeHtml(gr) + '</td>' +
                    '<td>' + escapeHtml(codigoProd) + '</td>' +
                    '<td>' + escapeHtml(nombreProd) + '</td>' +
                    '<td>' + escapeHtml(udm) + '</td>' +
                    '<td>' + escapeHtml(cantidad) + '</td>' +
                    '<td>' + escapeHtml(comentarios) + '</td>' +
                    '<td>' + estadoHtml + '</td>';

                grilla.appendChild(tr);
            });

            // Tooltips para celdas truncadas
            try {
                grilla.querySelectorAll('tr').forEach(function(row) {
                    row.querySelectorAll('td').forEach(function(td) {
                        try {
                            var txt = (td.textContent || '').trim();
                            if (txt) td.setAttribute('title', txt);
                        } catch(e) {}
                    });
                });
            } catch(e) {}

            // Refrescar filtros tipo Excel
            setTimeout(function() {
                if (window.ColumnFilters) window.ColumnFilters.refresh();
            }, 50);

            // Re-aplicar visibilidad de columnas después de renderizar
            setTimeout(function() {
                aplicarVisibilidadColumnas();
            }, 30);

            // Delegación de eventos
            if (!grilla._actionHandlerAttached) {
                grilla.addEventListener('click', function(e) {
                    var item = e.target.closest('.dropdown-item');
                    if (!item) return;
                    e.preventDefault();
                    var id = item.getAttribute('data-id');
                    if (!id) return;

                    if (item.classList.contains('btn-editar-despacho')) {
                        e.preventDefault();
                        window.location.href = window.BASE_URL + '/index.php?url=despachosexternos/edicion&autoLoadId=' + id;
                        return;
                    }
                    if (item.classList.contains('btn-anular-despacho')) {
                        confirmarAnulacion(id);
                        return;
                    }
                    if (item.classList.contains('btn-reactivar-despacho')) {
                        confirmarReactivacion(id);
                        return;
                    }
                });
                grilla._actionHandlerAttached = true;
            }
        }

        // ---- Paginación ----
        function renderPaginacion(totalPaginas) {
            totalPaginas = Math.max(1, parseInt(totalPaginas || 1, 10));
            var contenedores = document.querySelectorAll('#paginacionReporte, #paginacionReporteBottom');
            if (contenedores.length === 0) return;

            var html = '';
            html += '<li class="page-item ' + (paginaActual === 1 ? 'disabled' : '') + '"><a class="page-link" href="#" data-pagina="' + (paginaActual - 1) + '"><i class="bi bi-chevron-left"></i> Anterior</a></li>';

            var PAGINAS_VISIBLES = 5;
            var inicio = Math.max(1, paginaActual - Math.floor(PAGINAS_VISIBLES / 2));
            var fin = Math.min(totalPaginas, inicio + PAGINAS_VISIBLES - 1);
            if (fin - inicio + 1 < PAGINAS_VISIBLES) {
                inicio = Math.max(1, fin - PAGINAS_VISIBLES + 1);
            }

            if (inicio > 1) {
                html += '<li class="page-item"><a class="page-link" href="#" data-pagina="1">1</a></li>';
                if (inicio > 2) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }

            for (var i = inicio; i <= fin; i++) {
                html += '<li class="page-item ' + (paginaActual === i ? 'active' : '') + '"><a class="page-link" href="#" data-pagina="' + i + '">' + i + '</a></li>';
            }

            if (fin < totalPaginas) {
                if (fin < totalPaginas - 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
                html += '<li class="page-item"><a class="page-link" href="#" data-pagina="' + totalPaginas + '">' + totalPaginas + '</a></li>';
            }

            html += '<li class="page-item ' + (paginaActual === totalPaginas ? 'disabled' : '') + '"><a class="page-link" href="#" data-pagina="' + (paginaActual + 1) + '">Siguiente <i class="bi bi-chevron-right"></i></a></li>';

            contenedores.forEach(function(container) {
                container.innerHTML = html;
                container.querySelectorAll('a.page-link[data-pagina]').forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        var pagina = parseInt(this.getAttribute('data-pagina'), 10);
                        if (!isNaN(pagina) && pagina >= 1 && pagina <= totalPaginas && pagina !== paginaActual) {
                            cargarDatos(pagina);
                        }
                    });
                });
            });
        }

        // ---- Exportar Excel (vía POST con todos los filtros) ----
        window.exportarExcel = async function() {
            if (window._exportandoExcel) return;
            window._exportandoExcel = true;
            try {
                // Mostrar indicador de carga
                if (typeof window.mostrarOverlayCarga === 'function') {
                    window.mostrarOverlayCarga('Generando archivo Excel...');
                }

                var baseUrl = window.BASE_URL || '';
                var filtrosActuales = obtenerFiltros();
                var exportarTodo = window.hayFiltrosActivos ? window.hayFiltrosActivos() : Object.keys(filtrosActuales).length > 0 || Object.keys(window._filtrosColumna || {}).length > 0;
                var paginaActualExport = window.paginaActual || 1;

                var body = JSON.stringify({
                    filtros: filtrosActuales,
                    filtrosColumna: window._filtrosColumna || {},
                    paginaActual: paginaActualExport,
                    exportarTodo: exportarTodo
                });

                var resp = await fetch(baseUrl + '/index.php?url=reportesexternos/exportarExcelDespachosExternos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: body
                });

                if (!resp.ok) {
                    var errorText = await resp.text();
                    throw new Error('Error del servidor: ' + errorText.substring(0, 200));
                }

                var blob = await resp.blob();
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'DespachosExternos_' + new Date().toISOString().slice(0,19).replace(/[T:]/g,'-') + '.xlsx';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } catch(e) {
                console.error('exportarExcel error', e);
                if (typeof window.ocultarOverlayCarga === 'function') window.ocultarOverlayCarga();
                alert('Error al exportar a Excel: ' + e.message);
            } finally {
                window._exportandoExcel = false;
                if (typeof window.ocultarOverlayCarga === 'function') window.ocultarOverlayCarga();
            }
        };

        // ---- Anulación ----
        function confirmarAnulacion(id) {
            if (!id) return;
            var motivo = prompt('Ingrese el motivo de anulación (opcional):');
            fetch(window.BASE_URL + '/index.php?url=reportesexternos/anularDespachoExterno', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, motivo: motivo || '' })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res && res.success) {
                    mostrarMensaje('Despacho anulado correctamente', 'success');
                    cargarDatos(paginaActual);
                } else {
                    mostrarMensaje('Error: ' + (res.message || 'Error desconocido'), 'danger');
                }
            })
            .catch(function() {
                mostrarMensaje('Error de conexión al anular', 'danger');
            });
        }
        window.confirmarAnulacion = confirmarAnulacion;

        function confirmarReactivacion(id) {
            if (!id) return;
            if (!confirm('¿Está seguro de reactivar este despacho?')) return;
            fetch(window.BASE_URL + '/index.php?url=reportesexternos/reactivarDespachoExterno', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res && res.success) {
                    mostrarMensaje('Despacho reactivado correctamente', 'success');
                    cargarDatos(paginaActual);
                } else {
                    mostrarMensaje('Error: ' + (res.message || 'Error desconocido'), 'danger');
                }
            })
            .catch(function() {
                mostrarMensaje('Error de conexión al reactivar', 'danger');
            });
        }

        // ---- Vista previa ----
        function mostrarVistaPrevia(id) {
            if (!id) return;
            (async function() {
                try {
                    if (typeof window._asegurarPrintPreview === 'function') {
                        await window._asegurarPrintPreview();
                    }
                    let resp = await fetch(window.BASE_URL + '/index.php?url=despachosexternos/getById', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    let json = await resp.json();
                    if (!json || !json.success || !json.data) {
                        mostrarMensaje('Error al obtener datos del vale', 'danger');
                        return;
                    }
                    var d = json.data;
                    var productosArr = d.Productos || d.Detalles || d.productos || [];

                    if (window.printPreviewDE && typeof window.printPreviewDE.showModalPreview === 'function') {
                        // Construir HTML para preview
                        var baseUrl = window.BASE_URL || '';
                        var logoUrl = baseUrl + '/img/Logo-Lavoro-1536x442.png';
                        var correlDisplay = String(d.NVale || '').replace(/^VDE-?/, '').padStart(6, '0');

                        var productosHtml = '';
                        if (Array.isArray(productosArr) && productosArr.length) {
                            productosArr.forEach(function(p) {
                                productosHtml += '<tr>' +
                                    '<td style="border:0.25pt solid #000; padding:2px 4px; text-align:center;">' + escapeHtml(formatCodigo(p.Codigo || '')) + '</td>' +
                                    '<td style="border:0.25pt solid #000; padding:2px 4px;">' + escapeHtml(p.Producto || '') + '</td>' +
                                    '<td style="border:0.25pt solid #000; padding:2px 4px; text-align:center;">' + escapeHtml(p.UnidadMedida || '') + '</td>' +
                                    '<td style="border:0.25pt solid #000; padding:2px 4px; text-align:center;">' + escapeHtml(p.Cantidad || '') + '</td>' +
                                    '<td style="border:0.25pt solid #000; padding:2px 4px;">' + escapeHtml(p.Comentarios || '') + '</td>' +
                                '</tr>';
                            });
                        }

                        var html = '<div style="font-family:Arial,sans-serif; padding:10px; font-size:0.58rem; line-height:1.08;">' +
                            '<div style="text-align:center;">' +
                                '<img src="' + logoUrl + '" style="height:26px; max-width:100px;" />' +
                                '<h3 style="font-weight:bold; margin:6px 0;">VALE DE DESPACHO EXTERNO</h3>' +
                                '<h4 style="margin:4px 0;">VDE-' + correlDisplay + '</h4>' +
                            '</div>' +
                            '<table style="width:100%; margin-top:8px; border-collapse:collapse; font-size:0.52rem;">' +
                                '<tr><td style="font-weight:700;">Destino:</td><td>' + escapeHtml(d.Destino || '') + '</td><td style="font-weight:700;">RUC:</td><td>' + escapeHtml(d.RUC || '') + '</td></tr>' +
                                '<tr><td style="font-weight:700;">Chofer:</td><td>' + escapeHtml(d.Chofer || '') + '</td><td style="font-weight:700;">Placa:</td><td>' + escapeHtml(d.Placa_Tracto || '') + '</td></tr>' +
                            '</table>' +
                            '<table style="width:100%; margin-top:8px; border-collapse:collapse; font-size:0.52rem;">' +
                                '<thead><tr style="font-weight:bold; background:#f2f2f2;">' +
                                    '<th style="border:0.25pt solid #000; padding:2px 4px;">CÓDIGO</th>' +
                                    '<th style="border:0.25pt solid #000; padding:2px 4px;">PRODUCTO</th>' +
                                    '<th style="border:0.25pt solid #000; padding:2px 4px;">U.M.</th>' +
                                    '<th style="border:0.25pt solid #000; padding:2px 4px;">CANTIDAD</th>' +
                                    '<th style="border:0.25pt solid #000; padding:2px 4px;">COMENTARIOS</th>' +
                                '</tr></thead>' +
                                '<tbody>' + (productosHtml || '<tr><td colspan="5" style="padding:6px; text-align:center;">Sin productos</td></tr>') + '</tbody>' +
                            '</table>' +
                        '</div>';

                        window.printPreviewDE.showModalPreview(html, { title: 'Vale de Despacho Externo', twoUp: true, modalSize: 'md' });
                        return;
                    }
                    mostrarMensaje('Módulo de vista previa no disponible', 'warning');
                } catch(e) {
                    console.error('Error vista previa', e);
                    mostrarMensaje('Error al cargar vista previa', 'danger');
                }
            })();
        }

        // ---- SISTEMA DE COLUMNAS VISIBLES (OCULTAR/MOSTRAR) ----
        var columnPreferences = {};    // { "NVale": true, "Fecha": false, ... }
        var columnToggleMenu = null;   // Referencia al DOM del menú

        /**
         * Obtener la definición de todas las columnas filtrables desde el <thead>
         */
        function obtenerDefinicionColumnas() {
            var columns = [];
            var thead = document.querySelector('#grillaReporte thead');
            if (!thead) return columns;
            var headerRow = thead.querySelector('tr');
            if (!headerRow) return columns;
            var ths = headerRow.querySelectorAll('th');
            ths.forEach(function(th, index) {
                if (index === 0) return;
                var field = th.getAttribute('data-field');
                if (!field) return;
                var text = (th.textContent || '').trim().replace(/▼/g, '').replace(/[▼▲]/g, '').trim();
                columns.push({ index: index, field: field, text: text });
            });
            return columns;
        }

        /**
         * Aplicar visibilidad de columnas según columnPreferences
         */
        function aplicarVisibilidadColumnas() {
            var table = document.getElementById('grillaReporte');
            if (!table) return;
            
            var columns = obtenerDefinicionColumnas();
            columns.forEach(function(col) {
                var visible = true;
                if (columnPreferences.hasOwnProperty(col.field)) {
                    visible = columnPreferences[col.field];
                }
                var th = table.querySelectorAll('thead tr th')[col.index];
                if (th) {
                    th.style.display = visible ? '' : 'none';
                }
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    var td = row.querySelectorAll('td')[col.index];
                    if (td) {
                        td.style.display = visible ? '' : 'none';
                    }
                });
            });

            var firstTh = table.querySelector('thead tr th:first-child');
            if (firstTh) firstTh.style.display = '';
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                var firstTd = row.querySelector('td:first-child');
                if (firstTd) firstTd.style.display = '';
            });

            if (window.ColumnFilters) {
                setTimeout(function() {
                    try { window.ColumnFilters.refresh(); } catch(e) {}
                }, 50);
            }
        }

        /**
         * Cargar preferencias desde el servidor
         */
        function cargarPreferenciasColumnas() {
            var baseUrl = window.BASE_URL || '';
            fetch(baseUrl + '/index.php?url=reportes/obtenerPreferenciasColumnas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ report_code: 'despachos_externos' })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.preferences) {
                    var columns = obtenerDefinicionColumnas();
                    columns.forEach(function(col) {
                        if (res.preferences.hasOwnProperty(col.field)) {
                            columnPreferences[col.field] = res.preferences[col.field];
                        } else {
                            columnPreferences[col.field] = true;
                        }
                    });
                    aplicarVisibilidadColumnas();
                    actualizarCheckboxesMenu();
                } else {
                    var columns = obtenerDefinicionColumnas();
                    columns.forEach(function(col) {
                        columnPreferences[col.field] = true;
                    });
                    aplicarVisibilidadColumnas();
                }
            })
            .catch(function(err) {
                console.warn('[ColumnToggle] Error cargando preferencias:', err);
                var columns = obtenerDefinicionColumnas();
                columns.forEach(function(col) {
                    columnPreferences[col.field] = true;
                });
            });
        }

        /**
         * Guardar preferencias en el servidor
         */
        function guardarPreferenciasColumnas() {
            var baseUrl = window.BASE_URL || '';
            fetch(baseUrl + '/index.php?url=reportes/guardarPreferenciasColumnas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    report_code: 'despachos_externos',
                    preferences: columnPreferences
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    console.log('[ColumnToggle] Preferencias guardadas');
                }
            })
            .catch(function(err) {
                console.warn('[ColumnToggle] Error guardando preferencias:', err);
            });
        }

        function actualizarCheckboxesMenu() {
            if (!columnToggleMenu) return;
            var cbs = columnToggleMenu.querySelectorAll('input[type="checkbox"]');
            cbs.forEach(function(cb) {
                var field = cb.getAttribute('data-field');
                if (field && columnPreferences.hasOwnProperty(field)) {
                    cb.checked = columnPreferences[field];
                }
            });
        }

        /**
         * Construir el menú de toggle de columnas
         */
        function construirMenuColumnas() {
            var columns = obtenerDefinicionColumnas();
            if (columns.length === 0) return null;

            var menu = document.createElement('div');
            menu.className = 'col-toggle-dropdown';
            menu.style.cssText = 'display:none; position:fixed; z-index:1060; background:white; border:1px solid #ccc; border-radius:6px; box-shadow:0 4px 16px rgba(0,0,0,0.15); padding:8px; min-width:210px; max-width:280px; max-height:400px; font-size:13px;';

            var header = document.createElement('div');
            header.style.cssText = 'font-weight:600; padding:4px 8px 8px; border-bottom:1px solid #eee; margin-bottom:4px; display:flex; align-items:center; justify-content:space-between;';
            header.innerHTML = '<span><i class="bi bi-layout-three-columns me-1"></i> Columnas visibles</span>';
            
            var resetBtn = document.createElement('button');
            resetBtn.type = 'button';
            resetBtn.className = 'btn btn-sm btn-outline-secondary';
            resetBtn.title = 'Restablecer todas';
            resetBtn.style.cssText = 'font-size:11px; padding:1px 6px;';
            resetBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>';
            resetBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var cbs = menu.querySelectorAll('input[type="checkbox"]');
                cbs.forEach(function(cb) { cb.checked = true; });
            });
            header.appendChild(resetBtn);
            menu.appendChild(header);

            var listContainer = document.createElement('div');
            listContainer.style.cssText = 'max-height:280px; overflow-y:auto; margin-bottom:4px;';

            columns.forEach(function(col) {
                var isChecked = columnPreferences[col.field] !== false;
                var label = document.createElement('label');
                label.style.cssText = 'display:flex; align-items:center; gap:6px; padding:3px 8px; cursor:pointer; border-radius:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;';
                label.title = col.text;

                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.setAttribute('data-field', col.field);
                cb.checked = isChecked;
                cb.style.cssText = 'margin:0; flex-shrink:0;';

                var span = document.createElement('span');
                span.style.cssText = 'overflow:hidden; text-overflow:ellipsis; font-size:12px;';
                span.textContent = col.text;

                label.appendChild(cb);
                label.appendChild(span);
                listContainer.appendChild(label);
            });

            menu.appendChild(listContainer);

            var footer = document.createElement('div');
            footer.style.cssText = 'border-top:1px solid #eee; padding:6px 0 2px; display:flex; gap:4px; justify-content:space-between; align-items:center;';

            var countSpan = document.createElement('span');
            countSpan.id = 'col-toggle-count';
            countSpan.style.cssText = 'font-size:11px; color:#888;';
            countSpan.textContent = columns.length + ' columnas';
            footer.appendChild(countSpan);

            var btnWrapper = document.createElement('div');
            btnWrapper.style.cssText = 'display:flex; gap:4px;';

            var cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn btn-sm btn-secondary';
            cancelBtn.style.cssText = 'font-size:11px; padding:2px 10px;';
            cancelBtn.textContent = 'Cancelar';
            cancelBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                cerrarMenuColumnas();
                actualizarCheckboxesMenu();
            });
            btnWrapper.appendChild(cancelBtn);

            var applyBtn = document.createElement('button');
            applyBtn.type = 'button';
            applyBtn.className = 'btn btn-sm btn-primary';
            applyBtn.style.cssText = 'font-size:11px; padding:2px 10px;';
            applyBtn.textContent = 'Aplicar';
            applyBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var cbs = menu.querySelectorAll('input[type="checkbox"]');
                cbs.forEach(function(cb) {
                    var field = cb.getAttribute('data-field');
                    if (field) {
                        columnPreferences[field] = cb.checked;
                    }
                });
                aplicarVisibilidadColumnas();
                guardarPreferenciasColumnas();
                cerrarMenuColumnas();

                var visibles = Object.values(columnPreferences).filter(function(v) { return v; }).length;
                var total = Object.keys(columnPreferences).length;
                try { mostrarMensaje('Visibilidad de columnas actualizada (' + visibles + '/' + total + ' visibles)', 'success'); } catch(e) {}
            });
            btnWrapper.appendChild(applyBtn);
            footer.appendChild(btnWrapper);
            menu.appendChild(footer);

            return menu;
        }

        function posicionarMenuColumna(menu, btn) {
            if (!menu || !btn) return;
            var rect = btn.getBoundingClientRect();
            var menuWidth = menu.offsetWidth || 250;
            var left = rect.left;
            var right = left + menuWidth;
            if (right > window.innerWidth - 10) {
                left = window.innerWidth - menuWidth - 10;
            }
            if (left < 10) left = 10;
            menu.style.left = left + 'px';
            menu.style.top = (rect.bottom + 4) + 'px';
        }

        function cerrarMenuColumnas() {
            if (columnToggleMenu && columnToggleMenu.parentNode) {
                columnToggleMenu.style.display = 'none';
            }
        }

        function initColumnToggles() {
            var btnToggle = document.getElementById('btnToggleColumns');
            if (!btnToggle) return;

            btnToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (columnToggleMenu && columnToggleMenu.parentNode && columnToggleMenu.style.display !== 'none') {
                    cerrarMenuColumnas();
                    return;
                }

                if (window.ColumnFilters) {
                    try { document.querySelectorAll('.col-filter-dropdown').forEach(function(m) { m.style.display = 'none'; }); } catch(ex) {}
                }

                if (!columnToggleMenu || !columnToggleMenu.parentNode) {
                    columnToggleMenu = construirMenuColumnas();
                    if (!columnToggleMenu) return;
                    document.body.appendChild(columnToggleMenu);

                    columnToggleMenu.addEventListener('click', function(ev) {
                        ev.stopPropagation();
                    });
                }

                actualizarCheckboxesMenu();
                posicionarMenuColumna(columnToggleMenu, btnToggle);
                columnToggleMenu.style.display = 'block';

                var visibles = Object.keys(columnPreferences).filter(function(k) { return columnPreferences[k]; }).length;
                var total = Object.keys(columnPreferences).length;
                var countEl = document.getElementById('col-toggle-count');
                if (countEl) countEl.textContent = visibles + '/' + total + ' visibles';
            });

            document.addEventListener('click', function(e) {
                if (columnToggleMenu && columnToggleMenu.parentNode && columnToggleMenu.style.display !== 'none') {
                    if (!e.target.closest('#btnToggleColumns') && !e.target.closest('.col-toggle-dropdown')) {
                        cerrarMenuColumnas();
                    }
                }
            });
        }

        // ---- Convertir filtros de columna (índice -> nombre de campo) ----
        function convertirFiltrosColumna(activeFilters) {
            var result = {};
            if (!activeFilters) return result;
            var ths = document.querySelectorAll('#grillaReporte thead tr th');
            Object.keys(activeFilters).forEach(function(colIdx) {
                var idx = parseInt(colIdx, 10);
                var th = null;
                ths.forEach(function(t) {
                    if (t.cellIndex === idx) {
                        th = t;
                    }
                });
                var field = th ? th.getAttribute('data-field') : null;
                if (field) {
                    result[field] = activeFilters[colIdx];
                }
            });
            return result;
        }

        // ---- Inicialización ----
        function init() {
            injectCompactStyles();
            configurarFiltrosFecha();
            window._filtrosColumna = {};

            setTimeout(function() {
                if (window.ColumnFilters) {
                    window.ColumnFilters.init('#grillaReporte');
                    window.ColumnFilters.onFilterChange(function(activeFilters) {
                        window._filtrosColumna = convertirFiltrosColumna(activeFilters);
                        cargarDatos(1);
                    });
                }
            }, 100);

            // Inicializar sistema de toggle de columnas
            initColumnToggles();

            // Cargar preferencias de columnas desde el servidor
            cargarPreferenciasColumnas();

            cargarSelectoresSiNecesario().then(function() {
                cargarDatos(1);
            }).catch(function() {
                cargarDatos(1);
            });

            // Vincular botón exportar
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('#btnExportExcel');
                if (!btn) return;
                e.preventDefault();
                if (window.exportarExcel) window.exportarExcel();
            });
        }

        function injectCompactStyles() {
            var style = document.createElement('style');
            style.textContent =
                'table#grillaReporte tbody tr td, #grillaReporte tbody td, table#grillaReporte td { padding: 1px 4px !important; font-size: 11px !important; line-height: 1.2 !important; height: auto !important; }' +
                'table#grillaReporte thead tr th, #grillaReporte thead th { padding: 3px 6px !important; font-size: 12px !important; }' +
                '.action-btn { width: 28px !important; height: 28px !important; padding: 2px !important; }' +
                '.action-btn i { font-size: 0.9rem !important; }' +
                '.col-filter-counter { font-size: 11px !important; margin-top: 2px !important; }';
            document.head.appendChild(style);
        }

        function configurarFiltrosFecha() {
            document.querySelectorAll('.filtro-grilla[type="date"]').forEach(function(input) {
                try {
                    var hoy = new Date();
                    var yyyy = hoy.getFullYear();
                    var mm = String(hoy.getMonth() + 1).padStart(2, '0');
                    var dd = String(hoy.getDate()).padStart(2, '0');
                    input.setAttribute('max', yyyy + '-' + mm + '-' + dd);
                } catch(e) {}
            });
        }

        var _selectoresCargados = false;
        function cargarSelectoresSiNecesario() {
            if (_selectoresCargados) return Promise.resolve();
            return fetch(window.BASE_URL + '/index.php?url=reportesexternos/obtenerDatosSelectores', { method: 'GET' })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    if (json && json.success && json.data) {
                        if (Array.isArray(json.data.destinos)) window.destinosData = json.data.destinos;
                        if (Array.isArray(json.data.choferes)) window.choferesData = json.data.choferes;
                        if (Array.isArray(json.data.transportistas)) window.transportistasData = json.data.transportistas;
                        if (Array.isArray(json.data.productos)) window.productosData = json.data.productos;
                        if (Array.isArray(json.data.turnos)) window.turnosData = json.data.turnos;
                        if (Array.isArray(json.data.responsables)) window.responsablesData = json.data.responsables;
                        _selectoresCargados = true;
                    }
                })
                .catch(function(e) { console.warn('Error cargando selectores', e); });
        }

        init();
    });
})();
