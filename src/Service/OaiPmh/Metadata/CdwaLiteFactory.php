<?php

namespace OaiPmhRepository\Service\OaiPmh\Metadata;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\OaiPmh\Metadata\AbstractMetadata;
use OaiPmhRepository\OaiPmh\Metadata\CdwaLite;
use Zend\ServiceManager\Factory\FactoryInterface;

class CdwaLiteFactory implements FactoryInterface
{
    /**
     * Create the media ingester manager service.
     *
     * @return AbstractMetadata
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $settings = $container->get('Omeka\Settings');

        return new CdwaLite($settings);
    }
}
