-- Migración: Tabla de límites de modificación por usuario
-- Fecha: 2025-01-30
-- Descripción: Crea la tabla usuarios_limites_modificacion para configurar
-- límites de modificación personalizados por usuario (ventana de 24h, máximo
-- de modificaciones, permisos especiales)

CREATE TABLE IF NOT EXISTS `usuarios_limites_modificacion` (
    `Id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `UsuarioId` INT NOT NULL,
    `max_modificaciones` INT NULL DEFAULT NULL COMMENT 'NULL=usa default(1); ej:3=permite hasta 3 modificaciones',
    `ventana_horas` INT NULL DEFAULT 24 COMMENT 'NULL=sin restriccion temporal; 24=ventana de 24h desde creado_en',
    `permite_multiples` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=sin limite de modificaciones, override total',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_usuario` (`UsuarioId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
