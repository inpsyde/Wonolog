<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Psr\Log\LogLevel;

/**
 * Log to Query Monitor using the QM logging functionality.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class QueryMonitorHandler extends AbstractProcessingHandler {
	const LEVEL_MAP = [
		LogLevel::DEBUG => 'qm/debug',
		LogLevel::INFO => 'qm/info',
		LogLevel::NOTICE => 'qm/notice',
		LogLevel::WARNING => 'qm/warning',
		LogLevel::ERROR => 'qm/error',
		LogLevel::CRITICAL => 'qm/critical',
		LogLevel::ALERT => 'qm/alert',
		LogLevel::EMERGENCY => 'qm/emergency',
	];

	/**
	 * Write the log to the Query Monitor log.
	 *
	 * @param array $record The record to write.
	 *
	 * @return void
	 */
	protected function write( array $record ) {
		if ( empty( $record['message'] ) || ! is_string( $record['message'] ) ) {
			return;
		}

		if ( ! isset( $record['level_name'] ) || ! is_string( $record['level_name'] ) || ! array_key_exists( strtolower( $record['level_name'] ), self::LEVEL_MAP ) ) {
			$record['level_name'] = LogLevel::DEBUG;
		}

		if ( ! isset( $record['context'] ) || ! is_array( $record['context'] ) ) {
			$record['context'] = [];
		}

		$qm_level = $this->map_level( $record['level_name'] );
		do_action( $qm_level, $record['message'], $record['context'] );
	}

	/**
	 * Map the log level to the Query Monitor log level.
	 *
	 * @param string $level_name Level name to map.
	 *
	 * @return string
	 */
	private function map_level( $level_name ) {
		return array_key_exists( strtolower( $level_name ), self::LEVEL_MAP ) ? self::LEVEL_MAP[ strtolower( $level_name ) ] : 'qm/debug';
	}
}
