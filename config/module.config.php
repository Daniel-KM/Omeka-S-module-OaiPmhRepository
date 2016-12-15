<?php

return [
    'controllers' => [
        'factories' => [
            'OaiPmhRepository\Controller\Request' => 'OaiPmhRepository\Service\Controller\RequestControllerFactory',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            __DIR__ . '/../src/Entity',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'oaipmh_repository_tokens' => 'OaiPmhRepository\Api\Adapter\OaiPmhRepositoryTokenAdapter',
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
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'oaipmhrepository' => [
        'formats' => [
            'cdwalite' => 'OaiPmhRepository\Metadata\CdwaLite',
            'mets' => 'OaiPmhRepository\Metadata\Mets',
            'mods' => 'OaiPmhRepository\Metadata\Mods',
            'oai_dc' => 'OaiPmhRepository\Metadata\OaiDc',
        ],
    ],
];
