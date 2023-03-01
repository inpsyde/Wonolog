<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Configurator;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel as WonologLogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;

class LogActionUpdaterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testUpdateDoNothingIfConfiguratorNotLoaded(): void
    {
        $channels = \Mockery::mock(Channels::class);
        $channels->expects('isIgnored')->never();

        $updater = LogActionUpdater::new($channels);
        $updater->update(new Log(''));
    }

    /**
     * @test
     */
    public function testUpdateDoNothingIfLogLevelIsZeroOrLower(): void
    {
        do_action(Configurator::ACTION_LOADED);

        $log = new Log('Test', 0);

        $channels = \Mockery::mock(Channels::class);
        $channels->expects('isIgnored')->never();

        $updater = LogActionUpdater::new($channels);
        $updater->update($log);
    }

    /**
     * @test
     */
    public function testUpdateDoNothingIfLogIsIgnored(): void
    {
        do_action(Configurator::ACTION_LOADED);

        $log = new Log('Message', WonologLogLevel::DEBUG);

        $channels = \Mockery::mock(Channels::class);
        $channels->expects('isIgnored')->with($log)->andReturn(true);
        $channels->expects('logger')->never();

        $updater = LogActionUpdater::new($channels);
        $updater->update($log);
    }

    /**
     * @test
     */
    public function testUpdateTriggerActionOnError(): void
    {
        do_action(Configurator::ACTION_LOADED);

        $log = new Log('Message', WonologLogLevel::DEBUG, Channels::DEBUG);

        $channels = \Mockery::mock(Channels::class);
        $channels->expects('isIgnored')->with($log)->andReturn(false);

        $error = new \Error();
        $channels->expects('logger')->with(Channels::DEBUG)->andThrow($error);

        Monkey\Actions\expectDone(LogActionUpdater::ACTION_LOGGER_ERROR)
            ->once()
            ->with($log, $error);

        $updater = LogActionUpdater::new($channels);
        $updater->update($log);
    }

    /**
     * @test
     */
    public function testUpdateLogsUsingPsrLogger(): void
    {
        do_action(Configurator::ACTION_LOADED);

        $log = new Log('Test me', Logger::EMERGENCY, Channels::SECURITY, ['foo' => 'bar']);

        $logger = new TestLogger();

        $channels = \Mockery::mock(Channels::class);
        $channels->expects('isIgnored')->with($log)->andReturn(false);
        $channels->expects('logger')->with(Channels::SECURITY)->andReturn($logger);

        $updater = LogActionUpdater::new($channels);
        $updater->update($log);

        $record = $logger->records[0];

        static::assertSame('Test me', $record['message']);
        static::assertSame(LogLevel::EMERGENCY, $record['level']);
        static::assertSame(['foo' => 'bar'], $record['context']);
    }

    /**
     * @test
     */
    public function testUpdateLogsUsingPsrLoggerWithMaskedInput(): void
    {
        do_action(Configurator::ACTION_LOADED);

        $context = [
            'users' => [
                (object)[
                    'user_login' => 'foo',
                    'user_password' => 'secret1',
                ],
                (object)[
                    'user_login' => 'bar',
                    'user_password' => 'secret2',
                ]
            ]
        ];

        $contextExpected = [
            'users' => [
                [
                    'user_login' => 'foo',
                    'user_password' => '***',
                ],
                [
                    'user_login' => 'bar',
                    'user_password' => '***',
                ]
            ]
        ];

        $log = new Log('Test me', Logger::EMERGENCY, Channels::SECURITY, $context);

        $logger = new TestLogger();

        $channels = \Mockery::mock(Channels::class);
        $channels->expects('isIgnored')->with($log)->andReturn(false);
        $channels->expects('logger')->with(Channels::SECURITY)->andReturn($logger);

        $updater = LogActionUpdater::new($channels);
        $updater->update($log);

        $record = $logger->records[0];

        static::assertSame('Test me', $record['message']);
        static::assertSame(LogLevel::EMERGENCY, $record['level']);
        static::assertSame($contextExpected, $record['context']);
    }
}
