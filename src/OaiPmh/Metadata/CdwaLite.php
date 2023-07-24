<?php declare(strict_types=1);
/**
 * @author John Flatness
 * @copyright Copyright 2009 John Flatness
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2023
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
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item): void
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

        $values = $this->filterValuesPre($item);

        /* ====================
         * DESCRIPTIVE METADATA
         * ====================
         */

        $descriptive = $this->appendNewElement($cdwalite, 'cdwalite:descriptiveMetadata');

        /* Type => objectWorkTypeWrap->objectWorkType
         * Required.  Fill with 'Unknown' if omitted.
         */
        $types = $values['dcterms:type']['values'] ?? [];
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
        $titles = $values['dcterms:title']['values'] ?? [];
        $titleWrap = $this->appendNewElement($descriptive, 'cdwalite:titleWrap');

        foreach ($titles as $title) {
            $titleSet = $this->appendNewElement($titleWrap, 'cdwalite:titleSet');
            $this->appendNewElement($titleSet, 'cdwalite:title', (string) $title);
        }

        /* Creator => displayCreator
         * Required.  Fill with 'Unknown' if omitted.
         * Non-repeatable, implode for inclusion of many creators.
         */
        $creators = $values['dcterms:creator']['values'] ?? [];

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
         * FIXME Which date to use for cdwalite:displayCreationDate? dcterms:created? item created?
         */
        $dates = $values['dcterms:date']['values'] ?? [];
        $date = count($dates) ? reset($dates) : null;
        $dateText = (string) $date ?: 'Unknown'; // @translate
        $this->appendNewElement($descriptive, 'cdwalite:displayCreationDate', $dateText);

        /* Date => indexingDatesWrap->indexingDatesSet
         * Map to both earliest and latest date
         * Required.  Fill with 'Unknown' if omitted.
         */
        $indexingDatesWrap = $this->appendNewElement($descriptive, 'cdwalite:indexingDatesWrap');
        $dates = $values['dcterms:date']['values'] ?? [];
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
        $subjects = $values['dcterms:subject']['values'] ?? [];
        $classWrap = $this->appendNewElement($descriptive, 'cdwalite:classWrap');
        foreach ($subjects as $subject) {
            $this->appendNewElement($classWrap, 'cdwalite:classification', (string) $subject);
        }

        /* Description => descriptiveNoteWrap->descriptiveNoteSet->descriptiveNote
         * Not required.
         */
        $descriptions = $values['dcterms:description']['values'] ?? [];
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
        $rights = $values['dcterms:rights']['values'] ?? [];
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
        $recordInfoID = $this->appendNewElement($recordInfoWrap, 'cdwalite:recordInfoID', OaiIdentifier::itemToOaiId($item));
        $recordInfoID->setAttribute('cdwalite:type', 'oai');

        /* file link => resourceWrap->resourceSet->linkResource
         * Not required.
         */
        if ($this->params['expose_media']) {
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
}
