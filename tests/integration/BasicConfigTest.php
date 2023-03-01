<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Integration;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Configurator;
use Inpsyde\Wonolog\Tests\IntegrationTestCase;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function Inpsyde\Wonolog\makeLogger;

/**
 * @runTestsInSeparateProcesses
 */
class BasicConfigTest extends IntegrationTestCase
{
    /**
     * @var TestHandler
     */
    private $handler;

    /**
     * @param Configurator $configurator
     * @return void
     */
    protected function bootstrapWonolog(Configurator $configurator): void
    {
        $this->handler = new TestHandler();
        $configurator->pushHandler($this->handler);
    }

    /**
     * @test
     */
    public function testLogFromArray(): void
    {
        do_action(
            'wonolog.log',
            [
                'message' => 'Something happened.',
                'channel' => Channels::DEBUG,
                'level' => LogLevel::NOTICE,
                'context' => ['foo'],
            ]
        );

        static::assertTrue($this->handler->hasNoticeThatContains('Something happened.'));
    }

    /**
     * @test
     */
    public function testLogFromWpError(): void
    {
        $error = new \WP_Error('test', 'Lorem ipsum dolor sit amet.');

        do_action('wonolog.log.emergency', $error);

        static::assertTrue($this->handler->hasEmergencyThatContains('Lorem ipsum dolor sit amet.'));
    }

    /**
     * @test
     */
    public function testLogFromThrowable(): void
    {
        try {
            throw new \Exception('Bla bla bla');
        } catch (\Throwable $throwable) {
            do_action('wonolog.log', $throwable);
        }

        static::assertTrue($this->handler->hasErrorThatContains('Bla bla bla'));
    }

    /**
     * @test
     */
    public function testLevelRichHook()
    {
        do_action('wonolog.log.info', 'Hello, I\'m there');

        static::assertTrue($this->handler->hasInfoThatContains('Hello, I\'m there'));
    }

    /**
     * @test
     */
    public function testPsrLogger()
    {
        $logger = makeLogger('test');
        $logger->alert('From PSR-3 with love.', ['foo' => 'bar']);

        static::assertTrue($this->handler->hasAlertThatContains('From PSR-3 with love.'));
    }
}
