<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Functional;

use Inpsyde\Wonolog;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\HookListener;
use Inpsyde\Wonolog\Tests\FunctionalTestCase;
use Monolog\Logger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @runTestsInSeparateProcesses
 */
class DefaultHandlerLoggingTest extends FunctionalTestCase {

	private function check_logged_message( $message, $channel, $level, $text, $wp_context = TRUE ) {

		$regex = '^\[[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\] ';
		$regex .= $channel . '\.';
		$regex .= Logger::getLevelName( $level ) . ': ';
		$regex .= $message . ' \[\] ';
		$regex .= $wp_context
			? preg_quote( '{"wp":{"doing_cron":false,"doing_ajax":false,"is_admin":false}}', '~' )
			: '\[\]';

		$regex .= '$';

		self::assertRegExp( "~{$regex}~", $text );
	}

	public function test_log_custom_hook() {

		$dir = vfsStream::setup( 'TestDir' );
		$url = $dir->url();
		putenv( "WONOLOG_DEFAULT_HANDLER_ROOT_DIR={$url}" );

		Wonolog\bootstrap();

		do_action( Wonolog\LOG, new Error( 'Log via hook happened!', Channels::DB ) );

		$path = date( 'Y/m/d' ) . '.log';

		self::assertTrue( $dir->hasChild( $path ) );

		/** @var vfsStreamFile $file */
		$file = $dir->getChild( $path );

		$this->check_logged_message(
			'Log via hook happened!',
			Channels::DB,
			Logger::ERROR,
			$file->getContent()
		);
	}

	public function test_log_core_hook() {

		$dir = vfsStream::setup( 'TestDir' );
		$url = $dir->url();
		putenv( "WONOLOG_DEFAULT_HANDLER_ROOT_DIR={$url}" );

		Wonolog\bootstrap();

		do_action( 'muplugins_loaded' );

		$error = \Mockery::mock( \WP_Error::class );
		$error->shouldReceive( 'get_error_message' )
			->andReturn( 'WP mail is broken' );
		$error->shouldReceive( 'get_error_data' )
			->andReturn( [] );
		$error->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'wp_mail_failed' ] );

		do_action( 'wp_mail_failed', $error );

		$path = date( 'Y/m/d' ) . '.log';

		self::assertTrue( $dir->hasChild( $path ) );

		/** @var vfsStreamFile $file */
		$file = $dir->getChild( $path );

		$this->check_logged_message(
			'WP mail is broken',
			Channels::HTTP,
			Logger::ERROR,
			$file->getContent()
		);
	}

	public function test_core_hook_not_logged_if_no_hook_listeners() {

		$dir = vfsStream::setup( 'TestDir' );
		$url = $dir->url();
		putenv( "WONOLOG_DEFAULT_HANDLER_ROOT_DIR={$url}" );

		Wonolog\bootstrap( NULL, Wonolog\USE_DEFAULT_NONE );

		do_action( 'muplugins_loaded' );

		$error = \Mockery::mock( \WP_Error::class );
		$error->shouldReceive( 'get_error_message' )
			->andReturn( 'WP mail is broken' );
		$error->shouldReceive( 'get_error_data' )
			->andReturn( [] );
		$error->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'wp_mail_failed' ] );

		do_action( 'wp_mail_failed', $error );

		$path = date( 'Y/m/d' ) . '.log';

		self::assertFalse( $dir->hasChild( $path ) );
	}

	public function test_core_hook_with_single_listener() {

		$dir = vfsStream::setup( 'TestDir' );
		$url = $dir->url();
		putenv( "WONOLOG_DEFAULT_HANDLER_ROOT_DIR={$url}" );

		Wonolog\bootstrap( NULL, Wonolog\USE_DEFAULT_HANDLER )
			->useHookListener( new HookListener\MailerListener() );

		do_action( 'muplugins_loaded' );

		$error = \Mockery::mock( \WP_Error::class );
		$error->shouldReceive( 'get_error_message' )
			->andReturn( 'WP mail is broken' );
		$error->shouldReceive( 'get_error_data' )
			->andReturn( [] );
		$error->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'wp_mail_failed' ] );

		do_action( 'wp_mail_failed', $error );

		$path = date( 'Y/m/d' ) . '.log';

		self::assertTrue( $dir->hasChild( $path ) );

		/** @var vfsStreamFile $file */
		$file = $dir->getChild( $path );

		$this->check_logged_message(
			'WP mail is broken',
			Channels::HTTP,
			Logger::ERROR,
			$file->getContent(),
			FALSE
		);
	}

}
