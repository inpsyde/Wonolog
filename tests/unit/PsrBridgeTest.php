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
use Inpsyde\Wonolog\HookLogFactory;
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
    public function testAutoBuildLogWithSerialization()
    {
        $obj = HookLogFactory::new();
        $post = \Mockery::mock('WP_Post');
        $post->ID = 123;
        $context = [
            'arr' => [
                'foo' => new \Exception('Foo'),
                'nul' => null,
                'na' => ['a', 'b', 'c' => ['c1' => 1, 'c2' => 2]],
            ],
            'sc' => (object)[
                'na' => ['a', 'b' => null, 'c'],
            ],
            'dt' => \DateTime::createFromFormat('Y-m-d H:i:s', '2017-05-26 05:45:00'),
            'obj' => $obj,
            'int' => 123,
            'res' => STDOUT,
            'post' => $post,
            'channel' => 'MY_CHANNEL',
        ];
        $expected = [
            'arr' => [
                'foo' => 'Exception: Foo',
                'nul' => null,
                'na' => ['a', 'b', 'c' => ['c1' => 1, 'c2' => 2]],
            ],
            'sc' => [
                'na' => ['a', 'b' => null, 'c'],
            ],
            'dt' => 'Fri, 26 May 2017 05:45:00 +0000',
            'obj' => sprintf('%s instance (#%s)', HookLogFactory::class, spl_object_hash($obj)),
            'int' => 123,
            'res' => 'Resource (stream)',
            'post' => 'WP_Post instance (ID: 123)',
        ];

        $this->factoryBridge()->withDefaultChannel('X')->info('Test', $context);

        static::assertSame(LogLevel::INFO, $this->logged->level());
        static::assertSame('MY_CHANNEL', $this->logged->channel());
        static::assertSame('Test', $this->logged->message());
        static::assertSame($expected, $this->logged->context());
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
    public function testBuildLogForException()
    {
        $bridge = $this->factoryBridge();
        $exception = new \Error('Failed {x}');
        $bridge->notice($exception, ['x' => 'X!', 'y' => 'Y!']);

        static::assertSame(LogLevel::NOTICE, $this->logged->level());
        static::assertSame(Channels::PHP_ERROR, $this->logged->channel());
        static::assertSame('Failed X!', $this->logged->message());
        static::assertSame(['y' => 'Y!', 'exception' => 'Error: Failed {x}'], $this->logged->context());
    }

    /**
     * @test
     */
    public function testBuildLogForExceptionWithDefaultChannel()
    {
        $bridge = $this->factoryBridge('CUSTOM');
        $exception = new \Error('Failed {x}');
        $bridge->notice($exception, ['x' => 'X!', 'y' => 'Y!']);

        static::assertSame(LogLevel::NOTICE, $this->logged->level());
        static::assertSame(Channels::PHP_ERROR, $this->logged->channel());
        static::assertSame('Failed X!', $this->logged->message());
        static::assertSame(['y' => 'Y!', 'exception' => 'Error: Failed {x}'], $this->logged->context());
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
    public function testBuildLogForExceptionWithManualChannel()
    {
        $bridge = $this->factoryBridge()->withDefaultChannel('MY_PLUGIN');
        $exception = new \Error('Failed {x}');
        $bridge->notice($exception, ['x' => 'X!', 'y' => 'Y!']);

        static::assertSame(LogLevel::NOTICE, $this->logged->level());
        static::assertSame('MY_PLUGIN', $this->logged->channel());
        static::assertSame('Failed X!', $this->logged->message());
        static::assertSame(['y' => 'Y!', 'exception' => 'Error: Failed {x}'], $this->logged->context());
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
     * @test
     */
    public function testBuildLogForExceptionWithManualChannelFromBadLevel()
    {
        $bridge = $this->factoryBridge()->withDefaultChannel('MY_PLUGIN');
        $exception = new \Error('Failed {x}');
        $bridge->log('meh', $exception, ['x' => 'X!', 'y' => 'Y!']);

        static::assertSame(LogLevel::CRITICAL, $this->logged->level());
        static::assertSame('MY_PLUGIN', $this->logged->channel());
        static::assertSame('Failed X!', $this->logged->message());
        static::assertSame(['y' => 'Y!', 'exception' => 'Error: Failed {x}'], $this->logged->context());
    }

    /**
     * @param string $defaultChannel
     * @return PsrBridge
     */
    private function factoryBridge(string $defaultChannel = Channels::DEBUG)
    {
        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->shouldReceive('update')->andReturnUsing(function (LogData $log): void {
            $this->logged = $log;
        });

        $channels = \Mockery::mock(Channels::class);
        $channels->shouldReceive('defaultChannel')->andReturn($defaultChannel);
        $channels->shouldReceive('addChannel')->with(\Mockery::type('string'))->andReturnSelf();

        return PsrBridge::new($updater, $channels);
    }
}
