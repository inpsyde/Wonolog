<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\Processor;

use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class ProcessorsRegistryTest extends TestCase {

	public function test_same_processor_is_added_once() {

		$registry = new ProcessorsRegistry();

		$processor_a = 'strtolower';
		$processor_b = 'strtoupper';
		$processor_c = 'strrev';

		$registry->addProcessor( $processor_a, 'test' );
		$registry->addProcessor( $processor_b, 'test' );
		$registry->addProcessor( $processor_c, 'test' );

		self::assertCount( 1, $registry );
	}

	public function test_has_processor() {

		$registry = new ProcessorsRegistry( );

		$processor_a = 'strtolower';
		$processor_b = 'strtoupper';
		$processor_c = 'strrev';

		$registry->addProcessor( $processor_a, 'a' );
		$registry->addProcessor( $processor_b, 'b' );
		$registry->addProcessor( $processor_c, 'c' );

		self::assertTrue( $registry->hasProcessor( 'a' ) );
		self::assertTrue( $registry->hasProcessor( 'b' ) );
		self::assertTrue( $registry->hasProcessor( 'c' ) );
		self::assertFalse( $registry->hasProcessor( [ 'a' ] ) );
		self::assertFalse( $registry->hasProcessor( 'x' ) );

		self::assertCount( 3, $registry );
	}

	public function test_find_by_name_call_register_once() {

		$registry = new ProcessorsRegistry();

		Actions\expectDone( ProcessorsRegistry::ACTION_REGISTER )
			->once()
			->with( $registry );

		self::assertCount( 0, $registry );
		self::assertNull( $registry->find( 'foo' ) );
		self::assertNull( $registry->find( 'bar' ) );
		self::assertNull( $registry->find( 'baz' ) );
		self::assertNull( $registry->find( 1 ) );
	}

	public function test_register_on_find_by_name() {

		$registry = new ProcessorsRegistry();

		Actions\expectDone( ProcessorsRegistry::ACTION_REGISTER )
			->once()
			->with( $registry )
			->whenHappen(
				function ( ProcessorsRegistry $registry ) {

					$registry->addProcessor( 'strtolower', 'a' );
					$registry->addProcessor( 'strtoupper', 'b' );
				}
			);

		self::assertSame( 'strtolower', $registry->find( 'a' ) );
		self::assertSame( 'strtoupper', $registry->find( 'b' ) );
		self::assertSame( 'strtolower', $registry->find( 'a' ) );
		self::assertSame( 'strtoupper', $registry->find( 'b' ) );
		self::assertNull( $registry->find( 'bar' ) );
		self::assertNull( $registry->find( 'baz' ) );
		self::assertCount( 2, $registry );
	}
}
