/**
 * Fix para el dropdown de usuario en la barra superior
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[INFO] Inicializando fix para dropdowns...');
    
    // Función para inicializar los dropdowns de Bootstrap
    function inicializarDropdowns() {
        // Verificamos que Bootstrap esté disponible
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            // Inicializamos todos los dropdowns de forma explícita
            document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(dropdownToggle) {
                try {
                    new bootstrap.Dropdown(dropdownToggle);
                    console.log('[INFO] Dropdown inicializado:', dropdownToggle.id || 'dropdown sin id');
                } catch (error) {
                    console.error('[ERROR] Error al inicializar dropdown:', error);
                }
            });
            
            // Específicamente para el dropdown de usuario
            const userDropdown = document.getElementById('userDropdown');
            if (userDropdown) {
                userDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('[DEBUG] Click en userDropdown');
                    const dropdown = bootstrap.Dropdown.getInstance(userDropdown) || new bootstrap.Dropdown(userDropdown);
                    dropdown.toggle();
                });
                console.log('[INFO] Event listener agregado a userDropdown');
            } else {
                console.warn('[WARN] No se encontró el elemento userDropdown');
            }
        } else {
            console.error('[ERROR] Bootstrap no está disponible o no tiene la clase Dropdown');
        }
    }
    
    // Ejecutamos inmediatamente y después de un corto retraso para asegurar que todo esté cargado
    inicializarDropdowns();
    setTimeout(inicializarDropdowns, 500);
});