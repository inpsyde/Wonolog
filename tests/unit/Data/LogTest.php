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

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\Levels;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Logger;

class LogTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testBasicProperties(): void
    {
        $log = new Log('message', Levels::EMERGENCY, Channels::DEBUG, ['foo']);

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('message', $log->message());
        static::assertSame(['foo'], $log->context());
        static::assertSame(Levels::EMERGENCY, $log->level());
    }

    /**
     * @test
     */
    public function testFromWpError(): void
    {
        $error = \Mockery::mock(\WP_Error::class);

        $error->allows('get_error_message')->andReturn('Error!');
        $error->allows('get_error_data')->andReturn(['!']);
        $error->allows('get_error_codes')->andReturn(['x']);

        $log = Log::fromWpError($error);

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('Error!', $log->message());
        static::assertSame(['!'], $log->context());
        static::assertSame(Levels::NOTICE, $log->level());
    }

    /**
     * @test
     */
    public function testFromWpErrorWithExplicitLevel(): void
    {
        $error = \Mockery::mock(\WP_Error::class);

        $error->allows('get_error_message')->andReturn('Error!');
        $error->allows('get_error_data')->andReturn(['!']);
        $error->allows('get_error_codes')->andReturn(['x']);

        $log = Log::fromWpError($error, Levels::DEBUG);

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('Error!', $log->message());
        static::assertSame(['!'], $log->context());
        static::assertSame(Levels::DEBUG, $log->level());
    }

    /**
     * @test
     */
    public function testFromWpErrorWithExplicitLevelAndChannel(): void
    {
        $error = \Mockery::mock(\WP_Error::class);

        $error->allows('get_error_message')->andReturn('Error!');
        $error->allows('get_error_data')->andReturn(['!']);
        $error->allows('get_error_codes')->andReturn(['x']);

        $log = Log::fromWpError($error, Levels::DEBUG, Channels::DB);

        static::assertSame(Channels::DB, $log->channel());
        static::assertSame('Error!', $log->message());
        static::assertSame(['!'], $log->context());
        static::assertSame(Levels::NOTICE, $log->level());
    }

    /**
     * @test
     */
    public function testFromThrowable(): void
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception);
        static::assertInstanceOf(Log::class, $log);

        $context = $log->context();
        static::assertIsArray($context);

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('Fail!, Fail!', $log->message());
        static::assertSame(Levels::ERROR, $log->level());
        static::assertArrayHasKey('throwable', $context);
        static::assertSame($context['throwable']['class'], \Exception::class);
        static::assertSame($context['throwable']['file'], __FILE__);
        static::assertArrayHasKey('line', $context['throwable']);
        static::assertArrayHasKey('trace', $context['throwable']);
    }

    /**
     * @test
     */
    public function testFromThrowableWithExplicitLevel(): void
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception, Levels::DEBUG);
        static::assertInstanceOf(Log::class, $log);

        $context = $log->context();
        static::assertIsArray($context);

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('Fail!, Fail!', $log->message());
        static::assertSame(Levels::DEBUG, $log->level());
        static::assertArrayHasKey('throwable', $context);
        static::assertSame($context['throwable']['class'], \Exception::class);
        static::assertSame($context['throwable']['file'], __FILE__);
        static::assertArrayHasKey('line', $context['throwable']);
        static::assertArrayHasKey('trace', $context['throwable']);
    }

    /**
     * @test
     */
    public function testFromThrowableWithExplicitLevelAndChannel(): void
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception, Levels::NOTICE, Channels::HTTP);
        static::assertInstanceOf(Log::class, $log);

        $context = $log->context();
        static::assertIsArray($context);

        static::assertSame(Channels::HTTP, $log->channel());
        static::assertSame('Fail!, Fail!', $log->message());
        static::assertSame(Levels::NOTICE, $log->level());
        static::assertArrayHasKey('throwable', $context);
        static::assertSame($context['throwable']['class'], \Exception::class);
        static::assertSame($context['throwable']['file'], __FILE__);
        static::assertArrayHasKey('line', $context['throwable']);
        static::assertArrayHasKey('trace', $context['throwable']);
    }

    /**
     * @test
     */
    public function testFromArray(): void
    {
        $log = Log::fromArray(
            [
                LogData::MESSAGE => 'message',
                LogData::LEVEL => Levels::EMERGENCY,
                LogData::CHANNEL => Channels::HTTP,
                LogData::CONTEXT => ['foo'],
            ]
        );

        static::assertSame(Channels::HTTP, $log->channel());
        static::assertSame('message', $log->message());
        static::assertSame(['foo'], $log->context());
        static::assertSame(Levels::EMERGENCY, $log->level());
    }

    /**
     * @test
     */
    public function testFromArrayMerged(): void
    {
        $log = Log::fromArray(
            [
                LogData::MESSAGE => 'message',
                LogData::CONTEXT => ['foo'],
            ]
        );

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('message', $log->message());
        static::assertSame(['foo'], $log->context());
        static::assertSame(Levels::DEBUG, $log->level());
    }
}
