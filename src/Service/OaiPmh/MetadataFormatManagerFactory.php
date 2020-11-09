<?php
namespace OaiPmhRepository\Service\OaiPmh;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\OaiPmh\MetadataFormatManager;
use Omeka\Service\Exception\ConfigException;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MetadataFormatManagerFactory implements FactoryInterface
{
    /**
     * Create the oai metadata format manager service.
     *
     * @return MetadataFormatManager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        if (empty($config['oaipmhrepository']['metadata_formats'])) {
            throw new ConfigException('Missing metadata format configuration'); // @translate
        }

        return new MetadataFormatManager($container, $config['oaipmhrepository']['metadata_formats']);
    }
}
