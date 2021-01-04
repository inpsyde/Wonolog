<?php

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Processor;

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Processor\WpContextProcessor;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class WpContextProcessorTest extends UnitTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('get_option')->justReturn();
    }

    /**
     * @test
     */
    public function testAdminBeforeInitSingleSite():void
    {
        Functions\when('is_admin')->justReturn(true);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com');

        $processor = WpContextProcessor::new();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => true,
                    'doing_rest' => false,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function testFrontendBeforeInitSingleSite(): void
    {
        Functions\when('is_admin')->justReturn(false);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com');

        $processor = WpContextProcessor::new();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => false,
                    'doing_rest' => false,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function testAdminAfterInitSingleSite(): void
    {
        do_action('init');

        Functions\when('is_admin')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com');

        $processor = WpContextProcessor::new();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => true,
                    'doing_rest' => false,
                    'user_id' => 1,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function testRestAfterInitSingleSite(): void
    {
        do_action('init');

        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('http://example.com/wp-json/foo/bar');
        Functions\when('set_url_scheme')
            ->alias(
                static function (string $str): string {
                    return str_replace('http://', 'https://', $str);
                }
            );

        $processor = WpContextProcessor::new();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => false,
                    'doing_rest' => true,
                    'user_id' => 1,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function testFrontendAfterParseRequestSingleSite(): void
    {
        do_action('init');
        do_action('parse_request');

        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com/foo');

        $processor = WpContextProcessor::new();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => false,
                    'doing_rest' => false,
                    'user_id' => 1,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testFrontendAfterParseRequestMultiSite(): void
    {
        do_action('init');
        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(true);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com/foo');
        Functions\when('ms_is_switched')->justReturn(true);
        Functions\when('get_current_blog_id')->justReturn(2);
        Functions\when('get_current_network_id')->justReturn(3);

        $processor = WpContextProcessor::new();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => false,
                    'doing_rest' => false,
                    'user_id' => 1,
                    'ms_switched' => true,
                    'site_id' => 2,
                    'network_id' => 3,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }
}
