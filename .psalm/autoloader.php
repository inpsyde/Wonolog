<?php // phpcs:disable
if (defined('ABSPATH')) {
    return;
}

define('ABSPATH', dirname(__DIR__).'/vendor/wordpress/wordpress/');
define('WPINC', 'wp-includes');
define('OBJECT', 'OBJECT');

$wpIncPath = ABSPATH."wp-includes/";

require "{$wpIncPath}class-wp-post.php";
require "{$wpIncPath}class-wp-error.php";
require "{$wpIncPath}load.php";
require "{$wpIncPath}plugin.php";
require "{$wpIncPath}functions.php";
