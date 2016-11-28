<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Functions;
use Brain\Monkey\WP\Actions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\HookListeners\MailerListener;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class MailerListenerTest extends TestCase {

	public function test_on_mailer_init() {

		$listener = new MailerListener();

		Actions::expectFired( 'phpmailer_init' )
			->once()
			->whenHappen(
				function ( \PHPMailer $mailer ) use ( $listener ) {

					$log = $listener->update( [ $mailer ] );
					self::assertInstanceOf( NullLog::class, $log );
				}
			);

		Actions::expectFired( 'wonolog.log' )
			->once()
			->whenHappen(
				function ( Debug $debug ) {

					self::assertSame( 'Test email!', $debug->message() );
					self::assertSame( Channels::HTTP, $debug->channel() );
				}
			);

		$mailer              = \Mockery::mock( \PHPMailer::class );
		$mailer->SMTPDebug   = 0;
		$mailer->Debugoutput = NULL;

		do_action( 'phpmailer_init', $mailer );

		self::assertSame( 2, $mailer->SMTPDebug );
		self::assertInternalType( 'callable', $mailer->Debugoutput );

		/** @var callable $callback */
		$callback = $mailer->Debugoutput;
		$callback( 'Test email!' );
	}

	public function test_on_mail_failed() {

		$listener = new MailerListener();

		Functions::when( 'is_wp_error' )
			->alias(
				function ( $thing ) {

					return $thing instanceof \WP_Error;
				}
			);

		Actions::expectFired( 'wp_mail_failed' )
			->once()
			->whenHappen(
				function ( \WP_Error $error ) use ( $listener ) {

					/** @var LogDataInterface $log */
					$log   = $listener->update( [ $error ] );

					self::assertInstanceOf( LogDataInterface::class, $log );
					self::assertSame( Logger::ERROR, $log->level() );
					self::assertSame( Channels::HTTP, $log->channel() );
				}
			);

		$error = \Mockery::mock( '\WP_Error' );
		$error->shouldReceive( 'get_error_message' )
			->andReturn( 'Something when wrong!' );
		$error->shouldReceive( 'get_error_codes' )
			->andReturn( [ 0 ] );
		$error->shouldReceive( 'get_error_data' )
			->andReturn( [] );

		do_action( 'wp_mail_failed', $error );
	}

	/**
	 * Check for a consistent return type
	 *
	 * @see MailerListener::update()
	 * @see MailerListener::on_mail_failed()
	 */
	public function test_on_mail_failed_return_type() {

		$listener = new MailerListener();

		Functions::expect( 'current_filter' )
			->once()
			->andReturn( 'wp_mail_failed' );

		Functions::expect( 'is_wp_error' )
			->once()
			->andReturn( FALSE );

		$this->assertInstanceOf(
			LogDataInterface::class,
			$listener->update( [] )
		);
	}
}
