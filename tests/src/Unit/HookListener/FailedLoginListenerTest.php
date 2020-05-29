<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\HookListener\FailedLoginListener;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class FailedLoginListenerTest extends TestCase
{
    public function testLogDone()
    {
        Functions\when('get_site_transient')
            ->justReturn(['127.0.0.1' => ['count' => 2, 'last_logged' => 0]]);

        Functions\when('set_site_transient')
            ->justReturn(false);

        $listener = new FailedLoginListener();

        Actions\expectDone('wp_login_failed')
            ->once()
            ->whenHappen(
                static function () use ($listener) {
                    $log = $listener->update(func_get_args());
                    static::assertInstanceOf(FailedLogin::class, $log);
                }
            );

        do_action($listener->listenTo()[0]);
    }

    public function testLogNotDoneIfNoLevel()
    {
        Functions\when('get_site_transient')
            ->justReturn(['127.0.0.1' => ['count' => 5, 'last_logged' => 3]]);

        Functions\when('set_site_transient')
            ->justReturn(false);

        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->never();

        $listener = new FailedLoginListener();

        Actions\expectDone('wp_login_failed')
            ->once()
            ->whenHappen(
                static function () use ($listener) {
                    $log = $listener->update(func_get_args());
                    static::assertInstanceOf(FailedLogin::class, $log);
                    static::assertSame(0, $log->level());
                }
            );

        do_action($listener->listenTo()[0]);
    }
}
