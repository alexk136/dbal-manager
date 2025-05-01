-- Удалим базу, если она уже есть
DROP DATABASE IF EXISTS dbal_test;

-- Создаём новую базу данных
CREATE DATABASE dbal_test
  WITH ENCODING 'UTF8'
  LC_COLLATE = 'en_US.UTF-8'
  LC_CTYPE = 'en_US.UTF-8'
  TEMPLATE = template0;

-- Переключение на неё
\c dbal_test;

-- Создаём тип ENUM
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'status_enum') THEN
CREATE TYPE status_enum AS ENUM ('new', 'processing', 'done');
END IF;
END
$$;

DROP TABLE IF EXISTS test_data_types;

CREATE TABLE test_data_types (
    id SERIAL PRIMARY KEY,                                -- автоинкремент целое число
    uuid UUID NOT NULL,                                   -- UUID тип
    name VARCHAR(255) NOT NULL,                           -- строка
    price NUMERIC(10,2) NOT NULL DEFAULT 0.00,            -- число с фиксированной точностью
    active BOOLEAN NOT NULL DEFAULT TRUE,                 -- логическое значение
    meta JSONB NOT NULL,                                  -- JSON объект (Postgres использует JSONB)
    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, -- дата создания
    updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, -- можно триггером автообновлять
    deleted_at TIMESTAMP WITHOUT TIME ZONE NULL,          -- дата удаления
    status status_enum NOT NULL DEFAULT 'new',            -- ENUM через тип
    data_bytea BYTEA,                                     -- бинарные данные
    big_value BIGINT,                                     -- большое целое
    float_value DOUBLE PRECISION,                         -- плавающее число
    small_value SMALLINT,                                 -- маленькое целое
    text_field TEXT,                                      -- длинный текст
    inet_field INET,                                      -- IP-адрес
    mac_field MACADDR,                                    -- MAC-адрес
    point_field POINT,                                    -- геометрия: точка
    interval_field INTERVAL,                              -- интервал времени
    json_field JSON                                       -- обычный JSON
    coordinates FLOAT8[]                                  -- массив чисел (вектор)
    float4_coordinates FLOAT4[]                           -- массив чисел (вектор)
);

-- Enum для поля status
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'status_enum') THEN
CREATE TYPE status_enum AS ENUM ('new', 'processing', 'done');
END IF;
END$$;
