<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookListener\WpDieHandlerListener;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class WpDieHandlerListenerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testLogDoneOnBail(): void
    {
        require_once getenv('TESTS_PATH') . '/stubs/wpdb.php';

        $wpdb = new \wpdb('user', 'password', 'db', 'host');
        // @phpstan-ignore property.notFound
        $wpdb->wp_die_listener = new WpDieHandlerListener(LogLevel::CRITICAL);

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->andReturnUsing(static function (LogData $log): void {
                static::assertSame(LogLevel::CRITICAL, $log->level());
                static::assertSame('Bailed!', $log->message());
                static::assertSame(Channels::DB, $log->channel());
            });

        $wpdb->logActionUpdater = $updater; // @phpstan-ignore property.notFound

        static::assertSame('Handled: Bailed!', $wpdb->bail('Bailed!'));
    }

    /**
     * @test
     */
    public function testLogDoneOnPrintError(): void
    {
        require_once getenv('TESTS_PATH') . '/stubs/wpdb.php';

        $wpdb = new \wpdb('user', 'password', 'db', 'host');
        $wpdb->wp_die_listener = new WpDieHandlerListener(); // @phpstan-ignore property.notFound

        $updater = \Mockery::mock(LogActionUpdater::class);
        $updater->expects('update')
            ->andReturnUsing(static function (LogData $log): void {
                static::assertSame(LogLevel::CRITICAL, $log->level());
                static::assertSame('Error!', $log->message());
                static::assertSame(Channels::DB, $log->channel());
            });

        $wpdb->logActionUpdater = $updater; // @phpstan-ignore property.notFound

        static::assertSame('Handled: Error!', $wpdb->print_error('Error!'));
    }
}
