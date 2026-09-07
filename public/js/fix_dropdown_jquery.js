/**
 * Fix mejorado para el dropdown de usuario - Versión jQuery
 * Esta versión usa jQuery para mayor compatibilidad
 */
$(document).ready(function() {
    // Fix para el dropdown de usuario usando jQuery
    $('#userDropdown').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Toggle manual del dropdown
        const $dropdown = $(this).parent().find('.dropdown-menu');
        $dropdown.toggleClass('show');
        
        // Cerrar al hacer clic fuera
        $(document).on('click', function closeMenu(e) {
            if (!$(e.target).closest('.dropdown').length) {
                $dropdown.removeClass('show');
                $(document).off('click', closeMenu);
            }
        });
    });
    
    // Fix para menú general de bootstrap
    $('.dropdown-toggle').each(function() {
        const $this = $(this);
        const $parent = $this.parent();
        const $menu = $parent.find('.dropdown-menu');
        
        $this.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $menu.toggleClass('show');
            
            // Cerrar al hacer clic fuera
            $(document).on('click', function closeDropdown(e) {
                if (!$(e.target).closest('.dropdown').length) {
                    $menu.removeClass('show');
                    $(document).off('click', closeDropdown);
                }
            });
        });
    });
});