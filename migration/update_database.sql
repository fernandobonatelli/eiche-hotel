-- =====================================================
-- Pousada Bona - Script de Atualização do Banco de Dados
-- Versão 2.0
-- =====================================================
-- IMPORTANTE: Faça backup do banco antes de executar!
-- =====================================================

-- Adicionar campos novos à tabela de usuários
ALTER TABLE `eiche_users` 
    ADD COLUMN IF NOT EXISTS `remember_token` VARCHAR(255) NULL AFTER `password`,
    ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME NULL,
    ADD COLUMN IF NOT EXISTS `last_login_at` DATETIME NULL,
    ADD COLUMN IF NOT EXISTS `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Atualizar coluna de senha para suportar BCrypt (60 caracteres mínimo)
ALTER TABLE `eiche_users` MODIFY `password` VARCHAR(255) NOT NULL;

-- Criar tabela de logs se não existir
CREATE TABLE IF NOT EXISTS `eiche_log` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_user` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `ip` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`ID_user`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de quartos se não existir
CREATE TABLE IF NOT EXISTS `eiche_rooms` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `number` VARCHAR(20) NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'standard',
    `capacity` INT NOT NULL DEFAULT 2,
    `price_daily` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('available', 'occupied', 'maintenance', 'reserved') DEFAULT 'available',
    `description` TEXT NULL,
    `amenities` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_number` (`number`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de reservas se não existir
CREATE TABLE IF NOT EXISTS `eiche_reservations` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `room_id` INT NOT NULL,
    `check_in` DATE NOT NULL,
    `check_out` DATE NOT NULL,
    `adults` INT DEFAULT 1,
    `children` INT DEFAULT 0,
    `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_room` (`room_id`),
    INDEX `idx_check_in` (`check_in`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela de pagamentos se não existir
CREATE TABLE IF NOT EXISTS `eiche_payments` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `reservation_id` INT NULL,
    `customer_id` INT NOT NULL,
    `value` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) NULL,
    `status` ENUM('pending', 'paid', 'cancelled', 'refunded') DEFAULT 'pending',
    `due_date` DATE NULL,
    `paid_at` DATETIME NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_reservation` (`reservation_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atualizar tabela de clientes
ALTER TABLE `eiche_customers` 
    ADD COLUMN IF NOT EXISTS `status` CHAR(1) DEFAULT 'A',
    ADD COLUMN IF NOT EXISTS `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Adicionar índices para performance
CREATE INDEX IF NOT EXISTS `idx_customers_status` ON `eiche_customers` (`status`);
CREATE INDEX IF NOT EXISTS `idx_customers_name` ON `eiche_customers` (`name`);

-- Atualizar configuração para UTF-8 completo
ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Converter tabelas existentes para utf8mb4
ALTER TABLE `eiche_users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `eiche_config` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `eiche_menu` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `eiche_submenu` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================

