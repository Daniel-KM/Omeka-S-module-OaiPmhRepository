<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use DOMElement;
use OaiPmhRepository\OaiPmh\AbstractXmlGenerator;
use OaiPmhRepository\OaiPmh\OaiSet\OaiSetInterface;
use OaiPmhRepository\OaiPmh\Plugin\OaiIdentifier;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Settings\SettingsInterface;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;

/**
 * Abstract class on which all other metadata format handlers are based.
 * Includes logic for all metadata-independent record output.
 *
 * @todo Migration to PHP 5.3 will allow the abstract getter functions to be
 *       static, as they should be
 */
abstract class AbstractMetadata
    extends AbstractXmlGenerator
    implements MetadataInterface, EventManagerAwareInterface
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

    public function setSettings(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    public function setOaiSet(OaiSetInterface $oaiSet)
    {
        $this->oaiSet = $oaiSet;
    }

    public function getOaiSet()
    {
        return $this->oaiSet;
    }

    public function setIsGlobalRepository($isGlobalRepository)
    {
        $this->isGlobalRepository = $isGlobalRepository;
    }

    public function declareMetadataFormat(DOMElement $parent)
    {
        $elements = [
            'metadataPrefix' => $this->getMetadataPrefix(),
            'schema' => $this->getMetadataSchema(),
            'metadataNamespace' => $this->getMetadataNamespace(),
        ];
        $this->createElementWithChildren($parent, 'metadataFormat', $elements);
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
     * Filter values (remove, update or append) of a resource via an event.
     *
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
        /** @var \Zend\EventManager\EventManager $eventManager */
        $eventManager = $this->getEventManager();
        /** @var \ArrayObject $args */
        $args = $eventManager->prepareArgs([]);
        $args['repository'] = self::class;
        $args['resource'] = $resource;
        $args['term'] = $term;
        $args['values'] = $values;

        $event = new Event('oaipmhrepository.values', $this, $args);
        $eventManager->triggerEvent($event);

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
