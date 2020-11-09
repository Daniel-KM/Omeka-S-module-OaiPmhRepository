<?php declare(strict_types=1);

namespace OaiPmhRepositoryTest\Controller;

use OmekaTestHelper\Controller\OmekaControllerTestCase;

class RequestControllerTest extends OmekaControllerTestCase
{
    protected $site;
    protected $itemSet;
    protected $item;

    public function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();

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

        $this->resetApplication();

        $_SERVER['REQUEST_URI'] = '/';
    }

    public function tearDown(): void
    {
        $this->api()->delete('sites', $this->site->id());
        $this->api()->delete('item_sets', $this->itemSet->id());
        $this->api()->delete('items', $this->item->id());
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

        $this->assertObjectHasAttribute('Identify', $xml);
    }

    public function testListMetadataFormatsVerb(): void
    {
        $this->dispatch('/s/test/oai?verb=ListMetadataFormats');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasAttribute('ListMetadataFormats', $xml);

        $formats = [];
        foreach ($xml->ListMetadataFormats->metadataFormat as $metadataFormat) {
            $formats[(string) $metadataFormat->metadataPrefix] = true;
        }
        $this->assertArrayHasKey('cdwalite', $formats);
        $this->assertArrayHasKey('mets', $formats);
        $this->assertArrayHasKey('mods', $formats);
        $this->assertArrayHasKey('oai_dc', $formats);
    }

    public function testListSetsVerb(): void
    {
        $this->dispatch('/s/test/oai?verb=ListSets');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasAttribute('ListSets', $xml);
        $this->assertEquals((string) $xml->ListSets->set->setSpec, $this->itemSet->id());
    }

    public function testListIdentifiersVerbBadArgument(): void
    {
        $this->dispatch('/s/test/oai?verb=ListIdentifiers');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertEquals((string) $xml->error['code'], 'badArgument');
    }

    public function testListIdentifiersVerbOaiDc(): void
    {
        $this->dispatch('/s/test/oai?verb=ListIdentifiers&metadataPrefix=oai_dc');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasAttribute('ListIdentifiers', $xml);
        $expectedIdentifier = 'oai:test:' . $this->item->id();
        $this->assertEquals((string) $xml->ListIdentifiers->header->identifier, $expectedIdentifier);
    }

    public function testGetRecordVerbOaiDc(): void
    {
        $itemIdentifier = 'oai:test:' . $this->item->id();
        $this->dispatch("/s/test/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier=$itemIdentifier");
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string($this->getResponse()->getContent());
        $this->assertNotFalse($xml);

        $this->assertObjectHasAttribute('GetRecord', $xml);
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

        $this->assertObjectHasAttribute('ListRecords', $xml);
        $metadata = $xml->ListRecords->record->metadata;
        $dc = $metadata->children('oai_dc', true)->dc;
        $title = (string) $dc->children('dc', true)->title;
        $expectedTitle = (string) $this->item->value('dcterms:title');
        $this->assertEquals($expectedTitle, $title);
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
    }
}
