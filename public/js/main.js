// main.js
document.addEventListener('DOMContentLoaded', function() {
    // Limpieza: sin logs en consola para clientes externos
    // Consulta Factiliza para RUC en crear transportista
    const btnConsultarRUC = document.getElementById('btnConsultarRUC');
    if (btnConsultarRUC) {
        // If page explicitly opts out of the global RUC handler (data-skip-global-ruc), do not attach
        if (btnConsultarRUC.dataset && btnConsultarRUC.dataset.skipGlobalRuc) {
            return;
        }
        btnConsultarRUC.addEventListener('click', function() {
            const rucInput = document.getElementById('RUC');
            const empresaInput = document.getElementById('Empresa');
            const direccionInput = document.getElementById('Direccion');
            const ruc = rucInput.value.trim();
            if (ruc.length !== 11 || isNaN(ruc)) {
                mostrarMensajeRUC('Ingrese un RUC válido de 11 dígitos.');
                rucInput.focus();
                return;
            }
            btnConsultarRUC.disabled = true;
            btnConsultarRUC.textContent = 'Consultando...';
            fetch(BASE_URL + '/api/consultarRuc', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ruc })
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success && data.razon_social) {
                    empresaInput.value = data.razon_social.toUpperCase();
                    if (direccionInput && data.direccion) {
                        direccionInput.value = data.direccion.toUpperCase();
                    }
                } else {
                    mostrarMensajeRUC(data.message || 'No se encontró razón social para el RUC ingresado.');
                }
            })
            .catch(() => {
                mostrarMensajeRUC('No se encontró razón social para el RUC ingresado.');
            })
            .finally(() => {
                btnConsultarRUC.disabled = false;
                btnConsultarRUC.textContent = 'Consultar SUNAT';
            });
        });

        // Función para mostrar mensaje de error con diseño
        function mostrarMensajeRUC(mensaje) {
            let div = document.getElementById('mensajeRUC');
            const rucHelp = document.getElementById('rucHelp');
            if (!div) {
                div = document.createElement('div');
                div.id = 'mensajeRUC';
                div.style.marginTop = '10px';
                div.style.padding = '12px';
                div.style.background = '#fff3cd';
                div.style.color = '#856404';
                div.style.border = '1px solid #ffeeba';
                div.style.borderRadius = '5px';
                div.style.fontWeight = 'bold';
                div.style.fontSize = '1rem';
                div.style.textAlign = 'center';
                // Insertar el mensaje justo después del help del RUC si existe
                if (rucHelp && rucHelp.parentNode) {
                    rucHelp.parentNode.insertBefore(div, rucHelp.nextSibling);
                } else if (direccionInput) {
                    direccionInput.parentNode.insertBefore(div, direccionInput.nextSibling);
                } else {
                    empresaInput.parentNode.insertBefore(div, empresaInput.nextSibling);
                }
            }
            div.textContent = mensaje;
            setTimeout(() => { div.remove(); }, 4000);
        }
    }
});

// Inicializar Bootstrap Select (si está presente) después de que todos los scripts se hayan cargado
document.addEventListener('DOMContentLoaded', function() {
    try {
        if (window.jQuery && typeof $.fn.selectpicker === 'function') {
            // Mostrar en consola cuántas opciones hay en cada select
            $('.selectpicker').each(function() {
                console.log('selectpicker init:', this.id || this.name, 'options=', $(this).find('option').length);
            });
            // Inicializar y forzar render/refresh para evitar condiciones de carrera
            $('.selectpicker').selectpicker();
            try {
                $('.selectpicker').selectpicker('render');
                $('.selectpicker').selectpicker('refresh');
            } catch (e) {
                console.warn('selectpicker render/refresh failed:', e);
            }
        } else {
            // Plugin no disponible: logear conteo de opciones nativas
            document.querySelectorAll('select.selectpicker').forEach(function(s) {
                console.log('selectpicker plugin missing - native select', s.id || s.name, 'options=', s.options.length);
            });
        }
    } catch (e) {
        // No hacemos nada; si falla, el select nativo seguirá funcionando
        console.warn('Inicialización global de selectpicker falló:', e);
    }
});