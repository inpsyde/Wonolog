<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Exception;

use Inpsyde\Wonolog\Channels;

/**
 * @package wonolog
 */
class InvalidChannelNameException extends \Exception {

	/**
	 * @param $value
	 *
	 * @return static
	 */
	public static function for_invalid_type( $value ) {

		return new static(
			sprintf(
				'Channel name must me in a string, %s received.',
				is_object( $value ) ? 'instance of ' . get_class( $value ) : gettype( $value )
			)
		);
	}

	/**
	 * @param string $channel
	 *
	 * @return static
	 */
	public static function for_unregistered_channel( $channel ) {

		return new static(
			sprintf(
				'%s is not a registered channel. Use "%s" filter hook to register custom channels',
				$channel,
				Channels::FILTER_CHANNELS
			)
		);
	}
}
