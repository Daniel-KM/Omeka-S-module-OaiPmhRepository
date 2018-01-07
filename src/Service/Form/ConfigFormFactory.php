<?php
namespace OaiPmhRepository\Service\Form;

use Interop\Container\ContainerInterface;
use OaiPmhRepository\Form\ConfigForm;
use OaiPmhRepository\OaiPmh\OaiSetManager;
use Zend\ServiceManager\Factory\FactoryInterface;

class ConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $translator = $services->get('MvcTranslator');
        $oaiSetManager = $services->get(OaiSetManager::class);
        $form = new ConfigForm(null, $options);
        $form->setTranslator($translator);
        $form->setOaiSetFormats($oaiSetManager->getRegisteredNames());
        return $form;
    }
}
