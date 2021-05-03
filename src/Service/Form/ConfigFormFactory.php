<?php declare(strict_types=1);

namespace OaiPmhRepository\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OaiPmhRepository\Form\ConfigForm;
use OaiPmhRepository\OaiPmh\MetadataFormatManager;
use OaiPmhRepository\OaiPmh\OaiSetManager;

class ConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formats = [];
        /** @var \OaiPmhRepository\OaiPmh\MetadataFormatManager $metadataFormatManager */
        $metadataFormatManager = $services->get(MetadataFormatManager::class);
        foreach ($metadataFormatManager->getRegisteredNames() as $name) {
            $format = $metadataFormatManager->get($name);
            $formats[] = $format::METADATA_PREFIX;
        }

        $form = new ConfigForm(null, $options);
        return $form
            ->setTranslator($services->get('MvcTranslator'))
            ->setMetadataFormats($formats)
            ->setOaiSetFormats($services->get(OaiSetManager::class)->getRegisteredNames());
    }
}
