<?php
/**
 * Generador del Informe de Cumplimiento BASC — Lavoro ERP
 * Uso: php generar_informe_basc.php
 */

$outputPath     = '/home/jparra/Escritorio/OLA-GTI-IF-Informe_Cumplimiento_BASC_LavoroERP.docx';
$logoLavoroPath = '/var/www/html/swlavoro/public/img/Logo-Lavoro-1536x442.png';
$logoIJEPath    = '/var/www/html/swlavoro/public/img/logoije.png';
$today          = date('d/m/Y');
$todayFull      = date('d') . ' de ' . mesNombre(date('n')) . ' de ' . date('Y');

function mesNombre(string $n): string {
    return ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][(int)$n];
}

// Logos — dimensiones en EMU (1 cm = 360000 EMU)
// Lavoro: 1536×442 px → mostrar 8 cm ancho
$lavoroCx = 2880000;
$lavoroCy = (int)($lavoroCx * (442 / 1536));   // 828750

// IJE: 911×575 px → mostrar 3.2 cm ancho en firma
$ijeCx = 1152000;
$ijeCy = (int)($ijeCx * (575 / 911));           // 726783

// ── Helpers ───────────────────────────────────────────────────────────────────

function x(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function pEmpty(): string {
    return '<w:p><w:pPr><w:spacing w:after="80"/></w:pPr></w:p>';
}

function p(string $t, bool $bold=false, int $sz=20, string $col='', string $align='left'): string {
    $rpr = "<w:rFonts w:ascii=\"Calibri\" w:hAnsi=\"Calibri\"/><w:sz w:val=\"{$sz}\"/><w:szCs w:val=\"{$sz}\"/>";
    if ($bold) $rpr .= '<w:b/>';
    if ($col)  $rpr .= "<w:color w:val=\"{$col}\"/>";
    return "<w:p><w:pPr><w:jc w:val=\"{$align}\"/><w:spacing w:after=\"80\"/></w:pPr>"
         . "<w:r><w:rPr>{$rpr}</w:rPr><w:t xml:space=\"preserve\">".x($t)."</w:t></w:r></w:p>";
}

function imgXml(string $rId, int $cx, int $cy, int $id, string $name): string {
    return '<w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">
      <wp:extent cx="'.$cx.'" cy="'.$cy.'"/>
      <wp:effectExtent l="0" t="0" r="0" b="0"/>
      <wp:docPr id="'.$id.'" name="'.x($name).'"/>
      <wp:cNvGraphicFramePr>
        <a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/>
      </wp:cNvGraphicFramePr>
      <a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
        <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
          <pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
            <pic:nvPicPr>
              <pic:cNvPr id="'.$id.'" name="'.x($name).'"/>
              <pic:cNvPicPr><a:picLocks noChangeAspect="1" noChangeArrowheads="1"/></pic:cNvPicPr>
            </pic:nvPicPr>
            <pic:blipFill>
              <a:blip r:embed="'.$rId.'"/>
              <a:stretch><a:fillRect/></a:stretch>
            </pic:blipFill>
            <pic:spPr bwMode="auto">
              <a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm>
              <a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/>
            </pic:spPr>
          </pic:pic>
        </a:graphicData>
      </a:graphic>
    </wp:inline></w:drawing>';
}

function pImg(string $rId, int $cx, int $cy, int $id, string $name, string $align='center'): string {
    return '<w:p><w:pPr><w:jc w:val="'.$align.'"/><w:spacing w:after="0"/></w:pPr>'
         . '<w:r>'.imgXml($rId,$cx,$cy,$id,$name).'</w:r></w:p>';
}

function tc(string $txt, string $bg='', bool $bold=false, string $tc='', int $span=0, bool $center=false): string {
    $bdr = '<w:tcBorders><w:top w:val="single" w:sz="4" w:color="BFBFBF"/><w:left w:val="single" w:sz="4" w:color="BFBFBF"/><w:bottom w:val="single" w:sz="4" w:color="BFBFBF"/><w:right w:val="single" w:sz="4" w:color="BFBFBF"/></w:tcBorders>';
    $mar = '<w:tcMar><w:top w:w="80" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tcMar>';
    $shd = $bg ? "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bg}\"/>" : '';
    $spn = $span>1 ? "<w:gridSpan w:val=\"{$span}\"/>" : '';
    $rpr = '<w:sz w:val="18"/><w:szCs w:val="18"/>';
    if ($bold) $rpr .= '<w:b/>';
    if ($tc)   $rpr .= "<w:color w:val=\"{$tc}\"/>";
    $jc = $center ? '<w:jc w:val="center"/>' : '';
    return "<w:tc><w:tcPr>{$spn}{$shd}{$bdr}{$mar}</w:tcPr>"
         . "<w:p><w:pPr>{$jc}<w:spacing w:after=\"60\"/></w:pPr>"
         . "<w:r><w:rPr>{$rpr}</w:rPr><w:t xml:space=\"preserve\">".x($txt)."</w:t></w:r></w:p></w:tc>";
}

function trH(array $cols): string {
    $c = ''; foreach ($cols as $v) $c .= tc($v,'1F3864',true,'FFFFFF');
    return "<w:tr>{$c}</w:tr>";
}

function trR(string $n, string $req, string $est, string $ev): string {
    $bg = $est==='✅ CUMPLE' ? 'E2EFDA' : 'FFF2CC';
    $cl = $est==='✅ CUMPLE' ? '375623' : '7F4A00';
    return "<w:tr>".tc($n,'',false,'',0,true).tc($req).tc($est,$bg,true,$cl,0,true).tc($ev)."</w:tr>";
}

function tbl4(string $rows): string {
    return '<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/></w:tblPr>
    <w:tblGrid><w:gridCol w:w="360"/><w:gridCol w:w="3200"/><w:gridCol w:w="1700"/><w:gridCol w:w="4100"/></w:tblGrid>'
    .$rows.'</w:tbl>';
}

function sep(): string {
    return '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
          .'<w:r><w:rPr><w:color w:val="1F3864"/><w:sz w:val="8"/></w:rPr>'
          .'<w:t>▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬</w:t></w:r></w:p>';
}

// ── document.xml ──────────────────────────────────────────────────────────────
$doc = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document
  xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
  xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
  xmlns:o="urn:schemas-microsoft-com:office:office"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
  xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
  xmlns:v="urn:schemas-microsoft-com:vml"
  xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"
  xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
  xmlns:w10="urn:schemas-microsoft-com:office:word"
  xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
  xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"
  xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"
  xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"
  xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"
  xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"
  mc:Ignorable="w14 wp14">
<w:body>';

// Logo Lavoro — cabecera
$doc .= pImg('rIdLavoro', $lavoroCx, $lavoroCy, 1, 'Logo Lavoro');
$doc .= pEmpty();
$doc .= sep();

// Código + títulos
$doc .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="40"/></w:pPr>'
      . '<w:r><w:rPr><w:b/><w:color w:val="1F3864"/><w:sz w:val="18"/></w:rPr>'
      . '<w:t>OLA-GTI-IF-001  |  INFORME DE CUMPLIMIENTO BASC</w:t></w:r></w:p>';
$doc .= pEmpty();
$doc .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
      . '<w:r><w:rPr><w:b/><w:color w:val="1F3864"/><w:sz w:val="40"/><w:szCs w:val="40"/></w:rPr>'
      . '<w:t>Lavoro ERP</w:t></w:r></w:p>';
$doc .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
      . '<w:r><w:rPr><w:b/><w:color w:val="2E75B6"/><w:sz w:val="26"/></w:rPr>'
      . '<w:t>Informe de Cumplimiento de Requerimientos Técnicos</w:t></w:r></w:p>';
$doc .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
      . '<w:r><w:rPr><w:b/><w:color w:val="C00000"/><w:sz w:val="24"/></w:rPr>'
      . '<w:t>Estándar Internacional BASC</w:t></w:r></w:p>';
$doc .= pEmpty();

// Ficha
$ficha = [
    ['Código del Documento',   'OLA-GTI-IF-001'],
    ['Versión',                 '1.0'],
    ['Fecha de Elaboración',    $todayFull],
    ['Sistema Evaluado',        'Lavoro ERP — Control de Parihuelas y Almacén'],
    ['Elaborado Por',           'Jeimmy Parra Anchante — Ingeniero de Sistemas e Informática'],
    ['Empresa Proveedora',      'IJE Soluciones Tecnológicas'],
    ['Cliente',                 'Lavoro S.A.C.'],
    ['Documento de Referencia', 'OLA-GTI-IF-Requerimientos Estándar para Sistemas Externos (Basado en BASC)'],
    ['Revisado Por',            'Omar Joya Rojas — Jefe de Infraestructura TI'],
];
$doc .= '<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/>
  <w:tblBorders>
    <w:top w:val="single" w:sz="6" w:color="2E75B6"/><w:left w:val="single" w:sz="6" w:color="2E75B6"/>
    <w:bottom w:val="single" w:sz="6" w:color="2E75B6"/><w:right w:val="single" w:sz="6" w:color="2E75B6"/>
    <w:insideH w:val="single" w:sz="4" w:color="BDD7EE"/><w:insideV w:val="single" w:sz="4" w:color="BDD7EE"/>
  </w:tblBorders></w:tblPr>
  <w:tblGrid><w:gridCol w:w="2340"/><w:gridCol w:w="7020"/></w:tblGrid>';
foreach ($ficha as $r) {
    $mar = '<w:tcMar><w:top w:w="80" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="80" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tcMar>';
    $doc .= '<w:tr>
      <w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="D6E4F0"/>'.$mar.'</w:tcPr>
        <w:p><w:pPr><w:spacing w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="18"/><w:color w:val="1F3864"/></w:rPr><w:t xml:space="preserve">'.x($r[0]).'</w:t></w:r></w:p>
      </w:tc>
      <w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="EBF3FB"/>'.$mar.'</w:tcPr>
        <w:p><w:pPr><w:spacing w:after="60"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">'.x($r[1]).'</w:t></w:r></w:p>
      </w:tc>
    </w:tr>';
}
$doc .= '</w:tbl>';
$doc .= pEmpty();
$doc .= sep();

// Salto de página
$doc .= '<w:p><w:pPr><w:pageBreakBefore/></w:pPr></w:p>';

// ── S1 OBJETIVO ───────────────────────────────────────────────────────────────
$doc .= p('1. OBJETIVO Y ALCANCE', true, 26, '1F3864');
$doc .= pEmpty();
$doc .= p('El presente informe tiene como objetivo demostrar el cumplimiento técnico del sistema Lavoro ERP frente a los requerimientos mínimos establecidos por el área de TI de Lavoro S.A.C., conforme al estándar internacional BASC (Business Alliance for Secure Commerce), tal como se detalla en el documento de referencia OLA-GTI-IF-Requerimientos Estándar para Sistemas Externos.');
$doc .= pEmpty();
$doc .= p('El sistema evaluado es una aplicación web de gestión de almacén (control de parihuelas, despachos internos y externos, recepciones internas y externas), desarrollada a medida por IJE Soluciones Tecnológicas para Lavoro S.A.C.');
$doc .= pEmpty();

// ── S2 RESUMEN ────────────────────────────────────────────────────────────────
$doc .= p('2. RESUMEN EJECUTIVO DE CUMPLIMIENTO', true, 26, '1F3864');
$doc .= pEmpty();
$doc .= p('El sistema Lavoro ERP cumple con la totalidad de los requerimientos técnicos obligatorios establecidos en el estándar BASC:');
$doc .= pEmpty();
$doc .= '<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/></w:tblPr>
  <w:tblGrid><w:gridCol w:w="4680"/><w:gridCol w:w="2340"/><w:gridCol w:w="2340"/></w:tblGrid>
  '.trH(['Área de Control','Estado','Req. Cumplidos']).'
  <w:tr>'.tc('Control de Acceso y Gestión de Identidades').tc('✅ CUMPLE','E2EFDA',true,'375623',0,true).tc('3 / 3','E2EFDA',false,'375623',0,true).'</w:tr>
  <w:tr>'.tc('Trazabilidad y Log de Auditoría').tc('✅ CUMPLE','E2EFDA',true,'375623',0,true).tc('4 / 4','E2EFDA',false,'375623',0,true).'</w:tr>
  <w:tr>'.tc('Seguridad de los Datos').tc('✅ CUMPLE','E2EFDA',true,'375623',0,true).tc('2 / 2','E2EFDA',false,'375623',0,true).'</w:tr>
  <w:tr>'.tc('Política de Respaldo de Base de Datos').tc('✅ CUMPLE','E2EFDA',true,'375623',0,true).tc('3 / 3','E2EFDA',false,'375623',0,true).'</w:tr>
  <w:tr>'.tc('Seguridad Física y Lógica del Proveedor').tc('✅ CUMPLE','E2EFDA',true,'375623',0,true).tc('1 / 1','E2EFDA',false,'375623',0,true).'</w:tr>
</w:tbl>';
$doc .= pEmpty();
$doc .= p('TOTAL: 13 de 13 requerimientos cumplidos (100%)', true, 22, '375623', 'center');
$doc .= pEmpty();

// ── S3 CONTROL DE ACCESO ──────────────────────────────────────────────────────
$doc .= p('3. CONTROL DE ACCESO Y GESTIÓN DE IDENTIDADES', true, 26, '1F3864');
$doc .= pEmpty();
$doc .= p('El sistema implementa un esquema de control de acceso basado en roles (RBAC). Cada usuario posee credenciales únicas e intransferibles, y el acceso a cada módulo es controlado por privilegios individuales asignados a roles.');
$doc .= pEmpty();
$doc .= tbl4(
    trH(['N°','Requerimiento BASC','Estado','Evidencia técnica']).
    trR('1','Uso obligatorio de perfiles de usuario únicos (prohibido cuentas genéricas)','✅ CUMPLE','Tabla usuarios con campo username único (índice UNIQUE en BD). Cada operación registra el UsuarioId responsable.').
    trR('2','Políticas de contraseña robusta: mínimo 8 caracteres, alfanuméricos y caracteres especiales','✅ CUMPLE','Validación backend en UsuariosController::validarPassword() y validación frontend con minlength="8". Hash bcrypt PASSWORD_DEFAULT (costo 12).').
    trR('3','Cierre de sesión automático por inactividad','✅ CUMPLE','SESSION_INACTIVITY_TIMEOUT = 1200 seg. (20 min) en public/index.php. Redirige al login con aviso automático.')
);
$doc .= pEmpty();

// ── S4 TRAZABILIDAD ───────────────────────────────────────────────────────────
$doc .= p('4. TRAZABILIDAD Y LOG DE AUDITORÍA', true, 26, '1F3864');
$doc .= pEmpty();
$doc .= p('El sistema registra todas las transacciones de los 4 módulos operativos (despachos internos/externos, recepciones internas/externas) a través de tablas de auditoría inmutables.');
$doc .= pEmpty();
$doc .= tbl4(
    trH(['N°','Requerimiento BASC','Estado','Evidencia técnica']).
    trR('1','Registrar cada transacción identificando usuario, fecha y hora','✅ CUMPLE','Tablas *_modificaciones con campos UsuarioId y ModificadoEn. Tablas principales con creado_por y creado_en (timestamp automático).').
    trR('2','Registro de dirección IP por operación','✅ CUMPLE','Columna IpAddress en las 4 tablas *_modificaciones. Columna ip_creacion en las 4 tablas principales. Captura vía $_SERVER[\'REMOTE_ADDR\'].').
    trR('3','Registros inalterables','✅ CUMPLE','Solo operaciones INSERT en tablas de auditoría. No existe endpoint de edición ni borrado del log. Datos inmutables por diseño.').
    trR('4','Bitácora accesible para inspección de seguridad','✅ CUMPLE','Módulo Bitácora de Auditoría (privilegio ver_auditoria). Filtros por módulo, acción, usuario, fechas y N° vale. Exportación a Excel .xlsx.')
);
$doc .= pEmpty();

// ── S5 SEGURIDAD DATOS ────────────────────────────────────────────────────────
$doc .= p('5. SEGURIDAD DE LOS DATOS', true, 26, '1F3864');
$doc .= pEmpty();
$doc .= tbl4(
    trH(['N°','Requerimiento BASC','Estado','Evidencia técnica']).
    trR('1','Conexión cifrada HTTPS / SSL TLS 1.2 o superior','✅ CUMPLE','Hosting provee certificado SSL/TLS activo. Todas las peticiones HTTP son redirigidas automáticamente a HTTPS vía .htaccess.').
    trR('2','Contraseñas de usuarios cifradas en la base de datos','✅ CUMPLE','Hash bcrypt mediante password_hash($pass, PASSWORD_DEFAULT) en PHP. Las contraseñas nunca se almacenan ni transmiten en texto plano.')
);
$doc .= pEmpty();

// ── S6 RESPALDOS ──────────────────────────────────────────────────────────────
$doc .= p('6. POLÍTICA DE RESPALDO DE BASE DE DATOS', true, 26, '1F3864');
$doc .= pEmpty();
$doc .= p('El sistema cuenta con un módulo de gestión de respaldos integrado (BackupService), que genera volcados SQL comprimidos (.sql.gz) mediante exportación PDO nativa, sin dependencia de herramientas externas del servidor. Compatible con hosting compartido, WAMP y VPS.');
$doc .= pEmpty();
$doc .= '<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/></w:tblPr>
  <w:tblGrid><w:gridCol w:w="1750"/><w:gridCol w:w="1600"/><w:gridCol w:w="1400"/><w:gridCol w:w="2310"/><w:gridCol w:w="2300"/></w:tblGrid>
  '.trH(['Tipo','Frecuencia','Retención','Propósito','Estado']).'
  <w:tr>'.tc('Respaldo Diario').tc('Mín. 1 vez / 24 h','',false,'',0,true).tc('30 días','',false,'',0,true).tc('Recuperación ante errores operativos').tc('✅ CUMPLE','E2EFDA',true,'375623',0,true).'</w:tr>
  <w:tr>'.tc('Respaldo Mensual').tc('1 vez / mes','',false,'',0,true).tc('12 meses','',false,'',0,true).tc('Cumplimiento BASC y trazabilidad histórica').tc('✅ CUMPLE','E2EFDA',true,'375623',0,true).'</w:tr>
  <w:tr>'.tc('Respaldo Crítico').tc('Cada 6 horas','',false,'',0,true).tc('30 días','',false,'',0,true).tc('Alta transaccionalidad — parihuelas').tc('✅ CUMPLE','E2EFDA',true,'375623',0,true).'</w:tr>
</w:tbl>';
$doc .= pEmpty();
$doc .= p('Los respaldos se ejecutan automáticamente mediante cron jobs configurados en el panel de control del hosting. La retención se gestiona automáticamente eliminando los archivos más antiguos al superar el límite. La descarga está protegida por autenticación y restringida al privilegio ver_backups.');
$doc .= pEmpty();

// ── S7 SEGURIDAD FÍSICA ───────────────────────────────────────────────────────
$doc .= p('7. SEGURIDAD FÍSICA Y LÓGICA DEL PROVEEDOR DE HOSTING', true, 26, '1F3864');
$doc .= pEmpty();
$doc .= tbl4(
    trH(['N°','Requerimiento BASC','Estado','Evidencia técnica']).
    trR('1','El hosting/servidor cuenta con controles de acceso restringido y firewalls activos','✅ CUMPLE','Sistema alojado en hosting con firewall perimetral, acceso SSH restringido y panel cPanel con autenticación. Certificación disponible bajo requerimiento formal al proveedor de hosting (ver Anexos).')
);
$doc .= pEmpty();

// ── S8 DOCUMENTACIÓN DISPONIBLE ───────────────────────────────────────────────
$doc .= p('8. DOCUMENTACIÓN DE RESPALDO DISPONIBLE', true, 26, '1F3864');
$doc .= pEmpty();
$doc .= p('Los siguientes documentos y evidencias se encuentran disponibles para inspección por parte del área de Infraestructura TI ante cualquier auditoría BASC:');
$doc .= pEmpty();
$doc .= '<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/></w:tblPr>
  <w:tblGrid><w:gridCol w:w="360"/><w:gridCol w:w="3800"/><w:gridCol w:w="5200"/></w:tblGrid>
  '.trH(['N°','Documento','Descripción']).'
  <w:tr>'.tc('A1','',false,'',0,true).tc('Código fuente — módulo BackupService').tc('Lógica de generación, retención y gestión de respaldos comprimidos.').'</w:tr>
  <w:tr>'.tc('A2','',false,'',0,true).tc('Código fuente — módulo Auditoría').tc('Tablas *_modificaciones, captura de IP y módulo de consulta con filtros.').'</w:tr>
  <w:tr>'.tc('A3','',false,'',0,true).tc('Panel cPanel del hosting').tc('Evidencia de firewall activo, cron jobs configurados y certificado SSL. Disponible bajo coordinación con el proveedor de hosting.').'</w:tr>
  <w:tr>'.tc('A4','',false,'',0,true).tc('Capturas de pantalla del sistema').tc('Módulo de Auditoría, formularios con indicadores de seguridad de contraseña y panel de respaldos.').'</w:tr>
  <w:tr>'.tc('A5','',false,'',0,true).tc('Certificado SSL/TLS del dominio').tc('Constancia emitida por la autoridad certificadora. Disponible bajo solicitud formal al proveedor.').'</w:tr>
</w:tbl>';
$doc .= pEmpty();

// ── S9 CONCLUSIÓN ─────────────────────────────────────────────────────────────
$doc .= p('9. CONCLUSIÓN', true, 26, '1F3864');
$doc .= pEmpty();
$doc .= p('El sistema Lavoro ERP, en su versión actual, cumple íntegramente con los 13 requerimientos técnicos mínimos establecidos por el área de TI de Lavoro S.A.C. conforme al estándar internacional BASC. La implementación cubre los ejes de control de acceso, trazabilidad, cifrado, política de respaldo y seguridad lógica del hosting.');
$doc .= pEmpty();
$doc .= p('Se recomienda mantener una revisión semestral de este informe e incorporar nuevas medidas a medida que el estándar BASC las exija.');
$doc .= pEmpty();

// ── FIRMAS ────────────────────────────────────────────────────────────────────
$doc .= sep();
$doc .= pEmpty();
$doc .= p('FIRMAS DE CONFORMIDAD', true, 24, '1F3864', 'center');
$doc .= pEmpty();

$bdrFirma = '<w:tblBorders>
  <w:top w:val="single" w:sz="4" w:color="BDD7EE"/>
  <w:bottom w:val="single" w:sz="4" w:color="BDD7EE"/>
  <w:insideH w:val="single" w:sz="4" w:color="BDD7EE"/>
  <w:left w:val="none"/><w:right w:val="none"/><w:insideV w:val="none"/>
</w:tblBorders>';

$doc .= '<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/>'.$bdrFirma.'</w:tblPr>
  <w:tblGrid><w:gridCol w:w="4500"/><w:gridCol w:w="360"/><w:gridCol w:w="4500"/></w:tblGrid>';

// Logo IJE encima de la firma izquierda
$doc .= '<w:tr>
  <w:tc>
    <w:p><w:pPr><w:jc w:val="left"/><w:spacing w:after="40"/></w:pPr>'
    .'<w:r>'.imgXml('rIdIJE',$ijeCx,$ijeCy,2,'Logo IJE').'</w:r></w:p>
  </w:tc>
  <w:tc><w:p><w:r><w:t/></w:r></w:p></w:tc>
  <w:tc><w:p><w:pPr><w:spacing w:after="40"/></w:pPr>'
  .'<w:r><w:rPr><w:b/><w:sz w:val="18"/><w:color w:val="1F3864"/></w:rPr><w:t>REVISADO POR</w:t></w:r></w:p></w:tc>
</w:tr>';

$doc .= '<w:tr>
  <w:tc><w:p><w:r><w:rPr><w:b/><w:sz w:val="18"/><w:color w:val="1F3864"/></w:rPr><w:t>ELABORADO POR</w:t></w:r></w:p></w:tc>
  <w:tc><w:p><w:r><w:t/></w:r></w:p></w:tc>
  <w:tc><w:p><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">Firma: _______________________</w:t></w:r></w:p></w:tc>
</w:tr>';

$doc .= '<w:tr>
  <w:tc><w:p><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">Firma: _______________________</w:t></w:r></w:p></w:tc>
  <w:tc><w:p><w:r><w:t/></w:r></w:p></w:tc>
  <w:tc><w:p><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t>Omar Joya Rojas</w:t></w:r></w:p></w:tc>
</w:tr>';

$firma = [
    ['Jeimmy Parra Anchante',              'Jefe de Infraestructura TI'],
    ['Ingeniero de Sistemas e Informática', 'Lavoro S.A.C.'],
    ['IJE Soluciones Tecnológicas',         'Fecha: ___/___/______'],
    ['Fecha: '.$today,                      ''],
];
foreach ($firma as $r) {
    $doc .= '<w:tr>
      <w:tc><w:p><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">'.x($r[0]).'</w:t></w:r></w:p></w:tc>
      <w:tc><w:p><w:r><w:t/></w:r></w:p></w:tc>
      <w:tc><w:p><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">'.x($r[1]).'</w:t></w:r></w:p></w:tc>
    </w:tr>';
}

$doc .= '</w:tbl>';

$doc .= '<w:sectPr>
    <w:pgSz w:w="12240" w:h="15840"/>
    <w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="709" w:footer="709" w:gutter="0"/>
</w:sectPr>';
$doc .= '</w:body></w:document>';

// ── Estilos ───────────────────────────────────────────────────────────────────
$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault><w:rPr>
      <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>
      <w:sz w:val="20"/><w:szCs w:val="20"/><w:lang w:val="es-PE"/>
    </w:rPr></w:rPrDefault>
    <w:pPrDefault><w:pPr><w:spacing w:after="120"/></w:pPr></w:pPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:styleId="Normal" w:default="1">
    <w:name w:val="Normal"/>
    <w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="20"/></w:rPr>
  </w:style>
  <w:style w:type="table" w:styleId="TableGrid">
    <w:name w:val="Table Grid"/>
    <w:tblPr>
      <w:tblBorders>
        <w:top w:val="single" w:sz="4" w:color="BFBFBF"/>
        <w:left w:val="single" w:sz="4" w:color="BFBFBF"/>
        <w:bottom w:val="single" w:sz="4" w:color="BFBFBF"/>
        <w:right w:val="single" w:sz="4" w:color="BFBFBF"/>
        <w:insideH w:val="single" w:sz="4" w:color="BFBFBF"/>
        <w:insideV w:val="single" w:sz="4" w:color="BFBFBF"/>
      </w:tblBorders>
    </w:tblPr>
  </w:style>
</w:styles>';

// ── Ensamblar ZIP ─────────────────────────────────────────────────────────────
$zip = new ZipArchive();
if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("❌ No se pudo crear: {$outputPath}\n");
}

$zip->addFromString('[Content_Types].xml',
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Default Extension="png"  ContentType="image/png"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml"   ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>');

$zip->addFromString('_rels/.rels',
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>');

$zip->addFromString('word/_rels/document.xml.rels',
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"      Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rIdLavoro" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"  Target="media/logo_lavoro.png"/>
  <Relationship Id="rIdIJE"    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"  Target="media/logo_ije.png"/>
</Relationships>');

$zip->addFromString('word/document.xml', $doc);
$zip->addFromString('word/styles.xml',   $styles);
$zip->addFile($logoLavoroPath, 'word/media/logo_lavoro.png');
$zip->addFile($logoIJEPath,    'word/media/logo_ije.png');
$zip->close();

$size = round(filesize($outputPath)/1024, 1);
echo "✅ Informe generado: {$outputPath}\n";
echo "   Tamaño: {$size} KB\n";
echo "   Logo Lavoro: {$lavoroCx}×{$lavoroCy} EMU\n";
echo "   Logo IJE:    {$ijeCx}×{$ijeCy} EMU\n";
