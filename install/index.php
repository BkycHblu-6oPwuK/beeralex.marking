<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Beeralex\Marking\CodeCheckResponseTable;
use Beeralex\Marking\CodeCheckTable;

Loc::loadMessages(__FILE__);

class beeralex_marking extends CModule
{

    public function __construct()
    {
        if (is_file(__DIR__ . '/version.php')) {
            include_once(__DIR__ . '/version.php');
            $this->MODULE_ID           = 'beeralex.marking';
            $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
            $this->MODULE_NAME         = Loc::getMessage('BEERALEX_MARKING_NAME');
            $this->MODULE_DESCRIPTION  = Loc::getMessage('BEERALEX_MARKING_DESCRIPTION');
            $this->PARTNER_NAME = 'beeralex';
            $this->PARTNER_URI = '#';
        } else {
            CAdminMessage::showMessage(
                Loc::getMessage('BEERALEX_MARKING_FILE_NOT_FOUND') . ' version.php'
            );
        }
    }

    public function DoInstall()
    {
        global $APPLICATION;
        if ($this->checkRequirements()) {
            ModuleManager::registerModule($this->MODULE_ID);
            Loader::includeModule($this->MODULE_ID);
            $this->InstallDB();
        } else {
            $APPLICATION->ThrowException('Нет поддержки d7 в главном модуле или не установлен модуль beeralex.core');
        }
        $APPLICATION->IncludeAdminFile(
            'Установка модуля',
            __DIR__ . '/step.php'
        );
    }

    public function checkRequirements(): bool
    {
        return version_compare(ModuleManager::getVersion('main'), '14.00.00') >= 0 && Loader::includeModule('beeralex.core');
    }

    public function InstallDB()
    {
        CodeCheckTable::createTable();
        CodeCheckResponseTable::createTable();
    }

    public function UnInstallDB()
    {
        CodeCheckTable::dropTable();
        CodeCheckResponseTable::dropTable();
    }

    public function doUninstall()
    {
        global $APPLICATION;

        $context = \Bitrix\Main\Context::getCurrent();
        $request = $context->getRequest();
        Loader::includeModule($this->MODULE_ID);
        if ($request['step'] < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage('BEERALEX_MARKING_UNINSTALL_TITLE'), __DIR__ . '/unstep1.php');
        } else {
            if ($request['savedata'] !== 'Y') {
                $this->UnInstallDB();
            }

            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(Loc::getMessage('BEERALEX_MARKING_UNISTALL_TITLE'), __DIR__ . '/unstep2.php');
        }
    }
}
