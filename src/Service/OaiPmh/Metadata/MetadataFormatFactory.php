<?php declare(strict_types=1);

namespace OaiPmhRepository\Service\OaiPmh\Metadata;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OaiPmhRepository\OaiPmh\Metadata\AbstractMetadata;

class MetadataFormatFactory implements FactoryInterface
{
    /**
     * Prepare the metadata format.
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null): AbstractMetadata
    {
        $plugins = $services->get('ControllerPluginManager');
        $settings = $services->get('Omeka\Settings');
        $oaiSetManager = $services->get('OaiPmhRepository\OaiPmh\OaiSetManager');
        $oaiSet = $oaiSetManager->get($settings->get('oaipmhrepository_oai_set_format', 'basic'));

        /** @var \OaiPmhRepository\OaiPmh\Metadata\AbstractMetadata $metadataFormat */
        $metadataFormat = new $requestedName();
        $prefix = $metadataFormat->getMetadataPrefix();

        $isGlobalRepository = !$plugins->get('params')->fromRoute('__SITE__', false);

        $mainSite = $settings->get('default_site');
        if ($mainSite) {
            $mainSite = $plugins->get('api')->searchOne('sites', ['id' => $mainSite], ['responseContent' => 'resource'])->getContent();
        }

        $formatLiteralStripTags = $settings->get('oaipmhrepository_format_literal_striptags', [
            'oai_dc',
            'oai_dcterms',
            'mets',
            'cdwalite',
            'mods',
            // 'simple_xml',
        ]);

        $params = [
            'main_site_slug' => empty($mainSite) ? null : $mainSite->getSlug(),
            'expose_media' => (bool) $settings->get('oaipmhrepository_expose_media'),
            'append_identifier_global' => $settings->get('oaipmhrepository_append_identifier_global', 'absolute_site_url'),
            'append_identifier_site' => $settings->get('oaipmhrepository_append_identifier_site', 'absolute_site_url'),
            'format_literal_striptags' => in_array($prefix, $formatLiteralStripTags),
            'format_resource' => $settings->get('oaipmhrepository_format_resource', 'url_attr_title'),
            'format_resource_property' => $settings->get('oaipmhrepository_format_resource_property', 'dcterms:identifier'),
            'format_uri' => $settings->get('oaipmhrepository_format_uri', 'uri_attr_label'),
        ];

        switch ($prefix) {
            case 'oai_dc':
                $classType = $settings->get('oaipmhrepository_oai_dc_class_type', 'no') ?: 'no';
                if ($classType === 'table') {
                    $classTypeTable = $settings->get('oaipmhrepository_oai_table_class_type');
                    $viewHelpers = $services->get('ViewHelperManager');
                    $classTypeTable = $viewHelpers->has('table') ? $viewHelpers->get('table')($classTypeTable) : null;
                    $classType = $classTypeTable ? 'table' : 'no';
                } else {
                    $classTypeTable = null;
                }
                $params['oai_dc'] = [
                    'bnf_vignette' => $settings->get('oaipmhrepository_oai_dc_bnf_vignette', 'none') ?: 'none',
                    'class_type' => $classType,
                    'class_type_table' => $classTypeTable,
                ];
                break;
            case 'oai_dcterms':
                $classType = $settings->get('oaipmhrepository_oai_dcterms_class_type', 'no') ?: 'no';
                if ($classType === 'table') {
                    $classTypeTable = $settings->get('oaipmhrepository_oai_table_class_type');
                    $viewHelpers = $services->get('ViewHelperManager');
                    $classTypeTable = $viewHelpers->has('table') ? $viewHelpers->get('table')($classTypeTable) : null;
                    $classType = $classTypeTable ? 'table' : 'no';
                } else {
                    $classTypeTable = null;
                }
                $params['oai_dcterms'] = [
                    'bnf_vignette' => $settings->get('oaipmhrepository_oai_dcterms_bnf_vignette', 'none') ?: 'none',
                    'class_type' => $classType,
                    'class_type_table' => $classTypeTable,
                ];
                break;
            case 'mets':
                $params['mets'] = [
                    'data_item' => $settings->get('oaipmhrepository_mets_data_item', 'dcterms'),
                    'data_media' => $settings->get('oaipmhrepository_mets_data_media', 'dcterms'),
                ];
                break;
            case 'simple_xml':
                $api = $services->get('Omeka\ApiManager');
                $vocabulariesPrefixes = $api->search('vocabularies', ['sort_by' => 'prefix', 'sort_order' => 'asc'], ['returnScalar' => 'prefix'])->getContent();
                $vocabulariesNamespaceUri = $api->search('vocabularies', ['sort_by' => 'prefix', 'sort_order' => 'asc'], ['returnScalar' => 'namespaceUri'])->getContent();
                // Prepend omeka namespace to append resource metadata.
                // Keep dcterms first and include dctype for resource classes.
                $vocabularies = [
                    'o' => 'http://omeka.org/s/vocabs/o#',
                    'dcterms' => 'http://purl.org/dc/terms/',
                    'dctype' => 'http://purl.org/dc/dcmitype/',
                ] + array_combine($vocabulariesPrefixes, $vocabulariesNamespaceUri);
                $params['simple_xml'] = [
                    'vocabularies' => $vocabularies,
                ];
                $params['attribute_title'] = 'o:title';
                break;
            default:
                // nothing.
                break;
        }

        $metadataFormat
            ->setServices($services)
            ->setOaiSet($oaiSet)
            ->setIsGlobalRepository($isGlobalRepository)
            ->setParams($params)
            ->setEventManager($services->get('EventManager'));
        return $metadataFormat;
    }
}
