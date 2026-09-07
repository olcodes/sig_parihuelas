<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Consolidado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .export-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
        }
        
        .export-button:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.4);
        }
        
        .export-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }
        
        .export-button i {
            margin-right: 8px;
        }
        
        .container-consolidado {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .header-consolidado {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header-consolidado h2 {
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header-consolidado p {
            color: #64748b;
            font-size: 1.1rem;
        }
        
        .card-consolidado {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-consolidado">
        <div class="header-consolidado">
            <h2><i class="bi bi-grid-3x3-gap-fill me-2" style="color:#2563eb;"></i>Reporte Consolidado</h2>
            <p>Exportación de reporte consolidado del sistema</p>
        </div>
        
        <?php
        $anioActual = (int)date('Y');
        $anios = range($anioActual, $anioActual - 10);
        $mesActual = (int)date('n');
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        ?>
        <div class="card-consolidado">
            <div class="filtros-consolidado" style="text-align:left; margin-bottom:24px;">
                <h5 style="color:#1e293b; margin-bottom:16px;"><i class="bi bi-funnel-fill me-2" style="color:#2563eb;"></i>Filtros de exportación</h5>

                <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
                    <div>
                        <label class="form-label" style="font-weight:600; color:#334155; margin-bottom:4px;">Período</label>
                        <select id="filtroPeriodo" class="form-select" style="min-width:140px;">
                            <option value="dia">Día</option>
                            <option value="mes" selected>Mes</option>
                            <option value="anio">Año</option>
                            <option value="rango">Rango</option>
                        </select>
                    </div>
                    <div id="campoFecha" style="display:none;">
                        <label class="form-label" style="font-weight:600; color:#334155; margin-bottom:4px;">Fecha</label>
                        <input type="date" id="filtroFecha" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div id="campoMes">
                        <label class="form-label" style="font-weight:600; color:#334155; margin-bottom:4px;">Mes</label>
                        <select id="filtroMes" class="form-select" style="min-width:150px;">
                            <?php foreach ($meses as $num => $nombre): ?>
                                <option value="<?= $num ?>" <?= $num === $mesActual ? 'selected' : '' ?>><?= $nombre ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="campoAnio">
                        <label class="form-label" style="font-weight:600; color:#334155; margin-bottom:4px;">Año</label>
                        <select id="filtroAnio" class="form-select" style="min-width:110px;">
                            <?php foreach ($anios as $a): ?>
                                <option value="<?= $a ?>" <?= $a === $anioActual ? 'selected' : '' ?>><?= $a ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="campoRangoInicio" style="display:none;">
                        <label class="form-label" style="font-weight:600; color:#334155; margin-bottom:4px;">Fecha de inicio</label>
                        <input type="date" id="filtroFechaInicio" class="form-control" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div id="campoRangoFin" style="display:none;">
                        <label class="form-label" style="font-weight:600; color:#334155; margin-bottom:4px;">Fecha de fin</label>
                        <input type="date" id="filtroFechaFin" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div style="margin-top:18px;">
                    <label class="form-label" style="font-weight:600; color:#334155; margin-bottom:6px;">Módulos a exportar</label>
                    <div style="display:flex; flex-wrap:wrap; gap:18px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modDespachoInterno" value="despacho_interno" checked>
                            <label class="form-check-label" for="modDespachoInterno">Despacho Interno</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modDespachoExterno" value="despacho_externo" checked>
                            <label class="form-check-label" for="modDespachoExterno">Despacho Externo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modRecepcionInterna" value="recepcion_interna" checked>
                            <label class="form-check-label" for="modRecepcionInterna">Recepción Interna</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modRecepcionExterna" value="recepcion_externa" checked>
                            <label class="form-check-label" for="modRecepcionExterna">Recepción Externa</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center align-items-center" style="min-height: 80px;">
                <button type="button" class="btn export-button" id="btnExportarConsolidado">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                    Exportar Consolidado
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de progreso de exportación -->
    <div id="exportProgressModal" style="display:none; position:fixed; inset:0; z-index:9999;">
        <div style="position:absolute; inset:0; background:rgba(0,0,0,0.4);"></div>
        <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center;">
            <div style="background:#fff; padding:30px; border-radius:12px; width:440px; max-width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.2); text-align:center;">
                <h5 style="margin:0 0 8px 0; font-size:1.3rem; color:#1e293b;">Generando reporte</h5>
                <p id="exportModalMessage" style="margin:0 0 24px 0; color:#64748b;">Preparando la exportación, por favor espere...</p>

                <div style="display:flex; align-items:center; justify-content:center; margin-bottom:24px;">
                    <div class="spinner-border text-success" role="status" style="width:48px; height:48px;" aria-label="Cargando">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>

                <button id="exportCancelBtn" type="button" class="btn btn-light btn-sm">Cancelar</button>
            </div>
        </div>
    </div>

    <style>
        /* Modal styles */
        #exportProgressModal .spinner-border {
            border: 3px solid rgba(16, 185, 129, 0.2);
            border-top-color: #10b981;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }
        @keyframes spinner-border {
            to { transform: rotate(360deg); }
        }
        .progress-bar.indeterminate {
            width: 100% !important;
            animation: indeterminate-anim 1.5s ease-in-out infinite;
        }
        @keyframes indeterminate-anim {
            0% { transform: translateX(-100%); }
            50% { transform: translateX(0%); }
            100% { transform: translateX(100%); }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnExportar = document.getElementById('btnExportarConsolidado');
        const modalRoot = document.getElementById('exportProgressModal');
        const msgText = document.getElementById('exportModalMessage');
        const cancelBtn = document.getElementById('exportCancelBtn');

        let controller = null;
        const TIMEOUT_MS = 300000; // 5 minutos

        // === Lógica de filtros (período: día/mes/año/rango y módulos) ===
        const filtroPeriodo = document.getElementById('filtroPeriodo');
        const campoFecha = document.getElementById('campoFecha');
        const campoMes = document.getElementById('campoMes');
        const campoAnio = document.getElementById('campoAnio');
        const campoRangoInicio = document.getElementById('campoRangoInicio');
        const campoRangoFin = document.getElementById('campoRangoFin');

        function actualizarCamposPeriodo() {
            const p = filtroPeriodo.value;
            const rangoVisible = (p === 'rango');
            campoFecha.style.display = (p === 'dia') ? 'block' : 'none';
            campoMes.style.display = (p === 'mes') ? 'block' : 'none';
            campoAnio.style.display = (p === 'mes' || p === 'anio') ? 'block' : 'none';
            campoRangoInicio.style.display = rangoVisible ? 'block' : 'none';
            campoRangoFin.style.display = rangoVisible ? 'block' : 'none';
        }
        filtroPeriodo.addEventListener('change', actualizarCamposPeriodo);
        actualizarCamposPeriodo();

        function construirFiltros() {
            const p = filtroPeriodo.value;
            const fecha = document.getElementById('filtroFecha').value;
            const mes = document.getElementById('filtroMes').value;
            const anio = document.getElementById('filtroAnio').value;
            let fechaDesde = '', fechaHasta = '';

            if (p === 'dia') {
                if (!fecha) { showError('Seleccione una fecha.'); return null; }
                fechaDesde = fecha;
                fechaHasta = fecha;
            } else if (p === 'mes') {
                if (!mes || !anio) { showError('Seleccione mes y año.'); return null; }
                const ultimoDia = new Date(parseInt(anio, 10), parseInt(mes, 10), 0).getDate();
                fechaDesde = anio + '-' + String(mes).padStart(2, '0') + '-01';
                fechaHasta = anio + '-' + String(mes).padStart(2, '0') + '-' + String(ultimoDia).padStart(2, '0');
            } else if (p === 'rango') {
                const fechaInicio = document.getElementById('filtroFechaInicio').value;
                const fechaFin = document.getElementById('filtroFechaFin').value;
                if (!fechaInicio || !fechaFin) { showError('Seleccione la fecha de inicio y de fin.'); return null; }
                if (fechaInicio > fechaFin) { showError('La fecha de inicio no puede ser mayor que la fecha de fin.'); return null; }
                fechaDesde = fechaInicio;
                fechaHasta = fechaFin;
            } else {
                if (!anio) { showError('Seleccione el año.'); return null; }
                fechaDesde = anio + '-01-01';
                fechaHasta = anio + '-12-31';
            }

            const modulos = [];
            ['modDespachoInterno', 'modDespachoExterno', 'modRecepcionInterna', 'modRecepcionExterna'].forEach(function(id) {
                const el = document.getElementById(id);
                if (el && el.checked) modulos.push(el.value);
            });
            if (modulos.length === 0) { showError('Seleccione al menos un módulo para exportar.'); return null; }

            return { fechaDesde: fechaDesde, fechaHasta: fechaHasta, modulos: modulos.join(',') };
        }

        function showProgressModal() {
            modalRoot.style.display = 'block';
            msgText.textContent = 'Preparando la exportación, por favor espere...';
        }

        function hideProgressModal() {
            modalRoot.style.display = 'none';
        }

        function showError(msg) {
            alert(msg || 'Error al generar el reporte');
        }

        cancelBtn.addEventListener('click', function() {
            if (controller) {
                controller.abort();
                msgText.textContent = 'Cancelando...';
            }
        });

        btnExportar.addEventListener('click', function() {
            const filtros = construirFiltros();
            if (!filtros) return; // showError ya notificó

            btnExportar.disabled = true;
            showProgressModal();

            const params = new URLSearchParams();
            params.set('fechaDesde', filtros.fechaDesde);
            params.set('fechaHasta', filtros.fechaHasta);
            params.set('modulos', filtros.modulos);

            setTimeout(() => {
                const xhr = new XMLHttpRequest();
                controller = xhr;

                xhr.open('GET', '<?= app_url('reportes/exportarConsolidado') ?>?' + params.toString(), true);
                xhr.responseType = 'blob';
                xhr.timeout = TIMEOUT_MS;

                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        // Si el servidor devolvió un error HTML (aunque el status sea 200), no descargarlo como Excel
                        if (xhr.response && xhr.response.type && xhr.response.type.indexOf('text/html') !== -1) {
                            const reader = new FileReader();
                            reader.onload = function() {
                                showError('El servidor devolvió un error: ' + String(reader.result || '').slice(0, 300));
                            };
                            reader.readAsText(xhr.response);
                        } else {
                            const disposition = xhr.getResponseHeader('Content-Disposition');
                            const filename = getFilenameFromDisposition(disposition) || 'reporte_consolidado.xlsx';
                            downloadBlob(xhr.response, filename);
                        }
                    } else {
                        showError('Respuesta del servidor: ' + xhr.status);
                    }
                    hideProgressModal();
                    btnExportar.disabled = false;
                    controller = null;
                };

                xhr.ontimeout = function() {
                    showError('La exportación excedió el tiempo límite (5 min). Intente con un rango de fechas más pequeño o menos módulos.');
                    hideProgressModal();
                    btnExportar.disabled = false;
                    controller = null;
                };

                xhr.onerror = function() {
                    showError('Error de red durante la exportación.');
                    hideProgressModal();
                    btnExportar.disabled = false;
                    controller = null;
                };

                xhr.onabort = function() {
                    showError('Exportación cancelada.');
                    hideProgressModal();
                    btnExportar.disabled = false;
                    controller = null;
                };

                xhr.send();
            }, 100);
        });

        function downloadBlob(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }

        function getFilenameFromDisposition(disposition) {
            if (!disposition) return null;

            // RFC 5987 (filename*=UTF-8''...)
            const filenameStarMatch = disposition.match(/filename\*=(?:UTF-8'')?([^;\n]+)/i);
            if (filenameStarMatch) {
                let fn = filenameStarMatch[1].trim();
                fn = fn.replace(/^UTF-8''/i, '');
                fn = fn.replace(/^"|"$/g, '');
                try { fn = decodeURIComponent(fn); } catch (e) {}
                return sanitizeFilename(tryFixLatin1Utf8Mix(fn));
            }

            // fallback: filename="..." or filename=token
            const fnMatch = disposition.match(/filename=(?:"([^"\n]+)"|([^;\n]+))/i);
            if (fnMatch) {
                let fn = fnMatch[1] || fnMatch[2] || '';
                fn = fn.trim().replace(/^"|"$/g, '');
                try {
                    if (/%[0-9A-F]/i.test(fn)) fn = decodeURIComponent(fn);
                } catch (e) {}
                return sanitizeFilename(tryFixLatin1Utf8Mix(fn));
            }

            return null;
        }

        function tryFixLatin1Utf8Mix(s) {
            try {
                return decodeURIComponent(escape(s));
            } catch (e) {
                return s;
            }
        }

        function sanitizeFilename(name) {
            if (!name) return name;
            name = name.split('\\').pop().split('/').pop();
            name = name.replace(/[\\/:*?"<>|]+/g, '_');
            return name;
        }
    });
    </script>
</body>
</html>
