<?php

declare(strict_types=1);

// Loaded via auto_prepend_file for the wp-phpunit-integration workflow only (see
// php-wp-phpunit-integration.yml). Runs before any other code, including WP-CLI's own
// bootstrap, so it's active for the wp-cli subprocess syde/wp-phpunit-integration shells out to
// -- which treats any output on that subprocess's stderr as fatal, and a deprecation notice deep
// in a bundled wp-cli dependency (unrelated to our own code) writes there. Only E_DEPRECATED is
// swallowed; everything else is left to normal PHP handling.
set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline): bool {
    return $errno === E_DEPRECATED || $errno === E_USER_DEPRECATED;
});
