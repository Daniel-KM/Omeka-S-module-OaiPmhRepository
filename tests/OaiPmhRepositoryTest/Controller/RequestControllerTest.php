<?php declare(strict_types=1);

namespace OaiPmhRepositoryTest\Controller;

use CommonTest\AbstractHttpControllerTestCase;
use CommonTest\Bootstrap;
use OaiPmhRepositoryTest\OaiPmhRepositoryTestTrait;

/**
 * Test OAI-PMH request controller.
 */
class RequestControllerTest extends AbstractHttpControllerTestCase
{
    use OaiPmhRepositoryTestTrait;

    protected $site;
    protected $itemSet;
    protected $item;

    public function setUp(): void
    {
        parent::setUp();

        $this->loginAdmin();

        $response = $this->api()->create('item_sets', [
            'o:is_public' => true,
        ]);
        $this->itemSet = $response->getContent();

        $response = $this->api()->create('items', [
            'o:is_public' => true,
            'dcterms:title' => [
                [
                    'type' => 'literal',
                    'property_id' => $this->getPropertyId('dcterms:title'),
                    '@value' => 'Fahrenheit 451',
                ],
            ],
        ]);
        $this->item = $response->getContent();

        $response = $this->api()->create('sites', [
            'o:title' => 'Test site',
            'o:slug' => 'test',
            'o:theme' => 'default',
            'o:is_public' => true,
            'o:site_item_set' => [
                [
                    'o:item_set' => ['o:id' => $this->itemSet->id()],
                ],
            ],
        ]);
        $this->site = $response->getContent();

        $this->settings()->set('oaipmhrepository_namespace_id', 'test');
        $this->settings()->set('oaipmhrepository_global_repository', 'none');
        $this->settings()->set('oaipmhrepository_by_site_repository', 'item_set');
        $this->settings()->set('oaipmhrepository_hide_empty_sets', false);

        $this->reset();

        $_SERVER['REQUEST_URI'] = '/';
    }

    public function tearDown(): void
    {
        try {
            $this->api()->delete('sites', $this->site->id());
        } catch (\Exception $e) {
        }
        try {
            $this->api()->delete('item_sets', $this->itemSet->id());
        } catch (\Exception $e) {
        }
        try {
            $this->api()->delete('items', $this->item->id());
        } catch (\Exception $e) {
        }
        $this->cleanupResources();
    }

    protected function settings()
    {
        return $this->getApplicationServiceLocator()->get('Omeka\Settings');
    }

    protected function getPropertyId($term)
    {
        $response = $this->api()->search('properties', [
            'term' => $term,
        ]);
        $property = $response->getContent();

        if (!empty($property)) {
            return $property[0]->id();
        }
        return null;
    }

    public function testIndexAction(): void
    {
        $this->dispatch('/s/test/oai');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertEquals((string) $xml->error['code'], 'badVerb');
    }

    public function testIdentifyVerb(): void
    {
        $this->dispatch('/s/test/oai?verb=Identify');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasProperty('Identify', $xml);
    }

    public function testListMetadataFormatsVerb(): void
    {
        $this->dispatch('/s/test/oai?verb=ListMetadataFormats');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasProperty('ListMetadataFormats', $xml);

        $formats = [];
        foreach ($xml->ListMetadataFormats->metadataFormat as $metadataFormat) {
            $formats[(string) $metadataFormat->metadataPrefix] = true;
        }
        $this->assertArrayHasKey('cdwalite', $formats);
        $this->assertArrayHasKey('lido', $formats);
        $this->assertArrayHasKey('mets', $formats);
        $this->assertArrayHasKey('mods', $formats);
        $this->assertArrayHasKey('oai_dc', $formats);
    }

    public function testListIdentifiersVerbLido(): void
    {
        $this->dispatch('/s/test/oai?verb=ListIdentifiers&metadataPrefix=lido');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasProperty('ListIdentifiers', $xml);
        $expectedIdentifier = 'oai:test:' . $this->item->id();
        $this->assertEquals($expectedIdentifier, (string) $xml->ListIdentifiers->header->identifier);
    }

    public function testGetRecordVerbLido(): void
    {
        $itemIdentifier = 'oai:test:' . $this->item->id();
        $this->dispatch("/s/test/oai?verb=GetRecord&metadataPrefix=lido&identifier=$itemIdentifier");
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasProperty('GetRecord', $xml);
        $content = $this->getResponse()->getContent();
        $this->assertStringContainsString('lido:lido', $content);
        $this->assertStringContainsString('lido:lidoRecID', $content);
        $this->assertStringContainsString('lido:descriptiveMetadata', $content);
        $this->assertStringContainsString('lido:administrativeMetadata', $content);
    }

    public function testListRecordsVerbLido(): void
    {
        $this->dispatch('/s/test/oai?verb=ListRecords&metadataPrefix=lido');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasProperty('ListRecords', $xml);
        $content = $this->getResponse()->getContent();
        $this->assertStringContainsString('lido:lido', $content);
    }

    public function testListSetsVerb(): void
    {
        $this->dispatch('/s/test/oai?verb=ListSets');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasProperty('ListSets', $xml);
        $this->assertEquals((string) $this->itemSet->id(), (string) $xml->ListSets->set->setSpec);
    }

    public function testListIdentifiersVerbBadArgument(): void
    {
        $this->dispatch('/s/test/oai?verb=ListIdentifiers');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertEquals('badArgument', (string) $xml->error['code']);
    }

    public function testListIdentifiersVerbOaiDc(): void
    {
        $this->dispatch('/s/test/oai?verb=ListIdentifiers&metadataPrefix=oai_dc');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasProperty('ListIdentifiers', $xml);
        $expectedIdentifier = 'oai:test:' . $this->item->id();
        $this->assertEquals($expectedIdentifier, (string) $xml->ListIdentifiers->header->identifier);
    }

    public function testGetRecordVerbOaiDc(): void
    {
        $itemIdentifier = 'oai:test:' . $this->item->id();
        $this->dispatch("/s/test/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier=$itemIdentifier");
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasProperty('GetRecord', $xml);
        $metadata = $xml->GetRecord->record->metadata;
        $dc = $metadata->children('oai_dc', true)->dc;
        $title = (string) $dc->children('dc', true)->title;
        $expectedTitle = (string) $this->item->value('dcterms:title');
        $this->assertEquals($expectedTitle, $title);
    }

    public function testListRecordsVerbOaiDc(): void
    {
        $this->dispatch('/s/test/oai?verb=ListRecords&metadataPrefix=oai_dc');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasProperty('ListRecords', $xml);
        $metadata = $xml->ListRecords->record->metadata;
        $dc = $metadata->children('oai_dc', true)->dc;
        $title = (string) $dc->children('dc', true)->title;
        $expectedTitle = (string) $this->item->value('dcterms:title');
        $this->assertEquals($expectedTitle, $title);
    }
}
