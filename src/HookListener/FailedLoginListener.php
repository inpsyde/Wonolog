<?php # -*- coding: utf-8 -*-
namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\Data\LogDataInterface;

/**
 * Listens to failed login attempts and logs them.
 *
 * @package wonolog
 */
final class FailedLoginListener implements ActionListenerInterface {

	use ListenerIdByClassNameTrait;

	/**
	 * @inheritdoc
	 */
	public function listen_to() {

		return 'wp_login_failed';
	}

	/**
	 * Logs failed login attempts.
	 *
	 * @param array $args
	 *
	 * @return LogDataInterface
	 *
	 * @wp-hook wp_login_failed
	 *
	 * @see     \Inpsyde\Wonolog\Data\FailedLogin
	 */
	public function update( array $args ) {

		$username = $args ? reset( $args ) : 'Unknown user';

		return new FailedLogin( $username );
	}
}
