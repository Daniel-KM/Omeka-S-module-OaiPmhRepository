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
        $titles = $this->filterValues($item, 'dcterms:title', $titles);
        foreach ($titles as $title) {
            $titleInfo = $this->appendNewElement($mods, 'titleInfo');
            $this->appendNewElement($titleInfo, 'title', (string) $title);
        }

        $creators = $item->value('dcterms:creator', ['all' => true]);
        $creators = $this->filterValues($item, 'dcterms:creator', $creators);
        foreach ($creators as $creator) {
            $name = $this->appendNewElement($mods, 'name');
            $this->appendNewElement($name, 'namePart', (string) $creator);
            $role = $this->appendNewElement($name, 'role');
            $roleTerm = $this->appendNewElement($role, 'roleTerm', 'creator');
            $roleTerm->setAttribute('type', 'text');
        }

        $contributors = $item->value('dcterms:contributor', ['all' => true]);
        $contributors = $this->filterValues($item, 'dcterms:contributor', $contributors);
        foreach ($contributors as $contributor) {
            $name = $this->appendNewElement($mods, 'name');
            $this->appendNewElement($name, 'namePart', (string) $contributor);
            $role = $this->appendNewElement($name, 'role');
            $roleTerm = $this->appendNewElement($role, 'roleTerm', 'contributor');
            $roleTerm->setAttribute('type', 'text');
        }

        $subjects = $item->value('dcterms:subject', ['all' => true]);
        $subjects = $this->filterValues($item, 'dcterms:subject', $subjects);
        foreach ($subjects as $subject) {
            $subjectTag = $this->appendNewElement($mods, 'subject');
            $this->appendNewElement($subjectTag, 'topic', (string) $subject);
        }

        $descriptions = $item->value('dcterms:description', ['all' => true]);
        $descriptions = $this->filterValues($item, 'dcterms:description', $descriptions);
        foreach ($descriptions as $description) {
            $this->appendNewElement($mods, 'note', (string) $description);
        }

        $formats = $item->value('dcterms:format', ['all' => true]);
        $formats = $this->filterValues($item, 'dcterms:format', $formats);
        foreach ($formats as $format) {
            $physicalDescription = $this->appendNewElement($mods, 'physicalDescription');
            $this->appendNewElement($physicalDescription, 'form', (string) $format);
        }

        $languages = $item->value('dcterms:language', ['all' => true]);
        $languages = $this->filterValues($item, 'dcterms:language', $languages);
        foreach ($languages as $language) {
            $languageElement = $this->appendNewElement($mods, 'language');
            $languageTerm = $this->appendNewElement($languageElement, 'languageTerm', (string) $language);
            $languageTerm->setAttribute('type', 'text');
        }

        $rights = $item->value('dcterms:rights', ['all' => true]);
        $rights = $this->filterValues($item, 'dcterms:rights', $rights);
        foreach ($rights as $right) {
            $this->appendNewElement($mods, 'accessCondition', (string) $right);
        }

        $types = $item->value('dcterms:type', ['all' => true]);
        $types = $this->filterValues($item, 'dcterms:type', $types);
        foreach ($types as $type) {
            $this->appendNewElement($mods, 'genre', (string) $type);
        }

        $identifiers = $item->value('dcterms:identifier', ['all' => true]);
        $identifiers = $this->filterValues($item, 'dcterms:identifier', $identifiers);
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
        $sources = $this->filterValues($item, 'dcterms:source', $sources);
        foreach ($sources as $source) {
            $this->_addRelatedItem($mods, (string) $source, true);
        }

        $relations = $item->value('dcterms:relation', ['all' => true]);
        $relations = $this->filterValues($item, 'dcterms:relation', $relations);
        foreach ($relations as $relation) {
            $this->_addRelatedItem($mods, (string) $relation);
        }

        $location = $this->appendNewElement($mods, 'location');
        if ($this->isGlobalRepository()) {
            $mainSite = $this->settings->get('default_site');
            if ($mainSite) {
                $mainSiteSlug = $item->getServiceLocator()->get('ControllerPluginManager')
                    ->get('api')->read('sites', $mainSite)->getContent()->slug();
                $append = $this->settings->get('oaipmhrepository_append_identifier_global');
                $url = $item->siteUrl($mainSiteSlug, $append === 'absolute_site_url');
            } else {
                $url = $item->apiUrl();
            }
        } else {
            $append = $this->settings->get('oaipmhrepository_append_identifier_site');
            $url = $item->siteUrl(null, $append === 'absolute_site_url');
        }
        $url = $this->appendNewElement($location, 'url', $url);
        $url->setAttribute('usage', 'primary display');

        $publishers = $item->value('dcterms:publisher', ['all' => true]);
        $publishers = $this->filterValues($item, 'dcterms:publishers', $publishers);
        $dates = $item->value('dcterms:date', ['all' => true]);
        $dates = $this->filterValues($item, 'dcterms:date', $dates);

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
