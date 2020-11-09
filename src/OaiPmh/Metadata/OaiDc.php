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
 * Class implementing metadata output for the required oai_dc metadata format.
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
     * {@inheritDoc}
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item)
    {
        $document = $metadataElement->ownerDocument;

        $oai = $document->createElementNS(self::METADATA_NAMESPACE, 'oai_dc:dc');
        $metadataElement->appendChild($oai);

        /* Must manually specify XML schema uri per spec, but DOM won't include
         * a redundant xmlns:xsi attribute, so we just set the attribute
         */
        $oai->setAttribute('xmlns:dc', self::DC_NAMESPACE_URI);
        $oai->setAttribute('xmlns:xsi', parent::XML_SCHEMA_NAMESPACE_URI);
        $oai->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE . ' ' .
            self::METADATA_SCHEMA);

        /* Each of the 15 unqualified Dublin Core elements, in the order
         * specified by the oai_dc XML schema
         */
        $localNames = [
            'title',
            'creator',
            'subject',
            'description',
            'publisher',
            'contributor',
            'date',
            'type',
            'format',
            'identifier',
            'source',
            'language',
            'relation',
            'coverage',
            'rights',
        ];

        /* Must create elements using createElement to make DOM allow a
         * top-level xmlns declaration instead of wasteful and non-
         * compliant per-node declarations.
         */
        foreach ($localNames as $localName) {
            $term = 'dcterms:' . $localName;
            $values = $item->value($term, ['all' => true]);
            $values = $this->filterValues($item, $term, $values);
            foreach ($values as $value) {
                $this->appendNewElement($oai, 'dc:' . $localName, (string) $value);
            }
        }

        $appendIdentifier = $this->singleIdentifier($item);
        if ($appendIdentifier) {
            $this->appendNewElement($oai, 'dc:identifier', $appendIdentifier);
        }

        // Also append an identifier for each file
        if ($this->settings->get('oaipmhrepository_expose_media', false)) {
            foreach ($item->media() as $media) {
                $this->appendNewElement($oai, 'dc:identifier', $media->originalUrl());
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
