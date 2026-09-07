// Actualizar función para enviar un producto específico

// Guardar un producto específico en el servidor
function guardarProductoEnServidor(producto, despachoId) {
    console.log('Guardando producto en el servidor:', producto, 'para despacho:', despachoId);
    
    // Validar datos importantes
    if (!producto || !producto.Codigo || !despachoId) {
        console.error('Datos incompletos para guardar el producto');
        mostrarMensaje('Error: Datos incompletos del producto', 'danger');
        return Promise.reject('Datos incompletos');
    }
    
    // Asegurarse de que la cantidad sea un número
    if (producto.Cantidad) {
        producto.Cantidad = parseFloat(producto.Cantidad);
    }
    
    // Preparar datos para enviar
    const datosEnvio = {
        despachoId: despachoId,
        producto: {
            Codigo: producto.Codigo,
            Producto: producto.Producto,
            Cantidad: producto.Cantidad,
            Comentarios: producto.Comentarios || ''
        }
    };
    
    console.log('Datos a enviar:', JSON.stringify(datosEnvio));
    
    // Realizar petición AJAX
    return fetch('/reportes/actualizarProductoEspecifico', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datosEnvio)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta del servidor:', data);
        if (data.success) {
            mostrarMensaje('Producto guardado correctamente', 'success');
            return data;
        } else {
            console.error('Error al guardar el producto:', data.message);
            mostrarMensaje('Error: ' + data.message, 'danger');
            return Promise.reject(data.message);
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
        mostrarMensaje('Error al guardar el producto: ' + error.message, 'danger');
        return Promise.reject(error);
    });
}

// Función para activar la edición de un producto específico sin colapsar la fila
function activarEdicionProducto(btn) {
    const fila = btn.closest('tr');
    const filaId = fila.getAttribute('data-id');
    const productoId = fila.getAttribute('data-producto-id');
    const despachoId = fila.closest('table').getAttribute('data-despacho-id');
    
    console.log('Activando edición de producto:', productoId, 'en despacho:', despachoId);
    
    // Añadir esta fila a filasExpandidas si no está ya
    if (!window.filasExpandidas.includes(filaId)) {
        window.filasExpandidas.push(filaId);
        console.log('Fila añadida a expandidas:', filaId, window.filasExpandidas);
    }
    
    // Campos a habilitar para edición
    const camposEditables = fila.querySelectorAll('.campo-editable');
    camposEditables.forEach(campo => {
        campo.contentEditable = 'true';
        campo.classList.add('editando');
    });
    
    // Cambiar botones: ocultar editar, mostrar guardar y cancelar
    btn.style.display = 'none';
    
    const btnGuardar = fila.querySelector('.btn-guardar-producto');
    const btnCancelar = fila.querySelector('.btn-cancelar-producto');
    
    if (btnGuardar) btnGuardar.style.display = 'inline-block';
    if (btnCancelar) {
        // Limpiar estilos inline que podrían mantener apariencia oculta o errónea
        btnCancelar.style.cssText = '';
        btnCancelar.style.display = 'inline-block';
        try { btnCancelar.style.visibility = ''; } catch(e){}
        try { btnCancelar.style.opacity = ''; } catch(e){}
        try { btnCancelar.removeAttribute('hidden'); } catch(e){}
        try { btnCancelar.classList.remove('invisible'); } catch(e){}
        try { btnCancelar.style.zIndex = '2000'; } catch(e){}
        // Forzar apariencia gris en lugar de roja (evitar la semántica de "eliminar")
        try { btnCancelar.classList.remove('btn-danger'); } catch(e){}
        try { btnCancelar.classList.add('btn-secondary'); } catch(e){}
        try { btnCancelar.style.backgroundColor = ''; btnCancelar.style.borderColor = ''; } catch(e){}
        try { console.log('[DEBUG] Mostrar btn-cancelar-producto computedStyle (producto_edicion):', window.getComputedStyle(btnCancelar)); } catch(e){}
    }
    
    // Guardar valores originales para poder cancelar
    camposEditables.forEach(campo => {
        campo.setAttribute('data-original', campo.textContent.trim());
    });
    
    // Enfocar el primer campo editable
    if (camposEditables.length > 0) {
        camposEditables[0].focus();
    }
}

// Guardar cambios de un producto específico
function guardarCambiosProducto(btn) {
    const fila = btn.closest('tr');
    const filaId = fila.getAttribute('data-id');
    const despachoId = fila.closest('table').getAttribute('data-despacho-id');
    
    // Recopilar datos actualizados
    const codigo = fila.getAttribute('data-producto-id');
    const nombreProducto = fila.querySelector('[data-campo="producto"]').textContent.trim();
    const cantidad = fila.querySelector('[data-campo="cantidad"]').textContent.trim();
    const comentarios = fila.querySelector('[data-campo="comentarios"]')?.textContent.trim() || '';
    
    const productoActualizado = {
        Codigo: codigo,
        Producto: nombreProducto,
        Cantidad: cantidad,
        Comentarios: comentarios
    };
    
    console.log('Guardando cambios del producto:', productoActualizado);
    
    // Deshabilitar edición mientras se guarda
    const camposEditables = fila.querySelectorAll('.campo-editable');
    camposEditables.forEach(campo => {
        campo.contentEditable = 'false';
        campo.classList.remove('editando');
    });
    
    // Mostrar indicador de carga
    btn.disabled = true;
    const btnText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    
    // Guardar en el servidor
    guardarProductoEnServidor(productoActualizado, despachoId)
        .then(() => {
            // Restaurar botones
            btn.style.display = 'none';
            const cancelarBtn = fila.querySelector('.btn-cancelar-producto');
            if (cancelarBtn) {
                cancelarBtn.style.cssText = '';
                cancelarBtn.style.display = 'none';
                try { cancelarBtn.style.zIndex = ''; } catch(e){}
            }
            const editarBtn = fila.querySelector('.btn-editar-producto');
            if (editarBtn) editarBtn.style.display = 'inline-block';
        })
        .catch(error => {
            console.error('Error al guardar cambios:', error);
            // Reactivar edición en caso de error
            camposEditables.forEach(campo => {
                campo.contentEditable = 'true';
                campo.classList.add('editando');
            });
        })
        .finally(() => {
            // Restaurar botón de guardar
            btn.disabled = false;
            btn.innerHTML = btnText;
        });
}

// Cancelar edición de un producto
function cancelarEdicionProducto(btn) {
    const fila = btn.closest('tr');
    
    // Restaurar valores originales
    const camposEditables = fila.querySelectorAll('.campo-editable');
    camposEditables.forEach(campo => {
        const valorOriginal = campo.getAttribute('data-original');
        if (valorOriginal) {
            campo.textContent = valorOriginal;
        }
        campo.contentEditable = 'false';
        campo.classList.remove('editando');
    });
    
    // Restaurar botones
    try { btn.style.cssText = ''; } catch(e){}
    try { btn.style.display = 'none'; } catch(e){}
    const btnGuardar = fila.querySelector('.btn-guardar-producto');
    if (btnGuardar) try { btnGuardar.style.display = 'none'; } catch(e){}
    const btnEditar = fila.querySelector('.btn-editar-producto');
    if (btnEditar) try { btnEditar.style.display = 'inline-block'; } catch(e){}
    // Asegurar limpiar z-index si quedó aplicado
    try { btn.style.zIndex = ''; } catch(e){}
}

// Función para mostrar mensajes de notificación
function mostrarMensaje(mensaje, tipo) {
    // Crear elemento para el mensaje
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    `;
    
    // Buscar contenedor para mensajes
    let mensajesContainer = document.getElementById('mensajes-container');
    if (!mensajesContainer) {
        mensajesContainer = document.createElement('div');
        mensajesContainer.id = 'mensajes-container';
        mensajesContainer.style.position = 'fixed';
        mensajesContainer.style.top = '20px';
        mensajesContainer.style.right = '20px';
        mensajesContainer.style.zIndex = '9999';
        document.body.appendChild(mensajesContainer);
    }
    
    // Añadir mensaje al contenedor
    mensajesContainer.appendChild(alertDiv);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}