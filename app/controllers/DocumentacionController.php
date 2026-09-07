<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class DocumentacionController extends Controller
{
    // Documentos permitidos (whitelist para evitar path traversal)
    private $docsPermitidos = [
        '01_diseno_sistema_informacion' => 'Diseño del Sistema de Información',
        '02_modelo_software'            => 'Modelo de Software',
        '03_manual_usuario'             => 'Manual de Usuario',
    ];

    public function index()
    {
        $this->requirePrivilegio('ver_documentacion');

        // Detectar si estamos en entorno local (WAMP) o hosting
        $esLocal = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ||
                   (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) ||
                   (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);

        $this->view('documentacion/index', [
            'docs'    => $this->docsPermitidos,
            'esLocal' => $esLocal,
        ]);
    }

    public function ver($doc = '')
    {
        $this->requirePrivilegio('ver_documentacion');

        // Validar que el nombre esté en la whitelist
        if (empty($doc) || !array_key_exists($doc, $this->docsPermitidos)) {
            http_response_code(404);
            die('<p style="font-family:sans-serif;padding:2rem;">Documento no encontrado.</p>');
        }

        $ruta = realpath(__DIR__ . '/../../docs/' . $doc . '.html');
        $esperado = realpath(__DIR__ . '/../../docs/');

        // Verificar que el archivo existe y está dentro del directorio docs/
        if (!$ruta || !$esperado || strpos($ruta, $esperado) !== 0 || !file_exists($ruta)) {
            http_response_code(404);
            die('<p style="font-family:sans-serif;padding:2rem;">Archivo no disponible.</p>');
        }

        header('Content-Type: text/html; charset=UTF-8');
        readfile($ruta);
        exit;
    }

    /**
     * Descargar documento como PDF
     * @param string $doc Nombre del documento (clave en docsPermitidos)
     */
    public function descargarPDF($doc = '')
    {
        $this->requirePrivilegio('ver_documentacion');

        // Validar que el nombre esté en la whitelist
        if (empty($doc) || !array_key_exists($doc, $this->docsPermitidos)) {
            http_response_code(404);
            die('<p style="font-family:sans-serif;padding:2rem;">Documento no encontrado.</p>');
        }

        $ruta = realpath(__DIR__ . '/../../docs/' . $doc . '.html');
        $esperado = realpath(__DIR__ . '/../../docs/');

        // Verificar que el archivo existe y está dentro del directorio docs/
        if (!$ruta || !$esperado || strpos($ruta, $esperado) !== 0 || !file_exists($ruta)) {
            http_response_code(404);
            die('<p style="font-family:sans-serif;padding:2rem;">Archivo no disponible.</p>');
        }

        // Leer el contenido HTML
        $html = file_get_contents($ruta);

        // ============================================================
        // PROCESAR HTML PARA PDF
        // ============================================================

        // 1. Convertir diagramas Mermaid a PNG usando servicios externos
        //    Dompdf no ejecuta JavaScript, así que renderizamos los
        //    diagramas del lado del servidor.
        //    Usamos PNG en lugar de SVG porque Dompdf no renderiza bien
        //    SVG embebido como data URI (se ve distorsionado).
        //    Estrategia híbrida:
        //      1º intento: mermaid.ink/img GET (rápido, funciona para ~80%)
        //      2º intento: Kroki.io POST (más robusto para diagramas complejos)
        //    NOTA: Usamos cURL porque file_get_contents falla con SSL en WAMP
        $html = preg_replace_callback(
            '/<div\s+class="mermaid">\s*([\s\S]*?)\s*<\/div>/is',
            function ($matches) {
                $mermaidCode = trim($matches[1]);
                if (empty($mermaidCode)) {
                    return '<div style="color:#999;font-style:italic;padding:10px;">[Diagrama no disponible]</div>';
                }
                
                $png = false;
                
                if (function_exists('curl_init')) {
                    // ============================================
                    // 1er INTENTO: mermaid.ink GET (URL-safe base64)
                    // ============================================
                    $encoded = base64_encode($mermaidCode);
                    $encoded = strtr($encoded, '+/', '-_');
                    $encoded = rtrim($encoded, '=');
                    
                    $ch = curl_init('https://mermaid.ink/img/' . $encoded);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT        => 20,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]);
                    $png = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    // ============================================
                    // 2do INTENTO: Kroki POST (si mermaid.ink falló)
                    // ============================================
                    if ($httpCode !== 200) {
                        $ch2 = curl_init('https://kroki.io/mermaid/png');
                        curl_setopt_array($ch2, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT        => 25,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false,
                            CURLOPT_POST           => true,
                            CURLOPT_POSTFIELDS     => json_encode([
                                'diagram_source' => $mermaidCode,
                                'diagram_type'   => 'mermaid',
                                'output_format'  => 'png',
                            ]),
                            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                        ]);
                        $png = curl_exec($ch2);
                        $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                        curl_close($ch2);
                        if ($httpCode !== 200) {
                            $png = false;
                        }
                    }
                }
                
                if ($png !== false && !empty($png)) {
                    // Embeber PNG como data URI (Dompdf lo soporta perfectamente)
                    $base64Png = base64_encode($png);
                    return '<div style="text-align:center;margin:16px 0;">'
                         . '<img src="data:image/png;base64,' . $base64Png . '" '
                         . 'style="max-width:100%;height:auto;" alt="Diagrama">'
                         . '</div>';
                }
                
                // Fallback: si ningún servicio responde, mostrar el código como texto
                $codigoHtml = htmlspecialchars($mermaidCode);
                return '<div style="background:#f0f4f8;border:1px solid #c8d6e5;border-radius:4px;padding:12px;margin:12px 0;font-family:monospace;font-size:8pt;white-space:pre-wrap;overflow-x:auto;">'
                     . '<strong style="color:#e67e22;">📐 Diagrama (no se pudo renderizar):</strong><br>'
                     . $codigoHtml
                     . '</div>';
            },
            $html
        );

        // 2. Eliminar el script de Mermaid (no se ejecutará en PDF)
        //    Usamos str_replace en lugar de preg_replace porque el HTML
        //    puede ser muy grande (~1.2MB) después de insertar los PNGs
        //    y preg_replace puede fallar con error de backtracking de PCRE.
        $html = str_replace(
            '<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>',
            '',
            $html
        );
        $html = str_replace(
            "<script>mermaid.initialize({ startOnLoad: true, theme: 'default', fontSize: 12 });</script>",
            '',
            $html
        );

        // 3. Agregar estilos adicionales para impresión PDF
        $estilosPDF = '
        <style>
            /* Ajustes específicos para PDF */
            body {
                font-family: "DejaVu Sans", "Calibri", "Arial", sans-serif;
                font-size: 10pt;
                line-height: 1.5;
                max-width: 100%;
                padding: 20px 30px;
                margin: 0;
            }
            .portada { page-break-after: always; }
            .toc { page-break-after: always; }
            h1 { page-break-before: auto; }
            h2, h3 { page-break-after: avoid; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            img { max-width: 100% !important; height: auto; }
            .mermaid { display: none; }
            /* Evitar que tablas anchas se salgan del PDF */
            table {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
                word-wrap: break-word !important;
                font-size: 8pt !important;
            }
            table td, table th {
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                padding: 3px 4px !important;
            }
            /* Forzar que cualquier contenedor no se desborde */
            div, pre, blockquote {
                max-width: 100% !important;
                overflow-x: auto !important;
            }
            @page { margin: 1.5cm 2cm; }
        </style>';

        // Insertar estilos PDF antes de </head>
        $html = str_replace('</head>', $estilosPDF . "\n</head>", $html);

        // 4. Convertir rutas relativas de imágenes a absolutas (por si acaso)
        //    No hay imágenes en los docs actuales, pero por compatibilidad futura
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        if (!empty($baseUrl)) {
            $html = preg_replace(
                '/src=["\'](?!https?:\/\/)(?!data:)([^"\']+)["\']/i',
                'src="' . $baseUrl . '/$1"',
                $html
            );
        }

        // ============================================================
        // GENERAR PDF CON DOMPDF
        // ============================================================
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // ============================================================
        // ENVIAR PDF AL NAVEGADOR
        // ============================================================
        $nombreArchivo = $doc . '.pdf';
        $tituloDoc = $this->docsPermitidos[$doc];

        $dompdf->stream($nombreArchivo, [
            'Attachment' => true, // true = descargar, false = ver en navegador
        ]);
        exit;
    }
}
