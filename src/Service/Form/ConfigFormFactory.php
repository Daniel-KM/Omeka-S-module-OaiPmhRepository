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
        $translator = $services->get('MvcTranslator');
        $metadataFormatManager = $services->get(MetadataFormatManager::class);
        $oaiSetManager = $services->get(OaiSetManager::class);
        $form = new ConfigForm(null, $options);
        $form
            ->setTranslator($translator)
            ->setMetadataFormats($metadataFormatManager->getRegisteredNames())
            ->setOaiSetFormats($oaiSetManager->getRegisteredNames());
        return $form;
    }
}
