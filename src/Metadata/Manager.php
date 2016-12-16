<?php

namespace OaiPmhRepository\Metadata;

use Omeka\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = MetadataInterface::class;
}
