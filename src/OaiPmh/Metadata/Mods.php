<?php
/**
 * @author John Flatness
 * @copyright Copyright 2009 John Flatness
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2017
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use DOMElement;
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Class implmenting MODS metadata output format.
 *
 * @link http://www.loc.gov/standards/mods/
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
     * Appends a metadata element, an child element with the required format,
     * and further children for each of the Dublin Core fields present in the
     * item.
     *
     * @link http://www.loc.gov/standards/mods/dcsimple-mods.html
     *
     * {@inheritDoc}
     * @see \OaiPmhRepository\OaiPmh\Metadata\AbstractMetadata::appendMetadata()
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item)
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

        $titles = $item->value('dcterms:title', ['all' => true]);
        foreach ($titles as $title) {
            $titleInfo = $this->appendNewElement($mods, 'titleInfo');
            $this->appendNewElement($titleInfo, 'title', (string) $title);
        }

        $creators = $item->value('dcterms:creator', ['all' => true]);
        foreach ($creators as $creator) {
            $name = $this->appendNewElement($mods, 'name');
            $this->appendNewElement($name, 'namePart', (string) $creator);
            $role = $this->appendNewElement($name, 'role');
            $roleTerm = $this->appendNewElement($role, 'roleTerm', 'creator');
            $roleTerm->setAttribute('type', 'text');
        }

        $contributors = $item->value('dcterms:contributor', ['all' => true]);
        foreach ($contributors as $contributor) {
            $name = $this->appendNewElement($mods, 'name');
            $this->appendNewElement($name, 'namePart', (string) $contributor);
            $role = $this->appendNewElement($name, 'role');
            $roleTerm = $this->appendNewElement($role, 'roleTerm', 'contributor');
            $roleTerm->setAttribute('type', 'text');
        }

        $subjects = $item->value('dcterms:contributor', ['all' => true]);
        foreach ($subjects as $subject) {
            $subjectTag = $this->appendNewElement($mods, 'subject');
            $this->appendNewElement($subjectTag, 'topic', (string) $subject);
        }

        $descriptions = $item->value('dcterms:description', ['all' => true]);
        foreach ($descriptions as $description) {
            $this->appendNewElement($mods, 'note', (string) $description);
        }

        $formats = $item->value('dcterms:format', ['all' => true]);
        foreach ($formats as $format) {
            $physicalDescription = $this->appendNewElement($mods, 'physicalDescription');
            $this->appendNewElement($physicalDescription, 'form', (string) $format);
        }

        $languages = $item->value('dcterms:language', ['all' => true]);
        foreach ($languages as $language) {
            $languageElement = $this->appendNewElement($mods, 'language');
            $languageTerm = $this->appendNewElement($languageElement, 'languageTerm', (string) $language);
            $languageTerm->setAttribute('type', 'text');
        }

        $rights = $item->value('dcterms:rights', ['all' => true]);
        foreach ($rights as $right) {
            $this->appendNewElement($mods, 'accessCondition', (string) $right);
        }

        $types = $item->value('dcterms:type', ['all' => true]);
        foreach ($types as $type) {
            $this->appendNewElement($mods, 'genre', (string) $type);
        }

        $identifiers = $item->value('dcterms:identifier', ['all' => true]);
        foreach ($identifiers as $identifier) {
            $text = (string) $identifier;
            $idElement = $this->appendNewElement($mods, 'identifier', $text);
            if ($this->_isUrl($text)) {
                $idElement->setAttribute('type', 'uri');
            } else {
                $idElement->setAttribute('type', 'local');
            }
        }

        $sources = $item->value('dcterms:source', ['all' => true]);
        foreach ($sources as $source) {
            $this->_addRelatedItem($mods, (string) $source, true);
        }

        $relations = $item->value('dcterms:relation', ['all' => true]);
        foreach ($relations as $relation) {
            $this->_addRelatedItem($mods, (string) $relation);
        }

        $location = $this->appendNewElement($mods, 'location');
        $url = $this->appendNewElement($location, 'url', $item->siteUrl());
        $url->setAttribute('usage', 'primary display');

        $publishers = $item->value('dcterms:publisher', ['all' => true]);
        $dates = $item->value('dcterms:date', ['all' => true]);

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
     * @param string     $text
     * @param bool       $original
     */
    private function _addRelatedItem($mods, $text, $original = false)
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
     *
     * @return bool
     */
    private function _isUrl($text)
    {
        return strncmp($text, 'http://', 7) || strncmp($text, 'https://', 8);
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
