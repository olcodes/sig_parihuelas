/**
 * SOLUCIÓN PARA FILTRADO DE PRODUCTOS YA UTILIZADOS EN SELECTORES
 * 
 * INSTRUCCIONES DE IMPLEMENTACIÓN:
 * 1. Agrega este archivo a tu proyecto
 * 2. Incluye este archivo en tu vista (despachosinternos.php) antes del archivo reportes_despachosinternos.js
 * 3. Llama a la función generarSelectProductoFiltrado en lugar de generarSelectProducto
 * 
 * Esta solución filtra los productos ya utilizados en un despacho específico,
 * mostrando solo los productos disponibles y el producto actual que se está editando.
 */

// Módulo de filtrado de productos cargado sin mensajes de depuración
// Las notificaciones de depuración se han eliminado ya que el sistema funciona correctamente

// Variable para verificar si el módulo se cargó correctamente
window.moduloFiltroProductosCargado = true;

/**
 * Generador de selectores de productos con filtro
 * 
 * @param {string} valorActual - Código o descripción del producto actual
 * @param {string} nombreSelector - Nombre/ID para el elemento select
 * @param {Array} detallesDespacho - Array con todos los productos del despacho
 * @param {number} productoIdx - Índice del producto que se está editando
 * @returns {string} HTML del selector con opciones filtradas
 */
window.generarSelectProductoFiltrado = function(valorActual, nombreSelector, detallesDespacho, productoIdx) {
    // Verificar si tenemos datos de productos disponibles
    if (!window.datosSelectores || !window.datosSelectores.productos || !Array.isArray(window.datosSelectores.productos)) {
        console.warn('[WARN] No hay datos de productos disponibles para el selector filtrado');
        return '<input type="text" class="form-control form-control-sm" value="' + (valorActual || '') + '">';
    }
    
    // Obtener productos ya utilizados en este despacho (excepto el actual)
    const productosEnUso = [];
    if (Array.isArray(detallesDespacho)) {
        detallesDespacho.forEach((detalle, idx) => {
            if (idx !== parseInt(productoIdx) && detalle.Codigo) {
                productosEnUso.push(detalle.Codigo);
            }
        });
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
        }
        
        // Añadir la opción al selector
        select += `<option value="${producto.Codigo}" data-descripcion="${producto.Producto}" ${selected}>${producto.Producto}</option>`;
    });
    
    select += '</select>';
    return select;
};

/**
 * INSTRUCCIONES DE USO EN renderTablaProductos
 * 
 * En la función renderTablaProductos, encuentra el bloque donde se genera el HTML
 * para productos en edición y reemplaza la generación del select por:
 * 
 * const selectHTML = generarSelectProductoFiltrado(
 *     codigoActual,                // El código del producto actual
 *     `codigo-${prodIdx}`,         // Nombre para el select
 *     row.Detalles,                // Todos los detalles del despacho
 *     prodIdx                      // Índice del producto que se está editando
 * );
 * 
 * Esto generará un selector que solo muestra los productos disponibles.
 */

/**
 * INSTRUCCIONES DE USO EN activarEdicionProducto
 * 
 * En la función activarEdicionProducto, encuentra donde se genera el HTML
 * para el selector de productos y reemplázalo por:
 * 
 * // Buscar detalles del despacho para filtrar productos ya utilizados
 * let detallesDespacho = [];
 * if (window.datosDespachos && Array.isArray(window.datosDespachos)) {
 *     const despacho = window.datosDespachos.find(d => d.Id == despachoId);
 *     if (despacho && Array.isArray(despacho.Detalles)) {
 *         detallesDespacho = despacho.Detalles;
 *     }
 * }
 * 
 * // Generar el HTML del selector con el valor correcto
 * const selectHTML = generarSelectProductoFiltrado(
 *     valorParaSelect,
 *     'producto-selector',
 *     detallesDespacho,
 *     prodIdx
 * );
 */

/**
 * MEJORA ADICIONAL (OPCIONAL): GUARDAR DATOS DE DESPACHOS GLOBALMENTE
 * 
 * En la función cargarDatos, después de recibir la respuesta del servidor,
 * añade esta línea para guardar los datos globalmente:
 * 
 * window.datosDespachos = res.data;
 */