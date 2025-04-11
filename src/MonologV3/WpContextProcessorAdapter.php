<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\MonologV3;

use Inpsyde\Wonolog\Processor\WpContextProcessorAdapterAbstract;
use Monolog\LogRecord;

class WpContextProcessorAdapter extends WpContextProcessorAdapterAbstract
{
    public function __invoke(LogRecord $record): LogRecord
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

        if (is_multisite()) {
            $data['ms_switched'] = ms_is_switched();
            $data['site_id'] = get_current_blog_id();
            $data['network_id'] = get_current_network_id();
        }

        if (!isset($record->extra) || !is_array($record->extra)) {
            $record->extra = [];
        }

        $record->extra['wp'] = $data;

        return $record;
    }
}