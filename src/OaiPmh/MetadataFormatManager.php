<?php

namespace OaiPmhRepository\OaiPmh;

use OaiPmhRepository\OaiPmh\Metadata\MetadataInterface;
use Omeka\ServiceManager\AbstractPluginManager;

class MetadataFormatManager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = MetadataInterface::class;
}
