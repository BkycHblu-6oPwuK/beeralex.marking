# beeralex.marking

Модуль интеграции с API «Честный ЗНАК» для валидации кодов маркировки товаров в Bitrix.

## Требования

- PHP 8.1+
- Bitrix Framework
- `beeralex.core` модуль

## Установка

Добавьте в `composer.json` настройку для установки в `local/modules`:

```json
{
  "extra": {
    "installer-paths": {
      "local/modules/{$name}/": ["type:bitrix-module"]
    }
  }
}
```

Установите пакеты:

```bash
composer require beeralex/beeralex.core
composer require beeralex/beeralex.marking
```

### Настройка

1. Установите модули через админку Bitrix
2. Получите документ, подписанный УКЭП (усиленной квалифицированной электронной подписью)
3. Заполните настройки модуля:
   - **OAUTH_KEY** - документ, подписанный УКЭП в base64
   - **TOKEN** - токен из личного кабинета (используется, если OAUTH_KEY пустой)
   - **Fiscal Drive Number** - номер фискального накопителя
   - **Тестовый режим** - включение sandbox окружения

---

## Основные возможности

### ✅ Валидация кодов маркировки
- Проверка кодов через API Честного ЗНАКа
- Автоматическое переключение между CDN хостами
- Retry механизм при ошибках
- Сохранение результатов в БД

### 🔄 Работа с CDN
- Автоматическое получение списка CDN хостов
- Кэширование хостов
- Fallback на резервные хосты при недоступности

### 🔐 Авторизация
- Через УКЭП (OAUTH_KEY)
- Через токен из личного кабинета
- Автоматическое обновление токена

### 📦 Интеграция с кассами
- Настройки на уровне обработчика кассы
- Разные токены для разных касс
- Trait для добавления настроек в кассу

### 📝 Логирование
- Включение через настройки модуля

---

## Быстрый старт

### Базовая проверка кодов

```php
<?php
use Beeralex\Marking\Factory\CodesCheckServiceFactory;

try {
    $factory = service(CodesCheckServiceFactory::class);
    $codesCheckService = $factory->createDefault();
    
    $codes = [
        '01046406520015652193MTf2y!%sDJx',
        '01046406520015652193MTf2y!%sABC'
    ];
    
    /** @var \Beeralex\Marking\Entity\Codes\CodesCheckResult $result */
    $result = $codesCheckService->check($codes);
    
    if ($result->allCodesValid()) {
        echo "Все коды валидны!";
    } else {
        foreach ($result->getInvalidCodes() as $code) {
            echo "Невалидный код: {$code->cis}\n";
            echo "Причина: {$code->errorMessage}\n";
        }
    }
} catch (\Exception $e) {
    echo "Ошибка проверки: " . $e->getMessage();
}
```

### Работа через фабрику

```php
<?php
use Beeralex\Marking\Factory\CodesCheckServiceFactory;

$factory = service(CodesCheckServiceFactory::class);

// По заказу (автоматически находит кассу)
$service = $factory->createByOrder($order);

// По обработчику кассы
$service = $factory->createByCashbox($cashboxHandler);

// Дефолтные настройки модуля
$service = $factory->createDefault();

$result = $service->check($codes);
```

---

## Интеграция с кассами

Для использования разных токенов/настроек для каждой кассы:

### 1. Добавьте trait в обработчик кассы

```php
<?php
namespace Your\Namespace;

use Beeralex\Marking\CashboxSettingsTrait;
use Bitrix\Sale\Cashbox\Cashbox;

class YourCashboxHandler extends Cashbox
{
    use CashboxSettingsTrait;
    
    // Ваш код обработчика
}
```

Это добавит в настройки кассы поля:
- `MARKING_OAUTH_KEY`
- `MARKING_TOKEN`
- `MARKING_FISCAL_DRIVE_NUMBER`

### 2. Используйте через фабрику

```php
<?php
use Beeralex\Marking\Factory\CodesCheckServiceFactory;

$factory = service(CodesCheckServiceFactory::class);

// Автоматически возьмет настройки из кассы заказа
$service = $factory->createByOrder($order);
$result = $service->check($codes);
```

---

## Архитектура

### Сервисы

#### CodesCheckService
Основной сервис для проверки кодов маркировки.

```php
public function check(array $codes): CodesCheckResult
public function setFiscalDriveNumber(string $fiscalDriveNumber): self
```

#### CdnService
Получение и кэширование списка CDN хостов.

```php
public function getCdn(bool $withoutCache = false): Hosts
```

#### AuthService
Авторизация и получение токенов.

```php
protected function authorize(): string
protected function retryWithTokenRefresh(callable $callback): mixed
```

### Сущности

#### CodesCheckResult
Результат проверки кодов.

```php
public function allCodesValid(): bool
public function getValidCodes(): array
public function getInvalidCodes(): array
public function hasTransborderUnavailable(): bool
```

#### Code
Информация о проверенном коде.

```php
public readonly string $cis;              // Код маркировки
public readonly bool $valid;              // Валиден ли код
public readonly ?string $errorMessage;    // Сообщение об ошибке
public readonly ?string $errorCode;       // Код ошибки
```

### Репозитории

#### CodeCheckRepository
Сохранение результатов проверки в БД.

```php
public function save(CodesCheckResult $result): void
public function findByCode(string $code): ?array
```

---

## Настройки модуля

Через админку Bitrix → Модули → beeralex.marking:

| Параметр | Описание |
|----------|----------|
| `MARKING_OAUTH_KEY` | Документ, подписанный УКЭП в base64 |
| `MARKING_TOKEN` | Токен из личного кабинета (если нет OAUTH_KEY) |
| `MARKING_DEFAULT_FISKAL_DRIVE_NUMBER` | Номер фискального накопителя по умолчанию |
| `MARKING_BASE_TEST_URL` | URL тестового API |
| `MARKING_BASE_PROD_URL` | URL продакшн API |
| `MARKING_TEST` | Включить тестовый режим |
| `MARKING_LOGS` | Включить логирование |

---

## Обработка ошибок

### Типы исключений

```php
use Beeralex\Marking\Exceptions\CdnTemporarilyUnavailableException;
use Beeralex\Marking\Exceptions\TransborderCheckServiceUnavailableException;
use Beeralex\Core\Exceptions\ApiTooManyRequestsException;

try {
    $result = $service->check($codes);
} catch (ApiTooManyRequestsException $e) {
    // Превышен лимит запросов - автоматически переключится на другой CDN
} catch (CdnTemporarilyUnavailableException $e) {
    // CDN временно недоступен - попробует другой хост
} catch (TransborderCheckServiceUnavailableException $e) {
    // Сервис проверки трансграничных кодов недоступен
} catch (\Exception $e) {
    // Общая ошибка
}
```

### Логирование

При включенном `MARKING_LOGS` все ошибки логируются:

```php
// Логи находятся в:
// {module_dir}/logs/
// Например: /local/modules/beeralex.marking/logs/

$options = service(\Beeralex\Marking\Options::class);
if ($options->logsEnable) {
    // Логирование активно
}
```

---

## Примеры использования

### Проверка с кастомным Fiscal Drive Number

```php
$factory = service(CodesCheckServiceFactory::class);
$service = $factory->createDefault();
$service->setFiscalDriveNumber('9999078900005555');

$result = $service->check($codes);
```

### Получение детальной информации о кодах

```php
$factory = service(CodesCheckServiceFactory::class);
$service = $factory->createDefault();
$result = $service->check($codes);

foreach ($result->codes as $code) {
    echo "Код: {$code->cis}\n";
    echo "Валиден: " . ($code->valid ? 'Да' : 'Нет') . "\n";
    
    if (!$code->valid) {
        echo "Ошибка: {$code->errorMessage} ({$code->errorCode})\n";
    }
    echo "---\n";
}
```

### Проверка доступности трансграничного сервиса

```php
$factory = service(CodesCheckServiceFactory::class);
$service = $factory->createDefault();
$result = $service->check($codes);

if ($result->hasTransborderUnavailable()) {
    // Сервис проверки трансграничных кодов недоступен
    // Можно отложить проверку или использовать другой способ
}
```

---

## API Reference

[Подробная документация API](docs/api.md)

---

## Зависимости

- `beeralex.core` - базовые классы и сервисы
- `firebase/php-jwt` - работа с JWT токенами (транзитивная зависимость)

---

## Лицензия

MIT
