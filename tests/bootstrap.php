<?php declare(strict_types=1);

/**
 * Bootstrap file for module tests.
 *
 * Use Common module Bootstrap helper for test setup.
 * The Bootstrap automatically registers:
 * - CommonTest\ namespace (test utilities like AbstractHttpControllerTestCase)
 * - Module namespaces from composer.json (autoload and autoload-dev)
 */

require dirname(__DIR__, 3) . '/modules/Common/tests/Bootstrap.php';

\CommonTest\Bootstrap::bootstrap(
    ['Common', 'OaiPmhRepository'],
    'OaiPmhRepositoryTest',
    __DIR__ . '/OaiPmhRepositoryTest'
);
