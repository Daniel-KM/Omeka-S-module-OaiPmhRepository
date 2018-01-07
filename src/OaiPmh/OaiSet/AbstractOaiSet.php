<?php
/**
 * @author Daniel Berthereau
 * @copyright Daniel Berthereau, 2014-2017
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\OaiSet;

use DOMElement;
use OaiPmhRepository\OaiPmh\AbstractXmlGenerator;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\ItemSetRepresentation;
use Omeka\Api\Representation\SiteRepresentation;

/**
 * Abstract class on which all other set spec handlers are based.
 * Includes logic and default for all set spec-independent output.
 */
abstract class AbstractOaiSet extends AbstractXmlGenerator implements OaiSetInterface
{
    /**
     * The type of oai sets: "item_set", "site_pool", or "none" (default).
     *
     * "site_pool" can be used only for global oai-pmh repository.
     *
     * @var string
     */
    protected $setSpecType;

    /**
     * The site to filter oai sets for site repositories.
     *
     * @var SiteRepresentation
     */
    protected $site;

    /**
     * @var ApiManager
     */
    protected $api;

    /**
     * @param ApiManager $api
     */
    public function __construct(ApiManager $api)
    {
        $this->api = $api;
    }

    /**
     * @param string $setSpecType
     */
    public function setSetSpecType($setSpecType)
    {
        $this->setSpecType = $setSpecType;
    }

    public function setSite(SiteRepresentation $site = null)
    {
        $this->site = $site;
    }

    public function listSets()
    {
        $oaiSets = [];
        if ($this->site) {
            $siteItemSets = $this->site->siteItemSets();
            foreach ($siteItemSets as $siteItemSet) {
                $oaiSets[] = $siteItemSet->itemSet();
            }
        } else {
            switch ($this->setSpecType) {
                case 'site_pool':
                    $oaiSets = $this->api->search('sites')->getContent();
                    break;
                case 'item_set':
                    $oaiSets = $this->api->search('item_sets')->getContent();
                    break;
                case 'none':
                default:
                    // Nothing to do.
                    break;
            }
        }

        foreach ($oaiSets as $key => $oaiSet) {
            $elements = [];
            $elements['setSpec'] = $this->getSetSpec($oaiSet);
            $elements['setName'] = $this->getSetName($oaiSet);
            $description = $this->getSetDescription($oaiSet);
            if (!is_null($description)) {
                $elements['setDescription'] = $description;
            }
            $oaiSets[$key] = $elements;
        }

        return $oaiSets;
    }

    public function listSetSpecs(ItemRepresentation $item)
    {
        $setSpecs = [];
        switch ($this->setSpecType) {
            case 'item_set':
                // Currently, Omeka S doesn't filter item sets according to the
                // item sets attached to a site, so they are filtered here.
                if ($this->site) {
                    $itemSets = [];
                    foreach ($item->itemSets() as $itemSet) {
                        $itemSets[$itemSet->id()] = $itemSet;
                    }
                    $siteItemSets = [];
                    foreach ($site->siteItemSets() as $siteItemSet) {
                        $itemSet = $siteItemSet->itemSet();
                        $siteItemSets[$itemSet->id()] = $itemSet;
                    }
                    $itemSets = array_intersect_key($itemSets, $siteItemSets);
                } else {
                    $itemSets = $item->itemSets();
                }
                foreach ($itemSets as $itemSet) {
                    $setSpecs[] = $this->getSetSpecItemSet($itemSet);
                }
                break;

            case 'site_pool':
                $sites = $this->api->search('sites')->getContent();
                foreach ($sites as $site) {
                    $setSpecs[] = $this->getSetSpecSite($site);
                }
                break;

            case 'none':
            default:
                // Nothing to do.
                break;
        }
        return $setSpecs;
    }

    public function getSetSpec(AbstractEntityRepresentation $representation)
    {
        switch ($this->getJsonLdType($representation)) {
            case 'o:ItemSet':
                return $this->getSetSpecItemSet($representation);
            case 'o:Site':
                return $this->getSetSpecSite($representation);
        }
    }

    protected function getSetSpecItemSet(ItemSetRepresentation $itemSet)
    {
        return (string) $itemSet->id();
    }

    protected function getSetSpecSite(SiteRepresentation $site)
    {
        return $site->slug();
    }

    public function getSetName(AbstractEntityRepresentation $representation)
    {
        switch ($this->getJsonLdType($representation)) {
            case 'o:ItemSet':
                return $this->getSetNameItemSet($representation);
            case 'o:Site':
                return $this->getSetNameSite($representation);
        }
    }

    protected function getSetNameItemSet(ItemSetRepresentation $itemSet)
    {
        return $itemSet->displayTitle();
    }

    protected function getSetNameSite(SiteRepresentation $site)
    {
        return $site->title();
    }

    public function getSetDescription(AbstractEntityRepresentation $representation)
    {
        switch ($this->getJsonLdType($representation)) {
            case 'o:ItemSet':
                return $this->getSetDescriptionItemSet($representation);
            case 'o:Site':
                return $this->getSetDescriptionSite($representation);
        }
    }

    protected function getSetDescriptionItemSet(ItemSetRepresentation $itemSet)
    {
        return $itemSet->displayDescription() ?: null;
    }

    protected function getSetDescriptionSite(SiteRepresentation $site)
    {
    }

    public function findResource($setSpec)
    {
        if (empty($setSpec)) {
            return;
        }
        $set = null;
        if ((integer) $setSpec) {
            try {
                $set = $this->api->read('item_sets', ['id' => $setSpec])->getContent();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
            }
        } else {
            try {
                $set = $this->api->read('sites', ['slug' => $setSpec])->getContent();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
            }
        }
        return $set;
    }

    protected function getJsonLdType(AbstractEntityRepresentation $representation)
    {
        $jsonLdType = $representation->getJsonLdType();
        return is_array($jsonLdType)
            ? reset($jsonLdType)
            : $jsonLdType;
    }
}
