<?php declare(strict_types=1);
/**
 * @author Daniel Berthereau
 * @copyright Daniel Berthereau, 2026
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use DOMElement;
use OaiPmhRepository\OaiPmh\Plugin\OaiIdentifier;
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Class implementing metadata output for LIDO format (LIDO-MC profile).
 *
 * LIDO (Lightweight Information Describing Objects) is an XML harvesting
 * schema for cultural heritage objects. This implementation uses the LIDO-MC
 * French application profile adopted by the French Ministry of Culture.
 *
 * @link https://lido-schema.org/
 * @link https://lido-schema.org/documents/primer/latest/lido-primer.html
 * @link https://www.culture.gouv.fr/thematiques/innovation-numerique/faciliter-l-acces-aux-donnees-et-aux-contenus-culturels/partager-et-valoriser-les-donnees-et-les-contenus-culturels/partager-facilement-les-donnees-culturelles-avec-le-profil-d-application-lido-mc
 */
class Lido extends AbstractMetadata
{
    /** OAI-PMH metadata prefix */
    const METADATA_PREFIX = 'lido';

    /** XML namespace for output format */
    const METADATA_NAMESPACE = 'http://www.lido-schema.org';

    /** XML schema for output format */
    const METADATA_SCHEMA = 'http://lido-schema.org/schema/v1.1/lido-v1.1.xsd';

    /**
     * Appends LIDO-MC metadata.
     *
     * {@inheritDoc}
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item): void
    {
        $document = $metadataElement->ownerDocument;

        $lido = $document->createElementNS(self::METADATA_NAMESPACE, 'lido:lido');
        $metadataElement->appendChild($lido);

        $lido->setAttribute('xmlns:lido', self::METADATA_NAMESPACE);
        $lido->setAttribute('xmlns:gml', 'http://www.opengis.net/gml');
        $lido->setAttribute('xmlns:xsi', self::XML_SCHEMA_NAMESPACE_URI);
        $lido->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE
            . ' ' . self::METADATA_SCHEMA);

        $values = $this->filterValuesPre($item);

        // lidoRecID (mandatory): unique record identifier.
        $this->appendNewElement($lido, 'lido:lidoRecID', OaiIdentifier::itemToOaiId($item), [
            'lido:type' => 'local',
        ]);

        // Detect language from item or use default.
        $lang = $this->detectLanguage($item) ?: 'fr';

        // descriptiveMetadata (mandatory).
        $this->appendDescriptiveMetadata($lido, $item, $values, $lang);

        // administrativeMetadata (mandatory).
        $this->appendAdministrativeMetadata($lido, $item, $values, $lang);
    }

    /**
     * Append descriptive metadata section.
     */
    protected function appendDescriptiveMetadata(
        DOMElement $lido,
        ItemRepresentation $item,
        array $values,
        string $lang
    ): void {
        $descriptive = $this->appendNewElement($lido, 'lido:descriptiveMetadata');
        $descriptive->setAttribute('xml:lang', $lang);

        // objectClassificationWrap (contains mandatory objectWorkType).
        $this->appendObjectClassificationWrap($descriptive, $item, $values);

        // objectIdentificationWrap (contains mandatory titleSet).
        $this->appendObjectIdentificationWrap($descriptive, $item, $values);

        // eventWrap (creation/production events).
        $this->appendEventWrap($descriptive, $item, $values);

        // objectRelationWrap (subjects and related works).
        $this->appendObjectRelationWrap($descriptive, $item, $values);
    }

    /**
     * Append object classification wrap.
     */
    protected function appendObjectClassificationWrap(
        DOMElement $descriptive,
        ItemRepresentation $item,
        array $values
    ): void {
        $classificationWrap = $this->appendNewElement($descriptive, 'lido:objectClassificationWrap');

        // objectWorkTypeWrap → objectWorkType (mandatory).
        $objectWorkTypeWrap = $this->appendNewElement($classificationWrap, 'lido:objectWorkTypeWrap');

        $types = $values['dcterms:type']['values'] ?? [];
        if (empty($types)) {
            // Use resource class if available, otherwise "Unknown".
            $class = $item->resourceClass();
            if ($class) {
                $typeText = $item->displayResourceClassLabel() ?: $class->localName();
            } else {
                $typeText = 'Unknown';
            }
            $objectWorkType = $this->appendNewElement($objectWorkTypeWrap, 'lido:objectWorkType');
            $this->appendNewElement($objectWorkType, 'lido:term', $typeText);
        } else {
            foreach ($types as $type) {
                [$text, $attributes] = $this->formatValue($type);
                if (strlen($text)) {
                    $objectWorkType = $this->appendNewElement($objectWorkTypeWrap, 'lido:objectWorkType');
                    $this->appendNewElement($objectWorkType, 'lido:term', $text);
                }
            }
        }

        // classificationWrap (optional additional classifications).
        // Could be extended for resource templates or custom vocabularies.
    }

    /**
     * Append object identification wrap.
     */
    protected function appendObjectIdentificationWrap(
        DOMElement $descriptive,
        ItemRepresentation $item,
        array $values
    ): void {
        $identificationWrap = $this->appendNewElement($descriptive, 'lido:objectIdentificationWrap');

        // titleWrap → titleSet → appellationValue (mandatory).
        $titleWrap = $this->appendNewElement($identificationWrap, 'lido:titleWrap');
        $titles = $values['dcterms:title']['values'] ?? [];
        $alternatives = $values['dcterms:alternative']['values'] ?? [];

        if (empty($titles)) {
            $titleSet = $this->appendNewElement($titleWrap, 'lido:titleSet');
            $this->appendNewElement($titleSet, 'lido:appellationValue', $item->displayTitle('[Untitled]'), [
                'lido:pref' => 'preferred',
            ]);
        } else {
            $isFirst = true;
            foreach ($titles as $title) {
                [$text, $attributes] = $this->formatValue($title);
                if (strlen($text)) {
                    $titleSet = $this->appendNewElement($titleWrap, 'lido:titleSet');
                    $lidoAttr = $isFirst ? ['lido:pref' => 'preferred'] : ['lido:pref' => 'alternate'];
                    $this->appendNewElement($titleSet, 'lido:appellationValue', $text, $lidoAttr);
                    $isFirst = false;
                }
            }
        }
        foreach ($alternatives as $alt) {
            [$text, $attributes] = $this->formatValue($alt);
            if (strlen($text)) {
                $titleSet = $this->appendNewElement($titleWrap, 'lido:titleSet');
                $this->appendNewElement($titleSet, 'lido:appellationValue', $text, [
                    'lido:pref' => 'alternate',
                ]);
            }
        }

        // repositoryWrap (institution).
        $publishers = $values['dcterms:publisher']['values'] ?? [];
        $locators = $values['bibo:locator']['values'] ?? [];
        if (!empty($publishers) || !empty($locators)) {
            $repositoryWrap = $this->appendNewElement($identificationWrap, 'lido:repositoryWrap');
            $repositorySet = $this->appendNewElement($repositoryWrap, 'lido:repositorySet');
            $repositorySet->setAttribute('lido:type', 'current');

            foreach ($publishers as $publisher) {
                [$text, $attributes] = $this->formatValue($publisher);
                if (strlen($text)) {
                    $repositoryName = $this->appendNewElement($repositorySet, 'lido:repositoryName');
                    $legalBodyName = $this->appendNewElement($repositoryName, 'lido:legalBodyName');
                    $this->appendNewElement($legalBodyName, 'lido:appellationValue', $text);
                }
            }
            foreach ($locators as $locator) {
                [$text, $attributes] = $this->formatValue($locator);
                if (strlen($text)) {
                    $this->appendNewElement($repositorySet, 'lido:workID', $text, [
                        'lido:type' => 'inventory number',
                    ]);
                }
            }
        }

        // objectDescriptionWrap.
        $descriptions = $values['dcterms:description']['values'] ?? [];
        if (!empty($descriptions)) {
            $descriptionWrap = $this->appendNewElement($identificationWrap, 'lido:objectDescriptionWrap');
            foreach ($descriptions as $description) {
                [$text, $attributes] = $this->formatValue($description);
                if (strlen($text)) {
                    $descriptionSet = $this->appendNewElement($descriptionWrap, 'lido:objectDescriptionSet');
                    $this->appendNewElement($descriptionSet, 'lido:descriptiveNoteValue', $text);
                }
            }
        }

        // objectMeasurementsWrap.
        $extents = $values['dcterms:extent']['values'] ?? [];
        if (!empty($extents)) {
            $measurementsWrap = $this->appendNewElement($identificationWrap, 'lido:objectMeasurementsWrap');
            foreach ($extents as $extent) {
                [$text, $attributes] = $this->formatValue($extent);
                if (strlen($text)) {
                    $measurementsSet = $this->appendNewElement($measurementsWrap, 'lido:objectMeasurementsSet');
                    $this->appendNewElement($measurementsSet, 'lido:displayObjectMeasurements', $text);
                }
            }
        }
    }

    /**
     * Append event wrap (production, creation, etc.).
     */
    protected function appendEventWrap(
        DOMElement $descriptive,
        ItemRepresentation $item,
        array $values
    ): void {
        $creators = $values['dcterms:creator']['values'] ?? [];
        $contributors = $values['dcterms:contributor']['values'] ?? [];
        $createdDates = $values['dcterms:created']['values'] ?? [];
        $dates = $values['dcterms:date']['values'] ?? [];
        $spatials = $values['dcterms:spatial']['values'] ?? [];
        $mediums = $values['dcterms:medium']['values'] ?? [];
        $provenances = $values['dcterms:provenance']['values'] ?? [];

        // Only create eventWrap if we have event-related data.
        $hasEventData = !empty($creators) || !empty($contributors)
            || !empty($createdDates) || !empty($dates)
            || !empty($spatials) || !empty($mediums) || !empty($provenances);

        if (!$hasEventData) {
            return;
        }

        $eventWrap = $this->appendNewElement($descriptive, 'lido:eventWrap');

        // Production/Creation event.
        if (!empty($creators) || !empty($createdDates) || !empty($dates)
            || !empty($spatials) || !empty($mediums)) {
            $eventSet = $this->appendNewElement($eventWrap, 'lido:eventSet');
            $event = $this->appendNewElement($eventSet, 'lido:event');

            // eventType (mandatory for event).
            $eventType = $this->appendNewElement($event, 'lido:eventType');
            $this->appendNewElement($eventType, 'lido:term', 'Production');

            // eventActor (creators).
            foreach ($creators as $creator) {
                [$text, $attributes] = $this->formatValue($creator);
                if (strlen($text)) {
                    $eventActor = $this->appendNewElement($event, 'lido:eventActor');
                    $actorInRole = $this->appendNewElement($eventActor, 'lido:actorInRole');
                    $actor = $this->appendNewElement($actorInRole, 'lido:actor');
                    $nameActorSet = $this->appendNewElement($actor, 'lido:nameActorSet');
                    $this->appendNewElement($nameActorSet, 'lido:appellationValue', $text, [
                        'lido:pref' => 'preferred',
                    ]);
                }
            }

            // eventDate.
            $eventDates = !empty($createdDates) ? $createdDates : $dates;
            if (!empty($eventDates)) {
                $eventDate = $this->appendNewElement($event, 'lido:eventDate');
                $firstDate = reset($eventDates);
                [$text, $attributes] = $this->formatValue($firstDate);
                $this->appendNewElement($eventDate, 'lido:displayDate', $text);
            }

            // eventPlace.
            foreach ($spatials as $spatial) {
                [$text, $attributes] = $this->formatValue($spatial);
                // Skip prefixed spatial values (conservation, discovery, etc.).
                if (strlen($text) && strpos($text, ':') === false) {
                    $eventPlace = $this->appendNewElement($event, 'lido:eventPlace');
                    $place = $this->appendNewElement($eventPlace, 'lido:place');
                    $namePlaceSet = $this->appendNewElement($place, 'lido:namePlaceSet');
                    $this->appendNewElement($namePlaceSet, 'lido:appellationValue', $text);
                }
            }

            // eventMaterialsTech.
            if (!empty($mediums)) {
                $eventMaterialsTech = $this->appendNewElement($event, 'lido:eventMaterialsTech');
                foreach ($mediums as $medium) {
                    [$text, $attributes] = $this->formatValue($medium);
                    if (strlen($text)) {
                        $this->appendNewElement($eventMaterialsTech, 'lido:displayMaterialsTech', $text);
                        break; // Only first for display.
                    }
                }
            }
        }
    }

    /**
     * Append object relation wrap (subjects and related works).
     */
    protected function appendObjectRelationWrap(
        DOMElement $descriptive,
        ItemRepresentation $item,
        array $values
    ): void {
        $subjects = $values['dcterms:subject']['values'] ?? [];
        $relations = $values['dcterms:relation']['values'] ?? [];

        if (empty($subjects) && empty($relations)) {
            return;
        }

        $relationWrap = $this->appendNewElement($descriptive, 'lido:objectRelationWrap');

        // subjectWrap.
        if (!empty($subjects)) {
            $subjectWrap = $this->appendNewElement($relationWrap, 'lido:subjectWrap');
            foreach ($subjects as $subject) {
                [$text, $attributes] = $this->formatValue($subject);
                if (strlen($text)) {
                    $subjectSet = $this->appendNewElement($subjectWrap, 'lido:subjectSet');
                    $subjectEl = $this->appendNewElement($subjectSet, 'lido:subject');
                    $subjectConcept = $this->appendNewElement($subjectEl, 'lido:subjectConcept');
                    $this->appendNewElement($subjectConcept, 'lido:term', $text);
                }
            }
        }

        // relatedWorksWrap.
        if (!empty($relations)) {
            $relatedWorksWrap = $this->appendNewElement($relationWrap, 'lido:relatedWorksWrap');
            foreach ($relations as $relation) {
                [$text, $attributes] = $this->formatValue($relation);
                if (strlen($text)) {
                    $relatedWorkSet = $this->appendNewElement($relatedWorksWrap, 'lido:relatedWorkSet');
                    $relatedWork = $this->appendNewElement($relatedWorkSet, 'lido:relatedWork');
                    $object = $this->appendNewElement($relatedWork, 'lido:object');
                    $this->appendNewElement($object, 'lido:objectNote', $text);
                }
            }
        }
    }

    /**
     * Append administrative metadata section.
     */
    protected function appendAdministrativeMetadata(
        DOMElement $lido,
        ItemRepresentation $item,
        array $values,
        string $lang
    ): void {
        $administrative = $this->appendNewElement($lido, 'lido:administrativeMetadata');
        $administrative->setAttribute('xml:lang', $lang);

        // rightsWorkWrap.
        $this->appendRightsWorkWrap($administrative, $item, $values);

        // recordWrap (mandatory).
        $this->appendRecordWrap($administrative, $item, $values);

        // resourceWrap (media files).
        if ($this->params['expose_media']) {
            $this->appendResourceWrap($administrative, $item, $values);
        }
    }

    /**
     * Append rights work wrap.
     */
    protected function appendRightsWorkWrap(
        DOMElement $administrative,
        ItemRepresentation $item,
        array $values
    ): void {
        $rights = $values['dcterms:rights']['values'] ?? [];
        $rightsHolders = $values['dcterms:rightsHolder']['values'] ?? [];
        $licenses = $values['dcterms:license']['values'] ?? [];

        if (empty($rights) && empty($rightsHolders) && empty($licenses)) {
            return;
        }

        $rightsWorkWrap = $this->appendNewElement($administrative, 'lido:rightsWorkWrap');
        $rightsWorkSet = $this->appendNewElement($rightsWorkWrap, 'lido:rightsWorkSet');

        foreach ($rights as $right) {
            [$text, $attributes] = $this->formatValue($right);
            if (strlen($text)) {
                $rightsType = $this->appendNewElement($rightsWorkSet, 'lido:rightsType');
                $this->appendNewElement($rightsType, 'lido:term', $text);
            }
        }

        foreach ($licenses as $license) {
            [$text, $attributes] = $this->formatValue($license);
            if (strlen($text)) {
                $rightsType = $this->appendNewElement($rightsWorkSet, 'lido:rightsType');
                // Check if it's a URI.
                if (filter_var($text, FILTER_VALIDATE_URL)) {
                    $this->appendNewElement($rightsType, 'lido:conceptID', $text, [
                        'lido:type' => 'URI',
                    ]);
                } else {
                    $this->appendNewElement($rightsType, 'lido:term', $text);
                }
            }
        }

        foreach ($rightsHolders as $holder) {
            [$text, $attributes] = $this->formatValue($holder);
            if (strlen($text)) {
                $rightsHolder = $this->appendNewElement($rightsWorkSet, 'lido:rightsHolder');
                $legalBodyName = $this->appendNewElement($rightsHolder, 'lido:legalBodyName');
                $this->appendNewElement($legalBodyName, 'lido:appellationValue', $text);
            }
        }
    }

    /**
     * Append record wrap (mandatory).
     */
    protected function appendRecordWrap(
        DOMElement $administrative,
        ItemRepresentation $item,
        array $values
    ): void {
        $recordWrap = $this->appendNewElement($administrative, 'lido:recordWrap');

        // recordID (mandatory).
        $this->appendNewElement($recordWrap, 'lido:recordID', (string) $item->id(), [
            'lido:type' => 'local',
        ]);

        // recordType (mandatory).
        $recordType = $this->appendNewElement($recordWrap, 'lido:recordType');
        $this->appendNewElement($recordType, 'lido:term', 'item');

        // recordSource (mandatory).
        $recordSource = $this->appendNewElement($recordWrap, 'lido:recordSource');
        $legalBodyName = $this->appendNewElement($recordSource, 'lido:legalBodyName');
        $sourceName = $this->params['oaipmhrepository_name'] ?? 'Omeka S';
        $this->appendNewElement($legalBodyName, 'lido:appellationValue', $sourceName);

        // recordInfoSet.
        $recordInfoSet = $this->appendNewElement($recordWrap, 'lido:recordInfoSet');
        $modified = $item->modified() ?: $item->created();
        if ($modified) {
            $this->appendNewElement($recordInfoSet, 'lido:recordMetadataDate', $modified->format('Y-m-d'), [
                'lido:type' => 'modified',
            ]);
        }

        // Add link to item.
        $appendIdentifier = $this->singleIdentifier($item);
        if ($appendIdentifier) {
            $this->appendNewElement($recordInfoSet, 'lido:recordInfoLink', $appendIdentifier);
        }
    }

    /**
     * Append resource wrap (media files).
     */
    protected function appendResourceWrap(
        DOMElement $administrative,
        ItemRepresentation $item,
        array $values
    ): void {
        $mediaList = $item->media();
        if (empty($mediaList)) {
            return;
        }

        $resourceWrap = $this->appendNewElement($administrative, 'lido:resourceWrap');

        foreach ($mediaList as $media) {
            $resourceSet = $this->appendNewElement($resourceWrap, 'lido:resourceSet');

            // resourceRepresentation.
            $resourceRep = $this->appendNewElement($resourceSet, 'lido:resourceRepresentation');
            $this->appendNewElement($resourceRep, 'lido:linkResource', $media->originalUrl());

            // resourceType.
            $mediaType = $media->mediaType();
            if ($mediaType) {
                $resourceType = $this->appendNewElement($resourceSet, 'lido:resourceType');
                $type = strpos($mediaType, 'image') === 0 ? 'image' : 'other';
                $this->appendNewElement($resourceType, 'lido:term', $type);
            }

            // resourceDescription.
            $title = $media->displayTitle('');
            if (strlen($title)) {
                $this->appendNewElement($resourceSet, 'lido:resourceDescription', $title);
            }
        }
    }

    /**
     * Detect language from item metadata.
     */
    protected function detectLanguage(ItemRepresentation $item): ?string
    {
        $languages = $item->value('dcterms:language', ['all' => true]) ?: [];
        foreach ($languages as $language) {
            $lang = (string) $language;
            // Normalize to 2-letter code.
            if (strlen($lang) >= 2) {
                return substr($lang, 0, 2);
            }
        }
        return null;
    }
}
