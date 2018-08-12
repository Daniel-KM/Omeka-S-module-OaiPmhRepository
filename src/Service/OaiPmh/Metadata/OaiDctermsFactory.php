<?php

namespace OaiPmhRepository\Service\OaiPmh\Metadata;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\OaiPmh\Metadata\OaiDcterms;
use Zend\ServiceManager\Factory\FactoryInterface;

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
        return $metadataFormat;
    }
}
