<?php
/**
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright John Flatness, Center for History and New Media, 2013-2014
 * @copyright BibLibre, 2016
 */

namespace OaiPmhRepository;

use Omeka\Module\AbstractModule;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\View\Renderer\PhpRenderer;
use Zend\ServiceManager\ServiceLocatorInterface;

define('OAI_PMH_REPOSITORY_PLUGIN_DIRECTORY', __DIR__);
define('OAI_PMH_REPOSITORY_METADATA_DIRECTORY', OAI_PMH_REPOSITORY_PLUGIN_DIRECTORY . '/src/Metadata');

/**
 * OaiPmhRepository plugin class.
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $settings = $serviceLocator->get('Omeka\Settings');
        $settings->set('oaipmh_repository_name', $settings->get('installation_title'));
        $settings->set('oaipmh_repository_namespace_id', $this->_getServerName());
        $settings->set('oaipmh_repository_namespace_expose_files', 1);

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
        $sql = '
            CREATE TABLE IF NOT EXISTS `oai_pmh_repository_token` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `verb` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
                `metadata_prefix` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
                `cursor` INT(10) UNSIGNED NOT NULL,
                `from` DATETIME DEFAULT NULL,
                `until` DATETIME DEFAULT NULL,
                `set` INT(10) UNSIGNED DEFAULT NULL,
                `expiration` DATETIME NOT NULL,
                PRIMARY KEY  (`id`),
                INDEX(`expiration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ';
        $connection->exec($sql);
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $settings = $serviceLocator->get('Omeka\Settings');
        $settings->delete('oaipmh_repository_name');
        $settings->delete('oaipmh_repository_namespace_id');
        $settings->delete('oaipmh_repository_record_limit');
        $settings->delete('oaipmh_repository_expiration_time');
        $settings->delete('oaipmh_repository_expose_files');

        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = 'DROP TABLE IF EXISTS `oai_pmh_repository_token`;';
        $connection->exec($sql);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $post = $controller->getRequest()->getPost();

        $settings->set('oaipmh_repository_name', $post['oaipmh_repository_name']);
        $settings->set('oaipmh_repository_namespace_id', $post['oaipmh_repository_namespace_id']);
        $settings->set('oaipmh_repository_expose_files', $post['oaipmh_repository_expose_files']);
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        return $renderer->render('oai-pmh-repository/config-form');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Index',
            'view.browse.after',
            [$this, 'filterAdminDashboardPanels']
        );
    }

    public function filterAdminDashboardPanels()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $sites = $api->search('sites')->getContent();

        if (!empty($sites)) {
            echo '<div class="panel">';
            echo '<h2>OAI-PMH Repository</h2>';
            echo '<p>Harvester can access metadata from these URLs: ';
            echo '<ul>';
            foreach ($sites as $site) {
                $oaiUrl = $site->siteUrl() . '/oai';
                echo '<li><a href="' . $oaiUrl . '">' . $oaiUrl . '</a></li>';
            }
            echo '</ul></p>';
            echo '</div>';
        }
    }

    private function _getServerName()
    {
        $name = preg_replace('/[^a-z0-9\-\.]/i', '', $_SERVER['SERVER_NAME']);
        if ($name == 'localhost') {
            $name = 'default.must.change';
        }

        return $name;
    }
}
