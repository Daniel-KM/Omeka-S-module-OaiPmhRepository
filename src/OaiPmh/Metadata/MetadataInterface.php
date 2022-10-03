<?php declare(strict_types=1);

namespace OaiPmhRepository\OaiPmh\Metadata;

use DOMElement;
use OaiPmhRepository\OaiPmh\OaiSet\OaiSetInterface;
use Omeka\Api\Representation\ItemRepresentation;

interface MetadataInterface
{
    /**
     * @param OaiSetInterface $oaiSet
     */
    public function setOaiSet(OaiSetInterface $oaiSet);

    /**
     * @return OaiSetInterface $oaiSet
     */
    public function getOaiSet();

    /**
     * Returns the OAI-PMH metadata prefix for the output format.
     *
     * @return string Metadata prefix
     */
    public function getMetadataPrefix(): string;

    /**
     * Appends a metadataFormat element to the document.
     *
     * Declares the metadataPrefix, schema URI, and namespace for the oai_dc
     * metadata format.
     *
     * @param DOMElement $parent
     */
    public function declareMetadataFormat(DOMElement $parent);

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
     * @param ItemRepresentation $item
     */
    public function appendRecord(DOMElement $parent, ItemRepresentation $item);

    /**
     * Appends the record's header to the XML response.
     *
     * Adds the identifier, datestamp and setSpec to a header element, and
     * appends in to the document.
     *
     * @param DOMElement $parent
     * @param ItemRepresentation $item
     */
    public function appendHeader(DOMElement $parent, ItemRepresentation $item);

    /**
     * Appends the metadata for one Omeka item to the XML document.
     *
     * @param DOMElement $parent
     * @param ItemRepresentation $item
     */
    public function appendMetadata(DOMElement $parent, ItemRepresentation $item);
}
