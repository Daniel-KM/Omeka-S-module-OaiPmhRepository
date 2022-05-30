<?php declare(strict_types=1);

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
        'factories' => [
            Form\ConfigForm::class => Service\Form\ConfigFormFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\RequestController::class => Service\Controller\RequestControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            OaiPmh\MetadataFormatManager::class => Service\OaiPmh\MetadataFormatManagerFactory::class,
            OaiPmh\OaiSetManager::class => Service\OaiPmh\OaiSetManagerFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'oai-pmh' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/oai',
                            'defaults' => [
                                '__NAMESPACE__' => 'OaiPmhRepository\Controller',
                                'controller' => Controller\RequestController::class,
                                'action' => 'index',
                                'oai-repository' => 'by_site',
                            ],
                        ],
                    ],
                ],
            ],
            'oai-pmh' => [
                'type' => \Laminas\Router\Http\Literal::class,
                'options' => [
                    'route' => '/oai',
                    'defaults' => [
                        '__NAMESPACE__' => 'OaiPmhRepository\Controller',
                        'controller' => Controller\RequestController::class,
                        'action' => 'index',
                        'oai-repository' => 'global',
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
            'factories' => [
                OaiPmh\Metadata\CdwaLite::class => Service\OaiPmh\Metadata\MetadataFormatFactory::class,
                OaiPmh\Metadata\Mets::class => Service\OaiPmh\Metadata\MetadataFormatFactory::class,
                OaiPmh\Metadata\Mods::class => Service\OaiPmh\Metadata\MetadataFormatFactory::class,
                OaiPmh\Metadata\OaiDc::class => Service\OaiPmh\Metadata\MetadataFormatFactory::class,
                OaiPmh\Metadata\OaiDcterms::class => Service\OaiPmh\Metadata\MetadataFormatFactory::class,
            ],
            'aliases' => [
                'cdwalite' => OaiPmh\Metadata\CdwaLite::class,
                'mets' => OaiPmh\Metadata\Mets::class,
                'mods' => OaiPmh\Metadata\Mods::class,
                'oai_dc' => OaiPmh\Metadata\OaiDc::class,
                'oai_dcterms' => OaiPmh\Metadata\OaiDcterms::class,
            ],
        ],
        'oai_set_formats' => [
            'factories' => [
                'basic' => Service\OaiPmh\OaiSet\BasicFactory::class,
            ],
        ],
        'config' => [
            'oaipmhrepository_name' => '',
            'oaipmhrepository_namespace_id' => '',
            'oaipmhrepository_metadata_formats' => [
                'oai_dc',
                'cdwalite',
                'mets',
                'mods',
                'oai_dcterms',
            ],
            'oaipmhrepository_expose_media' => true,
            'oaipmhrepository_hide_empty_sets' => true,
            'oaipmhrepository_global_repository' => 'item_set',
            'oaipmhrepository_list_item_sets' => [],
            'oaipmhrepository_sets_queries' => [],
            'oaipmhrepository_by_site_repository' => 'disabled',
            'oaipmhrepository_append_identifier_global' => 'api_url',
            'oaipmhrepository_append_identifier_site' => 'absolute_site_url',
            'oaipmhrepository_oai_set_format' => 'basic',
            'oaipmhrepository_generic_dcterms' => [
                // Of course dcterms is not included.
                'oai_dc',
                'mets',
                'cdwalite',
                'mods',
            ],
            'oaipmhrepository_map_properties' => [
                '# Quick mapping between Bibliographic Ontology and Dublin Core terms',
                'bibo:abstract' => 'dcterms:abstract',
                'bibo:affirmedBy' => 'dcterms:relation',
                'bibo:annotates' => 'dcterms:description',
                'bibo:asin' => 'dcterms:identifier',
                'bibo:chapter' => 'dcterms:format',
                'bibo:citedBy' => 'dcterms:isReferencedBy',
                'bibo:cites' => 'dcterms:references',
                'bibo:coden' => 'dcterms:identifier',
                'bibo:content' => 'dcterms:description',
                'bibo:court' => 'dcterms:spatial',
                'bibo:argued' => 'dcterms:date',
                'bibo:director' => 'dcterms:contributor',
                'bibo:distributor' => 'dcterms:publisher',
                'bibo:doi' => 'dcterms:identifier',
                'bibo:eanucc13' => 'dcterms:identifier',
                'bibo:edition' => 'dcterms:format',
                'bibo:editor' => 'dcterms:publisher',
                'bibo:eissn' => 'dcterms:identifier',
                'bibo:gtin14' => 'dcterms:identifier',
                'bibo:handle' => 'dcterms:identifier',
                'bibo:identifier' => 'dcterms:identifier',
                'bibo:interviewee' => 'dcterms:contributor',
                'bibo:interviewer' => 'dcterms:contributor',
                'bibo:isbn' => 'dcterms:identifier',
                'bibo:isbn10' => 'dcterms:identifier',
                'bibo:isbn13' => 'dcterms:identifier',
                'bibo:issn' => 'dcterms:identifier',
                'bibo:issue' => 'dcterms:format',
                'bibo:issuer' => 'dcterms:publisher',
                'bibo:lccn' => 'dcterms:identifier',
                'bibo:authorList' => 'dcterms:creator',
                'bibo:contributorList' => 'dcterms:contributor',
                'bibo:editorList' => 'dcterms:publisher',
                'bibo:locator' => 'dcterms:identifier',
                'bibo:number' => 'dcterms:format',
                'bibo:numPages' => 'dcterms:format',
                'bibo:numVolumes' => 'dcterms:format',
                'bibo:oclcnum' => 'dcterms:identifier',
                'bibo:organizer' => 'dcterms:contributor',
                'bibo:owner' => 'dcterms:provenance',
                'bibo:pageEnd' => 'dcterms:format',
                'bibo:pageStart' => 'dcterms:format',
                'bibo:pages' => 'dcterms:format',
                'bibo:performer' => 'dcterms:creator',
                'bibo:pmid' => 'dcterms:identifier',
                'bibo:prefixName' => 'dcterms:description',
                'bibo:presentedAt' => 'dcterms:relation',
                'bibo:presents' => 'dcterms:relation',
                'bibo:producer' => 'dcterms:contributor',
                'bibo:recipient' => 'dcterms:contributor',
                'bibo:reproducedIn' => 'dcterms:relation',
                'bibo:reversedBy' => 'dcterms:relation',
                'bibo:reviewOf' => 'dcterms:relation',
                'bibo:section' => 'dcterms:format',
                'bibo:shortTitle' => 'dcterms:alternative',
                'bibo:shortDescription' => 'dcterms:abstract',
                'bibo:sici' => 'dcterms:identifier',
                'bibo:degree' => 'dcterms:description',
                'bibo:status' => 'dcterms:description',
                'bibo:subsequentLegalDecision' => 'dcterms:relation',
                'bibo:suffixName' => 'dcterms:description',
                'bibo:transcriptOf' => 'dcterms:isVersionOf',
                'bibo:translationOf' => 'dcterms:isVersionOf',
                'bibo:translator' => 'dcterms:contributor',
                'bibo:upc' => 'dcterms:identifier',
                'bibo:uri' => 'dcterms:identifier',
                'bibo:volume' => 'dcterms:format',
            ],
            'oaipmhrepository_format_resource' => 'url_attr_title',
            'oaipmhrepository_format_resource_property' => 'dcterms:identifier',
            'oaipmhrepository_format_uri' => 'uri_attr_label',
            'oaipmhrepository_mets_data_item' => 'dcterms',
            'oaipmhrepository_mets_data_media' => 'dcterms',
            'oaipmhrepository_human_interface' => true,
            'oaipmhrepository_redirect_route' => '',
            'oaipmhrepository_list_limit' => 50,
            'oaipmhrepository_token_expiration_time' => 10,
        ],
        'xml' => [
            'identify' => [
                'description' => [
                    // The toolkit describes the app that manages the repository.
                    // See http://oai.dlib.vt.edu/OAI/metadata/toolkit.xsd.
                    'toolkit' => [
                        'title' => 'Omeka S OAI-PMH Repository Module',
                        'author' => [
                            'name' => 'John Flatness; Julian Maurice; Daniel Berthereau; and other contributors',
                            'email' => 'john@zerocrates.org; julian.maurice@biblibre.com; daniel.github@berthereau.net',
                            'institution' => 'RRCHNM; BibLibre;',
                        ],
                        'version' => null,
                        'toolkitIcon' => 'https://omeka.org/favicon.ico',
                        'URL' => 'https://gitlab.com/Daniel-KM/Omeka-S-module-OaiPmhRepository',
                    ],
                ],
            ],
        ],
    ],
];
