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
        
        <div class="card-consolidado">
            <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
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
            btnExportar.disabled = true;
            showProgressModal();

            setTimeout(() => {
                const xhr = new XMLHttpRequest();
                controller = xhr;

                xhr.open('GET', '<?= app_url('reportes/exportarConsolidado') ?>', true);
                xhr.responseType = 'blob';

                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        const disposition = xhr.getResponseHeader('Content-Disposition');
                        const filename = getFilenameFromDisposition(disposition) || 'reporte_consolidado.xlsx';
                        downloadBlob(xhr.response, filename);
                    } else {
                        showError('Respuesta del servidor: ' + xhr.status);
                    }
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
