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

namespace Inpsyde\Wonolog\Data;

use Inpsyde\Wonolog\Channels;

/**
 * Class that is used to "guess" a proper channel from a WP_Error object based on its error codes.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpErrorChannel
{

    public const FILTER_CHANNEL = 'wonolog.wp-error-channel';

    /**
     * @var \WP_Error
     */
    private $error;

    /**
     * @var string
     */
    private $channel = '';

    /**
     * @param \WP_Error $error
     * @param string $channel
     *
     * @return WpErrorChannel
     */
    public static function forError(\WP_Error $error, string $channel = ''): WpErrorChannel
    {
        $instance = new static();
        $instance->error = $error;
        $channel and $instance->channel = (string) $channel;

        return $instance;
    }

    /**
     * @return string
     */
    public function channel(): string
    {
        if ($this->channel) {
            return $this->channel;
        }

        $channel = '';
        $codes = $this->error->get_error_codes();

        while (! $channel && $codes) {
            $code = array_shift($codes);
            $channel = $this->maybeDbChannel($code);
            $channel or $channel = $this->maybeHttpChannel($code);
            $channel or $channel = $this->maybeSecurityChannel($code);
        }

        $channel or $channel = Channels::DEBUG;

        /**
         * Filters the WordPress error channel.
         *
         * @param string $channel
         * @param \WP_Error|null $error
         */
        $filtered = apply_filters(self::FILTER_CHANNEL, $channel, $this->error);

        $this->channel = is_string($filtered)
            ? $filtered
            : $channel;

        return $this->channel;
    }

    /**
     * @param string $code
     *
     * @return string
     */
    private function maybeDbChannel(string $code): string
    {
        if (stripos($code, 'wpdb') !== false || preg_match('/(\b|_)db(\b|_)/i', $code)) {
            return Channels::DB;
        }

        return '';
    }

    /**
     * @param string $code
     *
     * @return string
     */
    private function maybeHttpChannel(string $code): string
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

        return '';
    }

    /**
     * @param string $code
     *
     * @return string
     */
    private function maybeSecurityChannel(string $code): string
    {
        if (
            stripos($code, 'cookie') !== false
            || stripos($code, 'login') !== false
            || stripos($code, 'authentication') !== false
        ) {
            return Channels::SECURITY;
        }

        return '';
    }
}
