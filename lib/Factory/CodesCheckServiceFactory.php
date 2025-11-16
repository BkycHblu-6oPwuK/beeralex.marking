<?php
declare(strict_types=1);
namespace Beeralex\Marking\Factory;

use Beeralex\Marking\CodeCheckRepository;
use Bitrix\Sale\Cashbox\Cashbox;
use Bitrix\Sale\Order;
use Bitrix\Sale\Cashbox\Manager;
use Beeralex\Marking\Services\CodesCheckService;

class CodesCheckServiceFactory
{
    public function __construct(
        protected CdnServiceFactory $cdnServiceFactory
    ){}

    /**
     * Из заказа достает объект кассы и из ее настроек токен
     */
    public function createByOrder(Order $order): CodesCheckService
    {
        $token = null;
        $oauthKey = null;
        $fiscalDriveNumber = null;
        foreach ($order->getShipmentCollection() as $shipment) {
            $cashboxList = Manager::getListWithRestrictions($shipment);
            foreach ($cashboxList as $cashbox) {
                $cashboxObj = Manager::getObjectById($cashbox['ID']);
                if($cashboxObj) {
                    return $this->createByCashbox($cashboxObj);
                }
            }
        }
        return $this->createDefault($token, $oauthKey, $fiscalDriveNumber);
    }

    public function createByCashbox(Cashbox $cashbox): CodesCheckService
    {
        $oauthKey = $cashbox->getValueFromSettings('MARKING', 'OAUTH_KEY') ?: '';
        $token = $cashbox->getValueFromSettings('MARKING', 'TOKEN') ?: '';
        $fiscalDriveNumber = $cashbox->getValueFromSettings('MARKING', 'FISCAL_DRIVE_NUMBER') ?: '';
        return $this->createDefault($token, $oauthKey, $fiscalDriveNumber);
    }

    public function createDefault(?string $token = null, ?string $oauthKey = null, ?string $fiscalDriveNumber = null): CodesCheckService
    {
        return new CodesCheckService(
            cdnService: $this->cdnServiceFactory->create($token, $oauthKey), 
            codeCheckRepository: service(CodeCheckRepository::class),
            token: $token, 
            oauthKey: $oauthKey, 
            fiscalDriveNumber: $fiscalDriveNumber
        );
    }
}
