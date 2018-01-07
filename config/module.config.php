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
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
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
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
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
        'settings' => [
            'oaipmhrepository_name' => '',
            'oaipmhrepository_namespace_id' => '',
            'oaipmhrepository_expose_media' => true,
            'oaipmhrepository_list_limit' => 50,
            'oaipmhrepository_token_expiration_time' => 10,
        ],
    ],
];
