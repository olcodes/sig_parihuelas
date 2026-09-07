// reportes-jabas.js - Reporte Jabas Negras/Blancas V1.0
// Columnas: Fecha, Saldo Inicial, Recep. Mañana, Recep. Tarde, Recep. Noche,
//           Total Recepción, Total Saldo Inicial, Desp. Mañana, Desp. Tarde,
//           Desp. Noche, Total Despacho, Saldo Final, Observaciones

(function() {
    'use strict';

    const timestamp = new Date().toISOString();
    console.log('>>>>> REPORTE JABAS - Timestamp:', timestamp, '<<<<<');

    let datosAgrupados = [];
    let datosFiltrados = [];
    let paginaActual = 1;
    let totalPaginas = 1;
    const LIMIT_PAGINA = 31;
    let filtrosColumnaActivos = {};
    let tipoActual = window.TIPO_JABA || 'negras';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('[ReporteJabas] Inicializando...');

        const now = new Date();
        document.getElementById('selectMes').value = now.getMonth() + 1;
        document.getElementById('selectAnio').value = now.getFullYear();

        if (document.getElementById('selectTipo')) {
            document.getElementById('selectTipo').value = tipoActual;
        }

        document.getElementById('btnFiltrar').addEventListener('click', function() {
            cargarDatos();
        });
        document.getElementById('selectTipo').addEventListener('change', function() {
            tipoActual = this.value;
        });

        setupExportar();

        setTimeout(function() {
            if (window.ColumnFilters) {
                window.ColumnFilters.init('#grillaReporte');
                window.ColumnFilters.onFilterChange(function(activeFilters) {
                    filtrosColumnaActivos = convertirFiltrosColumna(activeFilters);
                    aplicarFiltrosYRenderizar(1);
                });
            }
        }, 100);

        cargarDatos();
    }

    // ============================================
    // CARGA DE DATOS
    // ============================================

    function cargarDatos() {
        const mes = parseInt(document.getElementById('selectMes').value);
        const anio = parseInt(document.getElementById('selectAnio').value);

        console.log('[ReporteJabas] Cargando datos mes:', mes, 'anio:', anio, 'tipo:', tipoActual);

        fetch(window.BASE_URL + '/index.php?url=reportes/obtenerReporteJabas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mes: mes,
                anio: anio,
                tipo: tipoActual
            })
        })
        .then(r => {
            console.log('[ReporteJabas] Respuesta:', r.status);
            return r.json();
        })
        .then(res => {
            console.log('[ReporteJabas] Datos recibidos:', res);
            if (res.success) {
                const saldoInicialMes = parseFloat(res.saldoInicialMes) || 0;
                const observaciones = res.observaciones || {};
                datosAgrupados = agruparPorDia(res.data || [], saldoInicialMes, observaciones);
                console.log('[ReporteJabas] Datos agrupados:', datosAgrupados.length, 'filas');
                aplicarFiltrosYRenderizar(1);
            } else {
                console.error('[ReporteJabas] Error:', res.error);
                mostrarNotificacion('Error al cargar datos: ' + (res.error || 'Error desconocido'), 'error');
            }
        })
        .catch(err => {
            console.error('[ReporteJabas] Error de conexiÃ³n:', err);
            mostrarNotificacion('Error de conexiÃ³n al cargar datos', 'error');
        });
    }

    // ============================================
    // AGRUPACIÃ“N Y CÃLCULO DE SALDOS
    // ============================================

    function agruparPorDia(datosPlanos, saldoInicialMes, observacionesPorFecha) {
        const mapa = {};

        datosPlanos.forEach(row => {
            const fecha = row.fecha || '';
            if (!mapa[fecha]) {
                mapa[fecha] = {
                    fecha: fecha,
                    recep_maniana: 0,
                    recep_tarde: 0,
                    recep_noche: 0,
                    desp_maniana: 0,
                    desp_tarde: 0,
                    desp_noche: 0
                };
            }

            const turno = (row.turno || '').toUpperCase();
            const cantidad = parseFloat(row.cantidad) || 0;

            if (row.tipo_mov === 'RECEPCION') {
                if (turno === 'MAÃ‘ANA') mapa[fecha].recep_maniana += cantidad;
                else if (turno === 'TARDE') mapa[fecha].recep_tarde += cantidad;
                else if (turno === 'NOCHE') mapa[fecha].recep_noche += cantidad;
            } else if (row.tipo_mov === 'DESPACHO') {
                if (turno === 'MAÃ‘ANA') mapa[fecha].desp_maniana += cantidad;
                else if (turno === 'TARDE') mapa[fecha].desp_tarde += cantidad;
                else if (turno === 'NOCHE') mapa[fecha].desp_noche += cantidad;
            }
        });

        let saldoAnterior = saldoInicialMes;
        const filas = Object.keys(mapa).sort().map(fecha => {
            const f = mapa[fecha];
            f.saldo_inicial = saldoAnterior;
            f.total_recepcion = f.recep_maniana + f.recep_tarde + f.recep_noche;
            f.total_saldo_inicial = f.saldo_inicial + f.total_recepcion;
            f.total_despacho = f.desp_maniana + f.desp_tarde + f.desp_noche;
            f.saldo_final = f.saldo_inicial + f.total_recepcion - f.total_despacho;
            f.observaciones = observacionesPorFecha[fecha] || '';
            saldoAnterior = f.saldo_final;
            return f;
        });

        return filas;
    }

    // ============================================
    // FILTROS
    // ============================================

    function aplicarFiltrosYRenderizar(pagina) {
        paginaActual = pagina || 1;
        let datos = [...datosAgrupados];

        if (Object.keys(filtrosColumnaActivos).length > 0) {
            datos = datos.filter(row => {
                for (const campo in filtrosColumnaActivos) {
                    const valoresPermitidos = filtrosColumnaActivos[campo];
                    if (!Array.isArray(valoresPermitidos) || valoresPermitidos.length === 0) continue;
                    const valorCelda = String(row[campo] || '').trim();
                    if (!valoresPermitidos.includes(valorCelda)) return false;
                }
                return true;
            });
        }

        datosFiltrados = datos;

        totalPaginas = Math.max(1, Math.ceil(datosFiltrados.length / LIMIT_PAGINA));
        if (paginaActual > totalPaginas) paginaActual = totalPaginas;
        const offset = (paginaActual - 1) * LIMIT_PAGINA;
        const datosPagina = datosFiltrados.slice(offset, offset + LIMIT_PAGINA);

        renderizarGrilla(datosPagina);
        renderizarPaginacion();

        setTimeout(function() {
            if (window.ColumnFilters) {
                window.ColumnFilters.refresh();
            }
        }, 50);
    }

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

    // ============================================
    // RENDERIZADO DE GRILLA
    // ============================================

    function renderizarGrilla(datos) {
        const tbody = document.getElementById('grillaReporteBody');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (datos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="13" class="text-center py-4">No se encontraron registros</td></tr>';
            return;
        }

        datos.forEach(row => {
            const tr = document.createElement('tr');
            const fechaFormateada = formatearFecha(row.fecha);

            tr.innerHTML = `
                <td>${fechaFormateada}</td>
                <td class="celda-numero">${row.saldo_inicial}</td>
                <td class="celda-numero">${row.recep_maniana}</td>
                <td class="celda-numero">${row.recep_tarde}</td>
                <td class="celda-numero">${row.recep_noche}</td>
                <td class="celda-total-recepcion">${row.total_recepcion}</td>
                <td class="celda-total-recepcion">${row.total_saldo_inicial}</td>
                <td class="celda-numero">${row.desp_maniana}</td>
                <td class="celda-numero">${row.desp_tarde}</td>
                <td class="celda-numero">${row.desp_noche}</td>
                <td class="celda-total-despacho">${row.total_despacho}</td>
                <td class="celda-saldo-final">${row.saldo_final}</td>
                <td class="celda-obs">${row.observaciones || ''}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function formatearFecha(fecha) {
        if (!fecha) return '';
        var partes = fecha.split('-');
        if (partes.length === 3) {
            return partes[2] + '/' + partes[1] + '/' + partes[0];
        }
        return fecha;
    }

    // ============================================
    // PAGINACIÃ“N
    // ============================================

    function renderizarPaginacion() {
        const paginacionTop = document.querySelector('#paginacionReporte');
        const html = generarHTMLPaginacion();
        if (paginacionTop) paginacionTop.innerHTML = html;

        document.querySelectorAll('.pagination .page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const pagina = parseInt(link.getAttribute('data-pagina'));
                if (pagina && pagina >= 1 && pagina <= totalPaginas) {
                    aplicarFiltrosYRenderizar(pagina);
                }
            });
        });
    }

    function generarHTMLPaginacion() {
        if (totalPaginas <= 1) return '';
        let html = '';
        html += `<li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-pagina="${paginaActual - 1}" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>`;
        for (let i = 1; i <= totalPaginas; i++) {
            if (i === 1 || i === totalPaginas || (i >= paginaActual - 2 && i <= paginaActual + 2)) {
                html += `<li class="page-item ${i === paginaActual ? 'active' : ''}">
                    <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                </li>`;
            } else if (i === paginaActual - 3 || i === paginaActual + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        html += `<li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
            <a class="page-link" href="#" data-pagina="${paginaActual + 1}" aria-label="Siguiente">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>`;
        return html;
    }

    // ============================================
    // EXPORTAR
    // ============================================

    function setupExportar() {
        const btnExportar = document.getElementById('btnExportarExcel');
        if (btnExportar) {
            btnExportar.addEventListener('click', exportarExcel);
        }
    }

    async function exportarExcel() {
        console.log('[ReporteJabas] Exportando a Excel...');

        if (datosFiltrados.length === 0) {
            mostrarNotificacion('No hay datos para exportar', 'warning');
            return;
        }

        if (typeof window.mostrarOverlayCarga === 'function') {
            window.mostrarOverlayCarga('Generando archivo Excel...');
        }

        try {
            const resp = await fetch(window.BASE_URL + '/index.php?url=reportes/exportarExcelJabas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    datos: datosFiltrados,
                    tipo: tipoActual
                })
            });

            if (!resp.ok) {
                const errorText = await resp.text();
                throw new Error('Error del servidor: ' + errorText.substring(0, 200));
            }

            const blob = await resp.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const tipoLabel = tipoActual === 'blancas' ? 'Blancas' : 'Negras';
            a.href = url;
            a.download = 'Reporte_Jabas_' + tipoLabel + '_' + new Date().toISOString().slice(0, 10) + '.xlsx';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch (err) {
            console.error('[ReporteJabas] Error exportando Excel:', err);
            mostrarNotificacion('Error al exportar Excel', 'error');
        } finally {
            if (typeof window.ocultarOverlayCarga === 'function') {
                window.ocultarOverlayCarga();
            }
        }
    }

    // ============================================
    // UTILIDADES
    // ============================================

    function mostrarNotificacion(mensaje, tipo) {
        if (typeof window.mostrarNotificacion === 'function') {
            window.mostrarNotificacion(mensaje, tipo);
            return;
        }
        alert(mensaje);
    }

})();
