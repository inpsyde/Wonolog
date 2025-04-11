<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Processor;

abstract class WpContextProcessorAdapterAbstract
{
    protected ?bool $isRestRequest = null;

    /**
     * @return bool
     */
    protected function doingRest(): bool
    {
        if (isset($this->isRestRequest)) {
            return $this->isRestRequest;
        }

        // phpcs:disable WordPress.Security.NonceVerification
        // @phpstan-ignore-next-line
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

        $restUrl = (string) set_url_scheme(get_rest_url());
        $currentUrl = (string) set_url_scheme(add_query_arg([]));
        $this->isRestRequest = strpos($currentUrl, (string) set_url_scheme($restUrl)) === 0;

        return $this->isRestRequest;
    }
}
