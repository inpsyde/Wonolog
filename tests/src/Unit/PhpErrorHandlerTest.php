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

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\PhpErrorController;
use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Tests\TestCase;
use Mockery;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 *
 * phpcs:disable WordPress.PHP.NoSilencedErrors
 */
class PhpErrorHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();

        parent::tearDown();
    }

    public function testOnErrorNotice()
    {
        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->once()
            ->with(Mockery::type(LogDataInterface::class))
            ->whenHappen(
                static function (LogDataInterface $log) {
                    static::assertSame(Channels::PHP_ERROR, $log->channel());
                    static::assertSame(Logger::NOTICE, $log->level());
                    static::assertSame('Meh!', $log->message());
                    $context = $log->context();
                    static::assertArrayHasKey('line', $context);
                    static::assertArrayHasKey('file', $context);
                    static::assertSame(__FILE__, $context['file']);
                }
            );

        $controller = new PhpErrorController();
        $this->initializeErrorController($controller);

        @trigger_error('Meh!', E_USER_NOTICE);
    }

    public function testOnErrorFatal()
    {
        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->once()
            ->with(Mockery::type(LogDataInterface::class))
            ->whenHappen(
                static function (LogDataInterface $log) {

                    static::assertSame(Channels::PHP_ERROR, $log->channel());
                    static::assertSame(Logger::WARNING, $log->level());
                    static::assertSame('Warning!', $log->message());
                    $context = $log->context();
                    static::assertArrayHasKey('line', $context);
                    static::assertArrayHasKey('file', $context);
                    static::assertSame(__FILE__, $context['file']);
                }
            );

        $controller = new PhpErrorController();
        $this->initializeErrorController($controller);

        @trigger_error('Warning!', E_USER_WARNING);
    }

    public function testOnErrorDoNoContainGlobals()
    {
        Actions\expectDone('wonolog.log')
            ->once()
            ->with(Mockery::type(LogDataInterface::class))
            ->whenHappen(
                static function (LogDataInterface $log) {
                    static::assertSame(Channels::PHP_ERROR, $log->channel());
                    static::assertSame(Logger::WARNING, $log->level());
                    $context = $log->context();
                    static::assertArrayHasKey('line', $context);
                    static::assertArrayHasKey('file', $context);
                    static::assertSame(__FILE__, $context['file']);
                    static::assertArrayHasKey('localVar', $context);
                    static::assertSame('I am local', $context['localVar']);
                    static::assertArrayNotHasKey('wp_filter', $context);
                }
            );
        $controller = new PhpErrorController();
        $this->initializeErrorController($controller);
        global $wp_filter;
        $wp_filter = ['foo', 'bar'];
        $localVar = 'I am local';
        /** @noinspection PhpUndefinedFunctionInspection */
        @meh();
    }

    public function testOnException()
    {
        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->once()
            ->with(Mockery::type(LogDataInterface::class))
            ->whenHappen(
                static function (LogDataInterface $log) {

                    static::assertSame(Channels::PHP_ERROR, $log->channel());
                    static::assertSame(Logger::CRITICAL, $log->level());
                    static::assertSame('Exception!', $log->message());
                    $context = $log->context();
                    static::assertArrayHasKey('line', $context);
                    static::assertArrayHasKey('trace', $context);
                    static::assertArrayHasKey('file', $context);
                    static::assertArrayHasKey('exception', $context);
                    static::assertSame(__FILE__, $context['file']);
                    static::assertSame(\RuntimeException::class, $context['exception']);
                }
            );

        $controller = new PhpErrorController();
        $this->initializeErrorController($controller);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Exception!');

        try {
            throw new \RuntimeException('Exception!');
        } catch (\Exception $throwable) {
            $controller->onException($throwable);
        }
    }

    private function initializeErrorController(PhpErrorController $controller)
    {
        register_shutdown_function([$controller, 'onFatal']);
        set_error_handler([$controller, 'onError']);
        set_exception_handler([$controller, 'onException']);
    }
}
