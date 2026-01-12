<?php declare(strict_types=1);

namespace OaiPmhRepositoryTest\OaiPmh\Metadata;

use CommonTest\AbstractTestCase;
use OaiPmhRepositoryTest\OaiPmhRepositoryTestTrait;

/**
 * Test LIDO metadata format output.
 */
class LidoTest extends AbstractTestCase
{
    use OaiPmhRepositoryTestTrait;

    protected $item;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
        $this->item = $this->createTestItem();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
    }

    public function testLidoFormatIsRegistered(): void
    {
        $services = $this->getServiceLocator();
        $metadataFormatManager = $services->get(
            \OaiPmhRepository\OaiPmh\MetadataFormatManager::class
        );

        $this->assertTrue($metadataFormatManager->has('lido'));
    }

    public function testLidoMetadataPrefix(): void
    {
        $this->assertEquals(
            'lido',
            \OaiPmhRepository\OaiPmh\Metadata\Lido::METADATA_PREFIX
        );
    }

    public function testLidoNamespace(): void
    {
        $this->assertEquals(
            'http://www.lido-schema.org',
            \OaiPmhRepository\OaiPmh\Metadata\Lido::METADATA_NAMESPACE
        );
    }

    public function testLidoSchema(): void
    {
        $this->assertEquals(
            'http://lido-schema.org/schema/v1.1/lido-v1.1.xsd',
            \OaiPmhRepository\OaiPmh\Metadata\Lido::METADATA_SCHEMA
        );
    }

    public function testGenerateLidoMetadata(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');
        $xml = $doc->saveXML();

        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('lido:lido', $xml);
    }

    public function testLidoRecID(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists($doc, '//lido:lidoRecID');
        $this->assertXPathContains(
            $doc,
            '//lido:lidoRecID',
            (string) $this->item->id()
        );
    }

    public function testDescriptiveMetadata(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists($doc, '//lido:descriptiveMetadata');
        $this->assertXPathExists($doc, '//lido:descriptiveMetadata[@xml:lang]');
    }

    public function testAdministrativeMetadata(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists($doc, '//lido:administrativeMetadata');
        $this->assertXPathExists($doc, '//lido:administrativeMetadata[@xml:lang]');
    }

    public function testObjectWorkType(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:objectClassificationWrap/lido:objectWorkTypeWrap/lido:objectWorkType'
        );
        $this->assertXPathEquals(
            $doc,
            '//lido:objectWorkType/lido:term',
            'Peinture'
        );
    }

    public function testTitleSet(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists($doc, '//lido:titleWrap/lido:titleSet');
        $this->assertXPathEquals(
            $doc,
            '//lido:titleSet/lido:appellationValue[@lido:pref="preferred"]',
            'La Joconde'
        );
    }

    public function testAlternativeTitle(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:titleSet/lido:appellationValue[@lido:pref="alternate"]'
        );
        $this->assertXPathEquals(
            $doc,
            '//lido:titleSet/lido:appellationValue[@lido:pref="alternate"]',
            'Mona Lisa'
        );
    }

    public function testObjectDescription(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:objectDescriptionWrap/lido:objectDescriptionSet/lido:descriptiveNoteValue'
        );
        $this->assertXPathEquals(
            $doc,
            '//lido:objectDescriptionSet/lido:descriptiveNoteValue',
            'Portrait de Lisa Gherardini'
        );
    }

    public function testObjectMeasurements(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:objectMeasurementsWrap/lido:objectMeasurementsSet/lido:displayObjectMeasurements'
        );
        $this->assertXPathContains(
            $doc,
            '//lido:displayObjectMeasurements',
            '77 cm'
        );
    }

    public function testProductionEvent(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists($doc, '//lido:eventWrap/lido:eventSet/lido:event');
        $this->assertXPathEquals(
            $doc,
            '//lido:event/lido:eventType/lido:term',
            'Production'
        );
    }

    public function testEventActor(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:event/lido:eventActor/lido:actorInRole/lido:actor'
        );
        $this->assertXPathEquals(
            $doc,
            '//lido:actor/lido:nameActorSet/lido:appellationValue',
            'Léonard de Vinci'
        );
    }

    public function testEventDate(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists($doc, '//lido:event/lido:eventDate/lido:displayDate');
        $this->assertXPathEquals(
            $doc,
            '//lido:eventDate/lido:displayDate',
            '1503-1519'
        );
    }

    public function testMaterialsTech(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:event/lido:eventMaterialsTech/lido:displayMaterialsTech'
        );
        $this->assertXPathContains(
            $doc,
            '//lido:displayMaterialsTech',
            'Huile sur panneau'
        );
    }

    public function testSubjectConcept(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:objectRelationWrap/lido:subjectWrap/lido:subjectSet'
        );
        $this->assertXPathExists(
            $doc,
            '//lido:subjectSet/lido:subject/lido:subjectConcept/lido:term'
        );
    }

    public function testRightsWorkWrap(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:administrativeMetadata/lido:rightsWorkWrap/lido:rightsWorkSet'
        );
        $this->assertXPathEquals(
            $doc,
            '//lido:rightsWorkSet/lido:rightsType/lido:term',
            'Domaine public'
        );
    }

    public function testRecordWrap(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:administrativeMetadata/lido:recordWrap'
        );
        $this->assertXPathExists($doc, '//lido:recordWrap/lido:recordID');
        $this->assertXPathExists($doc, '//lido:recordWrap/lido:recordType/lido:term');
        $this->assertXPathExists(
            $doc,
            '//lido:recordWrap/lido:recordSource/lido:legalBodyName'
        );
    }

    public function testRecordID(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathEquals(
            $doc,
            '//lido:recordWrap/lido:recordID',
            (string) $this->item->id()
        );
    }

    public function testRecordType(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathEquals(
            $doc,
            '//lido:recordWrap/lido:recordType/lido:term',
            'item'
        );
    }

    public function testRepositoryName(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists(
            $doc,
            '//lido:repositoryWrap/lido:repositorySet/lido:repositoryName'
        );
        $this->assertXPathEquals(
            $doc,
            '//lido:repositoryName/lido:legalBodyName/lido:appellationValue',
            'Musée du Louvre'
        );
    }

    public function testXmlValidStructure(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');
        $xml = $doc->saveXML();

        // Parse and validate XML structure.
        $parsed = new \DOMDocument();
        $result = $parsed->loadXML($xml);

        $this->assertTrue($result, 'Generated XML should be valid');
    }

    public function testLanguageAttribute(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists($doc, '//lido:descriptiveMetadata[@xml:lang="fr"]');
        $this->assertXPathExists($doc, '//lido:administrativeMetadata[@xml:lang="fr"]');
    }

    public function testItemWithoutType(): void
    {
        $item = $this->createItem([
            'dcterms:title' => [
                ['@value' => 'Test item without type'],
            ],
        ]);

        $doc = $this->generateMetadata($item, 'lido');

        // Should default to 'Unknown' when no type is provided.
        $this->assertXPathExists($doc, '//lido:objectWorkType/lido:term');
    }

    public function testItemWithoutTitle(): void
    {
        $item = $this->createItem([
            'dcterms:type' => [
                ['@value' => 'Test type'],
            ],
        ]);

        $doc = $this->generateMetadata($item, 'lido');

        // Should have a title set even if empty.
        $this->assertXPathExists($doc, '//lido:titleWrap/lido:titleSet');
    }

    public function testMinimalItem(): void
    {
        $item = $this->createItem([]);

        $doc = $this->generateMetadata($item, 'lido');
        $xml = $doc->saveXML();

        // Minimal item should still produce valid LIDO XML.
        $this->assertStringContainsString('lido:lido', $xml);
        $this->assertStringContainsString('lido:lidoRecID', $xml);
        $this->assertStringContainsString('lido:descriptiveMetadata', $xml);
        $this->assertStringContainsString('lido:administrativeMetadata', $xml);
        $this->assertStringContainsString('lido:recordWrap', $xml);
    }
}
