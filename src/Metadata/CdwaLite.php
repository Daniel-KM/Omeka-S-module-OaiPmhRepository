<?php
/**
 * @author John Flatness
 * @copyright Copyright 2009 John Flatness
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace OaiPmhRepository\Metadata;

use OaiPmhRepository\OaiIdentifier;

/**
 * Class implmenting metadata output CDWA Lite.
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
     * Appends a metadata element, an child element with the required format,
     * and further children for each of the Dublin Core fields present in the
     * item.
     */
    public function appendMetadata($metadataElement)
    {
        $cdwaliteWrap = $this->document->createElementNS(
            self::METADATA_NAMESPACE, 'cdwalite:cdwaliteWrap');
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
        $types = $this->item->value('dcterms:type', ['all' => true]);
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
        $titles = $this->item->value('dcterms:title', ['all' => true]);
        $titleWrap = $this->appendNewElement($descriptive, 'cdwalite:titleWrap');

        foreach ($titles as $title) {
            $titleSet = $this->appendNewElement($titleWrap, 'cdwalite:titleSet');
            $this->appendNewElement($titleSet, 'cdwalite:title', (string) $title);
        }

        /* Creator => displayCreator
         * Required.  Fill with 'Unknown' if omitted.
         * Non-repeatable, implode for inclusion of many creators.
         */
        $creators = $this->item->value('dcterms:creator', ['all' => true]);

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
        $date = $this->item->value('dcterms:date');
        $dateText = $date ? (string) $date : 'Unknown';
        $this->appendNewElement($descriptive, 'cdwalite:displayCreationDate', $dateText);

        /* Date => indexingDatesWrap->indexingDatesSet
         * Map to both earliest and latest date
         * Required.  Fill with 'Unknown' if omitted.
         */
        $indexingDatesWrap = $this->appendNewElement($descriptive, 'cdwalite:indexingDatesWrap');
        $dates = $this->item->value('dcterms:date', ['all' => true]);
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
        $subjects = $this->item->value('dcterms:subject', ['all' => true]);
        $classWrap = $this->appendNewElement($descriptive, 'cdwalite:classWrap');
        foreach ($subjects as $subject) {
            $this->appendNewElement($classWrap, 'cdwalite:classification', (string) $subject);
        }

        /* Description => descriptiveNoteWrap->descriptiveNoteSet->descriptiveNote
         * Not required.
         */
        $descriptions = $this->item->value('dcterms:description', ['all' => true]);
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
        $rights = $this->item->value('dcterms:rights', ['all' => true]);
        foreach ($rights as $right) {
            $this->appendNewElement($administrative, 'cdwalite:rightsWork', (string) $right);
        }

        /* id => recordWrap->recordID
         * 'item' => recordWrap-recordType
         * Required.
         */
        $recordWrap = $this->appendNewElement($administrative, 'cdwalite:recordWrap');
        $this->appendNewElement($recordWrap, 'cdwalite:recordID', $this->item->id());
        $this->appendNewElement($recordWrap, 'cdwalite:recordType', 'item');
        $recordInfoWrap = $this->appendNewElement($recordWrap, 'cdwalite:recordInfoWrap');
        $recordInfoID = $this->appendNewElement($recordInfoWrap, 'cdwalite:recordInfoID', OaiIdentifier::itemToOaiId($this->item->id()));
        $recordInfoID->setAttribute('cdwalite:type', 'oai');

        /* file link => resourceWrap->resourceSet->linkResource
         * Not required.
         */
        $settings = $this->serviceLocator->get('Omeka\Settings');
        if ($settings->get('oaipmh_repository_expose_files', false)) {
            $mediaList = $this->item->media();
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

    /**
     * Returns the OAI-PMH metadata prefix for the output format.
     *
     * @return string Metadata prefix
     */
    public function getMetadataPrefix()
    {
        return self::METADATA_PREFIX;
    }

    /**
     * Returns the XML schema for the output format.
     *
     * @return string XML schema URI
     */
    public function getMetadataSchema()
    {
        return self::METADATA_SCHEMA;
    }

    /**
     * Returns the XML namespace for the output format.
     *
     * @return string XML namespace URI
     */
    public function getMetadataNamespace()
    {
        return self::METADATA_NAMESPACE;
    }
}
