<?php declare(strict_types=1);
/**
 * @author Daniel Berthereau
 * @copyright Daniel Berthereau, 2014-2022
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\OaiSet;

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
     * The type of oai sets: "item_set", "list_item_sets", "site_pool", or "none" (default).
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
     * Options for oai sets.
     *
     * Currently managed:
     * - hide_empty_sets (bool)
     * - list_item_sets (array)
     *
     * @var array $options
     */
    protected $options = [];

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
    public function setSetSpecType($setSpecType): void
    {
        $this->setSpecType = $setSpecType;
    }

    public function setSite(SiteRepresentation $site = null): void
    {
        $this->site = $site;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function listSets()
    {
        $oaiSets = [];
        if ($this->site) {
            switch ($this->setSpecType) {
                case 'item_set':
                    $siteItemSets = $this->site->siteItemSets();
                    foreach ($siteItemSets as $siteItemSet) {
                        $oaiSets[] = $siteItemSet->itemSet();
                    }
                    break;
            }
        } else {
            switch ($this->setSpecType) {
                case 'site_pool':
                    $oaiSets = $this->api->search('sites')->getContent();
                    break;
                case 'item_set':
                    $oaiSets = $this->api->search('item_sets')->getContent();
                    break;
                case 'list_item_sets':
                    $oaiSets = $this->options['list_item_sets']
                        ? $this->api->search('item_sets', ['id' => $this->options['list_item_sets']])->getContent()
                        : [];
                    break;
                case 'queries':
                    $oaiSets = [];
                    $aQuery = [];
                    foreach ($this->options['queries'] ?? [] as $name => $sQuery) {
                        $sQuery = trim((string) $sQuery, "? \n\t\r");
                        parse_str($sQuery, $aQuery);
                        $oaiSets[] = [
                            'spec' => $this->slugify($name),
                            'name' => $name,
                            'description' => null,
                            'sQuery' => $sQuery,
                            'aQuery' => $aQuery,
                        ];
                    }
                    break;
                case 'none':
                default:
                    // Nothing to do.
                    break;
            }
        }

        // TODO Use entity manager or direct query to find sets without items.
        $hideEmptySets = !empty($this->options['hide_empty_sets']);
        if ($oaiSets && $hideEmptySets) {
            switch ($this->setSpecType) {
                case 'site_pool':
                    foreach ($oaiSets as $key => $oaiSet) {
                        $itemCount = $this->api
                            // TODO Check if this limit is useful.
                            ->search('items', ['limit' => 0, 'site_id' => $oaiSet->id()])
                            ->getTotalResults();
                        if (empty($itemCount)) {
                            unset($oaiSets[$key]);
                        }
                    }
                    break;
                case 'item_set':
                case 'list_item_sets':
                    foreach ($oaiSets as $key => $oaiSet) {
                        $itemCount = $oaiSet->itemCount();
                        if (empty($itemCount)) {
                            unset($oaiSets[$key]);
                        }
                    }
                    break;
                case 'queries':
                    foreach ($oaiSets as $key => $queryData) {
                        // TODO Check if this limit is useful.
                        $q = $queryData['aQuery'];
                        $q['limit'] = 0;
                        $itemCount = $this->api
                            ->search('items', $q)
                            ->getTotalResults();
                        if (empty($itemCount)) {
                            unset($oaiSets[$key]);
                        }
                    }
                    break;
                default:
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
            case 'list_item_sets':
                // Currently, Omeka S doesn't filter item sets according to the
                // item sets attached to a site, so they are filtered here.
                if ($this->site) {
                    $itemSets = [];
                    foreach ($item->itemSets() as $itemSet) {
                        $itemSets[$itemSet->id()] = $itemSet;
                    }
                    $siteItemSets = [];
                    foreach ($this->site->siteItemSets() as $siteItemSet) {
                        $itemSet = $siteItemSet->itemSet();
                        $siteItemSets[$itemSet->id()] = $itemSet;
                    }
                    $itemSets = array_intersect_key($itemSets, $siteItemSets);
                } else {
                    $itemSets = $item->itemSets();
                }
                if ($this->setSpecType === 'list_item_sets') {
                    foreach ($itemSets as $itemSet) {
                        if (in_array($itemSet->id(), $this->options['list_item_sets'])) {
                            $setSpecs[] = $this->getSetSpecItemSet($itemSet);
                        }
                    }
                } else {
                    foreach ($itemSets as $itemSet) {
                        $setSpecs[] = $this->getSetSpecItemSet($itemSet);
                    }
                }
                break;

            case 'queries':
                // This is a slow process when sets are numerous.
                // TODO Create a table to store oai sets as query? Or use item sets…
                $aQuery = [];
                $itemId = $item->id();
                foreach ($this->options['queries'] ?? [] as $name => $sQuery) {
                    $sQuery = trim((string) $sQuery, "? \n\t\r");
                    parse_str($sQuery, $aQuery);
                    $aQuery['id'] = $itemId;
                    $aQuery['limit'] = 1;
                    $isInSet = $this->api->search('items', $aQuery)->getTotalResults();
                    if ($isInSet) {
                        $setSpecs[] = $this->slugify($name);
                    }
                }
                break;

            case 'site_pool':
                // TODO Improve query to get all item sets of an item that are attached to sites.
                $itemSets = [];
                foreach ($item->itemSets() as $itemSet) {
                    $itemSets[$itemSet->id()] = $itemSet;
                }
                $sites = $this->api->search('sites')->getContent();
                $siteItemSets = [];
                foreach ($sites as $site) {
                    foreach ($site->siteItemSets() as $siteItemSet) {
                        $itemSetId = $siteItemSet->itemSet()->id();
                        if (isset($itemSets[$itemSetId])) {
                            $setSpecs[] = $this->getSetSpecSite($site);
                            break;
                        }
                    }
                }
                break;

            default:
                break;
        }
        return $setSpecs;
    }

    public function getSetSpec($set)
    {
        if (is_array($set)) {
            return $set['spec'];
        }
        switch ($this->getJsonLdType($set)) {
            case 'o:ItemSet':
                return $this->getSetSpecItemSet($set);
            case 'o:Site':
                return $this->getSetSpecSite($set);
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

    public function getSetName($set)
    {
        if (is_array($set)) {
            return $set['name'];
        }
        switch ($this->getJsonLdType($set)) {
            case 'o:ItemSet':
                return $this->getSetNameItemSet($set);
            case 'o:Site':
                return $this->getSetNameSite($set);
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

    public function getSetDescription($set)
    {
        if (is_array($set)) {
            return $set['description'];
        }
        switch ($this->getJsonLdType($set)) {
            case 'o:ItemSet':
                return $this->getSetDescriptionItemSet($set);
            case 'o:Site':
                return $this->getSetDescriptionSite($set);
        }
    }

    protected function getSetDescriptionItemSet(ItemSetRepresentation $itemSet)
    {
        return $itemSet->displayDescription() ?: null;
    }

    protected function getSetDescriptionSite(SiteRepresentation $site): void
    {
        return $site->displayDescription() ?: null;
    }

    public function findResource($setSpec)
    {
        if (empty($setSpec)) {
            return;
        }
        $set = null;
        if ((int) $setSpec) {
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
        // Check queries.
        if (!$set && !empty($this->options['queries'])) {
            foreach ($this->options['queries'] ?? [] as $name => $sQuery) {
                $spec = $this->slugify($name);
                if ($spec === $setSpec) {
                    $aQuery = [];
                    $sQuery = trim((string) $sQuery, "? \n\t\r");
                    parse_str($sQuery, $aQuery);
                    $set = [
                        'spec' => $spec,
                        'name' => $name,
                        'description' => null,
                        'sQuery' => $sQuery,
                        'aQuery' => $aQuery,
                    ];
                    break;
                }
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

    /**
     * Transform the given string into a valid filename
     */
    protected function slugify(string $input): string
    {
        if (extension_loaded('intl')) {
            $transliterator = \Transliterator::createFromRules(':: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;');
            $slug = $transliterator->transliterate($input);
        } elseif (extension_loaded('iconv')) {
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);
        } else {
            $slug = $input;
        }
        $slug = mb_strtolower($slug, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9-]+/u', '_', $slug);
        $slug = preg_replace('/-{2,}/', '_', $slug);
        $slug = preg_replace('/-*$/', '', $slug);
        return $slug;
    }
}
