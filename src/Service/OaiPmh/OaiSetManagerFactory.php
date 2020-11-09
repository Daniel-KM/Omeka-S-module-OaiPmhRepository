<?php declare(strict_types=1);
namespace OaiPmhRepository\Service\OaiPmh;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OaiPmhRepository\OaiPmh\OaiSetManager;
use Omeka\Service\Exception\ConfigException;

class OaiSetManagerFactory implements FactoryInterface
{
    /**
     * Create the oai set format manager service.
     *
     * @return OaiSetManager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        if (empty($config['oaipmhrepository']['oai_set_formats'])) {
            throw new ConfigException('Missing set format configuration'); // @translate
        }

        return new OaiSetManager($container, $config['oaipmhrepository']['oai_set_formats']);
    }
}
