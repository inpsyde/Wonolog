<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Inpsyde\Wonolog\HookListeners;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;

/**
 * Listens to 'http_api_debug' hook to discover and log WP HTTP API errors.
 *
 * Differentiate between WP cron requests and other HTTP requests.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class HttpApiListener implements ActionListenerInterface {

	/**
	 * @inheritdoc
	 */
	public function listen_to() {

		return 'http_api_debug';
	}

	/**
	 * Log HTTP cron requests.
	 *
	 * @param array $args
	 *
	 * @return LogDataInterface
	 *
	 * @wp-hook 'http_api_debug'
	 */
	public function update( array $args ) {

		/**
		 * @var \WP_Error|array $response
		 * @var string          $context
		 * @var string          $class
		 */
		list( $response, $context, $class ) = $args;
		$http_args = isset( $args[ 3 ] ) ? $args[ 3 ] : [];
		$url       = isset( $args[ 4 ] ) ? $args[ 4 ] : '';

		if ( $this->isError( $response ) ) {
			return $this->logHttpError( $response, $context, $class, $http_args, $url );
		}

		if ( $this->isCron( $response, $url ) ) {
			return $this->logCron( $response, $context, $class, $http_args, $url );
		}

		return new NullLog();
	}

	/**
	 * @param array|\WP_Error $response
	 *
	 * @return bool
	 */
	private function isError( $response ) {

		return
			is_wp_error( $response )
			|| (
				is_array( $response )
				&& isset( $response[ 'response' ] )
				&& is_array( $response[ 'response' ] )
				&& isset( $response[ 'response' ][ 'code' ] )
				&& is_numeric( $response[ 'response' ][ 'code' ] )
				&& (int) $response[ 'response' ][ 'code' ] !== 200
			);
	}

	/**
	 * @param array|\WP_Error $response
	 * @param string          $url
	 *
	 * @return bool
	 */
	private function isCron( $response, $url ) {

		return
			is_array( $response )
			&& basename( parse_url( $url, PHP_URL_PATH ) ) === 'wp-cron.php';
	}

	/**
	 * Log HTTP cron requests.
	 *
	 * @param \WP_Error|array $response
	 * @param string          $context
	 * @param string          $class
	 * @param array           $args
	 * @param string          $url
	 *
	 * @return Debug
	 */
	private function logCron( $response, $context, $class, array $args = [], $url = '' ) {

		$log_context = [
			'transport'  => $class,
			'context'    => $context,
			'query_args' => $args,
			'url'        => $url,
		];

		if ( is_array( $response ) && isset( $response[ 'headers' ] ) ) {
			$log_context[ 'headers' ] = $response[ 'headers' ];
		}

		return new Debug( 'Cron request', Channels::DEBUG, $log_context );
	}

	/**
	 * Log any error for HTTP API.
	 *
	 * @param \WP_Error|array $response
	 * @param string          $context
	 * @param string          $class
	 * @param array           $args
	 * @param string          $url
	 *
	 * @return Error
	 */
	private function logHttpError( $response, $context, $class, array $args = [], $url = '' ) {

		$msg = is_wp_error( $response ) ? $response->get_error_message() : $response[ 'response' ][ 'message' ];

		$log_context = [ 'transport' => $class, 'context' => $context, 'query_args' => $args, 'url' => $url, ];

		if ( is_array( $response ) && isset( $response[ 'headers' ] ) ) {
			$msg .= ' - Response code: ' . $response[ 'response' ][ 'code' ];
			$log_context[ 'headers' ] = $response[ 'headers' ];
		}

		return new Error( "WP HTTP API Error, {$msg}", Channels::HTTP, $log_context );
	}
}