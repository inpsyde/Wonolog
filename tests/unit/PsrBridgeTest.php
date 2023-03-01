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

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\PsrBridge;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class PsrBridgeTest extends UnitTestCase
{
    /**
     * @var LogData|null
     */
    private $logged;

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
    public function testAutoBuildLog()
    {
        $bridge = $this->factoryBridge();
        $bridge->emergency('test {x}', ['x' => 'X!', 'y' => 'Y!']);

        static::assertSame(LogLevel::EMERGENCY, $this->logged->level());
        static::assertSame(Channels::DEBUG, $this->logged->channel());
        static::assertSame('test X!', $this->logged->message());
        static::assertSame(['y' => 'Y!'], $this->logged->context());
    }

    /**
     * @test
     */
    public function testBuildLogWithDefaultChannel()
    {
        $bridge = $this->factoryBridge('CUSTOM');
        $bridge->emergency('test {x}', ['x' => 'X!', 'y' => 'Y!']);

        static::assertSame(LogLevel::EMERGENCY, $this->logged->level());
        static::assertSame('CUSTOM', $this->logged->channel());
        static::assertSame('test X!', $this->logged->message());
        static::assertSame(['y' => 'Y!'], $this->logged->context());
    }

    /**
     * @test
     */
    public function testBuildLogWithManualChannel()
    {
        $bridge = $this->factoryBridge()->withDefaultChannel('MY_PLUGIN');
        $bridge->emergency('test {x}', ['x' => 'X!', 'y' => 'Y!']);

        static::assertSame(LogLevel::EMERGENCY, $this->logged->level());
        static::assertSame('MY_PLUGIN', $this->logged->channel());
        static::assertSame('test X!', $this->logged->message());
        static::assertSame(['y' => 'Y!'], $this->logged->context());
    }

    /**
     * @test
     */
    public function testBuildLogWithManualChannelFromBadLevel()
    {
        $bridge = $this->factoryBridge()->withDefaultChannel('MY_PLUGIN');
        $bridge->log('foo', 'test {x}', ['x' => 'X!', 'y' => 'Y!']);

        static::assertSame(LogLevel::DEBUG, $this->logged->level());
        static::assertSame('MY_PLUGIN', $this->logged->channel());
        static::assertSame('test X!', $this->logged->message());
        static::assertSame(['y' => 'Y!'], $this->logged->context());
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
