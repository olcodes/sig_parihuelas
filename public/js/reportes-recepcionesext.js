// reportes-recepcionesext.js - Reporte Recepciones Ext. V2.2 (Productos en columnas)
// Columnas: Fecha, Turno, N Vale, N Guia, N Doc Referencia, Origen, Transportista, Chofer, Observaciones,
//           19003031, 19002924, 19003730, 19003521, Total General
// Una fila por cada (Vale+Guia+Producto): si una guia tiene 2 productos salen 2 filas,
// cada una con la cantidad en la columna de su producto y 0 en las demas

(function() {
    'use strict';
    
    const timestamp = new Date().toISOString();
    console.log('>>>>> REPORTE RECEPCIONES EXT. V2.2 - Timestamp:', timestamp, '<<<<<');
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
    
    // Navegación por lotes de vales (500 vales por lote, igual que el reporte principal)
    let offsetVales = 0;
    let totalLotes = 1;
    let loteActual = 1;
    const LIMITE_VALES = 500;
    
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
        
        // Configurar navegación por lotes de vales
        setupNavegacionLotes();
    }
    
    // ============================================
    // CARGA DE DATOS
    // ============================================
    
    function cargarDatos(nuevoOffset) {
        // Si se indica un nuevo offset, cambiar de lote
        if (nuevoOffset !== undefined) {
            offsetVales = Math.max(0, nuevoOffset);
        }
        
        // Obtener filtros de texto (inputs simples, si existen)
        const filtros = obtenerFiltrosTexto();
        
        console.log('[ReporteRecepcionesExt] Cargando datos del servidor (lote ' + loteActual + ')...');
        
        fetch(window.BASE_URL + '/index.php?url=reportes/obtenerRecepcionesExternas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pagina: 1,
                filtros: filtros,
                exportar: false,
                agrupar: true,  // Flag para que el servidor no pagine (agrupacion cliente-side)
                offsetVales: offsetVales
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
                
                // Actualizar navegación por lotes de vales
                totalLotes = res.totalLotes || 1;
                loteActual = res.loteActual || 1;
                offsetVales = res.offsetVales !== undefined ? res.offsetVales : offsetVales;
                actualizarNavegacionLotes();
                
                console.log('[ReporteRecepcionesExt] Datos crudos:', datosOriginales.length, 'registros');
                
                // Separar datos por Vale+Guia+Producto (1 fila por producto de la guia)
                datosAgrupados = separarPorGuiaYProducto(datosOriginales);
                
                console.log('[ReporteRecepcionesExt] Datos separados:', datosAgrupados.length, 'filas');
                
                // Alimentar ColumnFilters con TODOS los valores unicos del dataset
                // (no solo los de la pagina actual) para que los filtros tipo Excel
                // muestren todas las opciones disponibles, igual que el reporte principal
                if (window.ColumnFilters) {
                    window.ColumnFilters.setServerUniqueValues(calcularUniqueValues(datosAgrupados));
                }
                
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
    
    // ============================================
    // NAVEGACION POR LOTES DE VALES
    // ============================================
    
    /**
     * Actualiza la UI de navegación por lotes (botones y texto)
     */
    function actualizarNavegacionLotes() {
        const loteInfo = document.getElementById('loteInfo');
        const btnAnt = document.getElementById('btnLoteAnterior');
        const btnSig = document.getElementById('btnLoteSiguiente');
        const btnPrimero = document.getElementById('btnLotePrimero');
        const btnUltimo = document.getElementById('btnLoteUltimo');
        const loteDetalle = document.getElementById('loteDetalle');
        
        if (loteInfo) loteInfo.textContent = loteActual + ' / ' + totalLotes;
        
        const alInicio = (loteActual <= 1);
        const alFinal = (loteActual >= totalLotes);
        
        if (btnAnt) btnAnt.disabled = alInicio;
        if (btnSig) btnSig.disabled = alFinal;
        if (btnPrimero) btnPrimero.disabled = alInicio;
        if (btnUltimo) btnUltimo.disabled = alFinal;
        
        if (loteDetalle) {
            const desde = offsetVales + 1;
            const hasta = Math.min(offsetVales + LIMITE_VALES, loteActual * LIMITE_VALES);
            loteDetalle.textContent = '(Vale ' + desde + ' - ' + hasta + ')';
        }
    }
    
    /**
     * Configura los botones de navegación por lotes
     */
    function setupNavegacionLotes() {
        const btnPrimero = document.getElementById('btnLotePrimero');
        const btnAnt = document.getElementById('btnLoteAnterior');
        const btnSig = document.getElementById('btnLoteSiguiente');
        const btnUltimo = document.getElementById('btnLoteUltimo');
        
        if (btnPrimero) btnPrimero.addEventListener('click', function(e) {
            e.preventDefault();
            if (loteActual > 1) {
                loteActual = 1;
                cargarDatos(0);
            }
        });
        if (btnAnt) btnAnt.addEventListener('click', function(e) {
            e.preventDefault();
            if (loteActual > 1) {
                loteActual--;
                cargarDatos(offsetVales - LIMITE_VALES);
            }
        });
        if (btnSig) btnSig.addEventListener('click', function(e) {
            e.preventDefault();
            if (loteActual < totalLotes) {
                loteActual++;
                cargarDatos(offsetVales + LIMITE_VALES);
            }
        });
        if (btnUltimo) btnUltimo.addEventListener('click', function(e) {
            e.preventDefault();
            if (loteActual < totalLotes) {
                loteActual = totalLotes;
                cargarDatos((totalLotes - 1) * LIMITE_VALES);
            }
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
     * Determina si el texto de una observación corresponde a un ADICIONAL.
     * Solo los adicionales se suman al total; pendientes, deja/lleva y
     * regularizaciones quedan excluidos.
     */
    function esObservacionAdicional(texto) {
        const t = String(texto || '').trim().toLowerCase();
        return t === 'a' || t.includes('adici');
    }

    // Observaciones tipo pendiente (P, DEJA, LLEVA, PT): restan del total
    function esObservacionPendiente(texto) {
        const t = String(texto || '').trim().toLowerCase();
        return t === 'p' || t.includes('pend') || t === 'de' || t.includes('deja') ||
               t === 'le' || t.includes('lleva') || t === 'pt' || t.includes('producto terminado');
    }

    // Observación Regulariza: el total neto equivale a la cantidad regularizada
    function esObservacionRegulariza(texto) {
        const t = String(texto || '').trim().toLowerCase();
        return t === 'r' || t.includes('regular');
    }
    
    /**
     * Separa los datos planos (una fila por producto) en una fila por
     * (Vale+Guia+Producto), manteniendo los 4 productos especificos como columnas.
     * Si una guia tiene 2 productos, se generan 2 filas; la cantidad de cada fila
     * va en la columna de su producto y 0 en las demas.
     * Los ADICIONALES de observacion se suman a la fila de su producto; si el
     * producto del adicional no tiene linea propia, se genera su propia fila.
     */
    function separarPorGuiaYProducto(datosPlanos) {
        // Mapa de guias: claveGuia -> { filaRef, productos: {codigo: fila}, adicionales: {codigo: cant} }
        const guias = {};
        const ordenGuias = [];
        
        datosPlanos.forEach(row => {
            const claveGuia = (row.NVale || '') + '|' + (row.NumeroGuia || '');
            
            if (!guias[claveGuia]) {
                guias[claveGuia] = { filaRef: row, productos: {}, adicionales: {}, pendientes: {}, regularizaciones: {} };
                ordenGuias.push(claveGuia);
            }
            
            // Observación de la guía: se procesa UNA SOLA VEZ por guia (los campos
            // de observacion de la guia se repiten en cada fila de producto, por eso
            // se guarda solo la primera vez que se detecta para no duplicar el total).
            const cantObs = parseFloat(row.CantidadObservada) || 0;
            const codigoObs = String(row.CodigoProductoObs || '').trim();
            const obsEsAdicional = esObservacionAdicional(row.TextoObservaciones) || esObservacionAdicional(row.ObservacionTexto);
            const obsEsPendiente = esObservacionPendiente(row.TextoObservaciones) || esObservacionPendiente(row.ObservacionTexto);
            const obsEsRegulariza = esObservacionRegulariza(row.TextoObservaciones) || esObservacionRegulariza(row.ObservacionTexto);
            if (cantObs > 0 && CODIGOS_PRODUCTO.includes(codigoObs)) {
                if (obsEsAdicional) {
                    if (!(codigoObs in guias[claveGuia].adicionales)) {
                        guias[claveGuia].adicionales[codigoObs] = cantObs;
                    }
                } else if (obsEsPendiente) {
                    if (!(codigoObs in guias[claveGuia].pendientes)) {
                        guias[claveGuia].pendientes[codigoObs] = cantObs;
                    }
                } else if (obsEsRegulariza) {
                    if (!(codigoObs in guias[claveGuia].regularizaciones)) {
                        guias[claveGuia].regularizaciones[codigoObs] = cantObs;
                    }
                }
            }
            
            // Linea de producto: crear/acumular la fila del producto (solo si esta en la lista)
            const codigo = String(row.CodigoProducto || '').trim();
            if (CODIGOS_PRODUCTO.includes(codigo)) {
                if (!guias[claveGuia].productos[codigo]) {
                    guias[claveGuia].productos[codigo] = crearFilaBase(row, codigo);
                }
                guias[claveGuia].productos[codigo].cantidades[codigo] += parseFloat(row.CantidadProducto) || 0;
            }
        });
        
        // Convertir a array plano: una fila por producto de cada guia (en orden de aparicion)
        const resultado = [];
        ordenGuias.forEach(claveGuia => {
            const guia = guias[claveGuia];
            
            // 1) Filas de productos con linea propia
            Object.keys(guia.productos).forEach(codigo => {
                const fila = guia.productos[codigo];
                // Sumar el adicional del mismo producto si existe
                if (guia.adicionales[codigo]) {
                    fila.cantidades[codigo] += guia.adicionales[codigo];
                }
                resultado.push(construirFilaReporte(fila, netoProducto(guia, fila, codigo)));
            });
            
            // 2) Adicionales de productos sin linea propia -> generar su propia fila
            Object.keys(guia.adicionales).forEach(codigoObs => {
                if (guia.productos[codigoObs]) return; // ya sumado en la fila del producto
                const fila = crearFilaBase(guia.filaRef, codigoObs);
                fila.cantidades[codigoObs] += guia.adicionales[codigoObs];
                resultado.push(construirFilaReporte(fila, netoProducto(guia, fila, codigoObs)));
            });
        });
        
        return resultado;
    }
    
    /**
     * Crea la estructura interna de una fila (datos del vale/guia + acumulador
     * de cantidades de los 4 productos) para un producto dado.
     */
    function crearFilaBase(row, codigo) {
        return {
            Fecha: row.Fecha || '',
            Turno: row.Turno || '',
            NVale: String(row.NVale || '').padStart(6, '0'),
            NumeroGuia: row.NumeroGuia || '',
            NumeroDocRef: row.NumeroDocRef || '',
            Origen: row.Origen || '',
            Empresa: row.Empresa || '',
            Chofer: row.Chofer || '',
            ObservacionTexto: row.ObservacionTexto || '',
            // Texto de observacion DETALLADO del producto (ej. "DEJA 20 UND DEL CODIGO ... GUIA ...")
            TextoObservacionesProducto: row.TextoObservacionesProducto || '',
            codigoProducto: codigo,
            cantidades: {
                '19003031': 0,
                '19002924': 0,
                '19003730': 0,
                '19003521': 0
            }
        };
    }
    
    /**
     * Total neto de un producto dentro de su guía, usando la misma fórmula del
     * CONSOLIDADO DE PRODUCTOS de la vista previa:
     *   Total = Cant GR (+ Adicional) - Pendiente
     * En observación Regulariza, el total neto es la cantidad regularizada.
     */
    function netoProducto(guia, fila, codigo) {
        let neto = (fila.cantidades[codigo] || 0);
        if (guia.pendientes && guia.pendientes[codigo]) neto -= guia.pendientes[codigo];
        if (guia.regularizaciones && guia.regularizaciones[codigo]) neto = guia.regularizaciones[codigo];
        return neto;
    }

    /**
     * Convierte una fila interna al formato plano que consume la grilla y la exportacion.
     */
    function construirFilaReporte(fila, totalNeto) {
        const p1 = fila.cantidades['19003031'];
        const p2 = fila.cantidades['19002924'];
        const p3 = fila.cantidades['19003730'];
        const p4 = fila.cantidades['19003521'];
        
        // Total Gral. con fórmula neta; fallback a la suma de columnas si no llega neto
        const totalGral = (typeof totalNeto !== 'undefined' && totalNeto !== null) ? totalNeto : (p1 + p2 + p3 + p4);
        
        return {
            Fecha: fila.Fecha,
            Turno: fila.Turno,
            NVale: fila.NVale,
            NumeroGuia: fila.NumeroGuia,
            NumeroDocRef: fila.NumeroDocRef,
            Origen: fila.Origen,
            Transportista: fila.Empresa,
            Chofer: fila.Chofer,
            // Mostrar el detalle de observacion del producto; si no tiene,
            // usar la observacion general de la guia como respaldo
            Observaciones: fila.TextoObservacionesProducto || fila.ObservacionTexto,
            Prod19003031: p1,
            Prod19002924: p2,
            Prod19003730: p3,
            Prod19003521: p4,
            TotalGeneral: totalGral
        };
    }
    
    /**
     * Calcula los valores unicos de cada columna filtrable desde el dataset
     * agrupado COMPLETO (no solo la pagina actual).
     * Esto permite que los filtros tipo Excel muestren todas las opciones
     * disponibles, igual que en el reporte principal de recepciones externas.
     */
    function calcularUniqueValues(datos) {
        const campos = ['Fecha', 'Turno', 'NVale', 'NumeroGuia', 'NumeroDocRef', 'Origen', 'Transportista', 'Chofer', 'Observaciones', 'Prod19003031', 'Prod19002924', 'Prod19003730', 'Prod19003521', 'TotalGeneral'];
        const unique = {};
        datos.forEach(row => {
            campos.forEach(campo => {
                const valor = String(row[campo] || '').trim();
                if (valor === '') return;
                if (!unique[campo]) unique[campo] = new Set();
                unique[campo].add(valor);
            });
        });
        const result = {};
        Object.keys(unique).forEach(campo => {
            result[campo] = Array.from(unique[campo]).sort((a, b) => a.localeCompare(b, 'es', { numeric: true }));
        });
        return result;
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
            tbody.innerHTML = '<tr><td colspan="15" class="text-center py-4">No se encontraron registros</td></tr>';
            return;
        }
        
        // Calcular numeración de items: filas consecutivas de la misma guía (NVale+NumeroGuia)
        // comparten el mismo número de item y se combinan con rowspan.
        // Se guarda el índice del item raíz (itemRaizIdx) para incrementar su rowspan en cada
        // fila de continuación; si se usara items[length-1] se rompería con grupos de 3+ filas.
        const items = [];
        let itemActual = 0;
        let claveAnterior = null;
        let itemRaizIdx = -1;
        datos.forEach(row => {
            const clave = String(row.NVale || '') + '|' + String(row.NumeroGuia || '');
            if (clave !== claveAnterior) {
                itemActual++;
                items.push({ num: itemActual, rowspan: 1, start: true });
                claveAnterior = clave;
                itemRaizIdx = items.length - 1;
            } else {
                items[itemRaizIdx].rowspan++;
                items.push({ num: itemActual, rowspan: 0, start: false });
            }
        });
        
        datos.forEach((row, idx) => {
            const tr = document.createElement('tr');
            
            const total = (row.TotalGeneral || 0).toFixed(2);
            const fechaFormateada = formatearFecha(row.Fecha);
            const item = items[idx];
            
            // Columna Item: inicio de grupo con rowspan; el resto queda cubierto por el rowspan
            const tdItem = item.start
                ? `<td class="celda-item" rowspan="${item.rowspan}" style="text-align:center; vertical-align:middle; font-weight:600;">${item.num}</td>`
                : '';
            
            tr.innerHTML = tdItem + `
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
