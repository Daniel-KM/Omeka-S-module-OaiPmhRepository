<?php
namespace OaiPmhRepository\Service\Form;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\Form\ConfigForm;
use OaiPmhRepository\OaiPmh\MetadataFormatManager;
use OaiPmhRepository\OaiPmh\OaiSetManager;
use Zend\ServiceManager\Factory\FactoryInterface;

class ConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $translator = $services->get('MvcTranslator');
        $metadataFormatManager = $services->get(MetadataFormatManager::class);
        $oaiSetManager = $services->get(OaiSetManager::class);
        $form = new ConfigForm(null, $options);
        $form->setTranslator($translator);
        $form->setMetadataFormats($metadataFormatManager->getRegisteredNames());
        $form->setOaiSetFormats($oaiSetManager->getRegisteredNames());
        return $form;
    }
}
