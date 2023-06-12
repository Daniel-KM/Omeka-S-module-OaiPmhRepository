<?php declare(strict_types=1);
/**
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright John Flatness, Center for History and New Media, 2013-2014
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2023
 */
namespace OaiPmhRepository;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Omeka\Api\Representation\PropertyRepresentation;

/**
 * OaiPmhRepository module class.
 */
class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);
        $this->addAclRules();
        $this->addRoutes();
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

        $router->addRoute('oai-pmh-repository-request', [
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
            [$this, 'filterOaiPmhRepositoryValuesPre']
        );
        $sharedEventManager->attach(
            \OaiPmhRepository\OaiPmh\Metadata\AbstractMetadata::class,
            'oaipmhrepository.values',
            [$this, 'filterOaiPmhRepositoryValues']
        );
        $sharedEventManager->attach(
            \OaiPmhRepository\OaiPmh\Metadata\AbstractMetadata::class,
            'oaipmhrepository.strings',
            [$this, 'editOaiPmhRepositoryValues']
        );
		$sharedEventManager->attach(
            \OaiPmhRepository\OaiPmh\Metadata\AbstractMetadata::class,
            'oaipmhrepository.appendElement.pre',
            [$this, 'editOaiPmhRepositoryElementText']
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
        static $mapping;

        if (is_null($mapping)) {
            $services = $this->getServiceLocator();
            $settings = $services->get('Omeka\Settings');
            $mapping = $settings->get('oaipmhrepository_map_properties', []);
            foreach ($mapping as $sourceTerm => $destinationTerm) {
                if ($sourceTerm === $destinationTerm
                    || empty($sourceTerm)
                    || empty($destinationTerm)
                    || is_numeric($sourceTerm)
                    || is_numeric($destinationTerm)
                    || mb_substr($sourceTerm, 0, 1) === '#'
                    || mb_substr($destinationTerm, 0, 1) === '#'
                    || !strpos($sourceTerm, ':')
                    || !strpos($destinationTerm, ':')
                ) {
                    unset($mapping[$sourceTerm]);
                    continue;
                }
                $property = $this->getProperty($sourceTerm);
                if (!$property) {
                    unset($mapping[$sourceTerm]);
                    continue;
                }
                $property = $this->getProperty($destinationTerm);
                if (!$property) {
                    unset($mapping[$sourceTerm]);
                    continue;
                }
            }
        }
        if (!count($mapping)) {
            return;
        }

        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
        $resource = $event->getParam('resource');
        $template = $resource->resourceTemplate();

        $values = $event->getParam('values', []);

        // Do a double loop for quicker process. Mapping is already checked.

        foreach ($mapping as $sourceTerm => $destinationTerm) {
            if (isset($values[$destinationTerm]['values'])) {
                continue;
            }
            $property = $this->getProperty($destinationTerm);
            if (!$property) {
                continue;
            }
            $rtp = $template ? $template->resourceTemplateProperty($property->id()) : null;
            if ($rtp) {
                $alternateLabel = $rtp->alternateLabel();
                $alternateComment = $rtp->alternateComment();
            } else {
                $alternateLabel = null;
                $alternateComment = null;
            }
            $values[$destinationTerm] = [
                'property' => $property,
                'alternate_label' => $alternateLabel,
                'alternate_comment' => $alternateComment,
                'values' => [],
            ];
        }

        foreach ($mapping as $sourceTerm => $destinationTerm) {
            if (empty($values[$sourceTerm]['values'])) {
                continue;
            }
            $values[$destinationTerm]['values'] = array_merge(
                array_values($values[$destinationTerm]['values']),
                array_values($values[$sourceTerm]['values'])
            );
        }

        $event->setParam('values', $values);
    }

    public function filterOaiPmhRepositoryValues(Event $event): void
    {
        static $genericDcterms;
        static $map;

        if (is_null($genericDcterms)) {
            $services = $this->getServiceLocator();
            $settings = $services->get('Omeka\Settings');
            $genericDcterms = array_diff(
                $settings->get('oaipmhrepository_generic_dcterms', ['oai_dc', 'cdwalite', 'mets', 'mods']),
                ['oai_dcterms']
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

        $term = $event->getParam('term');
        if (empty($map[$term])) {
            return;
        }

        $values = $event->getParam('values');

        $single = !is_array($values);
        if ($single) {
            if ($values) {
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

	public function editOaiPmhRepositoryValues(Event $event): void{
		static $value_mapping;
		
		    if (is_null($value_mapping)) {
				$services = $this->getServiceLocator();
				$settings = $services->get('Omeka\Settings');
				$value_mapping = $settings->get('oaipmhrepository_map_values', []);		
				foreach ($value_mapping as $sourceValue => $destinationValue) {
					if ($sourceValue === $destinationValue
						|| mb_substr($sourceValue, 0, 1) === '#'
					) {
						unset($value_mapping[$sourceValue]);
						continue;
					}
				}
			}
        if (!count($value_mapping) && !count($value_splitting)) {
            return;
        }
		
		$string = $event->getParam('string', '');

		if (array_key_exists($string, $value_mapping)) {
			$string = $value_mapping[$string];
		}
		
		$event->setParam('string', $string);
	}


    public function editOaiPmhRepositoryElementText(Event $event): void{
		static $value_splitting;
	
		if (is_null($value_splitting)) {
			$services = $this->getServiceLocator();
			$settings = $services->get('Omeka\Settings');
			$value_splitting = $settings->get('oaipmhrepository_split_properties', []);
			foreach ($value_splitting as $sourceTerm => $delimiter) {
				if (empty($sourceTerm)
					|| empty($delimiter)
					|| is_numeric($sourceTerm)
					|| mb_substr($sourceTerm, 0, 1) === '#'
					|| mb_substr($delimiter, 0, 1) != '"'
				) {
					unset($value_splitting[$sourceTerm]);
					continue;
				}
				
				$property = $this->getProperty('dcterms:' . explode(":", $sourceTerm)[1]);
				
				if (!$property) {
					unset($value_splitting[$sourceTerm]);
					continue;
				}
			}
		}
	
        if (!count($value_splitting)) {
            return;
        }

		$string = $event->getParam('text', '');
		$property = $event->getParam('name', '');
		
		if (!array_key_exists($property, $value_splitting)) {
			return;
		}
		
		$delimiter = trim($value_splitting[$property],'"');
		
		if (!strpos($string, $delimiter)){
			return;
		}
	
		$string = explode($delimiter, $string);
			
		$event->setParam('text', $string);
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

    protected function getProperty(string $term): ?PropertyRepresentation
    {
        static $api;
        static $properties = [];

        if (!array_key_exists($term, $properties)) {
            if (is_null($api)) {
                $api = $this->getServiceLocator()->get('Omeka\ApiManager');
            }
            $props = $api->search('properties', ['term' => $term, 'limit' => 1], ['initialize' => false])->getContent();
            $properties[$term] = count($props) ? reset($props) : null;
        }

        return $properties[$term];
    }
}
