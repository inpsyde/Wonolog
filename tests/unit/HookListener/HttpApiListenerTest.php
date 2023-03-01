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

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Inpsyde\Wonolog\HookListener\HttpApiListener;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Monolog\Logger;

class HttpApiListenerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testLogDoneOnWpError(): void
    {
        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->with(\Mockery::type(LogData::class))
            ->andReturnUsing(
                static function (LogData $log): void {
                    static::assertSame('WP HTTP API Error: Test!', $log->message());
                    static::assertSame(Channels::HTTP, $log->channel());
                    static::assertSame(Logger::ERROR, $log->level());
                    static::assertSame(
                        [
                            'transport' => 'TestClass',
                            'context' => 'response',
                            'query_args' => [],
                            'url' => 'https://example.com',
                        ],
                        $log->context()
                    );
                }
            );

        /** @var \WP_Error|\Mockery\MockInterface $response */
        $response = \Mockery::mock('WP_Error');
        $response->expects('get_error_message')->andReturn('Test!');

        $listener = new HttpApiListener();

        Actions\expectDone('http_api_debug')
            ->whenHappen(
            // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                static function (...$args) use ($listener, $updater): void {
                    $listener->update('a', $args, $updater);
                }
            );

        do_action(
            $listener->listenTo()[0],
            $response,
            'response',
            'TestClass',
            [],
            'https://example.com'
        );
    }

    /**
     * @test
     */
    public function testLogDoneOnBadResponse(): void
    {
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('shortcode_atts')->alias('array_merge');

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->with(\Mockery::type(LogData::class))
            ->andReturnUsing(
                static function (LogData $log): void {
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
                            'url' => 'https://example.com',
                            'response_body' => 'Server died.',
                            'headers' => ['foo' => 'bar'],
                        ],
                        $log->context()
                    );
                }
            );

        $listener = new HttpApiListener();

        Actions\expectDone('http_api_debug')
            ->whenHappen(
                static function () use ($listener, $updater): void {
                    $listener->update('a', func_get_args(), $updater);
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
            'https://example.com'
        );
    }

    /**
     * @test
     */
    public function testLogNotDoneOnGoodResponse(): void
    {
        Functions\when('is_wp_error')->justReturn(false);

        $listener = new HttpApiListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->never();

        Actions\expectDone('http_api_debug')
            ->whenHappen(
                // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                static function (...$args) use ($listener, $updater): void {
                    // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
                    $listener->update('a', $args, $updater);
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
            'https://example.com'
        );
    }

    /**
     * @test
     */
    public function testLogCron(): void
    {
        Functions\when('is_wp_error')
            ->justReturn(false);

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->with(\Mockery::type(LogData::class))
            ->andReturnUsing(
                static function (LogData $log) {

                    static::assertSame('Cron request', $log->message());
                    static::assertSame(Channels::DEBUG, $log->channel());
                    static::assertSame(Logger::DEBUG, $log->level());
                    static::assertSame(
                        [
                            'transport' => 'TestClass',
                            'context' => 'response',
                            'query_args' => [],
                            'url' => 'https://example.com/wp-cron.php',
                            'headers' => ['foo' => 'bar'],
                        ],
                        $log->context()
                    );
                }
            );

        /** @var \WP_Error|\Mockery\MockInterface $response */
        $response = [
            'response' => ['code' => 200, 'message' => 'Ok'],
            'headers' => ['foo' => 'bar'],
        ];

        $listener = new HttpApiListener();

        Actions\expectDone('http_api_debug')
            ->whenHappen(
                static function () use ($listener, $updater): void {
                    $listener->update('a', func_get_args(), $updater);
                }
            );

        do_action(
            $listener->listenTo()[0],
            $response,
            'response',
            'TestClass',
            [],
            'https://example.com/wp-cron.php'
        );
    }
}
