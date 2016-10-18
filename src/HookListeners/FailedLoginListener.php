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

use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\Data\LogDataInterface;

/**
 * Listens to failed login attempts and logs them.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class FailedLoginListener implements ActionListenerInterface {

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

		$username = $args ? reset( $args ) : 'Unknown';

		return new FailedLogin( $username );
	}
}