<?php

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Wonolog\Processor;

class WpContextProcessor
{
    /**
     * @var bool|null
     */
    private $isRestRequest;

    /**
     * @return WpContextProcessor
     */
    public static function new(): WpContextProcessor
    {
        return new self();
    }

    private function __construct()
    {
    }

    /**
     * @param array $record The complete log record containing 'message', 'context'
     *                      'level', 'level_name', 'channel', 'datetime' and 'extra'
     * @return array
     */
    public function __invoke(array $record): array
    {
        /** @psalm-suppress RedundantCondition */
        $data = [
            'doing_cron' => defined('DOING_CRON') && DOING_CRON,
            'doing_ajax' => defined('DOING_AJAX') && DOING_AJAX,
            'is_admin' => is_admin(),
            'doing_rest' => $this->doingRest(),
        ];

        if (did_action('init')) {
            $data['user_id'] = get_current_user_id();
        }

        if (is_multisite()) {
            $data['ms_switched'] = ms_is_switched();
            $data['site_id'] = get_current_blog_id();
            $data['network_id'] = get_current_network_id();
        }

        if (!isset($record['extra']) || !is_array($record['extra'])) {
            $record['extra'] = [];
        }

        $record['extra']['wp'] = $data;

        return $record;
    }

    /**
     * @return bool
     */
    private function doingRest(): bool
    {
        if (isset($this->isRestRequest)) {
            return $this->isRestRequest;
        }

        // phpcs:disable WordPress.Security.NonceVerification
        if ((defined('REST_REQUEST') && REST_REQUEST) || !empty($_REQUEST['rest_route'])) {
            // phpcs:enable WordPress.Security.NonceVerification
            $this->isRestRequest = true;

            return true;
        }

        if (get_option('permalink_structure') && empty($GLOBALS['wp_rewrite'])) {
            // Rewrites are used, but it's too early for global rewrites be there.
            // Let's instantiate it, or `get_rest_url()` will fail.
            // This is exactly how WP does it, so it will do nothing bad.
            // In worst case, WP will override it.
            $GLOBALS['wp_rewrite'] = new \WP_Rewrite();
        }

        $restUrl = (string)set_url_scheme(get_rest_url());
        $currentUrl = (string)set_url_scheme(add_query_arg([]));
        $this->isRestRequest = strpos($currentUrl, (string)set_url_scheme($restUrl)) === 0;

        return $this->isRestRequest;
    }
}
