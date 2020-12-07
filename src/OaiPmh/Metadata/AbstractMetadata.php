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
            $append = $this->settings->get('oaipmhrepository_append_identifier_global');
            switch ($append) {
                case 'api_url':
                    return $resource->apiUrl();
                case 'relative_site_url':
                case 'absolute_site_url':
                    $mainSite = $this->settings->get('default_site');
                    if ($mainSite) {
                        $mainSiteSlug = $resource->getServiceLocator()->get('ControllerPluginManager')
                            ->get('api')->read('sites', $mainSite)->getContent()->slug();
                        return $resource->siteUrl($mainSiteSlug, $append === 'absolute_site_url');
                    }
                    break;
            }
        } else {
            $append = $this->settings->get('oaipmhrepository_append_identifier_site');
            switch ($append) {
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
