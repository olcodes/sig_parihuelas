/**
 * Panel de Historial de Auditoría (Nivel 2)
 * Se incluye en todas las vistas de edición de vales.
 */
(function () {
    'use strict';

    var panelAbierto = false;
    var ultimoIdCargado = null;

    window.toggleHistorialPanel = function () {
        var body    = document.getElementById('historialBody');
        var chevron = document.getElementById('historialChevron');
        if (!body) return;

        panelAbierto = !panelAbierto;
        body.style.display = panelAbierto ? 'block' : 'none';
        if (chevron) {
            chevron.style.transform = panelAbierto ? 'rotate(180deg)' : '';
            chevron.style.transition = 'transform 0.2s';
        }

        if (panelAbierto) {
            cargarHistorial();
        }
    };

    window.cargarHistorial = function () {
        var id     = window.auditoriaIdActual  || null;
        var modulo = window.auditoriaModoActual || null;
        var contenido = document.getElementById('historialContenido');

        if (!contenido) return;

        if (!id || !modulo) {
            contenido.innerHTML = '<p class="text-muted mb-0 small"><i class="bi bi-info-circle me-1"></i>Cargue un vale para ver su historial de cambios.</p>';
            return;
        }

        // Evitar recargar si ya tenemos el mismo vale
        if (ultimoIdCargado === id && contenido.querySelector('table')) return;

        contenido.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2 text-muted small">Cargando historial…</span></div>';

        var url = (window.APP_URL || '') + '/auditoria/historial?modulo=' + encodeURIComponent(modulo) + '&id=' + encodeURIComponent(id);

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                ultimoIdCargado = id;
                renderizarHistorial(data, contenido);
            })
            .catch(function () {
                contenido.innerHTML = '<p class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Error al cargar el historial.</p>';
            });
    };

    function renderizarHistorial(data, contenido) {
        var badge = document.getElementById('historialBadge');

        if (!data.success || !data.historial || data.historial.length === 0) {
            contenido.innerHTML = '<p class="text-muted mb-0 small"><i class="bi bi-check-circle me-1"></i>Este vale no tiene modificaciones registradas.</p>';
            if (badge) badge.style.display = 'none';
            return;
        }

        var rows = data.historial.map(function (h, i) {
            var fh = h.ModificadoEn ? new Date(h.ModificadoEn) : null;
            var fecha = fh ? fh.toLocaleDateString('es-PE') : '—';
            var hora  = fh ? fh.toLocaleTimeString('es-PE') : '—';
            return '<tr>' +
                '<td class="px-3 text-center">' +
                    '<span class="badge rounded-pill" style="background:#fff3e0; color:#e65100; border:1px solid #ffcc80;">' + h.NModificacion + '</span>' +
                '</td>' +
                '<td class="px-2"><code style="font-size:0.85rem; background:#f1f3f9; padding:2px 6px; border-radius:4px; color:#1a237e;">' + escHtml(h.username || '—') + '</code></td>' +
                '<td class="px-2">' + escHtml(h.NombresApellidos || '—') + '</td>' +
                '<td class="px-2 text-nowrap">' + fecha + '</td>' +
                '<td class="px-2 text-nowrap" style="font-family:monospace;">' + hora + '</td>' +
                '</tr>';
        }).join('');

        contenido.innerHTML =
            '<div class="table-responsive">' +
            '<table class="table table-sm table-hover align-middle mb-0" style="font-size:0.88rem;">' +
            '<thead><tr style="background:#f0f4ff;">' +
            '<th class="px-3 py-1 text-center">N° Modif.</th>' +
            '<th class="px-2 py-1">Usuario</th>' +
            '<th class="px-2 py-1">Nombres y Apellidos</th>' +
            '<th class="px-2 py-1">Fecha</th>' +
            '<th class="px-2 py-1">Hora</th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table></div>';

        if (badge) {
            badge.textContent = data.historial.length + ' modif.';
            badge.style.display = '';
        }
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    // Auto-cargar cuando se asigna el ID (hook a través de setter)
    var _auditoriaIdActualInterno = null;
    Object.defineProperty(window, 'auditoriaIdActual', {
        get: function () { return _auditoriaIdActualInterno; },
        set: function (v) {
            _auditoriaIdActualInterno = v;
            ultimoIdCargado = null; // forzar recarga
            var badge = document.getElementById('historialBadge');
            if (badge) badge.style.display = 'none';
            // Si el panel ya está abierto, recargamos
            if (panelAbierto) {
                cargarHistorial();
            }
        },
        configurable: true
    });

}());
