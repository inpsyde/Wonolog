<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\PsrBridge;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class PsrBridgeTest extends UnitTestCase
{
    private LogData|null $logged;

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->logged = null;
    }

    /**
     * @test
     */
    public function testAutoBuildLog(): void
    {
        $bridge = $this->factoryBridge();
        $bridge->emergency('test {x}', ['x' => 'X!', 'y' => 'Y!']);

        $logged = $this->logged;
        static::assertInstanceOf(LogData::class, $logged);

        static::assertSame(LogLevel::EMERGENCY, $logged->level());
        static::assertSame(Channels::DEBUG, $logged->channel());
        static::assertSame('test X!', $logged->message());
        static::assertSame(['y' => 'Y!'], $logged->context());
    }

    /**
     * @test
     */
    public function testBuildLogWithDefaultChannel(): void
    {
        $bridge = $this->factoryBridge('CUSTOM');
        $bridge->emergency('test {x}', ['x' => 'X!', 'y' => 'Y!']);

        $logged = $this->logged;
        static::assertInstanceOf(LogData::class, $logged);

        static::assertSame(LogLevel::EMERGENCY, $logged->level());
        static::assertSame('CUSTOM', $logged->channel());
        static::assertSame('test X!', $logged->message());
        static::assertSame(['y' => 'Y!'], $logged->context());
    }

    /**
     * @test
     */
    public function testBuildLogWithManualChannel(): void
    {
        $bridge = $this->factoryBridge()->withDefaultChannel('MY_PLUGIN');
        $bridge->emergency('test {x}', ['x' => 'X!', 'y' => 'Y!']);

        $logged = $this->logged;
        static::assertInstanceOf(LogData::class, $logged);

        static::assertSame(LogLevel::EMERGENCY, $logged->level());
        static::assertSame('MY_PLUGIN', $logged->channel());
        static::assertSame('test X!', $logged->message());
        static::assertSame(['y' => 'Y!'], $logged->context());
    }

    /**
     * @test
     */
    public function testBuildLogWithManualChannelFromBadLevel(): void
    {
        $bridge = $this->factoryBridge()->withDefaultChannel('MY_PLUGIN');
        $bridge->log('foo', 'test {x}', ['x' => 'X!', 'y' => 'Y!']);

        $logged = $this->logged;
        static::assertInstanceOf(LogData::class, $logged);

        static::assertSame(LogLevel::DEBUG, $logged->level());
        static::assertSame('MY_PLUGIN', $logged->channel());
        static::assertSame('test X!', $logged->message());
        static::assertSame(['y' => 'Y!'], $logged->context());
    }

    /**
     * @param string $defaultChannel
     * @return PsrBridge
     */
    private function factoryBridge(string $defaultChannel = Channels::DEBUG): PsrBridge
    {
        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->andReturnUsing(function (LogData $log): void {
            $this->logged = $log;
        });

        $channels = \Mockery::mock(Channels::class);
        $channels->allows('defaultChannel')->andReturn($defaultChannel);
        $channels->allows('addChannel')->with(\Mockery::type('string'))->andReturnSelf();

        return PsrBridge::new($updater, $channels);
    }
}
