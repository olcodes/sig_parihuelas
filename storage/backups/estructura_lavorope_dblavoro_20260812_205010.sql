-- ============================================================
-- ESTRUCTURA DE BASE DE DATOS (SIN DATOS)
-- Generado por: Lavoro-ERP - Script de entrega al cliente
-- Base de datos: `lavorope_dblavoro`
-- Fecha: 2026-08-12 20:50:11 (Hora Perú UTC-5)
-- ============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='-05:00' */;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- ----------------------------------------------------------
-- CREACIÓN DE LA BASE DE DATOS
-- ----------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `lavorope_dblavoro`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE `lavorope_dblavoro`;

-- ============================================================
-- TABLAS (42)
-- ============================================================

-- ----------------------------------------------------------
-- Tabla: `areas`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `areas`;
CREATE TABLE `areas` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Area` varchar(100) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `avance_diario`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `avance_diario`;
CREATE TABLE `avance_diario` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Fecha` date NOT NULL,
  `HoraAvance` varchar(5) NOT NULL,
  `Turno` varchar(50) DEFAULT '',
  `Repliegues` decimal(10,2) DEFAULT 0.00,
  `RecepcionesExternas` decimal(10,2) DEFAULT 0.00,
  `ComprasUsadas` decimal(10,2) DEFAULT 0.00,
  `ComprasNuevas` decimal(10,2) DEFAULT 0.00,
  `TotalRecepcion` decimal(10,2) DEFAULT 0.00,
  `EanUsadasDespInternos` decimal(10,2) DEFAULT 0.00,
  `EanUsadasDespExternos` decimal(10,2) DEFAULT 0.00,
  `EanComprasUsadasDespInternos` decimal(10,2) DEFAULT 0.00,
  `EanComprasUsadasDespExternos` decimal(10,2) DEFAULT 0.00,
  `EanComprasNuevasDespInternos` decimal(10,2) DEFAULT 0.00,
  `EanComprasNuevasDespExternos` decimal(10,2) DEFAULT 0.00,
  `TotalDespacho` decimal(10,2) DEFAULT 0.00,
  `QPlanificada` decimal(10,2) DEFAULT 0.00,
  `PorcentajeCumplimiento` decimal(5,2) DEFAULT 0.00,
  `StockComprasNuevas` decimal(10,2) DEFAULT 0.00,
  `StockComprasUsadas` decimal(10,2) DEFAULT 0.00,
  `StockFlujoRegular` decimal(10,2) DEFAULT 0.00,
  `TotalStock` decimal(10,2) DEFAULT 0.00,
  `estado` varchar(20) DEFAULT 'activo',
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `modificado_por` int(11) DEFAULT NULL,
  `modificado_en` datetime DEFAULT NULL,
  `modificaciones_count` int(11) DEFAULT 0,
  PRIMARY KEY (`Id`),
  KEY `idx_fecha_hora` (`Fecha`,`HoraAvance`),
  KEY `idx_fecha` (`Fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=820 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `avance_diario_modificaciones`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `avance_diario_modificaciones`;
CREATE TABLE `avance_diario_modificaciones` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `AvanceDiarioId` int(11) NOT NULL,
  `UsuarioId` int(11) NOT NULL,
  `NModificacion` int(11) NOT NULL,
  `IpAddress` varchar(45) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `AvanceDiarioId` (`AvanceDiarioId`),
  CONSTRAINT `avance_diario_modificaciones_ibfk_1` FOREIGN KEY (`AvanceDiarioId`) REFERENCES `avance_diario` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `choferes`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `choferes`;
CREATE TABLE `choferes` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `DocIdentidad` varchar(45) NOT NULL,
  `Nacionalidad` varchar(45) NOT NULL,
  `ApellidosPaterno` varchar(45) NOT NULL,
  `ApellidoMaterno` varchar(45) NOT NULL,
  `Nombres` varchar(45) NOT NULL,
  `ApellidosNombres` varchar(45) NOT NULL,
  `Brevete` varchar(45) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=1588 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `clientesexternos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `clientesexternos`;
CREATE TABLE `clientesexternos` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `RUC` varchar(11) NOT NULL,
  `Empresa` varchar(255) NOT NULL,
  `Direccion` varchar(200) NOT NULL,
  `Direccion2` varchar(200) DEFAULT NULL,
  `Direccion3` varchar(200) DEFAULT NULL,
  `Direccion4` varchar(200) DEFAULT NULL,
  `Direccion5` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `despachos_externos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `despachos_externos`;
CREATE TABLE `despachos_externos` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NVale` int(10) unsigned NOT NULL,
  `Fecha` date NOT NULL,
  `Hora` time NOT NULL,
  `Turno` int(10) unsigned NOT NULL,
  `Destino` varchar(200) NOT NULL,
  `RUC` varchar(11) NOT NULL,
  `Direccion` varchar(400) NOT NULL,
  `Despachador` int(10) unsigned NOT NULL,
  `Chofer` int(11) NOT NULL,
  `Licencia` varchar(45) NOT NULL,
  `Transportista` int(11) NOT NULL,
  `RUC_Transportista` varchar(11) NOT NULL,
  `Placa_Tracto` varchar(45) NOT NULL,
  `Constancia_Inscripcion` varchar(45) NOT NULL,
  `Placa_Carreta` varchar(45) NOT NULL,
  `Constancia_Inscripcion_2` varchar(45) NOT NULL,
  `GR` varchar(45) DEFAULT NULL,
  `NLiquidacion` varchar(50) DEFAULT NULL,
  `creado_por` int(10) unsigned NOT NULL,
  `ip_creacion` varchar(45) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `anulado_por` int(10) unsigned DEFAULT NULL,
  `anulado_en` datetime DEFAULT NULL,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','anulado') NOT NULL DEFAULT 'activo',
  `modificaciones_count` int(11) NOT NULL DEFAULT 0,
  `Dia` varchar(45) DEFAULT NULL,
  `Semana` varchar(45) DEFAULT NULL,
  `Mes` varchar(45) DEFAULT NULL,
  `TipoDespacho` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `Turno` (`Turno`),
  KEY `Despachador` (`Despachador`),
  KEY `creado_por` (`creado_por`),
  KEY `anulado_por` (`anulado_por`),
  CONSTRAINT `despachos_externos_ibfk_1` FOREIGN KEY (`Turno`) REFERENCES `turnos` (`Id`),
  CONSTRAINT `despachos_externos_ibfk_5` FOREIGN KEY (`Despachador`) REFERENCES `responsables` (`Id`),
  CONSTRAINT `despachos_externos_ibfk_8` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`Id`),
  CONSTRAINT `despachos_externos_ibfk_9` FOREIGN KEY (`anulado_por`) REFERENCES `usuarios` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=812 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- Tabla: `despachos_externos_modificaciones`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `despachos_externos_modificaciones`;
CREATE TABLE `despachos_externos_modificaciones` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `DespachoId` int(10) NOT NULL,
  `UsuarioId` int(10) DEFAULT NULL,
  `NModificacion` int(11) NOT NULL,
  `IpAddress` varchar(45) DEFAULT NULL,
  `ModificadoEn` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------
-- Tabla: `despachos_externos_productos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `despachos_externos_productos`;
CREATE TABLE `despachos_externos_productos` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DespachoId` int(10) unsigned NOT NULL,
  `CodigoProducto` varchar(50) NOT NULL,
  `DescripcionProducto` varchar(255) NOT NULL,
  `UnidadMedida` varchar(45) NOT NULL,
  `Cantidad` int(11) NOT NULL,
  `Comentarios` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `idx_despacho` (`DespachoId`),
  KEY `idx_codigo` (`CodigoProducto`),
  CONSTRAINT `despachos_externos_productos_ibfk_1` FOREIGN KEY (`DespachoId`) REFERENCES `despachos_externos` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=852 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `despachos_internos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `despachos_internos`;
CREATE TABLE `despachos_internos` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NVale` int(10) unsigned NOT NULL,
  `Fecha` date NOT NULL,
  `Hora` time NOT NULL,
  `Turno` int(10) unsigned NOT NULL,
  `Area` int(10) unsigned NOT NULL,
  `Subarea` int(10) unsigned NOT NULL,
  `Emisor` int(10) unsigned NOT NULL,
  `Despachador` int(10) unsigned NOT NULL,
  `Recepcionista` int(10) unsigned NOT NULL,
  `Verificador` int(10) unsigned NOT NULL,
  `NLiquidacion` varchar(50) DEFAULT NULL,
  `creado_por` int(10) unsigned NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_creacion` varchar(45) DEFAULT NULL,
  `anulado_por` int(10) unsigned DEFAULT NULL,
  `anulado_en` datetime DEFAULT NULL,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','anulado') NOT NULL DEFAULT 'activo',
  `modificaciones_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Contador de modificaciones realizadas al vale (máximo 3)',
  `Dia` varchar(45) DEFAULT NULL,
  `Semana` varchar(45) DEFAULT NULL,
  `Mes` varchar(45) DEFAULT NULL,
  `TipoDespacho` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `Turno` (`Turno`),
  KEY `Area` (`Area`),
  KEY `Subarea` (`Subarea`),
  KEY `Emisor` (`Emisor`),
  KEY `Despachador` (`Despachador`),
  KEY `Recepcionista` (`Recepcionista`),
  KEY `Verificador` (`Verificador`),
  KEY `creado_por` (`creado_por`),
  KEY `anulado_por` (`anulado_por`),
  CONSTRAINT `despachos_internos_ibfk_1` FOREIGN KEY (`Turno`) REFERENCES `turnos` (`Id`),
  CONSTRAINT `despachos_internos_ibfk_2` FOREIGN KEY (`Area`) REFERENCES `areas` (`Id`),
  CONSTRAINT `despachos_internos_ibfk_3` FOREIGN KEY (`Subarea`) REFERENCES `subareas` (`Id`),
  CONSTRAINT `despachos_internos_ibfk_4` FOREIGN KEY (`Emisor`) REFERENCES `usuarios` (`Id`),
  CONSTRAINT `despachos_internos_ibfk_5` FOREIGN KEY (`Despachador`) REFERENCES `responsables` (`Id`),
  CONSTRAINT `despachos_internos_ibfk_6` FOREIGN KEY (`Recepcionista`) REFERENCES `recepcionistas` (`Id`),
  CONSTRAINT `despachos_internos_ibfk_7` FOREIGN KEY (`Verificador`) REFERENCES `responsables` (`Id`),
  CONSTRAINT `despachos_internos_ibfk_8` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`Id`),
  CONSTRAINT `despachos_internos_ibfk_9` FOREIGN KEY (`anulado_por`) REFERENCES `usuarios` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=5999 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- Tabla: `despachos_internos_modificaciones`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `despachos_internos_modificaciones`;
CREATE TABLE `despachos_internos_modificaciones` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DespachoId` int(10) unsigned NOT NULL,
  `UsuarioId` int(10) NOT NULL,
  `NModificacion` int(11) NOT NULL,
  `IpAddress` varchar(45) DEFAULT NULL,
  `ModificadoEn` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `idx_despacho` (`DespachoId`),
  CONSTRAINT `fk_dim_despacho` FOREIGN KEY (`DespachoId`) REFERENCES `despachos_internos` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- Tabla: `despachos_internos_productos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `despachos_internos_productos`;
CREATE TABLE `despachos_internos_productos` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DespachoId` int(10) unsigned NOT NULL,
  `CodigoProducto` varchar(50) NOT NULL,
  `DescripcionProducto` varchar(255) NOT NULL,
  `UnidadMedida` varchar(45) NOT NULL,
  `Cantidad` int(11) NOT NULL,
  `Comentarios` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `idx_despacho` (`DespachoId`),
  KEY `idx_codigo` (`CodigoProducto`),
  CONSTRAINT `despachos_internos_productos_ibfk_1` FOREIGN KEY (`DespachoId`) REFERENCES `despachos_internos` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7295 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `kardex_parihuelas`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `kardex_parihuelas`;
CREATE TABLE `kardex_parihuelas` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Fecha` date NOT NULL,
  `Turno` int(10) unsigned NOT NULL,
  `estado` varchar(20) DEFAULT 'activo',
  `si_asperjadas` decimal(10,2) DEFAULT 0.00,
  `si_aptas` decimal(10,2) DEFAULT 0.00,
  `si_danadas` decimal(10,2) DEFAULT 0.00,
  `si_sucias` decimal(10,2) DEFAULT 0.00,
  `si_por_seleccionar` decimal(10,2) DEFAULT 0.00,
  `si_lavadas` decimal(10,2) DEFAULT 0.00,
  `si_secas` decimal(10,2) DEFAULT 0.00,
  `si_total` decimal(10,2) DEFAULT 0.00,
  `total_recepcionado` decimal(10,2) DEFAULT 0.00,
  `total_despachado` decimal(10,2) DEFAULT 0.00,
  `total_parihuelas_lavadas` decimal(10,2) DEFAULT 0.00,
  `clasif_aptas` decimal(10,2) DEFAULT 0.00,
  `clasif_danadas` decimal(10,2) DEFAULT 0.00,
  `clasif_relavado` decimal(10,2) DEFAULT 0.00,
  `clasif_total` decimal(10,2) DEFAULT 0.00,
  `reparadas_aptas` decimal(10,2) DEFAULT 0.00,
  `reparadas_sucias` decimal(10,2) DEFAULT 0.00,
  `reparadas_total` decimal(10,2) DEFAULT 0.00,
  `clasificadas_aptas` decimal(10,2) DEFAULT 0.00,
  `clasificadas_danadas` decimal(10,2) DEFAULT 0.00,
  `clasificadas_sucias` decimal(10,2) DEFAULT 0.00,
  `clasificadas_total` decimal(10,2) DEFAULT 0.00,
  `reseleccion` decimal(10,2) DEFAULT 0.00,
  `reparacion` decimal(10,2) DEFAULT 0.00,
  `asperjadas_turno` decimal(10,2) DEFAULT 0.00,
  `despacho_asperjadas` decimal(10,2) DEFAULT 0.00,
  `autoservicios` decimal(10,2) DEFAULT 0.00,
  `observadas` decimal(10,2) DEFAULT 0.00,
  `sf_asperjadas` decimal(10,2) DEFAULT 0.00,
  `sf_aptas` decimal(10,2) DEFAULT 0.00,
  `sf_danadas` decimal(10,2) DEFAULT 0.00,
  `sf_sucias` decimal(10,2) DEFAULT 0.00,
  `sf_por_seleccionar` decimal(10,2) DEFAULT 0.00,
  `sf_lavadas_secadas` decimal(10,2) DEFAULT 0.00,
  `sf_total` decimal(10,2) DEFAULT 0.00,
  `ajuste_manual_sf_total` int(11) DEFAULT 0 COMMENT 'Ajuste manual interno - reemplaza sf_total al guardar si > 0',
  `nota` text DEFAULT NULL,
  `modificaciones_count` int(11) DEFAULT 0,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `modificado_por` int(11) DEFAULT NULL,
  `modificado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uk_fecha_turno` (`Fecha`,`Turno`),
  KEY `idx_fecha_turno` (`Fecha`,`Turno`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `kardex_parihuelas_despachos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `kardex_parihuelas_despachos`;
CREATE TABLE `kardex_parihuelas_despachos` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `KardexId` int(11) NOT NULL,
  `item` int(11) DEFAULT 0,
  `subarea_id` int(10) unsigned NOT NULL DEFAULT 0,
  `subarea_nombre` varchar(255) DEFAULT '',
  `tratadas` decimal(10,2) DEFAULT 0.00,
  `especiales` decimal(10,2) DEFAULT 0.00,
  `total_despachado` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`Id`),
  KEY `KardexId` (`KardexId`),
  CONSTRAINT `kardex_parihuelas_despachos_ibfk_1` FOREIGN KEY (`KardexId`) REFERENCES `kardex_parihuelas` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=387 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `kardex_parihuelas_modificaciones`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `kardex_parihuelas_modificaciones`;
CREATE TABLE `kardex_parihuelas_modificaciones` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `KardexId` int(11) NOT NULL,
  `UsuarioId` int(11) NOT NULL,
  `NModificacion` int(11) NOT NULL DEFAULT 0,
  `IpAddress` varchar(45) DEFAULT NULL,
  `CreadoEn` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `idx_kardex_mod` (`KardexId`),
  CONSTRAINT `kardex_parihuelas_modificaciones_ibfk_1` FOREIGN KEY (`KardexId`) REFERENCES `kardex_parihuelas` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `kardex_parihuelas_recepciones`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `kardex_parihuelas_recepciones`;
CREATE TABLE `kardex_parihuelas_recepciones` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `KardexId` int(11) NOT NULL,
  `item` int(11) DEFAULT 0,
  `subarea_id` int(10) unsigned NOT NULL DEFAULT 0,
  `subarea_nombre` varchar(255) DEFAULT '',
  `aptas` decimal(10,2) DEFAULT 0.00,
  `danadas` decimal(10,2) DEFAULT 0.00,
  `sucias` decimal(10,2) DEFAULT 0.00,
  `por_seleccionar` decimal(10,2) DEFAULT 0.00,
  `total_recepcionado` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`Id`),
  KEY `KardexId` (`KardexId`),
  CONSTRAINT `kardex_parihuelas_recepciones_ibfk_1` FOREIGN KEY (`KardexId`) REFERENCES `kardex_parihuelas` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=516 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `medio_transporte`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `medio_transporte`;
CREATE TABLE `medio_transporte` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `MedioTransporte` varchar(45) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `observaciones`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `observaciones`;
CREATE TABLE `observaciones` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Item` varchar(45) NOT NULL,
  `Observaciones` varchar(100) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `origen`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `origen`;
CREATE TABLE `origen` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Origen` varchar(45) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `placas`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `placas`;
CREATE TABLE `placas` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Placa` varchar(20) NOT NULL,
  `TipoPlaca` varchar(32) DEFAULT NULL,
  `ConstanciaInscripcion` varchar(50) DEFAULT NULL,
  `FechaCreacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `FechaModificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_placa` (`Placa`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `plan_abastecimiento`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `plan_abastecimiento`;
CREATE TABLE `plan_abastecimiento` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Fecha` date NOT NULL,
  `QTotalPlanificada` decimal(10,2) DEFAULT 0.00,
  `estado` varchar(20) DEFAULT 'activo',
  `modificaciones_count` int(11) DEFAULT 0,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `modificado_por` int(11) DEFAULT NULL,
  `modificado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `idx_fecha` (`Fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `plan_abastecimiento_modificaciones`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `plan_abastecimiento_modificaciones`;
CREATE TABLE `plan_abastecimiento_modificaciones` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `PlanAbastecimientoId` int(11) NOT NULL,
  `UsuarioId` int(11) NOT NULL,
  `NModificacion` int(11) NOT NULL DEFAULT 0,
  `IpAddress` varchar(45) DEFAULT NULL,
  `CreadoEn` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  KEY `idx_plan_mod` (`PlanAbastecimientoId`),
  CONSTRAINT `plan_abastecimiento_modificaciones_ibfk_1` FOREIGN KEY (`PlanAbastecimientoId`) REFERENCES `plan_abastecimiento` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `privilegios`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `privilegios`;
CREATE TABLE `privilegios` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) NOT NULL,
  `Descripcion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Nombre` (`Nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------
-- Tabla: `productos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Codigo` varchar(45) NOT NULL,
  `Producto` varchar(200) NOT NULL,
  `UnidadMedida` varchar(45) NOT NULL,
  `Abreviatura` varchar(45) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `recepciones_externas`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `recepciones_externas`;
CREATE TABLE `recepciones_externas` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `NVale` varchar(50) NOT NULL COMMENT 'Número correlativo del vale (ej: VRE-000001)',
  `Fecha` date NOT NULL,
  `Hora` time NOT NULL,
  `Turno` int(10) NOT NULL COMMENT 'FK a tabla turnos',
  `Origen` int(10) NOT NULL COMMENT 'FK a tabla origenes',
  `Recepcionista` int(11) DEFAULT NULL,
  `NLiquidacion` varchar(100) DEFAULT NULL,
  `TipoRecepcion` varchar(100) DEFAULT 'RECEPCIÓN EXTERNA',
  `Empresa` int(10) NOT NULL COMMENT 'FK a tabla transportistas (empresa)',
  `RUC` varchar(11) NOT NULL COMMENT 'RUC del transportista',
  `Chofer` int(10) NOT NULL COMMENT 'FK a tabla choferes',
  `Brevete` varchar(20) NOT NULL COMMENT 'Número de brevete del chofer',
  `creado_por` int(10) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `ip_creacion` varchar(45) DEFAULT NULL,
  `anulado_por` int(10) DEFAULT NULL,
  `anulado_en` datetime DEFAULT NULL,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','anulado') NOT NULL DEFAULT 'activo',
  `Comentarios` text DEFAULT NULL,
  `Observaciones` text DEFAULT NULL,
  `modificaciones_count` int(11) NOT NULL DEFAULT 0,
  `Dia` varchar(45) DEFAULT NULL,
  `Semana` varchar(45) DEFAULT NULL,
  `Mes` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `NVale_UNIQUE` (`NVale`),
  KEY `idx_fecha` (`Fecha`),
  KEY `idx_turno` (`Turno`),
  KEY `idx_origen` (`Origen`),
  KEY `idx_chofer` (`Chofer`),
  KEY `idx_estado` (`estado`),
  KEY `idx_empresa` (`Empresa`),
  KEY `idx_recepcionista` (`Recepcionista`)
) ENGINE=InnoDB AUTO_INCREMENT=25472 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tabla padre: almacena datos principales de vales de recepción externa';

-- ----------------------------------------------------------
-- Tabla: `recepciones_externas_guias`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `recepciones_externas_guias`;
CREATE TABLE `recepciones_externas_guias` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `RecepcionExternaId` int(10) NOT NULL COMMENT 'FK a recepciones_externas',
  `NumeroGuia` varchar(50) NOT NULL COMMENT 'Número de guía de remisión',
  `NumeroDocRef` varchar(50) DEFAULT NULL COMMENT 'Número de Documento de Referencia',
  `Observacion` int(10) DEFAULT NULL COMMENT 'FK a tabla observaciones',
  `CodigoProductoObs` varchar(50) DEFAULT NULL COMMENT 'Código del producto al que aplica la observación',
  `CantidadObservada` decimal(11,2) DEFAULT 0.00 COMMENT 'Cantidad observada en esta guía',
  `Orden` int(3) NOT NULL DEFAULT 1 COMMENT 'Orden de presentación de la guía en la grilla',
  `TextoObservaciones` text DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `idx_recepcion` (`RecepcionExternaId`),
  KEY `idx_observacion` (`Observacion`),
  KEY `idx_numero_guia` (`NumeroGuia`),
  CONSTRAINT `fk_guias_recepcion` FOREIGN KEY (`RecepcionExternaId`) REFERENCES `recepciones_externas` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=90475 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tabla intermedia: almacena guías de remisión por vale';

-- ----------------------------------------------------------
-- Tabla: `recepciones_externas_modificaciones`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `recepciones_externas_modificaciones`;
CREATE TABLE `recepciones_externas_modificaciones` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `RecepcionId` int(10) NOT NULL,
  `UsuarioId` int(10) DEFAULT NULL,
  `NModificacion` int(11) NOT NULL,
  `IpAddress` varchar(45) DEFAULT NULL,
  `ModificadoEn` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------
-- Tabla: `recepciones_externas_productos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `recepciones_externas_productos`;
CREATE TABLE `recepciones_externas_productos` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `GuiaId` int(10) NOT NULL COMMENT 'FK a recepciones_externas_guias',
  `CodigoProducto` varchar(50) NOT NULL COMMENT 'Código del producto',
  `DescripcionProducto` varchar(255) NOT NULL COMMENT 'Descripción del producto',
  `UnidadMedida` varchar(45) NOT NULL COMMENT 'Unidad de medida del producto',
  `Cantidad` decimal(11,2) NOT NULL DEFAULT 0.00 COMMENT 'Cantidad recibida',
  `ColumnaProducto` int(3) NOT NULL DEFAULT 1 COMMENT 'Número de columna del producto en la grilla',
  `Observacion` int(10) DEFAULT NULL COMMENT 'FK a tabla observaciones',
  `TextoObservaciones` text DEFAULT NULL,
  `CantidadObservada` decimal(11,2) DEFAULT 0.00 COMMENT 'Cantidad observada del producto',
  `Total` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`Id`),
  KEY `idx_guia` (`GuiaId`),
  KEY `idx_codigo_producto` (`CodigoProducto`),
  KEY `idx_guia_producto` (`GuiaId`,`CodigoProducto`),
  CONSTRAINT `fk_productos_guia` FOREIGN KEY (`GuiaId`) REFERENCES `recepciones_externas_guias` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=130928 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tabla hija: almacena productos y cantidades por guía';

-- ----------------------------------------------------------
-- Tabla: `recepciones_internas`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `recepciones_internas`;
CREATE TABLE `recepciones_internas` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NVale` int(10) unsigned NOT NULL,
  `Fecha` date NOT NULL,
  `Hora` time NOT NULL,
  `Turno` int(10) unsigned NOT NULL,
  `Area` int(10) unsigned NOT NULL,
  `Subarea` int(10) unsigned NOT NULL,
  `Emisor` int(10) unsigned NOT NULL,
  `Despachador` int(10) unsigned NOT NULL,
  `MedioTransporte` int(11) NOT NULL,
  `Verificador` int(10) unsigned NOT NULL,
  `NLiquidacion` varchar(50) DEFAULT NULL,
  `creado_por` int(10) unsigned NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_creacion` varchar(45) DEFAULT NULL,
  `anulado_por` int(10) unsigned DEFAULT NULL,
  `anulado_en` datetime DEFAULT NULL,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','anulado') NOT NULL DEFAULT 'activo',
  `modificaciones_count` int(11) NOT NULL DEFAULT 0,
  `Dia` varchar(45) DEFAULT NULL,
  `Semana` varchar(45) DEFAULT NULL,
  `Mes` varchar(45) DEFAULT NULL,
  `TipoRecepcion` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `Turno` (`Turno`),
  KEY `Area` (`Area`),
  KEY `Subarea` (`Subarea`),
  KEY `Emisor` (`Emisor`),
  KEY `Despachador` (`Despachador`),
  KEY `Verificador` (`Verificador`),
  KEY `creado_por` (`creado_por`),
  KEY `anulado_por` (`anulado_por`),
  CONSTRAINT `recepciones_internas_ibfk_1` FOREIGN KEY (`Turno`) REFERENCES `turnos` (`Id`),
  CONSTRAINT `recepciones_internas_ibfk_2` FOREIGN KEY (`Area`) REFERENCES `areas` (`Id`),
  CONSTRAINT `recepciones_internas_ibfk_3` FOREIGN KEY (`Subarea`) REFERENCES `subareas` (`Id`),
  CONSTRAINT `recepciones_internas_ibfk_4` FOREIGN KEY (`Emisor`) REFERENCES `usuarios` (`Id`),
  CONSTRAINT `recepciones_internas_ibfk_5` FOREIGN KEY (`Despachador`) REFERENCES `responsables` (`Id`),
  CONSTRAINT `recepciones_internas_ibfk_7` FOREIGN KEY (`Verificador`) REFERENCES `responsables` (`Id`),
  CONSTRAINT `recepciones_internas_ibfk_8` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`Id`),
  CONSTRAINT `recepciones_internas_ibfk_9` FOREIGN KEY (`anulado_por`) REFERENCES `usuarios` (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=3850 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------
-- Tabla: `recepciones_internas_modificaciones`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `recepciones_internas_modificaciones`;
CREATE TABLE `recepciones_internas_modificaciones` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `RecepcionId` int(10) NOT NULL,
  `UsuarioId` int(10) DEFAULT NULL,
  `NModificacion` int(11) NOT NULL,
  `IpAddress` varchar(45) DEFAULT NULL,
  `ModificadoEn` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------
-- Tabla: `recepciones_internas_productos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `recepciones_internas_productos`;
CREATE TABLE `recepciones_internas_productos` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DespachoId` int(10) unsigned NOT NULL,
  `CodigoProducto` varchar(50) NOT NULL,
  `DescripcionProducto` varchar(255) NOT NULL,
  `UnidadMedida` varchar(45) NOT NULL,
  `Cantidad` int(11) NOT NULL,
  `Comentarios` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `idx_despacho` (`DespachoId`),
  KEY `idx_codigo` (`CodigoProducto`),
  CONSTRAINT `recepciones_internas_productos_ibfk_1` FOREIGN KEY (`DespachoId`) REFERENCES `recepciones_internas` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6935 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `recepcionistas`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `recepcionistas`;
CREATE TABLE `recepcionistas` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Nombres` varchar(45) NOT NULL,
  `ApellidoPaterno` varchar(45) NOT NULL,
  `NombresApellidos` varchar(100) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `responsables`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `responsables`;
CREATE TABLE `responsables` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Nombres` varchar(45) NOT NULL,
  `ApellidoPaterno` varchar(45) NOT NULL,
  `NombresApellidos` varchar(100) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `roles`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) NOT NULL,
  `Descripcion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Nombre` (`Nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------
-- Tabla: `roles_privilegios`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `roles_privilegios`;
CREATE TABLE `roles_privilegios` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `RoleId` int(11) NOT NULL,
  `PrivilegioId` int(11) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_role_privilegio` (`RoleId`,`PrivilegioId`),
  KEY `PrivilegioId` (`PrivilegioId`),
  CONSTRAINT `roles_privilegios_ibfk_1` FOREIGN KEY (`RoleId`) REFERENCES `roles` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `roles_privilegios_ibfk_2` FOREIGN KEY (`PrivilegioId`) REFERENCES `privilegios` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=342 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------
-- Tabla: `series`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `series`;
CREATE TABLE `series` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Serie` varchar(45) NOT NULL,
  `CentroDistribucion` varchar(100) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `subareas`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `subareas`;
CREATE TABLE `subareas` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Subarea` varchar(100) NOT NULL,
  `IdArea` int(11) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `transportistas`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `transportistas`;
CREATE TABLE `transportistas` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `RUC` varchar(11) NOT NULL,
  `Empresa` varchar(100) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=1549 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `turnos`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `turnos`;
CREATE TABLE `turnos` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Turno` varchar(45) NOT NULL,
  `HoraInicio` time NOT NULL,
  `HoraFin` time NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `unidades_medida`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `unidades_medida`;
CREATE TABLE `unidades_medida` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `UnidadMedida` varchar(45) NOT NULL,
  `Abreviacion` varchar(45) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `user_report_preferences`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `user_report_preferences`;
CREATE TABLE `user_report_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `report_code` varchar(50) NOT NULL COMMENT 'Codigo del reporte, ej: recepciones_externas',
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'JSON con visibilidad de columnas' CHECK (json_valid(`preferences`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_report` (`user_id`,`report_code`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: `usuarios`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `DocIdentidad` int(11) NOT NULL,
  `NombresApellidos` varchar(100) NOT NULL,
  `RoleId` int(11) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ----------------------------------------------------------
-- Tabla: `usuarios_limites_modificacion`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `usuarios_limites_modificacion`;
CREATE TABLE `usuarios_limites_modificacion` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `UsuarioId` int(11) NOT NULL,
  `max_modificaciones` int(11) DEFAULT NULL COMMENT 'NULL=usa default(1); ej:3=permite hasta 3 modificaciones',
  `ventana_horas` int(11) DEFAULT 24 COMMENT 'NULL=sin restriccion temporal; 24=ventana de 24h desde creado_en',
  `permite_multiples` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=sin limite de modificaciones, override total',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uq_usuario` (`UsuarioId`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VISTAS (3)
-- ============================================================

-- ----------------------------------------------------------
-- Vista: `vista_despachos_externos`
-- ----------------------------------------------------------
DROP VIEW IF EXISTS `vista_despachos_externos`;
CREATE ALGORITHM=UNDEFINED DEFINER=`lavorope_adm`@`%.%.%.%` SQL SECURITY DEFINER VIEW `vista_despachos_externos` AS select `de`.`NVale` AS `NVale`,`de`.`Fecha` AS `Fecha`,`de`.`Hora` AS `Hora`,`t`.`Turno` AS `Nombre_Turno`,`de`.`Destino` AS `Destino`,`de`.`RUC` AS `RUC_Cliente`,`de`.`Direccion` AS `Direccion_Entrega`,`r`.`NombresApellidos` AS `Despachador`,`c`.`ApellidosNombres` AS `Chofer`,`de`.`Placa_Tracto` AS `Placa_Tracto`,`dep`.`CodigoProducto` AS `CodigoProducto`,`dep`.`DescripcionProducto` AS `DescripcionProducto`,`dep`.`Cantidad` AS `Cantidad`,`dep`.`UnidadMedida` AS `UnidadMedida`,`u`.`username` AS `Creado_Por` from (((((`despachos_externos` `de` join `turnos` `t` on(`de`.`Turno` = `t`.`Id`)) join `responsables` `r` on(`de`.`Despachador` = `r`.`Id`)) join `choferes` `c` on(`de`.`Chofer` = `c`.`Id`)) join `despachos_externos_productos` `dep` on(`de`.`Id` = `dep`.`DespachoId`)) join `usuarios` `u` on(`de`.`creado_por` = `u`.`Id`));

-- ----------------------------------------------------------
-- Vista: `vista_despachos_internos`
-- ----------------------------------------------------------
DROP VIEW IF EXISTS `vista_despachos_internos`;
CREATE ALGORITHM=UNDEFINED DEFINER=`lavorope_adm`@`%.%.%.%` SQL SECURITY DEFINER VIEW `vista_despachos_internos` AS select `di`.`NVale` AS `NVale`,`di`.`Fecha` AS `Fecha`,`a`.`Area` AS `Area_Destino`,`sa`.`Subarea` AS `Subarea_Destino`,`em`.`NombresApellidos` AS `Emisor`,`des`.`NombresApellidos` AS `Despachador`,`rec`.`NombresApellidos` AS `Recepcionista_Interno`,`dip`.`CodigoProducto` AS `CodigoProducto`,`dip`.`DescripcionProducto` AS `DescripcionProducto`,`dip`.`Cantidad` AS `Cantidad`,`di`.`estado` AS `estado` from ((((((`despachos_internos` `di` join `areas` `a` on(`di`.`Area` = `a`.`Id`)) join `subareas` `sa` on(`di`.`Subarea` = `sa`.`Id`)) join `usuarios` `em` on(`di`.`Emisor` = `em`.`Id`)) join `responsables` `des` on(`di`.`Despachador` = `des`.`Id`)) join `recepcionistas` `rec` on(`di`.`Recepcionista` = `rec`.`Id`)) join `despachos_internos_productos` `dip` on(`di`.`Id` = `dip`.`DespachoId`)) order by `di`.`Fecha` desc;

-- ----------------------------------------------------------
-- Vista: `vista_recepcionesexternas`
-- ----------------------------------------------------------
DROP VIEW IF EXISTS `vista_recepcionesexternas`;
CREATE ALGORITHM=UNDEFINED DEFINER=`lavorope_adm`@`%.%.%.%` SQL SECURITY DEFINER VIEW `vista_recepcionesexternas` AS select `re`.`NVale` AS `NVale`,`re`.`Fecha` AS `Fecha`,`ori`.`Origen` AS `Origen_Carga`,`trans`.`Empresa` AS `Transportista`,`ch`.`ApellidosNombres` AS `Chofer`,`ch`.`Brevete` AS `Brevete`,`reg`.`NumeroGuia` AS `NumeroGuia`,`rep`.`CodigoProducto` AS `CodigoProducto`,`rep`.`DescripcionProducto` AS `DescripcionProducto`,`rep`.`Cantidad` AS `Cantidad_Recibida`,`rep`.`CantidadObservada` AS `CantidadObservada` from (((((`recepciones_externas` `re` join `origen` `ori` on(`re`.`Origen` = `ori`.`Id`)) join `transportistas` `trans` on(`re`.`Empresa` = `trans`.`Id`)) join `choferes` `ch` on(`re`.`Chofer` = `ch`.`Id`)) join `recepciones_externas_guias` `reg` on(`re`.`Id` = `reg`.`RecepcionExternaId`)) join `recepciones_externas_productos` `rep` on(`reg`.`Id` = `rep`.`GuiaId`));

-- ============================================================
-- PROCEDIMIENTOS ALMACENADOS Y FUNCIONES (6)
-- ============================================================

-- ----------------------------------------------------------
-- PROCEDURE: `actualizar_producto_despacho`
-- ----------------------------------------------------------
DROP PROCEDURE IF EXISTS `actualizar_producto_despacho`;
DELIMITER $$
CREATE DEFINER=`lavorope_adm`@`%.%.%.%` PROCEDURE `actualizar_producto_despacho`(
    IN p_DespachoId INT UNSIGNED,
    IN p_CodigoProducto VARCHAR(30),
    IN p_DescripcionProducto VARCHAR(120),
    IN p_Cantidad DECIMAL(10,2),
    IN p_UnidadMedida VARCHAR(50),
    IN p_Comentarios VARCHAR(255)
)
BEGIN 
    DECLARE v_ProductoId INT UNSIGNED;
    
    -- Buscar el ID del producto en este despacho
    SELECT Id INTO v_ProductoId
    FROM despachos_internos_productos
    WHERE DespachoId = p_DespachoId AND CodigoProducto = p_CodigoProducto
    LIMIT 1;
    
    -- Si el producto existe, actualizarlo
    IF v_ProductoId IS NOT NULL THEN
        UPDATE despachos_internos_productos
        SET 
            DescripcionProducto = p_DescripcionProducto,
            Cantidad = p_Cantidad,
            Comentarios = p_Comentarios,
            UnidadMedida = IFNULL(p_UnidadMedida, UnidadMedida) -- Solo actualizar si se proporciona un valor
        WHERE Id = v_ProductoId;
    ELSE
        -- Si no existe, insertar nuevo
        INSERT INTO despachos_internos_productos
        (DespachoId, CodigoProducto, DescripcionProducto, Cantidad, UnidadMedida, Comentarios)
        VALUES
        (p_DespachoId, p_CodigoProducto, p_DescripcionProducto, p_Cantidad, p_UnidadMedida, p_Comentarios);
    END IF;
END$$
DELIMITER ;

-- ----------------------------------------------------------
-- PROCEDURE: `anular_despacho_interno`
-- ----------------------------------------------------------
DROP PROCEDURE IF EXISTS `anular_despacho_interno`;
DELIMITER $$
CREATE DEFINER=`lavorope_adm`@`%.%.%.%` PROCEDURE `anular_despacho_interno`(
    IN p_Id INT UNSIGNED,
    IN p_anulado_por INT UNSIGNED,
    IN p_motivo VARCHAR(255)
)
BEGIN
    DECLARE existe INT DEFAULT 0;

    -- Validar que el registro exista y esté activo
    SELECT COUNT(*) INTO existe FROM despachos_internos WHERE Id = p_Id AND estado = 'activo';
    IF existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El registro no existe o ya está anulado.';
    END IF;

    -- Anular el registro y registrar auditoría
    UPDATE despachos_internos
    SET estado = 'anulado',
        anulado_por = p_anulado_por,
        anulado_en = NOW(),
        Observaciones = CONCAT(IFNULL(Observaciones, ''), ' | Motivo anulación: ', p_motivo)
    WHERE Id = p_Id;
END$$
DELIMITER ;

-- ----------------------------------------------------------
-- PROCEDURE: `modificar_despacho_interno`
-- ----------------------------------------------------------
DROP PROCEDURE IF EXISTS `modificar_despacho_interno`;
DELIMITER $$
CREATE DEFINER=`lavorope_adm`@`%.%.%.%` PROCEDURE `modificar_despacho_interno`(
    IN p_Id INT UNSIGNED,
    IN p_NVale INT,
    IN p_Fecha DATE,
    IN p_Hora TIME,
    IN p_Turno INT UNSIGNED,
    IN p_Area INT UNSIGNED,
    IN p_Subarea INT UNSIGNED,
    IN p_Emisor INT UNSIGNED,
    IN p_Despachador INT UNSIGNED,
    IN p_Recepcionista INT UNSIGNED,
    IN p_Verificador INT UNSIGNED,
    IN p_CodigoProducto VARCHAR(30),
    IN p_DescripcionProducto VARCHAR(120),
    IN p_Cantidad INT,
    IN p_Observaciones VARCHAR(255),
    IN p_Liquidacion VARCHAR(50),
    IN p_modificado_por INT UNSIGNED
)
BEGIN
    DECLARE existe INT DEFAULT 0;

    -- Validar que el registro exista y esté activo
    SELECT COUNT(*) INTO existe FROM despachos_internos WHERE Id = p_Id AND estado = 'activo';
    IF existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El registro no existe o está anulado.';
    END IF;

    -- Validar que el nuevo NVale no esté duplicado (excepto el mismo registro)
    SELECT COUNT(*) INTO existe FROM despachos_internos WHERE NVale = p_NVale AND Id <> p_Id;
    IF existe > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El número de vale ya existe en otro registro.';
    END IF;

    -- Actualizar el registro
    UPDATE despachos_internos
    SET NVale = p_NVale,
        Fecha = p_Fecha,
        Hora = p_Hora,
        Turno = p_Turno,
        Area = p_Area,
        Subarea = p_Subarea,
        Emisor = p_Emisor,
        Despachador = p_Despachador,
        Recepcionista = p_Recepcionista,
        Verificador = p_Verificador,
        CodigoProducto = p_CodigoProducto,
        DescripcionProducto = p_DescripcionProducto,
        Cantidad = p_Cantidad,
        Observaciones = p_Observaciones,
        Liquidacion = p_Liquidacion,
        modificado_por = p_modificado_por,
        modificado_en = NOW()
    WHERE Id = p_Id;
END$$
DELIMITER ;

-- ----------------------------------------------------------
-- PROCEDURE: `pa_reporte_despachos_internos`
-- ----------------------------------------------------------
DROP PROCEDURE IF EXISTS `pa_reporte_despachos_internos`;
DELIMITER $$
CREATE DEFINER=`lavorope_adm`@`%.%.%.%` PROCEDURE `pa_reporte_despachos_internos`(
    IN p_search VARCHAR(255),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    
    DECLARE searchTerm VARCHAR(255);
    
    
    SET searchTerm = CONCAT('%', IFNULL(p_search, ''), '%');
    
    
    SELECT 
        d.id AS ID,
        DATE_FORMAT(d.fecha, '%d/%m/%Y') AS Fecha,
        DATE_FORMAT(d.hora, '%H:%i') AS Hora,
        IFNULL(o.nombre, 'N/A') AS Origen,
        IFNULL(s.nombre, 'N/A') AS Subareas,
        IFNULL(a.nombre, 'N/A') AS Areas,
        IFNULL(des.nombre, 'N/A') AS Destino,
        IFNULL(p.nombre, 'N/A') AS Producto,
        d.cantidad AS Cantidad,
        d.despacho AS Despacho,
        IFNULL(r.nombre, 'N/A') AS Responsable,
        IFNULL(t.nombre, 'N/A') AS Transportista,
        IFNULL(ch.nombre, 'N/A') AS Chofer,
        IFNULL(ce.nombre, 'N/A') AS ClienteExterno,
        IFNULL(obs.descripcion, '') AS Observacion,
        IFNULL(recep.nombre, 'N/A') AS Recepcionista,
        d.hora_recepcion AS HoraRecepcion,
        IFNULL(u.username, 'N/A') AS UsuarioRegistro,
        
        CASE 
            WHEN LOWER(d.estado) = 'activo' THEN 1 
            ELSE 0 
        END AS Estado
    FROM despachos_internos d
    
    LEFT JOIN origen o ON d.origen_id = o.id
    LEFT JOIN subareas s ON d.subarea_id = s.id
    LEFT JOIN area a ON s.area_id = a.id
    LEFT JOIN destino des ON d.destino_id = des.id
    LEFT JOIN productos p ON d.producto_id = p.id
    LEFT JOIN responsable r ON d.responsable_id = r.id
    LEFT JOIN transportista t ON d.transportista_id = t.id
    LEFT JOIN chofer ch ON d.chofer_id = ch.id
    LEFT JOIN cliente_externo ce ON d.cliente_externo_id = ce.id
    LEFT JOIN observacion obs ON d.observacion_id = obs.id
    LEFT JOIN recepcionista recep ON d.recepcionista_id = recep.id
    LEFT JOIN users u ON d.usuario_id = u.id
    
    WHERE 
        (p_search IS NULL OR p_search = '' OR
        d.despacho LIKE searchTerm OR
        d.cantidad LIKE searchTerm OR
        o.nombre LIKE searchTerm OR
        s.nombre LIKE searchTerm OR
        a.nombre LIKE searchTerm OR
        des.nombre LIKE searchTerm OR
        p.nombre LIKE searchTerm OR
        r.nombre LIKE searchTerm OR
        t.nombre LIKE searchTerm OR
        ch.nombre LIKE searchTerm OR
        ce.nombre LIKE searchTerm OR
        obs.descripcion LIKE searchTerm OR
        recep.nombre LIKE searchTerm OR
        u.username LIKE searchTerm OR
        DATE_FORMAT(d.fecha, '%d/%m/%Y') LIKE searchTerm)
    
    ORDER BY d.id DESC
    
    LIMIT p_limit OFFSET p_offset;
 END$$
DELIMITER ;

-- ----------------------------------------------------------
-- PROCEDURE: `pa_reporte_despachos_internos_total`
-- ----------------------------------------------------------
DROP PROCEDURE IF EXISTS `pa_reporte_despachos_internos_total`;
DELIMITER $$
CREATE DEFINER=`lavorope_adm`@`%.%.%.%` PROCEDURE `pa_reporte_despachos_internos_total`(
    IN p_search VARCHAR(255)
)
BEGIN
    
    DECLARE searchTerm VARCHAR(255);
    
    
    SET searchTerm = CONCAT('%', IFNULL(p_search, ''), '%');
    
    
    SELECT COUNT(*) AS total
    FROM despachos_internos d
    
    LEFT JOIN origen o ON d.origen_id = o.id
    LEFT JOIN subareas s ON d.subarea_id = s.id
    LEFT JOIN areas a ON s.area_id = a.id
    LEFT JOIN destino des ON d.destino_id = des.id
    LEFT JOIN productos p ON d.producto_id = p.id
    LEFT JOIN responsable r ON d.responsable_id = r.id
    LEFT JOIN transportista t ON d.transportista_id = t.id
    LEFT JOIN chofer ch ON d.chofer_id = ch.id
    LEFT JOIN cliente_externo ce ON d.cliente_externo_id = ce.id
    LEFT JOIN observacion obs ON d.observacion_id = obs.id
    LEFT JOIN recepcionista recep ON d.recepcionista_id = recep.id
    LEFT JOIN users u ON d.usuario_id = u.id
    
    WHERE 
        (p_search IS NULL OR p_search = '' OR
        d.despacho LIKE searchTerm OR
        d.cantidad LIKE searchTerm OR
        o.nombre LIKE searchTerm OR
        s.nombre LIKE searchTerm OR
        a.nombre LIKE searchTerm OR
        des.nombre LIKE searchTerm OR
        p.nombre LIKE searchTerm OR
        r.nombre LIKE searchTerm OR
        t.nombre LIKE searchTerm OR
        ch.nombre LIKE searchTerm OR
        ce.nombre LIKE searchTerm OR
        obs.descripcion LIKE searchTerm OR
        recep.nombre LIKE searchTerm OR
        u.username LIKE searchTerm OR
        DATE_FORMAT(d.fecha, '%d/%m/%Y') LIKE searchTerm);
 END$$
DELIMITER ;

-- ----------------------------------------------------------
-- PROCEDURE: `registrar_despacho_interno`
-- ----------------------------------------------------------
DROP PROCEDURE IF EXISTS `registrar_despacho_interno`;
DELIMITER $$
CREATE DEFINER=`lavorope_adm`@`%.%.%.%` PROCEDURE `registrar_despacho_interno`(
    IN p_NVale INT,
    IN p_Fecha DATE,
    IN p_Hora TIME,
    IN p_Turno INT UNSIGNED,
    IN p_Area INT UNSIGNED,
    IN p_Subarea INT UNSIGNED,
    IN p_Emisor INT UNSIGNED,
    IN p_Despachador INT UNSIGNED,
    IN p_Recepcionista INT UNSIGNED,
    IN p_Verificador INT UNSIGNED,
    IN p_Liquidacion VARCHAR(50),
    IN p_creado_por INT UNSIGNED
)
BEGIN
    INSERT INTO despachos_internos (
        NVale, Fecha, Hora, Turno, Area, Subarea, Emisor, Despachador, Recepcionista, Verificador,
        Liquidacion, creado_por, creado_en, estado
    ) VALUES (
        p_NVale, p_Fecha, p_Hora, p_Turno, p_Area, p_Subarea, p_Emisor, p_Despachador, p_Recepcionista, p_Verificador,
        p_Liquidacion, p_creado_por, NOW(), 'activo'
    );
    SELECT LAST_INSERT_ID() AS Id;
END$$
DELIMITER ;

-- ============================================================
-- FIN DE LA ESTRUCTURA
-- ============================================================
SET FOREIGN_KEY_CHECKS=1;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
