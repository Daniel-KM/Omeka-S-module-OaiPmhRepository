<?php

namespace OaiPmhRepository\Service\OaiPmh\OaiSet;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\OaiPmh\OaiSet\Base;
use Zend\ServiceManager\Factory\FactoryInterface;

class BaseFactory implements FactoryInterface
{
    /**
     * Prepare the base set format.
     *
     * @return Base
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $api = $container->get('Omeka\ApiManager');
        $settings = $container->get('Omeka\Settings');
        return new Base($api, $settings);
    }
}
