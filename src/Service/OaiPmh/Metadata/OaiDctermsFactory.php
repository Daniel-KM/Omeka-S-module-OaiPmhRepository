<?php declare(strict_types=1);

namespace OaiPmhRepository\Service\OaiPmh\Metadata;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OaiPmhRepository\OaiPmh\Metadata\OaiDcterms;

class OaiDctermsFactory implements FactoryInterface
{
    /**
     * Prepare the OaiDcterms format.
     *
     * @return OaiDcterms
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        $oaiSetManager = $services->get('OaiPmhRepository\OaiPmh\OaiSetManager');
        $oaiSet = $oaiSetManager->get($settings->get('oaipmhrepository_oai_set_format', 'base'));

        $metadataFormat = new OaiDcterms();
        $metadataFormat->setEventManager($services->get('EventManager'));
        $metadataFormat->setSettings($settings);
        $metadataFormat->setOaiSet($oaiSet);
        $isGlobalRepository = !$services->get('ControllerPluginManager')
            ->get('params')->fromRoute('__SITE__', false);
        $metadataFormat->setIsGlobalRepository($isGlobalRepository);
        $metadataFormat
            ->setParams([
                'format_resource' => $settings->get('oaipmhrepository_format_resource', 'url_attr_title'),
                'format_resource_property' => $settings->get('oaipmhrepository_format_resource_property', 'dcterms:identifier'),
                'format_uri' => $settings->get('oaipmhrepository_format_uri', 'uri_attr_label'),
            ]);
        return $metadataFormat;
    }
}
