<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;

/**
 * Looks a wp_die() and try to find and log db errors.
 *
 * @package wonolog
 */
final class WpDieHandlerListener implements FilterListenerInterface
{
    use ListenerIdByClassNameTrait;

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['wp_die_ajax_handler', 'wp_die_xmlrpc_handler', 'wp_die_handler'];
    }

    /**
     * Run as handler for wp_die() and checks if it was called by
     * wpdb::bail() or wpdb::print_error() so something gone wrong on db.
     * After logging error, the method calls original handler.
     *
     * @wp-hook wp_die_ajax_handler
     * @wp-hook wp_die_handler
     *
     * @param array $args
     * @return mixed
     */
    public function filter(array $args)
    {
        $handler = $args ? reset($args) : null;

        if (!$handler || !is_callable($handler) || !$this->stackTraceHasDbError()) {
            return $handler;
        }

        // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        // phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
        return static function ($message, $title = '', $args = []) use ($handler) {
            // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
            // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration

            $msg = filter_var($message, FILTER_SANITIZE_STRING) ?: '';
            $context = is_array($args) ? $args : [];
            $context['title'] = $title;

            // Log the wp_die() error message.
            do_action(\Inpsyde\Wonolog\LOG, new Error($msg, Channels::DB, $context));

            return $handler($message, $title, $args);
        };
    }

    /**
     * @return bool
     */
    private function stackTraceHasDbError(): bool
    {
        $stacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($stacktrace as $item) {
            $function = $item['function'] ?? null;
            $class = $item['class'] ?? null;
            if ($class === 'wpdb' && ($function === 'bail' || $function === 'print_error')) {
                return true;
            }
        }

        return false;
    }
}
