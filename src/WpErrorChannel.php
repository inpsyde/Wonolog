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

namespace Inpsyde\Wonolog;

class WpErrorChannel
{
    public const FILTER_CHANNEL = 'wonolog.wp-error-channel';

    /**
     * @return WpErrorChannel
     */
    public static function new(): WpErrorChannel
    {
        return new self();
    }

    private function __construct()
    {
    }

    /**
     * @param \WP_Error $error
     * @return string|null
     */
    public function channelFor(\WP_Error $error): ?string
    {
        /** @var array<string|int> $codes */
        $codes = $error->get_error_codes();

        foreach ($codes as $code) {
            $data = $error->get_error_data($code);
            if (!is_array($data) || !is_string($data['channel'] ?? null)) {
                continue;
            }

            $channel = $this->filterChannel((string)$data['channel'], $error);
            if ($channel) {
                return $channel;
            }
        }

        $channel = null;
        while (!$channel && $codes) {
            $code = (string)array_shift($codes);
            $channel = $this->maybeDbChannel($code);
            $channel or $channel = $this->maybeHttpChannel($code);
            $channel or $channel = $this->maybeSecurityChannel($code);
        }

        return $this->filterChannel($channel, $error);
    }

    /**
     * @param string $code
     * @return string|null
     */
    private function maybeDbChannel(string $code): ?string
    {
        if (stripos($code, 'wpdb') !== false || preg_match('/(\b|_)db(\b|_)/i', $code)) {
            return Channels::DB;
        }

        return null;
    }

    /**
     * @param string $code
     * @return string|null
     */
    private function maybeHttpChannel(string $code): ?string
    {
        if (
            stripos($code, 'http') !== false
            || stripos($code, 'request') !== false
            || stripos($code, 'download') !== false
            || stripos($code, 'upload') !== false
            || stripos($code, 'simplepie') !== false
            || stripos($code, 'mail') !== false
            || stripos($code, 'rest') !== false
            || stripos($code, 'wp_mail') !== false
            || stripos($code, 'email') !== false
        ) {
            return Channels::HTTP;
        }

        return null;
    }

    /**
     * @param string $code
     * @return string|null
     */
    private function maybeSecurityChannel(string $code): ?string
    {
        if (
            stripos($code, 'cookie') !== false
            || stripos($code, 'login') !== false
            || stripos($code, 'authentication') !== false
        ) {
            return Channels::SECURITY;
        }

        return null;
    }

    /**
     * @param string|null $channel
     * @param \WP_Error $error
     * @return string|null
     */
    private function filterChannel(?string $channel, \WP_Error $error): ?string
    {
        /**
         * Filters the WordPress error channel.
         *
         * @param string $channel
         * @param \WP_Error $error
         */
        $filtered = apply_filters(self::FILTER_CHANNEL, $channel, $error);

        if ($filtered !== null && !is_string($filtered)) {
            $filtered = null;
        }

        return $filtered ?? $channel;
    }
}
