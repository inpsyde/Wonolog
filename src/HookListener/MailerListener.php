<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class MailerListener implements ActionListenerInterface {

	use ListenerIdByClassNameTrait;

	/**
	 * @return string|string[]
	 */
	public function listen_to() {

		return [ 'phpmailer_init', 'wp_mail_failed' ];
	}

	/**
	 * @param array $args
	 *
	 * @return LogDataInterface
	 */
	public function update( array $args ) {

		switch ( current_filter() ) {
			case 'phpmailer_init' :
				$this->on_mailer_init( $args );
				break;
			case 'wp_mail_failed' :
				return $this->on_mail_failed( $args );
		}

		return new NullLog();

	}

	/**
	 * @param array $args
	 *
	 * @return LogDataInterface
	 */
	private function on_mail_failed( array $args ) {

		$error = $args ? reset( $args ) : NULL;
		if ( is_wp_error( $error ) ) {

			return Log::from_wp_error( $error, Logger::ERROR, Channels::HTTP );
		}

		return new NullLog();
	}

	/**
	 * @param array $args
	 */
	private function on_mailer_init( array $args ) {

		$mailer = $args ? reset( $args ) : NULL;
		if ( ! $mailer instanceof \PHPMailer ) {
			return;
		}

		$mailer->SMTPDebug   = 2;
		$mailer->Debugoutput = function ( $message ) {

			do_action( \Inpsyde\Wonolog\LOG, new Debug( $message, Channels::HTTP ) );
		};
	}
}