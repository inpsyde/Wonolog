<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\Handler;

use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Handler\HandlersRegistry;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Handler\HandlerInterface;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class HandlersRegistryTest extends TestCase {

	public function test_same_handler_is_added_once() {

		/** @var ProcessorsRegistry $processors_registry */
		$processors_registry = \Mockery::mock( ProcessorsRegistry::class );
		$registry            = new HandlersRegistry( $processors_registry );

		/** @var HandlerInterface $handler_a */
		$handler_a = \Mockery::mock( HandlerInterface::class );
		$handler_b = clone $handler_a;
		$handler_c = clone $handler_a;

		$registry->add_handler( $handler_a, 'test' );
		$registry->add_handler( $handler_b, 'test' );
		$registry->add_handler( $handler_c, 'test' );

		self::assertCount( 1, $registry );
	}

	public function test_has_handler() {

		/** @var ProcessorsRegistry $processors_registry */
		$processors_registry = \Mockery::mock( ProcessorsRegistry::class );
		$registry            = new HandlersRegistry( $processors_registry );

		/** @var HandlerInterface $handler_a */
		$handler_a = \Mockery::mock( HandlerInterface::class );
		$handler_b = clone $handler_a;
		$handler_c = clone $handler_a;

		$registry->add_handler( $handler_a, 'a' );
		$registry->add_handler( $handler_b, 'b' );
		$registry->add_handler( $handler_c, 'c' );

		self::assertTrue( $registry->has_handler( 'a' ) );
		self::assertTrue( $registry->has_handler( 'b' ) );
		self::assertTrue( $registry->has_handler( 'c' ) );
		self::assertFalse( $registry->has_handler( [ 'a' ] ) );
		self::assertFalse( $registry->has_handler( 'x' ) );

		self::assertCount( 3, $registry );
	}

	public function test_find_by_name_call_register_once() {

		/** @var ProcessorsRegistry $processors_registry */
		$processors_registry = \Mockery::mock( ProcessorsRegistry::class );
		$registry            = new HandlersRegistry( $processors_registry );

		Actions\expectDone( HandlersRegistry::ACTION_REGISTER )
			->once()
			->with( $registry );

		self::assertCount( 0, $registry );
		self::assertNull( $registry->find( 'foo' ) );
		self::assertNull( $registry->find( 'bar' ) );
		self::assertNull( $registry->find( 'baz' ) );
		self::assertNull( $registry->find( 1 ) );
	}

	public function test_register_on_find_by_name() {

		/** @var ProcessorsRegistry $processors_registry */
		$processors_registry = \Mockery::mock( ProcessorsRegistry::class );
		$registry            = new HandlersRegistry( $processors_registry );

		/** @var HandlerInterface $handler_a */
		$handler_a = \Mockery::mock( HandlerInterface::class );
		$handler_b = clone $handler_a;

		Actions\expectDone( HandlersRegistry::ACTION_REGISTER )
			->once()
			->with( $registry )
			->whenHappen(
				function ( HandlersRegistry $registry ) use ( $handler_a, $handler_b ) {

					$registry->add_handler( $handler_a, 'a' );
					$registry->add_handler( $handler_b, 'b' );
				}
			);

		Actions\expectDone( HandlersRegistry::ACTION_SETUP )
			->once()
			->with( $handler_a, 'a', $processors_registry );

		Actions\expectDone( HandlersRegistry::ACTION_SETUP )
			->once()
			->with( $handler_b, 'b', $processors_registry );

		self::assertSame( $handler_a, $registry->find( 'a' ) );
		self::assertSame( $handler_b, $registry->find( 'b' ) );
		self::assertSame( $handler_a, $registry->find( 'a' ) );
		self::assertSame( $handler_b, $registry->find( 'b' ) );
		self::assertSame( $handler_a, $registry->find( 'a' ) );
		self::assertSame( $handler_b, $registry->find( 'b' ) );
		self::assertNull( $registry->find( 'bar' ) );
		self::assertNull( $registry->find( 'baz' ) );
		self::assertCount( 2, $registry );
	}
}
