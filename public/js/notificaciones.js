/**
 * Ayuda visual para el usuario
 * Este script muestra mensajes de ayuda sobre los cambios recientes
 * 
 * NOTA: Las notificaciones están actualmente desactivadas porque
 * todas las funcionalidades están funcionando correctamente.
 */

// =========================================================
// Funciones de overlay para indicador de carga (Exportar Excel)
// =========================================================
(function() {
    'use strict';

    /**
     * Muestra un overlay con spinner y mensaje mientras se procesa una operación
     * @param {string} mensaje - Mensaje a mostrar (opcional)
     */
    window.mostrarOverlayCarga = function(mensaje) {
        mensaje = mensaje || 'Procesando...';
        // Si ya existe, no crear otro
        if (document.getElementById('overlay-carga-global')) return;

        var overlay = document.createElement('div');
        overlay.id = 'overlay-carga-global';
        overlay.style.cssText = 
            'position:fixed;top:0;left:0;width:100%;height:100%;' +
            'background:rgba(0,0,0,0.45);z-index:99999;' +
            'display:flex;align-items:center;justify-content:center;' +
            'flex-direction:column;';

        overlay.innerHTML = 
            '<div style="background:white;border-radius:12px;padding:30px 40px;' +
            'text-align:center;box-shadow:0 8px 30px rgba(0,0,0,0.2);">' +
            '<div class="spinner-border text-success" role="status" style="width:3rem;height:3rem;">' +
            '<span class="visually-hidden">Cargando...</span></div>' +
            '<p class="mt-3 mb-0 fw-bold text-dark" style="font-size:16px;">' +
            '<span class="spinner-grow spinner-grow-sm text-success me-2" role="status"></span>' +
            mensaje + '</p></div>';

        document.body.appendChild(overlay);
    };

    /**
     * Oculta y elimina el overlay de carga
     */
    window.ocultarOverlayCarga = function() {
        var overlay = document.getElementById('overlay-carga-global');
        if (overlay) {
            overlay.remove();
        }
    };

    /**
     * Verifica si hay filtros activos en los inputs .filtro-grilla
     * @returns {boolean} true si hay al menos un filtro con valor
     */
    window.hayFiltrosActivos = function() {
        var campos = document.querySelectorAll('.filtro-grilla');
        for (var i = 0; i < campos.length; i++) {
            var val = (campos[i].value || '').trim();
            if (val !== '') return true;
        }
        // También verificar filtros de columna
        if (window._filtrosColumna && Object.keys(window._filtrosColumna).length > 0) return true;
        return false;
    };

    console.log('[INFO] Funciones de overlay de carga disponibles');
})();

document.addEventListener('DOMContentLoaded', function() {
    // Las notificaciones han sido desactivadas porque el sistema
    // ya está funcionando correctamente.
    
    // Para habilitar las notificaciones en el futuro, descomenta el código
    // y modifica los mensajes según sea necesario.
    
    /*
    setTimeout(function() {
        // Código de notificaciones (desactivado)
    }, 1500);
    */
    
    console.log('[INFO] Notificaciones desactivadas - Sistema funcionando correctamente');
});
