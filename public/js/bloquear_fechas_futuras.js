/**
 * Script personalizado para bloquear fechas futuras en el calendario
 */
document.addEventListener('DOMContentLoaded', function() {
    // Función para obtener la fecha actual en formato YYYY-MM-DD
    function obtenerFechaHoy() {
        const hoy = new Date();
        const año = hoy.getFullYear();
        const mes = String(hoy.getMonth() + 1).padStart(2, '0');
        const dia = String(hoy.getDate()).padStart(2, '0');
        return `${año}-${mes}-${dia}`;
    }
    
    // Función para aplicar restricciones a un input de fecha
    function aplicarRestriccionesFecha(input) {
        if (!input || input.getAttribute('data-restriccion-aplicada') === 'true') {
            return; // Evitar aplicar más de una vez
        }
        
        const fechaHoy = obtenerFechaHoy();
        
        // Establecer la fecha máxima como hoy
        input.setAttribute('max', fechaHoy);
        
        // Agregar validación cuando cambia el valor
        input.addEventListener('change', function(e) {
            const fechaSeleccionada = new Date(this.value);
            fechaSeleccionada.setHours(0, 0, 0, 0);
            
            const fechaActual = new Date();
            fechaActual.setHours(0, 0, 0, 0);
            
            if (fechaSeleccionada > fechaActual) {
                console.warn('⚠️ Fecha futura bloqueada:', this.value);
                alert('No se permite seleccionar fechas futuras');
                this.value = fechaHoy;
            }
        });
        
        // Marcar como procesado
        input.setAttribute('data-restriccion-aplicada', 'true');
        console.log('✅ Restricción aplicada a input fecha:', input);
    }
    
    // Función que procesa todos los inputs de fecha en la página
    function limitarInputsFecha() {
        document.querySelectorAll('input[type="date"]').forEach(function(input) {
            aplicarRestriccionesFecha(input);
        });
    }
    
    // Ejecutar inmediatamente al cargar
    limitarInputsFecha();
    
    // Y también después de un retraso para asegurarnos
    setTimeout(limitarInputsFecha, 500);
    
    // Configurar un observador de mutaciones para detectar nuevos inputs de fecha
    // que se agreguen dinámicamente (por ejemplo, al editar un registro)
    const observador = new MutationObserver(function(mutaciones) {
        mutaciones.forEach(function(mutacion) {
            // Verificar si se agregaron nodos al DOM
            if (mutacion.addedNodes && mutacion.addedNodes.length > 0) {
                // Procesar cada nodo agregado
                mutacion.addedNodes.forEach(function(nodo) {
                    // Comprobar si el nodo es un elemento y posiblemente contiene inputs
                    if (nodo.nodeType === 1) { // ELEMENT_NODE
                        // Si el nodo es un input de fecha
                        if (nodo.nodeName === 'INPUT' && nodo.type === 'date') {
                            aplicarRestriccionesFecha(nodo);
                        }
                        
                        // O si contiene inputs de fecha
                        const inputsFecha = nodo.querySelectorAll('input[type="date"]');
                        if (inputsFecha.length > 0) {
                            inputsFecha.forEach(input => {
                                aplicarRestriccionesFecha(input);
                            });
                        }
                    }
                });
            }
        });
    });
    
    // Configurar y comenzar a observar
    observador.observe(document.body, {
        childList: true,      // Observar adiciones/eliminaciones directas de hijos
        subtree: true,        // Observar todos los descendientes
        attributes: false,    // No observar cambios de atributos
        characterData: false  // No observar cambios de datos de caracteres
    });
    
    console.log('🔍 Observador de mutaciones activado para inputs de fecha');
    
    // También verificar periódicamente por nuevos inputs que puedan haberse agregado
    setInterval(limitarInputsFecha, 2000);
});