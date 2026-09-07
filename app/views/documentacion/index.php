<?php
/**
 * Vista: Documentación del Sistema
 * Solo accesible para administradores (privilegio: gestionar_roles)
 * El botón PDF solo se muestra en entorno local (WAMP) porque Dompdf
 * no está disponible en hosting. En hosting solo se muestra "Ver documento".
 */
?>
<div class="container-fluid px-0">
    <div class="d-flex align-items-center mb-4 gap-3">
        <div style="background: linear-gradient(135deg, #1a3a5c, #2c5282); border-radius: 12px; padding: 12px 18px;">
            <i class="bi bi-file-earmark-text-fill text-white" style="font-size: 1.6rem;"></i>
        </div>
        <div>
            <h4 class="mb-0 fw-bold" style="color: #1a3a5c;">Documentación del Sistema</h4>
            <small class="text-muted">Lavoro-ERP &mdash; Documentos técnicos y de usuario</small>
        </div>
    </div>

    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" style="border-radius: 10px; font-size: 0.95rem;">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <span>Esta sección está disponible <strong>solo para administradores</strong>. Los documentos se abren en una nueva pestaña y pueden guardarse como PDF o Word desde el navegador.</span>
    </div>

    <div class="row g-4">

        <!-- Documento 1 -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #1a3a5c 0%, #2c5282 100%); padding: 24px 20px 18px;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.75rem; letter-spacing: 1px;">DOC 01</span>
                    </div>
                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.05rem; line-height: 1.4;">
                        Diseño del Sistema de Información
                    </h5>
                    <small style="color: rgba(255,255,255,0.75);">Lavoro-ERP &mdash; Módulos de Recepciones y Despachos</small>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-3" style="font-size: 0.9rem; color: #4b5563;">
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Descripción del negocio y contexto</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Actores, roles y requerimientos (RF/RNF)</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Casos de uso y diagramas</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Modelo ER conceptual</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Flujos de proceso e interfaz</li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="<?= app_url('documentacion/ver/01_diseno_sistema_informacion') ?>"
                           target="_blank"
                           class="btn btn-primary btn-sm flex-fill"
                           style="background: #1a3a5c; border-color: #1a3a5c; border-radius: 8px;">
                            <i class="bi bi-eye me-1"></i> Ver documento
                        </a>
                        <?php if (!empty($esLocal)): ?>
                        <a href="<?= app_url('documentacion/descargarPDF/01_diseno_sistema_informacion') ?>"
                           class="btn btn-outline-secondary btn-sm"
                           style="border-radius: 8px; white-space: nowrap;"
                           title="Descargar PDF (solo disponible en local)">
                            <i class="bi bi-filetype-pdf me-1"></i> PDF
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0" style="font-size: 0.8rem; color: #9ca3af;">
                    <i class="bi bi-calendar3 me-1"></i> Versión 1.0 &mdash; Abril 2026
                </div>
            </div>
        </div>

        <!-- Documento 2 -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #064e3b 0%, #065f46 100%); padding: 24px 20px 18px;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.75rem; letter-spacing: 1px;">DOC 02</span>
                    </div>
                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.05rem; line-height: 1.4;">
                        Modelo de Software
                    </h5>
                    <small style="color: rgba(255,255,255,0.75);">Arquitectura técnica del sistema</small>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-3" style="font-size: 0.9rem; color: #4b5563;">
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Stack tecnológico y arquitectura MVC</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Diagrama de clases y componentes</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Modelo ER físico de la base de datos</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>API interna y seguridad (RBAC)</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Integración Factiliza y módulo backup</li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="<?= app_url('documentacion/ver/02_modelo_software') ?>"
                           target="_blank"
                           class="btn btn-sm flex-fill"
                           style="background: #064e3b; border-color: #064e3b; color: white; border-radius: 8px;">
                            <i class="bi bi-eye me-1"></i> Ver documento
                        </a>
                        <?php if (!empty($esLocal)): ?>
                        <a href="<?= app_url('documentacion/descargarPDF/02_modelo_software') ?>"
                           class="btn btn-outline-secondary btn-sm"
                           style="border-radius: 8px; white-space: nowrap;"
                           title="Descargar PDF (solo disponible en local)">
                            <i class="bi bi-filetype-pdf me-1"></i> PDF
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0" style="font-size: 0.8rem; color: #9ca3af;">
                    <i class="bi bi-calendar3 me-1"></i> Versión 1.0 &mdash; Abril 2026
                </div>
            </div>
        </div>

        <!-- Documento 3 -->
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #7c2d12 0%, #92400e 100%); padding: 24px 20px 18px;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.75rem; letter-spacing: 1px;">DOC 03</span>
                    </div>
                    <h5 class="text-white fw-bold mb-1" style="font-size: 1.05rem; line-height: 1.4;">
                        Manual de Usuario
                    </h5>
                    <small style="color: rgba(255,255,255,0.75);">Guía completa de operación del sistema</small>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-3" style="font-size: 0.9rem; color: #4b5563;">
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Acceso, navegación y sesión</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Módulos VRI, VDI, VRE y VDE paso a paso</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Reportes y exportación a Excel</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Administración de usuarios y roles</li>
                        <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>FAQ y glosario</li>
                    </ul>
                    <div class="d-flex gap-2">
                        <a href="<?= app_url('documentacion/ver/03_manual_usuario') ?>"
                           target="_blank"
                           class="btn btn-sm flex-fill"
                           style="background: #7c2d12; border-color: #7c2d12; color: white; border-radius: 8px;">
                            <i class="bi bi-eye me-1"></i> Ver documento
                        </a>
                        <?php if (!empty($esLocal)): ?>
                        <a href="<?= app_url('documentacion/descargarPDF/03_manual_usuario') ?>"
                           class="btn btn-outline-secondary btn-sm"
                           style="border-radius: 8px; white-space: nowrap;"
                           title="Descargar PDF (solo disponible en local)">
                            <i class="bi bi-filetype-pdf me-1"></i> PDF
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0" style="font-size: 0.8rem; color: #9ca3af;">
                    <i class="bi bi-calendar3 me-1"></i> Versión 1.0 &mdash; Abril 2026
                </div>
            </div>
        </div>

    </div>

    <?php if (!empty($esLocal)): ?>
    <div class="mt-4 p-3 rounded-3 d-flex align-items-center gap-3" style="background: #f8fafc; border: 1px solid #e2e8f0; font-size: 0.88rem; color: #64748b;">
        <i class="bi bi-lightbulb-fill text-warning fs-5"></i>
        <span>
            <strong>Tip:</strong> Use el botón <strong>PDF</strong> para descargar el documento directamente.
            También puede abrir el documento y usar <kbd>Ctrl+P</kbd> → <em>Guardar como PDF</em>.
        </span>
    </div>
    <?php endif; ?>
</div>
