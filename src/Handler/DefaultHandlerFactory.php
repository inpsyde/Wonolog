<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Handler;

use Inpsyde\Wonolog\LogLevel;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;

/**
 * Wonolog builds a default handler if no custom handler is provided.
 * This class has the responsibility to create an instance of this default handler using sensitive defaults
 * and allowing configuration via hooks and environment variables.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class DefaultHandlerFactory {

	const FILTER_FOLDER = 'wonolog.default-handler-folder';
	const FILTER_FILENAME = 'wonolog.default-handler-filename';
	const FILTER_DATE_FORMAT = 'wonolog.default-handler-date-format';
	const FILTER_BUBBLE = 'wonolog.default-handler-bubble';
	const FILTER_USE_LOCKING = 'wonolog.default-handler-use-locking';

	/**
	 * @var HandlerInterface
	 */
	private $default_handler;

	/**
	 * @param HandlerInterface|NULL $handler
	 *
	 * @return static
	 */
	public static function with_default_handler( HandlerInterface $handler = NULL ) {

		$instance                  = new static();
		$instance->default_handler = $handler;

		return $instance;
	}

	/**
	 * @return HandlerInterface
	 */
	public function create_default_handler() {

		if ( $this->default_handler ) {
			return $this->default_handler;
		}

		$this->default_handler = $this->create_default_handler_from_configs();

		return $this->default_handler;
	}

	/**
	 * @return HandlerInterface
	 */
	private function create_default_handler_from_configs() {

		$folder = $this->handler_folder();

		if ( ! $folder ) {
			return new NullHandler();
		}

		list( $filename_format, $date_format ) = $this->handler_file_info();

		$log_level = LogLevel::instance();
		$stat      = @stat( $folder );

		try {
			$handler = new DateBasedStreamHandler(
				"{$folder}/{$filename_format}",
				$date_format,
				$log_level->default_min_level(),
				apply_filters( self::FILTER_BUBBLE, TRUE ),
				isset( $stat[ 'mode' ] ) ? ( $stat[ 'mode' ] & 0007777 ) : 0755,
				apply_filters( self::FILTER_USE_LOCKING, TRUE )
			);
		}
		catch ( \Exception $e ) {
			$handler = new NullHandler();
		}

		return $handler;
	}

	/**
	 * @return string
	 */
	private function handler_folder() {

		$folder = getenv( 'WONOLOG_DEFAULT_HANDLER_FILE_DIR' );

		if ( ! $folder && defined( 'WP_CONTENT_DIR' ) ) {
			$folder = rtrim( WP_CONTENT_DIR, '\\/' ) . '/wonolog';
		}

		$folder = apply_filters( self::FILTER_FOLDER, $folder );
		is_string( $folder ) or $folder = '';

		if ( $folder ) {
			$folder = rtrim( wp_normalize_path( $folder ), '/' );
			wp_mkdir_p( $folder ) or $folder = '';
		}

		return $folder;
	}

	/**
	 * @return array
	 */
	private function handler_file_info() {

		$filename_format = apply_filters( self::FILTER_FILENAME, '{date}.log' );
		$date_format     = apply_filters( self::FILTER_DATE_FORMAT, 'Y/m/d' );

		is_string( $filename_format ) and $filename_format = ltrim( $filename_format, '\\/' );

		return [ $filename_format, $date_format ];
	}

}