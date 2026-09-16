<?php

declare(strict_types=1);

use Syde\WpPhpUnitIntegration\Bootstrap;
use Syde\WpPhpUnitIntegration\BootstrapLifecycle;

$libraryPath = dirname(__DIR__);
$vendorPath = "{$libraryPath}/vendor";

if (!is_file("{$vendorPath}/autoload.php")) {
    die('Please install via Composer before running tests.');
}

require_once "{$vendorPath}/autoload.php";

// The default `load` phase boots WordPress here, before any test's setUp() can register hooks,
// leaving closures from WP's hook system in $GLOBALS. That crashes @runTestsInSeparateProcesses
// (used by our integration tests) when PHPUnit snapshots $GLOBALS for the isolated child process.
// It's a no-op instead; each test triggers the real boot itself at the end of its own setUp(),
// after Wonolog's own hooks are already registered. See IntegrationTestCase::setUp().
Bootstrap::init(
    $libraryPath,
    new BootstrapLifecycle(
        load: static function (): void {
        },
    ),
);

unset($libraryPath, $vendorPath);
