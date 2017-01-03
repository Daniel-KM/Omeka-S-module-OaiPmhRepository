<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace OaiPmhRepository\Metadata;

use DateTime;
use DOMElement;
use Omeka\Api\Representation\ItemRepresentation;
use OaiPmhRepository\XmlGeneratorAbstract;
use OaiPmhRepository\OaiIdentifier;

/**
 * Abstract class on which all other metadata format handlers are based.
 * Includes logic for all metadata-independent record output.
 *
 * @todo Migration to PHP 5.3 will allow the abstract getter functions to be
 *       static, as they should be
 */
abstract class AbstractMetadata extends XmlGeneratorAbstract
    implements MetadataInterface
{
    /**
     * Appends the record to the XML response.
     *
     * Adds both the header and metadata elements as children of a record
     * element, which is appended to the document.
     *
     * @uses appendHeader
     * @uses appendMetadata
     *
     * @param DOMElement $parent
     */
    public function appendRecord(DOMElement $parent, ItemRepresentation $item)
    {
        $document = $parent->ownerDocument;
        $record = $document->createElement('record');
        $parent->appendChild($record);
        $this->appendHeader($record, $item);

        $metadata = $document->createElement('metadata');
        $record->appendChild($metadata);
        $this->appendMetadata($metadata, $item);
    }

    /**
     * Appends the record's header to the XML response.
     *
     * Adds the identifier, datestamp and setSpec to a header element, and
     * appends in to the document.
     *
     * @param DOMElement $parent
     */
    public function appendHeader(DOMElement $parent, ItemRepresentation $item)
    {
        $headerData['identifier'] = OaiIdentifier::itemToOaiId($item->id());

        $datestamp = $item->modified();
        if (!$datestamp) {
            $datestamp = $item->created();
        }
        $dateFormat = \OaiPmhRepository\Date::OAI_DATE_FORMAT;
        $headerData['datestamp'] = $datestamp->format($dateFormat);

        $header = $this->createElementWithChildren($parent, 'header', $headerData);
        foreach ($item->itemSets() as $itemSet) {
            $this->appendNewElement($header, 'setSpec', $itemSet->id());
        }
    }

    /**
     * Appends a metadataFormat element to the document.
     *
     * Declares the metadataPrefix, schema URI, and namespace for the oai_dc
     * metadata format.
     *
     * @param DOMElement $parent
     */
    public function declareMetadataFormat(DOMElement $parent)
    {
        $elements = [
            'metadataPrefix' => $this->getMetadataPrefix(),
            'schema' => $this->getMetadataSchema(),
            'metadataNamespace' => $this->getMetadataNamespace(),
        ];
        $this->createElementWithChildren($parent, 'metadataFormat', $elements);
    }

    /**
     * Returns the OAI-PMH metadata prefix for the output format.
     *
     * @return string Metadata prefix
     */
    abstract public function getMetadataPrefix();

    /**
     * Returns the XML schema for the output format.
     *
     * @return string XML schema URI
     */
    abstract public function getMetadataSchema();

    /**
     * Returns the XML namespace for the output format.
     *
     * @return string XML namespace URI
     */
    abstract public function getMetadataNamespace();

    /**
     * Appends the metadata for one Omeka item to the XML document.
     *
     * @param DOMElement $parent
     */
    abstract public function appendMetadata(DOMElement $parent, ItemRepresentation $item);
}
