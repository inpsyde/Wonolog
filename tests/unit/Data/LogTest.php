<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

final class LogTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testBasicProperties(): void
    {
        $log = new Log('message', LogLevel::EMERGENCY, Channels::DEBUG, ['foo']);

        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame('message', $log->message());
        $this->assertSame(['foo'], $log->context());
        $this->assertSame(LogLevel::EMERGENCY, $log->level());
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

        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame('Error!', $log->message());
        $this->assertSame(['!'], $log->context());
        $this->assertSame(LogLevel::NOTICE, $log->level());
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

        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame('Error!', $log->message());
        $this->assertSame(['!'], $log->context());
        $this->assertSame(LogLevel::DEBUG, $log->level());
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

        $this->assertSame(Channels::DB, $log->channel());
        $this->assertSame('Error!', $log->message());
        $this->assertSame(['!'], $log->context());
        $this->assertSame(LogLevel::NOTICE, $log->level());
    }

    /**
     * @test
     */
    public function testFromThrowable(): void
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception);
        $this->assertInstanceOf(Log::class, $log);

        $context = $log->context();

        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame('Fail!, Fail!', $log->message());
        $this->assertSame(LogLevel::ERROR, $log->level());
        $this->assertArrayHasKey('throwable', $context);
        $this->assertIsArray($context['throwable']);
        $this->assertInstanceOf($context['throwable']['class'] ?? '', $exception);
        $this->assertSame(__FILE__, $context['throwable']['file'] ?? '');
        $this->assertArrayHasKey('line', $context['throwable']);
        $this->assertArrayHasKey('trace', $context['throwable']);
    }

    /**
     * @test
     */
    public function testFromThrowableWithExplicitLevel(): void
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception, LogLevel::DEBUG);
        $this->assertInstanceOf(Log::class, $log);

        $context = $log->context();

        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame('Fail!, Fail!', $log->message());
        $this->assertSame(LogLevel::DEBUG, $log->level());
        $this->assertArrayHasKey('throwable', $context);
        $this->assertIsArray($context['throwable']);
        $this->assertInstanceOf($context['throwable']['class'] ?? '', $exception);
        $this->assertSame(__FILE__, $context['throwable']['file'] ?? '');
        $this->assertArrayHasKey('line', $context['throwable']);
        $this->assertArrayHasKey('trace', $context['throwable']);
    }

    /**
     * @test
     */
    public function testFromThrowableWithExplicitLevelAndChannel(): void
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception, LogLevel::NOTICE, Channels::NETWORK);
        $this->assertInstanceOf(Log::class, $log);

        $context = $log->context();

        $this->assertSame(Channels::NETWORK, $log->channel());
        $this->assertSame('Fail!, Fail!', $log->message());
        $this->assertSame(LogLevel::NOTICE, $log->level());
        $this->assertArrayHasKey('throwable', $context);
        $this->assertIsArray($context['throwable']);
        $this->assertArrayHasKey('line', $context['throwable']);
        $this->assertArrayHasKey('trace', $context['throwable']);
        $this->assertInstanceOf($context['throwable']['class'] ?? '', $exception);
        $this->assertSame(__FILE__, $context['throwable']['file'] ?? '');
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

        $this->assertSame(Channels::NETWORK, $log->channel());
        $this->assertSame('message', $log->message());
        $this->assertSame(['foo'], $log->context());
        $this->assertSame(LogLevel::EMERGENCY, $log->level());
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

        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame('message', $log->message());
        $this->assertSame(['foo'], $log->context());
        $this->assertSame(LogLevel::DEBUG, $log->level());
    }
}
