<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\HookListener\HttpApiListener;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class HttpApiListenerTest extends TestCase
{
    public function testLogDoneOnWpError()
    {
        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->with(\Mockery::type(LogDataInterface::class))
            ->whenHappen(
                static function (LogDataInterface $log) {
                    static::assertSame('WP HTTP API Error: Test!', $log->message());
                    static::assertSame(Channels::HTTP, $log->channel());
                    static::assertSame(Logger::ERROR, $log->level());
                    static::assertSame(
                        [
                            'transport' => 'TestClass',
                            'context' => 'response',
                            'query_args' => [],
                            'url' => 'http://example.com',
                        ],
                        $log->context()
                    );
                }
            );

        /** @var \WP_Error|\Mockery\MockInterface $response */
        $response = \Mockery::mock('WP_Error');
        $response
            ->shouldReceive('get_error_message')
            ->once()
            ->andReturn('Test!');

        $listener = new HttpApiListener();

        Actions\expectDone('http_api_debug')
            ->whenHappen(
                // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                static function (...$args) use ($listener): void {
                    // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                    do_action(\Inpsyde\Wonolog\LOG, $listener->update($args));
                }
            );

        do_action(
            $listener->listenTo()[0],
            $response,
            'response',
            'TestClass',
            [],
            'http://example.com'
        );
    }

    public function testLogDoneOnBadResponse()
    {
        Functions\when('is_wp_error')
            ->justReturn(false);

        Functions\when('shortcode_atts')
            ->alias('array_merge');

        $tester = static function (LogDataInterface $log) {

            static::assertSame(Channels::HTTP, $log->channel());
            static::assertSame(Logger::ERROR, $log->level());
            static::assertSame(
                'WP HTTP API Error: Internal Server Error - Response code: 500',
                $log->message()
            );
            static::assertSame(
                [
                    'transport' => 'TestClass',
                    'context' => 'response',
                    'query_args' => [],
                    'url' => 'http://example.com',
                    'response_body' => 'Server died.',
                    'headers' => ['foo' => 'bar'],
                ],
                $log->context()
            );
        };

        $listener = new HttpApiListener();

        Actions\expectDone('http_api_debug')
            ->whenHappen(
                static function () use ($listener, $tester) {

                    $tester($listener->update(func_get_args()));
                }
            );

        $response = [
            'response' => [
                'code' => 500,
                'message' => 'Internal Server Error',
                'body' => 'Server died.',
            ],
            'headers' => ['foo' => 'bar'],
        ];

        do_action(
            $listener->listenTo()[0],
            $response,
            'response',
            'TestClass',
            [],
            'http://example.com'
        );
    }

    public function testLogNotDoneOnGoodResponse()
    {
        Functions\when('is_wp_error')
            ->justReturn(false);

        $listener = new HttpApiListener();

        Actions\expectDone('http_api_debug')
            ->whenHappen(
                // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                static function (...$args) use ($listener): void {
                    // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                    $log = $listener->update($args);
                    static::assertInstanceOf(NullLog::class, $log);
                }
            );

        $response = [
            'response' => ['code' => 200, 'message' => 'OK'],
            'headers' => ['foo' => 'bar'],
        ];

        do_action(
            $listener->listenTo()[0],
            $response,
            'response',
            'TestClass',
            [],
            'http://example.com'
        );
    }

    public function testLogCron()
    {
        Functions\when('is_wp_error')
            ->justReturn(false);

        $tester = static function (LogDataInterface $log) {

            static::assertSame('Cron request', $log->message());
            static::assertSame(Channels::DEBUG, $log->channel());
            static::assertSame(Logger::DEBUG, $log->level());
            static::assertSame(
                [
                    'transport' => 'TestClass',
                    'context' => 'response',
                    'query_args' => [],
                    'url' => 'http://example.com/wp-cron.php',
                    'headers' => ['foo' => 'bar'],
                ],
                $log->context()
            );
        };

        /** @var \WP_Error|\Mockery\MockInterface $response */
        $response = [
            'response' => ['code' => 200, 'message' => 'Ok'],
            'headers' => ['foo' => 'bar'],
        ];

        $listener = new HttpApiListener();

        Actions\expectDone('http_api_debug')
            ->whenHappen(
                static function () use ($listener, $tester) {

                    $tester($listener->update(func_get_args()));
                }
            );

        do_action(
            $listener->listenTo()[0],
            $response,
            'response',
            'TestClass',
            [],
            'http://example.com/wp-cron.php'
        );
    }
}
