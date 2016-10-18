<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Data;

use Monolog\Logger;

/**
 * A log level with variable value for level, that is returned from a callback set in constructor or via
 * with_level_callback() that is a factory method (it returns a _new_ instance).
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class VariableLevelLog implements LogDataInterface {

	use LogDataTrait;

	/**
	 * @var callable
	 */
	private $level_callback;

	/**
	 * Named constructor that receives same regular constructor arguments but `$level_callback`, useful to get
	 * a fully setup instance via `with_level_callback()` method.
	 *
	 * @example <code>$log = VariableLevelLog::instance( $message, $channel )->with_level_callback( $callback );</code>
	 *
	 * @param string $message
	 * @param string $channel
	 * @param array  $context
	 *
	 * @return VariableLevelLog
	 */
	public static function instance( $message, $channel, array $context = [] ) {

		return new static( $message, $channel, $context );
	}

	/**
	 * @param callable $level_callback
	 *
	 * @return VariableLevelLog
	 */
	public function with_level_callback( callable $level_callback ) {

		$instance                 = clone $this;
		$instance->level_callback = $level_callback;

		return $instance;
	}

	/**
	 * @return string
	 */
	public function level() {

		$callback = $this->level_callback;
		if ( ! $callback ) {
			return Logger::DEBUG;
		}

		return $callback();
	}
}
