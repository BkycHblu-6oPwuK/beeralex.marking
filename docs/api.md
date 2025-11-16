# API Reference

Подробная документация по классам и методам модуля `beeralex.marking`.

## CodesCheckService

Основной сервис для проверки кодов маркировки.

### Конструктор

```php
public function __construct(
    CdnService $cdnService,
    CodeCheckRepository $codeCheckRepository,
    ?string $token = null,
    ?string $oauthKey = null,
    ?string $fiscalDriveNumber = null
)
```

**Параметры:**
- `$cdnService` - сервис для работы с CDN
- `$codeCheckRepository` - репозиторий для сохранения результатов
- `$token` - токен авторизации (опционально)
- `$oauthKey` - УКЭП ключ (опционально)
- `$fiscalDriveNumber` - номер фискального накопителя (опционально)

### Методы

#### check()

Проверяет коды маркировки через API Честного ЗНАКа.

```php
public function check(array $codes): CodesCheckResult
```

**Параметры:**
- `$codes` - массив кодов маркировки для проверки

**Возвращает:** `CodesCheckResult` - результат проверки

**Исключения:**
- `RuntimeException` - если массив кодов пустой
- `ApiTooManyRequestsException` - превышен лимит запросов
- `CdnTemporarilyUnavailableException` - CDN недоступен
- `TransborderCheckServiceUnavailableException` - сервис недоступен

**Пример:**

```php
$factory = service(CodesCheckServiceFactory::class);
$service = $factory->createDefault();

try {
    $result = $service->check([
        '01046406520015652193MTf2y!%sDJx',
        '01046406520015652193MTf2y!%sABC'
    ]);
    
    if ($result->allCodesValid()) {
        echo "OK";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

#### setFiscalDriveNumber()

Устанавливает номер фискального накопителя для проверки.

```php
public function setFiscalDriveNumber(string $fiscalDriveNumber): self
```

**Параметры:**
- `$fiscalDriveNumber` - номер фискального накопителя

**Возвращает:** `self` - для цепочки вызовов

**Пример:**

```php
$factory = service(CodesCheckServiceFactory::class);
$service = $factory->createDefault();
$result = $service
    ->setFiscalDriveNumber('9999078900005555')
    ->check($codes);
```

---

## CodesCheckResult

Результат проверки кодов маркировки.

### Свойства

```php
/** @var Code[] */
public readonly array $codes;

public readonly bool $transborderServiceUnavailable;
```

### Методы

#### allCodesValid()

Проверяет, все ли коды валидны.

```php
public function allCodesValid(): bool
```

**Возвращает:** `true` если все коды валидны, иначе `false`

#### hasTransborderUnavailable()

Проверяет, доступен ли сервис проверки трансграничных кодов.

```php
public function hasTransborderUnavailable(): bool
```

#### getValidCodes()

Возвращает только валидные коды.

```php
public function getValidCodes(): array
```

**Возвращает:** массив валидных `Code` объектов

#### getInvalidCodes()

Возвращает только невалидные коды.

```php
public function getInvalidCodes(): array
```

**Возвращает:** массив невалидных `Code` объектов

**Пример:**

```php
$result = $service->check($codes);

if (!$result->allCodesValid()) {
    foreach ($result->getInvalidCodes() as $invalidCode) {
        echo "Код: {$invalidCode->cis}\n";
        echo "Ошибка: {$invalidCode->errorMessage}\n";
    }
}
```

---

## Code

Информация о проверенном коде маркировки.

### Свойства

```php
public readonly string $cis;              // Код маркировки
public readonly bool $valid;              // Валиден ли код
public readonly ?string $errorMessage;    // Сообщение об ошибке
public readonly ?string $errorCode;       // Код ошибки API
public readonly ?array $rawData;          // Полный ответ API
```

**Пример:**

```php
foreach ($result->codes as $code) {
    echo "CIS: {$code->cis}\n";
    echo "Valid: " . ($code->valid ? 'Yes' : 'No') . "\n";
    
    if (!$code->valid) {
        echo "Error: {$code->errorMessage}\n";
        echo "Code: {$code->errorCode}\n";
    }
}
```

---

## CdnService

Сервис для получения списка CDN хостов.

### Методы

#### getCdn()

Получает список CDN хостов для проверки кодов.

```php
public function getCdn(bool $withoutCache = false): Hosts
```

**Параметры:**
- `$withoutCache` - игнорировать кэш и запросить свежий список

**Возвращает:** `Hosts` - объект с списком CDN хостов

**Пример:**

```php
$cdnService = service(CdnService::class);

// С кэшем (по умолчанию)
$hosts = $cdnService->getCdn();

// Без кэша
$hosts = $cdnService->getCdn(withoutCache: true);
```

---

## Hosts

Коллекция CDN хостов.

### Свойства

```php
/** @var Host[] */
public readonly array $hosts;

public readonly bool $transborderServiceUnavailable;
```

### Методы

#### getHosts()

```php
public function getHosts(): array
```

**Возвращает:** массив `Host` объектов

**Пример:**

```php
$hosts = $cdnService->getCdn();

foreach ($hosts->getHosts() as $host) {
    echo "URL: {$host->url}\n";
}
```

---

## Host

Информация о CDN хосте.

### Свойства

```php
public readonly string $url;              // URL хоста
public readonly ?string $caption;         // Описание
public readonly ?bool $isAvailable;       // Доступен ли хост
```

---

## CodesCheckServiceFactory

Фабрика для создания сервиса проверки кодов с разными настройками.

### Методы

#### createDefault()

Создает сервис с настройками по умолчанию из модуля.

```php
public function createDefault(?string $token = null, ?string $oauthKey = null, ?string $fiscalDriveNumber = null): CodesCheckService
```

**Параметры:**
- `$token` - токен авторизации (опционально)
- `$oauthKey` - УКЭП ключ (опционально)
- `$fiscalDriveNumber` - номер ФН (опционально)

**Пример:**

```php
$factory = service(CodesCheckServiceFactory::class);
$service = $factory->createDefault();
$result = $service->check($codes);
```

#### createByCashbox()

Создает сервис с настройками из обработчика кассы.

```php
public function createByCashbox(\Bitrix\Sale\Cashbox\Cashbox $cashbox): CodesCheckService
```

**Параметры:**
- `$cashbox` - объект обработчика кассы

**Возвращает:** `CodesCheckService` с настройками из кассы

**Пример:**

```php
$factory = service(CodesCheckServiceFactory::class);
$cashbox = $payment->getField('PAY_SYSTEM')->getCashbox();
$service = $factory->createByCashbox($cashbox);
$result = $service->check($codes);
```

#### createByOrder()

Создает сервис с настройками из кассы заказа (автоматически находит кассу).

```php
public function createByOrder(\Bitrix\Sale\Order $order): CodesCheckService
```

**Параметры:**
- `$order` - объект заказа

**Возвращает:** `CodesCheckService` с настройками из кассы заказа

**Пример:**

```php
$factory = service(CodesCheckServiceFactory::class);
$order = \Bitrix\Sale\Order::load($orderId);
$service = $factory->createByOrder($order);
$result = $service->check($codes);
```

---

## CashboxSettingsTrait

Trait для добавления настроек маркировки в обработчик кассы.

### Добавляемые поля

При использовании trait в настройках кассы появляются поля:

- `MARKING_OAUTH_KEY` - УКЭП ключ для этой кассы
- `MARKING_TOKEN` - токен для этой кассы
- `MARKING_FISCAL_DRIVE_NUMBER` - номер ФН для этой кассы

### Использование

```php
<?php
namespace Your\Namespace;

use Beeralex\Marking\CashboxSettingsTrait;
use Bitrix\Sale\Cashbox\Cashbox;

class MyCashboxHandler extends Cashbox
{
    use CashboxSettingsTrait;
    
    public function check(Sale\Payment $payment)
    {
        // Настройки доступны через $this->getField()
        $oauthKey = $this->getField('MARKING_OAUTH_KEY');
        $token = $this->getField('MARKING_TOKEN');
        $fiscalDriveNumber = $this->getField('MARKING_FISCAL_DRIVE_NUMBER');
        
        // Или используйте фабрику
        $factory = service(CodesCheckServiceFactory::class);
        $service = $factory->createByCashbox($this);
    }
}
```

---

## Options

Класс настроек модуля.

### Свойства

```php
public readonly string $baseUrl;                    // URL API
public readonly string $oauthKey;                   // УКЭП ключ
public readonly string $token;                      // Токен
public readonly string $defaultFiscalDriveNumber;   // ФН по умолчанию
public readonly bool $isTest;                       // Тестовый режим
public readonly bool $logsEnable;                   // Логирование
```

### Использование

```php
$options = service(\Beeralex\Marking\Options::class);

if ($options->isTest) {
    echo "Работаем в тестовом режиме";
}

echo "Base URL: {$options->baseUrl}";
```

---

## CodeCheckRepository

Репозиторий для сохранения результатов проверки кодов в БД.

### Методы

#### save()

Сохраняет результат проверки в базу данных.

```php
public function save(CodesCheckResult $result): void
```

**Параметры:**
- `$result` - результат проверки кодов

**Пример:**

```php
$repository = service(CodeCheckRepository::class);
$result = $service->check($codes);
$repository->save($result);
```

#### findByCode()

Находит ранее проверенный код в БД.

```php
public function findByCode(string $code): ?array
```

**Параметры:**
- `$code` - код маркировки

**Возвращает:** массив с данными или `null`

**Пример:**

```php
$data = $repository->findByCode('01046406520015652193MTf2y!%sDJx');

if ($data) {
    echo "Код ранее проверялся: {$data['DATE_CREATE']}";
}
```

---

## Исключения

### CdnTemporarilyUnavailableException

Выбрасывается, когда все CDN хосты временно недоступны.

```php
namespace Beeralex\Marking\Exceptions;

class CdnTemporarilyUnavailableException extends \RuntimeException
```

### TransborderCheckServiceUnavailableException

Выбрасывается, когда сервис проверки трансграничных кодов недоступен.

```php
namespace Beeralex\Marking\Exceptions;

class TransborderCheckServiceUnavailableException extends \RuntimeException
```

### Обработка

```php
use Beeralex\Marking\Exceptions\CdnTemporarilyUnavailableException;
use Beeralex\Marking\Exceptions\TransborderCheckServiceUnavailableException;

try {
    $result = $service->check($codes);
} catch (CdnTemporarilyUnavailableException $e) {
    // Все CDN недоступны - повторить позже
    $this->scheduleRetry();
} catch (TransborderCheckServiceUnavailableException $e) {
    // Трансграничный сервис недоступен
    $this->notifyAdmin();
}
```
