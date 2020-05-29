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
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpContextProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('get_option')->justReturn();
    }

    public function testAdminBeforeInitSingleSite()
    {
        Functions\when('is_admin')->justReturn(true);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com');

        $processor = new WpContextProcessor();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => true,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testFrontendBeforeInitSingleSite()
    {
        Functions\when('is_admin')->justReturn(false);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com');

        $processor = new WpContextProcessor();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => false,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testAdminAfterInitSingleSite()
    {
        do_action('init');

        Functions\when('is_admin')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com');

        $processor = new WpContextProcessor();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => true,
                    'user_id' => 1,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testRestAfterInitSingleSite()
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

        $processor = new WpContextProcessor();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => false,
                    'user_id' => 1,
                    'doing_rest' => true,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testFrontendAfterParseRequestSingleSite()
    {
        do_action('init');
        do_action('parse_request');

        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com/foo');

        $processor = new WpContextProcessor();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => false,
                    'user_id' => 1,
                    'doing_rest' => false,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testFrontendAfterParseRequestMultiSite()
    {
        do_action('init');
        do_action('parse_request');
        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('is_multisite')->justReturn(true);
        Functions\when('set_url_scheme')->returnArg();
        Functions\when('get_rest_url')->justReturn('https://example.com/wp-json');
        Functions\when('add_query_arg')->justReturn('https://example.com/foo');
        Functions\when('ms_is_switched')->justReturn(true);
        Functions\when('get_current_blog_id')->justReturn(2);
        Functions\when('get_current_network_id')->justReturn(3);

        $processor = new WpContextProcessor();

        $actual = $processor([]);

        $expected = [
            'extra' => [
                'wp' => [
                    'doing_cron' => false,
                    'doing_ajax' => false,
                    'is_admin' => false,
                    'user_id' => 1,
                    'doing_rest' => false,
                    'ms_switched' => true,
                    'site_id' => 2,
                    'network_id' => 3,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }
}
