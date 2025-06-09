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

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookListener\ActionListener;
use Inpsyde\Wonolog\HookListener\FilterFromUpdateTrait;
use Inpsyde\Wonolog\HookListener\FilterListener;
use Inpsyde\Wonolog\HookListener\MethodNamesByHookTrait;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class MethodNamesByHookTraitTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testMethodName(): void
    {
        $logMessages = [];

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->times(4)
            ->andReturnUsing(
                static function (LogData $log) use (&$logMessages): void {
                    $logMessages[] = $log->message();
                }
            );

        $listener = new class implements ActionListener
        {
            use MethodNamesByHookTrait;

            public function listenTo(): array
            {
                return ['hookOne', 'hook two', 'hook-three', 'hook.four', 'hookFive'];
            }

            // @phpstan-ignore method.unused
            private function hookOne(): Log
            {
                return new Log('one');
            }

            // @phpstan-ignore method.unused
            private function hookTwo(): Log
            {
                return new Log('two');
            }

            // @phpstan-ignore method.unused
            private function hookThree(): Log
            {
                return new Log('three');
            }

            // @phpstan-ignore method.unused
            private function hookFour(): Log
            {
                return new Log('four');
            }

            // @phpstan-ignore method.unused
            private function hookFive(): int
            {
                return 5;
            }
        };

        $listener->update('hookOne', [], $updater);
        $listener->update('hook two', [], $updater);
        $listener->update('hook-three', [], $updater);
        $listener->update('hook.four', [], $updater);
        $listener->update('hookFive', [], $updater);

        static::assertSame(['one', 'two', 'three', 'four'], $logMessages);
    }

    /**
     * @test
     */
    public function testMethodNameWithPrefix(): void
    {
        $logMessages = [];

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->times(3)
            ->andReturnUsing(
                static function (LogData $log) use (&$logMessages): void {
                    $logMessages[] = $log->message();
                }
            );

        $listener = new class implements ActionListener
        {
            use MethodNamesByHookTrait;

            public function listenTo(): array
            {
                return ['prefix.one', 'prefix_two', 'three'];
            }

            // @phpstan-ignore method.unused
            private function one(): Log
            {
                return new Log('one');
            }

            // @phpstan-ignore method.unused
            private function two(): Log
            {
                return new Log('two');
            }

            // @phpstan-ignore method.unused
            private function three(): Log
            {
                return new Log('three');
            }
        };

        $listener->withHookPrefix('prefix');

        $listener->update('prefix.one', [], $updater);
        $listener->update('prefix_two', [], $updater);
        $listener->update('three', [], $updater);

        static::assertSame(['one', 'two', 'three'], $logMessages);
    }

    /**
     * @test
     */
    public function testInCombinationWithFilterFromUpdateTrait(): void
    {
        $logMessage = null;

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->andReturnUsing(
                static function (LogData $log) use (&$logMessage): void {
                    $logMessage = $log->message();
                }
            );

        $listener = new class implements FilterListener {
            use MethodNamesByHookTrait;
            use FilterFromUpdateTrait;

            public function listenTo(): array
            {
                return ['client.project.log'];
            }

            // @phpstan-ignore method.unused
            private function log(): Log
            {
                return new Log('logged!');
            }
        };

        $listener->withHookPrefix('client.project');
        $value = new \stdClass();
        $filtered = $listener->filter('log', [$value], $updater);

        static::assertSame('logged!', $logMessage);
        static::assertSame($value, $filtered);
    }
}
