<?php declare(strict_types=1);

namespace OaiPmhRepository;

use Common\Stdlib\PsrMessage;
use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

$defaultConfig = require dirname(__DIR__, 2) . '/config/module.config.php';
$defaultSettings = $defaultConfig['oaipmhrepository']['config'];

if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.57')) {
    $message = new Message(
        $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
        'Common', '3.4.57'
    );
    throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
}

if (version_compare($oldVersion, '0.3', '<')) {
    $connection = $services->get('Omeka\Connection');
    $sql = <<<'SQL'
ALTER TABLE oai_pmh_repository_token CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE verb verb VARCHAR(190) NOT NULL, CHANGE metadata_prefix metadata_prefix VARCHAR(190) NOT NULL, CHANGE `cursor` `cursor` INT NOT NULL, CHANGE `set` `set` INT DEFAULT NULL;
DROP INDEX expiration ON oai_pmh_repository_token;
CREATE INDEX IDX_E9AC4F9524CD504D ON oai_pmh_repository_token (expiration);
SQL;
    $connection->executeStatement($sql);

    $settings->set('oaipmhrepository_name', $settings->get('oaipmh_repository_name',
        $settings->get('installation_title')));
    $settings->set('oaipmhrepository_namespace_id', $settings->get('oaipmhrepository_namespace_id',
        $this->getServerNameWithoutProtocol($services)));
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
    $settings->set('oaipmhrepository_global_repository',
        $defaultSettings['oaipmhrepository_global_repository']);
    $settings->set('oaipmhrepository_by_site_repository', 'item_set');
    $settings->set('oaipmhrepository_oai_set_format',
        $defaultSettings['oaipmhrepository_oai_set_format']);
    $settings->set('oaipmhrepository_human_interface',
        $defaultSettings['oaipmhrepository_human_interface']);
    $settings->set('oaipmhrepository_hide_empty_sets',
        $defaultSettings['oaipmhrepository_hide_empty_sets']);
}

if (version_compare($oldVersion, '3.2.2', '<')) {
    $connection = $services->get('Omeka\Connection');
    $sql = <<<'SQL'
ALTER TABLE oai_pmh_repository_token CHANGE `set` `set` VARCHAR(190) DEFAULT NULL;
SQL;
    $connection->executeStatement($sql);

    $settings->set('oaipmhrepository_append_identifier_global',
        $defaultSettings['oaipmhrepository_append_identifier_global']);
    $settings->set('oaipmhrepository_append_identifier_site',
        $defaultSettings['oaipmhrepository_append_identifier_site']);
}

if (version_compare($oldVersion, '3.3.0', '<')) {
    $settings->set('oaipmhrepository_metadata_formats',
        $defaultSettings['oaipmhrepository_metadata_formats']);
    $settings->set('oaipmhrepository_generic_dcterms',
        $defaultSettings['oaipmhrepository_generic_dcterms']);
    $settings->set('oaipmhrepository_mets_data_item',
        $defaultSettings['oaipmhrepository_mets_data_item']);
    $settings->set('oaipmhrepository_mets_data_media',
        $defaultSettings['oaipmhrepository_mets_data_media']);
}

if (version_compare($oldVersion, '3.3.5.2', '<')) {
    $message = new PsrMessage(
        'The event "oaipmhrepository.values" that may be used by other modules was deprecated and replaced by event "oaipmhrepository.values.pre".' // @translate
    );
    $messenger->addWarning($message);
    $message = new PsrMessage(
        'Futhermore, a new option allows to map any term to any other term, so any values can be exposed if needed.' // @translate
    );
    $messenger->addWarning($message);

    $settings->set(
        'oaipmhrepository_generic_dcterms',
        $settings->get('oaipmhrepository_generic_dcterms', true) ? ['oai_dc', 'cdwalite', 'mets', 'mods'] : []
    );
    $settings->set('oaipmhrepository_map_properties', $defaultSettings['oaipmhrepository_map_properties']);
}

if (version_compare($oldVersion, '3.3.5.6', '<')) {
    $message = new PsrMessage(
        'It is now possible to define oai sets with a specific list of item sets or with a list of search queries.' // @translate
    );
    $messenger->addWarning($message);
}

if (version_compare($oldVersion, '3.3.6', '<')) {
    $message = new PsrMessage(
        'A simple mapping of foaf properties to Dublin Core has been added to the default config. It allows to publish, for example, common metadata of people.' // @translate
    );
    $messenger->addSuccess($message);

    // Update the mapping if this is the original one.
    $mapProperties = $settings->get('oaipmhrepository_map_properties');
    $mapPropertiesOriginal = $defaultSettings['oaipmhrepository_map_properties'];
    if ($mapProperties
        && count($mapPropertiesOriginal) === 68
        && array_slice($mapProperties, 1, 67, true) === array_slice($mapPropertiesOriginal, 1, 67, true)
    ) {
        $settings->set('oaipmhrepository_map_properties', $mapPropertiesOriginal);
    } else {
        $message = new PsrMessage(
            'You can copy the {link}default mapping foaf to dcterms{link_end} in the config of the module if needed.', // @translate
            [
                'link' => '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-OaiPmhRepository/-/blob/master/config/module.config.php#L130" target="_blank" rel="noopener">',
                'link_end' => '</a>',
            ],
        );
        $message->setEscapeHtml(false);
        $messenger->addWarning($message);
    }

    $message = new PsrMessage(
        'An option was added to append a thumbnail url according to the non-standard %1$srecommandation%2$s of the Bibliothèque nationale de France.', // @translate
        [
            'link' => '<a href="https://www.bnf.fr/sites/default/files/2019-02/Guide_oaipmh.pdf" target="_blank" rel="noopener">',
            'link_end' => '</a>',
        ],
    );
    $message->setEscapeHtml(false);
    $messenger->addSuccess($message);

    $message = new PsrMessage(
        'The deprecated event "oaipmhrepository.values" was removed. Use "oaipmhrepository.values.pre" instead.' // @translate
    );
    $messenger->addWarning($message);

    $metadataFormats = $settings->get('oaipmhrepository_metadata_formats', []);
    $metadataFormats[] = 'simple_xml';
    $settings->set('oaipmhrepository_metadata_formats', $metadataFormats);

    $urlHelper = $services->get('ViewHelperManager')->get('url');
    $message = new PsrMessage(
        'A new output metadata format was added, "simple_xml", that contains all the values in a simple xml, not only the dublin core ones. You can disabled it in the {link}config of the module{link_end}.', // @translate
        [
            'link' => '<a href="' . $urlHelper('admin/default', ['controller' => 'module', 'action' => 'configure'], ['query' => ['id' => 'OaiPmhRepository']]) . '">',
            'link_end' => '</a>',
            ],
        );
    $message->setEscapeHtml(false);
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.4.7', '<')) {
    $sql = <<<'SQL'
ALTER TABLE `oai_pmh_repository_token`
    DROP INDEX IDX_E9AC4F9524CD504D;
SQL;
    try {
        $connection->executeStatement($sql);
    } catch (\Exception $e) {
        // Nothing.
    }
    $sql = <<<'SQL'
ALTER TABLE `oai_pmh_repository_token`
    ADD INDEX IDX_F99CFEE424CD504D (`expiration`),
    CHANGE `verb` `verb` varchar(15) NOT NULL AFTER `id`,
    RENAME TO `oaipmhrepository_token`;
SQL;
    try {
        $connection->executeStatement($sql);
    } catch (\Exception $e) {
        // Nothing.
    }

    $message = new PsrMessage(
        'Some new options were added for compliance with non-standard requirements of BnF (Bibliothèque nationale de France): thumbnail, uri without attribute, class as main type.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.4.8', '<')) {
    // In some cases, the table was not removed or updated in 3.4.7.
    $sql = <<<'SQL'
ALTER TABLE `oaipmhrepository_token`
    DROP INDEX IDX_E9AC4F9524CD504D;
SQL;
    try {
        $connection->executeStatement($sql);
    } catch (\Exception $e) {
        // Nothing.
    }
    $sql = <<<'SQL'
ALTER TABLE `oaipmhrepository_token`
    ADD INDEX IDX_F99CFEE424CD504D (`expiration`),
    CHANGE `verb` `verb` varchar(15) NOT NULL AFTER `id`;
SQL;
    try {
        $connection->executeStatement($sql);
    } catch (\Exception $e) {
        // Nothing.
    }
    $sql = <<<'SQL'
DROP TABLE `oai_pmh_repository_token`;
SQL;
    try {
        $connection->executeStatement($sql);
    } catch (\Exception $e) {
        // Nothing.
    }
}
