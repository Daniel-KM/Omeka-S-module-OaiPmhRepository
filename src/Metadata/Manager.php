<?php

namespace OaiPmhRepository\Metadata;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = MetadataInterface::class;
}
