/**
 * planabastecimiento.js - Módulo Plan de Abastecimiento
 * Lógica: carga de grilla, guardar y modificar registros
 */
(function() {
    'use strict';

    console.log('[PlanAbastecimiento] Script cargado');

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Estado global del módulo
    var state = {
        modo: 'nuevo', // 'nuevo' | 'edicion'
        registroId: null,
        datosCargados: false
    };

    function init() {
        console.log('[PlanAbastecimiento] Inicializando...');

        // Configurar botones
        document.getElementById('btnNuevo').addEventListener('click', btnNuevoClick);
        document.getElementById('btnGuardar').addEventListener('click', btnGuardarClick);
        document.getElementById('btnModificar').addEventListener('click', btnModificarClick);

        // Prevenir valores negativos en inputs numéricos
        setupAntiNegativeInputs();

        // Seleccionar mes y año actual en los filtros
        var ahora = new Date();
        document.getElementById('selectMes').value = ahora.getMonth() + 1;
        document.getElementById('selectAnio').value = ahora.getFullYear();

        // Configurar filtro
        document.getElementById('btnFiltrar').addEventListener('click', function() {
            cargarRegistros();
        });

        // Cargar registros iniciales
        cargarRegistros();

        // Iniciar con campos bloqueados
        bloquearCampos(true);

        console.log('[PlanAbastecimiento] Inicialización completa');
    }

    // =============================================
    // BLOQUEO/HABILITACIÓN DE CAMPOS
    // =============================================

    /**
     * Bloquear o habilitar los campos del formulario
     * @param {boolean} bloquear - true para deshabilitar, false para habilitar
     */
    function bloquearCampos(bloquear) {
        document.getElementById('fecha').disabled = bloquear;
        document.getElementById('q_total_planificada').disabled = bloquear;
    }

    // =============================================
    // PREVENIR VALORES NEGATIVOS
    // =============================================
    function setupAntiNegativeInputs() {
        document.addEventListener('input', function(e) {
            var target = e.target;
            if (target && target.tagName === 'INPUT' && target.type === 'number') {
                var min = target.hasAttribute('min') ? parseFloat(target.getAttribute('min')) : 0;
                if (target.value !== '' && parseFloat(target.value) < min) {
                    target.value = String(min);
                }
            }
        }, true);

        document.addEventListener('change', function(e) {
            var target = e.target;
            if (target && target.tagName === 'INPUT' && target.type === 'number') {
                var min = target.hasAttribute('min') ? parseFloat(target.getAttribute('min')) : 0;
                if (target.value !== '' && parseFloat(target.value) < min) {
                    target.value = String(min);
                }
            }
        });
    }

    // =============================================
    // CARGA DE REGISTROS
    // =============================================

    /**
     * AJAX: Cargar registros filtrados por mes y año
     */
    function cargarRegistros() {
        var mes = document.getElementById('selectMes').value;
        var anio = document.getElementById('selectAnio').value;
        var url = window.APP_URL + '/planabastecimiento/listar?mes=' + encodeURIComponent(mes) + '&anio=' + encodeURIComponent(anio);
        console.log('[PlanAbastecimiento] Cargando registros mes=' + mes + ' anio=' + anio);

        showLoading(true);

        fetch(url)
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                showLoading(false);
                console.log('[PlanAbastecimiento] Respuesta servidor:', JSON.stringify(data));

                if (!data.success) {
                    mostrarError(data.error || 'Error al cargar registros');
                    return;
                }

                poblarGrilla(data.data || []);
                state.datosCargados = true;
            })
            .catch(function(err) {
                showLoading(false);
                console.error('[PlanAbastecimiento] Error de red:', err);
                mostrarError('Error de red: ' + err.message);
            });
    }

    // =============================================
    // POBLAR GRILLA
    // =============================================

    function poblarGrilla(registros) {
        var tbody = document.getElementById('tbodyPlanAbastecimiento');
        tbody.innerHTML = '';

        if (!registros || registros.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#999;">Sin registros</td></tr>';
            return;
        }

        // Calcular campos derivados para cada registro
        // Necesitamos orden ASC para calcular el acumulado (depende del día anterior)
        var registrosParaCalc = registros.slice().sort(function(a, b) {
            return (a.Fecha || '').localeCompare(b.Fecha || '');
        });

        // Mapa: codigo_planificacion → q_pendiente_despacho_acumulado
        var mapaAcumulado = {};

        registrosParaCalc.forEach(function(r) {
            var qTotalPlanif = formatNum(r.QTotalPlanificada || 0);
            var qTotalDesp = formatNum(r.q_total_despachada || 0);

            // Semana
            var numSemana = '';
            if (r.Fecha) {
                numSemana = obtenerSemanaISO(r.Fecha);
            }
            r._semana = numSemana;

            // Código Planificación
            var codigoPlanif = '';
            if (r.Fecha && numSemana) {
                var fechaDDMMAA = formatearFechaDDMMAA(r.Fecha);
                codigoPlanif = fechaDDMMAA + numSemana;
            }
            r._codigoPlanif = codigoPlanif;

            // Q Pendiente Día
            r._qPendDia = Math.max(0, qTotalPlanif - qTotalDesp);

            // Q Pendiente Despacho Acumulado:
            // = XLOOKUP(fecha_anterior(ddmmaa)+semana, codigos, acumulados, "", 0, 1)
            //   + XLOOKUP(codigo_actual, codigos, q_total_planificada, "", 0, 1)
            //   - XLOOKUP(codigo_actual, codigos, q_total_despachada, "", 0, 1)
            var acumuladoAnterior = 0;
            if (r.Fecha) {
                var fechaAyerDDMMAA = formatearFechaAnteriorDDMMAA(r.Fecha);
                var codigoAyer = fechaAyerDDMMAA + numSemana;
                acumuladoAnterior = mapaAcumulado[codigoAyer] || 0;
            }

            r._qPendDespAcum = acumuladoAnterior + qTotalPlanif - qTotalDesp;
            if (r._qPendDespAcum < 0) r._qPendDespAcum = 0;

            // Guardar en mapa para futuras búsquedas
            mapaAcumulado[codigoPlanif] = r._qPendDespAcum;
        });

        // Renderizar en orden original (DESC)
        registros.forEach(function(r, idx) {
            var tr = document.createElement('tr');
            tr.dataset.id = r.Id;
            tr.dataset.index = idx;

            var item = idx + 1;
            var fechaFormateada = formatearFechaDDMMYYYY(r.Fecha || '');
            var qTotalPlanif = formatNum(r.QTotalPlanificada || 0);
            var qTotalDesp = formatNum(r.q_total_despachada || 0);

            tr.innerHTML =
                '<td style="text-align:center;">' + item + '</td>' +
                '<td style="text-align:center;">' + escapeHtml(fechaFormateada) + '</td>' +
                '<td style="text-align:right;font-weight:600;">' + qTotalPlanif + '</td>' +
                '<td style="text-align:right;">' + qTotalDesp + '</td>' +
                '<td style="text-align:right;">' + r._qPendDia + '</td>' +
                '<td style="text-align:right;font-weight:600;">' + r._qPendDespAcum + '</td>' +
                '<td style="text-align:center;">' + escapeHtml(r._codigoPlanif || '') + '</td>' +
                '<td style="text-align:center;">' + (r._semana || '') + '</td>';

            // Click en fila → cargar datos en formulario
            tr.addEventListener('click', function() {
                seleccionarFila(tr, r);
            });

            tbody.appendChild(tr);
        });
    }

    /**
     * Seleccionar una fila de la grilla
     * Solo resalta visualmente y guarda el ID, NO carga datos en el formulario
     */
    function seleccionarFila(tr, registro) {
        // Quitar selección anterior
        var filas = document.querySelectorAll('#tbodyPlanAbastecimiento tr');
        filas.forEach(function(f) {
            f.classList.remove('seleccionado');
        });

        // Marcar esta fila
        tr.classList.add('seleccionado');

        // Guardar ID del registro seleccionado (sin cargar datos aún)
        state.registroId = registro.Id;

        // Habilitar solo botón Modificar (Guardar se habilita al dar Nuevo o al cargar datos para modificar)
        document.getElementById('btnModificar').disabled = false;
        document.getElementById('btnGuardar').disabled = true;
    }

    // =============================================
    // BOTONES
    // =============================================

    function btnGuardarClick() {
        var fecha = document.getElementById('fecha').value;
        var qTotalPlanificada = document.getElementById('q_total_planificada').value;

        if (!fecha) {
            mostrarError('Debe seleccionar una fecha');
            return;
        }

        if (!qTotalPlanificada || parseFloat(qTotalPlanificada) < 0) {
            mostrarError('Debe ingresar una cantidad total planificada válida');
            return;
        }

        var data = {
            id: state.registroId || null,
            fecha: fecha,
            q_total_planificada: qTotalPlanificada
        };

        var esNuevo = !data.id;

        showLoading(true);

        fetch(window.APP_URL + '/planabastecimiento/guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(resp) { return resp.json(); })
        .then(function(result) {
            showLoading(false);
            if (result.success) {
                mostrarExito(result.message || (esNuevo ? 'Registro guardado exitosamente' : 'Registro actualizado exitosamente'));
                limpiarFormulario();
                cargarRegistros();
            } else {
                mostrarError(result.error || 'Error al guardar');
            }
        })
        .catch(function(err) {
            showLoading(false);
            mostrarError('Error de red: ' + err.message);
        });
    }

    function btnModificarClick() {
        if (!state.registroId) {
            mostrarError('Debe seleccionar un registro de la grilla');
            return;
        }

        // 1. Primero obtener los datos del registro
        showLoading(true);
        fetch(window.APP_URL + '/planabastecimiento/obtener?id=' + state.registroId)
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (!result.success || !result.data) {
                    showLoading(false);
                    mostrarError('Error al obtener datos del registro');
                    return;
                }

                var registro = result.data;

                // 2. Verificar límite de modificaciones
                fetch(window.APP_URL + '/planabastecimiento/verificarModificaciones?id=' + state.registroId)
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        showLoading(false);
                        if (data.puede_modificar) {
                            // Cargar datos en el formulario
                            // Cargar datos en el formulario y habilitar campos
                            document.getElementById('registroId').value = registro.Id;
                            document.getElementById('fecha').value = registro.Fecha || '';
                            document.getElementById('q_total_planificada').value = parseInt(registro.QTotalPlanificada || 0);
                            bloquearCampos(false);

                            state.modo = 'nuevo';
                            document.getElementById('btnGuardar').disabled = false;
                            document.getElementById('btnModificar').disabled = true;
                            if (data.restantes > 0 && data.restantes < 999) {
                                mostrarExito('Modificaciones restantes: ' + data.restantes + '. Edite los datos y presione Guardar.');
                            } else {
                                mostrarExito('Puede modificar los datos y presionar Guardar');
                            }
                        } else {
                            mostrarError(data.mensaje || 'No puede modificar este registro');
                        }
                    })
                    .catch(function() {
                        showLoading(false);
                        mostrarError('Error al verificar modificaciones');
                    });
            })
            .catch(function() {
                showLoading(false);
                mostrarError('Error al obtener datos del registro');
            });
    }

    // =============================================
    // UTILIDADES
    // =============================================

    function btnNuevoClick() {
        // Limpiar formulario y habilitar campos para nuevo registro
        document.getElementById('registroId').value = '';
        document.getElementById('fecha').value = obtenerFechaActual();
        document.getElementById('q_total_planificada').value = '0';
        bloquearCampos(false);

        state.modo = 'nuevo';
        state.registroId = null;

        document.getElementById('btnModificar').disabled = true;
        document.getElementById('btnGuardar').disabled = false;

        // Quitar selección de la grilla
        var filas = document.querySelectorAll('#tbodyPlanAbastecimiento tr');
        filas.forEach(function(f) {
            f.classList.remove('seleccionado');
        });
    }

    function limpiarFormulario() {
        document.getElementById('registroId').value = '';
        document.getElementById('fecha').value = obtenerFechaActual();
        document.getElementById('q_total_planificada').value = '0';
        bloquearCampos(true);

        state.modo = 'nuevo';
        state.registroId = null;

        document.getElementById('btnModificar').disabled = true;
        document.getElementById('btnGuardar').disabled = true;

        // Quitar selección de la grilla
        var filas = document.querySelectorAll('#tbodyPlanAbastecimiento tr');
        filas.forEach(function(f) {
            f.classList.remove('seleccionado');
        });
    }

    function formatNum(n) {
        return parseInt(n || 0);
    }

    function escapeHtml(s) {
        if (!s) return '';
        return String(s).replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"');
    }

    /**
     * Obtener fecha actual en formato YYYY-MM-DD
     */
    function obtenerFechaActual() {
        var hoy = new Date();
        var dd = String(hoy.getDate()).padStart(2, '0');
        var mm = String(hoy.getMonth() + 1).padStart(2, '0');
        var yyyy = hoy.getFullYear();
        return yyyy + '-' + mm + '-' + dd;
    }

    /**
     * Convierte YYYY-MM-DD a DD-MM-YYYY
     */
    function formatearFechaDDMMYYYY(fechaStr) {
        if (!fechaStr) return '';
        var partes = fechaStr.split('-');
        if (partes.length === 3) {
            return partes[2] + '-' + partes[1] + '-' + partes[0];
        }
        return fechaStr;
    }

    /**
     * Convierte YYYY-MM-DD a DDMMAA (para código planificación)
     */
    function formatearFechaDDMMAA(fechaStr) {
        if (!fechaStr) return '';
        var partes = fechaStr.split('-');
        if (partes.length === 3) {
            var dd = partes[2];
            var mm = partes[1];
            var aa = partes[0].slice(-2);
            return dd + mm + aa;
        }
        return fechaStr;
    }

    /**
     * Retorna la fecha del día anterior en formato DDMMAA
     * Ej: "2026-06-29" → "280626"
     */
    function formatearFechaAnteriorDDMMAA(fechaStr) {
        if (!fechaStr) return '';
        var partes = fechaStr.split('-');
        if (partes.length !== 3) return '';
        var fecha = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
        fecha.setDate(fecha.getDate() - 1);
        var dd = String(fecha.getDate()).padStart(2, '0');
        var mm = String(fecha.getMonth() + 1).padStart(2, '0');
        var aa = String(fecha.getFullYear()).slice(-2);
        return dd + mm + aa;
    }

    /**
     * Obtener número de semana ISO 8601 a partir de fecha en formato YYYY-MM-DD
     */
    function obtenerSemanaISO(fechaStr) {
        if (!fechaStr) return '';
        var partes = fechaStr.split('-');
        if (partes.length !== 3) return '';
        var fecha = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
        // ISO week calculation
        var d = new Date(Date.UTC(fecha.getFullYear(), fecha.getMonth(), fecha.getDate()));
        var dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        var yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        var weekNum = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        return weekNum;
    }

    function showLoading(show) {
        var existing = document.getElementById('pa-loading');
        if (show) {
            if (!existing) {
                var div = document.createElement('div');
                div.id = 'pa-loading';
                div.style.cssText = 'position:fixed;top:10px;right:10px;background:#2563eb;color:#fff;padding:8px 16px;border-radius:8px;font-size:14px;z-index:9999;';
                div.textContent = 'Cargando...';
                document.body.appendChild(div);
            }
        } else {
            if (existing) existing.remove();
        }
    }

    function mostrarError(msg) {
        mostrarNotificacion(msg, 'danger');
    }

    function mostrarExito(msg) {
        mostrarNotificacion(msg, 'success');
    }

    function mostrarNotificacion(mensaje, tipo) {
        try {
            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + tipo + ' alert-dismissible fade show';
            alertDiv.setAttribute('role', 'alert');
            alertDiv.innerHTML =
                mensaje +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';

            var container = document.getElementById('pa-mensajes');
            if (!container) {
                container = document.createElement('div');
                container.id = 'pa-mensajes';
                container.style.position = 'fixed';
                container.style.top = '20px';
                container.style.right = '20px';
                container.style.zIndex = '99999';
                document.body.appendChild(container);
            }

            container.appendChild(alertDiv);

            setTimeout(function() {
                alertDiv.classList.remove('show');
                setTimeout(function() {
                    try { alertDiv.remove(); } catch(e) {}
                }, 300);
            }, 5000);
        } catch (e) {
            console.warn('[PlanAbastecimiento] Error mostrando notificación:', e);
        }
    }

})();
