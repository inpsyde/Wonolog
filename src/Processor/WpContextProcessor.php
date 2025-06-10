<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Processor;

use Monolog\LogRecord;

/**
 * @psalm-import-type _RecordType from \Inpsyde\Wonolog\Configurator
 * @psalm-import-type _RecordArray from \Inpsyde\Wonolog\Configurator
 * @psalm-import-type _RecordObject from \Inpsyde\Wonolog\Configurator
 */
class WpContextProcessor
{
    private ?bool $isRestRequest = null;

    /**
     * @return WpContextProcessor
     */
    public static function new(): WpContextProcessor
    {
        return new self();
    }

    /**
     * Empty on purpose.
     */
    private function __construct()
    {
    }

    /**
     * @template T of _RecordType
     *
     * @param T $record
     * @return T
     */
    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        $data = [
            'doing_cron' => defined('DOING_CRON') && DOING_CRON, // @phpstan-ignore-line
            'doing_ajax' => defined('DOING_AJAX') && DOING_AJAX, // @phpstan-ignore-line
            'is_admin' => is_admin(),
            'doing_rest' => $this->doingRest(),
        ];

        if (did_action('init')) {
            $data['user_id'] = get_current_user_id();
        }

        $multisite = is_multisite();
        $data['multisite'] = $multisite;
        if ($multisite) {
            $data['ms_switched'] = ms_is_switched();
            $data['site_id'] = get_current_blog_id();
            $data['network_id'] = get_current_network_id();
        }

        return ($record instanceof LogRecord)
            ? $this->handleExtraInfoFromLogRecord($record, $data)
            : $this->handleExtraInfoFromArrayRecord($record, $data);
    }

    /**
     * @param _RecordArray $record
     * @param array<mixed> $data
     * @return _RecordArray
     */
    private function handleExtraInfoFromArrayRecord(array $record, array $data): array
    {
        if (!is_array($record['extra'] ?? null)) {
            $record['extra'] = [];
        }
        $record['extra']['wp'] = $data;

        return $record;
    }

    /**
     * @param _RecordObject $record
     * @param array<mixed> $data
     * @return _RecordObject
     */
    private function handleExtraInfoFromLogRecord(LogRecord $record, array $data): LogRecord
    {
        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $record->extra['wp'] = $data;

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
