<?php

declare( strict_types=1 );

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Brain\Monkey;
use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\DefaultHandler\HandlerFactoryInterface;
use Inpsyde\Wonolog\Levels;
use Monolog\Formatter\JsonFormatter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

trait FileHandlerTrait
{
    public function testInstanceOfFileHandler(): void
    {
        $this->assertInstanceOf(FileHandler::class, FileHandler::new());
    }

    public function testCustomPathOutsideContent(): void
    {
        $dir = $this->setupFolders();

        $actual = FileHandler::new()
            ->withFolder($dir->url() . '/logs')
            ->withFilename('wonolog.log')
            ->logFilePath();

        static::assertSame($dir->url() . '/logs/wonolog.log', $actual);
        static::assertTrue(is_dir($dir->url() . '/logs'));
        static::assertFalse(file_exists($dir->url() . '/logs/.htaccess'));
    }

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

    public function testThrowExceptionOnInvalidFolder(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not determine or create valid log file path.');

        FileHandler::new()
            ->withFolder('invalid-folder')
            ->logFilePath();
    }

    public function testFileNamedByDate(): void
    {
        $dir = $this->setupFolders();
        $handler = FileHandler::new();
        $filename = $handler->logFilePath();
        $date = date('Y/m/d');
        static::assertSame($filename, $dir->url() . '/uploads/wonolog/' . $date . '.log');
    }

    public function testThrowExceptionOnNonWritableFolder(): void
    {
        $dir = $this->setupFolders();
        $nonWritableDir = $dir->url() . '/non-writable';
        \mkdir($nonWritableDir, 0555);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not obtain valid log file path: not writable.');

        FileHandler::new()
            ->withFolder($nonWritableDir)
            ->withFilename('wonolog.log')
            ->logFilePath();
    }

    public function testDateBasedFileFormat(): void
    {
        $format = 'Y/M/D';

        $dir = $this->setupFolders();
        $handler = FileHandler::new()
        ->withDateBasedFileFormat($format);
        $filename = $handler->logFilePath();
        static::assertSame($filename, $dir->url() . '/uploads/wonolog/' . \date($format) . '.log');
    }

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
