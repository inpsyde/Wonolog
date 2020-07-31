<?php // phpcs:disable
if (defined('ABSPATH')) {
    return;
}

define('ABSPATH', dirname(__DIR__) . '/vendor/wordpress/wordpress/');
define('WP_CONTENT_DIR', ABSPATH . '/wp-content');
define('WPINC', 'wp-includes');
define('OBJECT', 'OBJECT');

require ABSPATH . WPINC . '/class-wp.php';
require ABSPATH . WPINC . '/class-wp-error.php';
require ABSPATH . WPINC . '/class-wp-rewrite.php';
require ABSPATH . WPINC . '/class-phpmailer.php';
require ABSPATH . WPINC . '/shortcodes.php';
require ABSPATH . WPINC . '/load.php';
require ABSPATH . WPINC . '/plugin.php';
require ABSPATH . WPINC . '/functions.php';
require ABSPATH . WPINC . '/cron.php';
require ABSPATH . WPINC . '/link-template.php';
require ABSPATH . WPINC . '/rest-api.php';
require ABSPATH . WPINC . '/ms-blogs.php';
require ABSPATH . WPINC . '/user.php';
require ABSPATH . WPINC . '/query.php';
