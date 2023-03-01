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

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Brain\Monkey;
use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Formatter\JsonFormatter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @runTestsInSeparateProcesses
 */
class BufferedFileHandlerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testCustomPathOutsideContent(): void
    {
        $dir = $this->setupFolders();

        $actual = FileHandler::new()
            ->withFolder($dir->url() . '/logs')
            ->withFilename('wonolog.log');

        static::assertSame($dir->url() . '/logs/wonolog.log', $actual->logFilePath());
        static::assertTrue(is_dir($dir->url() . '/logs'));
        static::assertFalse(file_exists($dir->url() . '/logs/.htaccess'));
    }

    /**
     * @test
     */
    public function testCustomPathInsideContent(): void
    {
        $dir = $this->setupFolders();

        $actual = FileHandler::new()
            ->withFolder($dir->url() . '/wp-content/logs')
            ->withFilename('wonolog.log')
            ->logFilePath();

        static::assertSame($dir->url() . '/wp-content/logs/wonolog.log', $actual);
        static::assertTrue(is_dir($dir->url() . '/wp-content/logs'));
        static::assertTrue(file_exists($dir->url() . '/wp-content/logs/.htaccess'));
    }

    /**
     * @test
     */
    public function testProcessorAndFormatterAccessors(): void
    {
        $this->setupFolders();
        $handler = FileHandler::new();
        $formatter = new JsonFormatter();
        $processor = static function (array $record): array { return $record; };
        $handler->setFormatter($formatter);
        $handler->pushProcessor($processor);

        static::assertSame($formatter, $handler->getFormatter());
        static::assertSame($processor, $handler->popProcessor());
    }

    /**
     * @param bool $uploadsOk
     * @return vfsStreamDirectory
     */
    private function setupFolders(bool $uploadsOk = true): vfsStreamDirectory
    {
        $dir = vfsStream::setup('root', 0777);
        vfsStream::create(
            [
                'public' => [
                    'wp' => ['wp-includes' => [], 'wp-admin' => []],
                    'wp-content' => [],
                    'uploads' => [],
                ],
            ],
            $dir
        );

        define('WP_CONTENT_DIR', $dir->url() . '/wp-content');

        Monkey\Functions\when('wp_upload_dir')
            ->alias(static function () use ($uploadsOk, $dir): array {
                return $uploadsOk ? ['basedir' => $dir->url() . '/uploads'] : ['error' => 'error'];
            });

        return $dir;
    }
}
