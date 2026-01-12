<?php declare(strict_types=1);

namespace OaiPmhRepositoryTest;

use CommonTest\Bootstrap;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Shared test helpers for OaiPmhRepository module tests.
 */
trait OaiPmhRepositoryTestTrait
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array List of created resource IDs for cleanup.
     */
    protected $createdResources = [];

    /**
     * Get the API manager.
     */
    protected function api(): ApiManager
    {
        return $this->getServiceLocator()->get('Omeka\ApiManager');
    }

    /**
     * Get the service locator.
     */
    protected function getServiceLocator(): ServiceLocatorInterface
    {
        if ($this->services === null) {
            $this->services = Bootstrap::getApplication()->getServiceManager();
        }
        return $this->services;
    }

    /**
     * Get the application.
     */
    protected function getApplication()
    {
        return Bootstrap::getApplication();
    }

    /**
     * Get the entity manager.
     */
    protected function getEntityManager()
    {
        return $this->getServiceLocator()->get('Omeka\EntityManager');
    }

    /**
     * Login as admin user.
     */
    protected function loginAdmin(): void
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $adapter = $auth->getAdapter();
        $adapter->setIdentity('admin@example.com');
        $adapter->setCredential('root');
        $auth->authenticate();
    }

    /**
     * Logout current user.
     */
    protected function logout(): void
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->clearIdentity();
    }

    /**
     * Create a test item with common museum/cultural heritage metadata.
     *
     * @return ItemRepresentation
     */
    protected function createTestItem(): ItemRepresentation
    {
        return $this->createItem([
            'dcterms:title' => [
                ['@value' => 'La Joconde'],
            ],
            'dcterms:alternative' => [
                ['@value' => 'Mona Lisa'],
            ],
            'dcterms:type' => [
                ['@value' => 'Peinture'],
            ],
            'dcterms:creator' => [
                ['@value' => 'Léonard de Vinci'],
            ],
            'dcterms:created' => [
                ['@value' => '1503-1519'],
            ],
            'dcterms:description' => [
                ['@value' => 'Portrait de Lisa Gherardini'],
            ],
            'dcterms:subject' => [
                ['@value' => 'Portrait'],
                ['@value' => 'Femme'],
            ],
            'dcterms:extent' => [
                ['@value' => '77 cm × 53 cm'],
            ],
            'dcterms:medium' => [
                ['@value' => 'Huile sur panneau de bois de peuplier'],
            ],
            'dcterms:publisher' => [
                ['@value' => 'Musée du Louvre'],
            ],
            'dcterms:rights' => [
                ['@value' => 'Domaine public'],
            ],
            'dcterms:spatial' => [
                ['@value' => 'Florence, Italie'],
            ],
            'dcterms:language' => [
                ['@value' => 'fr'],
            ],
        ]);
    }

    /**
     * Create a test item.
     *
     * @param array $data Item data with property terms as keys.
     * @return ItemRepresentation
     */
    protected function createItem(array $data): ItemRepresentation
    {
        $itemData = ['o:is_public' => true];
        $easyMeta = $this->getServiceLocator()->get('Common\EasyMeta');

        foreach ($data as $term => $values) {
            if (strpos($term, ':') === false) {
                $itemData[$term] = $values;
                continue;
            }

            $propertyId = $easyMeta->propertyId($term);
            if (!$propertyId) {
                continue;
            }

            $itemData[$term] = [];
            foreach ($values as $value) {
                $valueData = [
                    'type' => $value['type'] ?? 'literal',
                    'property_id' => $propertyId,
                ];
                if (isset($value['@value'])) {
                    $valueData['@value'] = $value['@value'];
                }
                if (isset($value['@id'])) {
                    $valueData['@id'] = $value['@id'];
                }
                if (isset($value['o:label'])) {
                    $valueData['o:label'] = $value['o:label'];
                }
                $itemData[$term][] = $valueData;
            }
        }

        $response = $this->api()->create('items', $itemData);
        $item = $response->getContent();
        $this->createdResources[] = ['type' => 'items', 'id' => $item->id()];

        return $item;
    }

    /**
     * Generate metadata XML for an item using a specific format.
     *
     * @param ItemRepresentation $item
     * @param string $formatPrefix Metadata format prefix (e.g., 'lido', 'oai_dc').
     * @return \DOMDocument
     */
    protected function generateMetadata(ItemRepresentation $item, string $formatPrefix): \DOMDocument
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElement('metadata');
        $doc->appendChild($root);

        $metadataFormatManager = $services->get(
            \OaiPmhRepository\OaiPmh\MetadataFormatManager::class
        );
        $format = $metadataFormatManager->get($formatPrefix);

        $params = [
            'expose_media' => true,
            'oaipmhrepository_name' => $settings->get('installation_title', 'Omeka S'),
            'format_uri' => 'uri_attr_label',
            'format_resource' => 'url_attr_title',
            'format_resource_property' => 'dcterms:identifier',
            'format_literal_striptags' => false,
            'append_identifier_global' => 'api_url',
            'append_identifier_site' => 'api_url',
            'main_site_slug' => null,
        ];
        $format->setParams($params);

        $oaiSetManager = $services->get(\OaiPmhRepository\OaiPmh\OaiSetManager::class);
        $oaiSet = $oaiSetManager->get('basic');
        $oaiSet->setSetSpecType('none');
        $format->setOaiSet($oaiSet);

        $format->appendMetadata($root, $item);

        return $doc;
    }

    /**
     * Assert that an XPath query returns results.
     *
     * @param \DOMDocument $doc
     * @param string $xpath XPath expression.
     * @param string $message Optional assertion message.
     */
    protected function assertXPathExists(
        \DOMDocument $doc,
        string $xpath,
        string $message = ''
    ): void {
        $xpathObj = new \DOMXPath($doc);
        $this->registerNamespaces($xpathObj);
        $nodes = $xpathObj->query($xpath);
        $this->assertGreaterThan(0, $nodes->length, $message ?: "XPath '$xpath' should exist");
    }

    /**
     * Assert that an XPath query returns a specific value.
     *
     * @param \DOMDocument $doc
     * @param string $xpath XPath expression.
     * @param string $expected Expected value.
     * @param string $message Optional assertion message.
     */
    protected function assertXPathEquals(
        \DOMDocument $doc,
        string $xpath,
        string $expected,
        string $message = ''
    ): void {
        $xpathObj = new \DOMXPath($doc);
        $this->registerNamespaces($xpathObj);
        $nodes = $xpathObj->query($xpath);
        $this->assertGreaterThan(0, $nodes->length, "XPath '$xpath' should exist");
        $actual = $nodes->item(0)->nodeValue;
        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Assert that an XPath query contains a specific substring.
     *
     * @param \DOMDocument $doc
     * @param string $xpath XPath expression.
     * @param string $needle Substring to find.
     * @param string $message Optional assertion message.
     */
    protected function assertXPathContains(
        \DOMDocument $doc,
        string $xpath,
        string $needle,
        string $message = ''
    ): void {
        $xpathObj = new \DOMXPath($doc);
        $this->registerNamespaces($xpathObj);
        $nodes = $xpathObj->query($xpath);
        $this->assertGreaterThan(0, $nodes->length, "XPath '$xpath' should exist");
        $actual = $nodes->item(0)->nodeValue;
        $this->assertStringContainsString($needle, $actual, $message);
    }

    /**
     * Register common XML namespaces for XPath queries.
     */
    protected function registerNamespaces(\DOMXPath $xpath): void
    {
        $xpath->registerNamespace('lido', 'http://www.lido-schema.org');
        $xpath->registerNamespace('oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
        $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $xpath->registerNamespace('dcterms', 'http://purl.org/dc/terms/');
        $xpath->registerNamespace('cdwalite', 'http://www.getty.edu/CDWA/CDWALite');
        $xpath->registerNamespace('mets', 'http://www.loc.gov/METS/');
        $xpath->registerNamespace('mods', 'http://www.loc.gov/mods/v3');
        $xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    }

    /**
     * Clean up created resources after test.
     */
    protected function cleanupResources(): void
    {
        foreach ($this->createdResources as $resource) {
            try {
                $this->api()->delete($resource['type'], $resource['id']);
            } catch (\Exception $e) {
                // Ignore errors during cleanup.
            }
        }
        $this->createdResources = [];
    }
}
