<?php

namespace OaiPmhRepository\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Omeka\Service\Exception\ConfigException;
use OaiPmhRepository\Metadata\Manager;

class MetadataFormatManagerFactory implements FactoryInterface
{
    /**
     * Create the media ingester manager service.
     *
     * @return Manager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        if (!isset($config['oaipmhrepository']['metadata_formats'])) {
            throw new ConfigException('Missing metadata format configuration');
        }

        return new Manager($container, $config['oaipmhrepository']['metadata_formats']);
    }
}
