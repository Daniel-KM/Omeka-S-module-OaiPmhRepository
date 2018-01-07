<?php

namespace OaiPmhRepository\Service\OaiPmh\OaiSet;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\OaiPmh\OaiSet\Basic;
use Zend\ServiceManager\Factory\FactoryInterface;

class BasicFactory implements FactoryInterface
{
    /**
     * Prepare the base set format.
     *
     * @return Basic
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $api = $container->get('Omeka\ApiManager');
        $settings = $container->get('Omeka\Settings');
        return new Basic($api, $settings);
    }
}
