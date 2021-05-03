<?php declare(strict_types=1);

namespace OaiPmhRepository\Service\OaiPmh\Metadata;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MetadataFormatFactory implements FactoryInterface
{
    /**
     * Prepare the metadata format.
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null): \OaiPmhRepository\OaiPmh\Metadata\MetadataInterface
    {
        $plugins = $services->get('ControllerPluginManager');
        $settings = $services->get('Omeka\Settings');
        $oaiSetManager = $services->get('OaiPmhRepository\OaiPmh\OaiSetManager');
        $oaiSet = $oaiSetManager->get($settings->get('oaipmhrepository_oai_set_format', 'base'));

        $metadataFormat = new $requestedName();
        $metadataFormat->setEventManager($services->get('EventManager'));
        $metadataFormat->setSettings($settings);
        $metadataFormat->setOaiSet($oaiSet);
        $isGlobalRepository = !$plugins->get('params')->fromRoute('__SITE__', false);
        $metadataFormat->setIsGlobalRepository($isGlobalRepository);

        $mainSite = $settings->get('default_site');
        if ($mainSite) {
            $mainSite = $plugins->get('api')->searchOne('sites', ['id' => $mainSite], ['responseContent' => 'resource'])->getContent();
        }

        $metadataFormat
            ->setParams([
                'main_site_slug' => empty($mainSite) ? null : $mainSite->getSlug(),
                'expose_media' => (bool) $settings->get('oaipmhrepository_expose_media'),
                'append_identifier_global' => $settings->get('oaipmhrepository_append_identifier_global', 'api_url'),
                'append_identifier_site' => $settings->get('oaipmhrepository_append_identifier_site', 'api_url'),
                'format_resource' => $settings->get('oaipmhrepository_format_resource', 'url_attr_title'),
                'format_resource_property' => $settings->get('oaipmhrepository_format_resource_property', 'dcterms:identifier'),
                'format_uri' => $settings->get('oaipmhrepository_format_uri', 'uri_attr_label'),
                'mets_data_item' => $settings->get('oaipmhrepository_mets_data_item'),
                'mets_data_media' => $settings->get('oaipmhrepository_mets_data_media'),
            ]);
        return $metadataFormat;
    }
}
