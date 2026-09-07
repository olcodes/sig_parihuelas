/**
 * avancediario.js - Módulo Control de Stock de Racks y Parihuelas
 * Lógica: grilla diaria de 13 filas fijas, auto-cálculos y guardado
 */
(function() {
    'use strict';

    console.log('[AvanceDiario] Script cargado');

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Horas predefinidas para las 13 filas
    var HORAS = [
        '07:00', '09:00', '11:00', '13:00', '15:00', '17:00', '19:00',
        '21:00', '23:00', '01:00', '03:00', '05:00', '06:30'
    ];

    // Turnos predefinidos para cada hora (1 al 13)
    var TURNOS = [
        'INICIO CORTE', 'MAÑANA', 'MAÑANA', 'MAÑANA',
        'TARDE', 'TARDE', 'TARDE', 'TARDE',
        'NOCHE', 'NOCHE', 'NOCHE', 'NOCHE',
        'FIN CORTE'
    ];

    function init() {
        console.log('[AvanceDiario] Inicializando...');

        // Usar fecha del cliente (no del servidor)
        var ahora = new Date();
        var fechaLocal = formatearFechaISO(ahora);
        document.getElementById('inputFecha').value = fechaLocal;

        // Configurar botón filtrar
        document.getElementById('btnFiltrar').addEventListener('click', function() {
            cargarRegistros();
        });

        // Configurar botón guardar
        document.getElementById('btnGuardar').addEventListener('click', btnGuardarClick);

        // Cargar registros del día actual
        cargarRegistros();

        console.log('[AvanceDiario] Inicialización completa');
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
                // Recalcular después de corregir
                recalcularFilas();
            }
        }, true);

        document.addEventListener('change', function(e) {
            var target = e.target;
            if (target && target.tagName === 'INPUT' && target.type === 'number') {
                var min = target.hasAttribute('min') ? parseFloat(target.getAttribute('min')) : 0;
                if (target.value !== '' && parseFloat(target.value) < min) {
                    target.value = String(min);
                }
                recalcularFilas();
            }
        });
    }

    // =============================================
    // CARGA DE REGISTROS
    // =============================================
    function cargarRegistros() {
        var fecha = document.getElementById('inputFecha').value;
        if (!fecha) {
            var hoy = new Date();
            fecha = formatearFechaISO(hoy);
            document.getElementById('inputFecha').value = fecha;
        }

        var url = window.APP_URL + '/avancediario/listar?fecha=' + encodeURIComponent(fecha);
        console.log('[AvanceDiario] Cargando registros fecha=' + fecha);

        showLoading(true);

        fetch(url)
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                showLoading(false);
                console.log('[AvanceDiario] Respuesta:', JSON.stringify(data));

                if (!data.success) {
                    mostrarError(data.error || 'Error al cargar registros');
                    return;
                }

                poblarGrilla(data.data || []);
            })
            .catch(function(err) {
                showLoading(false);
                console.error('[AvanceDiario] Error de red:', err);
                mostrarError('Error de red: ' + err.message);
            });
    }

    // =============================================
    // POBLAR GRILLA
    // =============================================
    function poblarGrilla(registros) {
        var tbody = document.getElementById('tbodyAvanceDiario');
        tbody.innerHTML = '';

        if (!registros || registros.length === 0) {
            // Generar 13 filas vacías
            registros = [];
            for (var i = 0; i < HORAS.length; i++) {
                registros.push({
                    Id: null,
                    Fecha: document.getElementById('inputFecha').value,
                    HoraAvance: HORAS[i],
                    Turno: TURNOS[i] || '',
                    Repliegues: 0,
                    RecepcionesExternas: 0,
                    ComprasUsadas: 0,
                    ComprasNuevas: 0,
                    TotalRecepcion: 0,
                    EanUsadasDespInternos: 0,
                    EanUsadasDespExternos: 0,
                    EanComprasUsadasDespInternos: 0,
                    EanComprasUsadasDespExternos: 0,
                    EanComprasNuevasDespInternos: 0,
                    EanComprasNuevasDespExternos: 0,
                    TotalDespacho: 0,
                    QPlanificada: 0,
                    PorcentajeCumplimiento: 0,
                    StockComprasNuevas: 0,
                    StockComprasUsadas: 0,
                    StockFlujoRegular: 0,
                    TotalStock: 0
                });
            }
        }

        // Renderizar 13 filas
        registros.forEach(function(r, idx) {
            var tr = document.createElement('tr');
            tr.dataset.index = idx;
            tr.dataset.id = r.Id || '';
            tr.dataset.hora = r.HoraAvance || HORAS[idx];

            var fechaFormateada = formatearFechaDDMMYYYY(r.Fecha || document.getElementById('inputFecha').value);
            var item = idx + 1;

            // Helper para mostrar valores como texto plano (clickeable si > 0)
            function mostrarTexto(valor, fieldName) {
                var v = formatNum(valor);
                var fecha = document.getElementById('inputFecha').value;
                var hora = r.HoraAvance || HORAS[idx];
                var clickable = (v > 0) ? 'true' : 'false';
                var estilo = 'text-align:center;';
                if (v > 0) {
                    estilo += 'cursor:pointer;text-decoration:underline dotted;color:#1a237e;';
                }
                return '<span class="ad-text" data-field="' + fieldName + '" data-value="' + v + '"' +
                       ' data-clickable="' + clickable + '"' +
                       ' data-campo="' + fieldName + '"' +
                       ' data-hora="' + hora + '"' +
                       ' data-fecha="' + fecha + '"' +
                       ' style="' + estilo + '">' + v + '</span>';
            }

            // Helper para mostrar texto de turno
            function mostrarTurno(valor) {
                var v = escapeHtml(String(valor || ''));
                return '<span class="ad-text" data-field="turno" style="text-align:center;">' + v + '</span>';
            }

            tr.innerHTML =
                // 🟢 VERDE
                '<td style="text-align:center;font-weight:600;">' + item + '</td>' +
                '<td style="text-align:center;">' + escapeHtml(fechaFormateada) + '</td>' +
                '<td style="text-align:center;font-weight:600;" class="hora-cell">' + escapeHtml(r.HoraAvance || HORAS[idx]) + '</td>' +
                '<td>' + mostrarTurno(r.Turno || TURNOS[idx] || '') + '</td>' +

                // 🟡 AMARILLO - EAN USADAS
                '<td>' + mostrarTexto(r.Repliegues, 'repliegues') + '</td>' +
                '<td>' + mostrarTexto(r.RecepcionesExternas, 'recepciones_externas') + '</td>' +

                // 🟡 AMARILLO - RECEPCIONES
                '<td>' + mostrarTexto(r.ComprasUsadas, 'compras_usadas') + '</td>' +
                '<td>' + mostrarTexto(r.ComprasNuevas, 'compras_nuevas') + '</td>' +
                '<td>' + mostrarTexto(r.TotalRecepcion, 'total_recepcion') + '</td>' +

                // 🔴 ROJO - EAN USADAS despachos
                '<td>' + mostrarTexto(r.EanUsadasDespInternos, 'ean_usadas_desp_internos') + '</td>' +
                '<td>' + mostrarTexto(r.EanUsadasDespExternos, 'ean_usadas_desp_externos') + '</td>' +

                // 🔴 ROJO - EAN COMPRAS USADAS
                '<td>' + mostrarTexto(r.EanComprasUsadasDespInternos, 'ean_compras_usadas_desp_internos') + '</td>' +
                '<td>' + mostrarTexto(r.EanComprasUsadasDespExternos, 'ean_compras_usadas_desp_externos') + '</td>' +

                // 🔴 ROJO - EAN COMPRAS NUEVAS
                '<td>' + mostrarTexto(r.EanComprasNuevasDespInternos, 'ean_compras_nuevas_desp_internos') + '</td>' +
                '<td>' + mostrarTexto(r.EanComprasNuevasDespExternos, 'ean_compras_nuevas_desp_externos') + '</td>' +

                // 🔴 ROJO
                '<td>' + mostrarTexto(r.TotalDespacho, 'total_despacho') + '</td>' +

                // 🔵 CELESTE
                '<td>' + mostrarTexto(r.QPlanificada, 'q_planificada') + '</td>' +
                '<td class="pct-cell" style="text-align:center;font-weight:600;">' + formatNum(r.PorcentajeCumplimiento) + '%</td>' +

                // 🩷 ROSADO - STOCK GLOBAL
                '<td>' + mostrarTexto(r.StockComprasNuevas, 'stock_compras_nuevas') + '</td>' +
                '<td>' + mostrarTexto(r.StockComprasUsadas, 'stock_compras_usadas') + '</td>' +
                '<td>' + mostrarTexto(r.StockFlujoRegular, 'stock_flujo_regular') + '</td>' +
                '<td>' + mostrarTexto(r.TotalStock, 'total_stock') + '</td>';

            tbody.appendChild(tr);
        });

        // Agregar fila de totales
        agregarFilaTotales();

        // Vincular eventos de input para auto-cálculos
        vincularEventosCalculo();

        // Recalcular todo
        recalcularFilas();

        // Configurar Popovers para celdas clickeables
        configurarPopovers();
    }

    // =============================================
    // FILA DE TOTALES
    // =============================================
    function agregarFilaTotales() {
        var tbody = document.getElementById('tbodyAvanceDiario');
        var tr = document.createElement('tr');
        tr.className = 'fila-totales';

        // Columnas que NO se suman (verdes)
        tr.innerHTML =
            '<td style="text-align:center;">T</td>' +
            '<td style="text-align:center;"></td>' +
            '<td style="text-align:center;">TOTALES</td>' +
            '<td></td>';

        // Columnas que SÍ se suman (desde Repliegues hasta Total Despacho)
        var numFieldsConTotal = [
            'repliegues', 'recepciones_externas',
            'compras_usadas', 'compras_nuevas', 'total_recepcion',
            'ean_usadas_desp_internos', 'ean_usadas_desp_externos',
            'ean_compras_usadas_desp_internos', 'ean_compras_usadas_desp_externos',
            'ean_compras_nuevas_desp_internos', 'ean_compras_nuevas_desp_externos',
            'total_despacho'
        ];

        // Columnas sin total (desde Q Planificada hacia adelante)
        var numFieldsSinTotal = [
            'q_planificada', 'porcentaje_cumplimiento',
            'stock_compras_nuevas', 'stock_compras_usadas', 'stock_flujo_regular', 'total_stock'
        ];

        for (var i = 0; i < numFieldsConTotal.length; i++) {
            tr.innerHTML += '<td style="text-align:center;" data-total-field="' + numFieldsConTotal[i] + '">0</td>';
        }
        for (var i = 0; i < numFieldsSinTotal.length; i++) {
            tr.innerHTML += '<td style="text-align:center;"></td>';
        }

        tbody.appendChild(tr);
    }

    // =============================================
    // VINCULAR EVENTOS DE CÁLCULO
    // =============================================
    function vincularEventosCalculo() {
        // Los valores son de solo lectura, no hay inputs que escuchar
    }

    // =============================================
    // RECÁLCULOS
    // =============================================
    function recalcularFilas() {
        var tbody = document.getElementById('tbodyAvanceDiario');
        var filas = tbody.querySelectorAll('tr:not(.fila-totales)');
        var filaTotales = tbody.querySelector('tr.fila-totales');

        // Acumuladores para totales
        var totales = {
            repliegues: 0,
            recepciones_externas: 0,
            compras_usadas: 0,
            compras_nuevas: 0,
            total_recepcion: 0,
            ean_usadas_desp_internos: 0,
            ean_usadas_desp_externos: 0,
            ean_compras_usadas_desp_internos: 0,
            ean_compras_usadas_desp_externos: 0,
            ean_compras_nuevas_desp_internos: 0,
            ean_compras_nuevas_desp_externos: 0,
            total_despacho: 0,
            q_planificada: 0,
            porcentaje_cumplimiento: 0,
            stock_compras_nuevas: 0,
            stock_compras_usadas: 0,
            stock_flujo_regular: 0,
            total_stock: 0
        };

        var prevTotalRecepcion = 0;
        var prevTotalDespacho = 0;

        // Inicializar stocks acumulados desde la primera fila (07:00 - INICIO CORTE)
        // para que el valor enviado por el backend (stock del día anterior o corte manual)
        // no sea pisado por cero al recalcular
        var primeraFila = filas.length > 0 ? filas[0] : null;
        var prevStockComprasNuevas = primeraFila ? parseFloat(getFieldValue(primeraFila, 'stock_compras_nuevas')) || 0 : 0;
        var prevStockComprasUsadas = primeraFila ? parseFloat(getFieldValue(primeraFila, 'stock_compras_usadas')) || 0 : 0;
        var prevStockFlujoRegular = primeraFila ? parseFloat(getFieldValue(primeraFila, 'stock_flujo_regular')) || 0 : 0;

        filas.forEach(function(fila) {
            var inputs = fila.querySelectorAll('input.ad-input');

            // --- AUTO-CÁLCULOS POR FILA (calcular antes de acumular) ---

            // Total Recepción: acumulativo (mantiene valor anterior si todo está en cero)
            var repl = parseFloat(getFieldValue(fila, 'repliegues')) || 0;
            var recExt = parseFloat(getFieldValue(fila, 'recepciones_externas')) || 0;
            var compUsa = parseFloat(getFieldValue(fila, 'compras_usadas')) || 0;
            var compNue = parseFloat(getFieldValue(fila, 'compras_nuevas')) || 0;

            if (repl === 0 && recExt === 0 && compUsa === 0 && compNue === 0) {
                // Mantener el valor acumulado anterior
                setFieldValue(fila, 'total_recepcion', prevTotalRecepcion);
            } else {
                var nuevoTotalRec = prevTotalRecepcion + repl + recExt + compUsa + compNue;
                setFieldValue(fila, 'total_recepcion', nuevoTotalRec);
                prevTotalRecepcion = nuevoTotalRec;
            }

            // Total Despacho: acumulativo desde la fila anterior
            var eudi = parseFloat(getFieldValue(fila, 'ean_usadas_desp_internos')) || 0;
            var eude = parseFloat(getFieldValue(fila, 'ean_usadas_desp_externos')) || 0;
            var ecudi = parseFloat(getFieldValue(fila, 'ean_compras_usadas_desp_internos')) || 0;
            var ecude = parseFloat(getFieldValue(fila, 'ean_compras_usadas_desp_externos')) || 0;
            var ecndi = parseFloat(getFieldValue(fila, 'ean_compras_nuevas_desp_internos')) || 0;
            var ecnde = parseFloat(getFieldValue(fila, 'ean_compras_nuevas_desp_externos')) || 0;

            if (eudi === 0 && eude === 0 && ecudi === 0 && ecude === 0 && ecndi === 0 && ecnde === 0) {
                // Mantener el valor acumulado anterior
                setFieldValue(fila, 'total_despacho', prevTotalDespacho);
            } else {
                var nuevoTotalDesp = prevTotalDespacho + eudi + eude + ecudi + ecude + ecndi + ecnde;
                setFieldValue(fila, 'total_despacho', nuevoTotalDesp);
                prevTotalDespacho = nuevoTotalDesp;
            }

            // % Cumplimiento = si total_recepcion=0 o total_despacho=0 → 0, sino (total_despacho / q_planificada) * 100
            var totalRecFila = parseFloat(getFieldValue(fila, 'total_recepcion')) || 0;
            var totalDespFila = parseFloat(getFieldValue(fila, 'total_despacho')) || 0;
            var qPlanifFila = parseFloat(getFieldValue(fila, 'q_planificada')) || 0;
            var pctVal = 0;
            if (totalRecFila > 0 && totalDespFila > 0 && qPlanifFila > 0) {
                pctVal = Math.round((totalDespFila / qPlanifFila) * 100 * 100) / 100;
            }
            // Buscar el td con la clase pct-cell y actualizar su texto
            var pctTd = fila.querySelector('td.pct-cell');
            if (pctTd) {
                pctTd.textContent = formatNum(pctVal) + '%';
            }

            // Stock Compras Nuevas: acumulativo (arrastra valor anterior)
            var turnoActual = (getFieldValue(fila, 'turno') || '').trim().toUpperCase();
            var compNueVal = parseFloat(getFieldValue(fila, 'compras_nuevas')) || 0;
            var ecndiVal = parseFloat(getFieldValue(fila, 'ean_compras_nuevas_desp_internos')) || 0;
            var ecndeVal = parseFloat(getFieldValue(fila, 'ean_compras_nuevas_desp_externos')) || 0;

            if (totalRecFila === 0 && totalDespFila === 0) {
                // Mantener el valor acumulado anterior
                setFieldValue(fila, 'stock_compras_nuevas', prevStockComprasNuevas);
            } else {
                var nuevoStockCN = prevStockComprasNuevas + compNueVal - (ecndiVal + ecndeVal);
                setFieldValue(fila, 'stock_compras_nuevas', nuevoStockCN);
                prevStockComprasNuevas = nuevoStockCN;
            }

            // Stock Compras Usadas: acumulativo
            var compUsaVal = parseFloat(getFieldValue(fila, 'compras_usadas')) || 0;
            var ecudiVal = parseFloat(getFieldValue(fila, 'ean_compras_usadas_desp_internos')) || 0;
            var ecudeVal = parseFloat(getFieldValue(fila, 'ean_compras_usadas_desp_externos')) || 0;

            if (totalRecFila === 0 && totalDespFila === 0) {
                setFieldValue(fila, 'stock_compras_usadas', prevStockComprasUsadas);
            } else {
                var nuevoStockCU = prevStockComprasUsadas + compUsaVal - (ecudiVal + ecudeVal);
                setFieldValue(fila, 'stock_compras_usadas', nuevoStockCU);
                prevStockComprasUsadas = nuevoStockCU;
            }

            // Stock Flujo Regular: acumulativo
            var replVal = parseFloat(getFieldValue(fila, 'repliegues')) || 0;
            var recExtVal = parseFloat(getFieldValue(fila, 'recepciones_externas')) || 0;
            var eudiVal = parseFloat(getFieldValue(fila, 'ean_usadas_desp_internos')) || 0;
            var eudeVal = parseFloat(getFieldValue(fila, 'ean_usadas_desp_externos')) || 0;

            if (totalRecFila === 0 && totalDespFila === 0) {
                setFieldValue(fila, 'stock_flujo_regular', prevStockFlujoRegular);
            } else {
                var nuevoStockFR = prevStockFlujoRegular + replVal + recExtVal - (eudiVal + eudeVal);
                setFieldValue(fila, 'stock_flujo_regular', nuevoStockFR);
                prevStockFlujoRegular = nuevoStockFR;
            }

            // Total Stock = suma de los 3 stocks
            var scnCalc = parseFloat(getFieldValue(fila, 'stock_compras_nuevas')) || 0;
            var scuCalc = parseFloat(getFieldValue(fila, 'stock_compras_usadas')) || 0;
            var sfrCalc = parseFloat(getFieldValue(fila, 'stock_flujo_regular')) || 0;
            setFieldValue(fila, 'total_stock', scnCalc + scuCalc + sfrCalc);

            // Acumular totales leyendo desde los spans (ya actualizados)
            var spans = fila.querySelectorAll('span.ad-text[data-field]');
            spans.forEach(function(span) {
                var field = span.getAttribute('data-field');
                var val = parseFloat(span.getAttribute('data-value') || span.textContent) || 0;

                if (totales.hasOwnProperty(field)) {
                    totales[field] += val;
                }
            });
        });

        // Actualizar fila de totales con valores acumulados
        if (filaTotales) {
            var numFieldsConTotal = [
                'repliegues', 'recepciones_externas',
                'compras_usadas', 'compras_nuevas',
                'ean_usadas_desp_internos', 'ean_usadas_desp_externos',
                'ean_compras_usadas_desp_internos', 'ean_compras_usadas_desp_externos',
                'ean_compras_nuevas_desp_internos', 'ean_compras_nuevas_desp_externos'
            ];

            for (var i = 0; i < numFieldsConTotal.length; i++) {
                var fieldName = numFieldsConTotal[i];
                var td = filaTotales.querySelector('td[data-total-field="' + fieldName + '"]');
                if (td) {
                    td.textContent = formatNum(totales[fieldName]);
                }
            }

            // total_recepcion y total_despacho son acumulativos:
            // el total debe mostrar el valor de la última fila (no la suma simple)
            var ultimaFila = filas.length > 0 ? filas[filas.length - 1] : null;
            var totalRecFinal = ultimaFila ? parseFloat(getFieldValue(ultimaFila, 'total_recepcion')) || 0 : 0;
            var totalDespFinal = ultimaFila ? parseFloat(getFieldValue(ultimaFila, 'total_despacho')) || 0 : 0;

            var totalRecepcionTd = filaTotales.querySelector('td[data-total-field="total_recepcion"]');
            if (totalRecepcionTd) totalRecepcionTd.textContent = formatNum(totalRecFinal);

            var totalDespTd = filaTotales.querySelector('td[data-total-field="total_despacho"]');
            if (totalDespTd) totalDespTd.textContent = formatNum(totalDespFinal);

            // % Cumplimiento en totales
            var totQP = totales.q_planificada || 0;
            var pctTotal = 0;
            if (totalRecFinal > 0 && totalDespFinal > 0 && totQP > 0) {
                pctTotal = Math.round((totalDespFinal / totQP) * 100 * 100) / 100;
            }
            var pctTd = filaTotales.querySelector('td[data-total-field="porcentaje_cumplimiento"]');
            if (pctTd) pctTd.innerHTML = formatNum(pctTotal) + ' %';

            // Total Stock en totales = valor de la última fila (acumulado)
            var totalStockUltima = ultimaFila ? parseFloat(getFieldValue(ultimaFila, 'total_stock')) || 0 : 0;
            var totalStockTd = filaTotales.querySelector('td[data-total-field="total_stock"]');
            if (totalStockTd) totalStockTd.textContent = formatNum(totalStockUltima);
        }
    }

    // =============================================
    // UTILIDADES PARA INPUTS
    // =============================================
    function getFieldValue(fila, fieldName) {
        var span = fila.querySelector('[data-field="' + fieldName + '"]');
        if (span) {
            return span.getAttribute('data-value') || span.textContent || '0';
        }
        return '0';
    }

    function setFieldValue(fila, fieldName, value) {
        var span = fila.querySelector('[data-field="' + fieldName + '"]');
        if (span) {
            var numVal = formatNum(value);
            var current = parseFloat(span.getAttribute('data-value') || span.textContent) || 0;
            if (current !== numVal) {
                span.textContent = numVal;
                span.setAttribute('data-value', numVal);
            }
        }
    }

    // =============================================
    // GUARDAR
    // =============================================
    function btnGuardarClick() {
        var fecha = document.getElementById('inputFecha').value;
        if (!fecha) {
            mostrarError('Debe seleccionar una fecha');
            return;
        }

        var tbody = document.getElementById('tbodyAvanceDiario');
        var filas = tbody.querySelectorAll('tr:not(.fila-totales)');

        if (filas.length === 0) {
            mostrarError('No hay datos para guardar');
            return;
        }

        var filasData = [];
        filas.forEach(function(fila) {
            var spans = fila.querySelectorAll('[data-field]');
            var filaData = {
                hora_avance: fila.dataset.hora || '',
                turno: ''
            };

            spans.forEach(function(span) {
                var field = span.getAttribute('data-field');
                var val = parseFloat(span.getAttribute('data-value') || span.textContent) || 0;

                if (field === 'turno') {
                    filaData.turno = span.textContent;
                } else {
                    filaData[field] = val;
                }
            });

            filasData.push(filaData);
        });

        var data = {
            fecha: fecha,
            filas: filasData
        };

        console.log('[AvanceDiario] Guardando:', JSON.stringify(data));

        showLoading(true);

        fetch(window.APP_URL + '/avancediario/guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(resp) { return resp.json(); })
        .then(function(result) {
            showLoading(false);
            if (result.success) {
                mostrarExito(result.message || 'Registros guardados exitosamente');
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

    // =============================================
    // UTILIDADES
    // =============================================

    function formatNum(n) {
        return parseFloat(n || 0);
    }

    function escapeHtml(s) {
        if (!s) return '';
        return String(s).replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"');
    }

    function formatearFechaISO(fecha) {
        var dd = String(fecha.getDate()).padStart(2, '0');
        var mm = String(fecha.getMonth() + 1).padStart(2, '0');
        var yyyy = fecha.getFullYear();
        return yyyy + '-' + mm + '-' + dd;
    }

    function formatearFechaDDMMYYYY(fechaStr) {
        if (!fechaStr) return '';
        var partes = fechaStr.split('-');
        if (partes.length === 3) {
            return partes[2] + '/' + partes[1] + '/' + partes[0];
        }
        return fechaStr;
    }

    function showLoading(show) {
        var existing = document.getElementById('ad-loading');
        if (show) {
            if (!existing) {
                var div = document.createElement('div');
                div.id = 'ad-loading';
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

            var container = document.getElementById('ad-mensajes');
            if (!container) {
                container = document.createElement('div');
                container.id = 'ad-mensajes';
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
                console.warn('[AvanceDiario] Error mostrando notificación:', e);
            }
        }
    
        // =============================================
        // POPOVER DE DETALLE DE CANTIDADES
        // =============================================
    
        /**
         * Nombres legibles para cada campo en el título del Popover
         */
        function getNombreCampo(field) {
            var nombres = {
                'repliegues': 'Repliegues',
                'recepciones_externas': 'Recepc. Externas',
                'compras_usadas': 'Compras Usadas',
                'compras_nuevas': 'Compras Nuevas',
                'ean_usadas_desp_internos': 'EAN Usadas - Desp. Internos',
                'ean_usadas_desp_externos': 'EAN Usadas - Desp. Externos',
                'ean_compras_usadas_desp_internos': 'EAN Compras Usadas - Desp. Internos',
                'ean_compras_usadas_desp_externos': 'EAN Compras Usadas - Desp. Externos',
                'ean_compras_nuevas_desp_internos': 'EAN Compras Nuevas - Desp. Internos',
                'ean_compras_nuevas_desp_externos': 'EAN Compras Nuevas - Desp. Externos',
                'stock_compras_nuevas': 'Stock C. Nuevas',
                'stock_compras_usadas': 'Stock C. Usadas',
                'stock_flujo_regular': 'Stock F. Regular',
                'total_stock': 'Total Stock'
            };
            return nombres[field] || field;
        }
    
        /**
         * Generar HTML de tabla compacta para el contenido del Popover
         */
        function generarTablaPopover(data, total, field) {
            // Determinar si debe mostrar columna Total (solo para recepciones_externas y compras_nuevas)
            var mostrarTotal = (field === 'recepciones_externas' || field === 'compras_nuevas');
            // Determinar si es detalle de stock (muestra columna Tipo ENTRADA/SALIDA)
            var mostrarTipo = data.length > 0 && data[0].Tipo !== undefined && data[0].Tipo !== null && data[0].Tipo !== '';

            // Agrupar por N° Vale (y por Tipo si aplica)
            var map = {};
            data.forEach(function(item) {
                var tipo = item.Tipo || '';
                var vale = item.NValeFormateado || '--';
                var key = vale + '|' + tipo;
                if (!map[key]) {
                    map[key] = {
                        NValeFormateado: vale,
                        OrigenDestino: item.OrigenDestino || '--',
                        Tipo: tipo,
                        Cantidad: 0,
                        Total: 0
                    };
                }
                map[key].Cantidad += parseFloat(item.Cantidad) || 0;
                if (mostrarTotal) {
                    map[key].Total += parseFloat(item.Total !== undefined && item.Total !== null ? item.Total : item.Cantidad) || 0;
                }
            });

            var groupedData = Object.keys(map).map(function(key) { return map[key]; });

            // Ordenar por OrigenDestino, luego por Vale
            groupedData.sort(function(a, b) {
                if (a.OrigenDestino < b.OrigenDestino) return -1;
                if (a.OrigenDestino > b.OrigenDestino) return 1;
                if (a.NValeFormateado < b.NValeFormateado) return -1;
                if (a.NValeFormateado > b.NValeFormateado) return 1;
                return 0;
            });

            var html = '<div style="font-size:11px;max-height:220px;overflow-y:auto;">';
            html += '<table style="width:100%;border-collapse:collapse;">';
            html += '<tr style="border-bottom:1px solid #ddd;">';
            html += '<th style="padding:2px 4px;text-align:left;font-size:10px;">#</th>';
            html += '<th style="padding:2px 4px;text-align:left;font-size:10px;">N° Vale</th>';
            if (mostrarTipo) {
                html += '<th style="padding:2px 4px;text-align:left;font-size:10px;">Tipo</th>';
            }
            html += '<th style="padding:2px 4px;text-align:left;font-size:10px;">Origen/Destino</th>';
            html += '<th style="padding:2px 4px;text-align:right;font-size:10px;">Cant.</th>';
            if (mostrarTotal) {
                html += '<th style="padding:2px 4px;text-align:right;font-size:10px;">Total</th>';
            }
            html += '</tr>';

            groupedData.forEach(function(item, idx) {
                html += '<tr style="border-bottom:1px solid #eee;">';
                html += '<td style="padding:2px 4px;font-size:10px;">' + (idx + 1) + '</td>';
                html += '<td style="padding:2px 4px;font-size:10px;font-weight:600;color:#1565c0;">' + escapeHtml(item.NValeFormateado || '--') + '</td>';
                if (mostrarTipo) {
                    var tipoColor = (item.Tipo === 'SALIDA') ? '#b71c1c' : '#2e7d32';
                    html += '<td style="padding:2px 4px;font-size:10px;font-weight:600;color:' + tipoColor + ';">' + escapeHtml(item.Tipo || '') + '</td>';
                }
                html += '<td style="padding:2px 4px;font-size:10px;">' + escapeHtml(item.OrigenDestino || '--') + '</td>';
                html += '<td style="padding:2px 4px;text-align:right;font-size:10px;font-weight:600;">' + formatNum(item.Cantidad) + '</td>';
                if (mostrarTotal) {
                    var totalVal = formatNum(item.Total);
                    html += '<td style="padding:2px 4px;text-align:right;font-size:10px;font-weight:600;color:#b71c1c;">' + totalVal + '</td>';
                }
                html += '</tr>';
            });

            html += '<tr style="border-top:2px solid #333;font-weight:700;">';
            // Calcular número de columnas para el colspan del TOTAL
            var numCols = 4; // #, N°Vale, Origen/Destino, Cant.
            if (mostrarTipo) numCols++;
            if (mostrarTotal) numCols++;
            html += '<td colspan="' + (numCols - 1) + '" style="padding:2px 4px;text-align:right;font-size:10px;">TOTAL</td>';
            html += '<td style="padding:2px 4px;text-align:right;font-size:10px;">' + formatNum(total) + '</td>';
            html += '</tr>';
            html += '</table></div>';
            return html;
        }
    
        /**
         * Configurar Popovers de Bootstrap en las celdas clickeables
         */
        function configurarPopovers() {
            document.querySelectorAll('[data-clickable="true"]').forEach(function(el) {
                // Destruir popover previo si existe (por si se recarga la grilla)
                var popover = bootstrap.Popover.getInstance(el);
                if (popover) popover.dispose();
    
                new bootstrap.Popover(el, {
                    trigger: 'click',
                    placement: 'auto',
                    html: true,
                    title: 'Cargando...',
                    content: '<div style="text-align:center;padding:8px;"><div class="spinner-border spinner-border-sm"></div> Cargando...</div>',
                    container: 'body',
                    sanitize: false
                });
    
                el.addEventListener('shown.bs.popover', function() {
                    var self = this;
                    var campo = self.getAttribute('data-campo');
                    var hora = self.getAttribute('data-hora');
                    var fecha = self.getAttribute('data-fecha');
    
                    var popover = bootstrap.Popover.getInstance(self);
                    if (!popover) return;
    
                    // Si ya cargó el contenido (tiene data-loaded), no recargar
                    if (self.getAttribute('data-loaded') === 'true') return;
                    self.setAttribute('data-loaded', 'true');
    
                    var url = window.APP_URL + '/avancediario/detalleCampo?fecha=' + encodeURIComponent(fecha) +
                              '&campo=' + encodeURIComponent(campo) +
                              '&hora=' + encodeURIComponent(hora);
    
                    fetch(url)
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success && data.data && data.data.length > 0) {
                                var html = generarTablaPopover(data.data, data.total, campo);
                                var title = getNombreCampo(campo) + (hora ? ' - ' + hora : '');
                                popover.setContent({
                                    '.popover-header': title,
                                    '.popover-body': html
                                });
                            } else {
                                var title = getNombreCampo(campo) + (hora ? ' - ' + hora : '');
                                popover.setContent({
                                    '.popover-header': title,
                                    '.popover-body': '<div style="padding:8px;color:#999;font-size:11px;text-align:center;">Sin registros de detalle</div>'
                                });
                            }
                        })
                        .catch(function(err) {
                            popover.setContent({
                                '.popover-header': 'Error',
                                '.popover-body': '<div style="padding:8px;color:#c00;font-size:11px;">Error al cargar detalle: ' + (err.message || 'desconocido') + '</div>'
                            });
                        });
                });
    
                // Al cerrar el popover, limpiar flag para que recargue al reabrir
                el.addEventListener('hidden.bs.popover', function() {
                    this.removeAttribute('data-loaded');
                });
            });
        }
    
    })();
