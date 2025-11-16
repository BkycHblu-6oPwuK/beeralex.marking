<?php
declare(strict_types=1);
namespace Beeralex\Marking\Factory;

use Beeralex\Marking\Services\CdnService;

class CdnServiceFactory
{
    public function create(?string $token = null, ?string $oauthKey = null): CdnService
    {
        return new CdnService($token, $oauthKey);
    }
}
