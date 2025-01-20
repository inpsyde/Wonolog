<?php # -*- coding: utf-8 -*-
namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;

/**
 * At the end of any request looks for database errors and logs them if found.
 *
 * @package wonolog
 */
final class DbErrorListener implements ActionListenerInterface {

	use ListenerIdByClassNameTrait;

	/**
	 * @inheritdoc
	 */
	public function listen_to() {

		return 'shutdown';
	}

	/**
	 * Most db errors can't be caught up before request exit.
	 * This method runs on shutdown and look if there're errors in `$EZSQL_ERROR`
	 * global var and log them if so.
	 *
	 * @param array $args
	 *
	 * @return LogDataInterface
	 *
	 * @wp-hook shutdown
	 */
	public function update( array $args ) {

		/** @var array $EZSQL_ERROR */
		global $EZSQL_ERROR;
		if ( empty( $EZSQL_ERROR ) ) {
			return new NullLog();
		}

		$errors  = $EZSQL_ERROR;
		$last    = end( $errors );
		$context = [ 'last_query' => $last[ 'query' ], 'errors' => $errors, ];

		return new Error( $last[ 'error_str' ], Channels::DB, $context );
	}
}
