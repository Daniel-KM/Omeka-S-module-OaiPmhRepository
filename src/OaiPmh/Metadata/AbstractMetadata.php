<?php declare(strict_types=1);
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use ArrayObject;
use DOMElement;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use OaiPmhRepository\OaiPmh\AbstractXmlGenerator;
use OaiPmhRepository\OaiPmh\OaiSet\OaiSetInterface;
use OaiPmhRepository\OaiPmh\Plugin\OaiIdentifier;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Settings\SettingsInterface;

/**
 * Abstract class on which all other metadata format handlers are based.
 * Includes logic for all metadata-independent record output.
 *
 * @todo Migration to PHP 5.3 will allow the abstract getter functions to be
 *       static, as they should be
 */
abstract class AbstractMetadata extends AbstractXmlGenerator implements MetadataInterface, EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * The class used to create the set data (spec, name and description).
     *
     * @var OaiSetInterface
     */
    protected $oaiSet;

    /**
     * @var bool
     */
    protected $isGlobalRepository;

    /**
     * @var array
     */
    protected $params = [];

    public function setSettings(SettingsInterface $settings): void
    {
        $this->settings = $settings;
    }

    public function setOaiSet(OaiSetInterface $oaiSet): void
    {
        $this->oaiSet = $oaiSet;
    }

    public function getOaiSet()
    {
        return $this->oaiSet;
    }

    public function setIsGlobalRepository($isGlobalRepository): void
    {
        $this->isGlobalRepository = $isGlobalRepository;
    }

    public function setParams(array $params): MetadataInterface
    {
        $this->params = $params;
        return $this;
    }

    public function declareMetadataFormat(DOMElement $parent): void
    {
        $elements = [
            'metadataPrefix' => $this->getMetadataPrefix(),
            'schema' => $this->getMetadataSchema(),
            'metadataNamespace' => $this->getMetadataNamespace(),
        ];
        $this->createElementWithChildren($parent, 'metadataFormat', $elements);
    }

    /**
     * Filter the query for the two main List verbs.
     *
     * @see \OaiPmhRepository\OaiPmh\ResponseGenerator::listResponse()
     *
     * @param ArrayObject $query
     */
    public function filterList(ArrayObject $query): void
    {
    }

    public function appendRecord(DOMElement $parent, ItemRepresentation $item): void
    {
        $document = $parent->ownerDocument;
        $record = $document->createElement('record');
        $parent->appendChild($record);
        $this->appendHeader($record, $item);

        $metadata = $document->createElement('metadata');
        $record->appendChild($metadata);
        $this->appendMetadata($metadata, $item);
    }

    public function appendHeader(DOMElement $parent, ItemRepresentation $item): void
    {
        $headerData = [];
        $headerData['identifier'] = OaiIdentifier::itemToOaiId($item->id());

        $datestamp = $item->modified();
        if (!$datestamp) {
            $datestamp = $item->created();
        }
        $dateFormat = \OaiPmhRepository\OaiPmh\Plugin\Date::OAI_DATE_FORMAT;
        $headerData['datestamp'] = $datestamp->format($dateFormat);

        $header = $this->createElementWithChildren($parent, 'header', $headerData);
        $setSpecs = $this->oaiSet->listSetSpecs($item);
        foreach ($setSpecs as $setSpec) {
            $this->appendNewElement($header, 'setSpec', $setSpec);
        }
    }

    protected function isGlobalRepository()
    {
        return $this->isGlobalRepository;
    }

    protected function singleIdentifier(AbstractResourceEntityRepresentation $resource)
    {
        if ($this->isGlobalRepository()) {
            $append = $this->params['append_identifier_global'];
            switch ($append) {
                default:
                case 'api_url':
                    return $resource->apiUrl();
                case 'relative_site_url':
                case 'absolute_site_url':
                    if ($this->params['main_site_slug']) {
                        return $resource->siteUrl($this->params['main_site_slug'], $append === 'absolute_site_url');
                    }
                    break;
            }
        } else {
            switch ($this->params['append_identifier_site']) {
                default:
                case 'api_url':
                    return $resource->apiUrl();
                case 'relative_site_url':
                    return $resource->siteUrl();
                case 'absolute_site_url':
                    return $resource->siteUrl(null, true);
            }
        }
    }

    /**
     * Format specific data types for a value.
     *
     * Code similar in module Bulk Export.
     * @see \BulkExport\Traits\MetadataToStringTrait::stringMetadata()
     *
     * @param \Omeka\Api\Representation\ValueRepresentation $value
     * @return array The xml text and attributes.
     */
    protected function formatValue(ValueRepresentation $value): array
    {
        $attributes = [];
        $type = $value->type();
        switch ($type) {
            case 'resource':
            case substr($type, 0, 9) === 'resource:':
                return $this->formatValueResource($value->valueResource());
            case 'uri':
            case substr($type, 0, 13) === 'valuesuggest:':
            case substr($type, 0, 16) === 'valuesuggestall:':
                return $this->formatValueUri($value);
            // Module Custom vocab.
            case substr($type, 0, 12) === 'customvocab:':
                $vvr = $value->valueResource();
                if ($vvr) {
                    return $this->formatValueResource($vvr);
                }
                $v = (string) $value->value();
                break;
            // Module module Numeric data type.
            case substr($type, 0, 8) === 'numeric:':
                $v = (string) $value;
                break;
            // Module DataTypeRdf.
            case 'xml':
            // Module RdfDatatype (deprecated).
            case 'rdf:XMLLiteral':
            case 'xsd:date':
            case 'xsd:dateTime':
            case 'xsd:decimal':
            case 'xsd:gDay':
            case 'xsd:gMonth':
            case 'xsd:gMonthDay':
            case 'xsd:gYear':
            case 'xsd:gYearMonth':
            case 'xsd:time':
                $v = (string) $value;
                break;
            case 'integer':
            case 'xsd:integer':
                $v = (int) $value->value();
                break;
            case 'boolean':
            case 'xsd:boolean':
                $v = $value->value() ? 'true' : 'false';
                break;
            case 'html':
            case 'rdf:HTML':
                $v = $value->asHtml();
                break;
            case 'literal':
            default:
                // TODO Don't use $value->asHtml() here?
                $v = (string) $value;
                break;
        }

        $lang = $value->lang();
        if ($lang) {
            $attributes['xml:lang'] = $lang;
        }

        return [
            $v,
            $attributes,
        ];
    }

    protected function formatValueUri(ValueRepresentation $value): array
    {
        $attributes = [];
        switch ($this->params['format_uri']) {
            case 'uri':
                $v = (string) $value->uri();
                $attributes['xsi:type'] = 'dcterms:URI';
                break;
            case 'html':
                $v = $value->asHtml();
                break;
            case 'uri_label':
                $v = trim($value->uri() . ' ' . $value->value());
                break;
            case 'label_attr_uri':
                // For compatibility with many harvesters that don't manage
                // attributes, the uri is kept when no label.
                $vUri = (string) $value->uri();
                $v = (string) $value->value();
                $v = strlen($v) ? $v : $vUri;
                $attributes['href'] = $vUri;
                break;
            case 'uri_attr_label':
            default:
                $v = (string) $value->uri();
                $attributes['xsi:type'] = 'dcterms:URI';
                $w = (string) $value->value();
                if (strlen($w)) {
                    $attributes['title'] = $w;
                }
                break;
        }

        $lang = $value->lang();
        if ($lang) {
            $attributes['xml:lang'] = $lang;
        }

        return [
            $v,
            $attributes,
        ];
    }

    protected function formatValueResource(AbstractResourceEntityRepresentation $resource): array
    {
        $attributes = [];
        switch ($this->params['format_resource']) {
            case 'id':
                $v = (string) $resource->id();
                break;
            case 'identifier':
                $v = (string) $resource->value($this->params['format_resource_property']);
                break;
            case 'identifier_id':
                $v = (string) $resource->value($this->params['format_resource_property'], ['default' => $resource->id()]);
                break;
            case 'title':
                $v = $resource->displayTitle('[#' . $resource->id() . ']');
                break;
            case 'url':
                $v = $this->singleIdentifier($resource);
                $attributes['xsi:type'] = 'dcterms:URI';
                break;
            case 'url_title':
                $vUrl = $this->singleIdentifier($resource);
                $vTitle = $resource->displayTitle('');
                $v = $vUrl . (strlen($vTitle) ? ' ' . $vTitle : '');
                break;
            case 'title_attr_url':
                // For compatibility with many harvesters that don't manage
                // attributes, the uri is kept when no label.
                $vUrl = $this->singleIdentifier($resource);
                $v = $resource->displayTitle('');
                $v = strlen($v) ? $v : $vUrl;
                $attributes['href'] = $vUrl;
                break;
            case 'url_attr_title':
            default:
                $v = $this->singleIdentifier($resource);
                $attributes['xsi:type'] = 'dcterms:URI';
                $vTitle = $resource->displayTitle('');
                if (strlen($vTitle)) {
                    $attributes['title'] = $vTitle;
                }
                break;
        }

        // A resource has no language.

        return [
            $v,
            $attributes,
        ];
    }

    /**
     * Filter values of a resource before processing (remove, update or append).
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return array See \Omeka\Api\Representation\AbstractResourceEntityRepresentation::values()
     */
    protected function filterValuesPre(
        AbstractResourceEntityRepresentation $resource
    ) {
        $args = [];
        $args['prefix'] = $this->getMetadataPrefix();
        $args['resource'] = $resource;
        $args['values'] = $resource->values();

        /** @var \ArrayObject $args */
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs($args);
        $eventManager->trigger('oaipmhrepository.values.pre', $this, $args);
        return $args['values'];
    }

    /**
     * Filter values (remove, update or append) of a resource via an event.
     *
     * @deprecated Since 3.3.5.2 Use filterValuesPre() instead, that filters them globally. Will be removed in a future version.
     * @param AbstractResourceEntityRepresentation $resource
     * @param string $term
     * @param ValueRepresentation|ValueRepresentation[]|null $values
     * @return ValueRepresentation|ValueRepresentation[]|null
     */
    protected function filterValues(
        AbstractResourceEntityRepresentation $resource,
        $term,
        $values
    ) {
        $args = [];
        $args['prefix'] = $this->getMetadataPrefix();
        $args['term'] = $term;
        $args['resource'] = $resource;
        $args['values'] = $values;

        /** @var \ArrayObject $args */
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs($args);
        $eventManager->trigger('oaipmhrepository.values', $this, $args);
        return $args['values'];
    }

    /**
     * Appends a metadata element, a child element with the required format, and
     * further children for each of the properties present in the item.
     *
     * {@inheritDoc}
     */
    abstract public function appendMetadata(DOMElement $parent, ItemRepresentation $item);

    abstract public function getMetadataPrefix();

    /**
     * Returns the XML schema for the output format.
     *
     * @return string XML schema URI
     */
    abstract public function getMetadataSchema();

    /**
     * Returns the XML namespace for the output format.
     *
     * @return string XML namespace URI
     */
    abstract public function getMetadataNamespace();
}
