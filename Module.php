<?php declare(strict_types=1);
/**
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright John Flatness, Center for History and New Media, 2013-2014
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2024
 */
namespace OaiPmhRepository;

if (!class_exists('Common\TraitModule', false)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Omeka\Api\Representation\PropertyRepresentation;
use Omeka\Module\AbstractModule;

/**
 * OaiPmhRepository module class.
 */
class Module extends AbstractModule
{
    use TraitModule;

    const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);
        $this->addAclRules();
        $this->addRoutes();
    }

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $plugins = $services->get('ControllerPluginManager');
        $translate = $plugins->get('translate');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.76')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.76'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }
    }

    protected function postInstall(): void
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $settings->set('oaipmhrepository_name', $settings->get('installation_title'));
        $settings->set('oaipmhrepository_namespace_id', $this->getServerNameWithoutProtocol($services));
    }

    /**
     * Add ACL rules for this module.
     */
    protected function addAclRules(): void
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

    protected function addRoutes(): void
    {
        $serviceLocator = $this->getServiceLocator();

        $settings = $serviceLocator->get('Omeka\Settings');
        $redirect = $settings->get('oaipmhrepository_redirect_route');
        if (empty($redirect)) {
            return;
        }

        $router = $serviceLocator->get('Router');
        if (!$router instanceof \Laminas\Router\Http\TreeRouteStack) {
            return;
        }

        $router->addRoute('oai-pmh-redirect', [
            'type' => \Laminas\Router\Http\Literal::class,
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

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
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
            'oaipmhrepository.values.pre',
            [$this, 'filterOaiPmhRepositoryValuesPre'],
            // Process internal filter first.
            100
        );
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $result = $this->handleConfigFormAuto($controller);
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

    public function filterAdminDashboardPanels(Event $event): void
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

    public function filterOaiPmhRepositoryValuesPre(Event $event): void
    {
        $this->mapDublinCoreTerms($event);
        $this->mapOtherProperties($event);
    }

    protected function mapDublinCoreTerms(Event $event): void
    {
        static $genericDcterms;
        static $map;

        if ($genericDcterms === null) {
            $services = $this->getServiceLocator();
            $settings = $services->get('Omeka\Settings');
            $genericDcterms = array_diff(
                $settings->get('oaipmhrepository_generic_dcterms', ['oai_dc', 'cdwalite', 'mets', 'mods']),
                ['oai_dcterms', 'simple_xml']
            );
            $map = include __DIR__ . '/data/mappings/dc_generic.php';
        }
        if (!count($genericDcterms) || !count($map)) {
            return;
        }

        $prefix = $event->getParam('prefix');
        if (!in_array($prefix, $genericDcterms)) {
            return;
        }

        $resource = $event->getParam('resource');

        // Check if the filter is enable for the current format.
        if ($prefix === 'mets') {
            $services = $this->getServiceLocator();
            $settings = $services->get('Omeka\Settings');
            switch (get_class($resource)) {
                case \Omeka\Api\Representation\MediaRepresentation::class:
                    $dataFormat = $settings->get('oaipmhrepository_mets_data_media');
                    break;
                case \Omeka\Api\Representation\ItemRepresentation::class:
                default:
                    $dataFormat = $settings->get('oaipmhrepository_mets_data_item');
                    break;
            }
            if ($dataFormat === 'dcterms') {
                return;
            }
        }

        $values = $event->getParam('values');

        foreach ($map as $destinationTerm => $dcterms) foreach ($dcterms as $sourceTerm) {
            if (empty($values[$sourceTerm]['values'])) {
                continue;
            }
            if (empty($values[$destinationTerm]['values'])) {
                $values[$destinationTerm]['values'] = array_values($values[$sourceTerm]['values']);
            } else {
                $values[$destinationTerm]['values'] = array_merge(
                    array_values($values[$destinationTerm]['values']),
                    array_values($values[$sourceTerm]['values'])
                );
            }
        }

        $event->setParam('values', $values);
    }

    protected function mapOtherProperties(Event $event): void
    {
        static $mapping;

        if ($mapping === null) {
            /**
             * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
             * @var \Omeka\Api\Manager $api
             * @var \Omeka\Settings\Settings $settings
             * @var \Common\Stdlib\EasyMeta $easyMeta
             */
            $services = $this->getServiceLocator();
            $settings = $services->get('Omeka\Settings');
            $easyMeta = $services->get('Common\EasyMeta');
            $mapping = $settings->get('oaipmhrepository_map_properties', []);
            foreach ($mapping as $sourceTerm => $destinationTerm) {
                if ($sourceTerm === $destinationTerm
                    || empty($sourceTerm)
                    || empty($destinationTerm)
                    || is_numeric($sourceTerm)
                    || is_numeric($destinationTerm)
                    || mb_substr($sourceTerm, 0, 1) === '#'
                    || mb_substr($destinationTerm, 0, 1) === '#'
                    || strpos($sourceTerm, ':') === false
                    || strpos($destinationTerm, ':') === false
                ) {
                    unset($mapping[$sourceTerm]);
                    continue;
                }
                $propertyId = $easyMeta->propertyId($sourceTerm);
                if (!$propertyId) {
                    unset($mapping[$sourceTerm]);
                    continue;
                }
                $propertyId = $easyMeta->propertyId($destinationTerm);
                if (!$propertyId) {
                    unset($mapping[$sourceTerm]);
                    continue;
                }
            }
        }
        if (!count($mapping)) {
            return;
        }

        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $easyMeta = $services->get('Common\EasyMeta');

        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
        $resource = $event->getParam('resource');
        $template = $resource->resourceTemplate();

        $values = $event->getParam('values', []);

        // Do a double loop for quicker process. Mapping is already checked.

        foreach ($mapping as $sourceTerm => $destinationTerm) {
            if (isset($values[$destinationTerm]['values'])) {
                continue;
            }
            $propertyId = $easyMeta->propertyId($destinationTerm);
            if (!$propertyId) {
                continue;
            }
            $rtp = $template ? $template->resourceTemplateProperty($propertyId) : null;
            if ($rtp) {
                $alternateLabel = $rtp->alternateLabel();
                $alternateComment = $rtp->alternateComment();
            } else {
                $alternateLabel = null;
                $alternateComment = null;
            }
            $values[$destinationTerm] = [
                // The api manages the cache automatically via doctrine.
                'property' => $api->read('properties', $propertyId)->getContent(),
                'alternate_label' => $alternateLabel,
                'alternate_comment' => $alternateComment,
                'values' => [],
            ];
        }

        foreach ($mapping as $sourceTerm => $destinationTerm) {
            if (empty($values[$sourceTerm]['values'])) {
                continue;
            }
            if (empty($values[$destinationTerm]['values'])) {
                $values[$destinationTerm]['values'] = array_values($values[$sourceTerm]['values']);
            } else {
                $values[$destinationTerm]['values'] = array_merge(
                    array_values($values[$destinationTerm]['values']),
                    array_values($values[$sourceTerm]['values'])
                );
            }
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
