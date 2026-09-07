<?php 
require_once __DIR__ . '/../../helpers/base_url.php'; 
require_once __DIR__ . '/../../helpers/url_helper.php';

// DESACTIVAR CACHE COMPLETAMENTE PARA TODAS LAS PÁGINAS
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- PRUEBA COPILOT HEAD -->
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= isset($titulo) ? htmlspecialchars($titulo) : 'Lavoro-ERP' ?></title>
    <!-- Bootstrap siempre primero -->
    <link rel="stylesheet" href="<?= asset_url('css/bootstrap.min.css') ?>">
    <!-- Tu CSS personalizado -->
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <!-- Choices y otros -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- CSS para el autocorrector -->
    <link rel="stylesheet" href="<?= asset_url('css/autocorrector.css?v=' . time()) ?>">
    <style>
        /* Fallback visual para bootstrap-select: ocultar el select nativo cuando se usa el plugin */
        select.selectpicker { display: none !important; }
        /* Asegurar que el botón generado por bootstrap-select ocupe ancho completo */
        .bootstrap-select .dropdown-toggle { width: 100% !important; }
        /* Estilo para campos autocompletados en modo readOnly: gris, no seleccionable */
        .auto-filled-readonly {
            background-color: #e9ecef !important;
            color: #6c757d !important;
            user-select: none !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
        }
    </style>
    <!-- Definir rutas como variables globales para JavaScript -->
    <script>
        window.BASE_URL = "<?= BASE_URL ?>"; // Para assets (CSS, JS, imágenes)
        window.APP_URL = "<?= APP_URL ?>";   // Para rutas de aplicación (AJAX, formularios)
        console.log('BASE_URL (assets):', window.BASE_URL);
        console.log('APP_URL (app):', window.APP_URL);
    </script>
</head>
<body>
    <?php 
        $isLogin = (isset($titulo) && strtolower($titulo) === 'login') || (isset($esLogin) && $esLogin);
        // Mejor detección del controlador actual SOLO dentro del menú lateral
    ?>
    <?php if (!$isLogin): ?>
    <header class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3" style="height:70px;">
        <div class="container-fluid p-0 d-flex align-items-center justify-content-between">
            <!-- Botón toggle sidebar -->
            <button id="toggleSidebar" class="btn btn-sm btn-outline-secondary me-3" title="Mostrar/Ocultar menú lateral" style="border:none; background:transparent;">
                <i class="bi bi-list" style="font-size:1.5rem;"></i>
            </button>
            <!-- Logo alineado visualmente al centro del panel lateral -->
            <a class="navbar-brand d-flex align-items-center" href="<?= app_url('home') ?>" style="margin-left:8px;"> <!-- Ajuste más fino -->
                <img src="<?= asset_url('img/Logo-Lavoro-1536x442.png') ?>" alt="Logo Lavoro" style="height:38px; width:auto; display:block;">
            </a>
            <!-- Menú usuario a la derecha - Versión simplificada con jQuery -->
            <div class="dropdown ms-auto">
                <a href="#" class="d-flex align-items-center text-primary fw-bold p-0" 
                   id="userDropdown" 
                   style="cursor:pointer; text-decoration:none;">
                    <i class="bi bi-person-circle me-2" style="font-size:1.3rem;"></i>
                    <?= htmlspecialchars(($_SESSION['user']['NombresApellidos'] ?? ($_SESSION['user']['username'] ?? ($_SESSION['user'] ?? 'Usuario')))) ?>
                    <i class="bi bi-chevron-down ms-2" style="font-size:0.8rem;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end mt-2" style="z-index:1050;">
                    <li><a class="dropdown-item text-dark" href="#"><i class="bi bi-person me-2"></i> <?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger fw-bold" href="<?= app_url('login/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </header>
    <div class="d-flex" style="min-height: 90vh;">
        <!-- Menú lateral -->
        <nav id="sidebar" class="sidebar bg-white border-end shadow-sm d-flex flex-column p-0 sidebar-expanded" style="width:240px; min-height:100vh; max-height:100vh; overflow-y:auto; background: #f8fafc; font-size:1.08rem; transition: width 0.3s ease, margin-left 0.3s ease;">
            <div class="sidebar-sticky pt-4">
                <?php
                // Inicializar $currentController justo antes del menú lateral
                $currentController = '';
                $currentAction = '';
                if (isset($GLOBALS['currentController']) && $GLOBALS['currentController']) {
                    $currentController = strtolower($GLOBALS['currentController']);
                    $currentAction = isset($GLOBALS['currentAction']) ? strtolower($GLOBALS['currentAction']) : '';
                } else if (isset($_GET['url'])) {
                    $parts = explode('/', trim($_GET['url'], '/'));
                    $currentController = strtolower($parts[0] ?? '');
                    $currentAction = strtolower($parts[1] ?? '');
                } else if (isset($_SERVER['REQUEST_URI'])) {
                    $uri = str_replace(BASE_URL, '', $_SERVER['REQUEST_URI']);
                    $uri = strtok($uri, '?');
                    $parts = explode('/', trim($uri, '/'));
                    $currentController = strtolower($parts[0] ?? '');
                    $currentAction = strtolower($parts[1] ?? '');
                }
                // echo "<!-- DEBUG: Controller=$currentController, Action=$currentAction -->";
                ?>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2 px-3 fw-bold" style="font-size:1.08rem; letter-spacing:0.5px; color:#26324b;">Módulos</li>
                    <!-- Almacén principal -->
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center sidebar-link" data-bs-toggle="collapse" href="#almacenMenu" role="button" aria-expanded="true" aria-controls="almacenMenu" style="font-weight:600; background:#e9eef6; border-radius:7px; font-size:1.05rem; color:#26324b;">
                            <i class="bi bi-boxes me-2"></i> Almacén <i class="bi bi-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse show" id="almacenMenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item mb-1 px-2 fw-bold small" style="color:#4b5563;">
                                    <a class="nav-link d-flex align-items-center sidebar-link" data-bs-toggle="collapse" href="#tablasMenu" role="button" aria-expanded="true" aria-controls="tablasMenu" style="padding-left:0; font-weight:600; background:transparent; font-size:1.02rem; color:#4b5563;">
                                        <i class="bi bi-table me-2"></i> Tablas <i class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <div class="collapse show" id="tablasMenu">
                                        <ul class="nav flex-column ms-3">
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'choferes' ? ' active' : '' ?>" href="<?= app_url('choferes') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'choferes' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-truck me-2"></i> Choferes</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'placas' ? ' active' : '' ?>" href="<?= app_url('placas') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'placas' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-car-front-fill me-2"></i> Placas</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'responsables' ? ' active' : '' ?>" href="<?= app_url('responsables') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'responsables' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-person-badge me-2"></i> Responsables</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'recepcionistas' ? ' active' : '' ?>" href="<?= app_url('recepcionistas') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'recepcionistas' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-person-vcard me-2"></i> Recepcionistas</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'areas' ? ' active' : '' ?>" href="<?= app_url('areas') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'areas' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-diagram-3 me-2"></i> Áreas</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'subareas' ? ' active' : '' ?>" href="<?= app_url('subareas') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'subareas' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-diagram-2 me-2"></i> Subáreas</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'productos' ? ' active' : '' ?>" href="<?= app_url('productos') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'productos' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-box-seam me-2"></i> Productos</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'transportistas' ? ' active' : '' ?>" href="<?= app_url('transportistas') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'transportistas' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-truck-front me-2"></i> Transportistas</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'clientesexternos' ? ' active' : '' ?>" href="<?= app_url('clientesexternos') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'clientesexternos' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-people me-2"></i> Clientes Externos</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'origen' ? ' active' : '' ?>" href="<?= app_url('origen') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'origen' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-geo-alt me-2"></i> Origen</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'observaciones' ? ' active' : '' ?>" href="<?= app_url('observaciones') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'observaciones' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-journal-text me-2"></i> Observaciones</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'series' ? ' active' : '' ?>" href="<?= app_url('series') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'series' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-list-ol me-2"></i> Series</a></li>
                                        </ul>
                                    </div>
                                </li>
                                <li class="nav-item mb-1 px-2 fw-bold small mt-3" style="color:#4b5563;">
                                    <a class="nav-link d-flex align-items-center sidebar-link" data-bs-toggle="collapse" href="#registrosMenu" role="button" aria-expanded="true" aria-controls="registrosMenu" style="padding-left:0; color:#4b5563; font-weight:600; background:transparent;">
                                        <i class="bi bi-journal me-2"></i> Registros <i class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <div class="collapse show" id="registrosMenu">
                                        <ul class="nav flex-column ms-3">
                                            <li class="nav-item">
                                                <a class="nav-link sidebar-link d-flex align-items-center justify-content-between<?= $currentController === 'despachosinternos' ? ' active' : '' ?>" data-bs-toggle="collapse" href="#despachosInternosSubmenu" role="button" aria-expanded="false" aria-controls="despachosInternosSubmenu" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'despachosinternos' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>">
                                                    <span><i class="bi bi-truck-flatbed me-2"></i> Despachos Internos</span>
                                                    <i class="bi bi-chevron-down" style="font-size:0.8rem;"></i>
                                                </a>
                                                <div class="collapse<?= $currentController === 'despachosinternos' ? ' show' : '' ?>" id="despachosInternosSubmenu">
                                                    <ul class="nav flex-column ms-4">
                                                        <li class="nav-item">
                                                            <?php $newActive = ($currentController === 'despachosinternos' && ($currentAction === '' || $currentAction === 'index')); ?>
                                                            <a class="nav-link sidebar-link<?= $newActive ? ' active' : '' ?>" href="<?= app_url('despachosinternos') ?>" style="font-size:0.92rem; color:#6b7280; <?= $newActive ? 'background:#e6f7ef; font-weight:700; color:#064e3b; border-radius:7px;' : 'background:#f0fdf4; border-radius:6px;' ?>">
                                                                <i class="bi bi-plus-circle me-2"></i> Nuevo Registro
                                                            </a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <?php $editActive = ($currentController === 'despachosinternos' && $currentAction === 'edicion'); ?>
                                                            <a class="nav-link sidebar-link<?= $editActive ? ' active' : '' ?>" href="<?= app_url('despachosinternos/edicion') ?>" style="font-size:0.92rem; color:#6b7280; <?= $editActive ? 'background:#e6f7ef; font-weight:700; color:#064e3b; border-radius:7px;' : 'background:#f0fdf4; border-radius:6px;' ?>">
                                                                <i class="bi bi-pencil-square me-2"></i> Edición de Vale
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link sidebar-link d-flex align-items-center justify-content-between<?= $currentController === 'despachosexternos' ? ' active' : '' ?>" data-bs-toggle="collapse" href="#despachosExternosSubmenu" role="button" aria-expanded="false" aria-controls="despachosExternosSubmenu" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'despachosexternos' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>">
                                                    <span><i class="bi bi-truck-front me-2"></i> Despachos Externos</span>
                                                    <i class="bi bi-chevron-down" style="font-size:0.8rem;"></i>
                                                </a>
                                                <div class="collapse<?= $currentController === 'despachosexternos' ? ' show' : '' ?>" id="despachosExternosSubmenu">
                                                    <ul class="nav flex-column ms-4">
                                                        <li class="nav-item">
                                                            <?php $newActive = ($currentController === 'despachosexternos' && ($currentAction === '' || $currentAction === 'index')); ?>
                                                            <a class="nav-link sidebar-link<?= $newActive ? ' active' : '' ?>" href="<?= app_url('despachosexternos') ?>" style="font-size:0.92rem; color:#6b7280; <?= $newActive ? 'background:#e6f7ef; font-weight:700; color:#064e3b; border-radius:7px;' : 'background:#f0fdf4; border-radius:6px;' ?>">
                                                                <i class="bi bi-plus-circle me-2"></i> Nuevo Registro
                                                            </a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <?php $editActive = ($currentController === 'despachosexternos' && $currentAction === 'edicion'); ?>
                                                            <a class="nav-link sidebar-link<?= $editActive ? ' active' : '' ?>" href="<?= app_url('despachosexternos/edicion') ?>" style="font-size:0.92rem; color:#6b7280; <?= $editActive ? 'background:#e6f7ef; font-weight:700; color:#064e3b; border-radius:7px;' : 'background:#f0fdf4; border-radius:6px;' ?>">
                                                                <i class="bi bi-pencil-square me-2"></i> Edición de Vale
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link sidebar-link d-flex align-items-center justify-content-between<?= $currentController === 'recepcionesinternas' ? ' active' : '' ?>" data-bs-toggle="collapse" href="#recepcionesInternasSubmenu" role="button" aria-expanded="false" aria-controls="recepcionesInternasSubmenu" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'recepcionesinternas' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>">
                                                    <span><i class="bi bi-inbox me-2"></i> Recepciones Internas</span>
                                                    <i class="bi bi-chevron-down" style="font-size:0.8rem;"></i>
                                                </a>
                                                <div class="collapse<?= $currentController === 'recepcionesinternas' ? ' show' : '' ?>" id="recepcionesInternasSubmenu">
                                                    <ul class="nav flex-column ms-4">
                                                        <li class="nav-item">
                                                            <?php $newActiveRI = ($currentController === 'recepcionesinternas' && ($currentAction === '' || $currentAction === 'index')); ?>
                                                            <a class="nav-link sidebar-link<?= $newActiveRI ? ' active' : '' ?>" href="<?= app_url('recepcionesinternas') ?>" style="font-size:0.92rem; color:#6b7280; <?= $newActiveRI ? 'background:#e6f7ef; font-weight:700; color:#064e3b; border-radius:7px;' : 'background:#f0fdf4; border-radius:6px;' ?>">
                                                                <i class="bi bi-file-earmark-plus me-2"></i> Nuevo Registro
                                                            </a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <?php $editActiveRI = ($currentController === 'recepcionesinternas' && $currentAction === 'edicion'); ?>
                                                            <a class="nav-link sidebar-link<?= $editActiveRI ? ' active' : '' ?>" href="<?= app_url('recepcionesinternas/edicion') ?>" style="font-size:0.92rem; color:#6b7280; <?= $editActiveRI ? 'background:#e6f7ef; font-weight:700; color:#064e3b; border-radius:7px;' : 'background:#f0fdf4; border-radius:6px;' ?>">
                                                                <i class="bi bi-pencil-square me-2"></i> Edición de Vale
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link sidebar-link d-flex align-items-center justify-content-between<?= $currentController === 'recepcionesexternas' ? ' active' : '' ?>" data-bs-toggle="collapse" href="#recepcionesExternasSubmenu" role="button" aria-expanded="false" aria-controls="recepcionesExternasSubmenu" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'recepcionesexternas' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>">
                                                    <span><i class="bi bi-box-arrow-in-right me-2"></i> Recepciones Externas</span>
                                                    <i class="bi bi-chevron-down" style="font-size:0.8rem;"></i>
                                                </a>
                                                <div class="collapse<?= $currentController === 'recepcionesexternas' ? ' show' : '' ?>" id="recepcionesExternasSubmenu">
                                                    <ul class="nav flex-column ms-4">
                                                        <li class="nav-item">
                                                            <?php $newActive = ($currentController === 'recepcionesexternas' && ($currentAction === '' || $currentAction === 'index')); ?>
                                                            <a class="nav-link sidebar-link<?= $newActive ? ' active' : '' ?>" href="<?= app_url('recepcionesexternas') ?>" style="font-size:0.92rem; color:#6b7280; <?= $newActive ? 'background:#e6f7ef; font-weight:700; color:#064e3b; border-radius:7px;' : 'background:#f0fdf4; border-radius:6px;' ?>">
                                                                <i class="bi bi-plus-circle me-2"></i> Nuevo Registro
                                                            </a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <?php $editActive = ($currentController === 'recepcionesexternas' && $currentAction === 'edicion'); ?>
                                                            <a class="nav-link sidebar-link<?= $editActive ? ' active' : '' ?>" href="<?= app_url('recepcionesexternas/edicion') ?>" style="font-size:0.92rem; color:#6b7280; <?= $editActive ? 'background:#e6f7ef; font-weight:700; color:#064e3b; border-radius:7px;' : 'background:#f0fdf4; border-radius:6px;' ?>">
                                                                <i class="bi bi-pencil-square me-2"></i> Edición de Vale
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                                <li class="nav-item mb-1 px-2 fw-bold small mt-3" style="color:#4b5563;">
                                    <a class="nav-link d-flex align-items-center sidebar-link" data-bs-toggle="collapse" href="#reportesMenu" role="button" aria-expanded="true" aria-controls="reportesMenu" style="padding-left:0; color:#4b5563; font-weight:600; background:transparent;">
                                        <i class="bi bi-bar-chart-line me-2"></i> Reportes <i class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <div class="collapse show" id="reportesMenu">
                                        <ul class="nav flex-column ms-3">
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= ($currentController === 'reportes' && $currentAction === 'despachosinternos') ? ' active' : '' ?>" href="<?= app_url('reportes/despachosinternos') ?>" style="font-size:0.98rem; color:#4b5563;<?= ($currentController === 'reportes' && $currentAction === 'despachosinternos') ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-bar-chart-line me-2"></i> Reporte Despachos Internos</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= ($currentController === 'reportes' && $currentAction === 'despachosexternos') ? ' active' : '' ?>" href="<?= app_url('reportes/despachosexternos') ?>" style="font-size:0.98rem; color:#4b5563;<?= ($currentController === 'reportes' && $currentAction === 'despachosexternos') ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-bar-chart-line me-2"></i> Reporte Despachos Externos</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= ($currentController === 'reportes' && $currentAction === 'recepcionesinternas') ? ' active' : '' ?>" href="<?= app_url('reportes/recepcionesinternas') ?>" style="font-size:0.98rem; color:#4b5563;<?= ($currentController === 'reportes' && $currentAction === 'recepcionesinternas') ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-bar-chart-line me-2"></i> Reporte Recepciones Internas</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= ($currentController === 'reportes' && $currentAction === 'recepcionesexternas' && (!isset($_GET['view']) || $_GET['view'] !== 'ext')) ? ' active' : '' ?>" href="<?= app_url('reportes/recepcionesexternas') ?>" style="font-size:0.98rem; color:#4b5563;<?= ($currentController === 'reportes' && $currentAction === 'recepcionesexternas' && (!isset($_GET['view']) || $_GET['view'] !== 'ext')) ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-bar-chart-line me-2"></i> Reporte Recepciones Externas</a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= ($currentController === 'reportes' && $currentAction === 'consolidado') ? ' active' : '' ?>" href="<?= app_url('reportes/consolidado') ?>" style="font-size:0.98rem; color:#2563eb;<?= ($currentController === 'reportes' && $currentAction === 'consolidado') ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-grid-3x3-gap-fill me-2"></i> <span style="color:#2563eb;">Reporte Consolidado</span></a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= ($currentController === 'reportes' && $currentAction === 'recepcionesexternas' && isset($_GET['view']) && $_GET['view'] === 'ext') ? ' active' : '' ?>" href="<?= app_url('reportes/recepcionesexternas') . '?view=ext' ?>" style="font-size:0.98rem; color:#2563eb;<?= ($currentController === 'reportes' && $currentAction === 'recepcionesexternas' && isset($_GET['view']) && $_GET['view'] === 'ext') ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-grid-3x3-gap-fill me-2"></i> <span style="color:#2563eb;">Reporte Recepciones Ext.</span></a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= ($currentController === 'reportes' && $currentAction === 'picking') ? ' active' : '' ?>" href="<?= app_url('reportes/picking') ?>" style="font-size:0.98rem; color:#2563eb;<?= ($currentController === 'reportes' && $currentAction === 'picking') ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-grid-3x3-gap-fill me-2"></i> <span style="color:#2563eb;">Picking</span></a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= ($currentController === 'reportes' && $currentAction === 'recepcionesexternasliquidadas') ? ' active' : '' ?>" href="<?= app_url('reportes/recepcionesexternasliquidadas') ?>" style="font-size:0.98rem; color:#2563eb;<?= ($currentController === 'reportes' && $currentAction === 'recepcionesexternasliquidadas') ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-grid-3x3-gap-fill me-2"></i> <span style="color:#2563eb;">Recepciones Externas Liquidadas</span></a></li>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= ($currentController === 'reportes' && $currentAction === 'jabas') ? ' active' : '' ?>" href="<?= app_url('reportes/jabas') ?>" style="font-size:0.98rem; color:#16a34a;<?= ($currentController === 'reportes' && $currentAction === 'jabas') ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-grid-3x3-gap-fill me-2"></i> <span style="color:#16a34a;">Reporte Jabas</span></a></li>
                                        </ul>
                                    </div>
                                </li>
                                <?php
                                $tieneKardex = isset($_SESSION['privilegios']) && in_array('ver_kardex_parihuelas', $_SESSION['privilegios']);
                                $tienePlanAbastecimiento = isset($_SESSION['privilegios']) && in_array('ver_plan_abastecimiento', $_SESSION['privilegios']);
                                $tieneAvanceDiario = isset($_SESSION['privilegios']) && in_array('ver_avance_diario', $_SESSION['privilegios']);
                                if ($tieneKardex || $tienePlanAbastecimiento || $tieneAvanceDiario):
                                ?>
                                <li class="nav-item mb-1 px-2 fw-bold small mt-3" style="color:#4b5563;">
                                    <a class="nav-link d-flex align-items-center sidebar-link" data-bs-toggle="collapse" href="#kardexMenu" role="button" aria-expanded="true" aria-controls="kardexMenu" style="padding-left:0; color:#4b5563; font-weight:600; background:transparent;">
                                        <i class="bi bi-clipboard-data me-2"></i> Kardex y Stock <i class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <div class="collapse show" id="kardexMenu">
                                        <ul class="nav flex-column ms-3">
                                            <?php if ($tieneKardex): ?>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'kardexparihuelas' ? ' active' : '' ?>" href="<?= app_url('kardexparihuelas') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'kardexparihuelas' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-box me-2"></i> Kardex Parihuelas</a></li>
                                            <?php endif; ?>
                                            <?php if ($tieneAvanceDiario): ?>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'avancediario' ? ' active' : '' ?>" href="<?= app_url('avancediario') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'avancediario' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-speedometer2 me-2"></i> Control de Stock de Racks y Parihuelas</a></li>
                                            <?php endif; ?>
                                            <?php if ($tienePlanAbastecimiento): ?>
                                            <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'planabastecimiento' ? ' active' : '' ?>" href="<?= app_url('planabastecimiento') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'planabastecimiento' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-calendar-check me-2"></i> Plan de Abastecimiento</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </li>
                                <?php endif; ?>
                                <li class="nav-item mb-1 px-2 fw-bold small mt-3" style="color:#4b5563;">
                                    <a class="nav-link d-flex align-items-center sidebar-link" data-bs-toggle="collapse" href="#mantenimientoMenu" role="button" aria-expanded="true" aria-controls="mantenimientoMenu" style="padding-left:0; color:#4b5563; font-weight:600; background:transparent;">
                                        <i class="bi bi-tools me-2"></i> Mantenimiento <i class="bi bi-chevron-down ms-auto"></i>
                                    </a>
                                    <div class="collapse show" id="mantenimientoMenu">
                                        <ul class="nav flex-column ms-3">
                                            <?php if (isset($_SESSION['privilegios']) && in_array('gestionar_roles', $_SESSION['privilegios'])): ?>
                                                <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'roles' ? ' active' : '' ?>" href="<?= app_url('roles') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'roles' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-shield-lock me-2"></i> Roles</a></li>
                                                <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'privilegios' ? ' active' : '' ?>" href="<?= app_url('privilegios') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'privilegios' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-key me-2"></i> Privilegios</a></li>
                                            <?php endif; ?>
                                            <?php if (isset($_SESSION['privilegios']) && in_array('ver_usuarios', $_SESSION['privilegios'])): ?>
                                                <li class="nav-item"><a class="nav-link sidebar-link<?= $currentController === 'usuarios' ? ' active' : '' ?>" href="<?= app_url('usuarios') ?>" style="font-size:0.98rem; color:#4b5563;<?= $currentController === 'usuarios' ? ' background:#e0e7ff; font-weight:700; border-radius:7px;' : '' ?>"><i class="bi bi-person-gear me-2"></i> Usuarios</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <!-- Sección Documentación (privilegio: ver_documentacion) -->
                    <?php if (isset($_SESSION['privilegios']) && in_array('ver_documentacion', $_SESSION['privilegios'])): ?>
                    <li class="nav-item mt-3 px-2">
                        <div class="fw-bold small px-1 mb-1" style="color:#b45309; letter-spacing:0.5px; font-size:0.95rem;">
                            <i class="bi bi-file-earmark-text-fill me-2"></i>Sistema
                        </div>
                        <ul class="nav flex-column ms-2">
                            <li class="nav-item">
                                <a class="nav-link sidebar-link<?= $currentController === 'documentacion' ? ' active' : '' ?>"
                                   href="<?= app_url('documentacion') ?>"
                                   style="font-size:0.97rem; color:#92400e;<?= $currentController === 'documentacion' ? ' background:#fef3c7; font-weight:700; border-radius:7px;' : '' ?>">
                                    <i class="bi bi-book me-2"></i> Documentación
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <!-- Sección Auditoría (solo si tiene privilegio ver_auditoria) -->
                    <?php if (isset($_SESSION['privilegios']) && in_array('ver_auditoria', $_SESSION['privilegios'])): ?>
                    <li class="nav-item mt-3 px-2">
                        <div class="fw-bold small px-1 mb-1" style="color:#7c3aed; letter-spacing:0.5px; font-size:0.95rem;">
                            <i class="bi bi-shield-lock-fill me-2"></i>Auditoría
                        </div>
                        <ul class="nav flex-column ms-2">
                            <li class="nav-item">
                                <a class="nav-link sidebar-link<?= $currentController === 'auditoria' ? ' active' : '' ?>"
                                   href="<?= app_url('auditoria') ?>"
                                   style="font-size:0.97rem; color:#5b21b6;<?= $currentController === 'auditoria' ? ' background:#ede9fe; font-weight:700; border-radius:7px;' : '' ?>">
                                    <i class="bi bi-clock-history me-2"></i> Bitácora de Auditoría
                                </a>
                            </li>
                            <?php if (in_array('ver_backups', $_SESSION['privilegios'])): ?>
                            <li class="nav-item">
                                <a class="nav-link sidebar-link<?= $currentController === 'backup' ? ' active' : '' ?>"
                                   href="<?= app_url('backup') ?>"
                                   style="font-size:0.97rem; color:#5b21b6;<?= $currentController === 'backup' ? ' background:#ede9fe; font-weight:700; border-radius:7px;' : '' ?>">
                                    <i class="bi bi-database-fill-down me-2"></i> Gestión de Backups
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
        <main class="flex-grow-1 p-4 bg-light" style="min-height:100vh; padding-bottom:0 !important;">
            <div style="margin-bottom: 0;">
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const menuIds = ['almacenMenu','tablasMenu','registrosMenu','reportesMenu','mantenimientoMenu'];
                menuIds.forEach(function(id) {
                    const el = document.getElementById(id);
                    if (!el) return;
                    const state = localStorage.getItem('menu_'+id);
                    if (state === 'show') {
                        el.classList.add('show');
                    } else if (state === 'hide') {
                        el.classList.remove('show');
                    }
                    el.addEventListener('show.bs.collapse', function() {
                        localStorage.setItem('menu_'+id, 'show');
                    });
                    el.addEventListener('hide.bs.collapse', function() {
                        localStorage.setItem('menu_'+id, 'hide');
                    });
                });
                document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        if (link.getAttribute('data-bs-toggle') === 'collapse') return;
                        menuIds.forEach(function(id) {
                            const el = document.getElementById(id);
                            if (!el) return;
                            localStorage.setItem('menu_'+id, el.classList.contains('show') ? 'show' : 'hide');
                        });
                    });
                });
            });
        </script>
            </div>
        </nav>
        <main class="flex-grow-1 p-4 bg-light" style="min-height:100vh; padding-bottom:0 !important;">
            <div style="margin-bottom: 0;">
    <?php else: ?>
        <main style="flex:1; padding: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
            <div style="width:100%;">
    <?php endif; ?>
                <?php if (isset($contenido)) echo $contenido; ?>
            </div>
        </main>
    <?php if (!$isLogin): ?>
        </div>
    <?php endif; ?>
    <?php if (empty($esLogin)): ?>
    <footer class="bg-white border-top text-center py-3 small text-secondary">
        &copy; 2025 Todos los derechos reservados. Desarrollado por &lt;IJE&gt;.
    </footer>
    <?php endif; ?>
    <style>
        .dropdown-menu.show { display: block; animation: fadeIn 0.2s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        #userDropdown:hover { color: #0d6efd !important; }
        .sidebar-link { border-radius: 7px; transition: background 0.18s, color 0.18s; color: #26324b; }
        .sidebar-link:hover, .sidebar-link.active { background: #dbeafe !important; color: #1e293b !important; font-weight: 600; }
        .sidebar { box-shadow: 2px 0 8px rgba(0,0,0,0.04); background: #f8fafc; }
        .sidebar .nav-link { color: #26324b; }
        .sidebar .nav-link.active { background: #dbeafe !important; color: #1e293b !important; }
        .sidebar .nav-link:focus { outline: none; box-shadow: 0 0 0 2px #60a5fa33; }
        .sidebar-sticky { max-height: calc(100vh - 70px); overflow-y: auto; }
        .sidebar-collapsed { width: 0 !important; min-width: 0 !important; margin-left: -240px !important; overflow: hidden !important; }
        .sidebar-expanded { width: 240px !important; margin-left: 0 !important; }
        #toggleSidebar { transition: transform 0.3s ease; }
        #toggleSidebar:hover { background: #f3f4f6 !important; transform: scale(1.1); }
        @media (max-width: 991px) { .sidebar { width: 100px !important; min-width: 70px !important; } .sidebar .nav-link span { display: none; } }
        @media (min-width: 992px) { .sidebar { width: 240px !important; min-width: 240px !important; } }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= asset_url('js/bootstrap.bundle.min.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
    <script src="<?= asset_url('js/main.js') ?>"></script>
    <script src="<?= asset_url('js/fix_dropdown_jquery.js?v=' . time()) ?>"></script>
    <script src="<?= asset_url('js/vale_print_professional.js?v=' . time()) ?>"></script>
    <script>
        (function() {
            const toggleBtn = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            if (!toggleBtn || !sidebar) return;
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'collapsed') { sidebar.classList.remove('sidebar-expanded'); sidebar.classList.add('sidebar-collapsed'); }
            toggleBtn.addEventListener('click', function() {
                if (sidebar.classList.contains('sidebar-collapsed')) { sidebar.classList.remove('sidebar-collapsed'); sidebar.classList.add('sidebar-expanded'); localStorage.setItem('sidebarState', 'expanded'); }
                else { sidebar.classList.remove('sidebar-expanded'); sidebar.classList.add('sidebar-collapsed'); localStorage.setItem('sidebarState', 'collapsed'); }
            });
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            try{ document.querySelectorAll('#grillaDespacho thead th:nth-child(3), #grillaDespacho thead th:nth-child(6)').forEach(function(el){ el.style.setProperty('text-align','center','important'); }); }catch(e){ console.error('forceHeaderAlign error', e); }
        });
    </script>
    <?php if (!$isLogin): ?>
    <div class="modal fade" id="modalInactividad" tabindex="-1" aria-labelledby="modalInactividadLabel" aria-modal="true" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark"><h5 class="modal-title fw-bold" id="modalInactividadLabel"><i class="bi bi-clock-history me-2"></i>Sesión por expirar</h5></div>
                <div class="modal-body py-4 text-center"><p class="mb-1">Su sesión cerrará automáticamente por inactividad en:</p><p class="display-4 fw-bold text-danger mb-1" id="cuentaRegresiva">30</p><p class="text-muted small">segundos</p></div>
                <div class="modal-footer justify-content-center"><button type="button" class="btn btn-success px-4" id="btnSeguirConectado"><i class="bi bi-check-circle me-2"></i>Continuar sesión</button></div>
            </div>
        </div>
    </div>
    <script>
    (function () {
        var TIMEOUT_MS = 20 * 60 * 1000;
        var WARNING_MS = 30 * 1000;
        var LOGOUT_URL = window.APP_URL + '/login/logout?timeout=1';
        var KEEPALIVE_URL = window.BASE_URL + '/api/keep_alive.php';
        var HEARTBEAT_MS = 10 * 60 * 1000;
        var warningTimer, logoutTimer, countdownInterval, heartbeatTimer;
        var lastHeartbeat = 0;
        var modalVisible = false;
        var modal = null;
        var modalEl = document.getElementById('modalInactividad');
        function getModal() { if (!modal && typeof bootstrap !== 'undefined') { modal = new bootstrap.Modal(modalEl); } return modal; }
        function startCountdown(seconds) { var remaining = seconds; document.getElementById('cuentaRegresiva').textContent = remaining; clearInterval(countdownInterval); countdownInterval = setInterval(function () { remaining--; var el = document.getElementById('cuentaRegresiva'); if (el) el.textContent = remaining; if (remaining <= 0) clearInterval(countdownInterval); }, 1000); }
        function showWarning() { modalVisible = true; var warningSeconds = Math.round(WARNING_MS / 1000); startCountdown(warningSeconds); var m = getModal(); if (m) m.show(); logoutTimer = setTimeout(function () { window.location.href = LOGOUT_URL; }, WARNING_MS); }
        function sendHeartbeat() { var now = Date.now(); if (now - lastHeartbeat < 60000) return; lastHeartbeat = now; fetch(KEEPALIVE_URL, { method: 'POST', credentials: 'same-origin' }).then(function (res) { if (res.status === 401) { window.location.href = LOGOUT_URL; } }).catch(function () {}); }
        function resetTimers() { if (modalVisible) return; clearTimeout(warningTimer); clearTimeout(logoutTimer); clearInterval(countdownInterval); warningTimer = setTimeout(showWarning, TIMEOUT_MS - WARNING_MS); sendHeartbeat(); }
        function keepAlive() { fetch(KEEPALIVE_URL, { method: 'POST', credentials: 'same-origin' }).then(function (res) { if (res.status === 401) { window.location.href = LOGOUT_URL; } }).catch(function () {}); modalVisible = false; clearTimeout(warningTimer); clearTimeout(logoutTimer); clearInterval(countdownInterval); var m = getModal(); if (m) m.hide(); warningTimer = setTimeout(showWarning, TIMEOUT_MS - WARNING_MS); }
        document.getElementById('btnSeguirConectado').addEventListener('click', keepAlive);
        ['mousemove','keydown','mousedown','scroll','touchstart','click'].forEach(function (evt) { document.addEventListener(evt, resetTimers, { passive: true }); });
        heartbeatTimer = setInterval(function() { if (!modalVisible) { sendHeartbeat(); } }, HEARTBEAT_MS);
        resetTimers();
    })();
    </script>
    <?php endif; ?>
</body>
</html>
