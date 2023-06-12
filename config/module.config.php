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
                OaiPmh\Metadata\SimpleXml::class => Service\OaiPmh\Metadata\MetadataFormatFactory::class,
            ],
            'aliases' => [
                'cdwalite' => OaiPmh\Metadata\CdwaLite::class,
                'mets' => OaiPmh\Metadata\Mets::class,
                'mods' => OaiPmh\Metadata\Mods::class,
                'oai_dc' => OaiPmh\Metadata\OaiDc::class,
                'oai_dcterms' => OaiPmh\Metadata\OaiDcterms::class,
                'simple_xml' => OaiPmh\Metadata\SimpleXml::class,
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
                'simple_xml',
            ],
            'oaipmhrepository_expose_media' => true,
            'oaipmhrepository_hide_empty_sets' => true,
            'oaipmhrepository_global_repository' => 'item_set',
            'oaipmhrepository_list_item_sets' => [],
            'oaipmhrepository_sets_queries' => [],
            'oaipmhrepository_by_site_repository' => 'disabled',
            'oaipmhrepository_append_identifier_global' => 'absolute_site_url',
            'oaipmhrepository_append_identifier_site' => 'absolute_site_url',
            'oaipmhrepository_oai_set_format' => 'basic',
            'oaipmhrepository_generic_dcterms' => [
                // Of course dcterms is not included.
                'oai_dc',
                'mets',
                'cdwalite',
                'mods',
                'simple_xml',
            ],
            'oaipmhrepository_map_properties' => [
                '# Quick mapping between Bibliographic Ontology (bibo) and Dublin Core terms. See https://www.bibliontology.com/',
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

                // Alert: Warning, the version on http://xmlns.com/foaf/0.1/ is outdated (2004)! Use the 2014 one on archive.org.
                '# Quick mapping between Friend of a Friend (foaf) and Dublin Core terms. See https://web.archive.org/web/20220620163542/http://xmlns.com/foaf/spec/20140114.html',
                'foaf:account' => 'dcterms:identifier', // Online Accounts / IM // Social Web
                'foaf:accountName' => 'dcterms:identifier', // Online Accounts / IM // Social Web
                'foaf:accountServiceHomepage' => 'dcterms:references', // Online Accounts / IM // Social Web
                'foaf:age' => 'dcterms:extent', // Personal Info // Core / Agent
                'foaf:aimChatID' => 'dcterms:identifier', // Online Accounts / IM
                'foaf:based_near' => 'dcterms:spatial', // Personal Info // Core / Agent
                'foaf:birthday' => 'dcterms:issued', // Personal Info
                'foaf:currentProject' => 'dcterms:requires', // Personal Info // Social Web
                'foaf:depiction' => 'dcterms:description', // Basics // Core / Agent
                'foaf:depicts' => 'dcterms:description', // Basics // Core / Agent
                'foaf:dnaChecksum' => 'dcterms:identifier', // Personal Info // archaic
                'foaf:family_name' => 'dcterms:title', // Basics // archaic
                'foaf:familyName' => 'dcterms:title', // Basics // Core / Agent
                'foaf:firstName' => 'dcterms:title', // Basics
                'foaf:focus' => 'dcterms:subject', // Personal Info // Linked Data utiliies
                'foaf:fundedBy' => 'dcterms:isReferencedBy', // Projects and Groups // archaic
                'foaf:geekcode' => 'dcterms:abstract', // Personal Info // archaic
                'foaf:gender' => 'dcterms:format', // Personal Info
                'foaf:givenName' => 'dcterms:title', // Basics // Core / Agent
                'foaf:givenname' => 'dcterms:title', // Basics // archaic
                'foaf:holdsAccount' => 'dcterms:identifier', // Online Accounts / IM // archaic
                'foaf:homepage' => 'dcterms:references', // Basics // Social Web
                'foaf:icqChatID' => 'dcterms:identifier', // Online Accounts / IM
                'foaf:img' => 'dcterms:hasFormat', // Basics // Core / Agent
                'foaf:interest' => 'dcterms:subject', // Personal Info // Social Web
                'foaf:isPrimaryTopicOf' => 'dcterms:isReferencedBy', // Documents and Images // Core / Agent
                'foaf:jabberID' => 'dcterms:identifier', // Online Accounts / IM // Social Web
                'foaf:knows' => 'dcterms:relation', // Personal Info // Core / Agent
                'foaf:lastName' => 'dcterms:title', // Basics
                'foaf:logo' => 'dcterms:hasFormat', // Documents and Images // Social Web
                'foaf:made' => 'dcterms:isReferencedBy', // Documents and Images // Core / Agent
                'foaf:maker' => 'dcterms:creator', // Documents and Images // Core / Agent
                'foaf:mbox' => 'dcterms:identifier', // Basics // Social Web
                'foaf:mbox_sha1sum' => 'dcterms:identifier', // Basics // Social Web
                'foaf:member' => 'dcterms:isPartOf', // Projects and Groups // Core / Project
                'foaf:membershipClass' => 'dcterms:isPartOf', // Projects and Groups
                'foaf:msnChatID' => 'dcterms:identifier', // Online Accounts / IM
                'foaf:myersBriggs' => 'dcterms:abstract', // Personal Info
                'foaf:name' => 'dcterms:title', // Basics // Core / Agent
                'foaf:nick' => 'dcterms:alternative', // Basics // Social Web
                'foaf:openid' => 'dcterms:identifier', // Online Accounts / IM // Social Web
                'foaf:page' => 'dcterms:references', // Documents and Images // Social Web
                'foaf:pastProject' => 'dcterms:requires', // Personal Info // Social Web
                'foaf:phone' => 'dcterms:identifier', // Basics
                'foaf:plan' => 'dcterms:abstract', // Personal Info
                'foaf:primaryTopic' => 'dcterms:subject', // Documents and Images // Core / Agent
                'foaf:publications' => 'dcterms:references', // Personal Info // Social Web
                'foaf:schoolHomepage' => 'dcterms:references', // Personal Info // Social Web
                'foaf:sha1' => 'dcterms:identifier', // Documents and Images // Social Web
                'foaf:skypeID' => 'dcterms:identifier', // Online Accounts / IM
                'foaf:status' => 'dcterms:medium', // Personal Info
                'foaf:surname' => 'dcterms:alternative', // Basics // archaic
                'foaf:theme' => 'dcterms:subject', // Projects and Groups // archaic
                'foaf:thumbnail' => 'dcterms:hasFormat', // Documents and Images // Social Web
                'foaf:tipjar' => 'dcterms:identifier', // Documents and Images // Social Web
                'foaf:title' => 'dcterms:title', // Basics // Core / Agent
                'foaf:topic' => 'dcterms:subject', // Documents and Images // Social Web
                'foaf:topic_interest' => 'dcterms:subject', // Personal Info // Social Web
                'foaf:weblog' => 'dcterms:references', // Personal Info // Social Web
                'foaf:workInfoHomepage' => 'dcterms:references', // Personal Info // Social Web
                'foaf:workplaceHomepage' => 'dcterms:references', // Personal Info // Social Web
                'foaf:yahooChatID' => 'dcterms:identifier', // Online Accounts / IM
            ],
            'oaipmhrepository_map_values' => '',
            //'oaipmhrepository_split_properties' => '',
            'oaipmhrepository_format_resource' => 'url_attr_title',
            'oaipmhrepository_format_resource_property' => 'dcterms:identifier',
            'oaipmhrepository_format_uri' => 'uri_attr_label',
            'oaipmhrepository_oai_dc_bnf_vignette' => 'none',
            'oaipmhrepository_oai_dcterms_bnf_vignette' => 'none',
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
                            'email' => 'john@zerocrates.org; julian.maurice@biblibre.com; daniel.git@berthereau.net',
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
