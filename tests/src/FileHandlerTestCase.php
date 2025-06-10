<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests;

use Brain\Monkey;
use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\DefaultHandler\PassthroughFormatter;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Processor\NullProcessor;
use Monolog\Formatter\JsonFormatter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @runTestsInSeparateProcesses
 *
 * phpcs:disable SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
 */
abstract class FileHandlerTestCase extends UnitTestCase
{
    protected bool $buffered;

    /**
     * @test
     */
    public function testCustomPathOutsideContent(): void
    {
        $dir = $this->setupFolders()->url() . '/logs';
        $handler = $this->factoryHandler($dir, 'wonolog.log');

        static::assertSame("{$dir}/wonolog.log", $handler->logFilePath());
        static::assertTrue(is_dir($dir));
        static::assertFalse(file_exists("{$dir}/.htaccess"));
    }

    /**
     * @test
     */
    public function testCustomPathInsideContent(): void
    {
        $dir = $this->setupFolders()->url() . '/wp-content/logs';
        $handler = $this->factoryHandler($dir, 'wonolog.log');

        static::assertSame("{$dir}/wonolog.log", $handler->logFilePath());
        static::assertTrue(is_dir($dir));
        static::assertFalse(file_exists("{$dir}/.htaccess"));
    }

    /**
     * @test
     */
    public function testDefaultFolderWhenUploadsIsFound(): void
    {
        $handler = $this->factoryHandler(null, 'wonolog.log');

        static::assertSame(
            WP_CONTENT_DIR . '/uploads/wonolog/wonolog.log',
            $handler->logFilePath()
        );
    }

    /**
     * @test
     */
    public function testDefaultFolderWhenUploadsErrors(): void
    {
        $this->setupFolders(generateError: true);
        $handler = $this->factoryHandler(null, 'wonolog.log');

        static::assertSame(
            WP_CONTENT_DIR . '/wonolog/wonolog.log',
            $handler->logFilePath()
        );
    }

    /**
     * @test
     */
    public function testThrowExceptionOnInvalidFolder(): void
    {
        Monkey\Functions\when('wp_mkdir_p')->alias(
            static function (string $folder): bool {
                return $folder !== WP_CONTENT_DIR;
            }
        );
        $this->setupFolders(generateError: true);

        $this->expectExceptionMessageMatches('~valid.+?path~i');

        $this->factoryHandler(null)->logFilePath();
    }

    /**
     * @test
     */
    public function testThrowExceptionOnInvalidCreationOfDefaultLogFileDir(): void
    {
        Monkey\Functions\when('wp_mkdir_p')->justReturn(false);
        $this->expectExceptionMessageMatches('~create.+valid.+?path~i');

        $this->factoryHandler(null)->logFilePath();
    }

    /**
     * @test
     */
    public function testThrowExceptionOnInvalidCreationOfCustomLogFileDir(): void
    {
        $url = $this->setupFolders()->url() . '/logs';

        Monkey\Functions\when('wp_mkdir_p')->justReturn(false);
        $this->expectExceptionMessageMatches('~determine.+valid.+path~i');

        $this->factoryHandler($url)->logFilePath();
    }

    /**
     * @test
     */
    public function testThrowExceptionOnInvalidFolderNotWritable(): void
    {
        $dateFormat = 'Y';

        $url = $this->setupFolders()->url() . '/logs/' . date($dateFormat);
        mkdir($url, 0444, true);

        $this->expectExceptionMessageMatches('~valid.+?path.+writable~i');

        $this->factoryHandler($url)->withDateBasedFileFormat($dateFormat)->logFilePath();
    }

    /**
     * @test
     */
    public function testFileNamedByDate(): void
    {
        $dir = $this->setupFolders()->url() . '/uploads/wonolog';

        $handler = $this->factoryHandler($dir);
        $date = date('Y/m/d');

        static::assertSame($handler->logFilePath(), "{$dir}/{$date}.log");
    }

    /**
     * @test
     */
    public function testDateBasedFileFormat(): void
    {
        $format = '\H\e\l\l\o YMD';

        $dir = $this->setupFolders()->url() . '/logs';
        $handler = $this->factoryHandler($dir)
            ->withDateBasedFileFormat($format);

        static::assertSame("{$dir}/" . date($format) . '.log', $handler->logFilePath());
    }

    /**
     * @test
     */
    public function testHandle(): void
    {
        $dir = $this->setupFolders()->url() . '/logs';
        $handler = $this->factoryHandler($dir)->withMinimumLevel(LogLevel::DEBUG);

        $message = 'Test log message.';

        $records = $this->factoryRecords($message, random_int(1, 5));

        foreach ($records as $record) {
            $handler->handle($record);
        }

        if ($this->buffered) {
            $handler->reset();
        }

        $fileContents = (string) file_get_contents($handler->logFilePath());

        static::assertSame(count($records), substr_count($fileContents, $message));
    }

    /**
     * @test
     */
    public function testHandleBatch(): void
    {
        $dir = $this->setupFolders()->url() . '/logs';
        $handler = $this->factoryHandler($dir);

        $message = 'Test log message.';

        $records = $this->factoryRecords($message, random_int(2, 6));
        $handler->handleBatch($records);

        if ($this->buffered) {
            $handler->reset();
        }

        $fileContents = (string) file_get_contents($handler->logFilePath());

        static::assertSame(count($records), substr_count($fileContents, $message));
    }

    /**
     * @test
     */
    public function testPopProcessorReturnsLastPushed(): void
    {
        $handler = $this->factoryHandler(null);

        $processor1 = static fn (array $record): array => $record;
        $processor2 = static fn (array $record): array => $record;
        $processor3 = static fn (array $record): array => $record;

        $handler
            ->pushProcessor($processor1)
            ->pushProcessor($processor2)
            ->pushProcessor($processor3);

        static::assertSame($processor3, $handler->popProcessor());
    }

    /**
     * @test
     */
    public function testPopProcessorAccessorsOnError(): void
    {
        Monkey\Functions\when('wp_mkdir_p')->justReturn(false);

        $handler = $this->factoryHandler(null);
        $handler->pushProcessor(static fn (array $record): array => $record);

        static::assertInstanceOf(NullProcessor::class, $handler->popProcessor());
    }

    /**
     * @test
     */
    public function testFormatterAccessors(): void
    {
        $handler = $this->factoryHandler(null);

        $formatter = new JsonFormatter();
        $handler->setFormatter($formatter);

        static::assertSame($formatter, $handler->getFormatter());
    }

    /**
     * @test
     */
    public function testFormatterAccessorsOnError(): void
    {
        Monkey\Functions\when('wp_mkdir_p')->justReturn(false);

        $handler = $this->factoryHandler(null)->setFormatter(new JsonFormatter());

        static::assertInstanceOf(PassthroughFormatter::class, $handler->getFormatter());
    }

    /**
     * @param bool $generateError
     * @return vfsStreamDirectory
     */
    private function setupFolders(bool $generateError = false): vfsStreamDirectory
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

        define('WP_CONTENT_DIR', $dir->url() . '/public/wp-content');

        Monkey\Functions\when('wp_upload_dir')
            ->alias(
                static function () use ($generateError): array {
                    return $generateError
                        ? ['error' => 'error']
                        : ['basedir' => WP_CONTENT_DIR . '/uploads'];
                }
            );

        return $dir;
    }

    /**
     * @param string|null $logsDir
     * @param string|null $fileName
     * @return FileHandler
     */
    private function factoryHandler(?string $logsDir, ?string $fileName = null): FileHandler
    {
        if (!defined('WP_CONTENT_DIR')) {
            $this->setupFolders();
        }

        $handler = FileHandler::new();

        if ($logsDir !== null) {
            $handler = $handler->withFolder($logsDir);
        }
        if ($fileName !== null) {
            $handler = $handler->withFilename($fileName);
        }
        if (!$this->buffered) {
            $handler = $handler->disableBuffering();
        }

        return $handler;
    }
}
