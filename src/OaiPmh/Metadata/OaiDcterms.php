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
 * Class implementing metadata output for the oai_dcterms metadata format.
 * oai_dcterms is output of the 55 Dublin Core terms.
 *
 * This format is not standardized, but used by some repositories.
 *
 * Note: the namespace and the schema don’t exist. It is designed as an extended
 * version of oai_dc.
 *
 * @link http://www.bl.uk/schemas/
 * @link http://dublincore.org/documents/dc-xml-guidelines/
 * @link http://dublincore.org/schemas/xmls/qdc/dcterms.xsd
 */
class OaiDcterms extends AbstractMetadata
{
    /** OAI-PMH metadata prefix */
    const METADATA_PREFIX = 'oai_dcterms';

    /** XML namespace for output format */
    const METADATA_NAMESPACE = 'http://www.openarchives.org/OAI/2.0/oai_dcterms/';

    /** XML schema for output format */
    const METADATA_SCHEMA = 'http://www.openarchives.org/OAI/2.0/oai_dcterms.xsd';

    /** XML namespace for Dublin Core */
    const DCTERMS_NAMESPACE_URI = 'http://purl.org/dc/terms/';

    /**
     * Appends Dublin Core terms metadata.
     *
     * {@inheritDoc}
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item): void
    {
        $document = $metadataElement->ownerDocument;

        $oai = $document->createElementNS(self::METADATA_NAMESPACE, 'oai_dcterms:dcterms');
        $metadataElement->appendChild($oai);

        /* Must manually specify XML schema uri per spec, but DOM won't include
         * a redundant xmlns:xsi attribute, so we just set the attribute
         */
        $oai->setAttribute('xmlns:dcterms', self::DCTERMS_NAMESPACE_URI);
        $oai->setAttribute('xmlns:xsi', parent::XML_SCHEMA_NAMESPACE_URI);
        $oai->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE . ' ' .
            self::METADATA_SCHEMA);

        // Each of the 55 Dublin Core terms, in the Omeka order.
        $localNames = [
            // Dublin Core Elements.
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
            // Dublin Core terms.
            'audience',
            'alternative',
            'tableOfContents',
            'abstract',
            'created',
            'valid',
            'available',
            'issued',
            'modified',
            'extent',
            'medium',
            'isVersionOf',
            'hasVersion',
            'isReplacedBy',
            'replaces',
            'isRequiredBy',
            'requires',
            'isPartOf',
            'hasPart',
            'isReferencedBy',
            'references',
            'isFormatOf',
            'hasFormat',
            'conformsTo',
            'spatial',
            'temporal',
            'mediator',
            'dateAccepted',
            'dateCopyrighted',
            'dateSubmitted',
            'educationLevel',
            'accessRights',
            'bibliographicCitation',
            'license',
            'rightsHolder',
            'provenance',
            'instructionalMethod',
            'accrualMethod',
            'accrualPeriodicity',
            'accrualPolicy',
        ];

        $bnfVignette = $this->params['oai_dcterms']['bnf_vignette'];

        /* Must create elements using createElement to make DOM allow a
         * top-level xmlns declaration instead of wasteful and non-
         * compliant per-node declarations.
         */
        $values = $this->filterValuesPre($item);
        foreach ($localNames as $localName) {
            $term = 'dcterms:' . $localName;
            $termValues = $values[$term]['values'] ?? [];
            foreach ($termValues as $value) {
                [$text, $attributes] = $this->formatValue($value);
                $this->appendNewElement($oai, $term, $text, $attributes);
            }
            if ($bnfVignette !== 'none' && $localName === 'relation') {
                $thumbnail = $item->thumbnailDisplayUrl($bnfVignette);
                if ($thumbnail) {
                    $this->appendNewElement($oai, 'dcterms:relation', 'vignette : ' . $thumbnail, []);
                }
            }
        }

        $appendIdentifier = $this->singleIdentifier($item);
        if ($appendIdentifier) {
            $this->appendNewElement($oai, 'dcterms:identifier', $appendIdentifier, ['xsi:type' => 'dcterms:URI']);
        }

        // Also append an identifier for each file
        if ($this->params['expose_media']) {
            foreach ($item->media() as $media) {
                $this->appendNewElement($oai, 'dcterms:identifier', $media->originalUrl(), ['xsi:type' => 'dcterms:URI']);
            }
        }
    }
}
