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
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogTest extends TestCase
{
    public function testBasicProperties()
    {
        $log = new Log('message', Logger::EMERGENCY, Channels::DEBUG, ['foo']);

        self::assertSame(Channels::DEBUG, $log->channel());
        self::assertSame('message', $log->message());
        self::assertSame(['foo'], $log->context());
        self::assertSame(Logger::EMERGENCY, $log->level());
    }

    public function testFromWpError()
    {
        $error = \Mockery::mock(\WP_Error::class);

        $error->shouldReceive('get_error_message')->andReturn('Error!');
        $error->shouldReceive('get_error_data')->andReturn(['!']);
        $error->shouldReceive('get_error_codes')->andReturn(['x']);

        $log = Log::fromWpError($error);

        self::assertSame(Channels::DEBUG, $log->channel());
        self::assertSame('Error!', $log->message());
        self::assertSame(['!'], $log->context());
        self::assertSame(Logger::NOTICE, $log->level());
    }

    public function testFromWpErrorWithExplicitLevel()
    {
        $error = \Mockery::mock(\WP_Error::class);

        $error->shouldReceive('get_error_message')->andReturn('Error!');
        $error->shouldReceive('get_error_data')->andReturn(['!']);
        $error->shouldReceive('get_error_codes')->andReturn(['x']);

        $log = Log::fromWpError($error, Logger::DEBUG);

        self::assertSame(Channels::DEBUG, $log->channel());
        self::assertSame('Error!', $log->message());
        self::assertSame(['!'], $log->context());
        self::assertSame(Logger::DEBUG, $log->level());
    }

    public function testFromWpErrorWithExplicitLevelAndChannel()
    {
        $error = \Mockery::mock(\WP_Error::class);

        $error->shouldReceive('get_error_message')->andReturn('Error!');
        $error->shouldReceive('get_error_data')->andReturn(['!']);
        $error->shouldReceive('get_error_codes')->andReturn(['x']);

        $log = Log::fromWpError($error, Logger::DEBUG, Channels::DB);

        self::assertSame(Channels::DB, $log->channel());
        self::assertSame('Error!', $log->message());
        self::assertSame(['!'], $log->context());
        self::assertSame(Logger::DEBUG, $log->level());
    }

    public function testFromThrowable()
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception);
        self::assertInstanceOf(Log::class, $log);

        $context = $log->context();
        self::assertIsArray($context);

        self::assertSame(Channels::DEBUG, $log->channel());
        self::assertSame('Fail!, Fail!', $log->message());
        self::assertSame(Logger::ERROR, $log->level());
        self::assertArrayHasKey('throwable', $context);
        self::assertSame($context['throwable']['class'], \Exception::class);
        self::assertSame($context['throwable']['file'], __FILE__);
        self::assertArrayHasKey('line', $context['throwable']);
        self::assertArrayHasKey('trace', $context['throwable']);
    }

    public function testFromThrowableWithExplicitLevel()
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception, Logger::DEBUG);
        self::assertInstanceOf(Log::class, $log);

        $context = $log->context();
        self::assertIsArray($context);

        self::assertSame(Channels::DEBUG, $log->channel());
        self::assertSame('Fail!, Fail!', $log->message());
        self::assertSame(Logger::DEBUG, $log->level());
        self::assertArrayHasKey('throwable', $context);
        self::assertSame($context['throwable']['class'], \Exception::class);
        self::assertSame($context['throwable']['file'], __FILE__);
        self::assertArrayHasKey('line', $context['throwable']);
        self::assertArrayHasKey('trace', $context['throwable']);
    }

    public function testFromThrowableWithExplicitLevelAndChannel()
    {
        $exception = new \Exception('Fail!, Fail!', 123);

        $log = Log::fromThrowable($exception, Logger::NOTICE, Channels::HTTP);
        self::assertInstanceOf(Log::class, $log);

        $context = $log->context();
        self::assertIsArray($context);

        self::assertSame(Channels::HTTP, $log->channel());
        self::assertSame('Fail!, Fail!', $log->message());
        self::assertSame(Logger::NOTICE, $log->level());
        self::assertArrayHasKey('throwable', $context);
        self::assertSame($context['throwable']['class'], \Exception::class);
        self::assertSame($context['throwable']['file'], __FILE__);
        self::assertArrayHasKey('line', $context['throwable']);
        self::assertArrayHasKey('trace', $context['throwable']);
    }

    public function testFromArray()
    {
        $log = Log::fromArray(
            [
                Log::MESSAGE => 'message',
                Log::LEVEL => Logger::EMERGENCY,
                Log::CHANNEL => Channels::HTTP,
                Log::CONTEXT => ['foo'],
            ]
        );

        self::assertSame(Channels::HTTP, $log->channel());
        self::assertSame('message', $log->message());
        self::assertSame(['foo'], $log->context());
        self::assertSame(Logger::EMERGENCY, $log->level());
    }

    public function testFromArrayMerged()
    {

        $log = Log::fromArray(
            [
                Log::MESSAGE => 'message',
                Log::CONTEXT => ['foo'],
            ]
        );

        self::assertSame(Channels::DEBUG, $log->channel());
        self::assertSame('message', $log->message());
        self::assertSame(['foo'], $log->context());
        self::assertSame(Logger::DEBUG, $log->level());
    }
}
