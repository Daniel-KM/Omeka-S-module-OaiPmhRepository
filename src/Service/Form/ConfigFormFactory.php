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
        $form = new ConfigForm(null, $options);
        $oaiSetManager = $services->get(OaiSetManager::class);
        $form->setOaiSetFormats($oaiSetManager->getRegisteredNames());
        return $form;
    }
}
