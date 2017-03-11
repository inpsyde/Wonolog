<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Exception\InvalidChannelNameException;
use Inpsyde\Wonolog\Handler\HandlersRegistry;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

/**
 * Class that acts as a sort of service provider for loggers, creating them first time or just returning on subsequent
 * requests.
 * We don't use Monolog registry to be able to register handle here as constants the list of Wonolog default channels
 * and to initialize via hooks the logger the first time is retrieved.
 *
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
	const ACTION_LOGGER = 'wonolog.logger';
	const FILTER_USE_DEFAULT_HANDLER = 'wonolog.use-default-handler';
	const FILTER_USE_DEFAULT_PROCESSOR = 'wonolog.use-default-processor';

	private static $default_channels = [
		Channels::HTTP,
		Channels::DB,
		Channels::SECURITY,
		Channels::DEBUG,
	];

	/**
	 * @var HandlersRegistry
	 */
	private $handlers_registry;

	/**
	 * @var ProcessorsRegistry
	 */
	private $processors_registry;

	/**
	 * @var Logger[]
	 */
	private $loggers = [];

	/**
	 * @var string[]
	 */
	private $channels = [];

	/**
	 * @var string[]
	 */
	private $channels_initialized = [];

	/**
	 * @return string[]
	 */
	public static function all_channels() {

		$default_channels = self::$default_channels;
		$channels         = apply_filters( self::FILTER_CHANNELS, $default_channels );

		return is_array( $channels ) ? array_unique( array_filter( $channels, 'is_string' ) ) : [];
	}

	/**
	 * @param HandlersRegistry   $handlers
	 * @param ProcessorsRegistry $processors
	 */
	public function __construct( HandlersRegistry $handlers, ProcessorsRegistry $processors ) {

		$this->channels            = self::all_channels();
		$this->handlers_registry   = $handlers;
		$this->processors_registry = $processors;
	}

	/**
	 * @param string $channel
	 *
	 * @return bool
	 * @throws InvalidChannelNameException
	 */
	public function has_channel( $channel ) {

		if ( ! is_string( $channel ) ) {
			throw InvalidChannelNameException::for_invalid_type( $channel );
		}

		return in_array( $channel, $this->channels );
	}

	/**
	 * @param string $channel
	 *
	 * @return Logger
	 *
	 * @throws InvalidChannelNameException
	 */
	public function logger( $channel ) {

		if ( ! $this->has_channel( $channel ) ) {
			throw InvalidChannelNameException::for_unregistered_channel( $channel );
		}

		if ( ! array_key_exists( $channel, $this->loggers ) ) {
			$this->loggers[ $channel ] = new Logger( $channel );
		}

		if ( ! in_array( $channel, $this->channels_initialized, TRUE ) ) {
			$this->channels_initialized[] = $channel;

			return $this->initialize_logger( $this->loggers[ $channel ] );
		}

		return $this->loggers[ $channel ];
	}

	/**
	 * @param Logger $logger
	 *
	 * @return Logger
	 */
	private function initialize_logger( Logger $logger ) {

		$default_handler = $this->use_default_handler( $logger );
		$default_handler and $logger = $logger->pushHandler( $default_handler );

		$default_processor = $this->use_default_processor( $logger );
		$default_processor and $logger = $logger->pushProcessor( $default_processor );

		/**
		 * Fire before a logger is used first time.
		 * Can be used to setup the logger, for example adding handlers or processors.
		 */
		do_action( self::ACTION_LOGGER, $logger, $this->handlers_registry, $this->processors_registry );

		return $logger;
	}

	/**
	 * @param $logger
	 *
	 * @return HandlerInterface|null
	 */
	private function use_default_handler( $logger ) {

		$handler = $this->handlers_registry->find( HandlersRegistry::DEFAULT_NAME );

		if (
			$handler instanceof HandlerInterface
			&& (bool) apply_filters( self::FILTER_USE_DEFAULT_HANDLER, TRUE, $logger, $handler )
		) {

			return $handler;
		}

		return NULL;
	}

	/**
	 * @param $logger
	 *
	 * @return callable|null
	 */
	private function use_default_processor( $logger ) {

		$processor = $this->processors_registry->find( ProcessorsRegistry::DEFAULT_NAME );

		if (
			is_callable( $processor )
			&& (bool) apply_filters( self::FILTER_USE_DEFAULT_PROCESSOR, TRUE, $logger, $processor )
		) {

			return $processor;
		}

		return NULL;
	}

}