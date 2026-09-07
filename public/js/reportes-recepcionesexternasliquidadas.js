// reportes-recepcionesexternasliquidadas.js

(function() {
    'use strict';
    
    let datosOriginales = [];
    let datosFiltrados = [];
    
    // Configuración de paginación
    const itemsPorPagina = 20;
    let paginaActual = 1;
    
    // Cargar datos al inicio
    document.addEventListener('DOMContentLoaded', function() {
        cargarDatos();
        
        // Event listeners
        document.getElementById('btnExportExcel')?.addEventListener('click', exportarExcel);
        document.getElementById('filtroFecha')?.addEventListener('change', aplicarFiltrosEncabezado);
        document.getElementById('filtroTurno')?.addEventListener('change', aplicarFiltrosEncabezado);
        
        // Filtros de grilla
        document.querySelectorAll('.filtro-grilla').forEach(input => {
            input.addEventListener('input', aplicarFiltrosGrilla);
        });
    });
    
    function cargarDatos() {
        const url = window.APP_URL + '/reportes/obtenerPicking';
        
        // Construir filtros desde encabezado
        const filtros = {};
        const fecha = document.getElementById('filtroFecha')?.value;
        const turno = document.getElementById('filtroTurno')?.value;
        
        if (fecha) filtros.Fecha = fecha;
        if (turno) filtros.Turno = turno;
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                pagina: 1,
                filtros: filtros,
                exportar: true  // Para obtener todos los registros
            })
        })
            .then(response => response.json())
            .then(data => {
                console.log('Datos recibidos:', data);
                if (data.success && data.data) {
                    datosOriginales = procesarDatosLiquidadas(data.data);
                    console.log('Datos procesados:', datosOriginales);
                    datosFiltrados = [...datosOriginales];
                    aplicarFiltrosEncabezado();
                } else {
                    mostrarError('No se pudieron cargar los datos');
                }
            })
            .catch(error => {
                console.error('Error cargando datos:', error);
                mostrarError('Error al cargar los datos: ' + error.message);
            });
    }
    
    function procesarDatosLiquidadas(recepciones) {
        const resultado = [];
        
        recepciones.forEach(recepcion => {
            if (recepcion.Guias && Array.isArray(recepcion.Guias)) {
                recepcion.Guias.forEach(guia => {
                    if (guia.Productos && Array.isArray(guia.Productos)) {
                        guia.Productos.forEach(producto => {
                            // Construir texto de observación
                            let obsTexto = '';
                            if (guia.CantidadObservada && guia.CantidadObservada > 0 && guia.ObservacionTexto) {
                                const obs = guia.ObservacionTexto.toLowerCase();
                                const codigo = guia.CodigoProductoObs || producto.CodigoProducto || '';
                                
                                if (obs === 'p' || obs.includes('pend')) {
                                    obsTexto = `PENDIENTE ${guia.CantidadObservada} UND DEL CODIGO ${codigo} GUIA ${guia.NumeroGuia}`;
                                } else if (obs === 'de' || obs.includes('deja')) {
                                    obsTexto = `DEJA ${guia.CantidadObservada} UND DEL CODIGO ${codigo} GUIA ${guia.NumeroGuia}`;
                                } else if (obs === 'le' || obs.includes('lleva')) {
                                    obsTexto = `LLEVA ${guia.CantidadObservada} UND DEL CODIGO ${codigo} GUIA ${guia.NumeroGuia}`;
                                } else if (obs === 'a' || obs.includes('adici')) {
                                    obsTexto = `TRAE ADICIONAL ${guia.CantidadObservada} UND DEL CODIGO ${codigo} GUIA ${guia.NumeroGuia}`;
                                } else if (obs === 'r' || obs.includes('regular')) {
                                    obsTexto = `REGULARIZA ${guia.CantidadObservada} UND DEL CODIGO ${codigo} GUIA ${guia.NumeroGuia}`;
                                }
                            }
                            
                            // Calcular pendiente y adicional basado en la observación
                            let pendiente = 0;
                            let adicional = 0;
                            
                            if (guia.CantidadObservada && guia.CodigoProductoObs === producto.CodigoProducto) {
                                const obs = (guia.ObservacionTexto || '').toLowerCase();
                                if (obs === 'p' || obs.includes('pend') || obs === 'de' || obs.includes('deja') || obs === 'le' || obs.includes('lleva')) {
                                    pendiente = parseFloat(guia.CantidadObservada) || 0;
                                } else if (obs === 'a' || obs.includes('adici') || obs === 'r' || obs.includes('regular')) {
                                    adicional = parseFloat(guia.CantidadObservada) || 0;
                                }
                            }
                            
                            const cantidad = parseFloat(producto.Cantidad) || 0;
                            // Total = Cantidad + Adicional - Pendiente
                            const total = cantidad + adicional - pendiente;
                            
                            resultado.push({
                                NumeroGuia: guia.NumeroGuia || '',
                                Fecha: recepcion.Fecha || '',
                                Observaciones: obsTexto,
                                Cantidad: cantidad,
                                Pendiente: pendiente,
                                Adicional: adicional,
                                Total: total,
                                Turno: recepcion.Turno || ''
                            });
                        });
                    }
                });
            }
        });
        
        return resultado;
    }
    
    function aplicarFiltrosEncabezado() {
        const filtroFecha = document.getElementById('filtroFecha')?.value || '';
        const filtroTurno = document.getElementById('filtroTurno')?.value || '';
        
        datosFiltrados = datosOriginales.filter(item => {
            if (filtroFecha && item.Fecha !== filtroFecha) return false;
            if (filtroTurno && item.Turno !== filtroTurno) return false;
            return true;
        });
        
        paginaActual = 1;
        aplicarFiltrosGrilla();
    }
    
    function aplicarFiltrosGrilla() {
        const filtros = {};
        document.querySelectorAll('.filtro-grilla').forEach(input => {
            const campo = input.getAttribute('data-campo');
            const valor = input.value.toLowerCase().trim();
            if (valor) {
                filtros[campo] = valor;
            }
        });
        
        let datos = datosFiltrados.filter(item => {
            for (let campo in filtros) {
                const valorItem = String(item[campo] || '').toLowerCase();
                if (!valorItem.includes(filtros[campo])) {
                    return false;
                }
            }
            return true;
        });
        
        renderizarGrilla(datos);
        renderizarPaginacion(datos.length);
    }
    
    function renderizarGrilla(datos) {
        const tbody = document.getElementById('tbodyReporte');
        if (!tbody) return;
        
        if (datos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron registros</td></tr>';
            return;
        }
        
        // Paginar datos
        const inicio = (paginaActual - 1) * itemsPorPagina;
        const fin = inicio + itemsPorPagina;
        const datosPaginados = datos.slice(inicio, fin);
        
        let html = '';
        datosPaginados.forEach(item => {
            const fechaFormateada = item.Fecha ? formatearFecha(item.Fecha) : '';
            
            html += `
                <tr>
                    <td class="text-center">${item.NumeroGuia}</td>
                    <td class="text-center">${fechaFormateada}</td>
                    <td>${item.Observaciones || ''}</td>
                    <td class="text-center">${item.Cantidad || ''}</td>
                    <td class="text-center">${item.Pendiente || ''}</td>
                    <td class="text-center">${item.Adicional || ''}</td>
                    <td class="text-center fw-bold">${item.Total || ''}</td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
    }
    
    function renderizarPaginacion(totalRegistros) {
        const totalPaginas = Math.ceil(totalRegistros / itemsPorPagina);
        const paginacion = document.getElementById('paginacionReporte');
        
        if (!paginacion || totalPaginas <= 1) {
            if (paginacion) paginacion.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Botón anterior
        html += `<li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-pagina="${paginaActual - 1}">Anterior</a>
        </li>`;
        
        // Páginas
        const rango = 2;
        for (let i = Math.max(1, paginaActual - rango); i <= Math.min(totalPaginas, paginaActual + rango); i++) {
            html += `<li class="page-item ${i === paginaActual ? 'active' : ''}">
                <a class="page-link" href="#" data-pagina="${i}">${i}</a>
            </li>`;
        }
        
        // Botón siguiente
        html += `<li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
            <a class="page-link" href="#" data-pagina="${paginaActual + 1}">Siguiente</a>
        </li>`;
        
        paginacion.innerHTML = html;
        
        // Event listeners
        paginacion.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const pagina = parseInt(this.getAttribute('data-pagina'));
                if (pagina > 0 && pagina <= totalPaginas) {
                    paginaActual = pagina;
                    aplicarFiltrosGrilla();
                }
            });
        });
    }
    
    function formatearFecha(fecha) {
        if (!fecha) return '';
        const partes = fecha.split('-');
        if (partes.length === 3) {
            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        }
        return fecha;
    }
    
    function exportarExcel() {
        const params = new URLSearchParams();
        
        const filtroFecha = document.getElementById('filtroFecha')?.value || '';
        const filtroTurno = document.getElementById('filtroTurno')?.value || '';
        
        if (filtroFecha) params.append('Fecha', filtroFecha);
        if (filtroTurno) params.append('Turno', filtroTurno);
        
        // Agregar filtros de grilla
        document.querySelectorAll('.filtro-grilla').forEach(input => {
            const valor = input.value.trim();
            if (valor) {
                params.append(input.getAttribute('data-campo'), valor);
            }
        });
        
        const url = window.APP_URL + '/reportes/exportarExcelRecepcionesExternasLiquidadas?' + params.toString();
        window.location.href = url;
    }
    
    function mostrarError(mensaje) {
        const tbody = document.getElementById('tbodyReporte');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${mensaje}</td></tr>`;
        }
    }
    
})();
