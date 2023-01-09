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
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Class implementing MODS metadata output format.
 *
 * @link https://www.loc.gov/standards/mods/
 */
class Mods extends AbstractMetadata
{
    /** OAI-PMH metadata prefix */
    const METADATA_PREFIX = 'mods';

    /** XML namespace for output format */
    const METADATA_NAMESPACE = 'http://www.loc.gov/mods/v3';

    /** XML schema for output format */
    const METADATA_SCHEMA = 'http://www.loc.gov/standards/mods/v3/mods-3-3.xsd';

    /**
     * Appends MODS metadata.
     *
     * @link http://www.loc.gov/standards/mods/dcsimple-mods.html
     *
     * {@inheritDoc}
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item): void
    {
        $document = $metadataElement->ownerDocument;

        $mods = $document->createElementNS(self::METADATA_NAMESPACE, 'mods');
        $metadataElement->appendChild($mods);

        /* Must manually specify XML schema uri per spec, but DOM won't include
         * a redundant xmlns:xsi attribute, so we just set the attribute
         */
        $mods->setAttribute('xmlns:xsi', self::XML_SCHEMA_NAMESPACE_URI);
        $mods->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE
            . ' ' . self::METADATA_SCHEMA);

        $values = $this->filterValuesPre($item);

        $titles = $values['dcterms:title']['values'] ?? [];
        foreach ($titles as $title) {
            $titleInfo = $this->appendNewElement($mods, 'titleInfo');
            $this->appendNewElement($titleInfo, 'title', (string) $title);
        }

        $creators = $values['dcterms:creator']['values'] ?? [];
        foreach ($creators as $creator) {
            $name = $this->appendNewElement($mods, 'name');
            $this->appendNewElement($name, 'namePart', (string) $creator);
            $role = $this->appendNewElement($name, 'role');
            $roleTerm = $this->appendNewElement($role, 'roleTerm', 'creator');
            $roleTerm->setAttribute('type', 'text');
        }

        $contributors = $values['dcterms:contributor']['values'] ?? [];
        foreach ($contributors as $contributor) {
            $name = $this->appendNewElement($mods, 'name');
            $this->appendNewElement($name, 'namePart', (string) $contributor);
            $role = $this->appendNewElement($name, 'role');
            $roleTerm = $this->appendNewElement($role, 'roleTerm', 'contributor');
            $roleTerm->setAttribute('type', 'text');
        }

        $subjects = $values['dcterms:subject']['values'] ?? [];
        foreach ($subjects as $subject) {
            $subjectTag = $this->appendNewElement($mods, 'subject');
            $this->appendNewElement($subjectTag, 'topic', (string) $subject);
        }

        $descriptions = $values['dcterms:description']['values'] ?? [];
        foreach ($descriptions as $description) {
            $this->appendNewElement($mods, 'note', (string) $description);
        }

        $formats = $values['dcterms:format']['values'] ?? [];
        foreach ($formats as $format) {
            $physicalDescription = $this->appendNewElement($mods, 'physicalDescription');
            $this->appendNewElement($physicalDescription, 'form', (string) $format);
        }

        $languages = $values['dcterms:language']['values'] ?? [];
        foreach ($languages as $language) {
            $languageElement = $this->appendNewElement($mods, 'language');
            $languageTerm = $this->appendNewElement($languageElement, 'languageTerm', (string) $language);
            $languageTerm->setAttribute('type', 'text');
        }

        $rights = $values['dcterms:rights']['values'] ?? [];
        foreach ($rights as $right) {
            $this->appendNewElement($mods, 'accessCondition', (string) $right);
        }

        $types = $values['dcterms:type']['values'] ?? [];
        foreach ($types as $type) {
            $this->appendNewElement($mods, 'genre', (string) $type);
        }

        $identifiers = $values['dcterms:identifier']['values'] ?? [];
        foreach ($identifiers as $identifier) {
            $text = (string) $identifier;
            $idElement = $this->appendNewElement($mods, 'identifier', $text);
            if ($this->_isUrl($text)) {
                $idElement->setAttribute('type', 'uri');
            } else {
                $idElement->setAttribute('type', 'local');
            }
        }

        $sources = $values['dcterms:source']['values'] ?? [];
        foreach ($sources as $source) {
            $this->_addRelatedItem($mods, (string) $source, true);
        }

        $relations = $values['dcterms:relation']['values'] ?? [];
        foreach ($relations as $relation) {
            $this->_addRelatedItem($mods, (string) $relation);
        }

        $location = $this->appendNewElement($mods, 'location');
        if ($this->isGlobalRepository()) {
            if ($this->params['main_site_slug']) {
                $url = $item->siteUrl($this->params['main_site_slug'], $this->params['append_identifier_global'] === 'absolute_site_url');
            } else {
                $url = $item->apiUrl();
            }
        } else {
            $url = $item->siteUrl(null, $this->params['append_identifier_site'] === 'absolute_site_url');
        }
        $url = $this->appendNewElement($location, 'url', $url);
        $url->setAttribute('usage', 'primary display');

        $publishers = $values['dcterms:publisher']['values'] ?? [];
        $dates = $values['dcterms:date']['values'] ?? [];

        // Empty originInfo sections are illegal
        if (count($publishers) + count($dates) > 0) {
            $originInfo = $this->appendNewElement($mods, 'originInfo');

            foreach ($publishers as $publisher) {
                $this->appendNewElement($originInfo, 'publisher', (string) $publisher);
            }

            foreach ($dates as $date) {
                $this->appendNewElement($originInfo, 'dateOther', (string) $date);
            }
        }

        $recordInfo = $this->appendNewElement($mods, 'recordInfo');
        $this->appendNewElement($recordInfo, 'recordIdentifier', $item->id());
    }

    /**
     * Add a relatedItem element.
     *
     * Checks the $text to see if it looks like a URL, and creates a
     * location subelement if so. Otherwise, a titleInfo is used.
     *
     * @param DomElement $mods
     * @param string $text
     * @param bool $original
     */
    private function _addRelatedItem($mods, $text, $original = false): void
    {
        $relatedItem = $this->appendNewElement($mods, 'relatedItem');
        if ($this->_isUrl($text)) {
            $titleInfo = $this->appendNewElement($relatedItem, 'titleInfo');
            $this->appendNewElement($titleInfo, 'title', $text);
        } else {
            $location = $this->appendNewElement($relatedItem, 'location');
            $this->appendNewElement($location, 'url', $text);
        }
        if ($original) {
            $relatedItem->setAttribute('type', 'original');
        }
    }

    /**
     * Returns whether the given test is (looks like) a URL.
     *
     * @param string $text
     * @return bool
     */
    private function _isUrl($text)
    {
        return strncmp($text, 'http://', 7) || strncmp($text, 'https://', 8);
    }
}
