<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Wonolog;

// We want to load this file just once.
// Being loaded by Composer autoload, and being in WordPress context,
// we have to put special care on this.
if (defined(__NAMESPACE__ . '\\LOG')) {
    return;
}

const LOG = 'wonolog.log';

(static function () {
    $hook = 'muplugins_loaded';
    $priority = 5;
    $callback = [Configurator::new(), 'setup'];

    $addActionExists = function_exists('add_action');
    if ($addActionExists || defined('ABSPATH')) {
        if (!$addActionExists) {
            require_once ABSPATH . 'wp-includes/plugin.php';
        }

        add_action($hook, $callback, $priority);

        return;
    }

    /**
     * If here, this file is loaded very early, probably _too_ early, before ABSPATH is defined.
     * Only option we have is to "manually" write in global `$wp_filter` array.
     * Hint: in whole-site Composer-based WP installs, require Composer autoload _after_ defining
     * ABSPATH.
     */
    global $wp_filter;
    is_array($wp_filter) or $wp_filter = [];
    is_array($wp_filter[$hook] ?? null) or $wp_filter[$hook] = [];
    is_array($wp_filter[$hook][$priority] ?? null) or $wp_filter[$hook][$priority] = [];
    $callbackId = spl_object_hash($callback[0]) . 'setup';
    $wp_filter[$hook][$priority][$callbackId] = ['function' => $callback, 'accepted_args' => 0];
})();
