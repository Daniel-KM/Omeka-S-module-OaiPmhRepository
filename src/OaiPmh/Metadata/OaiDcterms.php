<?php declare(strict_types=1);
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
 * Class implementing metadata output for the oai_dcterms metadata format.
 * oai_dcterms is output of the 55 Dublin Core terms.
 *
 * This format is not standardized, but used by some repositories.
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
    const DC_NAMESPACE_URI = 'http://purl.org/dc/elements/1.1/';
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
        $oai->setAttribute('xmlns:dc', self::DC_NAMESPACE_URI);
        $oai->setAttribute('xmlns:dcterms', self::DCTERMS_NAMESPACE_URI);
        $oai->setAttribute('xmlns:xsi', parent::XML_SCHEMA_NAMESPACE_URI);
        $oai->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE . ' ' .
            self::METADATA_SCHEMA);

        // Each of the 55 Dublin Core terms, in the Omeka order.
        $oaiDCterms = [
          'dc:title',
          'dc:creator',
          'dc:subject',
          'dc:description',
          'dc:publisher',
          'dc:contributor',
          'dc:date',
          'dc:type',
          'dc:format',
          'dc:identifier',
          'dc:source',
          'dc:language',
          'dc:relation',
          'dc:coverage',
          'dc:rights',
          'dcterms:abstract',
          'dcterms:accessRights',
          'dcterms:accrualMethod',
          'dcterms:accrualPeriodicity',
          'dcterms:accrualPolicy',
          'dcterms:alternative',
          'dcterms:audience',
          'dcterms:available',
          'dcterms:bibliographicCitation',
          'dcterms:conformsTo',
          'dcterms:created',
          'dcterms:dateAccepted',
          'dcterms:dateCopyrighted',
          'dcterms:dateSubmitted',
          'dcterms:educationLevel',
          'dcterms:extent',
          'dcterms:hasFormat',
          'dcterms:hasPart',
          'dcterms:hasVersion',
          'dcterms:instructionalMethod',
          'dcterms:isFormatOf',
          'dcterms:isPartOf',
          'dcterms:isReferencedBy',
          'dcterms:isReplacedBy',
          'dcterms:isRequiredBy',
          'dcterms:issued',
          'dcterms:isVersionOf',
          'dcterms:license',
          'dcterms:mediator',
          'dcterms:medium',
          'dcterms:modified',
          'dcterms:provenance',
          'dcterms:references',
          'dcterms:replaces',
          'dcterms:requires',
          'dcterms:rightsHolder',
          'dcterms:spatial',
          'dcterms:tableOfContents',
          'dcterms:temporal',
          'dcterms:valid'
        ];


        /* Must create elements using createElement to make DOM allow a
         * top-level xmlns declaration instead of wasteful and non-
         * compliant per-node declarations.
         */
        $values = $this->filterValuesPre($item);
        foreach ($oaiDCterms as $oaiDCterm) {
            $term = str_replace('dc:', 'dcterms:', $oaiDCterm);
            $termValues = $values[$term]['values'] ?? [];
            $termValues = $this->filterValues($item, $term, $termValues);
            foreach ($termValues as $value) {
                list($text, $attributes) = $this->formatValue($value);
                //get relator terms
                if ($value->property()->term() == "dcterms:contributor") {
                    if ($valueAnnotation = $value->valueAnnotation()) {
                        foreach ($valueAnnotation->values() as $annotationTerm => $propertyData) {
                            if ($propertyData['property']->term() == "bf:role") {
                                $relatorList = [];
                                $relatorString = '';
                                foreach ($propertyData['values'] as $annotationValue) {
                                    array_push($relatorList, $annotationValue);
                                }
                                if ($relatorList) {
                                    $relatorString = ' [' . implode(", ", $relatorList) . ']';
                                    $text = $text . $relatorString;
                                }
                            }
                        }
                    }
                }
                //append
                $this->appendNewElement($oai, $oaiDCterm, $text, $attributes);
            }
        }

        $appendIdentifier = $this->singleIdentifier($item);
        if ($appendIdentifier) {
            $this->appendNewElement($oai, 'dc:identifier', $appendIdentifier);
        }
        // Append thumbnail for record
        $thumbnail = $item->thumbnail();
        $primaryMedia = $item->primaryMedia();
        $thumbnailURL = '';
        if (($primaryMedia) && ($primaryMedia->ingester() == 'remoteFile')) {
            $thumbnailURL = $primaryMedia->mediaData()['thumbnail'];
        }
        if (($thumbnailURL == '') && ($primaryMedia) && ($primaryMedia->hasThumbnails())) {
            $thumbnailURL = $primaryMedia->thumbnailUrl('medium');
        }
        $thumbnailURL = $thumbnail ? $thumbnail->assetUrl() : $thumbnailURL;
        if ($thumbnailURL) {
            $this->appendNewElement($oai, 'dc:identifier', $thumbnailURL);
        }

        // Also append an identifier for each file
        if ($this->params['expose_media']) {
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
