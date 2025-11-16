<?php
declare(strict_types=1);
namespace Beeralex\Marking\Services;

use Beeralex\Core\Service\Api\ApiService as CoreApiService;
use Beeralex\Marking\Options;

/**
 * @property-read Options $options
 * @property-read ClientService $clientService
 */
abstract class ApiService extends CoreApiService
{
    public function __construct()
    {
        parent::__construct(service(Options::class), service(ClientService::class));

        if ($this->options->isTest) {
            $this->clientService->disableSslVerification();
        }
    }

    public function logsEnabled(): bool
    {
        return $this->options->logsEnable;
    }
}
