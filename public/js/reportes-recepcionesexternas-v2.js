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
            tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4">No se encontraron registros</td></tr>';
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
        let estadoTexto = recepcion.Estado || 'activo';
        if (estadoTexto.toLowerCase() === 'anulado') {
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
            <td>${recepcion.Empresa || ''}</td>
            <td>${recepcion.RUC || ''}</td>
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
        
        tr.innerHTML = `
            <td style="text-align:center; padding-left:20px; vertical-align:middle;">
                ${tieneProductos ? `<button class="btn btn-sm btn-secondary toggle-productos" title="Expandir/Contraer productos" style="width:32px; height:24px; padding:2px;">
                    <i class="${iconoChevron}" style="font-size:0.9rem;"></i>
                </button>` : ''}
            </td>
            <td colspan="2" style="padding-left:30px;">${guia.NumeroGuia || ''}</td>
            <td colspan="2">${guia.ObservacionTexto || '-'}</td>
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
        
        // Cargar los datos completos de la recepción
        fetch(window.BASE_URL + '/reportes/obtenerRecepcionExternaId', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: recepcion.Id })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                // Abrir modal o ventana de impresión con los datos
                mostrarVistaPrevia(res.data);
            } else {
                mostrarNotificacion('Error al cargar el detalle', 'error');
            }
        })
        .catch(err => {
            console.error('[ReporteRecepcionesExternas] Error:', err);
            mostrarNotificacion('Error de conexión', 'error');
        });
    }
    
    function mostrarVistaPrevia(recepcion) {
        // Crear HTML de la vista previa (similar al vale de la interfaz principal)
        const baseUrl = window.BASE_URL || '';
        const logoUrl = baseUrl + '/img/Logo-Lavoro-1536x442.png';
        
        // Construir tabla de guías
        let guiasHtml = '';
        if (recepcion.Guias && recepcion.Guias.length > 0) {
            recepcion.Guias.forEach(guia => {
                let productosHtml = '';
                if (guia.Productos && guia.Productos.length > 0) {
                    guia.Productos.forEach(prod => {
                        productosHtml += `<span style="margin-right:15px;">${prod.Codigo}: ${prod.Cantidad} ${prod.UnidadMedida || ''}</span>`;
                    });
                }
                
                guiasHtml += `
                    <tr>
                        <td style="border:1px solid #999; padding:4px;">${guia.NumeroGuia || ''}</td>
                        <td style="border:1px solid #999; padding:4px;">${productosHtml || '-'}</td>
                        <td style="border:1px solid #999; padding:4px; text-align:center;">${guia.ObservacionTexto || '-'}</td>
                        <td style="border:1px solid #999; padding:4px; text-align:center;">${guia.CodigoObservacion || '-'}</td>
                        <td style="border:1px solid #999; padding:4px; text-align:center;">${guia.CantidadObservacion || 0}</td>
                    </tr>
                `;
            });
        } else {
            guiasHtml = '<tr><td colspan="5" style="border:1px solid #999; padding:8px; text-align:center;">Sin guías registradas</td></tr>';
        }
        
        const html = `
            <div style="font-family:Arial,sans-serif; font-size:0.75rem; padding:20px;">
                <div style="text-align:right; margin-bottom:8px; color:#444; font-size:0.7rem;">
                    ${recepcion.NVale || ''}
                </div>
                
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                    <div style="flex:1;">
                        <img src="${logoUrl}" style="height:32px; max-width:120px; display:block;" alt="Logo Lavoro" />
                    </div>
                    <div style="flex:2; text-align:center;">
                        <div style="font-weight:bold; font-size:0.85rem; margin-bottom:2px;">VALE DE RECEPCIONES EXTERNAS</div>
                    </div>
                    <div style="flex:1; text-align:right;">
                        <div style="font-size:0.7rem; font-weight:600;">FECHA:</div>
                        <div style="font-size:0.7rem;">${recepcion.Fecha || ''}</div>
                    </div>
                </div>
                
                <table style="width:100%; border-collapse:collapse; font-size:0.7rem; margin-bottom:12px;">
                    <tr>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">EMPRESA:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${recepcion.EmpresaTexto || ''}</td>
                        <td style="padding:3px 6px; font-weight:600; width:15%;">HORA:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999; width:35%;">${recepcion.Hora || ''}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600;">RUC:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${recepcion.RUC || ''}</td>
                        <td style="padding:3px 6px; font-weight:600;">TURNO:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;">${recepcion.TurnoTexto || ''}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600;">CHOFER:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;" colspan="3">${recepcion.Chofer || ''}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600;">BREVETE:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;" colspan="3">${recepcion.Brevete || ''}</td>
                    </tr>
                    <tr>
                        <td style="padding:3px 6px; font-weight:600;">ORIGEN:</td>
                        <td style="padding:3px 6px; border-bottom:1px solid #999;" colspan="3">${recepcion.OrigenTexto || ''}</td>
                    </tr>
                </table>
                
                <div style="margin-top:12px;">
                    <div style="background:#d1d5db; padding:6px 8px; font-weight:bold; font-size:0.75rem; border:1px solid #999;">
                        GUÍAS
                    </div>
                    <table style="width:100%; border-collapse:collapse; font-size:0.7rem; margin-bottom:12px; border:1px solid #999;">
                        <thead>
                            <tr style="background:#f3f4f6;">
                                <th style="border:1px solid #999; padding:4px; text-align:center; width:15%;">N° Guía</th>
                                <th style="border:1px solid #999; padding:4px; text-align:center;">Productos</th>
                                <th style="border:1px solid #999; padding:4px; text-align:center; width:15%;">Observación</th>
                                <th style="border:1px solid #999; padding:4px; text-align:center; width:12%;">Cód. Obs</th>
                                <th style="border:1px solid #999; padding:4px; text-align:center; width:10%;">Cant. Obs</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${guiasHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        // Abrir ventana de impresión
        const ventana = window.open('', '_blank', 'width=800,height=600');
        ventana.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Vale ${recepcion.NVale}</title>
                <style>
                    body { margin: 0; padding: 0; }
                    @media print {
                        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                    }
                </style>
            </head>
            <body>
                ${html}
                <script>
                    window.onload = function() {
                        window.print();
                    };
                </script>
            </body>
            </html>
        `);
        ventana.document.close();
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
        console.log('[ReporteRecepcionesExternas] Exportando a Excel desde servidor...');
        
        // Obtener filtros activos
        const filtros = obtenerFiltrosActivos();
        
        // Construir URL con parámetros
        const params = new URLSearchParams(filtros);
        const url = window.BASE_URL + '/reportes/exportarExcelRecepcionesExternas?' + params.toString();
        
        // Redirigir para descargar el archivo
        window.location.href = url;
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
                            guia.ObservacionTexto || '',
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
