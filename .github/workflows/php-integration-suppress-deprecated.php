<?php

declare(strict_types=1);

// syde/wp-phpunit-integration treats any wp-cli subprocess stderr output as fatal. Bundled
// wp-cli/php-cli-tools writes a deprecation there (Colors.php: null used as an array offset),
// but only under CI's non-interactive/piped stdout, not a local terminal:
// https://github.com/inpsyde/Wonolog/actions/runs/35096721293/job/104795785216
//
// Fixed upstream in php-cli-tools 0.13.0 -- remove this once johnpbloch/wp-cli-phar bundles it.
// Loaded via auto_prepend_file (see php-integration-tests.yml), active before WP-CLI boots.
set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline): bool {
    return $errno === E_DEPRECATED || $errno === E_USER_DEPRECATED;
});
