<?php
declare(strict_types=1);

use Beeralex\Core\Config\Module\Schema\Schema;
use Beeralex\Core\Config\Module\Schema\SchemaTab;

return Schema::make()
    ->tab(
        'edit1',
        'Настройки',
        'Настройки',
        function (SchemaTab $tab) {
            $tab->input(
                'MARKING_OAUTH_KEY',
                'OAUTH_KEY - любой документ подписанный с помощью УКЭП в base64',
            );

            $tab->input(
                'MARKING_TOKEN',
                'токен полученный через лк, берется если не заполнен OAUTH_KEY'
            );

            $tab->input(
                'MARKING_DEFAULT_FISKAL_DRIVE_NUMBER',
                'ФН (Fiscal Drive Number)'
            );

            $tab->input(
                'MARKING_BASE_TEST_URL',
                'Базовый тестовый url',
                default: 'https://markirovka.sandbox.crptech.ru'
            );

            $tab->input(
                'MARKING_BASE_PROD_URL',
                'Базовый боевой url',
                default: 'https://cdn.crpt.ru'
            );

            $tab->checkbox(
                'MARKING_TEST',
                'Тестовый режим',
                checked: false
            );

            $tab->checkbox(
                'MARKING_LOGS',
                'Включить логирование',
                checked: true
            );
        }
    );