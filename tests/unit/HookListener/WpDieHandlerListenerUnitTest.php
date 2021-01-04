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

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookListener\WpDieHandlerListener;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Logger;

class WpDieHandlerListenerUnitTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testLogDoneOnBail(): void
    {
        /** @noinspection PhpIncludeInspection */
        require_once getenv('TESTS_PATH') . '/stubs/wpdb.php';

        $wpdb = new \wpdb('user', 'password', 'db', 'host');
        $wpdb->wp_die_listener = new WpDieHandlerListener(Logger::CRITICAL);

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->shouldReceive('update')
            ->once()
            ->andReturnUsing(static function (LogData $log) {
                static::assertSame(Logger::CRITICAL, $log->level());
                static::assertSame('Bailed!', $log->message());
                static::assertSame(Channels::DB, $log->channel());
            });

        $wpdb->logActionUpdater = $updater;

        static::assertSame('Handled: Bailed!', $wpdb->bail('Bailed!'));
    }

    /**
     * @test
     */
    public function testLogDoneOnPrintError(): void
    {
        require_once getenv('TESTS_PATH') . '/stubs/wpdb.php';

        $wpdb = new \wpdb('user', 'password', 'db', 'host');
        $wpdb->wp_die_listener = new WpDieHandlerListener();

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->shouldReceive('update')
            ->once()
            ->andReturnUsing(static function (LogData $log) {
                static::assertSame(Logger::CRITICAL, $log->level());
                static::assertSame('Error!', $log->message());
                static::assertSame(Channels::DB, $log->channel());
            });

        $wpdb->logActionUpdater = $updater;

        static::assertSame('Handled: Error!', $wpdb->print_error('Error!'));
    }
}
