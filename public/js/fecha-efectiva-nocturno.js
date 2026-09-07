/**
 * fecha-efectiva-nocturno.js
 * 
 * Utilidad para calcular la fecha efectiva de registro cuando el turno nocturno
 * está activo. Para turnos que cruzan la medianoche (ej. 21:31 - 05:30),
 * los registros realizados después de las 00:00 pero antes de la HoraFin
 * deben pertenecer a la fecha de inicio del turno (día anterior).
 * 
 * @author Sistema Lavoro-ERP
 */

(function() {
    'use strict';

    /**
     * Calcula la fecha efectiva de registro según el turno activo.
     * 
     * @param {Array} turnos - Array de objetos turno con HoraInicio, HoraFin, Id
     * @param {string} horaActual - Hora actual en formato 'HH:MM' o 'HH:MM:SS'
     * @returns {Object} Resultado con:
     *   - fechaEfectiva {string} - Fecha que debe usarse para el registro (Y-m-d)
     *   - fechaActual {string} - Fecha real de hoy (Y-m-d)
     *   - esTurnoNocturno {boolean} - True si estamos en turno nocturno y la fecha difiere
     *   - fechaInicioTurno {string} - Fecha de inicio del turno activo (Y-m-d)
     *   - turnoActivo {Object|null} - El objeto turno activo, o null si no se encontró
     */
    window.calcularFechaEfectiva = function(turnos, horaActual) {
        // Usar datos de window si no se pasan parámetros
        turnos = turnos || window.turnosData || [];
        horaActual = horaActual || window.horaActual || new Date().toTimeString().slice(0, 5);

        var hoy = new Date();
        var fechaHoy = hoy.toISOString().slice(0, 10); // YYYY-MM-DD

        // Si no hay turnos, devolver fecha actual
        if (!turnos || turnos.length === 0) {
            return {
                fechaEfectiva: fechaHoy,
                fechaActual: fechaHoy,
                esTurnoNocturno: false,
                fechaInicioTurno: fechaHoy,
                turnoActivo: null
            };
        }

        // Parsear hora actual a minutos
        var partesHora = horaActual.split(':').map(Number);
        var actualMinutos = partesHora[0] * 60 + (partesHora[1] || 0);

        var turnoActivo = null;

        // Encontrar el turno activo (misma lógica que actualizarTurnoAutomatico)
        for (var i = 0; i < turnos.length; i++) {
            var t = turnos[i];
            var hi = (t.HoraInicio || '00:00').split(':').map(Number);
            var hf = (t.HoraFin || '23:59').split(':').map(Number);
            var inicioMin = hi[0] * 60 + (hi[1] || 0);
            var finMin = hf[0] * 60 + (hf[1] || 0);

            if (inicioMin <= finMin) {
                // Turno normal (no cruza medianoche)
                if (actualMinutos >= inicioMin && actualMinutos <= finMin) {
                    turnoActivo = t;
                    break;
                }
            } else {
                // Turno nocturno (cruza medianoche)
                if (actualMinutos >= inicioMin || actualMinutos <= finMin) {
                    turnoActivo = t;
                    break;
                }
            }
        }

        if (!turnoActivo) {
            return {
                fechaEfectiva: fechaHoy,
                fechaActual: fechaHoy,
                esTurnoNocturno: false,
                fechaInicioTurno: fechaHoy,
                turnoActivo: null
            };
        }

        var horaInicio = (turnoActivo.HoraInicio || '00:00').split(':').map(Number);
        var horaFin = (turnoActivo.HoraFin || '23:59').split(':').map(Number);
        var inicioMin = horaInicio[0] * 60 + (horaInicio[1] || 0);
        var finMin = horaFin[0] * 60 + (horaFin[1] || 0);
        var esNocturno = inicioMin > finMin;

        var fechaEfectiva = fechaHoy;
        var fechaInicioTurno = fechaHoy;

        if (esNocturno) {
            // Si es turno nocturno y estamos pasada la medianoche (hora actual <= HoraFin)
            if (actualMinutos <= finMin) {
                // Ya pasó medianoche: la fecha efectiva es la de ayer
                var ayer = new Date(hoy);
                ayer.setDate(ayer.getDate() - 1);
                fechaInicioTurno = ayer.toISOString().slice(0, 10);
                fechaEfectiva = fechaInicioTurno;
            } else {
                // Aún no pasa medianoche: la fecha efectiva es hoy
                fechaInicioTurno = fechaHoy;
                fechaEfectiva = fechaHoy;
            }
        }

        return {
            fechaEfectiva: fechaEfectiva,
            fechaActual: fechaHoy,
            esTurnoNocturno: esNocturno && (fechaEfectiva !== fechaHoy),
            fechaInicioTurno: fechaInicioTurno,
            turnoActivo: turnoActivo
        };
    };

    /**
     * Muestra u oculta el badge de fecha nocturna y actualiza el campo fecha.
     * 
     * @param {Object} fechaInfo - Resultado de window.calcularFechaEfectiva()
     * @param {boolean} forzarOcultar - Si es true, oculta el badge y NO modifica la fecha
     */
    window.actualizarBadgeFechaNocturno = function(fechaInfo, forzarOcultar) {
        var badge = document.getElementById('badgeFechaNocturno');
        var fechaInput = document.getElementById('fecha');
        var display = document.getElementById('fechaEfectivaDisplay');

        if (!badge || !fechaInput) return;

        if (forzarOcultar || !fechaInfo || !fechaInfo.esTurnoNocturno) {
            // Ocultar badge
            badge.classList.add('d-none');
            return;
        }

        // Mostrar badge con la fecha efectiva
        if (display) {
            // Formatear fecha de Y-m-d a d/m/Y para mejor legibilidad
            var partes = fechaInfo.fechaEfectiva.split('-');
            display.textContent = partes[2] + '/' + partes[1] + '/' + partes[0];
        }
        badge.classList.remove('d-none');

        // Actualizar el input fecha con la fecha efectiva
        if (fechaInput && fechaInput.value !== fechaInfo.fechaEfectiva) {
            fechaInput.value = fechaInfo.fechaEfectiva;
        }

        // Guardar fecha efectiva globalmente para que recopilarDatos la use
        window.fechaEfectiva = fechaInfo.fechaEfectiva;
    };

    /**
     * Función completa: calcula fecha efectiva y actualiza badge + input fecha.
     * Es la función principal que deben llamar los módulos.
     * 
     * @param {boolean} enModoEdicion - Si es true, NO modifica la fecha cargada
     */
    window.aplicarFechaEfectivaTurno = function(enModoEdicion) {
        var fechaInfo = window.calcularFechaEfectiva();
        
        if (enModoEdicion) {
            // En modo edición, solo actualizar el badge informativo,
            // NO modificar la fecha del vale cargado
            window.actualizarBadgeFechaNocturno(fechaInfo, true);
            window.fechaEfectiva = null; // No usar fecha efectiva en edición
        } else {
            window.actualizarBadgeFechaNocturno(fechaInfo, false);
        }

        return fechaInfo;
    };

    console.log('[fecha-efectiva-nocturno] Script cargado');

})();
