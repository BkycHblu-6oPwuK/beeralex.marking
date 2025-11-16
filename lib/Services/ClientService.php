<?php
declare(strict_types=1);
namespace Beeralex\Marking\Services;

use Beeralex\Core\Exceptions\ApiClientException;
use Beeralex\Core\Exceptions\ApiClientUnauthorizedException;
use Beeralex\Core\Exceptions\ApiTooManyRequestsException;
use Beeralex\Core\Service\Api\ClientService as CoreClientService;
use Beeralex\Marking\Exceptions\CdnTemporarilyUnavailableException;
use Beeralex\Marking\Exceptions\TransborderCheckServiceUnavailableException;

class ClientService extends CoreClientService
{
    protected function handleResult(): void
    {
        $status = $this->getStatus();
        if ($status === 401) throw new ApiClientUnauthorizedException('Client unauthorized');
        if ($status === 429) throw new ApiTooManyRequestsException("rate limit exceeded");
        if ($status >= 500 && $status < 600) {
            $errorCode = \Bitrix\Main\Web\Json::decode($this->getResult())['code'] ?? null;

            if ($errorCode == 5000) {
                throw new TransborderCheckServiceUnavailableException("Transborder check service is unavailable");
            }

            throw new CdnTemporarilyUnavailableException("CDN temporarily unavailable");
        }
        if (!$this->isSuccess()) throw new ApiClientException('HTTP Request Failed');
    }
}
