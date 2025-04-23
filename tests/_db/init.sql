# Mysql8 совместивмая таблица

-- Удалим базу, если она уже есть
DROP DATABASE IF EXISTS dbal_test;

-- Создаём новую базу данных с правильной кодировкой
CREATE DATABASE dbal_test
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

-- Используем её
USE dbal_test;

DROP TABLE IF EXISTS `test_data_types`;

CREATE TABLE `test_data_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,                             -- целое число
    `uuid` CHAR(36) NOT NULL,                                                 -- UUID
    `name` VARCHAR(255) NOT NULL,                                             -- строка
    `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,                             -- число с фиксированной точностью
    `active` BOOLEAN NOT NULL DEFAULT TRUE,                                   -- логическое значение
    `meta` JSON NOT NULL,                                                     -- JSON объект
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,                -- дата создания
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- автообновляемое поле
    `status` ENUM('new', 'processing', 'done') NOT NULL DEFAULT 'new',        -- перечисление
    `data_blob` BLOB                                                          -- бинарные данные
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;