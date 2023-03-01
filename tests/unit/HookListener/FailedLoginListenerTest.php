<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

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

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookListener\FailedLoginListener;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class FailedLoginListenerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testLogDone(): void
    {
        Functions\when('get_site_transient')
            ->justReturn(['127.0.0.1' => ['count' => 2, 'last_logged' => 0]]);

        Functions\when('set_site_transient')
            ->justReturn(false);

        $listener = new FailedLoginListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')->with(\Mockery::type(FailedLogin::class));

        Actions\expectDone('wp_login_failed')
            ->once()
            ->whenHappen(
                static function () use ($listener, $updater): void {
                    $listener->update('a', func_get_args(), $updater);
                }
            );

        do_action($listener->listenTo()[0]);
    }

    /**
     * @test
     */
    public function testLogNotDoneIfNoLevel(): void
    {
        Functions\when('get_site_transient')
            ->justReturn(['127.0.0.1' => ['count' => 5, 'last_logged' => 3]]);

        Functions\when('set_site_transient')
            ->justReturn(false);

        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->never();

        $listener = new FailedLoginListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->with(\Mockery::type(FailedLogin::class))
            ->andReturnUsing(static function (LogData $log): void {
                static::assertSame(0, $log->level());
            });

        Actions\expectDone('wp_login_failed')
            ->once()
            ->whenHappen(
                static function () use ($listener, $updater): void {
                    $listener->update('a', func_get_args(), $updater);
                }
            );

        do_action($listener->listenTo()[0]);
    }
}
