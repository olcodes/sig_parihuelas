/**
 * reportes_recepcionesinternas.js - REPORTE DE RECEPCIONES INTERNAS
 * VERSIÓN PLANA: Muestra todos los registros en una grilla plana
 * con filtros tipo Excel en la cabecera.
 * 
 * Dependencias: jQuery, Bootstrap 5, column-filters.js
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ---- Variables de estado ----
    let paginaActual = 1;
    let grilla = document.getElementById('grillaReporteBody');
    let filtrosActivos = {};

    try { window.paginaActual = paginaActual; } catch(e) {}

    // ---- Polyfill para mostrar mensajes ----
    if (typeof window.mostrarMensaje !== 'function') {
        window.mostrarMensaje = function(mensaje, tipo) {
            tipo = tipo || 'success';
            try {
                var container = document.getElementById('mensajes-container') || document.body;
                var alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-' + tipo + ' alert-dismissible fade show';
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = mensaje + ' <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';
                container.appendChild(alertDiv);
                setTimeout(function() {
                    try { alertDiv.classList.remove('show'); setTimeout(function() { try { alertDiv.remove(); } catch(e){} }, 300); } catch(e){}
                }, 4500);
            } catch(e) {
                try { alert(mensaje); } catch(ex) {}
            }
        };
    }

    // ---- Obtener filtros de búsqueda ----
    function obtenerFiltros() {
        var filtros = {};
        document.querySelectorAll('.filtro-grilla').forEach(function(input) {
            var campo = input.getAttribute('data-campo');
            if (input.value !== null && input.value !== '') {
                switch (campo) {
                    case 'Fecha':
                        if (input.type === 'date') {
                            filtros['fechaDesde'] = input.value;
                            filtros['fechaHasta'] = input.value;
                        } else {
                            filtros['Fecha'] = input.value;
                        }
                        break;
                    default:
                        filtros[campo] = input.value;
                }
            }
        });
        return filtros;
    }

    // ---- Formateo ----
    function formatearNVale(valor) {
        if (!valor) return '';
        try {
            var s = String(valor).replace(/\D/g, '');
            return s.padStart(6, '0');
        } catch(e) { return valor; }
    }

    function formatearFecha(fecha) {
        if (!fecha) return '';
        try {
            var parts = fecha.split('-');
            if (parts.length === 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
            return fecha;
        } catch(e) { return fecha || ''; }
    }

    function formatearHora(hora) {
        if (!hora) return '';
        try {
            if (!hora.includes(':')) return hora;
            var parts = hora.split(':');
            var h = parseInt(parts[0], 10);
            var m = parts[1];
            var ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return h + ':' + m + ' ' + ampm;
        } catch(e) { return hora || ''; }
    }

    // ---- Render de la grilla plana ----
    function renderGrilla(datos) {
        if (!grilla) return;
        grilla.innerHTML = '';

        if (!Array.isArray(datos) || datos.length === 0) {
            grilla.innerHTML = '<tr><td colspan="16" class="text-center py-4">Sin registros</td></tr>';
            return;
        }

        var html = '';
        datos.forEach(function(row) {
            var estadoVal = (row.Estado || '').toString().toUpperCase();
            var estadoColor = (estadoVal === 'ANULADO') ? '#dc3545' : '#198754';
            var accionAnular = (estadoVal === 'ANULADO')
                ? '<li><a class="dropdown-item btn-reactivar-recepcion" href="javascript:void(0)" data-id="' + row.Id + '" title="Reactivar"><i class="bi bi-arrow-counterclockwise me-2 text-success"></i>Reactivar</a></li>'
                : '<li><a class="dropdown-item btn-anular-recepcion" href="javascript:void(0)" data-id="' + row.Id + '" title="Anular"><i class="bi bi-x-circle-fill me-2 text-danger"></i>Anular</a></li>';

            var codigoProd = (row.CodigoProducto === 0 || row.CodigoProducto) ? String(row.CodigoProducto) : '';

            html += '<tr class="fila-recepcion" data-id="' + row.Id + '">' +
                '<td>' +
                    '<div class="dropdown">' +
                        '<button type="button" class="btn btn-sm btn-primary action-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Acciones">' +
                            '<i class="bi bi-three-dots-vertical"></i>' +
                        '</button>' +
                        '<ul class="dropdown-menu shadow-sm">' +
                            '<li><a class="dropdown-item btn-editar-recepcion" href="javascript:void(0)" data-id="' + row.Id + '" title="Ver detalle"><i class="bi bi-eye-fill me-2"></i>Ver detalle</a></li>' +
                            '<li><hr class="dropdown-divider"></li>' +
                            accionAnular +
                        '</ul>' +
                    '</div>' +
                '</td>' +
                '<td>' + formatearNVale(row.NVale) + '</td>' +
                '<td>' + formatearFecha(row.Fecha) + '</td>' +
                '<td>' + formatearHora(row.Hora) + '</td>' +
                '<td>' + (row.Turno || '') + '</td>' +
                '<td>' + (row.Area || '') + '</td>' +
                '<td>' + (row.Subarea || '') + '</td>' +
                '<td>' + (row.Despachador || '') + '</td>' +
                '<td>' + (row.MedioTransporte || '') + '</td>' +
                '<td>' + (row.Verificador || '') + '</td>' +
                '<td>' + codigoProd + '</td>' +
                '<td>' + (row.NombreProducto || '') + '</td>' +
                '<td>' + (row.UnidadMedida || '') + '</td>' +
                '<td>' + (row.Cantidad || '') + '</td>' +
                '<td>' + (row.ComentariosProducto || '') + '</td>' +
                '<td><span style="color:' + estadoColor + '; font-weight:700">' + estadoVal + '</span></td>' +
            '</tr>';
        });

        grilla.innerHTML = html;

        // Refrescar filtros tipo Excel
        setTimeout(function() {
            if (window.ColumnFilters) {
                window.ColumnFilters.refresh();
            }
        }, 50);

        // Re-aplicar visibilidad de columnas después de renderizar
        setTimeout(function() {
            aplicarVisibilidadColumnas();
        }, 30);

        agregarEventosAcciones();
    }

    // ---- Eventos de acciones ----
    function agregarEventosAcciones() {
        grilla.querySelectorAll('.btn-editar-recepcion').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                window.location.href = window.BASE_URL + '/index.php?url=recepcionesinternas/edicion&autoLoadId=' + id;
            });
        });

        grilla.querySelectorAll('.btn-anular-recepcion').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                if (window.confirmarAnulacion) window.confirmarAnulacion(id);
            });
        });

        grilla.querySelectorAll('.btn-reactivar-recepcion').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                confirmarReactivacion(id);
            });
        });
    }

    // ---- Cargar datos ----
    function cargarDatos(pagina) {
        paginaActual = pagina || 1;
        try { window.paginaActual = paginaActual; } catch(e) {}
        
        filtrosActivos = obtenerFiltros();

        if (grilla) {
            grilla.innerHTML = '<tr><td colspan="16" class="text-center py-3">Cargando...</td></tr>';
        }

        fetch(window.BASE_URL + '/index.php?url=reportes/obtenerRecepcionesInternas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pagina: paginaActual, filtros: filtrosActivos, filtrosColumna: window._filtrosColumna || {} })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res && Array.isArray(res.data)) {
                window.datosRecepciones = res.data;
                var table = document.getElementById('grillaReporte');
                if (table && res.totalRegistros) {
                    table.setAttribute('data-total-registros', res.totalRegistros);
                }
                // Pasar valores únicos del servidor a ColumnFilters
                if (window.ColumnFilters && res.uniqueValues) {
                    window.ColumnFilters.setServerUniqueValues(res.uniqueValues);
                }
                grilla.innerHTML = '';
                if (res.data.length > 0) {
                    renderGrilla(res.data);
                    renderPaginacion(res.totalPaginas || 1);
                } else {
                    grilla.innerHTML = '<tr><td colspan="16" class="text-center text-warning">Sin registros</td></tr>';
                    renderPaginacion(1);
                }
            }
        })
        .catch(function() {
            if (grilla) grilla.innerHTML = '<tr><td colspan="16" class="text-center text-danger">Error al cargar datos</td></tr>';
            renderPaginacion(1);
        });
    }

    try {
        window.cargarDatos = function(pagina) {
            try { return cargarDatos(pagina); } catch(e) { console.warn('Error cargarDatos', e); }
        };
    } catch(e) {}

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

            var resp = await fetch(baseUrl + '/index.php?url=reportes/exportarExcelRecepcionesInternas', {
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
            a.download = 'RecepcionesInternas_' + new Date().toISOString().slice(0,19).replace(/[T:]/g,'-') + '.xlsx';
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
    window.confirmarAnulacion = function(id) {
        if (!id) return;
        var motivo = prompt('Ingrese el motivo de anulación (opcional):');
        fetch(window.BASE_URL + '/index.php?url=reportes/anularRecepcionInterna', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, motivo: motivo || '' })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res && res.success) {
                mostrarMensaje('Recepción anulada correctamente', 'success');
                cargarDatos(paginaActual);
            } else {
                mostrarMensaje('Error: ' + (res.message || 'Error desconocido'), 'danger');
            }
        })
        .catch(function() {
            mostrarMensaje('Error de conexión al anular', 'danger');
        });
    };

    function confirmarReactivacion(id) {
        if (!id) return;
        if (!confirm('¿Está seguro de reactivar esta recepción?')) return;
        fetch(window.BASE_URL + '/index.php?url=reportes/reactivarRecepcionInterna', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res && res.success) {
                mostrarMensaje('Recepción reactivada correctamente', 'success');
                cargarDatos(paginaActual);
            } else {
                mostrarMensaje('Error: ' + (res.message || 'Error desconocido'), 'danger');
            }
        })
        .catch(function() {
            mostrarMensaje('Error de conexión al reactivar', 'danger');
        });
    }

    // ---- Vista previa (simplificada) ----
    function mostrarVistaPrevia(id) {
        if (!id) return;
        (async function() {
            try {
                // Asegurar que printPreview esté cargado
                if (typeof window._asegurarPrintPreview === 'function') {
                    await window._asegurarPrintPreview();
                }
                let resp = await fetch(window.BASE_URL + '/index.php?url=recepcionesinternas/getById', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                let json = await resp.json();
                if (json && json.success && json.data) {
                    var d = json.data;
                    var productosArr = d.Detalles || d.Productos || d.productos || [];
                    var correlativo = d.NVale || d.NVALE || d.nvale || '';
                    var fecha = d.Fecha || '';

                    if (window.printPreview && typeof window.printPreview.showModalPreview === 'function') {
                        var html = generarHTMLVistaPrevia(d, productosArr, correlativo, fecha);
                        window.printPreview.showModalPreview(html, { 
                            title: 'Vale de Recepción Interna - ' + correlativo,
                            twoUp: true, modalSize: 'md'
                        });
                        return;
                    }
                    alert('Módulo de vista previa no disponible');
                } else {
                    mostrarMensaje('Error al obtener datos del vale', 'danger');
                }
            } catch(e) {
                console.error('Error vista previa', e);
                mostrarMensaje('Error al cargar vista previa', 'danger');
            }
        })();
    }

    function generarHTMLVistaPrevia(d, productos, correlativo, fecha) {
        var baseUrl = window.BASE_URL || '';
        var logoUrl = baseUrl + '/img/Logo-Lavoro-1536x442.png';
        var correlDisplay = String(correlativo || '').replace(/^VRI-?/, '').padStart(6, '0');

        var productosHtml = '';
        if (Array.isArray(productos) && productos.length > 0) {
            productos.forEach(function(p) {
                productosHtml += '<tr>' +
                    '<td style="border:0.25pt solid #000; padding:2px 4px; text-align:center;">' + (p.Codigo || p.CodigoProducto || '') + '</td>' +
                    '<td style="border:0.25pt solid #000; padding:2px 4px;">' + (p.Producto || p.DescripcionProducto || '') + '</td>' +
                    '<td style="border:0.25pt solid #000; padding:2px 4px; text-align:center;">' + (p.UnidadMedida || '') + '</td>' +
                    '<td style="border:0.25pt solid #000; padding:2px 4px; text-align:center;">' + (p.Cantidad || '') + '</td>' +
                    '<td style="border:0.25pt solid #000; padding:2px 4px;">' + (p.Comentarios || '') + '</td>' +
                '</tr>';
            });
        }

        return '<div style="font-family:Arial,sans-serif; padding:10px; font-size:0.58rem; line-height:1.08;">' +
            '<div style="text-align:center;">' +
                '<img src="' + logoUrl + '" style="height:26px; max-width:100px;" alt="Logo Lavoro" />' +
                '<h3 style="font-weight:bold; margin:6px 0;">VALE DE RECEPCIÓN INTERNA</h3>' +
                '<h4 style="margin:4px 0;">VRI-' + correlDisplay + '</h4>' +
            '</div>' +
            '<table style="width:100%; margin-top:8px; border-collapse:collapse; font-size:0.52rem;">' +
                '<tr><td style="font-weight:700; width:20%;">Fecha:</td><td>' + formatearFecha(fecha) + '</td><td style="font-weight:700; width:20%;">Área:</td><td>' + (d.Area || '') + '</td></tr>' +
                '<tr><td style="font-weight:700;">Despachador:</td><td>' + (d.Despachador || '') + '</td><td style="font-weight:700;">Verificador:</td><td>' + (d.Verificador || '') + '</td></tr>' +
            '</table>' +
            '<table class="productos" style="width:100%; margin-top:8px; border-collapse:collapse; font-size:0.52rem;">' +
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
    }

    // ---- Forzar estilos compactos vía JS (al final de todo) ----
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
            body: JSON.stringify({ report_code: 'recepciones_internas' })
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
                report_code: 'recepciones_internas',
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
            try { mostrarMensaje('Visibilidad de columnas actualizada (' + visibles + '/' + total + ' visibles)', 'success'); } catch(e) {}
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

        cargarDatos(1);

        var btnExport = document.getElementById('btnExportExcel');
        if (btnExport) {
            btnExport.addEventListener('click', function(e) {
                e.preventDefault();
                if (window.exportarExcel) window.exportarExcel();
            });
        }
    }

    init();
});
