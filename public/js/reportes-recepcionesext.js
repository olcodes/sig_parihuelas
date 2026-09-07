// reportes-recepcionesext.js - Reporte Recepciones Ext. V2.1 (Productos en columnas)
// Columnas: Fecha, Turno, N Vale, N Guia, N Doc Referencia, Origen, Transportista, Chofer, Observaciones,
//           19003031, 19002924, 19003730, 19003521, Total General
// Una fila por cada combinacion Vale+Guia, con los 4 productos especificos en columnas

(function() {
    'use strict';
    
    const timestamp = new Date().toISOString();
    console.log('>>>>> REPORTE RECEPCIONES EXT. V2.1 - Timestamp:', timestamp, '<<<<<');
    console.log('[ReporteRecepcionesExt] Script cargado');

    // Codigos de producto fijos para las columnas
    const CODIGOS_PRODUCTO = ['19003031', '19002924', '19003730', '19003521'];
    
    // Variables globales
    let datosOriginales = [];
    let datosAgrupados = [];
    let datosFiltrados = [];
    let paginaActual = 1;
    let totalPaginas = 1;
    const LIMIT_PAGINA = 20;
    
    // Estado de filtros de columna (tipo Excel)
    let filtrosColumnaActivos = {};  // { "NVale": ["000001", "000002"], ... }
    
    // Esperar a que el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        console.log('[ReporteRecepcionesExt] Inicializando...');
        
        // Configurar boton de exportar
        setupExportar();
        
        // Inicializar ColumnFilters (filtros tipo Excel)
        setTimeout(function() {
            if (window.ColumnFilters) {
                window.ColumnFilters.init('#grillaReporte');
                window.ColumnFilters.onFilterChange(function(activeFilters) {
                    // Convertir filtros de columna (indice -> nombre de campo)
                    filtrosColumnaActivos = convertirFiltrosColumna(activeFilters);
                    aplicarFiltrosYRenderizar(1);
                });
            }
        }, 100);
        
        // Cargar datos iniciales
        cargarDatos();
    }
    
    // ============================================
    // CARGA DE DATOS
    // ============================================
    
    function cargarDatos() {
        // Obtener filtros de texto (inputs simples, si existen)
        const filtros = obtenerFiltrosTexto();
        
        console.log('[ReporteRecepcionesExt] Cargando datos del servidor...');
        
        fetch(window.BASE_URL + '/index.php?url=reportes/obtenerRecepcionesExternas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pagina: 1,
                filtros: filtros,
                exportar: false,
                agrupar: true  // Flag para que el servidor no pagine (agrupacion cliente-side)
            })
        })
        .then(r => {
            console.log('[ReporteRecepcionesExt] Respuesta:', r.status);
            return r.json();
        })
        .then(res => {
            console.log('[ReporteRecepcionesExt] Datos recibidos:', res);
            if (res.success) {
                datosOriginales = res.data || [];
                
                console.log('[ReporteRecepcionesExt] Datos crudos:', datosOriginales.length, 'registros');
                
                // Agrupar datos por Vale+Guia y pivotear productos
                datosAgrupados = agruparPorValeYGuia(datosOriginales);
                
                console.log('[ReporteRecepcionesExt] Datos agrupados:', datosAgrupados.length, 'filas');
                
                // Aplicar filtros y renderizar
                aplicarFiltrosYRenderizar(1);
            } else {
                console.error('[ReporteRecepcionesExt] Error en respuesta:', res.error);
                mostrarNotificacion('Error al cargar datos: ' + (res.error || 'Error desconocido'), 'error');
            }
        })
        .catch(err => {
            console.error('[ReporteRecepcionesExt] Error cargando datos:', err);
            mostrarNotificacion('Error de conexion al cargar datos', 'error');
        });
    }
    
    /**
     * Aplica filtros de texto + filtros de columna (Excel), pagina y renderiza
     */
    function aplicarFiltrosYRenderizar(pagina) {
        paginaActual = pagina || 1;
        
        // 1. Filtros de texto (si existen inputs .filtro-grilla)
        let datos = [...datosAgrupados];
        const filtrosTexto = obtenerFiltrosTexto();
        if (Object.keys(filtrosTexto).length > 0) {
            datos = datos.filter(row => {
                for (const campo in filtrosTexto) {
                    if (!filtrosTexto[campo]) continue;
                    const valor = String(filtrosTexto[campo]).toLowerCase();
                    const valorCelda = String(row[campo] || '').toLowerCase();
                    if (valorCelda.indexOf(valor) === -1) return false;
                }
                return true;
            });
        }
        
        // 2. Filtros de columna (tipo Excel)
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
        
        // Paginacion local
        totalPaginas = Math.max(1, Math.ceil(datosFiltrados.length / LIMIT_PAGINA));
        if (paginaActual > totalPaginas) paginaActual = totalPaginas;
        const offset = (paginaActual - 1) * LIMIT_PAGINA;
        const datosPagina = datosFiltrados.slice(offset, offset + LIMIT_PAGINA);
        
        renderizarGrilla(datosPagina);
        renderizarPaginacion();
        
        // Refrescar filtros de columna con los nuevos datos renderizados
        setTimeout(function() {
            if (window.ColumnFilters) {
                window.ColumnFilters.refresh();
            }
        }, 50);
    }
    
    // ============================================
    // CONVERSION DE FILTROS DE COLUMNA
    // ============================================
    
    /**
     * Convierte filtros de columna (indice de columna -> valores) a (nombre de campo -> valores)
     */
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
    // AGRUPACION Y PIVOTEO
    // ============================================
    
    /**
     * Agrupa los datos planos (una fila por producto) en una fila por (Vale+Guia)
     * con los 4 productos especificos como columnas.
     */
    function agruparPorValeYGuia(datosPlanos) {
        const grupos = {};
        
        datosPlanos.forEach(row => {
            // Clave unica: NVale + NumeroGuia
            const clave = (row.NVale || '') + '|' + (row.NumeroGuia || '');
            
            if (!grupos[clave]) {
                // Inicializar grupo con datos del vale/guia
                grupos[clave] = {
                    Fecha: row.Fecha || '',
                    Turno: row.Turno || '',
                    NVale: row.NVale || '',
                    Origen: row.Origen || '',
                    Empresa: row.Empresa || '',
                    Chofer: row.Chofer || '',
                    NumeroGuia: row.NumeroGuia || '',
                    NumeroDocRef: row.NumeroDocRef || '',
                    ObservacionTexto: row.ObservacionTexto || '',
                    cantidades: {
                        '19003031': 0,
                        '19002924': 0,
                        '19003730': 0,
                        '19003521': 0
                    }
                };
            }
            
            // Acumular cantidad si el codigo del producto esta en nuestra lista
            const codigo = String(row.CodigoProducto || '').trim();
            const cantidad = parseFloat(row.CantidadProducto) || 0;
            
            if (CODIGOS_PRODUCTO.includes(codigo)) {
                grupos[clave].cantidades[codigo] += cantidad;
            }
        });
        
        // Convertir grupos a array plano para renderizado
        const resultado = [];
        Object.keys(grupos).forEach(clave => {
            const g = grupos[clave];
            const p1 = g.cantidades['19003031'];
            const p2 = g.cantidades['19002924'];
            const p3 = g.cantidades['19003730'];
            const p4 = g.cantidades['19003521'];
            const total = p1 + p2 + p3 + p4;
            
            resultado.push({
                Fecha: g.Fecha,
                Turno: g.Turno,
                NVale: g.NVale,
                NumeroGuia: g.NumeroGuia,
                NumeroDocRef: g.NumeroDocRef,
                Origen: g.Origen,
                Transportista: g.Empresa,
                Chofer: g.Chofer,
                Observaciones: g.ObservacionTexto,
                Prod19003031: p1,
                Prod19002924: p2,
                Prod19003730: p3,
                Prod19003521: p4,
                TotalGeneral: total
            });
        });
        
        return resultado;
    }
    
    /**
     * Obtener filtros de texto (inputs .filtro-grilla) - POR AHORA VACIO
     * porque estamos usando ColumnFilters tipo Excel en lugar de inputs de texto
     */
    function obtenerFiltrosTexto() {
        const filtros = {};
        const inputsFiltro = document.querySelectorAll('.filtro-grilla');
        inputsFiltro.forEach(input => {
            const campo = input.getAttribute('data-campo');
            const valor = input.value.trim();
            if (valor) {
                filtros[campo] = valor;
            }
        });
        return filtros;
    }
    
    // ============================================
    // RENDERIZADO DE GRILLA
    // ============================================
    
    function renderizarGrilla(datos) {
        const tbody = document.getElementById('grillaReporteBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        console.log('[ReporteRecepcionesExt] Renderizando grilla. Filas:', datos.length);
        
        if (datos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="14" class="text-center py-4">No se encontraron registros</td></tr>';
            return;
        }
        
        datos.forEach(row => {
            const tr = document.createElement('tr');
            
            const total = (row.TotalGeneral || 0).toFixed(2);
            const fechaFormateada = formatearFecha(row.Fecha);
            
            tr.innerHTML = `
                <td>${fechaFormateada}</td>
                <td>${row.Turno || ''}</td>
                <td>${String(row.NVale || '').padStart(6, '0')}</td>
                <td>${row.NumeroGuia || ''}</td>
                <td>${row.NumeroDocRef || ''}</td>
                <td>${row.Origen || ''}</td>
                <td>${row.Transportista || ''}</td>
                <td>${row.Chofer || ''}</td>
                <td>${row.Observaciones || ''}</td>
                <td class="celda-producto">${(row.Prod19003031 || 0).toFixed(2)}</td>
                <td class="celda-producto">${(row.Prod19002924 || 0).toFixed(2)}</td>
                <td class="celda-producto">${(row.Prod19003730 || 0).toFixed(2)}</td>
                <td class="celda-producto">${(row.Prod19003521 || 0).toFixed(2)}</td>
                <td class="celda-total">${total}</td>
            `;
            
            tbody.appendChild(tr);
        });
        
        console.log('[ReporteRecepcionesExt] Total filas renderizadas:', datos.length);
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
    // PAGINACION
    // ============================================
    
    function renderizarPaginacion() {
        const paginacionTop = document.querySelector('#paginacionReporte');
        
        const html = generarHTMLPaginacion();
        
        if (paginacionTop) paginacionTop.innerHTML = html;
        
        // Agregar eventos a los botones
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
        console.log('[ReporteRecepcionesExt] Exportando a Excel...');
        
        if (datosFiltrados.length === 0) {
            mostrarNotificacion('No hay datos para exportar', 'warning');
            return;
        }
        
        // Mostrar overlay de carga
        if (typeof window.mostrarOverlayCarga === 'function') {
            window.mostrarOverlayCarga('Generando archivo Excel...');
        }
        
        try {
            // Determinar si hay filtros activos (columna o de texto)
            const hayFiltros = Object.keys(filtrosColumnaActivos).length > 0 || Object.keys(obtenerFiltrosTexto()).length > 0;
            
            // Enviar los datos ya filtrados y pivoteados al servidor
            // para que genere el Excel con estilo profesional
            const resp = await fetch(window.BASE_URL + '/index.php?url=reportes/exportarExcelRecepcionesExt', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    datos: datosFiltrados,
                    paginaActual: paginaActual,
                    exportarTodo: hayFiltros
                })
            });
            
            if (!resp.ok) {
                const errorText = await resp.text();
                throw new Error('Error del servidor: ' + errorText.substring(0, 200));
            }
            
            const blob = await resp.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Reporte_Recepciones_Ext_' + new Date().toISOString().slice(0, 10) + '.xlsx';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            // No mostrar mensaje de "exportado correctamente"
        } catch (err) {
            console.error('[ReporteRecepcionesExt] Error exportando Excel:', err);
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
        console.log('[ReporteRecepcionesExt] Notificacion:', tipo, '-', mensaje);
        
        if (typeof window.mostrarNotificacion === 'function') {
            window.mostrarNotificacion(mensaje, tipo);
            return;
        }
        
        alert(mensaje);
    }
    
})();
