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

namespace Inpsyde\Wonolog\Tests\Unit\Handler;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Handler\DefaultHandlerFactory;
use Inpsyde\Wonolog\Handler\DateBasedStreamHandler;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class DefaultHandlerFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        Functions\when('wp_normalize_path')
            ->alias(
                static function (string $str): string {
                    return str_replace('\\', '/', $str);
                }
            );

        Functions\when('wp_mkdir_p')
            ->alias(
                static function (string $str): string {
                    return filter_var($str, FILTER_SANITIZE_URL) ?: '';
                }
            );

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('WONOLOG_DEFAULT_HANDLER_ROOT_DIR');
        parent::tearDown();
    }

    public function testEnforcedInstanceIsReturned()
    {
        $handler = \Mockery::mock(HandlerInterface::class);
        $handler->shouldReceive('close')->andReturnNull();
        $factory = DefaultHandlerFactory::new($handler);

        self::assertSame($handler, $factory->createDefaultHandler());
    }

    public function testDefaultHandlerIsNullIfNoFolder()
    {
        $factory = DefaultHandlerFactory::new();
        $handler = $factory->createDefaultHandler();

        self::assertInstanceOf(NullHandler::class, $handler);
    }

    public function testDefaultHandlerWithDirFromEnv()
    {
        $dir = str_replace('\\', '/', __DIR__);
        putenv('WONOLOG_DEFAULT_HANDLER_ROOT_DIR=' . $dir);

        $factory = DefaultHandlerFactory::new();

        /** @var DateBasedStreamHandler $handler */
        $handler = $factory->createDefaultHandler();

        self::assertInstanceOf(DateBasedStreamHandler::class, $handler);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultHandlerWithDirFromConstant()
    {
        $dir = str_replace('\\', '/', __DIR__);
        define('WP_CONTENT_DIR', $dir);

        $factory = DefaultHandlerFactory::new();

        /** @var DateBasedStreamHandler $handler */
        $handler = $factory->createDefaultHandler();

        self::assertInstanceOf(DateBasedStreamHandler::class, $handler);
    }

    public function testDefaultHandlerIsNullIfInvalidFileFormatFromHooks()
    {
        Filters\expectApplied(DefaultHandlerFactory::FILTER_FILENAME)
            ->once()
            ->andReturn('foo');

        $dir = str_replace('\\', '/', __DIR__);
        putenv('WONOLOG_DEFAULT_HANDLER_ROOT_DIR=' . $dir);

        $factory = DefaultHandlerFactory::new();

        $handler = $factory->createDefaultHandler();

        self::assertInstanceOf(NullHandler::class, $handler);
    }

    public function testDefaultHandlerIsNullIfInvalidDateFormatFromHooks()
    {
        Filters\expectApplied(DefaultHandlerFactory::FILTER_DATE_FORMAT)
            ->once()
            ->andReturn('meeeee');

        $dir = str_replace('\\', '/', __DIR__);
        putenv('WONOLOG_DEFAULT_HANDLER_ROOT_DIR=' . $dir);

        $factory = DefaultHandlerFactory::new();
        $handler = $factory->createDefaultHandler();

        self::assertInstanceOf(NullHandler::class, $handler);
    }

    public function estDefaultHandlerCustomPathFromHook()
    {
        Filters\expectApplied(DefaultHandlerFactory::FILTER_DATE_FORMAT)
            ->once()
            ->andReturn('mYd');

        Filters\expectApplied(DefaultHandlerFactory::FILTER_FILENAME)
            ->once()
            ->andReturn('wonolog/{date}.text');

        Filters\expectApplied(DefaultHandlerFactory::FILTER_FOLDER)
            ->once()
            ->andReturn('/etc/logs/');

        $factory = DefaultHandlerFactory::new();

        /** @var DateBasedStreamHandler $handler */
        $handler = $factory->createDefaultHandler();

        self::assertInstanceOf(DateBasedStreamHandler::class, $handler);

        $streamUrl = $handler->streamHandlerForRecord([])->getUrl();

        self::assertSame('/etc/logs/wonolog/' . date('mYd') . '.text', $streamUrl);
    }
}
