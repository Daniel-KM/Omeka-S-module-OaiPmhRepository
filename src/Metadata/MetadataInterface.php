<?php

namespace OaiPmhRepository\Metadata;

use DOMElement;
use Omeka\Api\Representation\ItemRepresentation;

interface MetadataInterface
{
    public function appendHeader(DOMElement $parent, ItemRepresentation $item);
    public function appendRecord(DOMElement $parent, ItemRepresentation $item);
    public function declareMetadataFormat(DOMElement $parent);
    public function getMetadataPrefix();
}
