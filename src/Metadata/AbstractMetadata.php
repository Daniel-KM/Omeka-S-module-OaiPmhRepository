<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace OaiPmhRepository\Metadata;

use DateTime;
use OaiPmhRepository\OaiXmlGeneratorAbstract;
use OaiPmhRepository\OaiIdentifier;

/**
 * Abstract class on which all other metadata format handlers are based.
 * Includes logic for all metadata-independent record output.
 *
 * @todo Migration to PHP 5.3 will allow the abstract getter functions to be
 *       static, as they should be
 */
abstract class AbstractMetadata extends OaiXmlGeneratorAbstract
{
    /**
     * Item object for this record.
     *
     * @var ItemRepresentation
     */
    protected $item;

    /**
     * Document to append to.
     *
     * @var DOMDocument
     */
    protected $document;

    protected $serviceLocator;

    /**
     * Metadata_Abstract constructor.
     *
     * Sets base class properties.
     *
     * @param ItemRepresentation item Item object whose metadata will be output
     * @param DOMDocument $document
     */
    public function __construct($item = null, $document = null, $serviceLocator = null)
    {
        if ($item) {
            $this->item = $item;
        }
        if ($document) {
            $this->document = $document;
        }

        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Appends the record to the XML response.
     *
     * Adds both the header and metadata elements as children of a record
     * element, which is appended to the document.
     *
     * @uses appendHeader
     * @uses appendMetadata
     *
     * @param DOMElement $parentElement
     */
    public function appendRecord($parentElement)
    {
        $record = $this->document->createElement('record');
        $parentElement->appendChild($record);
        $this->appendHeader($record);

        $metadata = $this->document->createElement('metadata');
        $record->appendChild($metadata);
        $this->appendMetadata($metadata);
    }

    /**
     * Appends the record's header to the XML response.
     *
     * Adds the identifier, datestamp and setSpec to a header element, and
     * appends in to the document.
     *
     * @param DOMElement $parentElement
     */
    public function appendHeader($parentElement)
    {
        $headerData['identifier'] = OaiIdentifier::itemToOaiId($this->item->id());

        $datestamp = $this->item->modified();
        if (!$datestamp) {
            $datestamp = $this->item->created();
        }
        $headerData['datestamp'] = $datestamp->format(DateTime::ATOM);

        $header = $this->createElementWithChildren($parentElement, 'header', $headerData);
        foreach ($this->item->itemSets() as $itemSet) {
            $this->appendNewElement($header, 'setSpec', $itemSet->id());
        }
    }

    /**
     * Appends a metadataFormat element to the document.
     *
     * Declares the metadataPrefix, schema URI, and namespace for the oai_dc
     * metadata format.
     *
     * @param DOMElement $parentElement
     */
    public function declareMetadataFormat($parentElement)
    {
        $elements = [
            'metadataPrefix' => $this->getMetadataPrefix(),
            'schema' => $this->getMetadataSchema(),
            'metadataNamespace' => $this->getMetadataNamespace(),
        ];
        $this->createElementWithChildren($parentElement, 'metadataFormat', $elements);
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
     * @param DOMElement $parentElement
     */
    abstract public function appendMetadata($parentElement);
}
