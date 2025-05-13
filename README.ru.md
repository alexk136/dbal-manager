# Dbal Bundle for Symfony

**Dbal Bundle** — это модуль для Symfony-приложений, предназначенный для высоконагруженных (highload) систем, где стандартные возможности Doctrine ORM становятся узким местом. Бандл предоставляет абстракции и интерфейсы для прямой, эффективной и масштабируемой работы с базой данных на уровне Doctrine DBAL.


## Основные возможности

- Высокопроизводительная работа с базой данных на уровне DBAL.
- Прямая работа с DTO и массивами данных, без слоя ORM.
- Расширенные bulk-операции: insert, update, upsert, delete.
- Интерфейсы для cursor-based и offset-based итераторов.
- Базовые интерфейсы Finder/Mutator для чтения и изменения данных.
- Поддержка нескольких соединений к БД.
- Полный контроль над SQL-запросами.
- Поддержка слеюущих ORM:
    - MySQL 8


## Архитектура

Dbal Bundle построен на интерфейсах и абстракциях, которые легко расширять и адаптировать под любые нужды.

В основе select операций лежат **генераторы (`yield`)**, что позволяет:

- обрабатывать большие объёмы данных с минимальным потреблением памяти
- начинать обработку данных до завершения всего запроса (ленивая загрузка)
- реализовывать потоковую обработку и передачу данных — полезно при интеграции с очередями, API, логикой синхронизации и экспортами

### Основные интерфейсы:

#### Finder/Mutator

- `DbalFinderInterface`: чтение данных, доступен маппинг результат в DTO.
- `DbalMutatorInterface`: обновление, удаление, вставка, есть чистый метеод execute.

#### Bulk-операции

- `BulkInserterInterface`: Вставка одной или нескольких строк в базу данных.
- `BulkUpdaterInterface`: Обновление ожной\множства строк в базу данных.
- `BulkUpserterInterface`: Комбинированная операция обновления или вставки строк (upsert) в базу данных.
- `BulkDeleterInterface`: Удаление строк из базы данных, включая поддержку soft delete.

#### Итераторы

- `CursorIteratorInterface`: поддержка cursor-based чтения, подходит для потоковой обработки.
- `OffsetIteratorInterface`: стандартная постраничная итерация.

#### Вспомогательные классы

- `DtoFieldExtractor`: извлекает и нормализует поля из DTO.
- `DbalTypeGuesser`: маппинг PHP-типов в SQL-типы.
- `MysqlSqlBuilder`: генератор SQL-запросов под MySQL.


## Установка

```bash
composer require alexk136/dbal-bundle
```

Зарегистрируйте бандл в `config/bundles.php`:

```php
Elrise\Bundle\DbalBundle\ElriseDbalBundle::class::class => ['all' => true],
```


## Работа с `DbalManagerFactory`

Класс `DbalManagerFactory` позволяет удобно создавать компоненты DBAL-инфраструктуры с возможностью переопределения подключения к базе данных (`Connection`) и конфигурации (`DbalBundleConfig`) на уровне каждого сервиса.

### Быстрое создание `DbalManager`

Если вы хотите использовать все компоненты DBAL сразу — достаточно вызвать метод `createManager()`:

```php
$dbalManager = $factory->createManager();
```

Можно передать кастомные `Connection` и `DbalBundleConfig`:

```php
$dbalManager = $factory->createManager($customConnection, $customConfig);
```

### Создание отдельных компонентов

Если необходимо использовать один из компонентов отдельно — используйте соответствующий метод:

```php
$finder = $factory->createFinder(...);
$mutator = $factory->createMutator(...);
$cursorIterator = $factory->createCursorIterator(...);
$offsetIterator = $factory->createOffsetIterator(...);
$bulkInserter = $factory->createBulkInserter(...);
$bulkUpdater = $factory->createBulkUpdater(...);
$bulkUpserter = $factory->createBulkUpserter(...);
```

Для каждого из методов можно указать собственный `Connection` и (опционально) `DbalBundleConfig`:

```php
$bulkUpdater = $factory->createBulkUpdater($customConnection, $customConfig);
```

Это особенно полезно, если вы работаете с несколькими базами данных или хотите использовать разные стратегии конфигурации.

---

### Пример использования в сервисе

```php
class MyService
{
    public function __construct(private DbalManagerFactory $factory) {}

    public function updateBulkData(array $rows): void
    {
        $bulkUpdater = $this->factory->createBulkUpdater();
        $bulkUpdater->update('my_table', $rows);
    }
}
```

## Bulk Insert

Модуль поддерживает массовую вставку данных с возможностью указания:

- Названия таблицы
- Массива строк для вставки
- Автоматической или ручной генерации ID
- Явного указания типа значения для каждого поля

### Пример использования

```php
/** @var BulkInserterInterface $inserter */
$inserter->insert('user_table', [
    [
        'id' => IdStrategy::AUTO_INCREMENT, // ID сгенерируется в БД
        'email' => ['user1@example.com', ParameterType::STRING],
        'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
    ],
    [
        'id' => IdStrategy::UUID, // ID будет сгенерирован в коде
        'email' => ['user2@example.com', ParameterType::STRING],
        'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
    ],
    [
        // ID будет сгенерирован в коде
        'email' => ['user3@example.com', ParameterType::STRING],
        'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
    ],
]);
```

> Массив `['value', ParameterType::TYPE]` позволяет задать **тип значения**, совместимый с `Doctrine\DBAL\ParameterType`.  
> Если тип не указан — он будет определён автоматически.

---

### Стратегии генерации ID (`IdStrategy`)

ID может быть сгенерирован автоматически или задан вручную, в зависимости от стратегии:

| Стратегия                    | Описание                                                                                                      |
|------------------------------|---------------------------------------------------------------------------------------------------------------|
| `IdStrategy::AUTO_INCREMENT` | Значение не указывается — генерируется на уровне БД                                                           |
| `IdStrategy::UUID`           | Значение генерируется в коде (UUID v7)                                                                        |
| `IdStrategy::UID`            | Значение генерируется в коде (18 символов)                                                                    |
| `IdStrategy::INT`            | Значение генерируется как случайное целое число                                                               |
| `IdStrategy::STRING`         | Генерируется строка (например, на основе `uniqid()`)                                                          |
| `IdStrategy::DEFAULT`        | Значение неоьходимо использовать для работы с Postgres и генерации ID DEFAULT в рамках Insert\Upsert операции |

## DbalBulkUpdater

`DbalBulkUpdater` позволяет обновлять от 1 до множества строк в базе данных.

### 📌 Пример

```php
$bulkUpdater
    ->updateMany('api_history', [
        ['id' => 1, 'status' => 'success'],
        ['id' => 2, 'status' => 'success'],
    ]);
```

> По умолчанию в качестве условия используется поле `id`.
> Обновление происходит с помощью `CASE WHEN ... THEN ...` без множественных запросов.
> Возвращается количество затронутых строк.

## DbalBulkUpserter

`DbalBulkUpserter` позволяет вставлять или обновлять записи по ключевым полям. Если запись с таким `id` уже существует, она будет обновлена; если нет — будет вставлена новая.

### Пример

```php
$bulkUpserter
    ->upsertMany('api_history', [
        [
            'id' => 123,
            'status' => 'success',
            'updated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ],
        [
            'id' => IdStrategy::AUTO_INCREMENT,
            'status' => 'success',
            'updated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ], ['status', 'updated_at']);
```

> Обновляемые поля передаются третьим аргументом (`replaceFields`).  
> `id` может быть сгенерирован автоматически через `IdStrategy::AUTO_INCREMENT`.

## DbalFinder

`DbalFinder` предоставляет методы для типизированного извлечения данных из базы.

### Примеры использования

```php
// Получить одну строку по SQL (автоматически добавляется LIMIT 1)
$result = $finder->fetchOneBySql(
    'SELECT * FROM api_history WHERE id = :id',
    ['id' => $id],
    ApiDto::class
);

// Получить несколько строк с маппингом в DTO
$results = $finder->fetchAllBySql(
    'SELECT * FROM api_history ORDER BY id LIMIT 10',
    [],
    ApiDto::class
);

// Найти запись по ID
$result = $finder->findById($id, 'api_history', ApiDto::class);

// Найти записи по ID
$result = $finder->findByIdList($idList, 'api_history', ApiDto::class);
```

> Если не указан класс DTO — вернётся массив.

## DbalMutator

`DbalMutator` предназначен для безопасной вставки и изменения данных в таблицах базы данных.

### Примеры использования

```php
// Вставка одной строки в таблицу
$mutator->insert('api_history', [
    'type' => ['callback', ParameterType::STRING],
    'merchant_id' => '12345',
    'provider' => 'example-provider',
    'trace_id' => 'trace-001',
    'our_id' => 'our-001',
    'ext_id' => 'ext-001',
    'data' => json_encode(['source' => 'test']),
    'status' => 'success',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
```
> Поддерживаются поля с типами (например, `['value', ParameterType::STRING]`).
> Если тип не указан — он будет определён автоматически.


## ⚠️ Важно

Перед использованием методов `insert()`, `updateMany()`, `upsertMany()` необходимо **обязательно указать актуальные служебные поля** через метод `setFieldNames()` или общий конфиг в поле `fieldNames`:

```php
->setFieldNames([
    BundleConfigurationInterface::ID_NAME => 'id',
    BundleConfigurationInterface::CREATED_AT_NAME => 'created_at',
    BundleConfigurationInterface::UPDATED_AT_NAME => 'updated_at',
])
```

## BulkTest Console Commands Setup

Для использования тестовых консольных команд, связанных с массовыми DBAL-операциями (`insertMany`, `updateMany`, `upsertMany`, `deleteMany`, `softDeleteMany`), добавьте следующую конфигурацию в ваш `services.yaml`:

```yaml
services:
    Elrise\Bundle\DbalBundle\Manager\Bulk\BulkUpserter:
        arguments:
            $connection: '@Doctrine\DBAL\Connection'
            $config: '@Elrise\Bundle\DbalBundle\Config\DbalBundleConfig'
            $sqlBuilder: '@Elrise\Bundle\DbalBundle\Sql\Builder\SqlBuilderInterface'

    Elrise\Bundle\DbalBundle\BulkTestCommands\BulkInsertManyCommand:
        arguments:
            $connection: '@Doctrine\DBAL\Connection'
            $bulkInserter: '@Elrise\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface'
        tags: [ 'console.command' ]

    Elrise\Bundle\DbalBundle\BulkTestCommands\BulkUpdateManyCommand:
        arguments:
            $connection: '@Doctrine\DBAL\Connection'
            $bulkInserter: '@Elrise\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface'
            $bulkUpdater: '@Elrise\Bundle\DbalBundle\Manager\Contract\BulkUpdaterInterface'
        tags: [ 'console.command' ]

    Elrise\Bundle\DbalBundle\BulkTestCommands\BulkUpsertManyCommand:
        arguments:
            $connection: '@Doctrine\DBAL\Connection'
            $bulkInserter: '@Elrise\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface'
            $bulkUpserter: '@Elrise\Bundle\DbalBundle\Manager\Contract\BulkUpserterInterface'
        tags: [ 'console.command' ]

    Elrise\Bundle\DbalBundle\BulkTestCommands\BulkDeleteManyCommand:
        arguments:
            $connection: '@Doctrine\DBAL\Connection'
            $bulkDeleter: '@Elrise\Bundle\DbalBundle\Manager\Contract\BulkDeleterInterface'
            $bulkInserter: '@Elrise\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface'
        tags: [ 'console.command' ]

    Elrise\Bundle\DbalBundle\BulkTestCommands\BulkSoftDeleteManyCommand:
        arguments:
            $connection: '@Doctrine\DBAL\Connection'
            $bulkDeleter: '@Elrise\Bundle\DbalBundle\Manager\Contract\BulkDeleterInterface'
            $bulkInserter: '@Elrise\Bundle\DbalBundle\Manager\Contract\BulkInserterInterface'
        tags: [ 'console.command' ]
```

---

### Тестовая таблица

Для запуска команд можно использовать заранее подготовленную таблицу из SQL-файла:

```
// для MySQL
tests/_db/init.sql

// для PostgreSQL
tests/_db/init_postgres.sql
```

Запусти этот SQL-файл вручную в своей тестовой базе перед выполнением команд.

---

### Использование команд

```bash
bin/console dbal:test:run-all # Запускакт все команды
bin/console dbal:test:bulk-insert-many
bin/console dbal:test:bulk-update-many
bin/console dbal:test:bulk-upsert-many
bin/console dbal:test:bulk-delete-many
bin/console dbal:test:bulk-soft-delete-many
bin/console dbal:test:cursor-iterator
bin/console dbal:test:offset-iterator
bin/console dbal:test:finder
bin/console dbal:test:mutator
bin/console dbal:test:transaction-service
bin/console dbal:test:insert
```

Каждая команда поддерживает:
- `--chunk=<int>` — размер чанка для пакетной обработки
- `--count=<int>` — количество записей (по умолчанию 1000)
- `--cycle=<int>` — число повторов вставки/обновления/удаления (для бенчмарка)
- `--track` — включает логирование результатов

Пример:

```bash
bin/console app:test:bulk-upsert-many --chunk=200 --count=5000 --cycle=5 --track
```

---

### Логирование результатов

Если передан флаг `--track`, команда будет сохранять логи производительности в CSV-файл:

```
var/log/<тип_теста>_<timestamp>.csv
```

Каждая строка в логе содержит:
- номер итерации
- время выполнения
- использование памяти
- изменение памяти
- накопленное время

## Совместимость

- PHP 8.2+
- Symfony 7.0+
- Doctrine DBAL 3.6+
- MySQL 5.7 / PostgreSQL 16
