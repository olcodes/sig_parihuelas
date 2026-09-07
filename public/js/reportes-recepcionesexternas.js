// reportes-recepcionesexternas.js - Reporte de Recepciones Externas con jerarquía Vale → Guías → Productos

(function() {
    'use strict';
    
    console.log('[ReporteRecepcionesExternas] Script cargado');
    
    // Variables globales
    let datosOriginales = [];
    let datosFiltrados = [];
    let paginaActual = 1;
    let totalPaginas = 1;
    let datosSelectores = {};
    let recepcionEnEdicion = null;
    let guiasExpandidas = new Set();
    let productosExpandidos = new Set();
    
    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        console.log('[ReporteRecepcionesExternas] Inicializando...');
        
        // Cargar datos de selectores (orígenes, empresas, choferes, productos, etc.)
        cargarDatosSelectores();
        
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
    
    function cargarDatosSelectores() {
        console.log('[ReporteRecepcionesExternas] Cargando selectores...');
        fetch(window.BASE_URL + '/reportes/obtenerDatosSelectoresRecepcionesExternas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(r => {
            console.log('[ReporteRecepcionesExternas] Respuesta selectores:', r.status);
            return r.json();
        })
        .then(res => {
            console.log('[ReporteRecepcionesExternas] Datos selectores:', res);
            if (res.success) {
                datosSelectores = res.data;
                console.log('[ReporteRecepcionesExternas] Datos de selectores cargados:', datosSelectores);
            } else {
                console.error('[ReporteRecepcionesExternas] Error en respuesta:', res);
            }
        })
        .catch(err => {
            console.error('[ReporteRecepcionesExternas] Error cargando selectores:', err);
        });
    }
    
    function cargarDatos(pagina) {
        paginaActual = pagina || 1;
        
        // Obtener filtros activos
        const filtros = obtenerFiltrosActivos();
        
        console.log('[ReporteRecepcionesExternas] Cargando datos página:', paginaActual, 'filtros:', filtros);
        
        fetch(window.BASE_URL + '/reportes/obtenerRecepcionesExternas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pagina: paginaActual,
                filtros: filtros,
                exportar: false
            })
        })
        .then(r => {
            console.log('[ReporteRecepcionesExternas] Respuesta:', r.status);
            return r.json();
        })
        .then(res => {
            console.log('[ReporteRecepcionesExternas] Datos recibidos:', res);
            if (res.success) {
                datosOriginales = res.data || [];
                datosFiltrados = datosOriginales;
                totalPaginas = res.totalPaginas || 1;
                
                console.log('[ReporteRecepcionesExternas] Datos cargados:', datosOriginales.length, 'registros, páginas:', totalPaginas);
                
                // DEBUG: Verificar si los datos tienen Guias
                if (datosOriginales.length > 0) {
                    console.log('[ReporteRecepcionesExternas] Primer registro:', datosOriginales[0]);
                    console.log('[ReporteRecepcionesExternas] ¿Tiene Guias?', datosOriginales[0].Guias);
                    if (datosOriginales[0].Guias && datosOriginales[0].Guias.length > 0) {
                        console.log('[ReporteRecepcionesExternas] Primera guía:', datosOriginales[0].Guias[0]);
                        console.log('[ReporteRecepcionesExternas] ¿Primera guía tiene Productos?', datosOriginales[0].Guias[0].Productos);
                    }
                }
                
                renderizarGrilla();
                renderizarPaginacion();
            } else {
                console.error('[ReporteRecepcionesExternas] Error en respuesta:', res.error);
                mostrarNotificacion('Error al cargar datos: ' + (res.error || 'Error desconocido'), 'error');
            }
        })
        .catch(err => {
            console.error('[ReporteRecepcionesExternas] Error cargando datos:', err);
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
            tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4">No se encontraron registros</td></tr>';;
            return;
        }
        
        datosFiltrados.forEach((recepcion, index) => {
            tbody.appendChild(crearFilaRecepcion(recepcion, index));
        });
    }
    
    function crearFilaRecepcion(recepcion, index) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-recepcion-id', recepcion.Id);
        tr.setAttribute('data-nivel', 'recepcion');
        tr.className = 'recepcion-row';
        
        // Determinar color de estado
        let estadoColor = '';
        let estadoTexto = (recepcion.Estado || 'activo').toUpperCase();
        if (estadoTexto === 'ANULADO') {
            estadoColor = 'background: #ffe0e0;';
            tr.style.textDecoration = 'line-through';
            tr.style.opacity = '0.7';
        }
        
        // Verificar si tiene guías
        const tieneGuias = recepcion.Guias && recepcion.Guias.length > 0;
        const iconoChevron = guiasExpandidas.has(recepcion.Id) ? 'bi-chevron-up' : 'bi-chevron-down';
        
        console.log('[ReporteRecepcionesExternas] Creando fila para recepción ID:', recepcion.Id, 'NVale:', recepcion.NVale);
        console.log('[ReporteRecepcionesExternas] ¿Tiene Guias?', tieneGuias, 'Cantidad:', recepcion.Guias ? recepcion.Guias.length : 0);
        
        tr.innerHTML = `
            <td style="text-align:center; vertical-align:middle;">
                <div class="d-flex flex-column gap-1 align-items-center">
                    <div class="dropdown" data-bs-boundary="viewport">
                        <button class="btn btn-sm btn-primary action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="true">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="position: absolute;">
                            <li><a class="dropdown-item" href="#" data-action="detalle"><i class="bi bi-eye me-2"></i>Ver Detalle</a></li>
                            <li><a class="dropdown-item" href="#" data-action="editar"><i class="bi bi-pencil me-2"></i>Editar</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" data-action="anular"><i class="bi bi-x-circle me-2"></i>Anular</a></li>
                        </ul>
                    </div>
                    ${tieneGuias ? `<button class="btn btn-sm btn-info toggle-guias" title="Expandir/Contraer guías" style="width:32px; height:24px; padding:2px;">
                        <i class="${iconoChevron}" style="font-size:0.9rem;"></i>
                    </button>` : ''}
                </div>
            </td>
            <td>${recepcion.NVale || ''}</td>
            <td>${recepcion.Fecha || ''}</td>
            <td>${recepcion.Hora || ''}</td>
            <td>${recepcion.Turno || ''}</td>
            <td>${recepcion.Origen || ''}</td>
            <td>${recepcion.Recepcionista || ''}</td>
            <td>${recepcion.RUC || ''}</td>
            <td>${recepcion.Empresa || ''}</td>
            <td>${recepcion.Chofer || ''}</td>
            <td>${recepcion.Brevete || ''}</td>
            <td style="${estadoColor}">${estadoTexto}</td>
        `;
        
        // Event listeners para acciones
        const dropdownItems = tr.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const action = item.getAttribute('data-action');
                if (action) {
                    manejarAccion(action, recepcion);
                }
            });
        });
        
        // Event listener para expandir/contraer guías (clic en botón)
        const btnToggle = tr.querySelector('.toggle-guias');
        if (btnToggle) {
            btnToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleGuias(recepcion.Id);
            });
        }
        
        // Event listener para doble clic en la fila (solo si tiene guías)
        if (tieneGuias) {
            tr.addEventListener('dblclick', (e) => {
                console.log('[ReporteRecepcionesExternas] ¡DOBLE CLIC detectado en recepción ID:', recepcion.Id);
                console.log('[ReporteRecepcionesExternas] Target del evento:', e.target);
                console.log('[ReporteRecepcionesExternas] ¿Es dropdown?', e.target.closest('.dropdown'));
                // Evitar que se active si se hace doble clic en el dropdown
                if (!e.target.closest('.dropdown')) {
                    console.log('[ReporteRecepcionesExternas] Llamando a toggleGuias...');
                    toggleGuias(recepcion.Id);
                }
            });
            // Agregar cursor pointer para indicar que es clickeable
            tr.style.cursor = 'pointer';
            console.log('[ReporteRecepcionesExternas] Evento dblclick agregado para recepción:', recepcion.Id);
        } else {
            console.log('[ReporteRecepcionesExternas] NO se agregó evento dblclick (no tiene guías)');
        }
        
        return tr;
    }
    
    function toggleGuias(recepcionId) {
        console.log('[ReporteRecepcionesExternas] ===== Toggle guías para recepción:', recepcionId, '=====');
        
        const tbody = document.getElementById('grillaReporteBody');
        const filaRecepcion = tbody.querySelector(`tr[data-recepcion-id="${recepcionId}"][data-nivel="recepcion"]`);
        const filasGuias = tbody.querySelectorAll(`tr[data-recepcion-id="${recepcionId}"][data-nivel="guia"]`);
        const btnToggle = filaRecepcion?.querySelector('.toggle-guias i');
        
        console.log('[ReporteRecepcionesExternas] Fila recepción encontrada:', !!filaRecepcion);
        console.log('[ReporteRecepcionesExternas] Filas guías existentes:', filasGuias.length);
        console.log('[ReporteRecepcionesExternas] Botón toggle encontrado:', !!btnToggle);
        
        if (!filaRecepcion) {
            console.error('[ReporteRecepcionesExternas] No se encontró la fila de recepción');
            return;
        }
        
        if (filasGuias.length > 0) {
            // Si ya existen, alternar visibilidad (incluyendo cabecera)
            const estanOcultas = filasGuias[0].style.display === 'none';
            
            filasGuias.forEach(fila => {
                fila.style.display = estanOcultas ? 'table-row' : 'none';
                
                // También ocultar productos de esta guía si se está ocultando
                if (!estanOcultas) {
                    const guiaId = fila.getAttribute('data-guia-id');
                    const filasProductos = tbody.querySelectorAll(`tr[data-guia-id="${guiaId}"][data-nivel="producto"]`);
                    filasProductos.forEach(fp => fp.style.display = 'none');
                    productosExpandidos.delete(parseInt(guiaId));
                }
            });
            
            // Cambiar icono y estado
            if (estanOcultas) {
                btnToggle.className = 'bi bi-chevron-up';
                guiasExpandidas.add(recepcionId);
            } else {
                btnToggle.className = 'bi bi-chevron-down';
                guiasExpandidas.delete(recepcionId);
            }
        } else {
            // Cargar guías del servidor
            cargarGuias(recepcionId, filaRecepcion);
        }
    }
    
    function cargarGuias(recepcionId, filaRecepcion) {
        console.log('[ReporteRecepcionesExternas] ===== Cargando guías para recepción:', recepcionId, '=====');
        console.log('[ReporteRecepcionesExternas] datosOriginales:', datosOriginales.length, 'registros');
        
        // Buscar la recepción en datosOriginales
        const recepcion = datosOriginales.find(r => r.Id == recepcionId);
        console.log('[ReporteRecepcionesExternas] Recepción encontrada:', !!recepcion);
        
        if (!recepcion) {
            console.error('[ReporteRecepcionesExternas] No se encontró la recepción en datosOriginales');
            return;
        }
        
        console.log('[ReporteRecepcionesExternas] Recepción completa:', recepcion);
        console.log('[ReporteRecepcionesExternas] ¿Tiene propiedad Guias?', 'Guias' in recepcion);
        console.log('[ReporteRecepcionesExternas] Guias:', recepcion.Guias);
        console.log('[ReporteRecepcionesExternas] Cantidad de guías:', recepcion.Guias ? recepcion.Guias.length : 0);
        
        if (!recepcion.Guias || recepcion.Guias.length === 0) {
            console.warn('[ReporteRecepcionesExternas] No hay guías para esta recepción');
            mostrarNotificacion('Esta recepción no tiene guías registradas', 'info');
            return;
        }
        
        const tbody = document.getElementById('grillaReporteBody');
        const btnToggle = filaRecepcion.querySelector('.toggle-guias i');
        
        // Crear cabecera de guías
        const cabecera = crearCabeceraGuias(recepcionId);
        let ultimaFila = filaRecepcion;
        insertarDespuesDe(ultimaFila, cabecera);
        ultimaFila = cabecera;
        
        // Crear filas de guías
        recepcion.Guias.forEach((guia, index) => {
            const filaGuia = crearFilaGuia(guia, recepcionId, index);
            insertarDespuesDe(ultimaFila, filaGuia);
            ultimaFila = filaGuia;
        });
        
        // Cambiar icono y agregar a expandidas
        btnToggle.className = 'bi bi-chevron-up';
        guiasExpandidas.add(recepcionId);
        
        console.log('[ReporteRecepcionesExternas] Guías cargadas:', recepcion.Guias.length);
    }
    
    function crearCabeceraGuias(recepcionId) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-recepcion-id', recepcionId);
        tr.setAttribute('data-nivel', 'guia');
        tr.className = 'guia-row cabecera-guias';
        tr.style.backgroundColor = '#e3f2fd';
        tr.style.fontWeight = 'bold';
        
        tr.innerHTML = `
            <td style="text-align:center; padding-left:20px;"></td>
            <td colspan="2" style="padding-left:30px;">N° Guía</td>
            <td colspan="2">Observación</td>
            <td colspan="2">Código Obs.</td>
            <td colspan="4">Cantidad Obs.</td>
        `;
        
        return tr;
    }
    
    function crearFilaGuia(guia, recepcionId, index) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-recepcion-id', recepcionId);
        tr.setAttribute('data-guia-id', guia.Id);
        tr.setAttribute('data-nivel', 'guia');
        tr.className = 'guia-row';
        
        // Verificar si tiene productos
        const tieneProductos = guia.Productos && guia.Productos.length > 0;
        const iconoChevron = productosExpandidos.has(guia.Id) ? 'bi-chevron-up' : 'bi-chevron-down';
        
        // Construir texto de observaciones concatenado
        console.log('Guía:', guia.NumeroGuia, 'ObservacionTexto:', guia.ObservacionTexto, 'CantidadObservada:', guia.CantidadObservada, 'CodigoProductoObs:', guia.CodigoProductoObs);
        
        let obsTexto = '-';
        if (guia.CantidadObservada && guia.CantidadObservada > 0 && guia.ObservacionTexto) {
            const tipo = (guia.ObservacionTexto || '').toUpperCase();
            const cantidad = guia.CantidadObservada || 0;
            const codigo = guia.CodigoProductoObs || '';
            const numeroGuia = guia.NumeroGuia || '';
            obsTexto = `${tipo} ${cantidad} UND DEL CODIGO ${codigo} GUIA ${numeroGuia}`;
            console.log('Texto concatenado construido:', obsTexto);
        } else if (guia.ObservacionTexto) {
            obsTexto = guia.ObservacionTexto;
            console.log('Usando ObservacionTexto directo:', obsTexto);
        }
        
        tr.innerHTML = `
            <td style="text-align:center; padding-left:20px; vertical-align:middle;">
                ${tieneProductos ? `<button class="btn btn-sm btn-secondary toggle-productos" title="Expandir/Contraer productos" style="width:32px; height:24px; padding:2px;">
                    <i class="${iconoChevron}" style="font-size:0.9rem;"></i>
                </button>` : ''}
            </td>
            <td colspan="2" style="padding-left:30px;">${guia.NumeroGuia || ''}</td>
            <td colspan="2">${obsTexto}</td>
            <td colspan="2">${guia.CodigoProductoObs || '-'}</td>
            <td colspan="4" style="text-align:center;">${guia.CantidadObservada || 0}</td>
        `;
        
        // Event listener para expandir/contraer productos (clic en botón)
        const btnToggle = tr.querySelector('.toggle-productos');
        if (btnToggle) {
            btnToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleProductos(guia.Id);
            });
        }
        
        // Event listener para doble clic en la fila (solo si tiene productos)
        if (tieneProductos) {
            tr.addEventListener('dblclick', (e) => {
                toggleProductos(guia.Id);
            });
            // Agregar cursor pointer para indicar que es clickeable
            tr.style.cursor = 'pointer';
        }
        
        return tr;
    }
    
    function toggleProductos(guiaId) {
        console.log('[ReporteRecepcionesExternas] Toggle productos para guía:', guiaId);
        
        const tbody = document.getElementById('grillaReporteBody');
        const filaGuia = tbody.querySelector(`tr[data-guia-id="${guiaId}"][data-nivel="guia"]`);
        const filasProductos = tbody.querySelectorAll(`tr[data-guia-id="${guiaId}"][data-nivel="producto"]`);
        const btnToggle = filaGuia?.querySelector('.toggle-productos i');
        
        if (!filaGuia) {
            console.error('[ReporteRecepcionesExternas] No se encontró la fila de guía');
            return;
        }
        
        if (filasProductos.length > 0) {
            // Alternar visibilidad (incluyendo cabecera)
            const estanOcultos = filasProductos[0].style.display === 'none';
            
            filasProductos.forEach(fila => {
                fila.style.display = estanOcultos ? 'table-row' : 'none';
            });
            
            // Cambiar icono
            if (estanOcultos) {
                btnToggle.className = 'bi bi-chevron-up';
                productosExpandidos.add(guiaId);
            } else {
                btnToggle.className = 'bi bi-chevron-down';
                productosExpandidos.delete(guiaId);
            }
        } else {
            // Cargar productos
            cargarProductos(guiaId, filaGuia);
        }
    }
    
    function cargarProductos(guiaId, filaGuia) {
        console.log('[ReporteRecepcionesExternas] Cargando productos para guía:', guiaId);
        
        // Buscar la guía en datosOriginales
        let guia = null;
        for (const recepcion of datosOriginales) {
            if (recepcion.Guias) {
                guia = recepcion.Guias.find(g => g.Id == guiaId);
                if (guia) break;
            }
        }
        
        if (!guia || !guia.Productos || guia.Productos.length === 0) {
            console.warn('[ReporteRecepcionesExternas] No hay productos para esta guía');
            mostrarNotificacion('Esta guía no tiene productos registrados', 'info');
            return;
        }
        
        const tbody = document.getElementById('grillaReporteBody');
        const btnToggle = filaGuia.querySelector('.toggle-productos i');
        
        // Crear cabecera de productos
        const cabecera = crearCabeceraProductos(guiaId);
        let ultimaFila = filaGuia;
        insertarDespuesDe(ultimaFila, cabecera);
        ultimaFila = cabecera;
        
        // Crear filas de productos
        guia.Productos.forEach((producto, index) => {
            const filaProducto = crearFilaProducto(producto, guiaId, index);
            insertarDespuesDe(ultimaFila, filaProducto);
            ultimaFila = filaProducto;
        });
        
        // Cambiar icono
        btnToggle.className = 'bi bi-chevron-up';
        productosExpandidos.add(guiaId);
        
        console.log('[ReporteRecepcionesExternas] Productos cargados:', guia.Productos.length);
    }
    
    function crearCabeceraProductos(guiaId) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-guia-id', guiaId);
        tr.setAttribute('data-nivel', 'producto');
        tr.className = 'producto-row cabecera-productos';
        tr.style.backgroundColor = '#f3e5f5';
        tr.style.fontWeight = 'bold';
        
        tr.innerHTML = `
            <td style="padding-left:40px;"></td>
            <td colspan="2" style="padding-left:50px;">Código</td>
            <td colspan="3">Descripción Producto</td>
            <td style="text-align:center;">Cantidad</td>
            <td colspan="4">Unidad Medida</td>
        `;
        
        return tr;
    }
    
    function crearFilaProducto(producto, guiaId, index) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-guia-id', guiaId);
        tr.setAttribute('data-producto-id', producto.Id);
        tr.setAttribute('data-nivel', 'producto');
        tr.className = 'producto-row';
        
        tr.innerHTML = `
            <td style="padding-left:40px;"></td>
            <td colspan="2" style="padding-left:50px;">${producto.CodigoProducto || ''}</td>
            <td colspan="3">${producto.DescripcionProducto || ''}</td>
            <td style="text-align:center;">${producto.Cantidad || 0}</td>
            <td colspan="4">${producto.UnidadMedida || ''}</td>
        `;
        
        return tr;
    }
    
    function insertarDespuesDe(nodoReferencia, nuevoNodo) {
        const siguiente = nodoReferencia.nextSibling;
        if (siguiente) {
            nodoReferencia.parentNode.insertBefore(nuevoNodo, siguiente);
        } else {
            nodoReferencia.parentNode.appendChild(nuevoNodo);
        }
    }
    
    // ============================================
    // ACCIONES (Ver Detalle, Editar, Anular)
    // ============================================
    
    function manejarAccion(action, recepcion) {
        switch(action) {
            case 'detalle':
                verDetalle(recepcion);
                break;
            case 'editar':
                editarRecepcion(recepcion);
                break;
            case 'anular':
                anularRecepcion(recepcion);
                break;
        }
    }
    
    function verDetalle(recepcion) {
        console.log('[ReporteRecepcionesExternas] Ver detalle de recepción:', recepcion.Id);
        
        // Abrir la ventana PRIMERO (antes del fetch) para evitar que el navegador la bloquee
        const ventanaPrevia = window.open('', '_blank', 'width=900,height=700');
        
        if (!ventanaPrevia) {
            mostrarNotificacion('No se pudo abrir la ventana de vista previa. Verifique que no esté bloqueando ventanas emergentes.', 'warning');
            return;
        }
        
        // Mostrar un mensaje de carga mientras se obtienen los datos
        ventanaPrevia.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Cargando...</title>
                <style>
                    body { 
                        display: flex; 
                        justify-content: center; 
                        align-items: center; 
                        height: 100vh; 
                        margin: 0;
                        font-family: Arial, sans-serif;
                        background: #f3f4f6;
                    }
                    .loader {
                        text-align: center;
                    }
                    .spinner {
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #3b82f6;
                        border-radius: 50%;
                        width: 40px;
                        height: 40px;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 20px;
                    }
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            </head>
            <body>
                <div class="loader">
                    <div class="spinner"></div>
                    <p>Cargando vista previa...</p>
                </div>
            </body>
            </html>
        `);
        
        // Cargar los datos completos de la recepción
        fetch(window.BASE_URL + '/reportes/obtenerRecepcionExternaId', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: recepcion.Id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                // Pasar la ventana ya abierta a la función de vista previa
                mostrarVistaPrevia(res.data, ventanaPrevia);
            } else {
                ventanaPrevia.close();
                mostrarNotificacion('Error al cargar el detalle', 'error');
            }
        })
        .catch(err => {
            console.error('[ReporteRecepcionesExternas] Error:', err);
            ventanaPrevia.close();
            mostrarNotificacion('Error de conexión', 'error');
        });
    }
    
    function mostrarVistaPrevia(recepcion, ventanaExistente = null) {
        // Crear HTML de la vista previa (similar al vale de la interfaz principal)
        const baseUrl = window.BASE_URL || '';
        const logoUrl = baseUrl + '/img/Logo-Lavoro-1536x442.png';
        
        // Consolidar productos y construir observaciones (misma lógica que el formulario)
        const productosConsolidados = {};
        const observacionesTexto = [];
        const guiasArray = [];
        
        if (recepcion.Guias && recepcion.Guias.length > 0) {
            recepcion.Guias.forEach(guia => {
                const guiaObj = {
                    numeroGuia: guia.NumeroGuia || '',
                    numeroRef: guia.NumeroDocumentoRef || '',
                    productos: []
                };
                
                const productosGuiaMap = {};
                
                // Procesar productos de la guía
                if (guia.Productos && guia.Productos.length > 0) {
                    guia.Productos.forEach(prod => {
                        const codigo = prod.Codigo || prod.CodigoProducto || '';
                        const cantidad = parseInt(prod.Cantidad) || 0;
                        
                        if (cantidad > 0 && codigo) {
                            // Consolidado global
                            if (!productosConsolidados[codigo]) {
                                productosConsolidados[codigo] = {
                                    codigo: codigo,
                                    abreviatura: prod.Abreviatura || '',
                                    nombre: prod.Producto || prod.Nombre || '',
                                    cantidadTotal: 0,
                                    pendiente: 0,
                                    adicional: 0
                                };
                            }
                            productosConsolidados[codigo].cantidadTotal += cantidad;
                            
                            // Productos de la guía
                            if (!productosGuiaMap[codigo]) {
                                productosGuiaMap[codigo] = {
                                    codigo: codigo,
                                    nombre: prod.Producto || prod.Nombre || '',
                                    cantidad: 0,
                                    pendiente: 0,
                                    adicional: 0
                                };
                            }
                            productosGuiaMap[codigo].cantidad += cantidad;
                        }
                    });
                }
                
                // Procesar observaciones
                const cantObs = parseInt(guia.CantidadObservada) || 0;
                const obsTexto = (guia.ObservacionTexto || '').trim();
                const codigoObs = guia.CodigoProductoObs || '';
                
                if (cantObs > 0 && obsTexto && codigoObs) {
                    // Asegurar que existe en el consolidado
                    if (!productosConsolidados[codigoObs]) {
                        productosConsolidados[codigoObs] = {
                            codigo: codigoObs,
                            abreviatura: '',
                            nombre: '',
                            cantidadTotal: 0,
                            pendiente: 0,
                            adicional: 0
                        };
                    }
                    
                    if (!productosGuiaMap[codigoObs]) {
                        productosGuiaMap[codigoObs] = {
                            codigo: codigoObs,
                            nombre: '',
                            cantidad: 0,
                            pendiente: 0,
                            adicional: 0
                        };
                    }
                    
                    const lowerObs = obsTexto.toLowerCase();
                    if (lowerObs === 'p' || lowerObs.includes('pend')) {
                        productosConsolidados[codigoObs].pendiente += cantObs;
                        productosGuiaMap[codigoObs].pendiente += cantObs;
                        observacionesTexto.push(`PENDIENTE ${cantObs} UND DEL CODIGO ${codigoObs} GUIA ${guia.NumeroGuia}`);
                    } else if (lowerObs === 'de' || lowerObs.includes('deja')) {
                        productosConsolidados[codigoObs].pendiente += cantObs;
                        productosGuiaMap[codigoObs].pendiente += cantObs;
                        observacionesTexto.push(`DEJA ${cantObs} UND DEL CODIGO ${codigoObs} GUIA ${guia.NumeroGuia}`);
                    } else if (lowerObs === 'le' || lowerObs.includes('lleva')) {
                        productosConsolidados[codigoObs].pendiente += cantObs;
                        productosGuiaMap[codigoObs].pendiente += cantObs;
                        observacionesTexto.push(`LLEVA ${cantObs} UND DEL CODIGO ${codigoObs} GUIA ${guia.NumeroGuia}`);
                    } else if (lowerObs === 'a' || lowerObs.includes('adici')) {
                        productosConsolidados[codigoObs].adicional += cantObs;
                        productosGuiaMap[codigoObs].adicional += cantObs;
                        observacionesTexto.push(`TRAE ADICIONAL ${cantObs} UND DEL CODIGO ${codigoObs} GUIA ${guia.NumeroGuia}`);
                    } else if (lowerObs === 'r' || lowerObs.includes('regular')) {
                        // Regulariza: la cantidad ya está contabilizada en cantidadTotal,
                        // no se suma a adicional ni a pendiente
                        observacionesTexto.push(`REGULARIZA ${cantObs} UND DEL CODIGO ${codigoObs} GUIA ${guia.NumeroGuia}`);
                    }
                }
                
                guiaObj.productos = Object.values(productosGuiaMap);
                guiasArray.push(guiaObj);
            });
        }
        
        // Construir HTML de guías (formato simplificado con productos en línea)
        let guiasHtml = '';
        if (guiasArray.length > 0) {
            guiasArray.forEach(guia => {
                let productosTexto = '';
                if (guia.productos && guia.productos.length > 0) {
                    const items = guia.productos.map(p => {
                        let texto = `${p.codigo}: ${p.cantidad}`;
                        if (p.pendiente > 0) texto += ` (P: ${p.pendiente})`;
                        if (p.adicional > 0) texto += ` (A: ${p.adicional})`;
                        return texto;
                    });
                    productosTexto = items.join(' | ');
                } else {
                    productosTexto = '-';
                }
                
                guiasHtml += `
                    <tr>
                        <td style="border:1px solid #999; padding:4px; font-size:0.65rem;">${guia.numeroGuia}</td>
                        <td style="border:1px solid #999; padding:4px; font-size:0.65rem;">${guia.numeroRef || '-'}</td>
                        <td style="border:1px solid #999; padding:4px; font-size:0.65rem;">${productosTexto}</td>
                    </tr>
                `;
            });
        } else {
            guiasHtml = '<tr><td colspan="3" style="border:1px solid #999; padding:8px; text-align:center; font-size:0.65rem;">Sin guías registradas</td></tr>';
        }
        
        // Construir HTML de productos consolidados
        let productosHtml = '';
        const productosArray = Object.values(productosConsolidados);
        if (productosArray.length > 0) {
            productosArray.forEach(prod => {
                const totalProd = (parseInt(prod.cantidadTotal) || 0) + (parseInt(prod.pendiente) || 0) + (parseInt(prod.adicional) || 0);
                productosHtml += `
                    <tr>
                        <td style="border:1px solid #999; padding:2px 4px; text-align:center; font-size:0.6rem;">${prod.abreviatura || prod.codigo}</td>
                        <td style="border:1px solid #999; padding:2px 4px; font-size:0.6rem;">${prod.nombre || '-'}</td>
                        <td style="border:1px solid #999; padding:2px 4px; text-align:center; font-size:0.6rem;">${prod.cantidadTotal}</td>
                        <td style="border:1px solid #999; padding:2px 4px; text-align:center; font-size:0.6rem;">${prod.pendiente || ''}</td>
                        <td style="border:1px solid #999; padding:2px 4px; text-align:center; font-size:0.6rem;">${prod.adicional || ''}</td>
                        <td style="border:1px solid #999; padding:2px 4px; text-align:center; font-size:0.6rem; font-weight:600;">${totalProd}</td>
                    </tr>
                `;
            });
        } else {
            productosHtml = '<tr><td colspan="6" style="border:1px solid #999; padding:6px; text-align:center; color:#999; font-size:0.6rem;">Sin productos registrados</td></tr>';
        }
        
        // Construir HTML de observaciones
        let observacionesHtml = '';
        if (observacionesTexto.length > 0) {
            observacionesTexto.forEach(obs => {
                observacionesHtml += `<div style="padding:4px 8px; font-size:0.65rem; border-bottom:1px solid #e5e7eb;">${obs}</div>`;
            });
        } else {
            observacionesHtml = '<div style="padding:8px; text-align:center; color:#999; font-size:0.65rem;">Sin observaciones</div>';
        }
        
        const html = `
            <div style="font-family:Arial,sans-serif; font-size:0.75rem; padding:20px;">
                <div style="text-align:right; margin-bottom:8px; color:#444; font-size:0.7rem;">
                    ${recepcion.NVale || ''}
                </div>
                
                <div style="display:flex; align-items:center; justify-content:center; margin-bottom:12px;">
                    <div style="flex:1;">
                        <img src="${logoUrl}" style="height:32px; max-width:120px; display:block;" alt="Logo Lavoro" />
                    </div>
                    <div style="flex:2; text-align:center;">
                        <div style="font-weight:bold; font-size:0.85rem; margin-bottom:2px;">VALE DE RECEPCIONES EXTERNAS</div>
                    </div>
                    <div style="flex:1;"></div>
                </div>
                
                <table style="width:100%; border-collapse:collapse; font-size:0.7rem; margin-bottom:12px;">
                    <tr>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">RUC:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${recepcion.RUC || ''}</td>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">ORIGEN:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${recepcion.OrigenTexto || ''}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600;">EMPRESA:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${recepcion.EmpresaTexto || ''}</td>
                        <td style="padding:3px 6px; font-weight:600;">FECHA:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${recepcion.Fecha || ''}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600;">BREVETE:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${recepcion.Brevete || ''}</td>
                        <td style="padding:3px 6px; font-weight:600;">TURNO:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${recepcion.TurnoTexto || ''}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600;">CHOFER:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${recepcion.Chofer || ''}</td>
                        <td style="padding:3px 6px; font-weight:600;">HORA:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${recepcion.Hora || ''}</td>
                    </tr>
                </table>
                
                <div style="margin-top:12px;">
                    <div style="background:#d1d5db; padding:6px 8px; font-weight:bold; font-size:0.75rem; border:1px solid #999; border-bottom:none; border-radius:4px 4px 0 0; text-align:center;">
                        GUÍAS
                    </div>
                    <table style="width:100%; border-collapse:collapse; font-size:0.7rem; margin-bottom:12px; border:1px solid #999; border-top:none;">
                        <thead>
                            <tr style="background:#e5e7eb;">
                                <th style="border:1px solid #999; padding:4px; text-align:center; width:15%; font-size:0.65rem;">N° Guía</th>
                                <th style="border:1px solid #999; padding:4px; text-align:center; width:20%; font-size:0.65rem;">N° Doc. Ref.</th>
                                <th style="border:1px solid #999; padding:4px; text-align:center; font-size:0.65rem;">Productos</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${guiasHtml}
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top:12px;">
                    <div style="background:#d1d5db; padding:6px 8px; font-weight:bold; font-size:0.75rem; border:1px solid #999; border-bottom:none; border-radius:4px 4px 0 0; text-align:center;">
                        PRODUCTOS
                    </div>
                    <table style="width:100%; border-collapse:collapse; font-size:0.6rem; margin-bottom:12px; border:1px solid #999; border-top:none;">
                        <thead>
                            <tr style="background:#e5e7eb;">
                                <th style="border:1px solid #999; padding:2px 4px; text-align:center; width:12%; font-size:0.6rem;">CÓDIGO</th>
                                <th style="border:1px solid #999; padding:2px 4px; text-align:center; width:38%; font-size:0.6rem;">MATERIAL</th>
                                <th style="border:1px solid #999; padding:2px 4px; text-align:center; width:10%; font-size:0.6rem;">CANTIDAD</th>
                                <th style="border:1px solid #999; padding:2px 4px; text-align:center; width:10%; font-size:0.6rem;">PENDIENTE</th>
                                <th style="border:1px solid #999; padding:2px 4px; text-align:center; width:15%; font-size:0.6rem;">ADICIONAL</th>
                                <th style="border:1px solid #999; padding:2px 4px; text-align:center; width:15%; font-size:0.6rem;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${productosHtml}
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:12px;">
                    <div style="border:1px solid #999; border-radius:4px; overflow:hidden;">
                        <div style="background:#d1d5db; padding:6px 8px; font-weight:bold; font-size:0.75rem; text-align:center;">
                            OBSERVACIONES
                        </div>
                        <div style="background:#fff; min-height:60px;">
                            ${observacionesHtml}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Usar la ventana existente o crear una nueva
        const ventana = ventanaExistente || window.open('', '_blank', 'width=900,height=700');
        
        if (!ventana) {
            mostrarNotificacion('No se pudo abrir la ventana de vista previa.', 'warning');
            return;
        }
        
        ventana.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Vista Previa - Vale ${recepcion.NVale}</title>
                <style>
                    body { 
                        margin: 0; 
                        padding: 0; 
                        font-family: Arial, sans-serif;
                    }
                    .no-print {
                        position: fixed;
                        top: 10px;
                        right: 10px;
                        z-index: 1000;
                        display: flex;
                        gap: 10px;
                    }
                    .btn-imprimir {
                        background-color: #3b82f6;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 14px;
                        font-weight: 600;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    .btn-imprimir:hover {
                        background-color: #2563eb;
                    }
                    .btn-cerrar {
                        background-color: #6b7280;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 14px;
                        font-weight: 600;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    .btn-cerrar:hover {
                        background-color: #4b5563;
                    }
                    @media print {
                        body { 
                            print-color-adjust: exact; 
                            -webkit-print-color-adjust: exact; 
                        }
                        .no-print {
                            display: none !important;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="no-print">
                    <button class="btn-imprimir" onclick="window.print()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" style="vertical-align: middle; margin-right: 6px;" viewBox="0 0 16 16">
                            <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
                            <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        </svg>
                        Imprimir
                    </button>
                    <button class="btn-cerrar" onclick="window.close()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" style="vertical-align: middle; margin-right: 6px;" viewBox="0 0 16 16">
                            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                        </svg>
                        Cerrar
                    </button>
                </div>
                ${html}
            </body>
            </html>
        `);
        ventana.document.close();
        
        // Asegurarse de que la ventana tenga el foco
        if (ventana) {
            ventana.focus();
        }
    }
    
    function editarRecepcion(recepcion) {
        console.log('[ReporteRecepcionesExternas] Editar recepción:', recepcion.Id);
        
        if (recepcionEnEdicion) {
            mostrarNotificacion('Ya hay una recepción en edición. Guárdela o cancele primero.', 'warning');
            return;
        }
        
        // Cargar datos completos
        fetch(window.BASE_URL + '/reportes/obtenerRecepcionExternaId', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: recepcion.Id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                recepcionEnEdicion = res.data;
                mostrarFormularioEdicion(res.data);
            } else {
                mostrarNotificacion('Error al cargar datos para edición', 'error');
            }
        })
        .catch(err => {
            console.error('[ReporteRecepcionesExternas] Error:', err);
            mostrarNotificacion('Error de conexión', 'error');
        });
    }
    
    function mostrarFormularioEdicion(recepcion) {
        // Por ahora, mostrar mensaje indicando que se debe usar la interfaz principal
        mostrarNotificacion('La edición completa debe realizarse desde la interfaz principal de Recepciones Externas', 'info');
        
        // TODO: Implementar edición inline similar a despachos internos si se requiere
    }
    
    function anularRecepcion(recepcion) {
        if (recepcion.Estado && recepcion.Estado.toLowerCase() === 'anulado') {
            mostrarNotificacion('Esta recepción ya está anulada', 'warning');
            return;
        }
        
        const motivo = prompt('Ingrese el motivo de anulación:');
        if (!motivo || motivo.trim() === '') {
            mostrarNotificacion('Debe proporcionar un motivo de anulación', 'warning');
            return;
        }
        
        if (!confirm('¿Está seguro de anular esta recepción? Esta acción no se puede deshacer.')) {
            return;
        }
        
        fetch(window.BASE_URL + '/reportes/anularRecepcionExterna', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: recepcion.Id,
                motivo: motivo.trim()
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                mostrarNotificacion('Recepción anulada correctamente', 'success');
                cargarDatos(paginaActual);
            } else {
                mostrarNotificacion('Error al anular: ' + (res.message || 'Error desconocido'), 'error');
            }
        })
        .catch(err => {
            console.error('[ReporteRecepcionesExternas] Error:', err);
            mostrarNotificacion('Error de conexión', 'error');
        });
    }
    
    // ============================================
    // FILTROS
    // ============================================
    
    function setupFiltros() {
        const inputsFiltro = document.querySelectorAll('.filtro-grilla');
        
        inputsFiltro.forEach(input => {
            input.addEventListener('input', debounce(() => {
                cargarDatos(1); // Reiniciar a página 1 al filtrar
            }, 500));
        });
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
        const contenedor = document.getElementById('paginacionReporte');
        if (!contenedor) return;
        
        contenedor.innerHTML = '';
        
        if (totalPaginas <= 1) return;
        
        // Botón Anterior
        const liPrev = document.createElement('li');
        liPrev.className = `page-item ${paginaActual === 1 ? 'disabled' : ''}`;
        liPrev.innerHTML = `<a class="page-link" href="#">Anterior</a>`;
        liPrev.addEventListener('click', (e) => {
            e.preventDefault();
            if (paginaActual > 1) {
                cargarDatos(paginaActual - 1);
            }
        });
        contenedor.appendChild(liPrev);
        
        // Páginas numeradas
        for (let i = 1; i <= totalPaginas; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === paginaActual ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            li.addEventListener('click', (e) => {
                e.preventDefault();
                cargarDatos(i);
            });
            contenedor.appendChild(li);
        }
        
        // Botón Siguiente
        const liNext = document.createElement('li');
        liNext.className = `page-item ${paginaActual === totalPaginas ? 'disabled' : ''}`;
        liNext.innerHTML = `<a class="page-link" href="#">Siguiente</a>`;
        liNext.addEventListener('click', (e) => {
            e.preventDefault();
            if (paginaActual < totalPaginas) {
                cargarDatos(paginaActual + 1);
            }
        });
        contenedor.appendChild(liNext);
    }
    
    // ============================================
    // EXPORTACIÓN A EXCEL
    // ============================================
    
    function setupExportar() {
        const btnExportar = document.getElementById('btnExportExcel');
        if (btnExportar) {
            btnExportar.addEventListener('click', exportarExcel);
        }
    }
    
    function exportarExcel() {
        console.log('[ReporteRecepcionesExternas] Exportando a Excel...');
        
        // Obtener filtros activos
        const filtros = obtenerFiltrosActivos();
        
        // Solicitar todos los datos con filtros aplicados
        fetch(window.BASE_URL + '/reportes/obtenerRecepcionesExternas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pagina: 1,
                filtros: filtros,
                exportar: true // Sin límite
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                generarExcel(res.data);
            } else {
                mostrarNotificacion('Error al obtener datos para exportación', 'error');
            }
        })
        .catch(err => {
            console.error('[ReporteRecepcionesExternas] Error:', err);
            mostrarNotificacion('Error de conexión', 'error');
        });
    }
    
    function generarExcel(datos) {
        const timestamp = new Date().toISOString();
        console.log('>>>>> EXCEL NUEVO - Timestamp:', timestamp, '<<<<<');
        console.log('[ReporteRecepcionesExternas] Generando Excel con', datos.length, 'registros');
        
        // Preparar datos: una fila por producto, con columnas de Vale, Guía y Producto
        const filas = [];
        
        // Encabezados
        filas.push([
            'N° Vale', 'Fecha', 'Hora', 'Turno', 'Origen', 'Empresa', 'RUC', 'Chofer', 'Brevete',
            'N° Guía', 'Observación', 'Cód. Obs', 'Cant. Obs',
            'Código Producto', 'Descripción', 'Cantidad', 'Unidad', 'Estado'
        ]);
        
        // Datos
        datos.forEach(recepcion => {
            console.log('[ReporteRecepcionesExternas] Procesando recepción:', recepcion.NVale, 'Guías:', recepcion.Guias?.length || 0);
            
            if (!recepcion.Guias || recepcion.Guias.length === 0) {
                // Recepción sin guías
                filas.push([
                    recepcion.NVale || '',
                    recepcion.Fecha || '',
                    recepcion.Hora || '',
                    recepcion.Turno || '',
                    recepcion.Origen || '',
                    recepcion.Empresa || '',
                    recepcion.RUC || '',
                    recepcion.Chofer || '',
                    recepcion.Brevete || '',
                    '', '', '', '',
                    '', '', '', '',
                    recepcion.Estado || ''
                ]);
            } else {
                recepcion.Guias.forEach(guia => {
                    console.log('[ReporteRecepcionesExternas] Procesando guía:', guia.NumeroGuia, 'Productos:', guia.Productos?.length || 0);
                    
                    // Construir texto de observaciones concatenado
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
                    
                    if (!guia.Productos || guia.Productos.length === 0) {
                        // Guía sin productos
                        filas.push([
                            recepcion.NVale || '',
                            recepcion.Fecha || '',
                            recepcion.Hora || '',
                            recepcion.Turno || '',
                            recepcion.Origen || '',
                            recepcion.Empresa || '',
                            recepcion.RUC || '',
                            recepcion.Chofer || '',
                            recepcion.Brevete || '',
                            guia.NumeroGuia || '',
                            obsTexto,
                            guia.CodigoProductoObs || '',
                            guia.CantidadObservada || 0,
                            '', '', '', '',
                            recepcion.Estado || ''
                        ]);
                    } else {
                        guia.Productos.forEach(producto => {
                            // Una fila por producto
                            filas.push([
                                recepcion.NVale || '',
                                recepcion.Fecha || '',
                                recepcion.Hora || '',
                                recepcion.Turno || '',
                                recepcion.Origen || '',
                                recepcion.Empresa || '',
                                recepcion.RUC || '',
                                recepcion.Chofer || '',
                                recepcion.Brevete || '',
                                guia.NumeroGuia || '',
                                obsTexto,
                                guia.CodigoProductoObs || '',
                                guia.CantidadObservada || 0,
                                producto.CodigoProducto || '',
                                producto.DescripcionProducto || '',
                                producto.Cantidad || 0,
                                producto.UnidadMedida || '',
                                recepcion.Estado || ''
                            ]);
                        });
                    }
                });
                            filas.push([
                                recepcion.NVale || '',
                                recepcion.Fecha || '',
                                recepcion.Hora || '',
                                recepcion.Turno || '',
                                recepcion.Origen || '',
                                recepcion.Empresa || '',
                                recepcion.RUC || '',
                                recepcion.Chofer || '',
                                recepcion.Brevete || '',
                                guia.NumeroGuia || '',
                                guia.ObservacionTexto || '',
                                guia.CodigoProductoObs || '',
                                guia.CantidadObservada || 0,
                                producto.CodigoProducto || '',
                                producto.DescripcionProducto || '',
                                producto.Cantidad || 0,
                                producto.UnidadMedida || '',
                                recepcion.Estado || ''
                            ]);
                        });
                    }
                });
            }
        });
        
        console.log('[ReporteRecepcionesExternas] Total de filas:', filas.length);
        
        // Crear workbook y worksheet
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(filas);
        
        // IMPORTANTE: Configurar para DESACTIVAR líneas de cuadrícula
        if (!wb.Workbook) wb.Workbook = {};
        if (!wb.Workbook.Views) wb.Workbook.Views = [];
        if (!wb.Workbook.Views[0]) wb.Workbook.Views[0] = {};
        wb.Workbook.Views[0].showGridLines = false; // Desactivar cuadrícula
        
        // Configurar hoja: sin líneas de cuadrícula al imprimir
        ws['!margins'] = { left: 0.7, right: 0.7, top: 0.75, bottom: 0.75, header: 0.3, footer: 0.3 };
        if (!ws['!printOptions']) ws['!printOptions'] = {};
        ws['!printOptions'].gridLines = false;
        
        // Ajustar anchos de columna
        ws['!cols'] = [
            { wch: 12 }, { wch: 12 }, { wch: 8 }, { wch: 10 }, { wch: 15 },
            { wch: 25 }, { wch: 12 }, { wch: 25 }, { wch: 12 },
            { wch: 15 }, { wch: 15 }, { wch: 10 }, { wch: 10 },
            { wch: 12 }, { wch: 30 }, { wch: 10 }, { wch: 10 }, { wch: 10 }
        ];
        
        // Aplicar estilos profesionales a la cabecera
        const range = XLSX.utils.decode_range(ws['!ref']);
        
        // Definir bordes estándar para toda la tabla
        const borderStyle = {
            top: { style: "thin", color: { rgb: "000000" } },
            bottom: { style: "thin", color: { rgb: "000000" } },
            left: { style: "thin", color: { rgb: "000000" } },
            right: { style: "thin", color: { rgb: "000000" } }
        };
        
        // Estilo para encabezado
        for (let col = range.s.c; col <= range.e.c; col++) {
            const cellAddress = XLSX.utils.encode_cell({ r: 0, c: col });
            if (!ws[cellAddress]) continue;
            
            ws[cellAddress].s = {
                font: { bold: true, color: { rgb: "FFFFFF" }, sz: 11 },
                fill: { fgColor: { rgb: "366092" } },
                alignment: { horizontal: "center", vertical: "center" },
                border: borderStyle
            };
        }
        
        // Estilo para datos
        for (let row = range.s.r + 1; row <= range.e.r; row++) {
            for (let col = range.s.c; col <= range.e.c; col++) {
                const cellAddress = XLSX.utils.encode_cell({ r: row, c: col });
                if (!ws[cellAddress]) continue;
                
                ws[cellAddress].s = {
                    font: { sz: 10 },
                    alignment: { vertical: "center", wrapText: true },
                    border: borderStyle
                };
                
                // Alternar color de filas
                if (row % 2 === 0) {
                    ws[cellAddress].s.fill = { fgColor: { rgb: "F8F9FA" } };
                }
            }
        }
        
        XLSX.utils.book_append_sheet(wb, ws, 'Recepciones Externas');
        
        // Generar archivo
        const fecha = new Date().toISOString().split('T')[0];
        XLSX.writeFile(wb, `Reporte_Recepciones_Externas_${fecha}.xlsx`);
        
        console.log('[ReporteRecepcionesExternas] Excel generado exitosamente');
    }
    
    // ============================================
    // UTILIDADES
    // ============================================
    
    function mostrarNotificacion(mensaje, tipo) {
        // Buscar función global de notificaciones
        if (typeof window.mostrarNotificacion === 'function') {
            window.mostrarNotificacion(mensaje, tipo);
        } else {
            alert(mensaje);
        }
    }
    
})();
