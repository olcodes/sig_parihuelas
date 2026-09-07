// reportes-picking.js - Reporte de Picking con jerarquía Vale → Guías → Productos (Solo visualización)

(function() {
    'use strict';
    
    const timestamp = new Date().toISOString();
    console.log('>>>>> REPORTE PICKING V1.0 - Timestamp:', timestamp, '<<<<<');
    console.log('[ReportePicking] Script cargado');
    
    // Variables globales
    let datosOriginales = [];
    let datosFiltrados = [];
    let paginaActual = 1;
    let totalPaginas = 1;
    let guiasExpandidas = new Set();
    let productosExpandidos = new Set();
    // Modo de renderizado: si true muestra una fila por cada guía (plano). Si false mantiene jerarquía Vale->Guías->Productos
    let modoPorGuias = true;
    
    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        console.log('[ReportePicking] Inicializando...');
        
        // Configurar filtros
        setupFiltros();
        
        // Configurar botón de exportar
        setupExportar();
        
        // Cargar datos iniciales
        cargarDatos(1);
    }
    
    // ============================================
    // CARGA DE DATOS
    // ============================================
    
    function cargarDatos(pagina) {
        paginaActual = pagina || 1;
        
        // Obtener filtros activos
        const filtros = obtenerFiltrosActivos();
        
        console.log('[ReportePicking] Cargando datos página:', paginaActual, 'filtros:', filtros);
        
        fetch(window.BASE_URL + '/reportes/obtenerPicking', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pagina: paginaActual,
                filtros: filtros,
                exportar: false
            })
        })
        .then(r => {
            console.log('[ReportePicking] Respuesta:', r.status);
            return r.json();
        })
        .then(res => {
            console.log('[ReportePicking] Datos recibidos:', res);
            if (res.success) {
                datosOriginales = res.data || [];
                datosFiltrados = datosOriginales;
                totalPaginas = res.totalPaginas || 1;
                
                console.log('[ReportePicking] Datos cargados:', datosOriginales.length, 'registros, páginas:', totalPaginas);
                
                renderizarGrilla();
                renderizarPaginacion();
            } else {
                console.error('[ReportePicking] Error en respuesta:', res.error);
                mostrarNotificacion('Error al cargar datos: ' + (res.error || 'Error desconocido'), 'error');
            }
        })
        .catch(err => {
            console.error('[ReportePicking] Error cargando datos:', err);
            mostrarNotificacion('Error de conexión al cargar datos', 'error');
        });
    }
    
    function obtenerFiltrosActivos() {
        const filtros = {};
        const inputsFiltro = document.querySelectorAll('.filtro-grilla');
        
        inputsFiltro.forEach(input => {
            const campo = input.getAttribute('data-campo');
            const valor = input.value.trim();
            if (valor) {
                filtros[campo] = valor;
            }
        });
        
        // Agregar filtro de fecha del encabezado
        const filtroFecha = document.getElementById('filtroFecha');
        if (filtroFecha && filtroFecha.value) {
            filtros['Fecha'] = filtroFecha.value;
        }
        
        // Agregar filtro de turno del encabezado
        const filtroTurno = document.getElementById('filtroTurno');
        if (filtroTurno && filtroTurno.value) {
            filtros['Turno'] = filtroTurno.value;
        }
        
        return filtros;
    }
    
    // ============================================
    // RENDERIZADO DE GRILLA
    // ============================================
    
    function renderizarGrilla() {
        const tbody = document.getElementById('grillaReporteBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (datosFiltrados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">No se encontraron registros</td></tr>';
            return;
        }

        // Mostrar una fila por cada producto de cada guía de cada vale
        // Columnas: Fecha, N Vale, N Doc Referencia, N Guia, Origen, Observaciones, Turno
        datosFiltrados.forEach((recepcion) => {
            // Formatear fecha
            let fechaFormateada = '';
            if (recepcion.Fecha) {
                const partes = recepcion.Fecha.split('-');
                if (partes.length === 3) {
                    fechaFormateada = `${partes[2]}/${partes[1]}/${partes[0]}`;
                }
            }
            
            if (recepcion.Guias && recepcion.Guias.length > 0) {
                recepcion.Guias.forEach((guia) => {
                    // Construir texto de observaciones a nivel de guía
                    let obsTexto = '';
                    if (guia.CantidadObservada && guia.CantidadObservada > 0 && guia.ObservacionTexto) {
                        const tipo = (guia.ObservacionTexto || '').toUpperCase();
                        const cantidad = guia.CantidadObservada || 0;
                        const codigo = guia.CodigoProductoObs || '';
                        const numeroGuia = guia.NumeroGuia || '';
                        
                        obsTexto = `${tipo} ${cantidad} UND DEL CODIGO ${codigo} GUIA ${numeroGuia}`;
                    } else if (guia.ObservacionTexto) {
                        obsTexto = guia.ObservacionTexto;
                    }
                    
                    if (guia.Productos && guia.Productos.length > 0) {
                        guia.Productos.forEach((producto) => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${fechaFormateada}</td>
                                <td>${recepcion.NVale || ''}</td>
                                <td>${guia.NumeroDocRef || guia.NumeroDocReferencia || ''}</td>
                                <td>${guia.NumeroGuia || ''}</td>
                                <td>${recepcion.Origen || ''}</td>
                                <td>${obsTexto}</td>
                                <td>${recepcion.Turno || ''}</td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        // Guía sin productos
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${fechaFormateada}</td>
                            <td>${recepcion.NVale || ''}</td>
                            <td>${guia.NumeroDocRef || guia.NumeroDocReferencia || ''}</td>
                            <td>${guia.NumeroGuia || ''}</td>
                            <td>${recepcion.Origen || ''}</td>
                            <td>${obsTexto}</td>
                            <td>${recepcion.Turno || ''}</td>
                        `;
                        tbody.appendChild(tr);
                    }
                });
            }
        });
    }
    
    function crearFilaVale(recepcion, index) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-vale-id', recepcion.Id);
        tr.setAttribute('data-nivel', 'vale');
        tr.className = 'vale-row';
        
        // Formatear fecha a dd/mm/yyyy
        let fechaFormateada = '';
        if (recepcion.Fecha) {
            const partes = recepcion.Fecha.split('-');
            if (partes.length === 3) {
                fechaFormateada = `${partes[2]}/${partes[1]}/${partes[0]}`;
            }
        }
        
        // Verificar si tiene guías
        const tieneGuias = recepcion.Guias && recepcion.Guias.length > 0;
        
        // Mostrar número de guías (resumen)
        const numeroGuias = tieneGuias ? recepcion.Guias.length : 0;
        const observacionResumen = tieneGuias && recepcion.Guias[0] ? (recepcion.Guias[0].ObservacionTexto || '') : '';
        
        tr.innerHTML = `
            <td>${fechaFormateada}</td>
            <td>${recepcion.NVale || ''}</td>
            <td>${numeroGuias} guía(s)</td>
            <td>${recepcion.Origen || ''}</td>
            <td>${observacionResumen}</td>
            <td>${recepcion.Turno || ''}</td>
        `;
        
        // Event listener para doble clic en la fila (expandir guías)
        if (tieneGuias) {
            tr.addEventListener('dblclick', (e) => {
                console.log('[ReportePicking] Doble clic en vale ID:', recepcion.Id);
                toggleGuias(recepcion.Id);
            });
            tr.style.cursor = 'pointer';
            tr.title = 'Doble clic para ver guías';
        }
        
        return tr;
    }
    
    function toggleGuias(valeId) {
        console.log('[ReportePicking] Toggle guías para vale:', valeId);
        
        const tbody = document.getElementById('grillaReporteBody');
        const filaVale = tbody.querySelector(`tr[data-vale-id="${valeId}"][data-nivel="vale"]`);
        const filasGuias = tbody.querySelectorAll(`tr[data-vale-id="${valeId}"][data-nivel="guia"]`);
        
        if (!filaVale) {
            console.error('[ReportePicking] No se encontró la fila del vale');
            return;
        }
        
        if (filasGuias.length > 0) {
            // Alternar visibilidad
            const estanOcultas = filasGuias[0].style.display === 'none';
            
            filasGuias.forEach(fila => {
                fila.style.display = estanOcultas ? 'table-row' : 'none';
                
                // También ocultar productos si se está ocultando la guía
                if (!estanOcultas) {
                    const guiaId = fila.getAttribute('data-guia-id');
                    const filasProductos = tbody.querySelectorAll(`tr[data-guia-id="${guiaId}"][data-nivel="producto"]`);
                    filasProductos.forEach(fp => fp.style.display = 'none');
                    productosExpandidos.delete(parseInt(guiaId));
                }
            });
            
            if (estanOcultas) {
                guiasExpandidas.add(valeId);
            } else {
                guiasExpandidas.delete(valeId);
            }
        } else {
            // Cargar guías
            cargarGuias(valeId, filaVale);
        }
    }
    
    function cargarGuias(valeId, filaVale) {
        console.log('[ReportePicking] Cargando guías para vale:', valeId);
        
        const recepcion = datosOriginales.find(r => r.Id == valeId);
        
        if (!recepcion || !recepcion.Guias || recepcion.Guias.length === 0) {
            console.warn('[ReportePicking] No hay guías para este vale');
            mostrarNotificacion('Este vale no tiene guías registradas', 'info');
            return;
        }
        
        const tbody = document.getElementById('grillaReporteBody');
        
        // Crear cabecera de guías
        const cabecera = crearCabeceraGuias(valeId);
        let ultimaFila = filaVale;
        insertarDespuesDe(ultimaFila, cabecera);
        ultimaFila = cabecera;
        
        // Crear filas de guías
        recepcion.Guias.forEach((guia, index) => {
            const filaGuia = crearFilaGuia(guia, valeId, recepcion, index);
            insertarDespuesDe(ultimaFila, filaGuia);
            ultimaFila = filaGuia;
        });
        
        guiasExpandidas.add(valeId);
        
        console.log('[ReportePicking] Guías cargadas:', recepcion.Guias.length);
    }
    
    function crearCabeceraGuias(valeId) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-vale-id', valeId);
        tr.setAttribute('data-nivel', 'guia');
        tr.className = 'guia-row cabecera-guias';
        
        tr.innerHTML = `
            <td colspan="6" style="text-align:center; font-weight:bold; background-color:#b0bec5;">
                GUÍAS DEL VALE
            </td>
        `;
        
        return tr;
    }
    
    function crearFilaGuia(guia, valeId, recepcion, index) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-vale-id', valeId);
        tr.setAttribute('data-guia-id', guia.Id);
        tr.setAttribute('data-nivel', 'guia');
        tr.className = 'guia-row';
        
        // Formatear fecha
        let fechaFormateada = '';
        if (recepcion.Fecha) {
            const partes = recepcion.Fecha.split('-');
            if (partes.length === 3) {
                fechaFormateada = `${partes[2]}/${partes[1]}/${partes[0]}`;
            }
        }
        
        const tieneProductos = guia.Productos && guia.Productos.length > 0;
        const numeroProductos = tieneProductos ? guia.Productos.length : 0;
        
        // Si tiene productos, mostrar una fila por cada producto
        if (tieneProductos) {
            guia.Productos.forEach((producto) => {
                tr.innerHTML = `
                    <td>${recepcion.Turno || ''}</td>
                    <td>${recepcion.NVale || ''}</td>
                    <td>${guia.NumeroGuia || ''}</td>
                    <td>${recepcion.Origen || ''}</td>
                    <td>${recepcion.Empresa || ''}</td>
                    <td>${recepcion.Chofer || ''}</td>
                    <td>${producto.CodigoProducto || ''}</td>
                    <td>${producto.DescripcionProducto || ''}</td>
                    <td>${producto.Cantidad || ''}</td>
                `;
            });
        } else {
            tr.innerHTML = `
                <td>${recepcion.Turno || ''}</td>
                <td>${recepcion.NVale || ''}</td>
                <td>${guia.NumeroGuia || ''}</td>
                <td>${recepcion.Origen || ''}</td>
                <td>${recepcion.Empresa || ''}</td>
                <td>${recepcion.Chofer || ''}</td>
                <td></td>
                <td></td>
                <td></td>
            `;
        }
        
        return tr;
    }
    
    function toggleProductos(guiaId, valeId) {
        console.log('[ReportePicking] Toggle productos para guía:', guiaId);
        
        const tbody = document.getElementById('grillaReporteBody');
        const filaGuia = tbody.querySelector(`tr[data-guia-id="${guiaId}"][data-nivel="guia"]:not(.cabecera-productos)`);
        const filasProductos = tbody.querySelectorAll(`tr[data-guia-id="${guiaId}"][data-nivel="producto"]`);
        
        if (!filaGuia) {
            console.error('[ReportePicking] No se encontró la fila de la guía');
            return;
        }
        
        if (filasProductos.length > 0) {
            // Alternar visibilidad
            const estanOcultos = filasProductos[0].style.display === 'none';
            
            filasProductos.forEach(fila => {
                fila.style.display = estanOcultos ? 'table-row' : 'none';
            });
            
            if (estanOcultos) {
                productosExpandidos.add(parseInt(guiaId));
            } else {
                productosExpandidos.delete(parseInt(guiaId));
            }
        } else {
            // Cargar productos
            cargarProductos(guiaId, valeId, filaGuia);
        }
    }
    
    function cargarProductos(guiaId, valeId, filaGuia) {
        console.log('[ReportePicking] Cargando productos para guía:', guiaId);
        
        const recepcion = datosOriginales.find(r => r.Id == valeId);
        if (!recepcion) return;
        
        const guia = recepcion.Guias.find(g => g.Id == guiaId);
        if (!guia || !guia.Productos || guia.Productos.length === 0) {
            console.warn('[ReportePicking] No hay productos para esta guía');
            mostrarNotificacion('Esta guía no tiene productos registrados', 'info');
            return;
        }
        
        const tbody = document.getElementById('grillaReporteBody');
        
        // Crear cabecera de productos
        const cabecera = crearCabeceraProductos(guiaId, valeId);
        let ultimaFila = filaGuia;
        insertarDespuesDe(ultimaFila, cabecera);
        ultimaFila = cabecera;
        
        // Crear filas de productos
        guia.Productos.forEach((producto, index) => {
            const filaProducto = crearFilaProducto(producto, guiaId, valeId, recepcion, guia, index);
            insertarDespuesDe(ultimaFila, filaProducto);
            ultimaFila = filaProducto;
        });
        
        productosExpandidos.add(parseInt(guiaId));
        
        console.log('[ReportePicking] Productos cargados:', guia.Productos.length);
    }
    
    function crearCabeceraProductos(guiaId, valeId) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-vale-id', valeId);
        tr.setAttribute('data-guia-id', guiaId);
        tr.setAttribute('data-nivel', 'producto');
        tr.className = 'producto-row cabecera-productos';
        
        tr.innerHTML = `
            <td colspan="6" style="text-align:center; font-weight:bold; background-color:#a5d6a7;">
                PRODUCTOS DE LA GUÍA
            </td>
        `;
        
        return tr;
    }
    
    function crearFilaProducto(producto, guiaId, valeId, recepcion, guia, index) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-vale-id', valeId);
        tr.setAttribute('data-guia-id', guiaId);
        tr.setAttribute('data-producto-id', producto.Id || index);
        tr.setAttribute('data-nivel', 'producto');
        tr.className = 'producto-row';
        
        // Formatear fecha
        let fechaFormateada = '';
        if (recepcion.Fecha) {
            const partes = recepcion.Fecha.split('-');
            if (partes.length === 3) {
                fechaFormateada = `${partes[2]}/${partes[1]}/${partes[0]}`;
            }
        }
        
        tr.innerHTML = `
            <td>${fechaFormateada}</td>
            <td>${recepcion.NVale || ''}</td>
            <td>${guia.NumeroGuia || ''}</td>
            <td>${recepcion.Origen || ''}</td>
            <td>${producto.DescripcionProducto || ''} (${producto.CodigoProducto || ''})</td>
            <td>${recepcion.Turno || ''}</td>
        `;
        
        return tr;
    }
    
    function insertarDespuesDe(referencia, nuevo) {
        if (referencia.nextSibling) {
            referencia.parentNode.insertBefore(nuevo, referencia.nextSibling);
        } else {
            referencia.parentNode.appendChild(nuevo);
        }
    }
    
    // ============================================
    // FILTROS
    // ============================================
    
    function setupFiltros() {
        const inputsFiltro = document.querySelectorAll('.filtro-grilla');
        
        inputsFiltro.forEach(input => {
            input.addEventListener('keyup', debounce(function() {
                cargarDatos(1);
            }, 500));
            
            // Para campos de fecha, aplicar filtro inmediatamente
            if (input.type === 'date') {
                input.addEventListener('change', function() {
                    cargarDatos(1);
                });
            }
        });
        
        // Filtro de fecha (encabezado)
        const filtroFecha = document.getElementById('filtroFecha');
        if (filtroFecha) {
            filtroFecha.addEventListener('change', function() {
                cargarDatos(1);
            });
        }
        
        // Filtro de turno (encabezado)
        const filtroTurno = document.getElementById('filtroTurno');
        if (filtroTurno) {
            filtroTurno.addEventListener('change', function() {
                cargarDatos(1);
            });
        }
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // ============================================
    // PAGINACIÓN
    // ============================================
    
    function renderizarPaginacion() {
        const paginacionTop = document.querySelector('#paginacionReporte');
        const paginacionBottom = document.querySelector('nav[aria-label="Paginación de reporte"] .pagination');
        
        const html = generarHTMLPaginacion();
        
        if (paginacionTop) paginacionTop.innerHTML = html;
        if (paginacionBottom) paginacionBottom.innerHTML = html;
        
        // Agregar eventos a los botones
        document.querySelectorAll('.pagination .page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const pagina = parseInt(link.getAttribute('data-pagina'));
                if (pagina && pagina >= 1 && pagina <= totalPaginas) {
                    cargarDatos(pagina);
                }
            });
        });
    }
    
    function generarHTMLPaginacion() {
        if (totalPaginas <= 1) return '';
        
        let html = '';
        
        // Botón anterior
        html += `<li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-pagina="${paginaActual - 1}" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>`;
        
        // Páginas
        for (let i = 1; i <= totalPaginas; i++) {
            // Mostrar solo algunas páginas alrededor de la actual
            if (i === 1 || i === totalPaginas || (i >= paginaActual - 2 && i <= paginaActual + 2)) {
                html += `<li class="page-item ${i === paginaActual ? 'active' : ''}">
                    <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                </li>`;
            } else if (i === paginaActual - 3 || i === paginaActual + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        // Botón siguiente
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
        const btnExportar = document.getElementById('btnExportExcel');
        if (btnExportar) {
            btnExportar.addEventListener('click', exportarExcel);
        }
    }
    
    function exportarExcel() {
        console.log('[ReportePicking] Exportando a Excel desde servidor...');
        
        // Obtener filtros activos
        const filtros = obtenerFiltrosActivos();
        
        // Construir URL con parámetros
        const params = new URLSearchParams(filtros);
        const url = window.BASE_URL + '/reportes/exportarExcelPicking?' + params.toString();
        
        // Redirigir para descargar el archivo
        window.location.href = url;
    }
    
    // ============================================
    // UTILIDADES
    // ============================================
    
    function mostrarNotificacion(mensaje, tipo) {
        console.log('[ReportePicking] Notificación:', tipo, '-', mensaje);
        
        // Si existe la función global de notificaciones, usarla
        if (typeof window.mostrarNotificacion === 'function') {
            window.mostrarNotificacion(mensaje, tipo);
            return;
        }
        
        // Fallback: alert simple
        alert(mensaje);
    }
    
})();
