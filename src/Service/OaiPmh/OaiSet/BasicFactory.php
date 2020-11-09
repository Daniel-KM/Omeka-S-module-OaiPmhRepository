<?php declare(strict_types=1);

namespace OaiPmhRepository\Service\OaiPmh\OaiSet;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OaiPmhRepository\OaiPmh\OaiSet\Basic;

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
