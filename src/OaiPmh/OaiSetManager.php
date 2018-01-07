<?php

namespace OaiPmhRepository\OaiPmh;

use OaiPmhRepository\OaiPmh\OaiSet\OaiSetInterface;
use Omeka\ServiceManager\AbstractPluginManager;

class OaiSetManager extends AbstractPluginManager
{
    /**
     * Keep base first.
     *
     * @var array
     */
    protected $sortedNames = [
        'basic',
    ];

    protected $autoAddInvokableClass = false;

    protected $instanceOf = OaiSetInterface::class;
}
