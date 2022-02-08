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
 * Class QueryMonitorHandler.
 *
 * @package BoxUk\Plugins\Base
 */
class QueryMonitorHandler extends AbstractProcessingHandler {
	private static $level_map = array(
		LogLevel::DEBUG => 'qm/debug',
		LogLevel::INFO => 'qm/info',
		LogLevel::NOTICE => 'qm/notice',
		LogLevel::WARNING => 'qm/warning',
		LogLevel::ERROR => 'qm/error',
		LogLevel::CRITICAL => 'qm/critical',
		LogLevel::ALERT => 'qm/alert',
		LogLevel::EMERGENCY => 'qm/emergency',
	);

	/**
	 * Write the log to the Query Monitor log.
	 *
	 * @param array $record The record to write.
	 *
	 * @return void
	 */
	protected function write( array $record ) {
		$level = $this->map_level( $record['level_name'] );
		do_action( $level, $record['message'], $record['context'] );
	}

	/**
	 * Map the log level to the Query Monitor log level.
	 *
	 * @param string $level_name Level name to map.
	 *
	 * @return string
	 */
	private function map_level( $level_name ) {
		return isset( self::$level_map[ strtolower( $level_name ) ] ) ? self::$level_map[ strtolower( $level_name ) ] : 'qm/debug';
	}
}
