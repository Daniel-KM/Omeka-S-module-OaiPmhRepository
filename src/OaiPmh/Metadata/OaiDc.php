<?php declare(strict_types=1);
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2023
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
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item): void
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

        $classType = $this->params['oai_dc']['class_type'];
        $classTypeTable = $this->params['oai_dc']['class_type_table'];
        $bnfVignette = $this->params['oai_dc']['bnf_vignette'];
        $formatUri = $this->params['format_uri'];
        $formatUriAttributes = in_array($formatUri, ['uri', 'uri_attr_label'])
            ? ['xsi:type' => 'dcterms:URI']
            : [];

        /* Must create elements using createElement to make DOM allow a
         * top-level xmlns declaration instead of wasteful and non-
         * compliant per-node declarations.
         */
        $values = $this->filterValuesPre($item);
        foreach ($localNames as $localName) {
            $vals = [];
            $term = 'dcterms:' . $localName;
            $termValues = $values[$term]['values'] ?? [];
            foreach ($termValues as $value) {
                [$text, $attributes] = $this->formatValue($value);
                $vals[] = $text;
                $this->appendNewElement($oai, 'dc:' . $localName, $text, $attributes);
            }
            // Option class as type.
            if ($classType !== 'no' && $localName === 'type' && ($class = $item->resourceClass())) {
                if ($classType === 'term') {
                    $text = $class->term();
                } elseif ($classType === 'local') {
                    $text = $class->localName();
                } elseif ($classType === 'label') {
                    $text = $item->displayResourceClassLabel();
                } elseif ($classType === 'table') {
                    $text = $classTypeTable->labelFromCode($class->term());
                } else {
                    $text = null;
                }
                if ($text && !in_array($text, $vals)) {
                    $this->appendNewElement($oai, 'dc:type', $text, []);
                }
            }
            // Option BnF vignette.
            elseif ($bnfVignette !== 'none' && $localName === 'relation') {
                $thumbnail = $item->thumbnailDisplayUrl($bnfVignette);
                if ($thumbnail && !in_array($thumbnail, $vals)) {
                    $this->appendNewElement($oai, 'dc:relation', 'vignette : ' . $thumbnail, []);
                }
            }
        }

        $appendIdentifier = $this->singleIdentifier($item);
        if ($appendIdentifier) {
            $this->appendNewElement($oai, 'dc:identifier', $appendIdentifier, $formatUriAttributes);
        }

        // Also append an identifier for each file
        if ($this->params['expose_media']) {
            foreach ($item->media() as $media) {
                $this->appendNewElement($oai, 'dc:identifier', $media->originalUrl(), $formatUriAttributes);
            }
        }
    }
}
