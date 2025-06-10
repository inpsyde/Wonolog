<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class LogTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testBasicProperties(): void
    {
        $log = new Log('message', LogLevel::EMERGENCY, Channels::DEBUG, ['foo']);

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('message', $log->message());
        static::assertSame(['foo'], $log->context());
        static::assertSame(LogLevel::EMERGENCY, $log->level());
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
        static::assertSame(LogLevel::NOTICE, $log->level());
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

        $log = Log::fromWpError($error, LogLevel::DEBUG);

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('Error!', $log->message());
        static::assertSame(['!'], $log->context());
        static::assertSame(LogLevel::DEBUG, $log->level());
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

        $log = Log::fromWpError($error, LogLevel::DEBUG, Channels::DB);

        static::assertSame(Channels::DB, $log->channel());
        static::assertSame('Error!', $log->message());
        static::assertSame(['!'], $log->context());
        static::assertSame(LogLevel::NOTICE, $log->level());
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

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('Fail!, Fail!', $log->message());
        static::assertSame(LogLevel::ERROR, $log->level());
        static::assertArrayHasKey('throwable', $context);
        static::assertIsArray($context['throwable']);
        static::assertSame($context['throwable']['class'] ?? '', get_class($exception));
        static::assertSame($context['throwable']['file'] ?? '', __FILE__);
        static::assertArrayHasKey('line', $context['throwable']);
        static::assertArrayHasKey('trace', $context['throwable']);
    }

    /**
     * @test
     */
    public function testFromThrowableWithExplicitLevel(): void
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception, LogLevel::DEBUG);
        static::assertInstanceOf(Log::class, $log);

        $context = $log->context();

        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('Fail!, Fail!', $log->message());
        static::assertSame(LogLevel::DEBUG, $log->level());
        static::assertArrayHasKey('throwable', $context);
        static::assertIsArray($context['throwable']);
        static::assertSame($context['throwable']['class'] ?? '', get_class($exception));
        static::assertSame($context['throwable']['file'] ?? '', __FILE__);
        static::assertArrayHasKey('line', $context['throwable']);
        static::assertArrayHasKey('trace', $context['throwable']);
    }

    /**
     * @test
     */
    public function testFromThrowableWithExplicitLevelAndChannel(): void
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception, LogLevel::NOTICE, Channels::NETWORK);
        static::assertInstanceOf(Log::class, $log);

        $context = $log->context();

        static::assertSame(Channels::NETWORK, $log->channel());
        static::assertSame('Fail!, Fail!', $log->message());
        static::assertSame(LogLevel::NOTICE, $log->level());
        static::assertArrayHasKey('throwable', $context);
        static::assertIsArray($context['throwable']);
        static::assertArrayHasKey('line', $context['throwable']);
        static::assertArrayHasKey('trace', $context['throwable']);
        static::assertSame($context['throwable']['class'] ?? '', get_class($exception));
        static::assertSame($context['throwable']['file'] ?? '', __FILE__);
    }

    /**
     * @test
     */
    public function testFromArray(): void
    {
        $log = Log::fromArray(
            [
                LogData::MESSAGE => 'message',
                LogData::LEVEL => LogLevel::EMERGENCY,
                LogData::CHANNEL => Channels::NETWORK,
                LogData::CONTEXT => ['foo'],
            ]
        );

        static::assertSame(Channels::NETWORK, $log->channel());
        static::assertSame('message', $log->message());
        static::assertSame(['foo'], $log->context());
        static::assertSame(LogLevel::EMERGENCY, $log->level());
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
        static::assertSame(LogLevel::DEBUG, $log->level());
    }
}
