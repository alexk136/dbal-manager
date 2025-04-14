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
composer require itech/dbal-bundle
```

Зарегистрируйте бандл в `config/bundles.php`:

```php
ITech\Bundle\DbalBundle\ItechDbalBundle::class::class => ['all' => true],
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

## Использование

### 1. DTO и Bulk Insert

```php
/** @var BulkInserterInterface $inserter */
$inserter->insert('user_table', [
    ['uuid-1', 'email1@example.com'],
    ['uuid-2', 'email2@example.com'],
]);
```

### 2. Обновление данных

```php
/** @var BulkUpdaterInterface $updater */
$updater->update('user_table', [
    ['uuid-1', 'new-email@example.com']
]);
```

### 3. Получение данных через Finder

```php
/** @var DbalFinderInterface $finder */
$users = $finder->find('user_table', ['status' => 'active']);
```

### 4. Использование курсора

```php
$cursor = $finder->cursor('user_table', ['status' => 'active']);
foreach ($cursor as $row) {
    // обработка
}
```

## Совместимость

- PHP 8.2+
- Symfony 7.0+
- Doctrine DBAL 3.6+
- MySQL 8 / PostgreSQL поддержка частично (в процессе)
