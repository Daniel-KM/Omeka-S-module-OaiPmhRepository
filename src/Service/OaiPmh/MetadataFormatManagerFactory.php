<?php declare(strict_types=1);
namespace OaiPmhRepository\Service\OaiPmh;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OaiPmhRepository\OaiPmh\MetadataFormatManager;
use Omeka\Service\Exception\ConfigException;

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
