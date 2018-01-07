<?php
/**
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright John Flatness, Center for History and New Media, 2013-2014
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2017
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
 * OaiPmhRepository plugin class.
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
    id INT AUTO_INCREMENT NOT NULL,
    verb VARCHAR(190) NOT NULL,
    metadata_prefix VARCHAR(190) NOT NULL,
    `cursor` INT NOT NULL,
    `from` DATETIME DEFAULT NULL,
    until DATETIME DEFAULT NULL,
    `set` INT DEFAULT NULL,
    expiration DATETIME NOT NULL,
    INDEX IDX_E9AC4F9524CD504D (expiration),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
SQL;
        $connection->exec($sql);

        $settings = $serviceLocator->get('Omeka\Settings');
        $this->manageSettings($settings, 'install');
        $settings->set('oaipmhrepository_name', $settings->get('installation_title'));
        $settings->set('oaipmhrepository_namespace_id', $this->getServerName($serviceLocator));
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
        if (version_compare($oldVersion, '0.3', '<')) {
            $connection = $serviceLocator->get('Omeka\Connection');
            $sql = <<<'SQL'
ALTER TABLE oai_pmh_repository_token CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE verb verb VARCHAR(190) NOT NULL, CHANGE metadata_prefix metadata_prefix VARCHAR(190) NOT NULL, CHANGE `cursor` `cursor` INT NOT NULL, CHANGE `set` `set` INT DEFAULT NULL;
DROP INDEX expiration ON oai_pmh_repository_token;
CREATE INDEX IDX_E9AC4F9524CD504D ON oai_pmh_repository_token (expiration);
SQL;
            $connection->exec($sql);

            $config = require __DIR__ . '/config/module.config.php';
            $defaultSettings = $config[strtolower(__NAMESPACE__)]['settings'];
            $settings = $serviceLocator->get('Omeka\Settings');

            $settings->set('oaipmhrepository_name', $settings->get('oaipmh_repository_name',
                $settings->get('installation_title')));
            $settings->set('oaipmhrepository_namespace_id', $settings->get('oaipmhrepository_namespace_id',
                $this->getServerName($serviceLocator)));
            $settings->set('oaipmhrepository_expose_media', $settings->get('oaipmh_repository_namespace_expose_files',
                $defaultSettings['oaipmhrepository_expose_media']));
            $settings->set('oaipmhrepository_list_limit',
                $defaultSettings['oaipmhrepository_list_limit']);
            $settings->set('oaipmhrepository_token_expiration_time',
                $defaultSettings['oaipmhrepository_token_expiration_time']);

            $settings->delete('oaipmh_repository_name');
            $settings->delete('oaipmh_repository_namespace_id');
            $settings->delete('oaipmh_repository_namespace_expose_files');
            $settings->delete('oaipmh_repository_record_limit');
            $settings->delete('oaipmh_repository_list_limit');
            $settings->delete('oaipmh_repository_expiration_time');
            $settings->delete('oaipmh_repository_token_expiration_time');
        }

        if (version_compare($oldVersion, '0.3.1', '<')) {
            $config = require __DIR__ . '/config/module.config.php';
            $defaultSettings = $config[strtolower(__NAMESPACE__)]['settings'];
            $settings = $serviceLocator->get('Omeka\Settings');

            $settings->set('oaipmhrepository_global_repository',
                $defaultSettings['oaipmhrepository_global_repository']);
            $settings->set('oaipmhrepository_by_site_repository', 'all');
        }
    }

    protected function manageSettings($settings, $process, $key = 'settings')
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

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Index',
            'view.browse.after',
            [$this, 'filterAdminDashboardPanels']
        );
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        $formElementManager = $services->get('FormElementManager');

        $data = [];
        $defaultSettings = $config[strtolower(__NAMESPACE__)]['settings'];
        foreach ($defaultSettings as $name => $value) {
            $data[$name] = $settings->get($name);
        }

        $form = $formElementManager->get(ConfigForm::class);
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

        $form = $this->getServiceLocator()->get('FormElementManager')
            ->get(ConfigForm::class);
        $form->init();
        $form->setData($params);
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        $defaultSettings = $config[strtolower(__NAMESPACE__)]['settings'];
        foreach ($params as $name => $value) {
            if (isset($defaultSettings[$name])) {
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

    protected function getServerName($serviceLocator)
    {
        $viewHelpers = $serviceLocator->get('ViewHelperManager');
        $serverUrlHelper = $viewHelpers->get('serverUrl');
        $serverName = $serverUrlHelper->getHost();

        $name = preg_replace('/[^a-z0-9\-\.]/i', '', $serverName);
        if (empty($name) || $name == 'localhost') {
            $name = 'default.must.change';
        }

        return $name;
    }
}
