<?php

namespace Itb\Marking;

use Bitrix\Sale\Cashbox\Cashbox;
use Bitrix\Sale\Order;
use Bitrix\Sale\Cashbox\Manager;
use Itb\Marking\Services\CodesCheck;

class CodesCheckFactory
{
    /**
     * Из заказа достает объект кассы и из ее настроек токен
     */
    public static function getByOrder(Order $order): CodesCheck
    {
        $token = null;
        $oauthKey = null;
        $fiscalDriveNumber = null;
        foreach ($order->getShipmentCollection() as $shipment) {
            $cashboxList = Manager::getListWithRestrictions($shipment);
            foreach ($cashboxList as $cashbox) {
                $cashboxObj = Manager::getObjectById($cashbox['ID']);
                if($cashboxObj) {
                    return static::getByCashbox($cashboxObj);
                }
            }
        }
        return static::getDefault($token, $oauthKey, $fiscalDriveNumber);
    }

    public static function getByCashbox(Cashbox $cashbox): CodesCheck
    {
        $oauthKey = $cashbox->getValueFromSettings('MARKING', 'OAUTH_KEY') ?: '';
        $token = $cashbox->getValueFromSettings('MARKING', 'TOKEN') ?: '';
        $fiscalDriveNumber = $cashbox->getValueFromSettings('MARKING', 'FISCAL_DRIVE_NUMBER') ?: '';
        return static::getDefault($token, $oauthKey, $fiscalDriveNumber);
    }

    public static function getDefault(?string $token = null, ?string $oauthKey = null, ?string $fiscalDriveNumber = null): CodesCheck
    {
        return new CodesCheck(token: $token, oauthKey: $oauthKey, fiscalDriveNumber: $fiscalDriveNumber);
    }
}
