<?php declare(strict_types=1);

namespace OaiPmhRepository\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;
use Omeka\Form\Element as OmekaElement;

class ConfigForm extends Form
{
    /**
     * @var array
     */
    protected $metadataFormats;

    /**
     * @var array
     */
    protected $oaiSetFormats;

    public function init(): void
    {
        $this
            ->add([
                'name' => 'oaipmhrepository_name',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Repository name', // @translate
                    'info' => 'Name for this OAI-PMH repository.', // @translate
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_name',
                    'required' => true,
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_namespace_id',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Namespace identifier', // @translate
                    'info' => 'This will be used to form globally unique IDs for the exposed metadata items. This value is required to be a domain name you have registered. Using other values will generate invalid identifiers.', // @translate
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_namespace_id',
                    'required' => true,
                ],
            ]);

        $valueOptions = $this->getMetadataFormats();
        $valueOptions = array_combine($valueOptions, $valueOptions);
        $this
            ->add([
                'name' => 'oaipmhrepository_metadata_formats',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Metadata formats', // @translate
                    'info' => 'The format that will be made available. oai_dc is required.', // @translate
                    'value_options' => $valueOptions,
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_metadata_formats',
                    'required' => 'true',
                    'class' => 'chosen-select',
                    'multiple' => true,
                    'data-placeholder' => 'Select formats', // @translate
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_expose_media',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Expose media', // @translate
                    'info' => 'Whether the plugin should include identifiers for the files associated with items. This provides harvesters with direct access to files.', // @translate
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_expose_media',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_hide_empty_sets',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Hide empty oai sets', // @translate
                    'info' => 'Whether the module should hide empty oai sets.', // @translate
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_hide_empty_sets',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_global_repository',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Global repository', // @translate
                    'info' => 'The global repository contains all the resources of Omeka S, in one place. Note that the oai set identifiers are different (item set id or site id).', // @translate
                    'value_options' => [
                        'disabled' => 'Disabled', // @translate
                        'none' => 'Without oai sets', // @translate
                        'item_set' => 'With item sets as oai sets', // @translate
                        'list_item_sets' => 'With the list of item sets below', // @translate
                        'queries' => 'With dynamic sets defined by queries below', // @translate
                        'site_pool' => 'With sites as oai sets', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_global_repository',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_list_item_sets',
                'type' => OmekaElement\ItemSetSelect::class,
                'options' => [
                    'label' => 'List of item sets for the global repository', // @translate
                    'empty_option' => '',
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_list_item_sets',
                    'multiple' => true,
                    'required' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select item sets…', // @translate
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_sets_queries',
                    'type' => OmekaElement\ArrayTextarea::class,
                    'options' => [
                        'label' => 'Dynamic sets based on advanced search queries', // @translate
                        'as_key_value' => true,
                    ],
                    'attributes' => [
                        'id' => 'oaipmhrepository_sets_queries',
                        'required' => false,
                        'placeholder' => 'Articles = resource_template_id[]=2
Books = resource_template_id[]=3
',
                        'rows' => 5,
                    ],
                ])
            ->add([
                'name' => 'oaipmhrepository_by_site_repository',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Site repositories', // @translate
                    'info' => 'The site repositories simulate multiple oai servers, with the site pools of items and the attached item sets as oai sets.', // @translate
                    'value_options' => [
                        'disabled' => 'Disabled', // @translate
                        'none' => 'Without oai sets', // @translate
                        'item_set' => 'With item sets as oai sets', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_by_site_repository',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_append_identifier_global',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Add identifier for global repository', // @translate
                    'info' => 'An identifier may be added to simplify harvests, in particular when there is no unique identifier (ark, noid, call number, etc.). Only one identifier may be added and it can be the api url or a site specific url. Some formats add their own identifier and other ones skip this option.', // @translate
                    'value_options' => [
                        'disabled' => 'None', // @translate
                        'api_url' => 'Api url', // @translate
                        'relative_site_url' => 'Relative site url', // @translate
                        'absolute_site_url' => 'Absolute site url', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_append_identifier_global',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_append_identifier_site',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Add identifier for site repositories', // @translate
                    'info' => 'An identifier may be added to simplify harvests, in particular when there is no unique identifier (ark, noid, call number, etc.). Only one identifier may be added and it can be the api url or a site specific url. Some formats add their own identifier and other ones skip this option.', // @translate
                    'value_options' => [
                        'disabled' => 'None', // @translate
                        'api_url' => 'Api url', // @translate
                        'relative_site_url' => 'Relative site url', // @translate
                        'absolute_site_url' => 'Absolute site url', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_append_identifier_site',
                ],
            ]);

        $valueOptions = $this->getOaiSetFormats();
        $valueOptions = array_combine($valueOptions, array_map('ucfirst', $valueOptions));
        $this
            ->add([
                'name' => 'oaipmhrepository_oai_set_format',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Oai set format', // @translate
                    'info' => 'The format of the oai set identifiers.', // @translate
                    'value_options' => $valueOptions,
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_oai_set_format',
                    'required' => 'true',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_generic_dcterms',
                'type' => Element\MultiCheckbox::class,
                'options' => [
                    'label' => 'Genericize dcterms for specific formats', // @translate
                    'info' => 'Use refined terms for Dublin Core elements, for example dcterms:abstract will be merged with dc:description. It allows to expose all metadata in the standard oai_dc. For other merges, the event "oaipmhrepository.values.pre" can be used.', // @translate
                    'value_options' => [
                        'oai_dc' => 'oai_dc',
                        'mets' => 'mets',
                        'cdwalite' => 'cdwalite',
                        'mods' => 'mods',
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_generic_dcterms',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_map_properties',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'Map properties', // @translate
                    'info' => 'Map any property to any other property, so they will be available in other formats, in particular "oai_dcterms" and "oai_dc".', // @translate
                    'as_key_value' => true,
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_map_properties',
                    'placeholder' => 'bibo:shortTitle = dcterms:alternative',
                    'rows' => 5,
                ],
            ])

            ->add([
                'name' => 'oaipmhrepository_format_resource',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Format of linked resources', // @translate
                    'value_options' => [
                        'url_attr_title' => 'Omeka url as text and title as attribute', // @translate
                        'title_attr_url' => 'Title as text and Omeka url as attribute', // @translate
                        'url_title' => 'Omeka url and title', // @translate
                        'title' => 'Title', // @translate
                        'url' => 'Omeka url', // @translate
                        'url_as_text' => 'Omeka url without attribute (BnF compliance)', // @translate
                        'identifier' => 'Identifier (property below)', // @translate
                        'id' => 'Id', // @translate
                        'identifier_id' => 'Identifier or id', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_format_resource',
                    'value' => 'url_attr_title',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_format_resource_property',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
                    'label' => 'Property for linked resources', // @translate
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_format_resource_property',
                    'multiple' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select property…', // @translate
                    'value' => 'dcterms:identifier',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_format_uri',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Format of uri', // @translate
                    'value_options' => [
                        'uri_attr_label' => 'Uri as text and label as attribute', // @translate
                        'label_attr_uri' => 'Label as text and uri as attribute', // @translate
                        'uri_label' => 'Uri and label separated by a space', // @translate
                        'uri' => 'Uri only', // @translate
                        'uri_as_text' => 'Uri only as text (BnF compliance: no attribute for uri)', // @translate
                        'html' => 'Html', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_format_uri',
                    'value' => 'uri_attr_label',
                ],
            ])

            ->add([
                'name' => 'oaipmhrepository_oai_dc_class_type',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Dublin Core: Add the class as Dublin Core type', // @translate
                    'info' => 'For compliance with non-standard requirements of BnF, use a table to map to main types.',
                    'documentation' => 'https://www.bnf.fr/sites/default/files/2019-02/Guide_oaipmh.pdf',
                    'value_options' => [
                        'no' => 'No', // @translate
                        'term' => 'Term', // @translate
                        'local' => 'Local name', // @translate
                        'label' => 'Label', // @translate
                        'table' => 'Map via module Table (for example map class to "text", "image", "sound", "video")', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_oai_dc_class_type',
                    'value' => 'no',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_oai_dcterms_class_type',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Dublin Core terms: Add the class as Dublin Core type', // @translate
                    'value_options' => [
                        'no' => 'No', // @translate
                        'term' => 'Term', // @translate
                        'local' => 'Local name', // @translate
                        'label' => 'Label', // @translate
                        'table' => 'Map via module Table', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_oai_dcterms_class_type',
                    'value' => 'no',
                ],
            ])
        ;
        if (class_exists('Table\Form\Element\TablesSelect')) {
            $this
                ->add([
                    'name' => 'oaipmhrepository_oai_table_class_type',
                    'type' => \Table\Form\Element\TablesSelect::class,
                    'options' => [
                        'label' => 'Dublin Core: Table to use when option above is "main type"', // @translate
                    ],
                    'attributes' => [
                        'id' => 'oaipmhrepository_oai_table_class_type',
                    ],
                ]);
        }

        $this
            ->add([
                'name' => 'oaipmhrepository_oai_dc_bnf_vignette',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Dublin Core: Append the url of the thumbnail for BnF', // @translate
                    'info' => 'For compliance with the non-standard recommandations of the Bibliothèque nationale de France, the url of the main thumbnail may be automatically included to records.', // @translate
                    'documentation' => 'https://www.bnf.fr/sites/default/files/2019-02/Guide_oaipmh.pdf',
                    'value_options' => [
                        'none' => 'None', // @translate
                        'large' => 'Large', // @translate
                        'medium' => 'Medium', // @translate
                        'square' => 'Square', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_oai_dc_bnf_vignette',
                    'value' => 'none',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_oai_dcterms_bnf_vignette',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Dublin Core terms: Append the url of the thumbnail', // @translate
                    'value_options' => [
                        'none' => 'None', // @translate
                        'large' => 'Large', // @translate
                        'medium' => 'Medium', // @translate
                        'square' => 'Square', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_oai_dcterms_bnf_vignette',
                    'value' => 'none',
                ],
            ])

            ->add([
                'name' => 'oaipmhrepository_mets_data_item',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Mets: data format for item', // @translate
                    'info' => 'The format of the metadata of item.', // @translate
                    'value_options' => [
                        'dc' => 'Dublin Core',
                        'dcterms' => 'Dublin Core terms',
                        // TODO Use mods inside mets.
                        // 'mods' => 'Mods',
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_mets_data_item',
                    'value' => 'dcterms',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_mets_data_media',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Mets: data format for media', // @translate
                    'info' => 'The format of the metadata of media.', // @translate
                    'value_options' => [
                        'dc' => 'Dublin Core',
                        'dcterms' => 'Dublin Core terms',
                        // TODO Use mods inside mets.
                        // 'mods' => 'Mods',
                    ],
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_mets_data_media',
                    'value' => 'dcterms',
                ],
            ])

            ->add([
                'name' => 'oaipmhrepository_human_interface',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Human interface', // @translate
                    'info' => 'The OAI-PMH pages can be displayed with a themable responsive human interface based on Bootstrap (https://getbootstrap.com).', // @translate
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_human_interface',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_redirect_route',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Global repository redirect route', // @translate
                    'info' => 'An alias (redirect 301) for backward compatibility with Omeka Classic, that used "/oai-pmh-repository/request", or any other old OAI-PMH repository.', // @translate
                ],
                'attributes' => [
                    'id' => 'oaipmhrepository_redirect_route',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_list_limit',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'List limit', // @translate
                    'info' => 'Number of individual records that can be returned in a response at once. Larger values will increase memory usage but reduce the number of database queries and HTTP requests. Smaller values will reduce memory usage but increase the number of DB queries and requests.', // @translate
                ],
                'attributes' => [
                    'min' => '1',
                ],
            ])
            ->add([
                'name' => 'oaipmhrepository_token_expiration_time',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Token expiration time', // @translate
                    'info' => 'In minutes, the length of time a resumption token is valid for. This means harvesters can re-try old partial list requests for this amount of time. Larger values will make the tokens table grow somewhat larger.', // @translate
                ],
                'attributes' => [
                    'min' => '1',
                ],
            ]);

        $this->getInputFilter()
            ->add([
                'name' => 'oaipmhrepository_generic_dcterms',
                'required' => false,
            ])
            ->add([
                'name' => 'oaipmhrepository_list_item_sets',
                'required' => false,
            ])
        ;
    }

    public function setMetadataFormats(array $metadataFormats): self
    {
        $this->metadataFormats = $metadataFormats;
        return $this;
    }

    public function getMetadataFormats(): array
    {
        return $this->metadataFormats;
    }

    public function setOaiSetFormats(array $oaiSetFormats): self
    {
        $this->oaiSetFormats = $oaiSetFormats;
        return $this;
    }

    public function getOaiSetFormats(): array
    {
        return $this->oaiSetFormats;
    }
}
