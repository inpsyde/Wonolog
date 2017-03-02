<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey\WP\Actions;
use Brain\Monkey\WP\Filters;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Controller;
use Inpsyde\Wonolog\Handler\HandlersRegistry;
use Inpsyde\Wonolog\HookListener\HookListenerInterface;
use Inpsyde\Wonolog\HookListener\HookListenersRegistry;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Handler\HandlerInterface;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class ControllerTest extends TestCase {

	protected function tearDown() {

		putenv( "WONOLOG_DISABLE" );
		parent::tearDown();
	}

	public function test_setup_disabled_via_env() {

		putenv( "WONOLOG_DISABLE=1" );
		Actions::expectFired( Controller::ACTION_SETUP )
			->never();

		$controller = new Controller();
		$controller->setup();
	}

	public function test_setup_do_nothing_if_did() {

		do_action( Controller::ACTION_SETUP );

		putenv( "WONOLOG_DISABLE=1" );
		Actions::expectFired( Controller::ACTION_SETUP )
			->never();

		$controller = new Controller();
		$controller->setup();
	}

	public function test_setup_add_hooks_once() {

		Actions::expectFired( Controller::ACTION_SETUP )
			->once();

		Actions::expectAdded( \Inpsyde\Wonolog\LOG )
			->once()
			->with( \Mockery::type( 'callable' ), 123, PHP_INT_MAX );

		$levels = array(
			'.debug'     => 100,
			'.info'      => 200,
			'.notice'    => 250,
			'.warning'   => 300,
			'.error'     => 400,
			'.critical'  => 500,
			'.alert'     => 550,
			'.emergency' => 600,
		);

		foreach ( $levels as $level => $severity ) {
			Actions::expectAdded( \Inpsyde\Wonolog\LOG . $level )
				->once()
				->with( \Mockery::type( 'callable' ), 123 + ( 601 - $severity ), PHP_INT_MAX );
		}

		Actions::expectAdded( 'muplugins_loaded' )
			->once()
			->with( [ HookListenersRegistry::class, 'initialize' ], PHP_INT_MAX );

		Actions::expectFired( Controller::ACTION_LOADED )
			->once();

		$controller = new Controller();
		$controller->setup( 123 );
		$controller->setup();
		$controller->setup();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_log_php_errors_run_once() {

		Filters::expectAdded( Channels::FILTER_CHANNELS )
			->once();

		$controller = new Controller();

		self::assertSame( $controller, $controller->log_php_errors() );
		self::assertSame( $controller, $controller->log_php_errors() );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_use_default_handler_add_hook_once() {

		Actions::expectAdded( HandlersRegistry::ACTION_REGISTER )
			->once();

		$controller = new Controller();
		self::assertSame( $controller, $controller->use_default_handler() );
		self::assertSame( $controller, $controller->use_default_handler() );
	}

	public function test_use_handler_add_hooks() {

		Actions::expectAdded( HandlersRegistry::ACTION_REGISTER )
			->twice();

		Actions::expectAdded( Channels::ACTION_LOGGER )
			->twice();

		$controller = new Controller();
		/** @var HandlerInterface $handler */
		$handler = \Mockery::mock( HandlerInterface::class );

		self::assertSame( $controller, $controller->use_handler( $handler ) );
		self::assertSame( $controller, $controller->use_handler( $handler ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_use_default_processor_add_hook_once() {

		Actions::expectAdded( ProcessorsRegistry::ACTION_REGISTER )
			->once();

		$controller = new Controller();
		self::assertSame( $controller, $controller->use_default_processor() );
		self::assertSame( $controller, $controller->use_default_processor() );
	}

	public function test_use_processor_no_channels_add_hook() {

		Actions::expectAdded( ProcessorsRegistry::ACTION_REGISTER )
			->twice();

		Actions::expectAdded( Channels::ACTION_LOGGER )
			->never();

		$controller = new Controller();
		self::assertSame( $controller, $controller->use_processor( 'strtolower' ) );
		self::assertSame( $controller, $controller->use_processor( 'strtoupper' ) );
	}

	public function test_use_processor_with_channels_add_hooks() {

		Actions::expectAdded( ProcessorsRegistry::ACTION_REGISTER )
			->twice();

		Actions::expectAdded( Channels::ACTION_LOGGER )
			->twice();

		$controller = new Controller();
		self::assertSame( $controller, $controller->use_processor( 'strtolower', [ 'foo' ], 'foo' ) );
		self::assertSame( $controller, $controller->use_processor( 'strtoupper', [ 'foo' ], 'bar' ) );
	}

	public function test_use_processor_for_handlers_add_hooks_if_handlers() {

		Actions::expectAdded( ProcessorsRegistry::ACTION_REGISTER )
			->twice();

		Actions::expectAdded( HandlersRegistry::ACTION_SETUP )
			->twice();

		$controller = new Controller();
		self::assertSame( $controller, $controller->use_processor_for_handlers( 'strtolower', [ 'foo' ], 'foo' ) );
		self::assertSame( $controller, $controller->use_processor_for_handlers( 'strtoupper', [ 'foo' ], 'bar' ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_use_default_hook_listeners_add_hook_once() {

		Actions::expectAdded( HookListenersRegistry::ACTION_REGISTER )
			->once();

		$controller = new Controller();
		self::assertSame( $controller, $controller->use_default_hook_listeners() );
		self::assertSame( $controller, $controller->use_default_hook_listeners() );

	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_use_hook_listener_add_hook() {

		Actions::expectAdded( HookListenersRegistry::ACTION_REGISTER )
			->twice();

		$controller = new Controller();
		/** @var HookListenerInterface $listener */
		$listener = \Mockery::mock( HookListenerInterface::class );

		self::assertSame( $controller, $controller->use_hook_listener( $listener ) );
		self::assertSame( $controller, $controller->use_hook_listener( $listener ) );
	}
}
