/**
 * reportes-recepcionesexternas-v4.js - REPORTE DE RECEPCIONES EXTERNAS
 * VERSIÓN PLANA 3 NIVELES: Vale + Guía + Producto en una sola fila
 * con filtros tipo Excel en la cabecera.
 *
 * Dependencias: jQuery, Bootstrap 5, column-filters.js
 * VERSION: 2026-06-09 (con botón acciones)
 */

console.log('[REC.EXT] JS v2.0 - Versión con botón de acciones');

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        console.log('[REC.EXT] DOMContentLoaded - Inicializando...');
        var grilla = document.getElementById('grillaReporteBody');
        var paginaActual = 1;
        var totalPaginas = 1;
        
        // Variables para navegaciÃƒÂ³n por lotes de vales
        var offsetVales = 0;
        var totalLotes = 1;
        var loteActual = 1;
        var LIMITE_VALES = 500;

        try { window.paginaActual = paginaActual; } catch(e) {}

        // ---- Mostrar notificación ----
        function mostrarNotificacion(mensaje, tipo) {
            tipo = tipo || 'success';
            try {
                var container = document.getElementById('mensajes-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'mensajes-container';
                    container.style.cssText = 'position:fixed;top:18px;right:18px;z-index:1060;max-width:420px;';
                    document.body.appendChild(container);
                }
                var alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-' + tipo + ' alert-dismissible fade show shadow-sm';
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = mensaje + ' <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';
                container.appendChild(alertDiv);
                setTimeout(function() {
                    try { alertDiv.classList.remove('show'); setTimeout(function() { try { alertDiv.remove(); } catch(e){} }, 300); } catch(e){}
                }, 4500);
            } catch(e) {
                try { alert(mensaje); } catch(ex) {}
            }
        }

        window.mostrarNotificacion = mostrarNotificacion;

        // ---- Obtener filtros ----
        function obtenerFiltrosActivos() {
            var filtros = {};
            document.querySelectorAll('.filtro-grilla').forEach(function(input) {
                var campo = input.getAttribute('data-campo');
                var valor = input.value.trim();
                if (valor) filtros[campo] = valor;
            });
            return filtros;
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

        // ---- Actualizar UI de navegaciÃƒÂ³n por lotes ----
        function actualizarNavegacionLotes() {
            var loteInfo = document.getElementById('loteInfo');
            var btnAnt = document.getElementById('btnLoteAnterior');
            var btnSig = document.getElementById('btnLoteSiguiente');
            var btnPrimero = document.getElementById('btnLotePrimero');
            var btnUltimo = document.getElementById('btnLoteUltimo');
            var loteDetalle = document.getElementById('loteDetalle');
            if (loteInfo) loteInfo.textContent = loteActual + ' / ' + totalLotes;
            var alInicio = (loteActual <= 1);
            var alFinal = (loteActual >= totalLotes);
            if (btnAnt) btnAnt.disabled = alInicio;
            if (btnSig) btnSig.disabled = alFinal;
            if (btnPrimero) btnPrimero.disabled = alInicio;
            if (btnUltimo) btnUltimo.disabled = alFinal;
            if (loteDetalle) {
                var desde = offsetVales + 1;
                var hasta = offsetVales + LIMITE_VALES;
                loteDetalle.textContent = '(Vale ' + desde + ' - ' + hasta + ')';
            }
        }

        // ---- Cargar datos ----
        function cargarDatos(pagina, nuevoOffset) {
            paginaActual = pagina || 1;
            if (nuevoOffset !== undefined) {
                offsetVales = nuevoOffset;
            }
            var filtros = obtenerFiltrosActivos();

            if (grilla) {
                grilla.innerHTML = '<tr><td colspan="21" class="text-center py-4">' +
                    '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>' +
                    '<p class="mt-2 text-muted">Cargando datos...</p></td></tr>';
            }

            fetch(window.BASE_URL + '/index.php?url=reportes/obtenerRecepcionesExternas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pagina: paginaActual, filtros: filtros, exportar: false, filtrosColumna: window._filtrosColumna || {}, offsetVales: offsetVales })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res && res.success && Array.isArray(res.data)) {
                    window.datosRecepcionesExternas = res.data;
                    totalPaginas = res.totalPaginas || 1;
                    // Actualizar datos de navegaciÃƒÂ³n por lotes
                    totalLotes = res.totalLotes || 1;
                    loteActual = res.loteActual || 1;
                    offsetVales = res.offsetVales || 0;
                    // Pasar valores ÃƒÂºnicos del servidor a ColumnFilters
                    if (window.ColumnFilters && res.uniqueValues) {
                        window.ColumnFilters.setServerUniqueValues(res.uniqueValues);
                    }
                    renderizarGrilla(res.data);
                    renderizarPaginacion();
                    actualizarNavegacionLotes();
                } else {
                    if (grilla) grilla.innerHTML = '<tr><td colspan="21" class="text-center py-4">Error al cargar datos</td></tr>';
                    mostrarNotificacion('Error al cargar datos: ' + (res.error || 'Error desconocido'), 'error');
                }
            })
            .catch(function(err) {
                console.error('Error cargando datos:', err);
                if (grilla) grilla.innerHTML = '<tr><td colspan="21" class="text-center py-4 text-danger">Error de conexión</td></tr>';
                mostrarNotificacion('Error de conexión al cargar datos', 'error');
            });
        }

        // ---- Renderizar grilla plana ----
        function renderizarGrilla(datos) {
            if (!grilla) return;
            console.log('[REC.EXT] renderizarGrilla llamado con', datos.length, 'registros');
            grilla.innerHTML = '';

            if (datos.length === 0) {
                grilla.innerHTML = '<tr><td colspan="21" class="text-center py-4">No se encontraron registros</td></tr>';
                return;
            }

            datos.forEach(function(row) {
                var tr = document.createElement('tr');
                tr.setAttribute('data-recepcion-id', row.Id);

                var estadoTexto = (row.Estado || 'activo').toUpperCase();
                var estadoColor = (estadoTexto === 'ANULADO') ? '#dc3545' : '#198754';

                var fechaFormateada = '';
                if (row.Fecha) {
                    var partes = row.Fecha.split('-');
                    if (partes.length === 3) {
                        fechaFormateada = partes[2] + '/' + partes[1] + '/' + partes[0];
                    }
                }

                var horaFormateada = '';
                if (row.Hora) {
                    var hParts = row.Hora.split(':');
                    if (hParts.length >= 2) {
                        var hh = parseInt(hParts[0], 10);
                        var ampm = hh >= 12 ? 'PM' : 'AM';
                        hh = hh % 12 || 12;
                        horaFormateada = hh + ':' + hParts[1] + ' ' + ampm;
                    }
                }

                var nVale = String(row.NVale || '').padStart(6, '0');

                // ---- ACCIONES: Botón con menú contextual manual ----
                var isAnulado = (estadoTexto === 'ANULADO');
                var txtAnular = isAnulado ? 'Reactivar' : 'Anular';
                var iconAnular = isAnulado ? 'bi-arrow-counterclockwise text-success' : 'bi-x-circle-fill text-danger';
                var clsAnular = isAnulado ? 'btn-reactivar-rec' : 'btn-anular-rec';

                // Total desde la base de datos (calculado correctamente según tipo de obs)
                var cantidadProd = parseFloat(row.CantidadProducto) || 0;
                var cantObsProd = parseFloat(row.CantidadObsProducto) || 0;
                var total = parseFloat(row.TotalProducto) !== undefined && row.TotalProducto !== null && row.TotalProducto !== ''
                    ? parseFloat(row.TotalProducto)
                    : cantidadProd; // fallback si no hay Total en BD

                tr.innerHTML =
                    '<td class="acc-cell" style="text-align:center; vertical-align:middle;">' +
                        '<div class="acc-dropdown" style="position:relative; display:inline-block;">' +
                            '<button class="btn btn-sm btn-primary acc-btn-trigger" type="button" style="width:30px; height:30px; padding:2px; cursor:pointer;" data-id="' + row.Id + '" data-estado="' + estadoTexto + '">' +
                                '<span class="bi bi-three-dots-vertical" style="font-size:14px;"></span>' +
                            '</button>' +
                            '<div class="acc-menu-items" style="display:none; position:absolute; top:100%; left:0; z-index:9999; background:white; border:1px solid rgba(0,0,0,0.15); border-radius:6px; padding:4px 0; min-width:150px; box-shadow:0 6px 16px rgba(0,0,0,0.15); font-size:13px;">' +
                                '<div class="acc-menu-item" data-action="detalle" data-id="' + row.Id + '" style="padding:8px 16px; cursor:pointer;"><span class="bi bi-eye-fill me-2"></span>Ver detalle</div>' +
                                '<div style="border-top:1px solid #eee; margin:2px 0;"></div>' +
                                '<div class="acc-menu-item" data-action="' + (isAnulado ? 'reactivar' : 'anular') + '" data-id="' + row.Id + '" style="padding:8px 16px; cursor:pointer; color:' + (isAnulado ? '#198754' : '#dc3545') + ';"><span class="bi ' + iconAnular + ' me-2"></span>' + txtAnular + '</div>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td>' + nVale + '</td>' +
                    '<td>' + fechaFormateada + '</td>' +
                    '<td>' + horaFormateada + '</td>' +
                    '<td>' + (row.Turno || '') + '</td>' +
                    '<td>' + (row.Origen || '') + '</td>' +
                    '<td>' + (row.Recepcionista || '') + '</td>' +
                    '<td>' + (row.Empresa || '') + '</td>' +
                    '<td>' + (row.RUC || '') + '</td>' +
                    '<td>' + (row.Chofer || '') + '</td>' +
                    '<td>' + (row.Brevete || '') + '</td>' +
                    '<td>' + (row.NumeroGuia || '') + '</td>' +
                    '<td>' + (row.NumeroDocRef || '') + '</td>' +
                    '<td>' + (row.CodigoProducto || '') + '</td>' +
                    '<td>' + (row.DescripcionProducto || '') + '</td>' +
                    '<td style="text-align:center;">' + cantidadProd + '</td>' +
                    '<td>' + (row.TextoObservaciones || '-') + '</td>' +
                    '<td style="text-align:center;">' + cantObsProd + '</td>' +
                    '<td style="text-align:center; font-weight:600;">' + total + '</td>' +
                    '<td>' + (row.TextoObservacionesProducto || '') + '</td>' +
                    '<td>' + (row.Comentarios || '') + '</td>';

                grilla.appendChild(tr);
            });

            // Event delegation: toggle manual del menú y acciones
            if (!grilla._accHandlerAttached) {
                grilla.addEventListener('click', function(e) {
                    var btn = e.target.closest('.acc-btn-trigger');
                    if (btn) {
                        e.preventDefault();
                        e.stopPropagation();
                        // Cerrar otros menús abiertos
                        document.querySelectorAll('.acc-menu-items.show').forEach(function(m) {
                            if (m !== btn.nextElementSibling) m.classList.remove('show');
                        });
                        // Toggle este menú
                        var menu = btn.nextElementSibling;
                        if (menu) {
                            var isVisible = menu.classList.contains('show');
                            menu.classList.toggle('show');
                            menu.style.display = isVisible ? 'none' : 'block';
                        }
                        return;
                    }
                    
                    var item = e.target.closest('.acc-menu-item');
                    if (item) {
                        e.preventDefault();
                        e.stopPropagation();
                        var action = item.getAttribute('data-action');
                        var id = item.getAttribute('data-id');
                        if (!id) return;
                        // Cerrar menú
                        var menu = item.closest('.acc-menu-items');
                        if (menu) { menu.classList.remove('show'); menu.style.display = 'none'; }
                        // Ejecutar acción
                        if (action === 'detalle') {
                            window.location.href = window.BASE_URL + '/index.php?url=recepcionesexternas/edicion&autoLoadId=' + id;
                        }
                        else if (action === 'anular') anularRecepcion(id);
                        else if (action === 'reactivar') reactivarRecepcion(id);
                        return;
                    }
                });
                grilla._accHandlerAttached = true;
            }
            
            // Cerrar menús al hacer clic fuera
            if (!document._accOutsideHandler) {
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.acc-dropdown')) {
                        document.querySelectorAll('.acc-menu-items.show').forEach(function(m) {
                            m.classList.remove('show');
                            m.style.display = 'none';
                        });
                    }
                });
                document._accOutsideHandler = true;
            }

            // Refrescar filtros tipo Excel
            setTimeout(function() {
                if (window.ColumnFilters) window.ColumnFilters.refresh();
            }, 50);

            // Re-aplicar visibilidad de columnas después de renderizar
            aplicarVisibilidadColumnas();
        }

        // ---- Paginación ----
        function renderizarPaginacion() {
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
        async function exportarExcel() {
            if (window._exportandoExcel) return;
            window._exportandoExcel = true;
            try {
                // Mostrar indicador de carga
                if (typeof window.mostrarOverlayCarga === 'function') {
                    window.mostrarOverlayCarga('Generando archivo Excel...');
                }

                var baseUrl = window.BASE_URL || '';
                var filtrosActuales = obtenerFiltrosActivos();
                var exportarTodo = window.hayFiltrosActivos ? window.hayFiltrosActivos() : Object.keys(filtrosActuales).length > 0 || Object.keys(window._filtrosColumna || {}).length > 0;
                var paginaActualExport = window.paginaActual || 1;

                // Incluir preferencias de columnas visibles/ocultas
                var columnasVisibles = {};
                if (typeof columnPreferences !== 'undefined') {
                    columnasVisibles = columnPreferences;
                }

                var body = JSON.stringify({
                    filtros: filtrosActuales,
                    filtrosColumna: window._filtrosColumna || {},
                    paginaActual: paginaActualExport,
                    exportarTodo: exportarTodo,
                    columnasVisibles: columnasVisibles
                });

                var resp = await fetch(baseUrl + '/index.php?url=reportes/exportarExcelRecepcionesExternas', {
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
                a.download = 'RecepcionesExternas_' + new Date().toISOString().slice(0,19).replace(/[T:]/g,'-') + '.xlsx';
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
        }

        // Exponer funciones globalmente para onclick
        window._verDetalleRec = function(id) { verDetalle(id); };
        window._anularRec = function(id) { anularRecepcion(id); };
        window._reactivarRec = function(id) { reactivarRecepcion(id); };

        // ---- Ver detalle ----
        function verDetalle(id) {
            if (!id) return;
            // Buscar datos en la data ya cargada
            var datos = window.datosRecepcionesExternas || [];
            var row = null;
            for (var i = 0; i < datos.length; i++) {
                if (datos[i].Id == id) {
                    row = datos[i];
                    break;
                }
            }
            if (!row) {
                mostrarNotificacion('No se encontraron datos del vale', 'warning');
                return;
            }
            
            // Generar ventana de detalle con los datos disponibles
            var w = window.open('', '_blank', 'width=900,height=700');
            if (!w) { mostrarNotificacion('Permita ventanas emergentes', 'warning'); return; }
            
            var html = '<html><head><meta charset="utf-8"><title>Recepción Ext. ' + (row.NVale || '') + '</title>';
            html += '<style>body{font-family:Arial,sans-serif;margin:20px;} table{width:100%;border-collapse:collapse;} td,th{border:1px solid #ccc;padding:4px 8px;text-align:left;font-size:12px;} th{background:#f0f0f0;}</style></head>';
            html += '<body><h2>Recepción Externa: ' + String(row.NVale || '').padStart(6,'0') + '</h2>';
            html += '<table><tr><th>Campo</th><th>Valor</th></tr>';
            html += '<tr><td>Fecha</td><td>' + (row.Fecha || '') + '</td></tr>';
            html += '<tr><td>Hora</td><td>' + (row.Hora || '') + '</td></tr>';
            html += '<tr><td>Turno</td><td>' + (row.Turno || '') + '</td></tr>';
            html += '<tr><td>Origen</td><td>' + (row.Origen || '') + '</td></tr>';
            html += '<tr><td>Recepcionista</td><td>' + (row.Recepcionista || '') + '</td></tr>';
            html += '<tr><td>Empresa</td><td>' + (row.Empresa || '') + '</td></tr>';
            html += '<tr><td>RUC</td><td>' + (row.RUC || '') + '</td></tr>';
            html += '<tr><td>Chofer</td><td>' + (row.Chofer || '') + '</td></tr>';
            html += '<tr><td>Brevete</td><td>' + (row.Brevete || '') + '</td></tr>';
            html += '<tr><td>Guía</td><td>' + (row.NumeroGuia || '') + '</td></tr>';
            html += '<tr><td>Doc Ref.</td><td>' + (row.NumeroDocRef || '') + '</td></tr>';
            html += '<tr><td>Producto</td><td>' + (row.CodigoProducto || '') + ' - ' + (row.DescripcionProducto || '') + '</td></tr>';
            html += '<tr><td>Cantidad</td><td>' + (row.CantidadProducto || '') + ' ' + (row.UnidadMedidaProducto || '') + '</td></tr>';
            html += '<tr><td>Estado</td><td>' + (row.Estado || '') + '</td></tr>';
            html += '</table>';
            html += '<br><button onclick="window.print()" style="padding:8px 20px;font-size:14px;">Imprimir</button>';
            html += '</body></html>';
            w.document.write(html);
            w.document.close();
        }

        // ---- Anular ----
        function anularRecepcion(id) {
            if (!id) return;
            var motivo = prompt('Ingrese el motivo de anulación:');
            if (!motivo || motivo.trim() === '') {
                mostrarNotificacion('Debe proporcionar un motivo', 'warning');
                return;
            }
            fetch(window.BASE_URL + '/index.php?url=reportes/anularRecepcionExterna', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, motivo: motivo.trim() })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    mostrarNotificacion('Recepción anulada correctamente', 'success');
                    cargarDatos(paginaActual);
                } else {
                    mostrarNotificacion('Error: ' + (res.message || 'Error desconocido'), 'error');
                }
            })
            .catch(function() {
                mostrarNotificacion('Error de conexión', 'error');
            });
        }

        // ---- Reactivar ----
        function reactivarRecepcion(id) {
            if (!id) return;
            if (!confirm('¿Está seguro de reactivar esta recepción?')) return;
            fetch(window.BASE_URL + '/index.php?url=reportes/reactivarRecepcionExterna', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    mostrarNotificacion('Recepción reactivada correctamente', 'success');
                    cargarDatos(paginaActual);
                } else {
                    mostrarNotificacion('Error: ' + (res.message || 'Error desconocido'), 'error');
                }
            })
            .catch(function() {
                mostrarNotificacion('Error de conexión', 'error');
            });
        }

        // ---- SISTEMA DE COLUMNAS VISIBLES (OCULTAR/MOSTRAR) ----
        var columnPreferences = {};    // { "NVale": true, "Fecha": false, ... }
        var columnToggleMenu = null;   // Referencia al DOM del menú

        /**
         * Obtener la definición de todas las columnas filtrables desde el <thead>
         * Retorna array de { index, field, text }
         */
        function obtenerDefinicionColumnas() {
            var columns = [];
            var thead = document.querySelector('#grillaReporte thead');
            if (!thead) return columns;
            var headerRow = thead.querySelector('tr');
            if (!headerRow) return columns;
            var ths = headerRow.querySelectorAll('th');
            ths.forEach(function(th, index) {
                // Saltar columna de acciones (primera, sin data-field)
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
                // Si hay preferencia guardada para este campo, usarla
                if (columnPreferences.hasOwnProperty(col.field)) {
                    visible = columnPreferences[col.field];
                }
                // Aplicar a <th>
                var th = table.querySelectorAll('thead tr th')[col.index];
                if (th) {
                    th.style.display = visible ? '' : 'none';
                }
                // Aplicar a <td> en todas las filas
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    var td = row.querySelectorAll('td')[col.index];
                    if (td) {
                        td.style.display = visible ? '' : 'none';
                    }
                });
            });

            // La columna de acciones (índice 0) siempre visible
            var firstTh = table.querySelector('thead tr th:first-child');
            if (firstTh) firstTh.style.display = '';
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                var firstTd = row.querySelector('td:first-child');
                if (firstTd) firstTd.style.display = '';
            });

            // Actualizar contador de registros si existe
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
                body: JSON.stringify({ report_code: 'recepciones_externas' })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.preferences) {
                    // Merge con defaults: lo que no venga del servidor se asume true
                    var columns = obtenerDefinicionColumnas();
                    columns.forEach(function(col) {
                        if (res.preferences.hasOwnProperty(col.field)) {
                            columnPreferences[col.field] = res.preferences[col.field];
                        } else {
                            columnPreferences[col.field] = true; // default visible
                        }
                    });
                    aplicarVisibilidadColumnas();
                    // Actualizar checkboxes del menú si ya se inicializó
                    actualizarCheckboxesMenu();
                } else {
                    // Sin preferencias: todas visibles
                    var columns = obtenerDefinicionColumnas();
                    columns.forEach(function(col) {
                        columnPreferences[col.field] = true;
                    });
                    aplicarVisibilidadColumnas();
                }
            })
            .catch(function(err) {
                console.warn('[ColumnToggle] Error cargando preferencias:', err);
                // Fallback: todo visible
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
                    report_code: 'recepciones_externas',
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

        /**
         * Sincronizar estado de los checkboxes con columnPreferences
         */
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
                // Restaurar checkboxes al estado guardado
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
                // Leer estado de checkboxes y actualizar
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

                // Mostrar notificación
                var visibles = Object.values(columnPreferences).filter(function(v) { return v; }).length;
                var total = Object.keys(columnPreferences).length;
                mostrarNotificacion('Visibilidad de columnas actualizada (' + visibles + '/' + total + ' visibles)', 'success');
            });
            btnWrapper.appendChild(applyBtn);
            footer.appendChild(btnWrapper);
            menu.appendChild(footer);

            return menu;
        }

        /**
         * Posicionar el menú debajo del botón
         */
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

        /**
         * Cerrar el menú de columnas
         */
        function cerrarMenuColumnas() {
            if (columnToggleMenu && columnToggleMenu.parentNode) {
                columnToggleMenu.style.display = 'none';
            }
        }

        /**
         * Inicializar el sistema de toggle de columnas
         */
        function initColumnToggles() {
            var btnToggle = document.getElementById('btnToggleColumns');
            if (!btnToggle) return;

            btnToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Si el menú ya está visible, cerrarlo
                if (columnToggleMenu && columnToggleMenu.parentNode && columnToggleMenu.style.display !== 'none') {
                    cerrarMenuColumnas();
                    return;
                }

                // Cerrar otros menús (filtros de columna)
                if (window.ColumnFilters) {
                    try { document.querySelectorAll('.col-filter-dropdown').forEach(function(m) { m.style.display = 'none'; }); } catch(ex) {}
                }

                // Construir menú si no existe
                if (!columnToggleMenu || !columnToggleMenu.parentNode) {
                    columnToggleMenu = construirMenuColumnas();
                    if (!columnToggleMenu) return;
                    document.body.appendChild(columnToggleMenu);

                    // Evitar cierre al hacer clic dentro del menú
                    columnToggleMenu.addEventListener('click', function(ev) {
                        ev.stopPropagation();
                    });
                }

                // Sincronizar checkboxes con estado actual
                actualizarCheckboxesMenu();
                posicionarMenuColumna(columnToggleMenu, btnToggle);
                columnToggleMenu.style.display = 'block';

                // Actualizar contador
                var visibles = Object.keys(columnPreferences).filter(function(k) { return columnPreferences[k]; }).length;
                var total = Object.keys(columnPreferences).length;
                var countEl = document.getElementById('col-toggle-count');
                if (countEl) countEl.textContent = visibles + '/' + total + ' visibles';
            });

            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (columnToggleMenu && columnToggleMenu.parentNode && columnToggleMenu.style.display !== 'none') {
                    if (!e.target.closest('#btnToggleColumns') && !e.target.closest('.col-toggle-dropdown')) {
                        cerrarMenuColumnas();
                    }
                }
            });
        }

        // ---- Inicialización ----
        function init() {
            window._filtrosColumna = {};
            columnPreferences = {};

            // Inicializar filtros tipo Excel
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

            // Cargar datos
            cargarDatos(1);

            // NavegaciÃƒÂ³n por lotes de vales
            document.getElementById('btnLoteAnterior').addEventListener('click', function(e) {
                e.preventDefault();
                if (loteActual > 1) {
                    var nuevoOffset = offsetVales - LIMITE_VALES;
                    loteActual--;
                    cargarDatos(1, nuevoOffset);
                }
            });
            document.getElementById('btnLoteSiguiente').addEventListener('click', function(e) {
                e.preventDefault();
                if (loteActual < totalLotes) {
                    var nuevoOffset = offsetVales + LIMITE_VALES;
                    loteActual++;
                    cargarDatos(1, nuevoOffset);
                }
            });
            document.getElementById('btnLotePrimero').addEventListener('click', function(e) {
                e.preventDefault();
                if (loteActual > 1) {
                    loteActual = 1;
                    cargarDatos(1, 0);
                }
            });
            document.getElementById('btnLoteUltimo').addEventListener('click', function(e) {
                e.preventDefault();
                if (loteActual < totalLotes) {
                    loteActual = totalLotes;
                    var nuevoOffset = (totalLotes - 1) * LIMITE_VALES;
                    cargarDatos(1, nuevoOffset);
                }
            });

            // Botón exportar
            var btnExport = document.getElementById('btnExportExcel');
            if (btnExport) {
                btnExport.addEventListener('click', function(e) {
                    e.preventDefault();
                    exportarExcel();
                });
            }
        }

        init();
    });
})();
