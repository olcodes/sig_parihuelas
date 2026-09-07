// Clean implementation for Despachos Externos (temporary safe file)
// Provides minimal, robust initialization: turno selection, disabling controls on load,
// and safe handlers for Nuevo/Modificar buttons. Keep this file small and syntactically correct.

(function(){
    'use strict';

    const btnNuevo = document.querySelector('.btn-outline-success');
    const btnModificar = document.getElementById('btnModificar');
    const turnoSelect = document.getElementById('turno');
    const turnos = window.turnosData || [];

    function horaEnMinutos(h){
        if(!h) return 0;
        const [hh, mm] = String(h).split(':');
        return (parseInt(hh,10)||0)*60 + (parseInt(mm,10)||0);
    }

    function setTurnoByHora(){
        if(!turnoSelect || !turnos.length) return false;
        const horaActual = window.horaActual || new Date().toTimeString().slice(0,5);
        const actualMin = horaEnMinutos(horaActual);
        for(const t of turnos){
            const ini = horaEnMinutos(t.HoraInicio);
            const fin = horaEnMinutos(t.HoraFin);
            const en = ini <= fin ? (actualMin >= ini && actualMin < fin) : (actualMin >= ini || actualMin < fin);
            if(en){
                try{ turnoSelect.value = t.Id; }catch(e){}
                if(window.choicesInstances && window.choicesInstances['turno']){
                    try{ window.choicesInstances['turno'].setChoiceByValue(t.Id); }catch(e){}
                }
                return true;
            }
        }
        return false;
    }

    function bloquearControles(){
        if(btnModificar) btnModificar.setAttribute('disabled','disabled');
        if(btnNuevo) btnNuevo.setAttribute('disabled','disabled');
        if(turnoSelect) turnoSelect.setAttribute('disabled','disabled');
    }

    function habilitarControles(){
        if(btnNuevo) btnNuevo.removeAttribute('disabled');
        if(btnModificar) btnModificar.removeAttribute('disabled');
        if(turnoSelect) turnoSelect.removeAttribute('disabled');
    }

    function inicializar(){
        try{
            // small delay in case DOM/Choices inits later
            setTimeout(function(){
                setTurnoByHora();
                // Start with controls blocked (user flow may enable Nuevo)
                bloquearControles();
                if(btnNuevo) btnNuevo.removeAttribute('disabled');
            }, 120);

            if(btnNuevo){
                btnNuevo.addEventListener('click', function(){
                    // Nuevo flow: enable inputs for creating a new despacho
                    habilitarControles();
                });
            }

            if(btnModificar){
                // btnModificar should be disabled until a despacho is loaded
                btnModificar.setAttribute('disabled','disabled');
            }

        }catch(err){
            console.error('despachosexternos.clean.js init error', err);
        }
    }

    if(typeof document !== 'undefined') document.addEventListener('DOMContentLoaded', inicializar);

})();
