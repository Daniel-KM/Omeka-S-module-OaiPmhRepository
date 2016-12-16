<?php

namespace OaiPmhRepository\Service\Metadata;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use OaiPmhRepository\Metadata\OaiDc;

class OaiDcFactory implements FactoryInterface
{
    /**
     * Create the media ingester manager service.
     *
     * @return Manager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $settings = $container->get('Omeka\Settings');

        return new OaiDc($settings);
    }
}
