# beeralex.marking
# Интеграция с api честный знак

модуль для bitrix

Минимальная версия php 8.1

# Установка

добавьте в composer.json экстра опцию, чтобы композер поставил пакет в local/modules

```json
"extra": {
  "installer-paths": {
    "local/modules/{$name}/": ["type:bitrix-module"]
  }
}
```

```bash
composer require beeralex/beeralex.marking
```

1. Установить модуль beeralex.core
2. Установить этот модуль
3. Получить документ подписанный УКЭП
4. Заполнить настройки модуля в админке, oauthKey - любой документ подписанный с помощью УКЭП в base64

Для использования разных токенов наследуйте обработчики касс и используйте трейт - ``` Beeralex\Marking\CashboxSettingsTrait ```, который добавит настройки oauth ключа, токена и Fiscal Drive Number в настройки обработчика кассы. 

# Использование

Валидация кодов:

```php
try {
    /** @var \Beeralex\Marking\Entity\Codes\CodesCheckResult $checkResult */
    $codes = ["mark_code1", "mark_code2"];
    $checkResult = (new \Beeralex\Marking\Services\CodesCheck())->check($codes);
    // или
    Itb\Marking\CodesCheckFactory::getByOrder($order)|getByCashbox($cashbox)|getDefault()
} catch (\Exception $e) {
    // Вероятнее всего ошибка в запросе - коды не проверены.
}
```
Itb\Marking\CodesCheckFactory::getByOrder принимает объект заказа, на основе отгрузки находится подходящая касса применяя исключения из админки и вызывается getByCashbox
Itb\Marking\CodesCheckFactory::getByCashbox принимает объект обработчика кассы, и находит заполненные настройки в кассе (TOKEN, FISCAL_DRIVE_NUMBER, OAUTH_KEY), эти значения используются вместо дефолтных из настроек модуля

При ошибках логирование идет в ```{dir_module}/logs/``` при включенном логировании в ```Beeralex\Marking\Options::$logsEnable```

Объект результата проверки ```\Beeralex\Marking\Entity\Codes\CodesCheckResult```:
- public readonly string $code - Результат обработки операции
- public readonly string $description - Текстовое описание результата выполнения метода
- public readonly \Beeralex\Marking\Entity\Codes\Code[] $codes - Список КМ
- public readonly string $reqId - Уникальный идентификатор запроса
- public readonly int $reqTimestamp - Дата и время формирования запроса
- public readonly bool $transborderServiceUnavailable - доступность сервиса трансграничной проверки кодов

Объект ```\Beeralex\Marking\Entity\Codes\Code```:
- public readonly bool $verified - Результат проверки валидности структуры КМ
- И другие поля - подробнее в самом классе

Получение результата проверки из БД:
```php
$codes = ["mark_code1", "mark_code2"];
/** @var null|CodesCheckResult[] сгруппированы по id запросов для каждого кода*/
$checkResult = (new \Beeralex\Marking\CodeCheckRepository)->findByCisList($codes);
$isValid = $checkResult?->get("mark_code1")?->verified
```

Или через метод ```findByCis```
```php
/** @var null|\Beeralex\Marking\Entity\Codes\CodesCheckResult */
$checkResult = (new \Beeralex\Marking\CodeCheckRepository)->findByCis("mark_code1");
$isValid = $checkResult?->get("mark_code1")?->verified
```

Далее при печате чека, на примере атол, добавить результат проверки в запрос. Создаете свой обработчик кассы и используйте trait ``` Beeralex\Marking\Traits\MarkingCashboxTrait ```
