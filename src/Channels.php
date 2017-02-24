<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Exception\InvalidChannelNameException;
use Monolog\Logger;

/**
 * Class that as a sort of service provider for loggers, creating them first time or just returning on subsequent
 * requests.
 *
 * It also holds in class constants the list of Wonolog default channels.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class Channels {

	const HTTP = 'HTTP';
	const DB = 'DB';
	const PHP_ERROR = 'PHP-ERROR';
	const SECURITY = 'SECURITY';
	const DEBUG = 'DEBUG';

	const FILTER_CHANNELS = 'wonolog.channels';
	const ACTION_INVALID_CHANNEL_NAME = 'wonolog.invalid-channel-name';

	private static $default_channels = [
		Channels::HTTP,
		Channels::DB,
		Channels::SECURITY,
		Channels::DEBUG,
	];

	/**
	 * @var Logger[]
	 */
	private $loggers = [];

	/**
	 * @var array
	 */
	private $channels = [];

	/**
	 * @return string[]
	 */
	public static function all_channels() {

		$default_channels = self::$default_channels;
		$channels         = apply_filters( self::FILTER_CHANNELS, $default_channels );

		return is_array( $channels ) ? array_unique( array_filter( $channels, 'is_string' ) ) : [];
	}

	/**
	 * Channels constructor.
	 */
	public function __construct() {

		$this->channels = self::all_channels();
	}

	/**
	 * @param string $channel
	 *
	 * @return Logger
	 */
	public function has_logger( $channel ) {

		return array_key_exists( $channel, $this->loggers );
	}

	/**
	 * @param string $channel
	 *
	 * @return Logger
	 *
	 * @throws InvalidChannelNameException
	 */
	public function logger( $channel ) {

		if ( ! in_array( $channel, $this->channels ) ) {
			throw new InvalidChannelNameException(
				sprintf( '%s is not a registered channel. Use "" to register custom channels', $channel )
			);
		}

		if ( ! $this->has_logger( $channel ) ) {
			$this->loggers[ $channel ] = new Logger( $channel );
		}

		return $this->loggers[ $channel ];
	}

}