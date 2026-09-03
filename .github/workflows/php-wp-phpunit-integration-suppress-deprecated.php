<?php

declare(strict_types=1);

// syde/wp-phpunit-integration treats any output on the wp-cli subprocess's stderr as fatal, and
// a deprecation notice deep in a bundled wp-cli dependency (unrelated to our own code, and
// unfixed in the latest release) writes there, breaking the integration test run. Loaded via
// auto_prepend_file (see php-wp-phpunit-integration.yml) so it's active before WP-CLI's own
// bootstrap. Only E_DEPRECATED is swallowed; everything else is left to normal PHP handling.
//
// Once wp-cli/php-cli-tools fixes this upstream (already fixed in v0.13.0, just not yet pulled
// in by the wp-cli release this package bundles), this file and the ini-values line referencing
// it can likely be removed.
set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline): bool {
    return $errno === E_DEPRECATED || $errno === E_USER_DEPRECATED;
});
