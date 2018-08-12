<?php
/**
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright John Flatness, Center for History and New Media, 2013-2014
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2018
 */
namespace OaiPmhRepository;

use OaiPmhRepository\Form\ConfigForm;
use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

/**
 * OaiPmhRepository module class.
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $this->addAclRules();
        $this->addRoutes();
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');

        /* Table: Stores currently active resumptionTokens

           id: primary key (also the value of the token)
           verb: Verb of original request
           metadata_prefix: metadataPrefix of original request
           cursor: Position of cursor within result set
           from: Optional from argument of original request
           until: Optional until argument of original request
           set: Optional set argument of original request
           expiration: Datestamp after which token is expired
        */
        $sql = <<<'SQL'
CREATE TABLE oai_pmh_repository_token (
    `id` INT AUTO_INCREMENT NOT NULL,
    `verb` VARCHAR(190) NOT NULL,
    `metadata_prefix` VARCHAR(190) NOT NULL,
    `cursor` INT NOT NULL,
    `from` DATETIME DEFAULT NULL,
    `until` DATETIME DEFAULT NULL,
    `set` VARCHAR(190) DEFAULT NULL,
    `expiration` DATETIME NOT NULL,
    INDEX IDX_E9AC4F9524CD504D (`expiration`),
    PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
SQL;
        $connection->exec($sql);

        $settings = $serviceLocator->get('Omeka\Settings');
        $this->manageSettings($settings, 'install');
        $settings->set('oaipmhrepository_name', $settings->get('installation_title'));
        $settings->set('oaipmhrepository_namespace_id', $this->getServerNameWithoutProtocol($serviceLocator));
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $sql = <<<'SQL'
SET foreign_key_checks = 0;
DROP TABLE IF EXISTS oai_pmh_repository_token;
SET foreign_key_checks = 1;
SQL;
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec($sql);

        $this->manageSettings($serviceLocator->get('Omeka\Settings'), 'uninstall');
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        require_once 'data/scripts/upgrade.php';
    }

    protected function manageSettings($settings, $process, $key = 'config')
    {
        $config = require __DIR__ . '/config/module.config.php';
        $defaultSettings = $config[strtolower(__NAMESPACE__)][$key];
        foreach ($defaultSettings as $name => $value) {
            switch ($process) {
                case 'install':
                    $settings->set($name, $value);
                    break;
                case 'uninstall':
                    $settings->delete($name);
                    break;
            }
        }
    }

    /**
     * Add ACL rules for this module.
     */
    protected function addAclRules()
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(null, Entity\OaiPmhRepositoryToken::class);
        $acl->allow(null, Api\Adapter\OaiPmhRepositoryTokenAdapter::class);
        $acl->allow(null, Controller\RequestController::class);
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
            'type' => 'Literal',
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

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(ConfigForm::class);

        $data = [];
        $defaultSettings = $config[strtolower(__NAMESPACE__)]['config'];
        foreach ($defaultSettings as $name => $value) {
            $data[$name] = $settings->get($name);
        }

        $form->init();
        $form->setData($data);
        $html = $renderer->formCollection($form);
        return $html;
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');

        $params = $controller->getRequest()->getPost();

        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $form->init();
        $form->setData($params);
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        $data = $form->getData();

        $defaultSettings = $config[strtolower(__NAMESPACE__)]['config'];
        foreach ($data as $name => $value) {
            if (array_key_exists($name, $defaultSettings)) {
                if ($name === 'oaipmhrepository_namespace_id' && $value === 'localhost') {
                    $value = 'default.must.change';
                } elseif ($name === 'oaipmhrepository_metadata_formats') {
                    $value[] = 'oai_dc';
                    $value = array_unique($value);
                }
                $settings->set($name, $value);
            }
        }
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
            $refinedValues = $resource->value($refinedTerm, ['all' => true, 'default' => []]);
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
