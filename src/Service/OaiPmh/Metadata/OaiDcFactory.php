<?php

namespace OaiPmhRepository\Service\OaiPmh\Metadata;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\OaiPmh\Metadata\OaiDc;
use Zend\ServiceManager\Factory\FactoryInterface;

class OaiDcFactory implements FactoryInterface
{
    /**
     * Prepare the OaiDc format.
     *
     * @return OaiDc
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        $oaiSetManager = $services->get('OaiPmhRepository\OaiPmh\OaiSetManager');
        $oaiSet = $oaiSetManager->get($settings->get('oaipmhrepository_oai_set_format', 'base'));
        $metadataFormat = new OaiDc();
        $metadataFormat->setSettings($settings);
        $metadataFormat->setOaiSet($oaiSet);
        return $metadataFormat;
    }
}
