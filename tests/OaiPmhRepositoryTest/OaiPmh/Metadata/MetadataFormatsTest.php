<?php declare(strict_types=1);

namespace OaiPmhRepositoryTest\OaiPmh\Metadata;

use CommonTest\AbstractTestCase;
use OaiPmhRepositoryTest\OaiPmhRepositoryTestTrait;

/**
 * Test all metadata formats are properly registered and generate valid XML.
 */
class MetadataFormatsTest extends AbstractTestCase
{
    use OaiPmhRepositoryTestTrait;

    protected $item;

    /**
     * List of expected metadata formats.
     */
    protected $expectedFormats = [
        'oai_dc',
        'oai_dcterms',
        'cdwalite',
        'mets',
        'mods',
        'simple_xml',
        'lido',
    ];

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

    public function testAllFormatsAreRegistered(): void
    {
        $services = $this->getServiceLocator();
        $metadataFormatManager = $services->get(
            \OaiPmhRepository\OaiPmh\MetadataFormatManager::class
        );

        foreach ($this->expectedFormats as $format) {
            $this->assertTrue(
                $metadataFormatManager->has($format),
                "Format '$format' should be registered"
            );
        }
    }

    /**
     * @dataProvider metadataFormatProvider
     */
    public function testFormatGeneratesValidXml(string $format): void
    {
        $doc = $this->generateMetadata($this->item, $format);
        $xml = $doc->saveXML();

        $this->assertNotEmpty($xml, "Format '$format' should generate XML");

        // Validate XML structure.
        $parsed = new \DOMDocument();
        $result = $parsed->loadXML($xml);
        $this->assertTrue($result, "Format '$format' should generate valid XML");
    }

    /**
     * @dataProvider metadataFormatProvider
     */
    public function testFormatContainsTitle(string $format): void
    {
        $doc = $this->generateMetadata($this->item, $format);
        $xml = $doc->saveXML();

        // All formats should contain the title somehow.
        $this->assertStringContainsString(
            'La Joconde',
            $xml,
            "Format '$format' should contain the item title"
        );
    }

    public function metadataFormatProvider(): array
    {
        return [
            'oai_dc' => ['oai_dc'],
            'oai_dcterms' => ['oai_dcterms'],
            'cdwalite' => ['cdwalite'],
            'mets' => ['mets'],
            'mods' => ['mods'],
            'simple_xml' => ['simple_xml'],
            'lido' => ['lido'],
        ];
    }

    public function testOaiDcFormat(): void
    {
        $doc = $this->generateMetadata($this->item, 'oai_dc');

        $this->assertXPathExists($doc, '//oai_dc:dc');
        $this->assertXPathExists($doc, '//dc:title');
        $this->assertXPathEquals($doc, '//dc:title', 'La Joconde');
    }

    public function testOaiDctermsFormat(): void
    {
        $doc = $this->generateMetadata($this->item, 'oai_dcterms');
        $xml = $doc->saveXML();

        $this->assertStringContainsString('dcterms', $xml);
        $this->assertStringContainsString('La Joconde', $xml);
    }

    public function testCdwaLiteFormat(): void
    {
        $doc = $this->generateMetadata($this->item, 'cdwalite');

        $this->assertXPathExists($doc, '//cdwalite:cdwalite');
        $this->assertXPathExists($doc, '//cdwalite:descriptiveMetadata');
        $this->assertXPathExists($doc, '//cdwalite:administrativeMetadata');
    }

    public function testMetsFormat(): void
    {
        $doc = $this->generateMetadata($this->item, 'mets');

        $this->assertXPathExists($doc, '//mets:mets');
        $this->assertXPathExists($doc, '//mets:dmdSec');
        $this->assertXPathExists($doc, '//mets:structMap');
    }

    public function testModsFormat(): void
    {
        $doc = $this->generateMetadata($this->item, 'mods');

        $this->assertXPathExists($doc, '//mods:mods');
    }

    public function testSimpleXmlFormat(): void
    {
        $doc = $this->generateMetadata($this->item, 'simple_xml');
        $xml = $doc->saveXML();

        $this->assertStringContainsString('La Joconde', $xml);
    }

    public function testLidoFormat(): void
    {
        $doc = $this->generateMetadata($this->item, 'lido');

        $this->assertXPathExists($doc, '//lido:lido');
        $this->assertXPathExists($doc, '//lido:descriptiveMetadata');
        $this->assertXPathExists($doc, '//lido:administrativeMetadata');
    }

    public function testMetadataFormatConstants(): void
    {
        $formats = [
            \OaiPmhRepository\OaiPmh\Metadata\OaiDc::class => [
                'prefix' => 'oai_dc',
                'namespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
            ],
            \OaiPmhRepository\OaiPmh\Metadata\OaiDcterms::class => [
                'prefix' => 'oai_dcterms',
                'namespace' => 'http://www.openarchives.org/OAI/2.0/oai_dcterms/',
            ],
            \OaiPmhRepository\OaiPmh\Metadata\CdwaLite::class => [
                'prefix' => 'cdwalite',
                'namespace' => 'http://www.getty.edu/CDWA/CDWALite',
            ],
            \OaiPmhRepository\OaiPmh\Metadata\Mets::class => [
                'prefix' => 'mets',
                'namespace' => 'http://www.loc.gov/METS/',
            ],
            \OaiPmhRepository\OaiPmh\Metadata\Lido::class => [
                'prefix' => 'lido',
                'namespace' => 'http://www.lido-schema.org',
            ],
        ];

        foreach ($formats as $class => $expected) {
            $this->assertEquals(
                $expected['prefix'],
                $class::METADATA_PREFIX,
                "Prefix for $class"
            );
            $this->assertEquals(
                $expected['namespace'],
                $class::METADATA_NAMESPACE,
                "Namespace for $class"
            );
        }
    }
}
