<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * @package wonolog\tests
 */
class FunctionalTestCase extends \PHPUnit_Framework_TestCase {

	protected $callbacks = [];

	protected $did_actions = [];

	private $current_filter = NULL;

	protected function setUp() {

		$stubs_path = getenv( 'TESTS_PATH' ) . '/stubs';
		$stub_files = glob( "{$stubs_path}/*.php" );
		foreach ( $stub_files as $stub_file ) {
			/** @noinspection PhpIncludeInspection */
			require_once $stub_file;
		}

		parent::setUp();
		Monkey\setUp();
		$this->mock_hook_functions();
	}

	protected function tearDown() {

		$this->callbacks   = [];
		$this->did_actions = [];
		Monkey\tearDown();
		parent::tearDown();
	}

	private function mock_hook_functions() {

		Functions\when( 'add_action' )
			->alias(
				function ( $hook, callable $callback, $priority = 10 ) {

					$this->store_hook( $hook, $callback, $priority );
				}
			);

		Functions\when( 'add_filter' )
			->alias(
				function ( $hook, callable $callback, $priority = 10 ) {

					$this->store_hook( $hook, $callback, $priority );
				}
			);

		Functions\when( 'do_action' )
			->alias(
				function () {

					$args = func_get_args();
					$this->execute_hook( array_shift( $args ), $args, FALSE );
				}
			);

		Functions\when( 'apply_filters' )
			->alias(
				function () {

					$args = func_get_args();

					return $this->execute_hook( array_shift( $args ), $args, TRUE );
				}
			);

		Functions\when( 'did_action' )
			->alias(
				function ( $action ) {

					return in_array( $action, $this->did_actions, TRUE );
				}
			);

		Functions\when( 'current_filter' )
			->alias(
				function () {

					return $this->current_filter;
				}
			);

		Functions\expect( 'get_option' )
			->with( 'permalink_structure' )
			->andReturn( FALSE );

	}

	/**
	 * @param string   $hook
	 * @param callable $callable
	 * @param int      $priority
	 */
	private function store_hook( $hook, callable $callable, $priority ) {

		if ( ! isset( $this->callbacks[ $hook ][ $priority ] ) ) {
			$this->callbacks[ $hook ][ $priority ] = [];
		}

		$this->callbacks[ $hook ][ $priority ][] = $callable;
	}

	/**
	 * @param string $hook
	 * @param array  $args
	 * @param bool   $filter
	 *
	 * @return mixed|null
	 */
	private function execute_hook( $hook, array $args = [], $filter = FALSE ) {

		$filter or $this->did_actions[] = $hook;
		$this->current_filter = $hook;

		$callbacks = empty( $this->callbacks[ $hook ] ) ? [] : $this->callbacks[ $hook ];

		if ( ! $callbacks ) {
			$this->current_filter = NULL;

			return $filter && $args ? reset( $args ) : NULL;
		}

		ksort( $callbacks );

		array_walk(
			$callbacks,
			function ( array $callbacks ) use ( &$args, $filter, $hook ) {

				array_walk(
					$callbacks,
					function ( callable $callback ) use ( &$args, $filter ) {

						$value = call_user_func_array( $callback, $args );
						$filter and $args[ 0 ] = $value;
					}
				);
			}
		);

		$this->current_filter = NULL;

		return $filter && isset( $args[ 0 ] ) ? $args[ 0 ] : NULL;
	}
}
