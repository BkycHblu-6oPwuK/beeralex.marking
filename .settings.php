<?php

use Beeralex\Marking\CodeCheckRepository;
use Beeralex\Marking\Factory\CdnServiceFactory;
use Beeralex\Marking\Factory\CodesCheckServiceFactory;
use Beeralex\Marking\Options;
use Beeralex\Marking\Services\ClientService;

return [
    'services' => [
        'value' => [
            Options::class => [
                'className' => Options::class
            ],
            ClientService::class => [
                'className' => ClientService::class
            ],
            CodeCheckRepository::class => [
                'className' => CodeCheckRepository::class
            ],
            CdnServiceFactory::class => [
                'className' => CdnServiceFactory::class
            ],
            CodesCheckServiceFactory::class => [
                'constructor' => static function() {
                    return new CodesCheckServiceFactory(
                        cdnServiceFactory: service(CdnServiceFactory::class)
                    );
                }
            ],
        ],
    ],
];