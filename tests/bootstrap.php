<?php // phpcs:disable

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
// phpcs:disable

declare(strict_types=1);

$testsDir = str_replace('\\', '/', __DIR__);
$libDir = dirname($testsDir);
$vendorDir = "{$libDir}/vendor";
$autoload = "{$vendorDir}/autoload.php";

if (!is_file($autoload)) {
    die('Please install via Composer before running tests.');
}

putenv('TESTS_PATH=' . $testsDir);
putenv('LIBRARY_PATH=' . $libDir);
putenv('VENDOR_DIR=' . $vendorDir);

error_reporting(E_ALL); // phpcs:ignore

require_once "{$libDir}/vendor/antecedent/patchwork/Patchwork.php";

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', $autoload);
    require_once $autoload;
}

if (file_exists(__DIR__ . '/environment.php')) {
    require_once __DIR__ . '/environment.php';
}

defined('ABSPATH') or define('ABSPATH', "{$vendorDir}/johnpbloch/wordpress-core/");

unset($testsDir, $libDir, $vendorDir, $autoload);
