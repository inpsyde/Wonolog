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

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\LogLevel;
use Monolog\Logger;

/**
 * The generic log data object.
 *
 * It is a value object used to pass data to wonolog.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class Log implements LogDataInterface {

	use LogDataTrait;

	/**
	 * @var string
	 */
	private $level;

	/**
	 * @param \WP_Error $error
	 * @param int       $level
	 * @param string    $channel
	 *
	 * @return Log
	 */
	public static function from_wp_error( \WP_Error $error, $level = Logger::NOTICE, $channel = '' ) {

		$log_level = LogLevel::instance();
		$level     = $log_level->check_level( $level ) ? : Logger::NOTICE;

		$message = $error->get_error_message();
		$context = $error->get_error_data();

		if ( $channel ) {
			return new static( $message, $level, $channel, $context );
		}

		$channel = WpErrorChannel::for_error( $error )
			->channel();

		// Raise level for "guessed" channels
		if ( $channel === Channels::SECURITY && $level < Logger::ERROR ) {
			$level = Logger::ERROR;
		} elseif ( $channel !== Channels::DEBUG && $level < Logger::WARNING ) {
			$level = Logger::WARNING;
		}

		return new static( $message, $level, $channel, $context );
	}

	/**
	 * @param array $log_data
	 *
	 * @return Log
	 */
	public static function from_array( array $log_data ) {

		$defaults = [
			'message' => 'Unknown error',
			'level'   => Logger::DEBUG,
			'channel' => Channels::DEBUG,
			'context' => []
		];

		$log_level = LogLevel::instance();
		$levels    = Logger::getLevels();

		// $data is a numeric indexed array, try to "discover" arguments based on items value and type
		if ( $log_data && ! array_filter( array_keys( $log_data ), 'is_string' ) ) {
			$values   = $log_data;
			$log_data = [];

			$channels = Channels::all_channels();

			$object_context = NULL;
			$log_levels     = [];

			foreach ( $values as $value ) {
				if ( count( $log_data ) === 4 ) {
					break;
				}
				if ( ! isset( $log_data[ 'channel' ] ) && is_string( $value ) && in_array( $value, $channels, TRUE ) ) {
					$log_data[ 'channel' ] = $value;
					continue;
				}
				if (
					( is_string( $value ) || is_numeric( $value ) )
					&& ( $min_level = $log_level->check_level( $value, $levels ) )
				) {
					$log_levels[] = $min_level;
					continue;
				}
				if ( ! isset( $log_data[ 'message' ] ) && is_string( $value ) ) {
					$log_data[ 'message' ] = $value;
					continue;
				}
				if ( ! isset( $log_data[ 'context' ] ) && is_array( $value ) ) {
					$log_data[ 'context' ] = $value;
				}
				if ( is_null( $object_context ) && is_object( $value ) ) {
					$object_context = $value;
				}
			}

			$log_levels and $log_data[ 'level' ] = max( $log_levels );

			// If no message was found, but an object was passed as log data, we use its class name for the message.
			if ( ! isset( $log_data[ 'message' ] ) && $object_context ) {
				$log_data[ 'message' ] = 'Logged: ' . get_class( $object_context );
			}

			// If no array context was found, but an object was passed as log data, we use that for context
			if ( ! isset( $log_data[ 'context' ] ) && $object_context ) {
				switch ( TRUE ) {
					case ( is_callable( [ $object_context, 'to_array' ] ) ) :
						$log_data[ 'context' ] = $object_context->to_array();
						break;
					case ( is_callable( [ $object_context, 'as_array' ] ) ) :
						$log_data[ 'context' ] = $object_context->as_array();
						break;
					case ( is_callable( [ $object_context, 'toArray' ] ) ) :
						$log_data[ 'context' ] = $object_context->toArray();
						break;
					case ( is_callable( [ $object_context, 'asArray' ] ) ) :
						$log_data[ 'context' ] = $object_context->asArray();
						break;
					default :
						$log_data[ 'context' ] = get_object_vars( $object_context );
				}
			}
		}

		$log_data = array_change_key_case( $log_data, CASE_LOWER );
		if ( isset( $log_data[ 'level' ] ) && is_string( $log_data[ 'level' ] ) ) {
			$log_data[ 'level' ] = $log_level->check_level( $log_data[ 'level' ], $levels );
		}

		$data = array_merge( $defaults, $log_data );

		return new static( $data[ 'message' ], $data[ 'level' ], $data[ 'channel' ], $data[ 'context' ] );

	}

	/**
	 * @param string $message
	 * @param int    $level
	 * @param string $channel
	 * @param array  $context
	 */
	public function __construct(
		$message = '',
		$level = Logger::DEBUG,
		$channel = Channels::DEBUG,
		array $context = []
	) {

		$this->level   = $level;
		$this->message = $message;
		$this->channel = $channel;
		$this->context = $context;
	}

	/**
	 * @inheritdoc
	 */
	public function level() {

		return $this->level;
	}
}
