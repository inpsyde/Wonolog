<?php

// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions
// phpcs:disable WordPress.PHP.DevelopmentFunctions

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

error_reporting(E_ALL);
ini_set('error_reporting', '-1');

require_once "{$vendorDir}/antecedent/patchwork/Patchwork.php";

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', $autoload);
    require_once $autoload;
}

if (!getenv('GITHUB_WORKFLOW') && file_exists(__DIR__ . '/environment.php')) {
    require_once __DIR__ . '/environment.php';
}

defined('ABSPATH') or define('ABSPATH', "{$vendorDir}/roots/wordpress-no-content/");
require_once ABSPATH . '/wp-includes/PHPMailer/SMTP.php';
require_once ABSPATH . '/wp-includes/PHPMailer/PHPMailer.php';

unset($testsDir, $libDir, $vendorDir, $autoload);
