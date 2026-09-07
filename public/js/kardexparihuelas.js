/**
 * kardexparihuelas.js - Módulo Kardex de Parihuelas Estándar
 * Lógica: auto-carga de datos, cálculos, guardado e impresión
 */
(function() {
    'use strict';

    console.log('[KardexParihuelas] Script cargado');

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Estado global del módulo
    var state = {
        modo: 'nuevo', // 'nuevo' | 'edicion' | 'ver'
        kardexId: null,
        datosCargados: false
    };

    function init() {
        console.log('[KardexParihuelas] Inicializando...');

        // Inicializar Choices.js en selects
        initializeChoices();

        // Configurar turno automático
        setupTurnoAutomatico();

        // Cargar datos al cambiar fecha o turno
        setupFechaTurnoChange();

        // Configurar botones
        document.getElementById('btnNuevo').addEventListener('click', btnNuevoClick);
        document.getElementById('btnGuardar').addEventListener('click', btnGuardarClick);
        document.getElementById('btnModificar').addEventListener('click', btnModificarClick);
        document.getElementById('btnImprimir').addEventListener('click', btnImprimirClick);

        // Configurar auto-cálculo de totales en ajustes
        setupCalculosAutomaticos();

        // Prevenir valores negativos en inputs numéricos
        setupAntiNegativeInputs();

        // Configurar colapsar sección
        setupToggleDatos();

        console.log('[KardexParihuelas] Inicialización completa');
    }

    // =============================================
    // PREVENIR VALORES NEGATIVOS
    // =============================================
    function setupAntiNegativeInputs() {
        // Delegación de eventos en fase de captura (true) para corregir el valor
        // ANTES de que los listeners de cada elemento recalculen dependencias
        document.addEventListener('input', function(e) {
            var target = e.target;
            if (target && target.tagName === 'INPUT' && target.type === 'number') {
                // Si no tiene min, usar 0 por defecto; si tiene, usar ese
                var min = target.hasAttribute('min') ? parseFloat(target.getAttribute('min')) : 0;
                if (target.value !== '' && parseFloat(target.value) < min) {
                    target.value = String(min);
                }
            }
        }, true); // ← captura: se ejecuta antes que los listeners del elemento

        // También al perder el foco como respaldo
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
    // CHOICES.JS
    // =============================================
    function initializeChoices() {
        try {
            var turnoSelect = document.getElementById('turno');
            if (turnoSelect && typeof Choices !== 'undefined') {
                var choices = new Choices(turnoSelect, {
                    searchEnabled: false,
                    itemSelectText: '',
                    shouldSort: false
                });
                window.choicesInstances = window.choicesInstances || {};
                window.choicesInstances.turno = choices;
            }
        } catch (e) {
            console.warn('[KardexParihuelas] Error Choices:', e);
        }
    }

    // =============================================
    // TURNO AUTOMÁTICO
    // =============================================
    function setupTurnoAutomatico() {
        var turnoSelect = document.getElementById('turno');
        if (!turnoSelect) return;

        // Detectar turno según hora actual al cargar
        function detectarTurno() {
            var hora = window.horaActual || '00:00';
            var turnos = window.turnosData || [];

            for (var i = 0; i < turnos.length; i++) {
                var t = turnos[i];
                var inicio = t.HoraInicio || '';
                var fin = t.HoraFin || '';

                if (inicio && fin) {
                    if (hora >= inicio && hora < fin) {
                        turnoSelect.value = t.Id;
                        if (window.choicesInstances && window.choicesInstances.turno) {
                            window.choicesInstances.turno.setChoiceByValue(String(t.Id));
                        }
                        // Cargar datos automáticamente al detectar el turno
                        var fechaEl = document.getElementById('fecha');
                        if (fechaEl && fechaEl.value) {
                            cargarDatosKardex(fechaEl.value, t.Id);
                        }
                        return;
                    }
                }
            }
        }

        detectarTurno();
        setTimeout(detectarTurno, 300);
    }

    // =============================================
    // CARGA DE DATOS AL CAMBIAR FECHA/TURNO
    // =============================================
    function setupFechaTurnoChange() {
        var fecha = document.getElementById('fecha');
        var turno = document.getElementById('turno');

        function onChange() {
            if (fecha.value && turno.value) {
                cargarDatosKardex(fecha.value, turno.value);
            }
        }

        fecha.addEventListener('change', onChange);
        turno.addEventListener('change', onChange);

        // También detectar cambios en Choices.js
        if (window.choicesInstances && window.choicesInstances.turno) {
            turno.addEventListener('addItem', function(e) {
                onChange();
            });
        }
    }

    /**
     * AJAX: Cargar datos del kardex
     */
    function cargarDatosKardex(fecha, turno) {
        var url = window.APP_URL + '/kardexparihuelas/cargarDatos?fecha=' + encodeURIComponent(fecha) + '&turno=' + encodeURIComponent(turno);
        console.log('[KardexParihuelas] cargarDatosKardex: fecha=' + fecha + ', turno=' + turno + ', url=' + url);

        showLoading(true);

        fetch(url)
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                showLoading(false);
                console.log('[KardexParihuelas] Respuesta servidor:', JSON.stringify(data));

                if (!data.success) {
                    mostrarError(data.error || 'Error al cargar datos');
                    return;
                }

                if (data.existente) {
                    console.log('[KardexParihuelas] Kardex EXISTENTE, sf_aptas=' + (data.kardex.sf_aptas || 0) + ', sf_danadas=' + (data.kardex.sf_danadas || 0));
                    // Cargar kardex existente
                    poblarKardexExistente(data.kardex);
                    state.modo = 'ver';
                    state.kardexId = data.kardex.Id;
                    state.datosCargados = true;
                    habilitarBotones(true, false, true, false);
                } else {
                    console.log('[KardexParihuelas] Kardex NUEVO, stockInicial:', JSON.stringify(data.stockInicial));
                    // Nuevo kardex
                    poblarStockInicial(data.stockInicial);
                    poblarGrillaRecepciones(data.recepciones);
                    poblarGrillaDespachos(data.despachos);
                    limpiarAjustes();
                    limpiarStockFinal();
                    state.modo = 'nuevo';
                    state.kardexId = null;
                    state.datosCargados = true;
                    habilitarBotones(true, true, false, true);
                    calcularTotalesGrillas();
                    calcularStockFinal();
                }
            })
            .catch(function(err) {
                showLoading(false);
                console.error('[KardexParihuelas] Error de red:', err);
                mostrarError('Error de red: ' + err.message);
            });
    }

    // =============================================
    // POBLAR DATOS
    // =============================================
    function poblarStockInicial(si) {
        if (!si) si = {};
        setVal('si_asperjadas', si.si_asperjadas || 0);
        setVal('si_aptas', si.si_aptas || 0);
        setVal('si_danadas', si.si_danadas || 0);
        setVal('si_sucias', si.si_sucias || 0);
        setVal('si_por_seleccionar', si.si_por_seleccionar || 0);
        setVal('si_lavadas_secadas', si.si_lavadas_secadas || 0);

        // Calcular total stock inicial
        var total = (parseInt(si.si_asperjadas || 0) + parseInt(si.si_aptas || 0) +
                     parseInt(si.si_danadas || 0) + parseInt(si.si_sucias || 0) +
                     parseInt(si.si_por_seleccionar || 0) + parseInt(si.si_lavadas_secadas || 0));
        setVal('si_total', total);
    }

    function poblarGrillaRecepciones(recepciones) {
        var tbody = document.getElementById('tbodyRecepciones');
        tbody.innerHTML = '';

        if (!recepciones || recepciones.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;">Sin recepciones para esta fecha y turno</td></tr>';
            document.getElementById('totalRecepcionado').textContent = '0';
            return;
        }

        var total = 0;
        recepciones.forEach(function(r, idx) {
            var tr = document.createElement('tr');
            tr.dataset.index = idx;

            var item = r.item || (idx + 1);
            var subareaNombre = r.subarea_nombre || '--';
            var totalRec = parseInt(r.total_recepcionado || 0);
            total += totalRec;

            tr.innerHTML =
                '<td style="text-align:center;">' + item + '</td>' +
                '<td>' + escapeHtml(subareaNombre) + '</td>' +
                '<td><input type="number" class="form-control form-control-sm campo-rec campo-rec-manual" data-rec="' + idx + '" data-field="aptas" value="' + (parseInt(r.aptas) || 0) + '" step="1" min="0" style="width:55px;"></td>' +
                '<td><input type="number" class="form-control form-control-sm campo-rec campo-rec-manual" data-rec="' + idx + '" data-field="danadas" value="' + (parseInt(r.danadas) || 0) + '" step="1" min="0" style="width:55px;"></td>' +
                '<td><input type="number" class="form-control form-control-sm campo-rec campo-rec-manual" data-rec="' + idx + '" data-field="sucias" value="' + (parseInt(r.sucias) || 0) + '" step="1" min="0" style="width:55px;"></td>' +
                '<td><input type="number" class="form-control form-control-sm campo-rec campo-rec-manual" data-rec="' + idx + '" data-field="por_seleccionar" value="' + (parseInt(r.por_seleccionar) || 0) + '" step="1" min="0" style="width:55px;"></td>' +
                '<td style="text-align:center;font-weight:600;">' + formatNum(totalRec) + '</td>';

            tbody.appendChild(tr);
        });

        document.getElementById('totalRecepcionado').textContent = formatNum(total);

        // Event listeners para campos manuales de recepción
        tbody.querySelectorAll('.campo-rec-manual').forEach(function(input) {
            input.addEventListener('input', function() {
                calcularTotalesGrillas();
                calcularStockFinal();
            });
        });
    }

    function poblarGrillaDespachos(despachos) {
        var tbody = document.getElementById('tbodyDespachos');
        tbody.innerHTML = '';

        if (!despachos || despachos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#999;">Sin despachos para esta fecha y turno</td></tr>';
            document.getElementById('totalDespachado').textContent = '0';
            return;
        }

        var total = 0;
        despachos.forEach(function(d, idx) {
            var tr = document.createElement('tr');
            tr.dataset.index = idx;

            var item = d.item || (idx + 1);
            var subareaNombre = d.subarea_nombre || '--';
            var totalDesp = parseInt(d.total_despachado || 0);
            total += totalDesp;

            tr.innerHTML =
                '<td style="text-align:center;">' + item + '</td>' +
                '<td>' + escapeHtml(subareaNombre) + '</td>' +
                '<td style="text-align:center;font-weight:600;">' + formatNum(totalDesp) + '</td>';

            tbody.appendChild(tr);
        });

        document.getElementById('totalDespachado').textContent = formatNum(total);
    }

    function poblarKardexExistente(kardex) {
        if (!kardex) return;

        // Stock Inicial (puede venir actualizado si el turno anterior fue modificado)
        poblarStockInicial({
            si_asperjadas: kardex.si_asperjadas,
            si_aptas: kardex.si_aptas,
            si_danadas: kardex.si_danadas,
            si_sucias: kardex.si_sucias,
            si_por_seleccionar: kardex.si_por_seleccionar,
            si_lavadas_secadas: kardex.si_lavadas_secadas || kardex.si_lavadas || 0,
            si_total: kardex.si_total
        });

        // Grillas
        poblarGrillaRecepciones(kardex.recepciones || []);
        poblarGrillaDespachos(kardex.despachos || []);

        // Ajustes
        setVal('total_parihuelas_lavadas', kardex.total_parihuelas_lavadas || 0);
        setVal('clasif_aptas', kardex.clasif_aptas || 0);
        setVal('clasif_danadas', kardex.clasif_danadas || 0);
        setVal('clasif_relavado', kardex.clasif_relavado || 0);
        setVal('clasif_total', kardex.clasif_total || 0);
        setVal('reparadas_aptas', kardex.reparadas_aptas || 0);
        setVal('reparadas_sucias', kardex.reparadas_sucias || 0);
        setVal('reparadas_total', kardex.reparadas_total || 0);
        setVal('clasificadas_aptas', kardex.clasificadas_aptas || 0);
        setVal('clasificadas_danadas', kardex.clasificadas_danadas || 0);
        setVal('clasificadas_sucias', kardex.clasificadas_sucias || 0);
        setVal('clasificadas_total', kardex.clasificadas_total || 0);
        setVal('reseleccion', kardex.reseleccion || 0);
        setVal('reparacion', kardex.reparacion || 0);
        setVal('asperjadas_turno', kardex.asperjadas_turno || 0);
        setVal('despacho_asperjadas', kardex.despacho_asperjadas || 0);
        setVal('autoservicios', kardex.autoservicios || 0);
        setVal('observadas', kardex.observadas || 0);
        setVal('ajuste_manual_sf_total', kardex.ajuste_manual_sf_total || 0);

        // ✅ CORRECCIÓN: Recalcular stock final completo en lugar de solo cargar
        // los valores guardados, para que el sf_total refleje correctamente
        // cualquier cambio en el si_* (actualizado por el servidor si el turno
        // anterior fue modificado). Los sf_* guardados pueden estar obsoletos.
        calcularTotalesGrillas();
        calcularStockFinal();
    }

    function limpiarAjustes() {
        var campos = ['total_parihuelas_lavadas', 'clasif_aptas', 'clasif_danadas', 'clasif_relavado',
                      'clasif_total', 'reparadas_aptas', 'reparadas_sucias', 'reparadas_total',
                      'clasificadas_aptas', 'clasificadas_danadas', 'clasificadas_sucias', 'clasificadas_total',
                      'reseleccion', 'reparacion',
                      'asperjadas_turno', 'despacho_asperjadas', 'autoservicios', 'observadas'];
        campos.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '0';
        });
    }

    function limpiarStockFinal() {
        var campos = ['sf_asperjadas', 'sf_aptas', 'sf_danadas', 'sf_sucias',
                      'sf_por_seleccionar', 'sf_lavadas_secadas', 'sf_total',
                      'ajuste_manual_sf_total'];
        campos.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '0';
        });
    }

    // =============================================
    // CÁLCULOS AUTOMÁTICOS
    // =============================================
    function setupCalculosAutomaticos() {
        // Clasificación Lavadas y Secadas
        ['clasif_aptas', 'clasif_danadas', 'clasif_relavado'].forEach(function(id) {
            document.getElementById(id).addEventListener('input', function() {
                calcularClasifTotal();
                calcularStockFinal();
            });
        });

        // Reparadas
        ['reparadas_aptas', 'reparadas_sucias'].forEach(function(id) {
            document.getElementById(id).addEventListener('input', function() {
                calcularReparadasTotal();
                calcularStockFinal();
            });
        });

        // Clasificadas
        ['clasificadas_aptas', 'clasificadas_danadas', 'clasificadas_sucias'].forEach(function(id) {
            document.getElementById(id).addEventListener('input', function() {
                calcularClasificadasTotal();
                calcularStockFinal();
            });
        });

        // Parihuelas lavadas, reselección, reparación
        ['total_parihuelas_lavadas', 'reseleccion', 'reparacion'].forEach(function(id) {
            document.getElementById(id).addEventListener('input', function() {
                calcularStockFinal();
            });
        });

        // Asperjadas del turno y despacho asperjadas (afectan sf_asperjadas)
        ['asperjadas_turno', 'despacho_asperjadas'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function() {
                    calcularStockFinal();
                });
            }
        });

        // Stock inicial - cualquier cambio debe recalcular stock final
        document.querySelectorAll('.campo-si').forEach(function(el) {
            el.addEventListener('input', function() {
                // Recalcular total stock inicial
                var total = 0;
                document.querySelectorAll('.campo-si').forEach(function(inp) {
                    if (inp.id !== 'si_total') {
                        total += parseInt(inp.value) || 0;
                    }
                });
                document.getElementById('si_total').value = total;
                calcularStockFinal();
            });
        });

        // asperjadas_turno → despacho_asperjadas se actualiza automáticamente
        var aspTurnoEl = document.getElementById('asperjadas_turno');
        if (aspTurnoEl) {
            aspTurnoEl.addEventListener('input', function() {
                var despAspInput = document.getElementById('despacho_asperjadas');
                if (despAspInput) {
                    despAspInput.value = this.value;
                }
                calcularStockFinal();
            });
        }

        // sf_lavadas_secadas es manual - recalcular total al editarlo
        var sfLavSec = document.getElementById('sf_lavadas_secadas');
        if (sfLavSec) {
            sfLavSec.addEventListener('input', function() {
                calcularStockFinal();
            });
        }

        // Autoservicios y observadas
        ['autoservicios', 'observadas'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function() {
                    calcularStockFinal();
                });
            }
        });
    }

    function calcularClasifTotal() {
        var aptas = parseInt(getVal('clasif_aptas')) || 0;
        var danadas = parseInt(getVal('clasif_danadas')) || 0;
        var relavado = parseInt(getVal('clasif_relavado')) || 0;
        setVal('clasif_total', aptas + danadas + relavado);
    }

    function calcularReparadasTotal() {
        var aptas = parseInt(getVal('reparadas_aptas')) || 0;
        var sucias = parseInt(getVal('reparadas_sucias')) || 0;
        setVal('reparadas_total', aptas + sucias);
    }

    function calcularClasificadasTotal() {
        var aptas = parseInt(getVal('clasificadas_aptas')) || 0;
        var danadas = parseInt(getVal('clasificadas_danadas')) || 0;
        var sucias = parseInt(getVal('clasificadas_sucias')) || 0;
        setVal('clasificadas_total', aptas + danadas + sucias);
    }

    // =============================================
    // VALIDACIÓN DE COINCIDENCIA EN GRILLAS
    // =============================================
    function validarCoincidenciaGrillas() {
        // --- Validación Recepciones ---
        var tbodyRec = document.getElementById('tbodyRecepciones');
        tbodyRec.querySelectorAll('tr').forEach(function(tr) {
            var inputs = tr.querySelectorAll('.campo-rec-manual');
            if (inputs.length < 4) return;

            var suma = 0;
            inputs.forEach(function(inp) {
                suma += parseInt(inp.value) || 0;
            });

            var tds = tr.querySelectorAll('td');
            var totalRecTd = tds[tds.length - 1]; // última celda = Total Recepcionado
            var totalRec = parseInt(totalRecTd.textContent.replace(/,/g, '')) || 0;

            // Remover clase de validación previa
            tr.classList.remove('tr-no-coincide');
            totalRecTd.classList.remove('td-no-coincide');

            if (suma !== totalRec) {
                tr.classList.add('tr-no-coincide');
                totalRecTd.classList.add('td-no-coincide');
            }
        });
    }

    function calcularTotalesGrillas() {
        var tbodyRec = document.getElementById('tbodyRecepciones');
        var recAptas = 0, recDanadas = 0, recSucias = 0, recPorSel = 0, totalRecBD = 0;

        tbodyRec.querySelectorAll('tr').forEach(function(tr) {
            var inputs = tr.querySelectorAll('.campo-rec-manual');
            if (inputs.length >= 4) {
                recAptas += parseInt(inputs[0].value) || 0;
                recDanadas += parseInt(inputs[1].value) || 0;
                recSucias += parseInt(inputs[2].value) || 0;
                recPorSel += parseInt(inputs[3].value) || 0;
            }
            var lastTd = tr.querySelector('td:last-child');
            if (lastTd) {
                totalRecBD += parseInt(lastTd.textContent.replace(/,/g, '')) || 0;
            }
        });

        document.getElementById('totalRecAptas').textContent = formatNum(recAptas);
        document.getElementById('totalRecDanadas').textContent = formatNum(recDanadas);
        document.getElementById('totalRecSucias').textContent = formatNum(recSucias);
        document.getElementById('totalRecPorSel').textContent = formatNum(recPorSel);
        document.getElementById('totalRecepcionado').textContent = formatNum(totalRecBD);

        // Total Despachos
        var tbodyDes = document.getElementById('tbodyDespachos');
        var totalDesBD = 0;

        tbodyDes.querySelectorAll('tr').forEach(function(tr) {
            var lastTd = tr.querySelector('td:last-child');
            if (lastTd) {
                totalDesBD += parseInt(lastTd.textContent.replace(/,/g, '')) || 0;
            }
        });

        document.getElementById('totalDespachado').textContent = formatNum(totalDesBD);

        // Auto-actualizar asperjadas_turno con el total despachado
        var aspTurnoInput = document.getElementById('asperjadas_turno');
        if (aspTurnoInput) {
            aspTurnoInput.value = formatNum(totalDesBD);
        }
        // despacho_asperjadas = asperjadas_turno
        var despAspInput = document.getElementById('despacho_asperjadas');
        if (despAspInput) {
            despAspInput.value = formatNum(totalDesBD);
        }

        validarCoincidenciaGrillas();
    }

    function calcularStockFinal() {
        // Stock Inicial
        var si_aspe = parseInt(getVal('si_asperjadas')) || 0;
        var si_aptas = parseInt(getVal('si_aptas')) || 0;
        var si_danadas = parseInt(getVal('si_danadas')) || 0;
        var si_sucias = parseInt(getVal('si_sucias')) || 0;
        var si_por_sel = parseInt(getVal('si_por_seleccionar')) || 0;
        var si_lavadas_sec = parseInt(getVal('si_lavadas_secadas')) || 0;

        // Ajustes (ingreso manual)
        var totalLavadas = parseInt(getVal('total_parihuelas_lavadas')) || 0;
        var clasifAptas = parseInt(getVal('clasif_aptas')) || 0;
        var clasifDanadas = parseInt(getVal('clasif_danadas')) || 0;
        var clasifRelavado = parseInt(getVal('clasif_relavado')) || 0;
        var reparadasAptas = parseInt(getVal('reparadas_aptas')) || 0;
        var reparadasSucias = parseInt(getVal('reparadas_sucias')) || 0;
        var reparadasTotal = parseInt(getVal('reparadas_total')) || 0;
        var clasificadasAptas = parseInt(getVal('clasificadas_aptas')) || 0;
        var clasificadasDanadas = parseInt(getVal('clasificadas_danadas')) || 0;
        var clasificadasSucias = parseInt(getVal('clasificadas_sucias')) || 0;
        var clasificadasTotal = parseInt(getVal('clasificadas_total')) || 0;
        var reseleccion = parseInt(getVal('reseleccion')) || 0;
        var reparacion = parseInt(getVal('reparacion')) || 0;
        var aspTurno = parseInt(getVal('asperjadas_turno')) || 0;
        var despAsp = parseInt(getVal('despacho_asperjadas')) || 0;
        var autoserv = parseInt(getVal('autoservicios')) || 0;
        var observ = parseInt(getVal('observadas')) || 0;

        // Totales de grillas auto-cargados
        var totalRec = parseInt(document.getElementById('totalRecepcionado').textContent.replace(/,/g, '')) || 0;
        var totalDes = parseInt(document.getElementById('totalDespachado').textContent.replace(/,/g, '')) || 0;

        // Totales de campos manuales en grilla de recepciones
        var totalRecAptas = parseInt(document.getElementById('totalRecAptas').textContent) || 0;
        var totalRecDanadas = parseInt(document.getElementById('totalRecDanadas').textContent) || 0;
        var totalRecSucias = parseInt(document.getElementById('totalRecSucias').textContent) || 0;
        var totalRecPorSel = parseInt(document.getElementById('totalRecPorSel').textContent) || 0;
        // Ya no hay campos manuales en despachos (se quitaron Tratadas/Especiales)
        var totalDesTratadas = 0;
        var totalDesEspeciales = 0;

        // Fórmulas Stock Final:
        var sf_asperjadas = Math.max(0, si_aspe + aspTurno - despAsp);
        var sf_aptas = Math.max(0, si_aptas + totalRecAptas + clasifAptas + reparadasAptas + clasificadasAptas + reseleccion - aspTurno + observ);
        var sf_danadas = Math.max(0, si_danadas + totalRecDanadas + clasifDanadas - reparadasTotal + clasificadasDanadas - reseleccion - totalDesEspeciales);
        var sf_sucias = Math.max(0, si_sucias + totalRecSucias + reparadasSucias + clasificadasSucias - totalLavadas - clasifAptas);
        var sf_por_sel = Math.max(0, si_por_sel + totalRecPorSel - clasificadasTotal);
        // sf_lavadas_secadas es manual, se toma directo del input
        var sf_lavadas_sec = parseInt(getVal('sf_lavadas_secadas')) || 0;

        // Asegurar que ningún valor sea negativo
        sf_por_sel = Math.max(0, sf_por_sel);

        var sf_total = sf_asperjadas + sf_aptas + sf_danadas + sf_sucias + sf_por_sel + sf_lavadas_sec;

        setVal('sf_asperjadas', sf_asperjadas);
        setVal('sf_aptas', sf_aptas);
        setVal('sf_danadas', sf_danadas);
        setVal('sf_sucias', sf_sucias);
        setVal('sf_por_seleccionar', sf_por_sel);
        setVal('sf_lavadas_secadas', sf_lavadas_sec);
        setVal('sf_total', sf_total);
    }

    // =============================================
    // BOTONES
    // =============================================
    function btnNuevoClick() {
        // No-op por ahora
        return;
    }

    function btnGuardarClick() {
        // Verificar privilegio directamente como respaldo
        if (!tienePrivilegio('guardar_kardex_parihuelas')) {
            mostrarError('No tiene permiso para guardar en el Kardex de Parihuelas');
            return;
        }

        var fecha = document.getElementById('fecha').value;
        var turno = document.getElementById('turno').value;

        if (!fecha || !turno) {
            mostrarError('Debe seleccionar fecha y turno');
            return;
        }

        // Ejecutar validación de coincidencia antes de guardar
        validarCoincidenciaGrillas();

        // Verificar si hay filas con error de coincidencia
        var filasError = document.querySelectorAll('.tr-no-coincide');
        if (filasError.length > 0) {
            mostrarError('No se puede guardar: ' + filasError.length + ' fila(s) tienen valores que no coinciden con el total. Corrija los campos resaltados en rojo.');
            return;
        }

        // Recolectar datos del formulario
        var data = recolectarDatos();

        // Recolectar datos de grillas
        data.recepciones = recolectarGrillaRecepciones();
        data.despachos = recolectarGrillaDespachos();

        showLoading(true);

        fetch(window.APP_URL + '/kardexparihuelas/guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(resp) { return resp.json(); })
        .then(function(result) {
            showLoading(false);
            if (result.success) {
                var msg = 'Kardex guardado exitosamente';
                if (result.modificaciones_restantes !== undefined) {
                    msg += '. Modificaciones restantes: ' + result.modificaciones_restantes;
                }
                mostrarExito(msg);
                state.modo = 'ver';
                state.kardexId = result.id;
                habilitarBotones(true, false, true, true);
            } else if (result.limite_alcanzado) {
                mostrarError(result.error || 'Límite de modificaciones alcanzado');
                state.modo = 'ver';
                habilitarBotones(true, false, true, true);
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
        if (state.modo === 'ver' && state.kardexId) {
            // Verificar límite de modificaciones antes de permitir edición
            showLoading(true);
            fetch(window.APP_URL + '/kardexparihuelas/verificarModificaciones?id=' + state.kardexId)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    showLoading(false);
                    if (data.puede_modificar) {
                        state.modo = 'edicion';
                        habilitarBotones(true, true, false, true);
                        if (data.restantes > 0 && data.restantes < 999) {
                            mostrarExito('Modificaciones restantes: ' + data.restantes);
                        }
                    } else {
                        mostrarError(data.mensaje || 'No puede modificar este kardex');
                    }
                })
                .catch(function() {
                    showLoading(false);
                    mostrarError('Error al verificar modificaciones');
                });
        }
    }

    function btnImprimirClick() {
        abrirModalImpresion();
    }

    // =============================================
    // RECOLECCIÓN DE DATOS
    // =============================================
    function recolectarDatos() {
        var sfTotalCalculado = parseInt(getVal('sf_total')) || 0;
        var ajusteManual = parseInt(getVal('ajuste_manual_sf_total')) || 0;

        return {
            fecha: document.getElementById('fecha').value,
            turno: document.getElementById('turno').value,
            si_asperjadas: getVal('si_asperjadas'),
            si_aptas: getVal('si_aptas'),
            si_danadas: getVal('si_danadas'),
            si_sucias: getVal('si_sucias'),
            si_por_seleccionar: getVal('si_por_seleccionar'),
            si_lavadas: getVal('si_lavadas_secadas'),
            si_secas: 0,
            si_total: getVal('si_total'),
            total_recepcionado: parseInt(document.getElementById('totalRecepcionado').textContent.replace(/,/g, '')) || 0,
            total_despachado: parseInt(document.getElementById('totalDespachado').textContent.replace(/,/g, '')) || 0,
            total_parihuelas_lavadas: getVal('total_parihuelas_lavadas'),
            clasif_aptas: getVal('clasif_aptas'),
            clasif_danadas: getVal('clasif_danadas'),
            clasif_relavado: getVal('clasif_relavado'),
            clasif_total: getVal('clasif_total'),
            reparadas_aptas: getVal('reparadas_aptas'),
            reparadas_sucias: getVal('reparadas_sucias'),
            reparadas_total: getVal('reparadas_total'),
            clasificadas_aptas: getVal('clasificadas_aptas'),
            clasificadas_danadas: getVal('clasificadas_danadas'),
            clasificadas_sucias: getVal('clasificadas_sucias'),
            clasificadas_total: getVal('clasificadas_total'),
            reseleccion: getVal('reseleccion'),
            reparacion: getVal('reparacion'),
            asperjadas_turno: getVal('asperjadas_turno'),
            despacho_asperjadas: getVal('despacho_asperjadas'),
            autoservicios: getVal('autoservicios'),
            observadas: getVal('observadas'),
            sf_asperjadas: getVal('sf_asperjadas'),
            sf_aptas: getVal('sf_aptas'),
            sf_danadas: getVal('sf_danadas'),
            sf_sucias: getVal('sf_sucias'),
            sf_por_seleccionar: getVal('sf_por_seleccionar'),
            sf_lavadas_secadas: getVal('sf_lavadas_secadas'),
            // Si hay ajuste manual, ese valor reemplaza al sf_total calculado
            sf_total: ajusteManual > 0 ? ajusteManual : sfTotalCalculado,
            ajuste_manual_sf_total: ajusteManual,
            nota: 'Importante: Toda recepción se selecciona de inmediato previo a su ingreso al almacén.'
        };
    }

    function recolectarGrillaRecepciones() {
        var rows = [];
        document.querySelectorAll('#tbodyRecepciones tr').forEach(function(tr) {
            var inputs = tr.querySelectorAll('.campo-rec-manual');
            if (inputs.length === 0) return;

            var tds = tr.querySelectorAll('td');
            if (tds.length < 7) return;

            rows.push({
                item: parseInt(tds[0].textContent) || 0,
                subarea_nombre: tds[1].textContent.trim(),
                aptas: parseInt(inputs[0].value) || 0,
                danadas: parseInt(inputs[1].value) || 0,
                sucias: parseInt(inputs[2].value) || 0,
                por_seleccionar: parseInt(inputs[3].value) || 0,
                total_recepcionado: parseInt(tds[6].textContent.replace(/,/g, '')) || 0
            });
        });
        return rows;
    }

    function recolectarGrillaDespachos() {
        var rows = [];
        document.querySelectorAll('#tbodyDespachos tr').forEach(function(tr) {
            var tds = tr.querySelectorAll('td');
            if (tds.length < 3) return;

            rows.push({
                item: parseInt(tds[0].textContent) || 0,
                subarea_nombre: tds[1].textContent.trim(),
                tratadas: 0,
                especiales: 0,
                total_despachado: parseInt(tds[2].textContent.replace(/,/g, '')) || 0
            });
        });
        return rows;
    }

    // =============================================
    // VISTA PREVIA DE IMPRESIÓN
    // =============================================
    function abrirModalImpresion() {
        var html = generarVistaPreviaHTML();
        var estiloModal = '' +
            '<style>' +
            '.kp-preview{font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#000;padding:6px;}' +
            '.kp-preview table{width:100%;border-collapse:collapse;margin-bottom:5px;}' +
            '.kp-preview th,.kp-preview td{border:1px solid #000;padding:2px 4px;text-align:center;font-size:9px;}' +
            '.kp-preview th{background:#d9d9d9;}' +
            '.kp-preview .titulo{font-size:14px;text-align:center;margin-bottom:4px;}' +
            '.kp-preview .header-info{display:flex;justify-content:center;gap:40px;margin-bottom:6px;font-size:10px;}' +
            '.kp-preview .section-title{background:#d9d9d9;padding:3px 4px;margin-top:5px;margin-bottom:3px;font-size:9px;text-align:left;}' +
            '.kp-preview .nota{text-align:left;font-style:italic;padding:3px;margin:5px 0;font-size:8px;}' +
            '.kp-preview .firma{margin-top:70px;text-align:center;}' +
            '.kp-preview .firma hr{width:260px;margin:6px auto;border:none;border-top:1px solid #000;}' +
            '.kp-preview .firma .firma-label{font-size:9px;margin-top:3px;}' +
            '.kp-preview .total-row td:first-child,.kp-preview .total-row th:first-child{background:#000;color:#fff;}' +
            '.kp-preview .label-left{text-align:left;padding:2px 8px;}' +
            '</style>';

        var modal = document.getElementById('modalKardexPreview');
        var content = document.getElementById('kardexPreviewContent');
        content.innerHTML = estiloModal + '<div class="kp-preview">' + html + '</div>';

        var modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        // Configurar botón de impresión (usando iframe oculto, sin abrir pestaña)
        document.getElementById('btnKardexPrint').onclick = function() {
            var stylePrint = '' +
                'body{font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#000;margin:0;padding:10px;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                'table{width:100%;border-collapse:collapse;margin-bottom:6px;}' +
                'th,td{border:1px solid #000;padding:3px 5px;text-align:center;font-size:9px;}' +
                'th{background:#d9d9d9;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                '.titulo{font-size:15px;text-align:center;margin-bottom:5px;}' +
                '.header-info{display:flex;justify-content:center;gap:50px;margin-bottom:8px;font-size:11px;}' +
                '.section-title{background:#d9d9d9;padding:4px 6px;margin-top:8px;margin-bottom:4px;font-size:10px;text-align:left;border:1px solid #000;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                '.nota{text-align:left;font-style:italic;padding:4px;margin:6px 0;font-size:9px;}' +
                '.firma{margin-top:80px;text-align:center;}' +
                '.firma hr{width:300px;margin:10px auto;border:none;border-top:1px solid #000;height:0;}' +
                '.firma .firma-label{font-size:10px;margin-top:5px;}' +
                '.total-row td:first-child,.total-row th:first-child{background:#000;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                '.label-left{text-align:left;padding:2px 10px;}' +
                '@page{size:A4 portrait;margin:8mm;}';

            var iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.style.visibility = 'hidden';
            document.body.appendChild(iframe);

            var fullHtml = '<!doctype html><html><head><meta charset="utf-8"><title>Kardex Parihuelas Estándar</title><style>' + stylePrint + '</style></head><body>' + html + '</body></html>';

            try {
                iframe.srcdoc = fullHtml;
            } catch(e) {
                iframe.src = 'about:blank';
            }

            var printed = false;
            function doPrint() {
                if (printed) return;
                printed = true;
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch(e) {}
                setTimeout(function() {
                    try { if (iframe.parentNode) iframe.parentNode.removeChild(iframe); } catch(e) {}
                }, 800);
            }

            iframe.onload = function() {
                setTimeout(doPrint, 100);
            };
            setTimeout(function() {
                if (!printed) doPrint();
            }, 1500);
        };

        // Configurar botón de exportar Excel
        document.getElementById('btnKardexExcel').onclick = function() {
            exportarExcelKardex();
        };
    }

    /**
     * Exportar el reporte del kardex a Excel (.xlsx) mediante el servidor
     * Envía todos los datos actuales del formulario al controlador,
     * que genera un archivo Excel profesional con PhpSpreadsheet.
     */
    function exportarExcelKardex() {
        // Recolectar datos del formulario (misma lógica que recolectarDatos)
        var data = recolectarDatos();

        // Recolectar recepciones agrupadas por área (igual que en generarVistaPreviaHTML)
        var gruposRec = [];
        document.querySelectorAll('#tbodyRecepciones tr').forEach(function(tr) {
            if (tr.querySelector('td')) {
                var tds = tr.querySelectorAll('td');
                if (tds.length >= 7) {
                    var areaOrigen = tds[1].textContent.trim();
                    var aptas = 0, danadas = 0, sucias = 0, porSel = 0, totalRecRow = 0;
                    for (var i = 2; i <= 5; i++) {
                        var input = tds[i].querySelector('input');
                        var val = input ? input.value : tds[i].textContent.trim();
                        if (i === 2) aptas = parseFloat(val) || 0;
                        else if (i === 3) danadas = parseFloat(val) || 0;
                        else if (i === 4) sucias = parseFloat(val) || 0;
                        else if (i === 5) porSel = parseFloat(val) || 0;
                    }
                    totalRecRow = parseFloat(tds[6].textContent.trim().replace(/,/g, '')) || 0;
                    gruposRec.push({
                        area: areaOrigen,
                        aptas: aptas,
                        danadas: danadas,
                        sucias: sucias,
                        porSel: porSel,
                        total: totalRecRow
                    });
                }
            }
        });

        // Recolectar despachos agrupados por área
        var gruposDes = [];
        document.querySelectorAll('#tbodyDespachos tr').forEach(function(tr) {
            if (tr.querySelector('td')) {
                var tds = tr.querySelectorAll('td');
                if (tds.length >= 3) {
                    gruposDes.push({
                        area: tds[1].textContent.trim(),
                        total: parseFloat(tds[2].textContent.trim().replace(/,/g, '')) || 0
                    });
                }
            }
        });

        data.recepcionesAgrupadas = gruposRec;
        data.despachosAgrupados = gruposDes;

        // Recolectar totales de las columnas de recepción
        data.totalRecAptas = document.getElementById('totalRecAptas').textContent;
        data.totalRecDanadas = document.getElementById('totalRecDanadas').textContent;
        data.totalRecSucias = document.getElementById('totalRecSucias').textContent;
        data.totalRecPorSel = document.getElementById('totalRecPorSel').textContent;

        // Obtener nombre del turno
        var turnoEl = document.getElementById('turno');
        data.turnoNombre = turnoEl.options[turnoEl.selectedIndex] ? turnoEl.options[turnoEl.selectedIndex].text : '';

        // Enviar al servidor
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.APP_URL + '/kardexparihuelas/exportarExcel', true);
        xhr.responseType = 'blob';

        xhr.onload = function() {
            if (xhr.status === 200) {
                var blob = xhr.response;
                var fechaRaw = document.getElementById('fecha').value;
                var fecha = fechaRaw ? fechaRaw.split('-').reverse().join('-') : '';
                var turnoText = data.turnoNombre.replace(/\s+/g, '_');
                var nombreArchivo = 'Kardex_Parihuelas_' + fecha + '_' + turnoText + '.xlsx';
                nombreArchivo = nombreArchivo.replace(/[\\/:*?"<>|]/g, '_');

                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = nombreArchivo;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            } else {
                mostrarError('Error al exportar Excel');
            }
        };

        xhr.onerror = function() {
            mostrarError('Error de red al exportar Excel');
        };

        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify(data));
    }

    function generarVistaPreviaHTML() {
        var fechaRaw = document.getElementById('fecha').value;
        // Formatear de YYYY-MM-DD a DD-MM-YYYY
        var fecha = fechaRaw ? fechaRaw.split('-').reverse().join('-') : '';
        var turnoEl = document.getElementById('turno');
        var turnoText = turnoEl.options[turnoEl.selectedIndex] ? turnoEl.options[turnoEl.selectedIndex].text : '';

        var si_aspe = getVal('si_asperjadas');
        var si_aptas = getVal('si_aptas');
        var si_dan = getVal('si_danadas');
        var si_suc = getVal('si_sucias');
        var si_ps = getVal('si_por_seleccionar');
        var si_lav_sec = getVal('si_lavadas_secadas');
        var si_tot = getVal('si_total');

        var totalRec = document.getElementById('totalRecepcionado').textContent;
        var totalDes = document.getElementById('totalDespachado').textContent;

        var totLav = getVal('total_parihuelas_lavadas');
        var cAptas = getVal('clasif_aptas');
        var cDan = getVal('clasif_danadas');
        var cRel = getVal('clasif_relavado');
        var cTot = getVal('clasif_total');
        var rAptas = getVal('reparadas_aptas');
        var rSuc = getVal('reparadas_sucias');
        var rTot = getVal('reparadas_total');
        var clAptas = getVal('clasificadas_aptas');
        var clDan = getVal('clasificadas_danadas');
        var clSuc = getVal('clasificadas_sucias');
        var clTot = getVal('clasificadas_total');
        var resel = getVal('reseleccion');
        var repar = getVal('reparacion');
        var aspTurno = getVal('asperjadas_turno');
        var despAsp = getVal('despacho_asperjadas');
        var autoserv = getVal('autoservicios');
        var observ = getVal('observadas');

        var sf_aspe = getVal('sf_asperjadas');
        var sf_aptas = getVal('sf_aptas');
        var sf_dan = getVal('sf_danadas');
        var sf_suc = getVal('sf_sucias');
        var sf_ps = getVal('sf_por_seleccionar');
        var sf_lav_sec = getVal('sf_lavadas_secadas');
        var sf_tot = getVal('sf_total');

        var logoUrl = window.BASE_URL + '/img/Logo-Lavoro-1536x442.png';

        var html = '';

        // Encabezado con borde: logo | título | código+versión
        html += '<div style="display:flex;align-items:stretch;border:1px solid #000;">';
        // Logo izquierda con borde derecho
        html += '<div style="flex:0 0 110px;display:flex;align-items:center;justify-content:center;border-right:1px solid #000;padding:4px;">';
        html += '<img src="' + logoUrl + '" style="max-width:100px;height:auto;" alt="Logo">';
        html += '</div>';
        // Título centrado
        html += '<div style="flex:1;text-align:center;padding:6px 8px;display:flex;align-items:center;justify-content:center;">';
        html += '<div class="titulo">REPORTE DE RECEPCIÓN, DESPACHOS Y STOCKS<br>DE PARIHUELAS ESTÁNDAR</div>';
        html += '</div>';
        // Código y versión derecha con borde izquierdo
        html += '<div style="flex:0 0 140px;text-align:left;font-size:8px;border-left:1px solid #000;display:flex;flex-direction:column;">';
        // Fila Código con borde inferior completo
        html += '<div style="display:flex;flex:1;border-bottom:1px solid #000;">';
        html += '<span style="flex:1;padding:3px 4px;border-right:1px solid #000;">Código:</span>';
        html += '<span style="flex:1;padding:3px 4px;text-align:right;color:#1565c0;">&nbsp;</span>';
        html += '</div>';
        // Fila Versión
        html += '<div style="display:flex;flex:1;">';
        html += '<span style="flex:1;padding:3px 4px;border-right:1px solid #000;">Versión:</span>';
        html += '<span style="flex:1;padding:3px 4px;text-align:right;color:#1565c0;">&nbsp;</span>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        // Fecha y Turno debajo del cuadro (con espacio)
        html += '<div style="display:flex;justify-content:center;gap:50px;margin-top:12px;margin-bottom:10px;font-size:10px;">';
        html += '<span><span style="">Fecha:</span> ' + fecha + '</span>';
        html += '<span><span style="">Turno:</span> ' + turnoText + '</span>';
        html += '</div>';

        // Stock Inicial (integrado en la tabla)
        html += '<table>';
        html += '<tr><th rowspan="2" style="vertical-align:middle;width:90px;">STOCK INICIAL</th><th>Asperjadas</th><th>Aptas</th><th>Dañadas</th><th>Sucias</th><th>Por Seleccionar</th><th>Lavadas y Secadas</th><th>Total</th></tr>';
        html += '<tr><td>' + si_aspe + '</td><td style="background:#fff3cd;">' + si_aptas + '</td><td style="background:#ffe0b2;">' + si_dan + '</td><td style="background:#bbdefb;">' + si_suc + '</td><td style="background:#c8e6c9;">' + si_ps + '</td><td>' + si_lav_sec + '</td><td style="color:#1565c0;"><span style="">' + si_tot + '</span></td></tr>';
        html += '</table>';

        // Grillas Recepciones y Despachos lado a lado
        html += '<div style="display:flex;gap:8px;align-items:flex-start;">';
        
        // Recepciones
        html += '<div style="flex:1;">';
        html += '<div class="section-title">RESUMEN DE RECEPCIONES DE PARIHUELAS ESTÁNDAR</div>';
        html += '<table>';
        html += '<tr><th style="width:30px;">Item</th><th>Área Origen</th><th style="background:#fff3cd;">Aptas</th><th style="background:#ffe0b2;">Dañadas</th><th style="background:#bbdefb;">Sucias</th><th style="background:#c8e6c9;">Por Sel.</th><th>Total Rec.</th></tr>';
        // Agrupar recepciones por Área Origen
        var gruposRec = {};
        document.querySelectorAll('#tbodyRecepciones tr').forEach(function(tr) {
            if (tr.querySelector('td')) {
                var tds = tr.querySelectorAll('td');
                if (tds.length >= 7) {
                    var areaOrigen = tds[1].textContent.trim();
                    var aptas = 0, danadas = 0, sucias = 0, porSel = 0, totalRecRow = 0;
                    for (var i = 2; i <= 5; i++) {
                        var input = tds[i].querySelector('input');
                        var val = input ? input.value : tds[i].textContent.trim();
                        if (i === 2) aptas = parseFloat(val) || 0;
                        else if (i === 3) danadas = parseFloat(val) || 0;
                        else if (i === 4) sucias = parseFloat(val) || 0;
                        else if (i === 5) porSel = parseFloat(val) || 0;
                    }
                    totalRecRow = parseFloat(tds[6].textContent.trim().replace(/,/g, '')) || 0;
                    if (gruposRec[areaOrigen]) {
                        gruposRec[areaOrigen].aptas += aptas;
                        gruposRec[areaOrigen].danadas += danadas;
                        gruposRec[areaOrigen].sucias += sucias;
                        gruposRec[areaOrigen].porSel += porSel;
                        gruposRec[areaOrigen].totalRecRow += totalRecRow;
                    } else {
                        gruposRec[areaOrigen] = {
                            aptas: aptas,
                            danadas: danadas,
                            sucias: sucias,
                            porSel: porSel,
                            totalRecRow: totalRecRow
                        };
                    }
                }
            }
        });
        var itemRec = 1;
        for (var area in gruposRec) {
            if (gruposRec.hasOwnProperty(area)) {
                var g = gruposRec[area];
                html += '<tr>';
                html += '<td style="text-align:center;">' + itemRec + '</td>';
                html += '<td>' + escapeHtml(area) + '</td>';
                html += '<td style="background:#fff3cd;">' + formatNum(g.aptas) + '</td>';
                html += '<td style="background:#ffe0b2;">' + formatNum(g.danadas) + '</td>';
                html += '<td style="background:#bbdefb;">' + formatNum(g.sucias) + '</td>';
                html += '<td style="background:#c8e6c9;">' + formatNum(g.porSel) + '</td>';
                html += '<td style="text-align:center;font-weight:600;">' + formatNum(g.totalRecRow) + '</td>';
                html += '</tr>';
                itemRec++;
            }
        }
        html += '<tr class="total-row"><td colspan="2" style="text-align:center;">TOTAL</td><td style="background:#fff3cd;">' + document.getElementById('totalRecAptas').textContent + '</td><td style="background:#ffe0b2;">' + document.getElementById('totalRecDanadas').textContent + '</td><td style="background:#bbdefb;">' + document.getElementById('totalRecSucias').textContent + '</td><td style="background:#c8e6c9;">' + document.getElementById('totalRecPorSel').textContent + '</td><td>' + totalRec + '</td></tr>';
        html += '</table>';
        html += '</div>';

        // Despachos
        html += '<div style="flex:1;">';
        html += '<div class="section-title">RESUMEN DE DESPACHOS DE PARIHUELAS ESTÁNDAR</div>';
        html += '<table>';
        html += '<tr><th style="width:30px;">Item</th><th>Área Destino</th><th>Total Desp.</th></tr>';
        document.querySelectorAll('#tbodyDespachos tr').forEach(function(tr) {
            if (tr.querySelector('td')) {
                var tds = tr.querySelectorAll('td');
                if (tds.length >= 3) {
                    html += '<tr>';
                    for (var i = 0; i < 3; i++) {
                        var val = tds[i].textContent.trim();
                        html += '<td>' + val + '</td>';
                    }
                    html += '</tr>';
                }
            }
        });
        html += '<tr class="total-row"><td colspan="2" style="text-align:center;">TOTAL</td><td>' + totalDes + '</td></tr>';
        html += '</table>';
        html += '</div>';

        html += '</div>'; // cierre flex row

        // Nota
        html += '<div class="nota">Importante: Toda recepción se selecciona de inmediato previo a su ingreso al almacén.</div>';

        // Ajustes lado izquierdo y Nuevos campos lado derecho
        html += '<div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px;">';
        
        // Columna izquierda: Total Lavadas, Clasificación, Reparadas, Clasificadas, Reselección, Reparación
        html += '<div style="flex:1;">';
        // Total Parihuelas Lavadas
        html += '<div style="display:flex;align-items:center;margin-bottom:4px;">';
        html += '<span style="width:115px;font-size:9px;font-weight:700;flex-shrink:0;">TOTAL PARIHUELAS LAVADAS:</span>';
        html += '<div style="flex:1;border:1px solid #000;padding:4px 6px;text-align:right;font-size:9px;min-height:22px;display:flex;align-items:center;justify-content:flex-end;box-sizing:border-box;">' + totLav + '</div>';
        html += '</div>';
        // Clasificación Lav/Sec
        html += '<div style="display:flex;align-items:center;margin-bottom:4px;">';
        html += '<span style="width:115px;font-size:8px;font-weight:700;flex-shrink:0;">CLASIFICACIÓN DE<br>PARIHUELAS LAVADAS Y SECADAS</span>';
        html += '<div style="flex:1;"><table style="width:100%;"><tr><th>Aptas</th><th>Dañadas</th><th>Relavado</th><th>Total</th></tr>';
        html += '<tr><td>' + cAptas + '</td><td>' + cDan + '</td><td>' + cRel + '</td><td style="color:#1565c0;"><span style="">' + cTot + '</span></td></tr></table></div>';
        html += '</div>';
        // Total Reparadas
        html += '<div style="display:flex;align-items:center;margin-bottom:4px;">';
        html += '<span style="width:115px;font-size:8px;font-weight:700;flex-shrink:0;">TOTAL PARIHUELAS REPARADAS</span>';
        html += '<div style="flex:1;"><table style="width:100%;"><tr><th>Aptas</th><th>Sucias</th><th>Total</th></tr>';
        html += '<tr><td>' + rAptas + '</td><td>' + rSuc + '</td><td style="color:#1565c0;"><span style="">' + rTot + '</span></td></tr></table></div>';
        html += '</div>';
        // Parihuelas Clasificadas
        html += '<div style="display:flex;align-items:center;margin-bottom:4px;">';
        html += '<span style="width:115px;font-size:8px;font-weight:700;flex-shrink:0;">PARIHUELAS CLASIFICADAS<br>(DEL TOTAL POR SELECCIONAR)</span>';
        html += '<div style="flex:1;"><table style="width:100%;"><tr><th>Aptas</th><th>Dañadas</th><th>Sucias</th><th>Total</th></tr>';
        html += '<tr><td>' + clAptas + '</td><td>' + clDan + '</td><td>' + clSuc + '</td><td style="color:#1565c0;"><span style="">' + clTot + '</span></td></tr></table></div>';
        html += '</div>';
        // Reselección y Reparación
        html += '<div style="display:flex;align-items:center;margin-top:4px;">';
        html += '<span style="width:115px;font-size:9px;flex-shrink:0;"></span>';
        html += '<div style="flex:1;display:flex;gap:8px;">';
        html += '<div style="flex:1;display:flex;align-items:center;"><span style="width:65px;font-size:9px;flex-shrink:0;">RESELECCIÓN:</span><div style="flex:1;border:1px solid #000;padding:4px 6px;text-align:right;font-size:9px;min-height:22px;display:flex;align-items:center;justify-content:flex-end;box-sizing:border-box;">' + resel + '</div></div>';
        html += '<div style="flex:1;display:flex;align-items:center;"><span style="width:65px;font-size:9px;flex-shrink:0;">REPARACIÓN:</span><div style="flex:1;border:1px solid #000;padding:4px 6px;text-align:right;font-size:9px;min-height:22px;display:flex;align-items:center;justify-content:flex-end;box-sizing:border-box;">' + repar + '</div></div>';
        html += '</div></div>';
        html += '</div>';

        // Columna derecha: Asperjadas del Turno, Despacho Asperjadas, Autoservicios, Observadas
        html += '<div style="flex:1;">';
        // Cada campo: label fijo, valor con borde
        html += '<div style="display:flex;align-items:center;margin-bottom:4px;">';
        html += '<span style="width:110px;font-size:9px;font-weight:700;flex-shrink:0;">ASPERJADAS DEL TURNO:</span>';
        html += '<div style="flex:1;border:1px solid #000;padding:4px 6px;text-align:right;font-size:9px;min-height:22px;display:flex;align-items:center;justify-content:flex-end;box-sizing:border-box;">' + aspTurno + '</div>';
        html += '</div>';
        html += '<div style="display:flex;align-items:center;margin-bottom:4px;">';
        html += '<span style="width:110px;font-size:9px;font-weight:700;flex-shrink:0;">DESPACHO ASPERJADAS:</span>';
        html += '<div style="flex:1;border:1px solid #000;padding:4px 6px;text-align:right;font-size:9px;min-height:22px;display:flex;align-items:center;justify-content:flex-end;box-sizing:border-box;">' + despAsp + '</div>';
        html += '</div>';
        html += '<div style="display:flex;align-items:center;margin-bottom:4px;">';
        html += '<span style="width:110px;font-size:9px;font-weight:700;flex-shrink:0;">AUTOSERVICIOS:</span>';
        html += '<div style="flex:1;border:1px solid #000;padding:4px 6px;text-align:right;font-size:9px;min-height:22px;display:flex;align-items:center;justify-content:flex-end;box-sizing:border-box;">' + autoserv + '</div>';
        html += '</div>';
        html += '<div style="display:flex;align-items:center;margin-bottom:4px;">';
        html += '<span style="width:110px;font-size:9px;font-weight:700;flex-shrink:0;">OBSERVADAS:</span>';
        html += '<div style="flex:1;border:1px solid #000;padding:4px 6px;text-align:right;font-size:9px;min-height:22px;display:flex;align-items:center;justify-content:flex-end;box-sizing:border-box;">' + observ + '</div>';
        html += '</div>';
        html += '</div>';

        html += '</div>'; // cierre flex row ajustes

        // Stock Final (integrado en la tabla)
        html += '<table>';
        html += '<tr><th rowspan="2" style="vertical-align:middle;width:90px;">STOCK FINAL</th><th>Asperjadas</th><th>Aptas</th><th>Dañadas</th><th>Sucias</th><th>Por Seleccionar</th><th>Lavadas y Secadas</th><th>Total</th></tr>';
        html += '<tr><td>' + sf_aspe + '</td><td style="background:#fff3cd;">' + sf_aptas + '</td><td style="background:#ffe0b2;">' + sf_dan + '</td><td style="background:#bbdefb;">' + sf_suc + '</td><td style="background:#c8e6c9;">' + sf_ps + '</td><td>' + sf_lav_sec + '</td><td style="color:#1565c0;"><span style="">' + sf_tot + '</span></td></tr>';
        html += '</table>';

        // Firma
        html += '<div class="firma">';
        html += '<hr>';
        html += '<div class="firma-label">NOMBRES Y FIRMA DEL RESPONSABLE</div>';
        html += '</div>';

        return html;
    }

    // =============================================
    // UTILIDADES
    // =============================================
    function setVal(id, val) {
        var el = document.getElementById(id);
        if (el) {
            // Asegurar que el valor sea entero (sin decimales)
            var num = parseInt(val) || 0;
            el.value = num;
        }
    }

    function getVal(id) {
        var el = document.getElementById(id);
        return el ? parseInt(el.value) || 0 : 0;
    }

    function formatNum(n) {
        return parseInt(n || 0);
    }

    function escapeHtml(s) {
        if (!s) return '';
        return String(s).replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"');
    }

    function tienePrivilegio(privilegio) {
        return window.usuarioPrivilegios && window.usuarioPrivilegios.indexOf(privilegio) !== -1;
    }

    function bloquearFormulario(bloquear) {
        // No-op: todos los campos siempre habilitados
        return;
    }

    function habilitarBotones(nuevo, guardar, modificar, imprimir) {
        var puedeGuardar = tienePrivilegio('guardar_kardex_parihuelas');
        document.getElementById('btnNuevo').disabled = !nuevo;
        document.getElementById('btnGuardar').disabled = !guardar || !puedeGuardar;
        document.getElementById('btnModificar').disabled = !modificar || !puedeGuardar;
        // El botón de imprimir siempre debe estar habilitado sin importar
        // la fecha, el usuario ni los roles/privilegios
        document.getElementById('btnImprimir').disabled = false;
    }

    function showLoading(show) {
        // Simple loading indicator
        var existing = document.getElementById('kardex-loading');
        if (show) {
            if (!existing) {
                var div = document.createElement('div');
                div.id = 'kardex-loading';
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

    /**
     * Notificación flotante estilizada (como en otros módulos)
     */
    function mostrarNotificacion(mensaje, tipo) {
        try {
            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + tipo + ' alert-dismissible fade show';
            alertDiv.setAttribute('role', 'alert');
            alertDiv.innerHTML =
                mensaje +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';

            var container = document.getElementById('kardex-mensajes');
            if (!container) {
                container = document.createElement('div');
                container.id = 'kardex-mensajes';
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
            console.warn('[KardexParihuelas] Error mostrando notificación:', e);
        }
    }

    function setupToggleDatos() {
        var btn = document.getElementById('btnToggleDatos');
        var section = document.getElementById('seccionDatosKardex');
        var icon = document.getElementById('iconToggleDatos');
        var header = document.getElementById('headerDatosKardex');

        if (!btn || !section) return;

        function toggle() {
            if (section.style.display === 'none') {
                section.style.display = 'block';
                if (icon) icon.className = 'bi bi-chevron-up';
            } else {
                section.style.display = 'none';
                if (icon) icon.className = 'bi bi-chevron-down';
            }
        }

        btn.addEventListener('click', function(e) { e.stopPropagation(); toggle(); });
        if (header) {
            header.addEventListener('click', function(e) {
                if (e.target !== btn && !btn.contains(e.target)) toggle();
            });
        }
    }

})();
