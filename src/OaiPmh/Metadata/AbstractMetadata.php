<?php declare(strict_types=1);
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2022
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use ArrayObject;
use DOMElement;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\ServiceManager\ServiceLocatorInterface;
use OaiPmhRepository\OaiPmh\AbstractXmlGenerator;
use OaiPmhRepository\OaiPmh\OaiSet\OaiSetInterface;
use OaiPmhRepository\OaiPmh\Plugin\OaiIdentifier;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\ValueRepresentation;

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
     * OAI-PMH metadata prefix
     *
     * @var string
     */
    const METADATA_PREFIX = null;

    /**
     * XML namespace for output format
     *
     * @var string
     */
    const METADATA_NAMESPACE = null;

    /**
     * XML schema for output format
     *
     * @var string
     */
    const METADATA_SCHEMA = null;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * The class used to create the set data (spec, name and description).
     *
     * @var OaiSetInterface
     */
    protected $oaiSet;

    /**
     * The repository is global by default.
     *
     * @var bool
     */
    protected $isGlobalRepository = true;

    /**
     * @var array
     */
    protected $params = [];

    public function setServices(ServiceLocatorInterface $services)
    {
        $this->services = $services;
        return $this;
    }

    public function setOaiSet(OaiSetInterface $oaiSet)
    {
        $this->oaiSet = $oaiSet;
        return $this;
    }

    public function getOaiSet(): OaiSetInterface
    {
        return $this->oaiSet;
    }

    public function setIsGlobalRepository($isGlobalRepository)
    {
        $this->isGlobalRepository = $isGlobalRepository;
        return $this;
    }

    public function setParams(array $params): MetadataInterface
    {
        $this->params = $params;
        return $this;
    }

    public function declareMetadataFormat(DOMElement $parent)
    {
        $elements = [
            'metadataPrefix' => $this->getMetadataPrefix(),
            'schema' => $this->getMetadataSchema(),
            'metadataNamespace' => $this->getMetadataNamespace(),
        ];
        $this->createElementWithChildren($parent, 'metadataFormat', $elements);
        return $this;
    }

    /**
     * Filter the query for the two main List verbs.
     *
     * @see \OaiPmhRepository\OaiPmh\ResponseGenerator::listResponse()
     *
     * @param ArrayObject $query
     */
    public function filterList(ArrayObject $query)
    {
        return $this;
    }

    public function appendRecord(DOMElement $parent, ItemRepresentation $item)
    {
        $document = $parent->ownerDocument;
        $record = $document->createElement('record');
        $parent->appendChild($record);
        $this->appendHeader($record, $item);

        $metadata = $document->createElement('metadata');
        $record->appendChild($metadata);
        $this->appendMetadata($metadata, $item);
        return $this;
    }

    public function appendHeader(DOMElement $parent, ItemRepresentation $item)
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
        return $this;
    }

    protected function isGlobalRepository(): bool
    {
        return $this->isGlobalRepository;
    }

    protected function singleIdentifier(AbstractResourceEntityRepresentation $resource): ?string
    {
        if ($this->isGlobalRepository()) {
            $append = $this->params['append_identifier_global'];
            switch ($append) {
                default:
                case 'api_url':
                    return $resource->apiUrl();
                case 'relative_site_url':
                case 'absolute_site_url':
                    return $this->params['main_site_slug']
                        ? $resource->siteUrl($this->params['main_site_slug'], $append === 'absolute_site_url')
                        : null;
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
            case substr($type, 0, 8) === 'resource':
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
                } elseif ($value->uri()) {
                    return $this->formatValueUri($value);
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
                //check if uri is valid link, if not, use label as prefix
                $vUri = (string) $value->uri();
                $v = (string) $value->value();
                if (filter_var($vUri, FILTER_VALIDATE_URL)) {
                  $v = strlen($v) ? $v : $vUri;
                  $attributes['href'] = $vUri;
                } else {
                  if (!$v) {
                    $v = $vUri;
                  } else {
                    $v = $v . ': ' . $vUri;
                  }

                }
                break;
            case 'uri_attr_label':
            default:
                $v = (string) $value->uri();
                $attributes['xsi:type'] = 'dcterms:URI';
                $w = (string) $value->value();
                if (strlen($w)) {
                    $attributes[$this->params['attribute_title'] ?? 'title'] = $w;
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
                    $attributes[$this->params['attribute_title'] ?? 'title'] = $vTitle;
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
    ): array {
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
     * Appends a metadata element, a child element with the required format, and
     * further children for each of the properties present in the item.
     *
     * {@inheritDoc}
     */
    abstract public function appendMetadata(DOMElement $parent, ItemRepresentation $item);

    /**
     * Returns the metadata prefix for the output format.
     *
     * @return string Metadata prefix
     */
    public function getMetadataPrefix(): string
    {
        return static::METADATA_PREFIX;
    }

    /**
     * Returns the XML schema for the output format.
     *
     * @return string XML schema URI
     */
    public function getMetadataSchema(): string
    {
        return static::METADATA_SCHEMA;
    }

    /**
     * Returns the XML namespace for the output format.
     *
     * @return string XML namespace URI
     */
    public function getMetadataNamespace(): string
    {
        return static::METADATA_NAMESPACE;
    }
}
