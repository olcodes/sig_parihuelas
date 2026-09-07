/**
 * Generador de selectores de productos con filtro para reportes despachos internos
 * Esta función genera un selector HTML con opciones filtradas de productos,
 * excluyendo aquellos que ya están en uso en un despacho específico
 */
function generarSelectProductoFiltrado(valorActual, nombreSelector, detallesDespacho, productoIdx) {
    if (!window.datosSelectores || !window.datosSelectores.productos) {
        return '<input type="text" class="form-control form-control-sm" value="' + (valorActual || '') + '">';
    }
    
    console.log('[DEBUG] generarSelectProductoFiltrado - Valor actual:', valorActual, 'Índice producto:', productoIdx);
    
    // Obtener productos ya utilizados en este despacho (excepto el actual)
    const productosEnUso = [];
    if (Array.isArray(detallesDespacho)) {
        detallesDespacho.forEach((detalle, idx) => {
            if (idx !== parseInt(productoIdx) && detalle.Codigo) {
                productosEnUso.push(detalle.Codigo);
            }
        });
        console.log('[DEBUG] Productos ya en uso:', productosEnUso);
    }
    
    // Encontrar el producto actual para asegurarnos de que esté seleccionado
    let productoEncontrado = null;
    if (valorActual) {
        productoEncontrado = window.datosSelectores.productos.find(p => 
            p.Codigo === valorActual || p.Producto === valorActual
        );
    }
    
    // Generar el HTML del select
    let select = '<select class="form-control form-control-sm producto-selector" name="' + nombreSelector + '">';
    select += '<option value="">Seleccionar producto</option>';
    
    // Añadir opciones, filtrando los productos ya utilizados
    window.datosSelectores.productos.forEach(producto => {
        // Si el producto ya está en uso y no es el producto actual que estamos editando, lo saltamos
        if (productosEnUso.includes(producto.Codigo) && producto.Codigo !== valorActual) {
            return;
        }
        
        // Determinar si este producto debe estar seleccionado
        let selected = '';
        if ((productoEncontrado && producto.Codigo === productoEncontrado.Codigo) ||
            (!productoEncontrado && (producto.Codigo === valorActual || producto.Producto === valorActual))) {
            selected = 'selected';
            console.log('[DEBUG] Producto seleccionado en selector filtrado:', producto.Producto);
        }
        
        // Añadir la opción al selector
        select += `<option value="${producto.Codigo}" data-descripcion="${producto.Producto}" ${selected}>${producto.Producto}</option>`;
    });
    
    select += '</select>';
    return select;
}