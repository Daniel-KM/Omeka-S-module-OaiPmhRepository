<?php declare(strict_types=1);
/**
 * @copyright Daniel Berthereau, 2014-2022
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use DOMElement;
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Class implementing metadata output for the  simple_xml metadata format.
 *
 * This format contains all the metadata of each resource, but is not standardized.
 *
 * Note: like oai_dcterms, the namespace and the schema don’t exist.
 */
class SimpleXml extends AbstractMetadata
{
    const METADATA_PREFIX = 'simple_xml';
    const METADATA_NAMESPACE = 'http://www.openarchives.org/OAI/2.0/simple_xml/';
    const METADATA_SCHEMA = 'http://www.openarchives.org/OAI/2.0/simple_xml.xsd';

    /**
     * Appends all metadata.
     *
     * {@inheritDoc}
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item): void
    {
        $document = $metadataElement->ownerDocument;

        $values = $this->filterValuesPre($item);

        // Append all schemas used by current resource, not all vocabularies.
        // Include dcterms in all cases to manage single identifier, media urls…
        $usedVocabularies = [
            'dcterms' => null,
        ];
        foreach (array_keys($values) as $term) {
            $usedVocabularies[strtok($term, ':')] = null;
        }

        // Keep dcterms first and order alphabetically.
        $usedVocabularies = array_intersect_key($this->params['simple_xml']['vocabularies'], $usedVocabularies);

        // Create the main node.
        $oai = $document->createElementNS(self::METADATA_NAMESPACE, 'simple_xml');
        $metadataElement->appendChild($oai);

        // Include resource metadata on the main node.
        $meta = $this->mainResourceMetadata($item);
        foreach ($meta as $name => $value) {
            if (!is_null($value)) {
                $oai->setAttribute($name, (string) $value);
            }
        }

        // The XML schema uris are included one time at upper level at last.
        foreach ($usedVocabularies as $prefix => $namespaceUri) {
            $oai->setAttribute('xmlns:' . $prefix, $namespaceUri);
        }

        $oai->setAttribute('xmlns:xsi', self::XML_SCHEMA_NAMESPACE_URI);
        /* // Don't include a non-existing schema, even simple.
        $oai->setAttribute('xsi:schemaLocation',
            self::METADATA_NAMESPACE . ' ' . self::METADATA_SCHEMA);
        */

        foreach ($values as $term => $propertyData) {
            foreach ($propertyData['values'] as $value) {
                list($text, $attributes) = $this->formatValue($value);
                $this->appendNewElement($oai, $term, $text, $attributes);
            }
        }

        $appendIdentifier = $this->singleIdentifier($item);
        if ($appendIdentifier) {
            $this->appendNewElement($oai, 'dcterms:identifier', $appendIdentifier, ['xsi:type' => 'dcterms:URI']);
        }
    }
}
