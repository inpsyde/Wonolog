<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Data;

use Inpsyde\Wonolog\Channels;
use Monolog\Logger;

/**
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class FailedLogin implements LogDataInterface {

	const TRANSIENT_NAME = 'wonolog.failed-login-count';

	/**
	 * @var string
	 */
	private $username;

	/**
	 * Contains the actual IP and the method used to retrieve it
	 *
	 * @var array
	 */
	private $ip_data;

	/**
	 * @var array
	 */
	private $attempts_data;

	/**
	 * @var int
	 */
	private $attempts;

	/**
	 * @param string $username Username used for the failed login attempt
	 */
	public function __construct( $username ) {

		$this->username = is_scalar( $username ) ? (string) $username : 'N/A';
	}

	/**
	 * Determine severity of the error based on the number of login attempts in
	 * las 5 minutes.
	 *
	 * @return int
	 */
	public function level() {

		isset( $this->attempts ) or $this->attempts = $this->count_attempts( 300 );

		switch ( TRUE ) {
			case ( $this->attempts > 2 && $this->attempts <= 100 ) :
				return Logger::NOTICE;
			case ( $this->attempts > 100 && $this->attempts <= 590 ) :
				return Logger::WARNING;
			case ( $this->attempts > 590 && $this->attempts <= 990 ) :
				return Logger::ERROR;
			case ( $this->attempts > 990 ) :
				return Logger::CRITICAL;
		}

		return 0;

	}

	/**
	 * @inheritdoc
	 */
	public function context() {

		list( $ip, $from ) = $this->sniff_ip();

		return [
			'ip'       => $ip,
			'ip_from'  => $from,
			'username' => $this->username
		];
	}

	/**
	 * @inheritdoc
	 */
	public function message() {

		isset( $this->attempts ) or $this->attempts = $this->count_attempts( 300 );

		if ( ! $this->attempts_data ) {
			return '';
		}

		list( $ip ) = $this->sniff_ip();
		if ( ! isset( $this->attempts_data[ $ip ][ 'count' ] ) ) {
			return '';
		}

		$message = sprintf(
			"%d failed login attempts from username '%s' in last 5 minutes",
			$this->attempts_data[ $ip ][ 'count' ],
			$this->username
		);

		$this->attempts_data[ $ip ] = [];

		return $message;
	}

	/**
	 * @inheritdoc
	 */
	public function channel() {

		return Channels::SECURITY;
	}

	/**
	 * Try to sniff the current client IP.
	 *
	 * @return array
	 */
	private function sniff_ip() {

		if ( $this->ip_data ) {
			return $this->ip_data;
		}

		if ( PHP_SAPI === 'cli' ) {
			$this->ip_data = [ '127.0.0.1', 'CLI' ];

			return $this->ip_data;
		}

		foreach ( [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $key ) {
			if ( array_key_exists( $key, $_SERVER ) ) {
				$this->ip_data = [ $_SERVER[ $key ], $key ];
			}
		}

		$this->ip_data or $this->ip_data = [ '0.0.0.0', 'Hidden IP' ];

		return $this->ip_data;
	}

	/**
	 * Determine how many failed login attempts comes from the guessed IP.
	 * Use a site transient to count them.
	 *
	 * @param int $ttl transient time to live in seconds
	 *
	 * @return int
	 */
	private function count_attempts( $ttl = 300 ) {

		list( $ip ) = $this->sniff_ip();

		$attempts = get_site_transient( self::TRANSIENT_NAME ) ? : [];

		// Seems the first time a failed attempt for this IP
		if ( ! $attempts || ! array_key_exists( $ip, $attempts ) ) {
			$attempts[ $ip ] = [ 'count' => 0, 'last_logged' => 0, ];
		}

		$attempts[ $ip ][ 'count' ] ++;

		// A couple of failed login attempts in 5 minutes are not a big deal.
		if ( $attempts[ $ip ][ 'count' ] < 3 ) {
			set_site_transient( self::TRANSIENT_NAME, $attempts, $ttl );
			$this->attempts_data = $attempts;

			return 0;
		}

		$count       = $attempts[ $ip ][ 'count' ];
		$last_logged = $attempts[ $ip ][ 'last_logged' ];

		/**
		 * During a brute force attack, logging all the failed attempts can be so expensive to put the server down.
		 * So we log:
		 *
		 * - 3rd attempt
		 * - every 20 when total attempts are > 23 && < 100 (23rd, 43rd...)
		 * - every 100 when total attempts are > 182 && < 1182 (183rd, 283rd...)
		 * - every 200 when total attempts are > 1182 (1183rd, 1383rd...)
		 */

		$do_log =
			( $last_logged === 0 )
			|| ( $count < 100 && ( $count - $last_logged ) === 20 )
			|| ( $count < 1000 && ( $count - $last_logged ) === 100 )
			|| ( ( $count - $last_logged ) === 200 );

		if ( ! $do_log ) {
			set_site_transient( self::TRANSIENT_NAME, $attempts, $ttl );

			return 0;
		}

		$attempts[ $ip ][ 'last_logged' ] = $count;

		set_site_transient( self::TRANSIENT_NAME, $attempts, $ttl );
		$this->attempts_data = $attempts;

		return $count;
	}
}
