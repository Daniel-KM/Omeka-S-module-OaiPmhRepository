<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use DOMElement;
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Class implmenting metadata output for the required oai_dc metadata format.
 * oai_dc is output of the 15 unqualified Dublin Core fields.
 */
class OaiDc extends AbstractMetadata
{
    /** OAI-PMH metadata prefix */
    const METADATA_PREFIX = 'oai_dc';

    /** XML namespace for output format */
    const METADATA_NAMESPACE = 'http://www.openarchives.org/OAI/2.0/oai_dc/';

    /** XML schema for output format */
    const METADATA_SCHEMA = 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';

    /** XML namespace for unqualified Dublin Core */
    const DC_NAMESPACE_URI = 'http://purl.org/dc/elements/1.1/';

    /**
     * Appends Dublin Core metadata.
     *
     * Appends a metadata element, an child element with the required format,
     * and further children for each of the Dublin Core fields present in the
     * item.
     *
     * {@inheritDoc}
     * @see \OaiPmhRepository\OaiPmh\Metadata\AbstractMetadata::appendMetadata()
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item)
    {
        $document = $metadataElement->ownerDocument;

        $oai_dc = $document->createElementNS(self::METADATA_NAMESPACE, 'oai_dc:dc');
        $metadataElement->appendChild($oai_dc);

        /* Must manually specify XML schema uri per spec, but DOM won't include
         * a redundant xmlns:xsi attribute, so we just set the attribute
         */
        $oai_dc->setAttribute('xmlns:dc', self::DC_NAMESPACE_URI);
        $oai_dc->setAttribute('xmlns:xsi', parent::XML_SCHEMA_NAMESPACE_URI);
        $oai_dc->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE . ' ' .
            self::METADATA_SCHEMA);

        /* Each of the 16 unqualified Dublin Core elements, in the order
         * specified by the oai_dc XML schema
         */
        $dcElementNames = [
            'title', 'creator', 'subject', 'description', 'publisher',
            'contributor', 'date', 'type', 'format', 'identifier', 'source',
            'language', 'relation', 'coverage', 'rights',
        ];

        /* Must create elements using createElement to make DOM allow a
         * top-level xmlns declaration instead of wasteful and non-
         * compliant per-node declarations.
         */
        foreach ($dcElementNames as $elementName) {
            $values = $item->value("dcterms:$elementName", ['all' => true]) ?: [];
            foreach ($values as $value) {
                $this->appendNewElement($oai_dc, "dc:$elementName", (string) $value);
            }
        }

        $appendIdentifier = $this->singleIdentifier($item);
        if ($appendIdentifier) {
            $this->appendNewElement($oai_dc, 'dc:identifier', $appendIdentifier);
        }

        // Also append an identifier for each file
        if ($this->settings->get('oaipmhrepository_expose_media', false)) {
            foreach ($item->media() as $media) {
                $this->appendNewElement($oai_dc, 'dc:identifier', $media->originalUrl());
            }
        }
    }

    public function getMetadataPrefix()
    {
        return self::METADATA_PREFIX;
    }

    public function getMetadataSchema()
    {
        return self::METADATA_SCHEMA;
    }

    public function getMetadataNamespace()
    {
        return self::METADATA_NAMESPACE;
    }
}
