<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\PhpErrorController;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class PhpErrorHandlerTest extends UnitTestCase
{
    /**
     * @return void
     */
    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();

        parent::tearDown();
    }

    /**
     * @test
     */
    public function testOnErrorNotice(): void
    {
        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->andReturnUsing(
            static function (LogData $log): void {
                static::assertSame(Channels::PHP_ERROR, $log->channel());
                static::assertSame(LogLevel::NOTICE, $log->level());
                static::assertSame('Meh!', $log->message());
                $context = $log->context();
                static::assertArrayHasKey('line', $context);
                static::assertArrayHasKey('file', $context);
                static::assertSame(__FILE__, $context['file']);
            }
        );

        $controller = PhpErrorController::new(
            errorTypes: E_ALL,
            logExceptions: false,
            logSilencedErrors: true,
            updater: $updater
        );
        $controller->setup();

        @trigger_error('Meh!', E_USER_NOTICE);
    }

    /**
     * @test
     */
    public function testOnErrorFatal(): void
    {
        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->andReturnUsing(
            static function (LogData $log): void {
                static::assertSame(Channels::PHP_ERROR, $log->channel());
                static::assertSame(LogLevel::WARNING, $log->level());
                static::assertSame('Warning!', $log->message());
                $context = $log->context();
                static::assertArrayHasKey('line', $context);
                static::assertArrayHasKey('file', $context);
                static::assertSame(__FILE__, $context['file']);
            }
        );

        $controller = PhpErrorController::new(
            errorTypes: E_ALL,
            logExceptions: false,
            logSilencedErrors: true,
            updater: $updater
        );
        $controller->setup();

        @trigger_error('Warning!', E_USER_WARNING);
    }

    /**
     * @test
     */
    public function testOnException(): void
    {
        $message = 'Exception!';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->andReturnUsing(
            static function (LogData $log) use ($message): void {
                static::assertSame(Channels::PHP_ERROR, $log->channel());
                static::assertSame(LogLevel::CRITICAL, $log->level());
                static::assertSame($message, $log->message());
                $context = $log->context();
                static::assertArrayHasKey('line', $context);
                static::assertArrayHasKey('trace', $context);
                static::assertArrayHasKey('file', $context);
                static::assertArrayHasKey('exception', $context);
                static::assertSame(__FILE__, $context['file']);
                static::assertSame(\RuntimeException::class, $context['exception']);
            }
        );

        $controller = PhpErrorController::new(
            errorTypes: E_ALL,
            logExceptions: true,
            logSilencedErrors: false,
            updater: $updater
        );
        $controller->setup();

        try {
            throw new \RuntimeException($message);
        } catch (\Throwable $throwable) {
            $controller->onException($throwable);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function testDetectSilencedErrors(): void
    {
        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->never();

        $controller = PhpErrorController::new(
            errorTypes: E_ALL,
            logExceptions: false,
            logSilencedErrors: false,
            updater: $updater
        );
        $controller->setup();

        $test = static function (): void {
            trigger_error('Test', E_USER_WARNING);
        };

        @$test();
    }
}
