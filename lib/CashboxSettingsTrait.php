<?php

namespace Itb\Marking;

trait CashboxSettingsTrait
{
    /**
     * Добавляет настройки для кассы.
     * Эти настройки будут отображаться в админке для конкретного обработчика кассы.
     *
     * @return array
     */
    public static function getSettings($modelId = 0)
    {
        $settings = parent::getSettings($modelId);

        $settings['MARKING'] = [
            'LABEL' => 'Маркировка',
            'ITEMS' => [
                'OAUTH_KEY' => [
                    'TYPE' => 'STRING',
                    'LABEL' => 'OAuth ключ - любой документ подписанный с помощью УКЭП в base64',
                ],
                'TOKEN' => [
                    'TYPE' => 'STRING',
                    'LABEL' => 'Token - токен полученный через лк',
                ],
                'FISCAL_DRIVE_NUMBER' => [
                    'TYPE' => 'STRING',
                    'LABEL' => 'ФН (Fiscal Drive Number)',
                ],
            ],
        ];

        return $settings;
    }
}
