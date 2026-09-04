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

// The default `load` phase boots WordPress here, before any test's setUp() can register hooks.
// It's a no-op instead; each test triggers the real boot itself at the end of its own setUp().
Bootstrap::init(
    $libraryPath,
    new BootstrapLifecycle(
        load: static function (): void {
        },
    ),
);

unset($libraryPath, $vendorPath);
