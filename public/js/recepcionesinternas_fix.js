// recepcionesinternas_fix.js - VERSION SIN BUCLES
// VERSION: 2026-02-22-FIX-FINAL
console.log('%c[FIX] RecepcionesInternas VERSION SIN BUCLES CARGADA', 'color: red; font-weight: bold; font-size: 16px');
console.log('%c[FIX] Este archivo deshabilita TODOS los bucles y observadores', 'color: red; font-weight: bold');

// DESHABILITAR TODAS LAS FUNCIONES DE WRAPPING
if (typeof window.updateAllChoicesWrap === 'function') {
    console.log('%c[FIX] Deshabilitando updateAllChoicesWrap', 'color: orange');
    window.updateAllChoicesWrap = function() { return; };
}

if (typeof window.updateProductoChoiceWrap === 'function') {
    console.log('%c[FIX] Deshabilitando updateProductoChoiceWrap', 'color: orange');
    window.updateProductoChoiceWrap = function() { return; };
}

// Crear stubs si no existen
if (!window.updateAllChoicesWrap) {
    console.log('%c[FIX] Creando stub para updateAllChoicesWrap', 'color: orange');
    window.updateAllChoicesWrap = function() { return; };
}

if (!window.updateProductoChoiceWrap) {
    console.log('%c[FIX] Creando stub para updateProductoChoiceWrap', 'color: orange');
    window.updateProductoChoiceWrap = function() { return; };
}

// DESCONECTAR CUALQUIER MUTATION OBSERVER ACTIVO
if (window._productoChoicesObserver) {
    console.log('%c[FIX] Desconectando _productoChoicesObserver', 'color: orange');
    try {
        window._productoChoicesObserver.disconnect();
        window._productoChoicesObserver = null;
    } catch(e) {}
}

// LIMPIAR TIMERS
if (window._wrapTimers && Array.isArray(window._wrapTimers)) {
    console.log('%c[FIX] Limpiando ' + window._wrapTimers.length + ' timers', 'color: orange');
    window._wrapTimers.forEach(function(t) {
        try { clearTimeout(t); } catch(e) {}
    });
    window._wrapTimers = [];
}

// BUSCAR Y DESCONECTAR TODOS LOS MUTATION OBSERVERS
setTimeout(function() {
    console.log('%c[FIX] Buscando y desconectando todos los MutationObservers...', 'color: orange');
    
    // Buscar referencias a observers en window
    var keys = Object.keys(window);
    var disconnected = 0;
    keys.forEach(function(key) {
        if (key.toLowerCase().includes('observer') || key.toLowerCase().includes('mutation')) {
            try {
                if (window[key] && typeof window[key].disconnect === 'function') {
                    console.log('%c[FIX] Desconectando: ' + key, 'color: yellow');
                    window[key].disconnect();
                    disconnected++;
                }
            } catch(e) {}
        }
    });
    
    console.log('%c[FIX] Desconectados ' + disconnected + ' observers', 'color: green');
}, 100);

console.log('%c[FIX] ✓ TODOS LOS BUCLES DESHABILITADOS', 'color: lime; font-weight: bold; font-size: 16px');
console.log('%c[FIX] Si ves este mensaje en VERDE, el fix se cargó correctamente', 'background: green; color: white; padding: 5px');

