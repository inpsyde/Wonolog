<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Configurator;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogActionUpdater;
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
        $log = \Mockery::mock(LogData::class);
        $log->shouldReceive('level')->never();

        $updater = LogActionUpdater::new(\Mockery::mock(Channels::class));
        $updater->update($log);
    }

    /**
     * @test
     */
    public function testUpdateDoNothingIfLogLevelIsZeroOrLower(): void
    {
        do_action(Configurator::ACTION_LOADED);

        $log = \Mockery::mock(LogData::class);
        $log->shouldReceive('level')->once()->andReturn(0);

        $channels = \Mockery::mock(Channels::class);
        $channels->shouldReceive('isIgnored')->never();

        $updater = LogActionUpdater::new($channels);
        $updater->update($log);
    }

    /**
     * @test
     */
    public function testUpdateDoNothingIfLogIsIgnored(): void
    {
        do_action(Configurator::ACTION_LOADED);

        $log = \Mockery::mock(LogData::class);
        $log->shouldReceive('level')->once()->andReturn(10);

        $channels = \Mockery::mock(Channels::class);
        $channels->shouldReceive('isIgnored')->once()->with($log)->andReturn(true);
        $channels->shouldReceive('logger')->never();

        $updater = LogActionUpdater::new($channels);
        $updater->update($log);
    }

    /**
     * @test
     */
    public function testUpdateTriggerActionOnError(): void
    {
        do_action(Configurator::ACTION_LOADED);

        $log = \Mockery::mock(LogData::class);
        $log->shouldReceive('level')->once()->andReturn(10);
        $log->shouldReceive('channel')->once()->andReturn(Channels::DEBUG);

        $channels = \Mockery::mock(Channels::class);
        $channels->shouldReceive('isIgnored')->once()->with($log)->andReturn(false);

        $error = new \Error();
        $channels->shouldReceive('logger')->with(Channels::DEBUG)->once()->andThrow($error);

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
        $channels->shouldReceive('isIgnored')->once()->with($log)->andReturn(false);
        $channels->shouldReceive('logger')->once()->with(Channels::SECURITY)->andReturn($logger);

        $updater = LogActionUpdater::new($channels);
        $updater->update($log);

        $record = $logger->records[0];

        static::assertSame('Test me', $record['message']);
        static::assertSame(LogLevel::EMERGENCY, $record['level']);
        static::assertSame(['foo' => 'bar'], $record['context']);
    }
}
