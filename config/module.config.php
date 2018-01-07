<?php
namespace OaiPmhRepository;

return [
    'api_adapters' => [
        'invokables' => [
            'oaipmh_repository_tokens' => Api\Adapter\OaiPmhRepositoryTokenAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'controllers' => [
        'factories' => [
            'OaiPmhRepository\Controller\Request' => Service\Controller\RequestControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            'OaiPmhRepository\MetadataFormatManager' => Service\MetadataFormatManagerFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'oai' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/oai',
                            'defaults' => [
                                '__NAMESPACE__' => 'OaiPmhRepository\Controller',
                                'controller' => 'Request',
                                'action' => 'index',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'oaipmhrepository' => [
        'metadata_formats' => [
            'invokables' => [
                'mets' => Metadata\Mets::class,
                'mods' => Metadata\Mods::class,
            ],
            'factories' => [
                'cdwalite' => Service\Metadata\CdwaLiteFactory::class,
                'oai_dc' => Service\Metadata\OaiDcFactory::class,
            ],
        ],
        /*
         * Number of individual records that can be returned in a response at
         * once.
         * Larger values will increase memory usage but reduce the number of
         * database queries and HTTP requests.  Smaller values will reduce
         * memory usage but increase the number of DB queries and requests.
         */
        'list_limit' => 50,
        /*
         * In minutes, the length of time a resumption token is valid for.
         * This means harvesters can re-try old partial list requests for
         * this amount of time.
         * Larger values will make the tokens table grow somewhat larger.
         */
        'token_expiration_time' => 10,
    ],
];
