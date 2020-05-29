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
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\HookListener\WpDieHandlerListener;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpDieHandlerListenerTest extends TestCase
{

    public function testLogDoneOnBail()
    {

        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->once()
            ->with(\Mockery::type(Error::class))
            ->whenHappen(
                static function (Error $log): void {
                    static::assertSame(Channels::DB, $log->channel());
                    static::assertSame($log->message(), 'Bailed!');
                }
            );

        /** @noinspection PhpIncludeInspection */
        require_once getenv('TESTS_PATH') . '/stubs/wpdb.php';

        $wpdb = new \wpdb('user', 'password', 'db', 'host');
        $wpdb->wp_die_listener = new WpDieHandlerListener();

        static::assertSame('Handled: Bailed!', $wpdb->bail('Bailed!'));
    }

    public function testLogDoneOnPrintError()
    {
        Actions\expectDone(\Inpsyde\Wonolog\LOG)
            ->once()
            ->with(\Mockery::type(Error::class))
            ->whenHappen(
                static function (Error $log): void {
                    static::assertSame(Channels::DB, $log->channel());
                    static::assertSame($log->message(), 'Error!');
                }
            );

        require_once getenv('TESTS_PATH') . '/stubs/wpdb.php';

        $wpdb = new \wpdb('user', 'password', 'db', 'host');
        $wpdb->wp_die_listener = new WpDieHandlerListener();

        static::assertSame('Handled: Error!', $wpdb->print_error('Error!'));
    }
}
