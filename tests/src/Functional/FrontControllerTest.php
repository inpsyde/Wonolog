<?php # -*- coding: utf-8 -*-
/*
 * This file is part of theInpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Functional;

use Brain\Monkey\Functions;
use Brain\Monkey\WP\Actions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Critical;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\Info;
use Inpsyde\Wonolog\FrontController;
use Inpsyde\Wonolog\HookListeners\ActionListenerInterface;
use Inpsyde\Wonolog\HookListenersRegistry;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class FrontControllerTest extends TestCase {

	protected function setUp() {

		parent::setUp();

		Functions::when( 'trailingslashit' )
			->alias(
				function ( $path ) {

					return rtrim( $path, '\\/' ) . '/';
				}
			);

		Functions::when( 'wp_mkdir_p' )
			->alias(
				function ( $dir ) {

					return file_exists( $dir ) or mkdir( $dir, 0777, TRUE );
				}
			);

		Functions::when( 'is_wp_error' )
			->alias(
				function ( $thing ) {

					return $thing instanceof \WP_Error;
				}
			);
	}

	private function bootstrap_package() {

		/** @noinspection PhpIncludeInspection */
		require_once getenv( 'LIBRARY_PATH' ) . '/inc/bootstrap.php';

		global $wp_filter;
		/** @var callable $boot */
		$boot = $wp_filter[ 'muplugins_loaded' ][ 20 ][ FrontController::class . '::boot' ][ 'function' ];
		$boot();
	}

	private function check_logged_message( $message, $channel, $level, $text ) {

		$regex = '^\[[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\] ';
		$regex .= $channel . '\.';
		$regex .= Logger::getLevelName( $level ) . ': ';
		$regex .= $message . ' \[\] \[\]$';

		self::assertRegExp( "~{$regex}~", $text );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_call_listen_directly() {

		$dir = vfsStream::setup( 'TestDir' );
		$url = $dir->url();
		putenv( "WONOLOG_HANDLER_FILE_DIR={$url}" );

		/** @var callable $listener */
		$listener = NULL;

		Actions::expectAdded( \Inpsyde\Wonolog\LOG )
			->once()
			->whenHappen(
				function ( callable $callback ) use ( &$listener ) {

					$listener = $callback;
				}
			);

		$this->bootstrap_package();

		$log = new Critical( 'Log happen!1!', Channels::SECURITY );
		$listener( $log );

		$path = date( 'Y/m-d' ) . '.log';

		self::assertTrue( $dir->hasChild( $path ) );

		/** @var vfsStreamFile $file */
		$file = $dir->getChild( $path );

		$this->check_logged_message(
			'Log happen!1!',
			Channels::SECURITY,
			Logger::CRITICAL,
			$file->getContent()
		);

	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_log_action_fired() {

		$dir = vfsStream::setup( 'TestDir' );
		$url = $dir->url();
		putenv( "WONOLOG_HANDLER_FILE_DIR={$url}" );

		/** @var callable $listener */
		$listener = NULL;

		Actions::expectAdded( \Inpsyde\Wonolog\LOG )
			->once()
			->whenHappen(
				function ( callable $callback ) use ( &$listener ) {

					$listener = $callback;
				}
			);

		Actions::expectFired( \Inpsyde\Wonolog\LOG )
			->once()
			->whenHappen(
				function ( $log ) use ( &$listener ) {

					$listener( $log );
				}
			);

		$this->bootstrap_package();

		do_action( \Inpsyde\Wonolog\LOG, new Error( 'Log via hook happened!', Channels::DB ) );
		$path = date( 'Y/m-d' ) . '.log';

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

	/**
	 * @runInSeparateProcess
	 */
	public function test_log_action_fired_with_wp_error() {

		$dir = vfsStream::setup( 'TestDir' );
		$url = $dir->url();
		putenv( "WONOLOG_HANDLER_FILE_DIR={$url}" );
		putenv( "WONOLOG_DEFAULT_MIN_LEVEL=DEBUG" );

		/** @var callable $listener */
		$listener = NULL;

		Actions::expectAdded( \Inpsyde\Wonolog\LOG )
			->once()
			->whenHappen(
				function ( callable $callback ) use ( &$listener ) {

					$listener = $callback;
				}
			);

		Actions::expectFired( \Inpsyde\Wonolog\LOG )
			->once()
			->whenHappen(
				function ( $log ) use ( &$listener ) {

					$listener( $log );
				}
			);

		$this->bootstrap_package();

		$error = \Mockery::mock( 'WP_Error' );
		$error->shouldReceive( 'get_error_message' )
			->andReturn( 'I am an error!' );
		$error->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'wpdb_failed' ] );
		$error->shouldReceive( 'get_error_data' )
			->andReturn( [] );

		do_action( \Inpsyde\Wonolog\LOG, $error );

		$path = date( 'Y/m-d' ) . '.log';

		self::assertTrue( $dir->hasChild( $path ) );

		/** @var vfsStreamFile $file */
		$file = $dir->getChild( $path );

		$this->check_logged_message(
			'I am an error!',
			Channels::DB,
			Logger::WARNING,
			$file->getContent()
		);

	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_hook_listener() {

		define( 'WP_DEBUG_LOG', TRUE );

		$dir = vfsStream::setup( 'TestDir' );
		$url = $dir->url();
		putenv( "WONOLOG_HANDLER_FILE_DIR={$url}" );

		/** @var callable $listener */
		$listener = NULL;

		Actions::expectAdded( \Inpsyde\Wonolog\LOG )
			->once()
			->whenHappen(
				function ( callable $callback ) use ( &$listener ) {

					$listener = $callback;
				}
			);

		Actions::expectFired( \Inpsyde\Wonolog\LOG )
			->once()
			->whenHappen(
				function ( $log ) use ( &$listener ) {

					$listener( $log );
				}
			);

		/** @var callable $hook_listener */
		$hook_listener = NULL;

		Actions::expectAdded( 'my_awesome_hook' )
			->once()
			->whenHappen(
				function ( $callable ) use ( &$hook_listener ) {

					$hook_listener = $callable;
				}
			);

		Actions::expectFired( 'my_awesome_hook' )
			->once()
			->whenHappen(
				function () use ( &$hook_listener ) {

					$hook_listener();
				}
			);

		Actions::expectFired( HookListenersRegistry::ACTION_REGISTER )
			->once()
			->whenHappen(
				function ( HookListenersRegistry $registry ) {

					$listener = \Mockery::mock( ActionListenerInterface::class );
					$listener->shouldReceive( 'listen_to' )
						->once()
						->andReturn( 'my_awesome_hook' );
					$listener->shouldReceive( 'update' )
						->andReturn( new Info( 'Hi!', Channels::DEBUG ) );

					$registry->register_listener( $listener );
				}
			);

		$this->bootstrap_package();

		do_action( 'my_awesome_hook' );

		$path = date( 'Y/m-d' ) . '.log';

		self::assertTrue( $dir->hasChild( $path ) );

		/** @var vfsStreamFile $file */
		$file = $dir->getChild( $path );

		$this->check_logged_message(
			'Hi!',
			Channels::DEBUG,
			Logger::INFO,
			$file->getContent()
		);

	}
}
