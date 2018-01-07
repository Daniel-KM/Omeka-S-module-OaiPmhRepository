<?php

namespace OaiPmhRepository\Service\OaiPmh\Metadata;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\OaiPmh\Metadata\Mets;
use Zend\ServiceManager\Factory\FactoryInterface;

class MetsFactory implements FactoryInterface
{
    /**
     * Prepare the Mets format.
     *
     * @return Mets
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        $oaiSetManager = $services->get('OaiPmhRepository\OaiPmh\OaiSetManager');
        $oaiSet = $oaiSetManager->get($settings->get('oaipmhrepository_oai_set_format', 'base'));
        $metadataFormat = new Mets();
        $metadataFormat->setSettings($settings);
        $metadataFormat->setOaiSet($oaiSet);
        return $metadataFormat;
    }
}
