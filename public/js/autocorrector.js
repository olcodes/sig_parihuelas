// Inicialización global para todos los campos con clase 'autocorrector'
// Validación global de mayúsculas para todos los campos con clase 'mayusculas'
document.addEventListener('DOMContentLoaded', function() {
    const camposMayus = document.querySelectorAll('textarea.mayusculas, input[type="text"].mayusculas');
    camposMayus.forEach(function(campo) {
        campo.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        campo.addEventListener('blur', function() {
            this.value = this.value.toUpperCase();
        });
    });
    // Manejo específico para placas: soportar `placa_tracto` y `placa_carreta` (A-Z y 0-9, máximo 6)
    ['placa_tracto', 'placa_carreta'].forEach(function(id){
        try{
            var el = document.getElementById(id);
            if(!el) return;
            el.setAttribute('maxlength','6');
            el.addEventListener('input', function(){
                let val = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if(val.length > 6) val = val.substring(0,6);
                if(this.value !== val) this.value = val;
            });
            el.addEventListener('blur', function(){
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g,'').substring(0,6);
            });
        }catch(e){}
    });
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const camposForm = form.querySelectorAll('textarea.mayusculas, input[type="text"].mayusculas');
            camposForm.forEach(function(campo) {
                campo.value = campo.value.toUpperCase();
            });
        });
    });
});
/**
 * AUTOCORRECTOR PARA CAMPO DE COMENTARIOS
 * Sistema que detecta errores comunes de escritura y los corrige automáticamente
 * 
 * INSTRUCCIONES DE USO:
 * 1. Escriba texto en el campo de comentarios (se convertirá a mayúsculas)
 * 2. Cuando termine de escribir y haga clic fuera del campo, el sistema verificará si hay errores
 * 3. Si encuentra errores, los corregirá automáticamente y mostrará una notificación
 * 
 * EJEMPLOS DE CORRECCIONES:
 * - Si escribe "DEFECUOSO", se corregirá a "DEFECTUOSO"
 * - Si escribe "IMCOMPLETO", se corregirá a "INCOMPLETO"
 * - Si escribe "EMBASE", se corregirá a "ENVASE"
 */


// Mensaje de depuración para confirmar que el script se ha cargado
console.log('Autocorrector cargado correctamente - ' + new Date().toLocaleString());

/**
 * Mostrar sugerencias de corrección al usuario para elegir
 * @param {HTMLElement} textarea - El textarea de comentarios
 * @param {Array} sugerencias - Array de objetos {incorrecta, sugerida, start, end}
 */
function mostrarSugerenciasAutocorrector(textarea, sugerencias) {
    // Eliminar sugerencias previas
    document.querySelectorAll('.autocorrector-sugerencias').forEach(el => el.remove());

    if (!sugerencias.length) return;

    // Crear contenedor de sugerencias
    const cont = document.createElement('div');
    cont.className = 'autocorrector-sugerencias';
    cont.style.position = 'absolute';
    cont.style.background = '#fff';
    cont.style.border = '1px solid #fd7e14';
    cont.style.borderRadius = '0.4rem';
    cont.style.boxShadow = '0 2px 8px #fd7e1440';
    cont.style.padding = '8px 12px';
    cont.style.zIndex = 9999;
    cont.style.fontSize = '1rem';
    cont.style.minWidth = '180px';
    cont.style.maxWidth = '320px';
    cont.style.top = (textarea.offsetTop + textarea.offsetHeight + 4) + 'px';
    cont.style.left = textarea.offsetLeft + 'px';

    cont.innerHTML = '<b>¿Quisiste decir?</b><ul style="margin:8px 0 0 0;padding-left:18px;">' +
        sugerencias.map(s => `<li style='margin-bottom:4px;cursor:pointer;color:#fd7e14;' data-start='${s.start}' data-end='${s.end}' data-sugerida='${s.sugerida}'>${s.sugerida}</li>`).join('') +
        '</ul>' +
        `<button type="button" class="btn btn-sm btn-secondary mt-2" style="margin-top:8px;" id="btnIgnorarSugerencia">Ignorar</button>`;

    // Evento click en sugerencia
    cont.querySelectorAll('li').forEach(li => {
        li.addEventListener('click', function() {
            const start = parseInt(this.getAttribute('data-start'));
            const end = parseInt(this.getAttribute('data-end'));
            const sugerida = this.getAttribute('data-sugerida');
            // Reemplazar la palabra en el textarea
            const val = textarea.value;
            textarea.value = val.substring(0, start) + sugerida + val.substring(end);
            textarea.focus();
            cont.remove();
        });
    });

    // Evento click en 'Ignorar'
    cont.querySelector('#btnIgnorarSugerencia').addEventListener('click', function() {
        cont.remove();
        textarea.blur(); // Permite pasar al siguiente campo
    });

    // Insertar en el DOM
    textarea.parentNode.appendChild(cont);
}
/**
 * Este diccionario contiene palabras mal escritas y su forma correcta
 * Escribir alguna de estas palabras mal escritas en el campo de comentarios
 * activará el autocorrector cuando se haga clic fuera del campo
 */
const diccionarioCorrecciones = {
    // Palabras de prueba - intente escribir estas para ver el autocorrector en acción
    'PRUEVA': 'PRUEBA',         // Escriba PRUEVA para ver cómo se corrige
    'AVER': 'HABER',            // Escriba AVER para ver cómo se corrige
    'VIRSION': 'VERSION',       // Escriba VIRSION para ver cómo se corrige
    
    // Palabras relacionadas con estado físico
    'OBEDESE': 'OBEDECE',
    'OBECEDE': 'OBEDECE',
    'OBECEDESE': 'OBEDECESE',
    'OBECEDECE': 'OBEDECE',
    'OBECEDECER': 'OBEDECER',
    'OBECEDE': 'OBEDECE',
    'OBECEDE': 'OBEDECE',
    'OBEDECO': 'OBEDEZCO',
    'OBEDESCO': 'OBEDEZCO',
    'OBECEDEN': 'OBEDECEN',
    'OBEDESEN': 'OBEDECEN',
    
    // Palabras comúnmente mal escritas
    'ATRAVES': 'A TRAVÉS',
    'ATRAVEZ': 'A TRAVÉS',
    'TALVES': 'TAL VEZ',
    'TALVEZ': 'TAL VEZ',
    'ATRAVESAR': 'ATRAVESAR',
    'HABECES': 'A VECES',
    'HABESES': 'A VECES',
    'AVECES': 'A VECES',
    'DEVEZ': 'DE VEZ',
    'DEVÉS': 'DE VEZ',
    
    // Errores comunes generales
    'IMCOMPLETO': 'INCOMPLETO',
    'IMCOMPLETOS': 'INCOMPLETOS',
    'COMPLETO': 'COMPLETO',
    'DEFECTUOSO': 'DEFECTUOSO',
    'DEFECTUOSOS': 'DEFECTUOSOS',
    'DEFECUOSO': 'DEFECTUOSO',
    'DEFECUOSOS': 'DEFECTUOSOS',
    
    // Errores específicos del negocio
    'DAÑADO': 'DAÑADO',
    'DAÑOS': 'DAÑOS',
    'DANO': 'DAÑO',
    'DANOS': 'DAÑOS',
    'DANADO': 'DAÑADO',
    'DANADOS': 'DAÑADOS',
    'ROTTURA': 'ROTURA',
    'ROTTURAS': 'ROTURAS',
    'RROTURA': 'ROTURA',
    'RROTURAS': 'ROTURAS',
    'AVERIADO': 'AVERIADO',
    'ABERIADO': 'AVERIADO',
    'ABERIADOS': 'AVERIADOS',
    'DETERIORADO': 'DETERIORADO',
    'DETERIORO': 'DETERIORO',
    'EMBASE': 'ENVASE',
    'ENBASES': 'ENVASES',
    'EMBASES': 'ENVASES',
    'ENPAQUE': 'EMPAQUE',
    'ENPAQUES': 'EMPAQUES',
    'EMBOLTURA': 'ENVOLTURA',
    'EMBOLTURAS': 'ENVOLTURAS',
    
    // Cantidades y descripciones comunes
    'FATAN': 'FALTAN',
    'FATA': 'FALTA',
    'SOBRAM': 'SOBRAN',
    'SOBRA': 'SOBRA',
    'SOBRANTE': 'SOBRANTE',
    'SOBRANTES': 'SOBRANTES',
    'EXEDENTE': 'EXCEDENTE',
    'EXSEDENTE': 'EXCEDENTE',
    'EXEDENTES': 'EXCEDENTES',
    'EXSEDENTES': 'EXCEDENTES',
    'ESCASO': 'ESCASO',
    'ESCASOS': 'ESCASOS',
    'ESCACES': 'ESCASEZ',
    'INSUFICIENTE': 'INSUFICIENTE',
    'INSUFISIENTE': 'INSUFICIENTE',
    'EXTRABIO': 'EXTRAVIO',
    'ESTRABIO': 'EXTRAVIO',
    'EXTRABIOS': 'EXTRAVIOS',
    'EXEDENTES': 'EXCEDENTES',
    'EXESO': 'EXCESO',
    'EXESOS': 'EXCESOS',
    
    // Estado del material
    'BUEM': 'BUEN',
    'BUEMA': 'BUENA',
    'BUENO': 'BUENO',
    'BUEMA': 'BUENA',
    'EXELENTE': 'EXCELENTE',
    'EXELENTES': 'EXCELENTES',
    'PERFETO': 'PERFECTO',
    'PERFETOS': 'PERFECTOS',
    
    // Material/Empaque
    'EMBASE': 'ENVASE',
    'EMBASES': 'ENVASES',
    'ENPAQUE': 'EMPAQUE',
    'ENPAQUES': 'EMPAQUES',
    'CAGA': 'CAJA',
    'CAGAS': 'CAJAS',
    'CAGON': 'CAJON',
    'CAGONES': 'CAJONES',
    'BOLZA': 'BOLSA',
    'BOLZAS': 'BOLSAS',
    
    // Acciones comunes
    'REBISAR': 'REVISAR',
    'REBISADO': 'REVISADO',
    'REBISADA': 'REVISADA',
    'REBISADOS': 'REVISADOS',
    'REBISADAS': 'REVISADAS',
    'BERIFICAR': 'VERIFICAR',
    'BERIFICADO': 'VERIFICADO',
    'BERIFICADA': 'VERIFICADA',
    'BERIFICADOS': 'VERIFICADOS',
    'BERIFICADAS': 'VERIFICADAS',
    'CAMVIAR': 'CAMBIAR',
    'CAMVIADO': 'CAMBIADO',
    'CAMVIADA': 'CAMBIADA',
    'CAMVIADOS': 'CAMBIADOS',
    'CAMVIADAS': 'CAMBIADAS',
    'REEMPLAZAR': 'REEMPLAZAR',
    'REEMPLAZADO': 'REEMPLAZADO',
    'REEMPLAZADA': 'REEMPLAZADA',
    'REEMPLAZADOS': 'REEMPLAZADOS',
    'REEMPLAZADAS': 'REEMPLAZADAS',
    'AJUSTAR': 'AJUSTAR',
    'AJUSTADO': 'AJUSTADO',
    'AJUSTADA': 'AJUSTADA',
    'AJUSTADOS': 'AJUSTADOS',
    'AJUSTADAS': 'AJUSTADAS',
    
    // Urgencia
    'URGENTE': 'URGENTE',
    'URJENTE': 'URGENTE',
    'PRIORITARIO': 'PRIORITARIO',
    'PRIORITARIOS': 'PRIORITARIOS',
    'INMEDIATO': 'INMEDIATO',
    'INMEDIATA': 'INMEDIATA',
    
    // Descripciones de inventario
    'EXISTENSIA': 'EXISTENCIA',
    'EXISTENSIAS': 'EXISTENCIAS',
    'NESECITA': 'NECESITA',
    'NESECITO': 'NECESITO',
    'NESESITA': 'NECESITA',
    'NESESITO': 'NECESITO',
    'NESESIDAD': 'NECESIDAD',
    'NESECIDAD': 'NECESIDAD',
    'MESCLA': 'MEZCLA',
    'MESCLAS': 'MEZCLAS',
    'APROCSIMADO': 'APROXIMADO',
    'APROCSIMADA': 'APROXIMADA',
    'APROCSIMADAS': 'APROXIMADAS',
    'APROCSIMADOS': 'APROXIMADOS',
    'SIERTO': 'CIERTO',
    'CIETO': 'CIERTO',
    'REVISAR': 'REVISAR',
    'REVICAR': 'REVISAR',
    'REVIZAR': 'REVISAR',
    'REVICION': 'REVISIÓN',
    'REVIZION': 'REVISIÓN',
};

/**
 * Función para mostrar una notificación de corrección
 * @param {string} original - Texto original
 * @param {string} corregido - Texto corregido
 */
function mostrarNotificacionCorreccion(original, corregido) {
    // Encontrar las palabras corregidas
    const palabrasOriginales = original.split(/\s+/);
    const palabrasCorregidas = corregido.split(/\s+/);
    const cambios = [];
    
    // Comparar y encontrar cambios
    for (let i = 0; i < Math.min(palabrasOriginales.length, palabrasCorregidas.length); i++) {
        if (palabrasOriginales[i] !== palabrasCorregidas[i]) {
            cambios.push({
                original: palabrasOriginales[i],
                corregido: palabrasCorregidas[i]
            });
        }
    }
    
    // Si hay cambios, mostrar notificación
    if (cambios.length > 0) {
        // Crear contenido de la notificación
        let mensaje = '<div class="autocorrector-notificacion">';
        mensaje += '<strong>Autocorrección aplicada:</strong><br>';
        mensaje += '<ul style="padding-left: 20px; margin-bottom: 5px;">';
        
        cambios.forEach(cambio => {
            mensaje += `<li>"${cambio.original}" → "${cambio.corregido}"</li>`;
        });
        
        mensaje += '</ul></div>';
        
        // Crear y mostrar notificación
        const notificacion = document.createElement('div');
        notificacion.className = 'toast show autocorrector-toast';
        notificacion.innerHTML = `
            <div class="toast-header">
                <strong class="me-auto"><i class="bi bi-magic me-1"></i> Autocorrector</strong>
                <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
            <div class="toast-body">
                ${mensaje}
            </div>
        `;
        
        // Estilos inline para la notificación
        notificacion.style.position = 'fixed';
        notificacion.style.bottom = '10px';
        notificacion.style.right = '10px';
        notificacion.style.minWidth = '250px';
        notificacion.style.zIndex = '9999';
        notificacion.style.backgroundColor = '#fff';
        notificacion.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
        notificacion.style.borderRadius = '0.25rem';
        notificacion.style.border = '1px solid rgba(0, 0, 0, 0.1)';
        
        // Agregar al cuerpo del documento
        document.body.appendChild(notificacion);
        
        // Auto eliminar después de 5 segundos
        setTimeout(() => {
            notificacion.classList.remove('show');
            setTimeout(() => {
                notificacion.remove();
            }, 300);
        }, 5000);
        
        return true; // Indica que se hizo alguna corrección
    }
    
    return false; // Indica que no hubo correcciones
}

/**
 * Función para corregir texto según el diccionario de correcciones
 * @param {string} texto - Texto a corregir
 * @param {boolean} mostrarNotificacion - Si es true, muestra una notificación con los cambios
 * @return {Object} - Objeto con el texto corregido y si hubo cambios
 */

function autocorregirTexto(texto, mostrarNotificacion = true, textarea = null) {
    if (!texto || typeof texto !== 'string') return { 
        textoCorregido: texto, 
        huboCorrecciones: false 
    };
    // Eliminar espacios al inicio y final, y reemplazar múltiples espacios por uno solo
    let textoLimpio = texto.trim().replace(/\s{2,}/g, ' ');
    const huboCorreccionesEspacios = texto !== textoLimpio;
    const textoOriginal = texto;
    const palabras = textoLimpio.split(/\s+/);
    let sugerencias = [];
    let palabrasCorregidas = palabras.map((palabra, idx) => {
        const palabraSinPuntuacion = palabra.replace(/[.,;:!?]/g, '');
        let start = texto.indexOf(palabra);
        let end = start + palabra.length;
        // Si la palabra está exactamente en el diccionario
        if (diccionarioCorrecciones[palabraSinPuntuacion]) {
            sugerencias.push({
                incorrecta: palabraSinPuntuacion,
                sugerida: diccionarioCorrecciones[palabraSinPuntuacion],
                start, end
            });
            // No corregimos automáticamente, solo sugerimos
            return palabra;
        }
        // Buscar variantes cercanas
        for (const [clave, valor] of Object.entries(diccionarioCorrecciones)) {
            if (palabraSinPuntuacion.length > 4 && palabraSinPuntuacion !== clave && esVariacionCercana(palabraSinPuntuacion, clave)) {
                sugerencias.push({
                    incorrecta: palabraSinPuntuacion,
                    sugerida: valor,
                    start, end
                });
                return palabra;
            }
        }
        return palabra;
    });
    function esVariacionCercana(palabra1, palabra2) {
        if (Math.abs(palabra1.length - palabra2.length) > 2) return false;
        let diferencias = 0;
        const longitudMaxima = Math.max(palabra1.length, palabra2.length);
        for (let i = 0; i < longitudMaxima; i++) {
            if (palabra1[i] !== palabra2[i]) {
                diferencias++;
                if (diferencias > 2) return false;
            }
        }
        return true;
    }
    const textoCorregido = palabrasCorregidas.join(' ');
    // Si hubo correcciones de espacios, también lo marcamos
    const huboCorrecciones = sugerencias.length > 0 || huboCorreccionesEspacios;
    if (huboCorrecciones && textarea) {
        mostrarSugerenciasAutocorrector(textarea, sugerencias);
    }
    if (huboCorrecciones && mostrarNotificacion) {
        mostrarNotificacionCorreccion(textoOriginal, textoCorregido);
    }
    return {
        textoCorregido,
        huboCorrecciones,
        sugerencias
    };
}

/**
 * Función para que los usuarios puedan sugerir nuevas correcciones
 * @param {string} palabraIncorrecta - La palabra mal escrita
 * @param {string} palabraCorrecta - La forma correcta de la palabra
 */
function sugerirCorreccion(palabraIncorrecta, palabraCorrecta) {
    // Esta función podría enviar la sugerencia a un servidor o guardarla localmente
    // Por ahora, solo mostramos un mensaje en la consola y almacenamos en localStorage
    console.log(`Nueva sugerencia de corrección: ${palabraIncorrecta} -> ${palabraCorrecta}`);
    
    // Obtener sugerencias existentes o inicializar array
    const sugerenciasExistentes = JSON.parse(localStorage.getItem('sugerenciasAutocorrector') || '[]');
    
    // Añadir nueva sugerencia
    sugerenciasExistentes.push({
        incorrecta: palabraIncorrecta.toUpperCase(),
        correcta: palabraCorrecta.toUpperCase(),
        fecha: new Date().toISOString()
    });
    
    // Guardar en localStorage
    localStorage.setItem('sugerenciasAutocorrector', JSON.stringify(sugerenciasExistentes));
    
    // Mostrar notificación al usuario
    alert(`Gracias por tu sugerencia. Se ha registrado la corrección: ${palabraIncorrecta} -> ${palabraCorrecta}`);
}