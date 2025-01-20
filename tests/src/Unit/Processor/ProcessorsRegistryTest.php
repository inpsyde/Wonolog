<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Tests\Unit\Processor;

use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 */
class ProcessorsRegistryTest extends TestCase {

	public function test_same_processor_is_added_once() {

		$registry = new ProcessorsRegistry();

		$processor_a = 'strtolower';
		$processor_b = 'strtoupper';
		$processor_c = 'strrev';

		$registry->add_processor( $processor_a, 'test' );
		$registry->add_processor( $processor_b, 'test' );
		$registry->add_processor( $processor_c, 'test' );

		self::assertCount( 1, $registry );
	}

	public function test_has_processor() {

		$registry = new ProcessorsRegistry( );

		$processor_a = 'strtolower';
		$processor_b = 'strtoupper';
		$processor_c = 'strrev';

		$registry->add_processor( $processor_a, 'a' );
		$registry->add_processor( $processor_b, 'b' );
		$registry->add_processor( $processor_c, 'c' );

		self::assertTrue( $registry->has_processor( 'a' ) );
		self::assertTrue( $registry->has_processor( 'b' ) );
		self::assertTrue( $registry->has_processor( 'c' ) );
		self::assertFalse( $registry->has_processor( [ 'a' ] ) );
		self::assertFalse( $registry->has_processor( 'x' ) );

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

					$registry->add_processor( 'strtolower', 'a' );
					$registry->add_processor( 'strtoupper', 'b' );
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
