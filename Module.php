<?php
/**
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright John Flatness, Center for History and New Media, 2013-2014
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2019
 */
namespace OaiPmhRepository;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;

/**
 * OaiPmhRepository module class.
 */
class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $this->addAclRules();
        $this->addRoutes();
    }

    protected function postInstall()
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $settings->set('oaipmhrepository_name', $settings->get('installation_title'));
        $settings->set('oaipmhrepository_namespace_id', $this->getServerNameWithoutProtocol($services));
    }

    /**
     * Add ACL rules for this module.
     */
    protected function addAclRules()
    {
        /** @var \Omeka\Permissions\Acl $acl */
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl
            ->allow(
                null,
                [
                    \OaiPmhRepository\Entity\OaiPmhRepositoryToken::class,
                    \OaiPmhRepository\Api\Adapter\OaiPmhRepositoryTokenAdapter::class,
                    \OaiPmhRepository\Controller\RequestController::class,
                ]
            );
    }

    protected function addRoutes()
    {
        $serviceLocator = $this->getServiceLocator();

        $settings = $serviceLocator->get('Omeka\Settings');
        $redirect = $settings->get('oaipmhrepository_redirect_route');
        if (empty($redirect)) {
            return;
        }

        $router = $serviceLocator->get('Router');
        if (!$router instanceof \Zend\Router\Http\TreeRouteStack) {
            return;
        }

        $router->addRoute('oai-pmh-repository-request', [
            'type' => \Zend\Router\Http\Literal::class,
            'options' => [
                'route' => $redirect,
                'defaults' => [
                    '__NAMESPACE__' => 'OaiPmhRepository\Controller',
                    'controller' => Controller\RequestController::class,
                    'action' => 'redirect',
                    'oai-repository' => 'global',
                ],
            ],
        ]);
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Index',
            'view.browse.after',
            [$this, 'filterAdminDashboardPanels']
        );

        // The AbstractMetadata class is used in order to manage all formats,
        // even if OaiDcTerms doesn't require it.
        $sharedEventManager->attach(
            \OaiPmhRepository\OaiPmh\Metadata\AbstractMetadata::class,
            'oaipmhrepository.values',
            [$this, 'filterOaiPmhRepositoryValues']
        );
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $result = parent::handleConfigForm($controller);
        if (!$result) {
            return false;
        }

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        $value = $settings->get('oaipmhrepository_namespace_id');
        if (empty($value) || $value === 'localhost') {
            $settings->set('oaipmhrepository_namespace_id', 'default.must.change');
        }

        $value = $settings->get('oaipmhrepository_metadata_formats', []);
        array_unshift($value, 'oai_dc');
        $value = array_unique($value);
        $settings->set('oaipmhrepository_metadata_formats', $value);
    }

    public function filterAdminDashboardPanels(Event $event)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $api = $services->get('Omeka\ApiManager');

        $sites = $api->search('sites')->getContent();

        if (empty($sites)) {
            return;
        }
        $view = $event->getTarget();
        echo $view->partial('common/admin/oai-pmh-repository-dashboard', [
            'sites' => $sites,
            'globalRepository' => $settings->get('oaipmhrepository_global_repository'),
            'bySiteRepository' => $settings->get('oaipmhrepository_by_site_repository'),
        ]);
    }

    public function filterOaiPmhRepositoryValues(Event $event)
    {
        static $genericDcterms;

        if (is_null($genericDcterms)) {
            $services = $this->getServiceLocator();
            $settings = $services->get('Omeka\Settings');
            $genericDcterms = $settings->get('oaipmhrepository_generic_dcterms', false);
        }
        if (!$genericDcterms) {
            return;
        }

        $resource = $event->getParam('resource');

        // Manage exception for mets and dcterms.
        $prefix = $event->getParam('prefix');
        if ($prefix === 'oai_dcterms') {
            return;
        }

        if ($prefix === 'mets') {
            $services = $this->getServiceLocator();
            $settings = $services->get('Omeka\Settings');
            switch (get_class($resource)) {
                case \Omeka\Api\Representation\ItemRepresentation::class:
                default:
                    $dataFormat = $settings->get('oaipmhrepository_mets_data_item');
                    break;
                case \Omeka\Api\Representation\MediaRepresentation::class:
                    $dataFormat = $settings->get('oaipmhrepository_mets_data_media');
                    break;
            }
            if ($dataFormat === 'dcterms') {
                return;
            }
        }

        $map = include __DIR__ . '/data/mappings/dc_generic.php';
        $term = $event->getParam('term');
        if (empty($map[$term])) {
            return;
        }

        $values = $event->getParam('values');

        $single = !is_array($values);
        if ($single) {
            if (count($values)) {
                return;
            }

            foreach ($map[$term] as $refinedTerm) {
                $refinedValue = $resource->value($refinedTerm);
                if ($refinedValue) {
                    $event->setParam('values', $refinedValue);
                    return;
                }
            }

            return;
        }

        foreach ($map[$term] as $refinedTerm) {
            $refinedValues = $resource->value($refinedTerm, ['all' => true]);
            $values = array_merge($values, $refinedValues);
        }
        $event->setParam('values', $values);
    }

    protected function getServerNameWithoutProtocol($serviceLocator)
    {
        $viewHelpers = $serviceLocator->get('ViewHelperManager');
        $serverUrlHelper = $viewHelpers->get('serverUrl');

        $serverName = preg_replace('~(?:\w+://)?([^:]+)(?::\d*)?$~', '$1', $serverUrlHelper->getHost());

        $name = preg_replace('/[^a-z0-9\-\.]/i', '', $serverName);
        if (empty($name) || $name === 'localhost') {
            $name = 'default.must.change';
        }

        return $name;
    }
}
