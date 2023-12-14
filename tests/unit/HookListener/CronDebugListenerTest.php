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

use Brain\Monkey;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Inpsyde\Wonolog\HookListener\CronDebugListener;
use Monolog\Logger;

class CronDebugListenerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testUpdateWhenNoCron(): void
    {
        Monkey\Functions\when('wp_doing_cron')->justReturn(false);
        Monkey\Functions\expect('_get_cron_array')->never();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->never();

        (new CronDebugListener())->update('a', [], $updater);
    }

    /**
     * @runInSeparateProcess
     */
    public function testUpdate(): void
    {
        Monkey\Functions\when('wp_doing_cron')->justReturn(true);

        $cronArray = [
            1593526488 => [
                'wp_version_check' => ['40cd750bba9870f18aada2478b24840a' => []],
                'wp_update_plugins' => ['40cd750bba9870f18aada2478b24840a' => []],
            ],
            1593526499 => [
                'wp_scheduled_delete' => ['40cd750bba9870f18aada2478b24840a' => []],
            ],
        ];

        Monkey\Functions\when('_get_cron_array')->justReturn($cronArray);

        /** @var callable|null $cb1 */
        $cb1 = null;
        /** @var callable|null $cb2 */
        $cb2 = null;
        /** @var callable|null $cb3 */
        $cb3 = null;

        Monkey\Actions\expectAdded('wp_version_check')->twice()->whenHappen(
            static function ($profileCallback) use (&$cb1): void {
                $cb1 = $profileCallback;
            }
        );

        Monkey\Actions\expectAdded('wp_update_plugins')->twice()->whenHappen(
            static function ($profileCallback) use (&$cb2): void {
                $cb2 = $profileCallback;
            }
        );

        Monkey\Actions\expectAdded('wp_scheduled_delete')->twice()->whenHappen(
            static function ($profileCallback) use (&$cb3): void {
                $cb3 = $profileCallback;
            }
        );

        $updater = \Mockery::mock(LogActionUpdater::class);

        $logs = [];
        $updater->expects('update')
            ->times(3)
            ->with(\Mockery::type(LogData::class))
            ->andReturnUsing(
                static function (LogData $log) use (&$logs): void {
                    $logs[] = $log->message();
                    static::assertSame(Logger::NOTICE, $log->level());
                }
            );

        (new CronDebugListener(Logger::NOTICE))->update('wp_loaded', [], $updater);

        static::assertIsCallable($cb1);
        static::assertIsCallable($cb2);
        static::assertIsCallable($cb3);

        $cb1();
        $cb2();
        $cb3();
        sleep(1);
        $cb3();
        $cb2();
        $cb1();

        $regxp = '~^Cron action "%s" performed\. Duration: [0|1]\.[0-9]+ seconds\.$~';

        static::assertMatchesRegularExpression(sprintf($regxp, 'wp_scheduled_delete'), $logs[0]);
        static::assertMatchesRegularExpression(sprintf($regxp, 'wp_update_plugins'), $logs[1]);
        static::assertMatchesRegularExpression(sprintf($regxp, 'wp_version_check'), $logs[2]);
    }
}
