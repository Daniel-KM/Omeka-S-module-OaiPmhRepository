<?php
/**
 * @author John Flatness
 * @copyright Copyright 2009 John Flatness
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use DOMElement;
use OaiPmhRepository\OaiPmh\Plugin\OaiIdentifier;
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Class implementing metadata output CDWA Lite.
 *
 * @link http://www.getty.edu/research/conducting_research/standards/cdwa/cdwalite.html
 */
class CdwaLite extends AbstractMetadata
{
    /** OAI-PMH metadata prefix */
    const METADATA_PREFIX = 'cdwalite';

    /** XML namespace for output format */
    const METADATA_NAMESPACE = 'http://www.getty.edu/CDWA/CDWALite';

    /** XML schema for output format */
    const METADATA_SCHEMA = 'http://www.getty.edu/CDWA/CDWALite/CDWALite-xsd-public-v1-1.xsd';

    /**
     * Appends CDWALite metadata.
     *
     * {@inheritDoc}
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item)
    {
        $document = $metadataElement->ownerDocument;
        $cdwaliteWrap = $document->createElementNS(self::METADATA_NAMESPACE,
            'cdwalite:cdwaliteWrap');
        $metadataElement->appendChild($cdwaliteWrap);

        /* Must manually specify XML schema uri per spec, but DOM won't include
         * a redundant xmlns:xsi attribute, so we just set the attribute
         */
        $cdwaliteWrap->setAttribute('xmlns:cdwalite', self::METADATA_NAMESPACE);
        $cdwaliteWrap->setAttribute('xmlns:xsi', self::XML_SCHEMA_NAMESPACE_URI);
        $cdwaliteWrap->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE
            . ' ' . self::METADATA_SCHEMA);

        $cdwalite = $this->appendNewElement($cdwaliteWrap, 'cdwalite:cdwalite');

        /* ====================
         * DESCRIPTIVE METADATA
         * ====================
         */

        $descriptive = $this->appendNewElement($cdwalite, 'cdwalite:descriptiveMetadata');

        /* Type => objectWorkTypeWrap->objectWorkType
         * Required.  Fill with 'Unknown' if omitted.
         */
        $types = $item->value('dcterms:type', ['all' => true, 'default' => []]);
        $types = $this->filterValues($item, 'dcterms:type', $types);
        $objectWorkTypeWrap = $this->appendNewElement($descriptive, 'cdwalite:objectWorkTypeWrap');
        if (empty($types)) {
            $types[] = 'Unknown';
        }

        foreach ($types as $type) {
            $this->appendNewElement($objectWorkTypeWrap, 'cdwalite:objectWorkTypeWrap', (string) $type);
        }

        /* Title => titleWrap->titleSet->title
         * Required.  Fill with 'Unknown' if omitted.
         */
        $titles = $item->value('dcterms:title', ['all' => true, 'default' => []]);
        $titles = $this->filterValues($item, 'dcterms:title', $titles);
        $titleWrap = $this->appendNewElement($descriptive, 'cdwalite:titleWrap');

        foreach ($titles as $title) {
            $titleSet = $this->appendNewElement($titleWrap, 'cdwalite:titleSet');
            $this->appendNewElement($titleSet, 'cdwalite:title', (string) $title);
        }

        /* Creator => displayCreator
         * Required.  Fill with 'Unknown' if omitted.
         * Non-repeatable, implode for inclusion of many creators.
         */
        $creators = $item->value('dcterms:creator', ['all' => true, 'default' => []]);
        $creators = $this->filterValues($item, 'dcterms:creator', $creators);

        $creatorTexts = [];
        foreach ($creators as $creator) {
            $creatorTexts[] = (string) $creator;
        }

        if (empty($creatorTexts)) {
            $creatorTexts[] = 'Unknown';
        }

        $creatorText = implode(', ', $creatorTexts);
        $this->appendNewElement($descriptive, 'cdwalite:displayCreator', $creatorText);

        /* Creator => indexingCreatorWrap->indexingCreatorSet->nameCreatorSet->nameCreator
         * Required.  Fill with 'Unknown' if omitted.
         * Also include roleCreator, fill with 'Unknown', required.
         */
        $indexingCreatorWrap = $this->appendNewElement($descriptive, 'cdwalite:indexingCreatorWrap');
        foreach ($creatorTexts as $creator) {
            $indexingCreatorSet = $this->appendNewElement($indexingCreatorWrap, 'cdwalite:indexingCreatorSet');
            $nameCreatorSet = $this->appendNewElement($indexingCreatorSet, 'cdwalite:nameCreatorSet');
            $this->appendNewElement($nameCreatorSet, 'cdwalite:nameCreator', $creator);
            $this->appendNewElement($indexingCreatorSet, 'cdwalite:roleCreator', 'Unknown');
        }

        /* displayMaterialsTech
         * Required.  No corresponding metadata, fill with 'not applicable'.
         */
        $this->appendNewElement($descriptive, 'cdwalite:displayMaterialsTech', 'not applicable');

        /* Date => displayCreationDate
         * Required. Fill with 'Unknown' if omitted.
         * Non-repeatable, include only first date.
         */
        $date = $item->value('dcterms:date');
        $date = $this->filterValues($item, 'dcterms:date', $date);
        $dateText = $date ? (string) $date : 'Unknown';
        $this->appendNewElement($descriptive, 'cdwalite:displayCreationDate', $dateText);

        /* Date => indexingDatesWrap->indexingDatesSet
         * Map to both earliest and latest date
         * Required.  Fill with 'Unknown' if omitted.
         */
        $indexingDatesWrap = $this->appendNewElement($descriptive, 'cdwalite:indexingDatesWrap');
        $dates = $item->value('dcterms:date', ['all' => true, 'default' => []]);
        $dates = $this->filterValues($item, 'dcterms:date', $dates);
        foreach ($dates as $date) {
            $indexingDatesSet = $this->appendNewElement($indexingDatesWrap, 'cdwalite:indexingDatesSet');
            $this->appendNewElement($indexingDatesSet, 'cdwalite:earliestDate', (string) $date);
            $this->appendNewElement($indexingDatesSet, 'cdwalite:latestDate', (string) $date);
        }

        /* locationWrap->locationSet->locationName
         * Required. No corresponding metadata, fill with 'location unknown'.
         */
        $locationWrap = $this->appendNewElement($descriptive, 'cdwalite:locationWrap');
        $locationSet = $this->appendNewElement($locationWrap, 'cdwalite:locationSet');
        $this->appendNewElement($locationSet, 'cdwalite:locationName', 'location unknown');

        /* Subject => classWrap->classification
         * Not required.
         */
        $subjects = $item->value('dcterms:subject', ['all' => true, 'default' => []]);
        $subjects = $this->filterValues($item, 'dcterms:subject', $subjects);
        $classWrap = $this->appendNewElement($descriptive, 'cdwalite:classWrap');
        foreach ($subjects as $subject) {
            $this->appendNewElement($classWrap, 'cdwalite:classification', (string) $subject);
        }

        /* Description => descriptiveNoteWrap->descriptiveNoteSet->descriptiveNote
         * Not required.
         */
        $descriptions = $item->value('dcterms:description', ['all' => true, 'default' => []]);
        $descriptions = $this->filterValues($item, 'dcterms:description', $descriptions);
        if (!empty($descriptions)) {
            $descriptiveNoteWrap = $this->appendNewElement($descriptive, 'cdwalite:descriptiveNoteWrap');
            foreach ($descriptions as $description) {
                $descriptiveNoteSet = $this->appendNewElement($descriptiveNoteWrap, 'cdwalite:descriptiveNoteSet');
                $this->appendNewElement($descriptiveNoteSet, 'cdwalite:descriptiveNote', (string) $description);
            }
        }

        /* =======================
         * ADMINISTRATIVE METADATA
         * =======================
         */

        $administrative = $this->appendNewElement($cdwalite, 'cdwalite:administrativeMetadata');

        /* Rights => rightsWork
         * Not required.
         */
        $rights = $item->value('dcterms:rights', ['all' => true, 'default' => []]);
        $rights = $this->filterValues($item, 'dcterms:rights', $rights);
        foreach ($rights as $right) {
            $this->appendNewElement($administrative, 'cdwalite:rightsWork', (string) $right);
        }

        /* id => recordWrap->recordID
         * 'item' => recordWrap-recordType
         * Required.
         */
        $recordWrap = $this->appendNewElement($administrative, 'cdwalite:recordWrap');
        $this->appendNewElement($recordWrap, 'cdwalite:recordID', $item->id());
        $this->appendNewElement($recordWrap, 'cdwalite:recordType', 'item');
        $recordInfoWrap = $this->appendNewElement($recordWrap, 'cdwalite:recordInfoWrap');
        $recordInfoID = $this->appendNewElement($recordInfoWrap, 'cdwalite:recordInfoID', OaiIdentifier::itemToOaiId($item->id()));
        $recordInfoID->setAttribute('cdwalite:type', 'oai');

        /* file link => resourceWrap->resourceSet->linkResource
         * Not required.
         */
        if ($this->settings->get('oaipmhrepository_expose_media', false)) {
            $mediaList = $item->media();
            if (!empty($mediaList)) {
                $resourceWrap = $this->appendNewElement($administrative, 'cdwalite:resourceWrap');
                foreach ($mediaList as $media) {
                    $resourceSet = $this->appendNewElement($resourceWrap, 'cdwalite:resourceSet');
                    $this->appendNewElement($resourceSet,
                        'cdwalite:linkResource', $media->originalUrl());
                }
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
