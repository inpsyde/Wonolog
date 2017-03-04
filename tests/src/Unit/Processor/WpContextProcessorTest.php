<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\Processor;

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Processor\WpContextProcessor;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpContextProcessorTest extends TestCase {

	protected function setUp() {

		parent::setUp();
		Functions::when( 'get_option' )
			->justReturn();
	}

	public function test_admin_before_init_single_site() {

		Functions::when( 'is_admin' )
			->justReturn( TRUE );

		Functions::when( 'is_multisite' )
			->justReturn( FALSE );

		Functions::when( 'set_url_scheme' )
			->returnArg();

		Functions::when( 'get_rest_url' )
			->justReturn( 'https://example.com/wp-json' );

		Functions::when( 'add_query_arg' )
			->justReturn( 'https://example.com' );

		$processor = new WpContextProcessor();

		$actual = $processor( [] );

		$expected = [
			'extra' => [
				'wp' => [
					'doing_cron' => FALSE,
					'doing_ajax' => FALSE,
					'is_admin'   => TRUE,
				]
			]
		];

		$this->assertEquals( $expected, $actual );
	}

	public function test_frontend_before_init_single_site() {

		Functions::when( 'is_admin' )
			->justReturn( FALSE );

		Functions::when( 'is_multisite' )
			->justReturn( FALSE );

		Functions::when( 'set_url_scheme' )
			->returnArg();

		Functions::when( 'get_rest_url' )
			->justReturn( 'https://example.com/wp-json' );

		Functions::when( 'add_query_arg' )
			->justReturn( 'https://example.com' );

		$processor = new WpContextProcessor();

		$actual = $processor( [] );

		$expected = [
			'extra' => [
				'wp' => [
					'doing_cron' => FALSE,
					'doing_ajax' => FALSE,
					'is_admin'   => FALSE,
				]
			]
		];

		$this->assertEquals( $expected, $actual );
	}

	public function test_admin_after_init_single_site() {

		do_action( 'init' );

		Functions::when( 'is_admin' )
			->justReturn( TRUE );

		Functions::when( 'get_current_user_id' )
			->justReturn( 1 );

		Functions::when( 'is_multisite' )
			->justReturn( FALSE );

		Functions::when( 'set_url_scheme' )
			->returnArg();

		Functions::when( 'get_rest_url' )
			->justReturn( 'https://example.com/wp-json' );

		Functions::when( 'add_query_arg' )
			->justReturn( 'https://example.com' );

		$processor = new WpContextProcessor();

		$actual = $processor( [] );

		$expected = [
			'extra' => [
				'wp' => [
					'doing_cron' => FALSE,
					'doing_ajax' => FALSE,
					'is_admin'   => TRUE,
					'user_id'    => 1,
				]
			]
		];

		$this->assertEquals( $expected, $actual );
	}

	public function test_rest_after_init_single_site() {

		do_action( 'init' );

		Functions::when( 'is_admin' )
			->justReturn( FALSE );

		Functions::when( 'get_current_user_id' )
			->justReturn( 1 );

		Functions::when( 'is_multisite' )
			->justReturn( FALSE );

		Functions::when( 'set_url_scheme' )
			->alias(
				function ( $str ) {

					return str_replace( 'http://', 'https://', $str );
				}
			);

		Functions::when( 'get_rest_url' )
			->justReturn( 'https://example.com/wp-json' );

		Functions::when( 'add_query_arg' )
			->justReturn( 'http://example.com/wp-json/foo/bar' );

		$processor = new WpContextProcessor();

		$actual = $processor( [] );

		$expected = [
			'extra' => [
				'wp' => [
					'doing_cron' => FALSE,
					'doing_ajax' => FALSE,
					'is_admin'   => FALSE,
					'user_id'    => 1,
					'doing_rest' => TRUE
				]
			]
		];

		$this->assertEquals( $expected, $actual );
	}

	public function test_frontend_after_parse_request_single_site() {

		do_action( 'init' );
		do_action( 'parse_request' );

		Functions::when( 'is_admin' )
			->justReturn( FALSE );

		Functions::when( 'get_current_user_id' )
			->justReturn( 1 );

		Functions::when( 'is_multisite' )
			->justReturn( FALSE );

		Functions::when( 'set_url_scheme' )
			->returnArg();

		Functions::when( 'get_rest_url' )
			->justReturn( 'https://example.com/wp-json' );

		Functions::when( 'add_query_arg' )
			->justReturn( 'https://example.com/foo' );

		$processor = new WpContextProcessor();

		$actual = $processor( [] );

		$expected = [
			'extra' => [
				'wp' => [
					'doing_cron' => FALSE,
					'doing_ajax' => FALSE,
					'is_admin'   => FALSE,
					'user_id'    => 1,
					'doing_rest' => FALSE
				]
			]
		];

		$this->assertEquals( $expected, $actual );
	}

	public function test_frontend_after_parse_request_multi_site() {

		do_action( 'init' );
		do_action( 'parse_request' );

		Functions::when( 'is_admin' )
			->justReturn( FALSE );

		Functions::when( 'get_current_user_id' )
			->justReturn( 1 );

		Functions::when( 'is_multisite' )
			->justReturn( TRUE );

		Functions::when( 'set_url_scheme' )
			->returnArg();

		Functions::when( 'get_rest_url' )
			->justReturn( 'https://example.com/wp-json' );

		Functions::when( 'add_query_arg' )
			->justReturn( 'https://example.com/foo' );

		Functions::when( 'ms_is_switched' )
			->justReturn( TRUE );

		Functions::when( 'get_current_blog_id' )
			->justReturn( 2 );

		Functions::when( 'get_current_network_id' )
			->justReturn( 3 );

		$processor = new WpContextProcessor();

		$actual = $processor( [] );

		$expected = [
			'extra' => [
				'wp' => [
					'doing_cron'  => FALSE,
					'doing_ajax'  => FALSE,
					'is_admin'    => FALSE,
					'user_id'     => 1,
					'doing_rest'  => FALSE,
					'ms_switched' => TRUE,
					'site_id'     => 2,
					'network_id'  => 3,
				]
			]
		];

		$this->assertEquals( $expected, $actual );
	}

}