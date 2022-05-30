<?php declare(strict_types=1);

namespace OaiPmhRepository\OaiPmh\OaiSet;

use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\SiteRepresentation;

interface OaiSetInterface
{
    /**
     * The type of oai sets: "item_set", "site_pool", or "none" (default).
     *
     * "site_pool" can be used only for global oai-pmh repository.

     * @param string $setSpecType
     */
    public function setSetSpecType($setSpecType);

    /**
     * The site to filter oai sets for site repositories.
     *
     * @param SiteRepresentation $site
     */
    public function setSite(SiteRepresentation $site = null);

    /**
     * Options for oai sets.
     *
     * Currently managed:
     * - hide_empty_sets (bool)
     *
     * @param array $options
     */
    public function setOptions(array $options);

    /**
     * Get the list of set specs of an item.
     *
     * @return array
     */
    public function listSets();

    /**
     * Get the list of set specs of an item.
     *
     * @param ItemRepresentation $item
     * @return array
     */
    public function listSetSpecs(ItemRepresentation $item);

    /**
     * Get the oai set spec of the specified resource (item set, site or array).
     *
     * @param AbstractEntityRepresentation|array $set Item set, site, or query.
     * @return string
     */
    public function getSetSpec($set);

    /**
     * Get the oai set name of the specified resource (item set, site or array).
     *
     * @param AbstractEntityRepresentation|array $set Item set, site, or query.
     * @return string
     */
    public function getSetName($set);

    /**
     * Get the oai set description of the resource (item set, siten or array).
     *
     * @param AbstractEntityRepresentation|array $set Item set, site, or query.
     * @return string
     */
    public function getSetDescription($set);

    /**
     * Get the Omeka item set, site or array according to the oai set spec.
     *
     * @return AbstractEntityRepresentation|string
     */
    public function findResource($setSpec);
}
