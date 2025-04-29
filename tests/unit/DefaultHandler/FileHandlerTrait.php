<?php

declare( strict_types=1 );

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Brain\Monkey;
use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\DefaultHandler\HandlerFactoryInterface;
use Inpsyde\Wonolog\DefaultHandler\PassthroughFormatter;
use Inpsyde\Wonolog\Levels;
use Inpsyde\Wonolog\Processor\NullProcessor;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\LogRecord;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Assert;

trait FileHandlerTrait
{
    public function testInstanceOfFileHandler(): void
    {
        $this->assertInstanceOf(FileHandler::class, FileHandler::new());
    }

    public function testCustomPathOutsideContent(): void
    {
        $dir = $this->setupFolders();

        $actual = $this->makeSut()
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

        $actual = $this->makeSut()
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

        $this->makeSut()
            ->logFilePath();
    }

//    public function testThrowExceptionOnInvalidLogFileDir(): void
//    {
//        Monkey\Functions\when('wp_mkdir_p')->alias(static function (string $path): bool {
//            return true;
//        });
//
//        $this->expectException(\Exception::class);
//        $this->expectExceptionMessage('Could not determine valid log file path.');
//        $this->makeSut()
//            ->withFolder('.')
//            ->withFilename('/')
//            ->logFilePath();
//    }

    public function testThrowExceptionOnInvalidCreationOfLogFiledir(): void
    {
        $dir = $this->setupFolders();

        $call = 0;
        Monkey\Functions\when('wp_mkdir_p')->alias(static function (string $path) use (&$call): bool {
            $call++;
            return $call === 1;
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not create valid log file path.');

        $this->makeSut()
             ->withFolder($dir->url() . '/public/wp-content')
            ->logFilePath();
    }

    public function testThrowExceptionOnInvalidFolderNotWritable(): void
    {
        $dir = $this->setupFolders();

        Monkey\Functions\when('wp_mkdir_p')->alias(static function (string $path): bool {
            return true;
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not obtain valid log file path: not writable.');

        $this->makeSut()
             ->withFolder($dir->url() . '/public/wp-content')
            ->logFilePath();
    }

    public function testFileNamedByDate(): void
    {
        $dir = $this->setupFolders();
        $handler = $this->makeSut();
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

        $this->makeSut()
            ->withFolder($nonWritableDir)
            ->withFilename('wonolog.log')
            ->logFilePath();
    }

    public function testDateBasedFileFormat(): void
    {
        $format = 'Y/M/D';

        $dir = $this->setupFolders();
        $handler = $this->makeSut()
            ->withDateBasedFileFormat($format);
        $filename = $handler->logFilePath();
        static::assertSame($filename, $dir->url() . '/uploads/wonolog/' . \date($format) . '.log');
    }

    public function testMinimumLevel(): void
    {
        $this->setupFolders();

        $factory = new class implements HandlerFactoryInterface
        {
            private bool $call = false;
            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                Assert::assertSame(Levels::EMERGENCY, $level);
                $this->call = true;

                return new NullHandler();
            }

            public function isCalled(): bool
            {
                return $this->call;
            }
        };

        Assert::assertFalse($factory->isCalled());

        $handler = $this->makeSut($factory)
            ->withMinimumLevel(Levels::EMERGENCY);

        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('ensureHandler');
        $method->invoke($handler);

        static::assertTrue($factory->isCalled());
    }

    public function testDefaultBubble(): void
    {
        $handler = $this->makeSut();
        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('bubble');

        static::assertTrue($property->getValue($handler));
    }

    public function testEnableDisableBubbling(): void
    {
        $handler = $this->makeSut()
            ->disableBubbling();

        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('bubble');

        static::assertFalse($property->getValue($handler));

        $handler = FileHandler::new()
            ->enableBubbling();

        static ::assertTrue($property->getValue($handler));
    }

    public function testPassBubbleToHandler(): void
    {
        $this->setupFolders();

        $factory = new class implements HandlerFactoryInterface
        {
            private ?bool $bubble = null;
            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                $this->bubble = $bubble;
                return new NullHandler();
            }

            public function getBubble(): ?bool
            {
                return $this->bubble;
            }
        };

        Assert::assertNull($factory->getBubble());

        $handler = $this->makeSut($factory)
            ->disableBubbling();

        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('ensureHandler');
        $method->invoke($handler);

        static::assertFalse($factory->getBubble());
    }

    public function testDefaultBuffering(): void
    {
        $handler = FileHandler::new();
        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('buffering');

        static::assertTrue($property->getValue($handler));
    }

    public function testEnableDisableBuffering(): void
    {
        $handler = $this->makeSut()
            ->disableBuffering();

        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('buffering');

        static::assertFalse($property->getValue($handler));

        $handler = FileHandler::new()
            ->enableBuffering();

        static::assertTrue($property->getValue($handler));
    }

    public function testPassBufferingToHandler(): void
    {
        $this->setupFolders();

        $factory = new class implements HandlerFactoryInterface
        {
            private ?bool $buffering = null;

            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                $this->buffering = $buffering;
                return new NullHandler();
            }

            public function getBuffering(): ?bool
            {
                return $this->buffering;
            }
        };

        Assert::assertNull($factory->getBuffering());

        $handler = $this->makeSut($factory)
            ->disableBuffering();

        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('ensureHandler');
        $method->invoke($handler);

        static::assertFalse($factory->getBuffering());
    }

    public static function recordProvider(): \Generator
    {
        yield 'array record' => [
            [
                'message' => 'Test log',
                'level' => \Monolog\Logger::DEBUG
            ],
        ];

        yield 'LogRecord instance' => [
            new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'test',
                level: \Monolog\Level::Debug,
                message: 'Test log'
            )
        ];
    }

    /**
     * @dataProvider recordProvider
     */
    public function testHandle(array|LogRecord $record): void
    {
        $this->setupFolders();

        $handlerCalled = false;

        $factory = new class($record, $handlerCalled) implements HandlerFactoryInterface
        {
            public function __construct(
                private array|LogRecord $expectedRecord,
                private bool &$handlerCalled
            )
            {
            }

            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                return new class($this->expectedRecord, $this->handlerCalled) implements HandlerInterface
                {
                    public function __construct(
                        private array|LogRecord $expectedRecord,
                        private bool &$handlerCalled
                    )
                    {
                    }

                    public function handle(array|LogRecord $record): bool
                    {
                        Assert::assertSame($this->expectedRecord, $record);
                        $this->handlerCalled = true;
                        return true;
                    }

                    public function isHandling(array|LogRecord $record): bool
                    {
                        return true;
                    }

                    public function handleBatch(array $records): void {}
                    public function close(): void {}
                };
            }
        };

        $fileHandler = $this->makeSut($factory);
        $result = $fileHandler->handle($record);

        static::assertTrue($result);
        static::assertTrue($handlerCalled);
    }

    /**
     * @dataProvider recordProvider
     */
    public function testIsHandling(array|LogRecord $record): void
    {
        $this->setupFolders();

        $handlerCalled = false;

        $factory = new class($record, $handlerCalled) implements HandlerFactoryInterface
        {
            public function __construct(
                private array|LogRecord $expectedRecord,
                private bool &$handlerCalled
            )
            {
            }

            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                return new class($this->expectedRecord, $this->handlerCalled) implements HandlerInterface
                {
                    public function __construct(
                        private array|LogRecord $expectedRecord,
                        private bool &$handlerCalled
                    )
                    {
                    }

                    public function handle(array|LogRecord $record): bool
                    {
                        return true;
                    }

                    public function isHandling(array|LogRecord $record): bool
                    {
                        Assert::assertSame($this->expectedRecord, $record);
                        $this->handlerCalled = true;
                        return true;
                    }

                    public function handleBatch(array $records): void {}
                    public function close(): void {}
                };
            }
        };

        $fileHandler = $this->makeSut($factory);
        $result = $fileHandler->isHandling($record);

        static::assertTrue($result);
        static::assertTrue($handlerCalled);
    }

    public function testHandleBatch(): void
    {
        $this->setupFolders();

        $handlerCalled = false;
        $mock = \Mockery::mock(HandlerInterface::class);
        $mock->shouldReceive('close')
            ->once()
            ->andReturnNull();

        $mock->shouldReceive('handleBatch')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function (array $records) use (&$handlerCalled) {
                Assert::assertNotEmpty($records);
                Assert::assertIsArray($records[0]);
                Assert::assertInstanceOf(LogRecord::class, $records[1]);
                $handlerCalled = true;
            });

        $factory = new class($mock) implements HandlerFactoryInterface
        {
            public function __construct(
                private HandlerInterface $mock
            )
            {
            }

            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                return $this->mock;
            }
        };

        $fileHandler = $this->makeSut($factory);
        $fileHandler->handleBatch([
            [
                'message' => 'Test log',
                'level' => \Monolog\Logger::DEBUG,
                'context' => [],
                'extra' => [],
                'datetime' => new \DateTimeImmutable(),
                'channel' => 'test',
            ],
            new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: \Monolog\Level::Debug,
            message: 'Test log'
        )
        ]);

        static::assertTrue($handlerCalled);
    }

    public function testCloseWhenHandlerIsNull(): void
    {
        $mock = \Mockery::mock(HandlerInterface::class);
        $mock->shouldReceive('close')
            ->never();

        $factory = new class ($mock) implements HandlerFactoryInterface
        {
            public function __construct(private HandlerInterface $mock)
            {
            }

            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                return $this->mock;
            }
        };

        $fileHandler = $this->makeSut($factory);
        $fileHandler->close();
    }

    public function testCloseWhenHandlerIsSet(): void
    {
        $this->setupFolders();

        $mock = \Mockery::mock(HandlerInterface::class);
        $mock->shouldReceive('close')
             ->twice() // This happens because the `close` method is also called in the destructor
             ->andReturnNull();

        $factory = new class($mock) implements HandlerFactoryInterface
        {
            public function __construct(private HandlerInterface $mock)
            {
            }

            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                return $this->mock;
            }
        };

        $fileHandler = $this->makeSut($factory);

        $reflection = new \ReflectionClass($fileHandler);
        $method = $reflection->getMethod('ensureHandler');
        $method->invoke($fileHandler);

        $fileHandler->close();
    }

    public function testPushProcessorWithCorrectInstance(): void
    {
        $this->setupFolders();
        $handler = $this->makeSut();

        $processor = static fn(array $record): array => $record;

        $handler->pushProcessor($processor);
        $poppedProcessor = $handler->popProcessor();

        static::assertSame($processor, $poppedProcessor);
    }

    public function testPopProcessorWithNoProcessor(): void
    {
        $this->setupFolders();
        $factory = new class implements HandlerFactoryInterface
        {
            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                return new NullHandler();
            }
        };

        $handler = $this->makeSut($factory);
        $poppedProcessor = $handler->popProcessor();

        static::assertInstanceOf( NullProcessor::class, $poppedProcessor);
    }

    public function testSetAndGetFormatterWithCorrectInstance(): void
    {
        $this->setupFolders();
        $handler = $this->makeSut();

        $formatter = new JsonFormatter();
        $handler->setFormatter($formatter);

        static::assertSame($formatter, $handler->getFormatter());
    }

    public function testGetFormatterWithNoFormatterSet(): void
    {
        $this->setupFolders();
        $factory = new class implements HandlerFactoryInterface {
            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                return new NullHandler();
            }
        };
        $handler = $this->makeSut($factory);

        $formatter = $handler->getFormatter();

        static::assertInstanceOf( PassthroughFormatter::class, $formatter);
    }

    public function testProcessorAndFormatterAccessors(): void
    {
        $this->setupFolders();
        $handler = $this->makeSut();
        $formatter = new JsonFormatter();
        $processor = static function (array $record): array { return $record; };
        $handler->setFormatter($formatter);
        $handler->pushProcessor($processor);

        static::assertSame($formatter, $handler->getFormatter());
        static::assertSame($processor, $handler->popProcessor());
    }

    public function testResetWhenHandlerIsSet(): void
    {
        $this->setupFolders();

        $mock = \Mockery::mock(HandlerInterface::class, \Monolog\ResettableInterface::class);
        $mock->shouldReceive('close')
             ->once()
             ->andReturnNull();
        $mock->shouldReceive('reset')
             ->once()
             ->andReturnNull();

        $factory = new class($mock) implements HandlerFactoryInterface
        {
            public function __construct(private HandlerInterface $mock)
            {
            }

            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                return $this->mock;
            }
        };

        $fileHandler = $this->makeSut($factory);

        $reflection = new \ReflectionClass($fileHandler);
        $method = $reflection->getMethod('ensureHandler');
        $method->invoke($fileHandler);

        $fileHandler->reset();
    }

    public function testResetWhenHandlerIsNull(): void
    {
        $this->setupFolders();

        $mock = \Mockery::mock(HandlerInterface::class);
        $mock->shouldReceive('close')
             ->once();

        $mock->shouldReceive('reset')
                ->never();

        $factory = new class($mock) implements HandlerFactoryInterface
        {
            public function __construct(private HandlerInterface $mock)
            {
            }

            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                return $this->mock;
            }
        };

        $fileHandler = $this->makeSut($factory);
        $fileHandler->reset();

        static::assertTrue(true);
    }

    public function testEnsureHandlerInstantiatesOnce(): void
    {
        $this->setupFolders();

        $factory = new class implements HandlerFactoryInterface
        {
            private int $callCount = 0;

            public function make(string $logFilePath, int $level, bool $buffering, bool $bubble): HandlerInterface
            {
                $this->callCount++;
                return new NullHandler();
            }

            public function getCallCount(): int
            {
                return $this->callCount;
            }
        };

        static::assertSame(0, $factory->getCallCount());

        $handler = $this->makeSut($factory);
        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('ensureHandler');

        $method->invoke($handler);
        $method->invoke($handler);
        $method->invoke($handler);

        static::assertSame(1, $factory->getCallCount());
    }

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
