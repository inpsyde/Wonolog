<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;

// Shared config builder, mounted read-only into every package's container
// alongside whichever rector-<config>.php is driving the run (both at the
// same paths every time -- /var/www/html/rector-config-common.php and
// /var/www/html/rector-config.php -- so __DIR__ here always resolves to
// that package's mounted root regardless of which package is running).
//
// Everything but the set list is identical between configs, so each
// rector-<config>.php just requires this file and hands it its own sets.
//
// Folders scanned for every package (only if they actually exist there).
$baseFolders = ['modules', 'tests', 'src', 'inc'];

// Extra folders on top of the base ones, per package. Keyed by the
// package's directory name, passed in via the RECTOR_PKG_NAME env var
// (set by rector-test.sh). Add an entry here whenever a package's layout
// doesn't match the base folders above.
$extraFoldersByPackage = [
    'cli' => ['blueprints'],
];

return static function (array $sets) use ($baseFolders, $extraFoldersByPackage): RectorConfigBuilder {
    $pkgName = getenv('RECTOR_PKG_NAME') ?: '';
    $folders = array_merge($baseFolders, $extraFoldersByPackage[$pkgName] ?? []);

    $candidatePaths = array_filter(
        array_map(static fn (string $folder): string => __DIR__ . '/' . $folder, $folders),
        static fn (string $path): bool => is_dir($path)
    );

    if ($candidatePaths === []) {
        $candidatePaths = [__DIR__];
    }

    return RectorConfig::configure()
        ->withPaths($candidatePaths)
        ->withSkip([
            __DIR__ . '/vendor',
            __DIR__ . '/node_modules',
        ])
        // No argument = auto-detect the target PHP version from that
        // package's own composer.json (resolved from the current working
        // directory).
        ->withPhpSets()
        ->withSets($sets);
};
