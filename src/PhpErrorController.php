<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog;

use Monolog\Logger;
use Inpsyde\Wonolog\Data\Log;

/**
 * Handler for PHP core errors, used to log those errors mapping error types to log levels.
 *Monolog
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class PhpErrorController {

	private static $errors_level_map = [
		E_USER_ERROR        => Logger::ERROR,
		E_USER_NOTICE       => Logger::NOTICE,
		E_USER_WARNING      => Logger::WARNING,
		E_USER_DEPRECATED   => Logger::NOTICE,
		E_RECOVERABLE_ERROR => Logger::ERROR,
		E_WARNING           => Logger::WARNING,
		E_NOTICE            => Logger::NOTICE,
		E_DEPRECATED        => Logger::NOTICE,
		E_STRICT            => Logger::NOTICE,
		E_ERROR             => Logger::CRITICAL,
		E_PARSE             => Logger::CRITICAL,
		E_CORE_ERROR        => Logger::CRITICAL,
		E_CORE_WARNING      => Logger::CRITICAL,
		E_COMPILE_ERROR     => Logger::CRITICAL,
		E_COMPILE_WARNING   => Logger::CRITICAL,
	];

	const CHANNEL = Channels::PHP_ERROR;

	/**
	 * Initialize the handler.
	 */
	public function init() {

		register_shutdown_function( [ $this, 'onFatal', ] );
		set_error_handler( [ $this, 'onError', ] );
		set_exception_handler( [ $this, 'onException', ] );
	}

	/**
	 * Error handler.
	 *
	 * @param  int        $num
	 * @param  string     $str
	 * @param  string     $file
	 * @param  int        $line
	 * @param  array|null $context
	 *
	 * @return bool
	 */
	public function onError( $num, $str, $file, $line, $context = NULL ) {

		$ext_context = array_merge( (array) $context, [ 'file' => $file, 'line' => $line ] );

		do_action(
			'wonolog.log',
			new Log( $str, self::$errors_level_map[ $num ], self::CHANNEL, $ext_context )
		);

		return FALSE;
	}

	/**
	 * Uncaught exception handler.
	 *
	 * @param  \Exception $e
	 *
	 * @throws \Exception
	 */
	public function onException( \Exception $e ) {

		do_action(
			'wonolog.log',
			new Log(
				$e->getMessage(),
				Logger::CRITICAL,
				self::CHANNEL,
				[
					'exception' => get_class( $e ),
					'file'      => $e->getFile(),
					'line'      => $e->getLine(),
					'trace'     => $e->getTraceAsString()
				]
			)
		);

		// after logging let's reset handler and throw the exception
		restore_exception_handler();
		throw $e;
	}

	/**
	 * Checks for a fatal error, work-around for `set_error_handler` not working with fatal errors.
	 */
	public function onFatal() {

		$error  = error_get_last();
		$fatals = [
			E_ERROR,
			E_PARSE,
			E_CORE_ERROR,
			E_CORE_WARNING,
			E_COMPILE_ERROR,
			E_COMPILE_WARNING,
		];

		if ( in_array( $error[ 'type' ], $fatals, TRUE ) ) {
			$this->onError( $error[ 'type' ], $error[ 'message' ], $error[ 'file' ], $error[ 'line' ] );
		}
	}
}
