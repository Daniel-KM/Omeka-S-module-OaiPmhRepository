<?php

namespace OaiPmhRepository\OaiPmh;

use OaiPmhRepository\OaiPmh\Metadata\MetadataInterface;
use Omeka\ServiceManager\AbstractPluginManager;

class MetadataFormatManager extends AbstractPluginManager
{
    /**
     * Keep oai dc first.
     *
     * @var array
     */
    protected $sortedNames = [
        'oai_dc',
    ];

    protected $autoAddInvokableClass = false;

    protected $instanceOf = MetadataInterface::class;
}
